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
        // Regenerate session for security
        $request->session()->regenerate();
        // Return empty 204 response (or user data if you want)
        return response()->noContent();
    }
    public function logout(Request $request)
    {
        Auth::logout(); // log out the user
        $request->session()->invalidate();       // invalidate session
        $request->session()->regenerateToken();  // prevent CSRF attacks
        return response()->noContent(); // 204 OK
    }
}
