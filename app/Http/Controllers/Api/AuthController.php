<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;


class AuthController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);
        if (!Auth::attempt($credentials)) {
            Log::warning('Login failed', [
                'email' => (string) $request->input('email', ''),
                'ip' => $request->ip(),
            ]);
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
        // For SPA/session auth, explicitly log out via the web guard.
        if (Auth::guard('web')->check()) {
            Auth::guard('web')->logout();
        }

        // For token-based Sanctum auth, revoke current token if present.
        if ($request->user() && $request->user()->currentAccessToken()) {
            $request->user()->currentAccessToken()->delete();
        }

        if ($request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return response()->noContent(); // 204 OK
    }
}
