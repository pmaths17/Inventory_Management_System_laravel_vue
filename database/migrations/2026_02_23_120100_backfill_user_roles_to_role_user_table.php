<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $now = now();

        DB::table('roles')->upsert([
            [
                'name' => 'Admin',
                'slug' => 'admin',
                'is_system' => true,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Staff',
                'slug' => 'staff',
                'is_system' => true,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ], ['slug'], ['name', 'is_system', 'is_active', 'updated_at']);

        $rolesBySlug = DB::table('roles')
            ->whereIn('slug', ['admin', 'staff'])
            ->pluck('id', 'slug');

        $users = DB::table('users')->select('id', 'role')->get();

        foreach ($users as $user) {
            $slug = $user->role === 'admin' ? 'admin' : 'staff';
            $roleId = $rolesBySlug[$slug] ?? null;

            if (!$roleId) {
                continue;
            }

            DB::table('role_user')->updateOrInsert([
                'role_id' => $roleId,
                'user_id' => $user->id,
            ], [
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $adminRoleId = DB::table('roles')->where('slug', 'admin')->value('id');
        $staffRoleId = DB::table('roles')->where('slug', 'staff')->value('id');

        DB::table('role_user')->when($adminRoleId, fn ($query) => $query->orWhere('role_id', $adminRoleId))
            ->when($staffRoleId, fn ($query) => $query->orWhere('role_id', $staffRoleId))
            ->delete();
    }
};
