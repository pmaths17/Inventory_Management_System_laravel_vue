<?php

namespace App\Services\Authorization;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Exceptions\HttpResponseException;

class UserManagementPolicy
{
    /**
     * @param array<string,mixed> $data
     * @return array<int,int>
     */
    public function resolveRequestedRoleIds(array $data): array
    {
        if (!empty($data['role_ids']) && is_array($data['role_ids'])) {
            return collect($data['role_ids'])
                ->map(fn ($id) => (int) $id)
                ->filter(fn (int $id) => $id > 0)
                ->unique()
                ->values()
                ->all();
        }

        if (!empty($data['role_id'])) {
            return [(int) $data['role_id']];
        }

        return [];
    }

    /**
     * @param array<int,int> $roleIds
     * @return Collection<int,Role>
     */
    public function resolveAndValidateAssignableRoles(array $roleIds): Collection
    {
        if (empty($roleIds)) {
            throw new HttpResponseException(response()->json([
                'message' => 'At least one role is required.',
            ], 422));
        }

        $roles = Role::query()
            ->whereIn('id', $roleIds)
            ->get(['id', 'slug', 'is_active']);

        if ($roles->count() !== count($roleIds)) {
            throw new HttpResponseException(response()->json([
                'message' => 'One or more selected roles do not exist.',
            ], 422));
        }

        if ($roles->contains(fn (Role $role) => !$role->is_active)) {
            throw new HttpResponseException(response()->json([
                'message' => 'Cannot assign archived roles.',
            ], 422));
        }

        return $roles;
    }

    /**
     * @param Collection<int,Role> $newRoles
     */
    public function assertCanChangeUserRoles(User $actor, User $target, Collection $newRoles): void
    {
        $isTargetCurrentlyAdmin = $this->userHasActiveAdminRole($target);
        $isNewRoleAdmin = $newRoles->contains(fn (Role $role) => $role->slug === 'admin');
        $isSelf = (int) $actor->id === (int) $target->id;

        if ($isSelf && $isTargetCurrentlyAdmin && !$isNewRoleAdmin) {
            throw new HttpResponseException(response()->json([
                'message' => 'You cannot remove your own admin access.',
            ], 422));
        }

        if ($isTargetCurrentlyAdmin && !$isNewRoleAdmin && $this->activeAdminCount() <= 1) {
            throw new HttpResponseException(response()->json([
                'message' => 'At least one active admin user is required.',
            ], 422));
        }
    }

    public function assertCanDeleteUser(?User $actor, User $target): void
    {
        if ($actor && (int) $actor->id === (int) $target->id) {
            throw new HttpResponseException(response()->json([
                'message' => 'You cannot delete your own account from user management.',
            ], 422));
        }

        if ($this->userHasActiveAdminRole($target) && $this->activeAdminCount() <= 1) {
            throw new HttpResponseException(response()->json([
                'message' => 'Cannot delete the last active admin user.',
            ], 422));
        }
    }

    private function activeAdminCount(): int
    {
        return User::whereHas('roles', function ($query) {
            $query->where('slug', 'admin')->where('is_active', true);
        })->count();
    }

    private function userHasActiveAdminRole(User $user): bool
    {
        return $user->roles()->where('slug', 'admin')->where('is_active', true)->exists();
    }
}

