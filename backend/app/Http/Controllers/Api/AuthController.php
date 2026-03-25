<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Services\JwtService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends BaseApiController
{
    public function __construct(private readonly JwtService $jwt) {}

    /**
     * POST /api/auth/login
     */
    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email'      => 'required|email',
            'password'   => 'required|string|min:8',
            'hotel_slug' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors()->toArray());
        }

        $hotel = \App\Models\Hotel::where('slug', $request->hotel_slug)
            ->where('is_active', true)
            ->first();

        if (!$hotel) {
            return $this->error('Hotel not found or inactive', 404);
        }

        $user = User::where('hotel_id', $hotel->id)
            ->where('email', strtolower($request->email))
            ->first();

        if (!$user || !$user->is_active || !Hash::check($request->password, $user->password)) {
            return $this->error('Invalid credentials', 401);
        }

        $user->update(['last_login_at' => now()]);

        $payload = $this->buildUserPayload($user);
        $accessToken  = $this->jwt->generateAccessToken($payload);
        $refreshToken = $this->jwt->generateRefreshToken($payload);

        $this->logAudit($user->hotel_id, $user->id, 'user', 'logged_in');

        return $this->success([
            'access_token'  => $accessToken,
            'refresh_token' => $refreshToken,
            'token_type'    => 'Bearer',
            'expires_in'    => (int) config('jwt.ttl', 15) * 60,
            'user'          => $this->formatUser($user),
        ]);
    }

    /**
     * POST /api/auth/refresh
     */
    public function refresh(Request $request): JsonResponse
    {
        $token = $request->input('refresh_token') ?? '';
        if (!$token) {
            return $this->error('Refresh token required', 422);
        }

        try {
            $payload = $this->jwt->decode($token);
        } catch (\RuntimeException $e) {
            return $this->error('Invalid refresh token: ' . $e->getMessage(), 401);
        }

        if (($payload['type'] ?? '') !== 'refresh') {
            return $this->error('Invalid token type', 401);
        }

        $user = User::find($payload['sub'] ?? 0);
        if (!$user || !$user->is_active) {
            return $this->error('User not found or inactive', 401);
        }

        $newPayload     = $this->buildUserPayload($user);
        $accessToken    = $this->jwt->generateAccessToken($newPayload);
        $newRefreshToken = $this->jwt->generateRefreshToken($newPayload);

        return $this->success([
            'access_token'  => $accessToken,
            'refresh_token' => $newRefreshToken,
            'token_type'    => 'Bearer',
            'expires_in'    => (int) config('jwt.ttl', 15) * 60,
        ]);
    }

    /**
     * POST /api/auth/logout
     */
    public function logout(Request $request): JsonResponse
    {
        $authHeader = $request->header('Authorization', '');
        if (str_starts_with($authHeader, 'Bearer ')) {
            $token     = substr($authHeader, 7);
            $tokenHash = hash('sha256', $token);
            // Revoke the token
            \DB::table('revoked_tokens')->insertOrIgnore([
                'token_hash' => $tokenHash,
                'expires_at' => now()->addMinutes((int) config('jwt.ttl', 15)),
                'revoked_at' => now(),
            ]);
        }

        return $this->success(null, 'Logged out successfully');
    }

    /**
     * GET /api/auth/me
     */
    public function me(Request $request): JsonResponse
    {
        $user = $this->getUser($request);
        return $this->success($this->formatUser($user->fresh(['hotel'])));
    }

    // --------- helpers ---------

    private function buildUserPayload(User $user): array
    {
        return [
            'sub'      => $user->id,
            'hotel_id' => $user->hotel_id,
            'role'     => $user->role,
            'guard'    => 'user',
        ];
    }

    private function formatUser(User $user): array
    {
        return [
            'id'       => $user->id,
            'name'     => $user->name,
            'email'    => $user->email,
            'role'     => $user->role,
            'locale'   => $user->locale,
            'hotel_id' => $user->hotel_id,
            'hotel'    => $user->hotel ? [
                'id'   => $user->hotel->id,
                'name' => $user->hotel->name,
                'slug' => $user->hotel->slug,
                'plan' => $user->hotel->plan,
            ] : null,
        ];
    }

    private function logAudit(?int $hotelId, int $userId, string $userType, string $action): void
    {
        \DB::table('audit_log')->insert([
            'hotel_id'   => $hotelId,
            'user_id'    => $userId,
            'user_type'  => $userType,
            'action'     => $action,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'created_at' => now(),
        ]);
    }
}
