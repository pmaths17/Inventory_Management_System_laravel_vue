<?php

namespace Tests\Feature\Api;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Models\AuditLog;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RbacAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    private function createUserWithRole(string $roleSlug): User
    {
        $role = Role::where('slug', $roleSlug)->firstOrFail();
        $user = User::factory()->create([
            'role' => in_array($roleSlug, ['admin', 'staff'], true) ? $roleSlug : 'staff',
        ]);
        $user->roles()->sync([$role->id]);
        return $user;
    }

    public function test_staff_can_view_products_but_can_not_delete_products(): void
    {
        $staff = $this->createUserWithRole('staff');
        Sanctum::actingAs($staff);

        $this->getJson('/api/products')->assertOk();
        $this->deleteJson('/api/products/999999')->assertForbidden();
    }

    public function test_viewer_can_view_reports_but_can_not_create_sales(): void
    {
        $viewer = $this->createUserWithRole('viewer');
        Sanctum::actingAs($viewer);

        $this->getJson('/api/reports/dashboard-summary')->assertOk();
        $this->postJson('/api/sales', [])->assertForbidden();
    }

    public function test_admin_can_not_demote_self(): void
    {
        $admin = $this->createUserWithRole('admin');
        $staffRole = Role::where('slug', 'staff')->firstOrFail();
        Sanctum::actingAs($admin);

        $this->putJson("/api/users/{$admin->id}", [
            'name' => $admin->name,
            'role_ids' => [$staffRole->id],
            'password' => '',
        ])->assertStatus(422);
    }

    public function test_cannot_delete_last_active_admin(): void
    {
        $admin = $this->createUserWithRole('admin');

        $managerRole = Role::create([
            'name' => 'Manager',
            'slug' => 'manager',
            'is_system' => false,
            'is_active' => true,
        ]);
        $usersDeletePermissionId = Permission::where('slug', 'users.delete')->value('id');
        $managerRole->permissions()->sync([$usersDeletePermissionId]);

        $manager = User::factory()->create(['role' => 'staff']);
        $manager->roles()->sync([$managerRole->id]);

        Sanctum::actingAs($manager);
        $this->deleteJson("/api/users/{$admin->id}")
            ->assertStatus(422)
            ->assertJsonFragment(['message' => 'Cannot delete the last active admin user.']);
    }

    public function test_cannot_assign_archived_role_to_user(): void
    {
        $admin = $this->createUserWithRole('admin');
        $staff = $this->createUserWithRole('staff');

        $archivedRole = Role::create([
            'name' => 'Archived Role',
            'slug' => 'archived-role',
            'is_system' => false,
            'is_active' => false,
        ]);

        Sanctum::actingAs($admin);

        $this->putJson("/api/users/{$staff->id}", [
            'name' => $staff->name,
            'role_ids' => [$archivedRole->id],
            'password' => '',
        ])->assertStatus(422)
            ->assertJsonFragment(['message' => 'Cannot assign archived roles.']);
    }

    public function test_admin_role_must_keep_all_permissions(): void
    {
        $admin = $this->createUserWithRole('admin');
        $adminRole = Role::where('slug', 'admin')->firstOrFail();
        Sanctum::actingAs($admin);

        $this->putJson("/api/roles/{$adminRole->id}", [
            'name' => $adminRole->name,
            'slug' => $adminRole->slug,
            'is_active' => true,
            'permission_ids' => [],
        ])->assertStatus(422)
            ->assertJsonFragment(['message' => 'Admin role must keep all permissions.']);
    }

    public function test_rbac_actions_are_written_to_audit_log(): void
    {
        $admin = $this->createUserWithRole('admin');
        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/roles', [
            'name' => 'QA Role',
            'slug' => 'qa-role',
            'is_active' => true,
            'permission_ids' => [Permission::where('slug', 'products.view')->value('id')],
        ])->assertCreated();

        $roleId = $response->json('role.id');

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'role.created',
            'entity_type' => 'role',
            'entity_id' => (string) $roleId,
            'actor_user_id' => $admin->id,
        ]);

        $this->assertGreaterThanOrEqual(1, AuditLog::count());
    }

    public function test_user_with_multiple_roles_gets_union_of_permissions(): void
    {
        $admin = $this->createUserWithRole('admin');
        $viewerRole = Role::where('slug', 'viewer')->firstOrFail();
        $staffRole = Role::where('slug', 'staff')->firstOrFail();

        $target = User::factory()->create(['role' => 'staff']);
        $target->roles()->sync([$viewerRole->id]);

        Sanctum::actingAs($admin);
        $this->putJson("/api/users/{$target->id}", [
            'name' => $target->name,
            'role_ids' => [$viewerRole->id, $staffRole->id],
            'password' => '',
        ])->assertOk();

        Sanctum::actingAs($target->fresh());
        $this->getJson('/api/sales')->assertOk();
        $this->getJson('/api/reports/dashboard-summary')->assertOk();
    }

    public function test_api_user_endpoint_returns_only_active_roles(): void
    {
        $viewer = $this->createUserWithRole('viewer');
        $archivedRole = Role::create([
            'name' => 'Old Role',
            'slug' => 'old-role',
            'is_system' => false,
            'is_active' => false,
        ]);
        $viewer->roles()->syncWithoutDetaching([$archivedRole->id]);

        Sanctum::actingAs($viewer->fresh());
        $response = $this->getJson('/api/user')->assertOk();

        $returnedRoleSlugs = collect($response->json('roles'))->pluck('slug')->all();
        $this->assertContains('viewer', $returnedRoleSlugs);
        $this->assertNotContains('old-role', $returnedRoleSlugs);
    }

    public function test_permission_cache_is_invalidated_after_role_permission_change(): void
    {
        $admin = $this->createUserWithRole('admin');
        $staff = $this->createUserWithRole('staff');
        $staffRole = Role::where('slug', 'staff')->firstOrFail();
        $permissions = $staffRole->permissions()->pluck('permissions.id')->all();
        $productsViewPermissionId = Permission::where('slug', 'products.view')->value('id');
        $reducedPermissions = array_values(array_filter(
            $permissions,
            fn ($permissionId) => (int) $permissionId !== (int) $productsViewPermissionId
        ));

        Sanctum::actingAs($staff);
        $this->getJson('/api/products')->assertOk();

        Sanctum::actingAs($admin);
        $this->putJson("/api/roles/{$staffRole->id}", [
            'name' => $staffRole->name,
            'slug' => $staffRole->slug,
            'is_active' => true,
            'permission_ids' => $reducedPermissions,
        ])->assertOk();

        Sanctum::actingAs($staff->fresh());
        $this->getJson('/api/products')->assertForbidden();
    }
}
