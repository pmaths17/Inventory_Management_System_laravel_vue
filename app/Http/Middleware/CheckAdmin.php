<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\User;

class CheckAdmin
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Get the authenticated user from the request
        /** @var User|null $user */
        $user = $request->user();

        // 2. Verify existence and role
        if ($user && $user->role === 'admin') {
            return $next($request);
        }

        // 3. Fail gracefully
        return response()->json([
            'message' => 'Access Denied: Admins only.'
        ], 403);
    }
}