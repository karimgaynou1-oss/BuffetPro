<?php

namespace App\Http\Controllers\Api;

use App\Models\SuperAdmin;
use App\Services\JwtService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class SuperAdminAuthController extends BaseApiController
{
    public function __construct(private readonly JwtService $jwt) {}

    /**
     * POST /api/admin/auth/login
     */
    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors()->toArray());
        }

        $admin = SuperAdmin::where('email', strtolower($request->email))->first();

        if (!$admin || !$admin->is_active || !Hash::check($request->password, $admin->password)) {
            return $this->error('Invalid credentials', 401);
        }

        $admin->update(['last_login_at' => now()]);

        $payload = [
            'sub'   => $admin->id,
            'guard' => 'super_admin',
        ];

        $accessToken  = $this->jwt->generateAccessToken($payload);
        $refreshToken = $this->jwt->generateRefreshToken($payload);

        return $this->success([
            'access_token'  => $accessToken,
            'refresh_token' => $refreshToken,
            'token_type'    => 'Bearer',
            'expires_in'    => (int) config('jwt.ttl', 15) * 60,
            'admin'         => [
                'id'    => $admin->id,
                'name'  => $admin->name,
                'email' => $admin->email,
            ],
        ]);
    }
}
