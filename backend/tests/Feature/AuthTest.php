<?php

namespace Tests\Feature;

use App\Models\Hotel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_with_valid_credentials(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'email'      => 'admin@test-hotel.com',
            'password'   => 'password123',
            'hotel_slug' => 'test-hotel',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'access_token',
                    'refresh_token',
                    'token_type',
                    'expires_in',
                    'user' => ['id', 'name', 'email', 'role', 'hotel_id'],
                ],
            ]);
    }

    public function test_login_with_invalid_password(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'email'      => 'admin@test-hotel.com',
            'password'   => 'wrong-password',
            'hotel_slug' => 'test-hotel',
        ]);

        $response->assertStatus(401);
    }

    public function test_login_with_invalid_hotel_slug(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'email'      => 'admin@test-hotel.com',
            'password'   => 'password123',
            'hotel_slug' => 'non-existent-hotel',
        ]);

        $response->assertStatus(404);
    }

    public function test_me_endpoint_returns_user(): void
    {
        $response = $this->withHeaders($this->getAuthHeaders())
            ->getJson('/api/auth/me');

        $response->assertStatus(200)
            ->assertJsonPath('data.email', 'admin@test-hotel.com')
            ->assertJsonPath('data.role', 'hotel_admin');
    }

    public function test_me_endpoint_requires_auth(): void
    {
        $response = $this->getJson('/api/auth/me');
        $response->assertStatus(401);
    }

    public function test_refresh_token(): void
    {
        $loginResponse = $this->postJson('/api/auth/login', [
            'email'      => 'admin@test-hotel.com',
            'password'   => 'password123',
            'hotel_slug' => 'test-hotel',
        ]);

        $refreshToken = $loginResponse->json('data.refresh_token');

        $response = $this->postJson('/api/auth/refresh', [
            'refresh_token' => $refreshToken,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['data' => ['access_token', 'refresh_token']]);
    }

    public function test_logout_revokes_token(): void
    {
        $headers = $this->getAuthHeaders();

        $this->withHeaders($headers)->postJson('/api/auth/logout')
            ->assertStatus(200);

        // After logout, the token should be revoked
        $this->withHeaders($headers)->getJson('/api/auth/me')
            ->assertStatus(401);
    }
}
