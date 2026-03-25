<?php

namespace App\Http\Middleware;

use App\Models\RevokedToken;
use App\Models\User;
use App\Services\JwtService;
use Closure;
use Illuminate\Http\Request;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

class JwtAuth
{
    public function __construct(private readonly JwtService $jwt) {}

    public function handle(Request $request, Closure $next, string $guard = 'user'): Response
    {
        $authHeader = $request->header('Authorization', '');
        if (!str_starts_with($authHeader, 'Bearer ')) {
            return response()->json(['error' => 'Unauthenticated', 'message' => 'Missing Bearer token'], 401);
        }

        $token = substr($authHeader, 7);

        try {
            $payload = $this->jwt->decode($token);
        } catch (RuntimeException $e) {
            return response()->json(['error' => 'Unauthenticated', 'message' => $e->getMessage()], 401);
        }

        if (($payload['type'] ?? '') !== 'access') {
            return response()->json(['error' => 'Unauthenticated', 'message' => 'Invalid token type'], 401);
        }

        // Check if token has been revoked
        $tokenHash = hash('sha256', $token);
        if (\DB::table('revoked_tokens')->where('token_hash', $tokenHash)->exists()) {
            return response()->json(['error' => 'Unauthenticated', 'message' => 'Token has been revoked'], 401);
        }

        if ($guard === 'super_admin') {
            $admin = \App\Models\SuperAdmin::find($payload['sub'] ?? 0);
            if (!$admin || !$admin->is_active || ($payload['guard'] ?? '') !== 'super_admin') {
                return response()->json(['error' => 'Unauthenticated', 'message' => 'Invalid super admin token'], 401);
            }
            $request->attributes->set('super_admin', $admin);
            $request->attributes->set('jwt_payload', $payload);
            return $next($request);
        }

        // Hotel user
        $user = User::where('id', $payload['sub'] ?? 0)
            ->where('hotel_id', $payload['hotel_id'] ?? 0)
            ->first();

        if (!$user || !$user->is_active) {
            return response()->json(['error' => 'Unauthenticated', 'message' => 'User not found or inactive'], 401);
        }

        $request->attributes->set('auth_user', $user);
        $request->attributes->set('jwt_payload', $payload);
        auth()->setUser($user);

        return $next($request);
    }
}
