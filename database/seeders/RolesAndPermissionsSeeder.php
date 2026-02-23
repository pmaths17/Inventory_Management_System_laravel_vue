<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissionMap = [
            'products' => ['view', 'create', 'update', 'delete'],
            'purchases' => ['view', 'create', 'update', 'delete'],
            'sales' => ['view', 'create', 'update', 'delete'],
            'customers' => ['view', 'create', 'update', 'delete'],
            'suppliers' => ['view', 'create', 'update', 'delete'],
            'reports' => ['view'],
            'users' => ['view', 'create', 'update', 'delete'],
            'roles' => ['view', 'create', 'update', 'delete'],
            'permissions' => ['view', 'create', 'update', 'delete'],
        ];

        $permissionIdsBySlug = [];

        foreach ($permissionMap as $module => $actions) {
            foreach ($actions as $action) {
                $slug = "{$module}.{$action}";
                $permission = Permission::query()->updateOrCreate(
                    ['slug' => $slug],
                    ['name' => ucfirst($action) . ' ' . ucfirst($module), 'module' => $module]
                );
                $permissionIdsBySlug[$slug] = $permission->id;
            }
        }

        $roles = [
            'admin' => [
                'name' => 'Admin',
                'is_system' => true,
                'permissions' => array_keys($permissionIdsBySlug),
            ],
            'staff' => [
                'name' => 'Staff',
                'is_system' => true,
                'permissions' => [
                    'products.view', 'products.create', 'products.update',
                    'purchases.view', 'purchases.create', 'purchases.update',
                    'sales.view', 'sales.create', 'sales.update',
                    'customers.view', 'customers.create', 'customers.update',
                    'suppliers.view', 'suppliers.create', 'suppliers.update',
                ],
            ],
            'viewer' => [
                'name' => 'Viewer',
                'is_system' => true,
                'permissions' => [
                    'products.view', 'purchases.view', 'sales.view',
                    'customers.view', 'suppliers.view', 'reports.view',
                ],
            ],
        ];

        foreach ($roles as $slug => $definition) {
            $role = Role::query()->updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => $definition['name'],
                    'is_system' => $definition['is_system'],
                    'is_active' => true,
                ]
            );

            $permissionIds = collect($definition['permissions'])
                ->map(fn ($permissionSlug) => $permissionIdsBySlug[$permissionSlug] ?? null)
                ->filter()
                ->values()
                ->all();

            $role->permissions()->sync($permissionIds);
        }

        $users = User::query()->get();
        foreach ($users as $user) {
            if ($user->roles()->exists()) {
                continue;
            }
            $slug = $user->role === 'admin' ? 'admin' : 'staff';
            $roleId = Role::query()->where('slug', $slug)->value('id');
            if ($roleId) {
                $user->roles()->sync([$roleId]);
            }
        }
    }
}
