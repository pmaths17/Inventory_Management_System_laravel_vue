<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class RoleController extends Controller
{
    public function index()
    {
        return response()->json(
            Role::withCount(['users', 'permissions'])
                ->with('permissions:id,name,slug,module')
                ->orderBy('name')
                ->get()
        );
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:100|unique:roles,name',
            'slug' => 'nullable|string|max:100|unique:roles,slug',
            'is_active' => 'nullable|boolean',
            'permission_ids' => 'nullable|array',
            'permission_ids.*' => 'integer|exists:permissions,id',
        ]);

        $role = Role::create([
            'name' => $data['name'],
            'slug' => $data['slug'] ?? Str::slug($data['name']),
            'is_active' => $data['is_active'] ?? true,
            'is_system' => false,
        ]);

        if (!empty($data['permission_ids'])) {
            $role->permissions()->sync($data['permission_ids']);
        }

        AuditLogger::log($request, 'role.created', 'role', $role->id, [
            'slug' => $role->slug,
            'permission_count' => count($data['permission_ids'] ?? []),
        ]);

        return response()->json([
            'message' => 'Role created successfully',
            'role' => $role->load('permissions:id,name,slug,module'),
        ], 201);
    }

    public function show($id)
    {
        return response()->json(
            Role::with(['permissions:id,name,slug,module', 'users:id,name,email'])->findOrFail($id)
        );
    }

    public function update(Request $request, $id)
    {
        $role = Role::findOrFail($id);

        $data = $request->validate([
            'name' => 'required|string|max:100|unique:roles,name,' . $role->id,
            'slug' => 'nullable|string|max:100|unique:roles,slug,' . $role->id,
            'is_active' => 'nullable|boolean',
            'permission_ids' => 'nullable|array',
            'permission_ids.*' => 'integer|exists:permissions,id',
        ]);

        if ($role->is_system && isset($data['slug']) && $data['slug'] !== $role->slug) {
            return response()->json([
                'message' => 'System role slug cannot be changed.',
            ], 422);
        }

        if ($role->slug === 'admin' && array_key_exists('is_active', $data) && $data['is_active'] === false) {
            return response()->json([
                'message' => 'Admin role cannot be archived.',
            ], 422);
        }

        if ($role->slug === 'admin' && array_key_exists('permission_ids', $data)) {
            $required = Permission::query()->pluck('id')->all();
            $incoming = $data['permission_ids'] ?? [];
            $missing = array_diff($required, $incoming);
            if (!empty($missing)) {
                return response()->json([
                    'message' => 'Admin role must keep all permissions.',
                ], 422);
            }
        }

        $role->update([
            'name' => $data['name'],
            'slug' => $data['slug'] ?? $role->slug,
            'is_active' => $data['is_active'] ?? $role->is_active,
        ]);

        if (array_key_exists('permission_ids', $data)) {
            $role->permissions()->sync($data['permission_ids'] ?? []);
        }

        AuditLogger::log($request, 'role.updated', 'role', $role->id, [
            'slug' => $role->slug,
            'is_active' => $role->is_active,
            'permission_count' => $role->permissions()->count(),
        ]);

        return response()->json([
            'message' => 'Role updated successfully',
            'role' => $role->load('permissions:id,name,slug,module'),
        ]);
    }

    public function destroy($id)
    {
        $role = Role::withCount('users')->findOrFail($id);

        if ($role->is_system) {
            return response()->json([
                'message' => 'System roles cannot be deleted.',
            ], 422);
        }

        if ($role->users_count > 0) {
            return response()->json([
                'message' => 'Reassign users before deleting this role.',
            ], 422);
        }

        AuditLogger::log(request(), 'role.deleted', 'role', $role->id, [
            'slug' => $role->slug,
        ]);
        $role->permissions()->detach();
        $role->delete();

        return response()->json([
            'message' => 'Role deleted successfully',
        ]);
    }
}
