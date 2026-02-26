<?php

namespace App\Services\Authorization;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\Exceptions\HttpResponseException;

class RoleManagementPolicy
{
    /**
     * @param array<string,mixed> $data
     */
    public function assertCanUpdate(Role $role, array $data): void
    {
        if ($role->is_system && isset($data['slug']) && $data['slug'] !== $role->slug) {
            throw new HttpResponseException(response()->json([
                'message' => 'System role slug cannot be changed.',
            ], 422));
        }

        if ($role->slug === 'admin' && array_key_exists('is_active', $data) && $data['is_active'] === false) {
            throw new HttpResponseException(response()->json([
                'message' => 'Admin role cannot be archived.',
            ], 422));
        }

        if ($role->slug === 'admin' && array_key_exists('permission_ids', $data)) {
            $required = Permission::query()->pluck('id')->all();
            $incoming = $data['permission_ids'] ?? [];
            $missing = array_diff($required, $incoming);
            if (!empty($missing)) {
                throw new HttpResponseException(response()->json([
                    'message' => 'Admin role must keep all permissions.',
                ], 422));
            }
        }
    }

    public function assertCanDelete(Role $role): void
    {
        if ($role->is_system) {
            throw new HttpResponseException(response()->json([
                'message' => 'System roles cannot be deleted.',
            ], 422));
        }

        if ($role->users()->exists()) {
            throw new HttpResponseException(response()->json([
                'message' => 'Reassign users before deleting this role.',
            ], 422));
        }
    }
}

