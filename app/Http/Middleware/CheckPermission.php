<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;
use App\Models\User;

class CheckPermission
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        /** @var User|null $user */
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $isAllowed = Gate::has($permission)
            ? Gate::forUser($user)->allows($permission)
            : ($user->hasRole('admin') || $user->hasPermission($permission));

        if ($isAllowed) {
            return $next($request);
        }

        return response()->json([
            'message' => "Forbidden: missing permission [{$permission}].",
        ], 403);
    }
}
