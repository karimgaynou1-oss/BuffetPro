<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleCheck
{
    /**
     * Usage: role:hotel_admin,chef
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        /** @var \App\Models\User|null $user */
        $user = $request->attributes->get('auth_user');
        if (!$user || !$user->hasRole(...$roles)) {
            return response()->json([
                'error'   => 'Forbidden',
                'message' => 'You do not have permission to access this resource',
            ], 403);
        }
        return $next($request);
    }
}
