<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use App\Models\User;

class UserController extends Controller
{
    // LIST USERS
    public function index()
    {
        return response()->json(
            User::orderBy('name')->paginate(15)
        );
    }

    // CREATE USER
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6',
            'role' => 'required|in:admin,staff',
        ]);

        $data['password'] = Hash::make($data['password']);

        return response()->json([
            'message' => 'User created',
            'user' => User::create($data)
        ]);
    }

    // UPDATE USER
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $data = $request->validate([
            'name' => 'required|string',
            'role' => 'required|in:admin,staff',
        ]);

        $user->update($data);

        return response()->json([
            'message' => 'User updated',
            'user' => $user
        ]);
    }

    // DELETE USER
    public function destroy($id)
    {
        User::findOrFail($id)->delete();

        return response()->json(['message' => 'User deleted']);
    }
}
