<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PermissionController extends Controller
{
    public function index()
    {
        return response()->json(
            Permission::orderBy('module')->orderBy('name')->get()
        );
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:100|unique:permissions,name',
            'slug' => 'nullable|string|max:100|unique:permissions,slug',
            'module' => 'nullable|string|max:100',
        ]);

        $permission = DB::transaction(function () use ($data, $request) {
            $permission = Permission::create([
                'name' => $data['name'],
                'slug' => $data['slug'] ?? Str::slug($data['name'], '.'),
                'module' => $data['module'] ?? null,
            ]);

            AuditLogger::log($request, 'permission.created', 'permission', $permission->id, [
                'slug' => $permission->slug,
                'module' => $permission->module,
            ]);

            return $permission;
        });

        return response()->json([
            'message' => 'Permission created successfully',
            'permission' => $permission,
        ], 201);
    }

    public function show($id)
    {
        return response()->json(Permission::findOrFail($id));
    }

    public function update(Request $request, $id)
    {
        $permission = Permission::findOrFail($id);

        $data = $request->validate([
            'name' => 'required|string|max:100|unique:permissions,name,' . $permission->id,
            'slug' => 'nullable|string|max:100|unique:permissions,slug,' . $permission->id,
            'module' => 'nullable|string|max:100',
        ]);

        DB::transaction(function () use ($data, $request, $permission) {
            $permission->update([
                'name' => $data['name'],
                'slug' => $data['slug'] ?? $permission->slug,
                'module' => $data['module'] ?? $permission->module,
            ]);

            AuditLogger::log($request, 'permission.updated', 'permission', $permission->id, [
                'slug' => $permission->slug,
                'module' => $permission->module,
            ]);
        });
        User::forgetPermissionCacheForPermission($permission->id);

        return response()->json([
            'message' => 'Permission updated successfully',
            'permission' => $permission,
        ]);
    }

    public function destroy($id)
    {
        $permission = Permission::withCount('roles')->findOrFail($id);

        if ($permission->roles_count > 0) {
            return response()->json([
                'message' => 'Remove this permission from all roles before deletion.',
            ], 422);
        }

        DB::transaction(function () use ($permission) {
            AuditLogger::log(request(), 'permission.deleted', 'permission', $permission->id, [
                'slug' => $permission->slug,
            ]);
            $permission->delete();
        });
        User::forgetPermissionCacheForPermission($permission->id);

        return response()->json([
            'message' => 'Permission deleted successfully',
        ]);
    }
}
