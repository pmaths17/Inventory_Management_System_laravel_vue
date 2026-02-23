<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Role;
use App\Services\AuditLogger;

class UserController extends Controller
{
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

    // LIST USERS
    public function index()
    {
        return response()->json(
            User::with('roles:id,name,slug,is_active')
                ->orderBy('name')
                ->paginate(15)
        );
    }

    // CREATE USER
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6',
            'role_id' => 'required|integer|exists:roles,id',
        ]);

        $role = Role::findOrFail($data['role_id']);
        if (!$role->is_active) {
            return response()->json(['message' => 'Cannot assign an archived role.'], 422);
        }

        $data['password'] = Hash::make($data['password']);
        unset($data['role_id']);

        $user = User::create($data);
        $user->assignRole($role);

        AuditLogger::log($request, 'user.created', 'user', $user->id, [
            'assigned_role' => $role->slug,
        ]);

        return response()->json([
            'message' => 'User created',
            'user' => $user->load('roles:id,name,slug,is_active')
        ]);
    }

    // SHOW USER
    public function show($id)
    {
        return response()->json(
            User::with('roles:id,name,slug,is_active')->findOrFail($id)
        );
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $data = $request->validate([
            'name' => 'required|string',
            'role_id' => 'required|integer|exists:roles,id',
            'password' => 'nullable|min:6', // Add this
        ]);

        $role = Role::findOrFail($data['role_id']);
        if (!$role->is_active) {
            return response()->json(['message' => 'Cannot assign an archived role.'], 422);
        }

        $isTargetCurrentlyAdmin = $this->userHasActiveAdminRole($user);
        $isNewRoleAdmin = $role->slug === 'admin';
        $isSelf = (int) $request->user()->id === (int) $user->id;

        if ($isSelf && $isTargetCurrentlyAdmin && !$isNewRoleAdmin) {
            return response()->json([
                'message' => 'You cannot remove your own admin access.',
            ], 422);
        }

        if ($isTargetCurrentlyAdmin && !$isNewRoleAdmin && $this->activeAdminCount() <= 1) {
            return response()->json([
                'message' => 'At least one active admin user is required.',
            ], 422);
        }

        if (!empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']); // Don't overwrite with null if empty
        }

        unset($data['role_id']);

        $user->update($data);
        $user->assignRole($role);

        AuditLogger::log($request, 'user.updated', 'user', $user->id, [
            'assigned_role' => $role->slug,
        ]);

        return response()->json([
            'message' => 'User updated',
            'user' => $user->load('roles:id,name,slug,is_active')
        ]);
    }

    // DELETE USER
    public function destroy($id)
    {
        /** @var User $target */
        $target = User::findOrFail($id);
        $actor = request()->user();

        if ($actor && (int) $actor->id === (int) $target->id) {
            return response()->json([
                'message' => 'You cannot delete your own account from user management.',
            ], 422);
        }

        if ($this->userHasActiveAdminRole($target) && $this->activeAdminCount() <= 1) {
            return response()->json([
                'message' => 'Cannot delete the last active admin user.',
            ], 422);
        }

        $request = request();
        AuditLogger::log($request, 'user.deleted', 'user', $target->id, [
            'email' => $target->email,
        ]);
        $target->delete();

        return response()->json(['message' => 'User deleted']);
    }
}
