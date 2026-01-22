<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;


class AuthController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (!Auth::attempt($credentials)) {
            return response()->json([
                'message' => 'Invalid credentials'
            ], 401);
        }

        // $user = Auth::user();

        // // Create token
        // $token = $user->createToken('api-token')->plainTextToken;

        // return response()->json([
        //     'token' => $token,
        //     'user' => $user,
        // ]);


        // Regenerate session for security
        $request->session()->regenerate();

        // Return empty 204 response (or user data if you want)
        return response()->noContent();
    }

    public function logout(Request $request)
    {
        // $request->user()->tokens()->delete();

        // return response()->json([
        //     'message' => 'Logged out'
        // ]);

        Auth::logout(); // log out the user

        $request->session()->invalidate();       // invalidate session
        $request->session()->regenerateToken();  // prevent CSRF attacks

        return response()->noContent(); // 204 OK
    }
    public function register(Request $request)
    {
        // Validate input
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6|confirmed', // expects password_confirmation
            'role' => 'required|in:admin,staff',
        ]);

        // Hash password
        $data['password'] = bcrypt($data['password']);

        // Create user
        $user = \App\Models\User::create($data);

        // // Create token
        // $token = $user->createToken('api-token')->plainTextToken;

        // // Return response
        // return response()->json([
        //     'message' => 'User registered successfully',
        //     'user' => $user,
        //     'token' => $token,
        // ], 201);
        Auth::login($user);
        $request->session()->regenerate();

        return response()->noContent();
    }
}
