<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\Authorization\UserManagementPolicy;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    public function __construct(
        private readonly UserManagementPolicy $policy
    ) {
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
            'password' => ['required', Password::min(8)->letters()->numbers()],
            'role_id' => 'nullable|integer|exists:roles,id',
            'role_ids' => 'nullable|array',
            'role_ids.*' => 'integer|distinct|exists:roles,id',
        ]);

        $roleIds = $this->policy->resolveRequestedRoleIds($data);
        $roles = $this->policy->resolveAndValidateAssignableRoles($roleIds);

        $data['password'] = Hash::make($data['password']);
        unset($data['role_id'], $data['role_ids']);

        $user = DB::transaction(function () use ($data, $roles, $request) {
            $user = User::create($data);
            $user->syncRoles($roles);

            AuditLogger::log($request, 'user.created', 'user', $user->id, [
                'assigned_roles' => $roles->pluck('slug')->values()->all(),
            ]);

            return $user;
        });

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
            'role_id' => 'nullable|integer|exists:roles,id',
            'role_ids' => 'nullable|array',
            'role_ids.*' => 'integer|distinct|exists:roles,id',
            'password' => ['nullable', Password::min(8)->letters()->numbers()],
        ]);

        $roleIds = $this->policy->resolveRequestedRoleIds($data);
        $roles = $this->policy->resolveAndValidateAssignableRoles($roleIds);
        $this->policy->assertCanChangeUserRoles($request->user(), $user, $roles);

        if (!empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']); // Don't overwrite with null if empty
        }

        unset($data['role_id'], $data['role_ids']);

        DB::transaction(function () use ($user, $data, $roles, $request) {
            $user->update($data);
            $user->syncRoles($roles);

            AuditLogger::log($request, 'user.updated', 'user', $user->id, [
                'assigned_roles' => $roles->pluck('slug')->values()->all(),
            ]);
        });

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
        $this->policy->assertCanDeleteUser($actor, $target);

        $request = request();
        DB::transaction(function () use ($request, $target) {
            AuditLogger::log($request, 'user.deleted', 'user', $target->id, [
                'email' => $target->email,
            ]);
            $target->delete();
        });
        $target->forgetPermissionCache();

        return response()->json(['message' => 'User deleted']);
    }
}
