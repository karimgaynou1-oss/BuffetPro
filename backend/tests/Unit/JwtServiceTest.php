<?php

namespace Tests\Unit;

use App\Models\Buffet;
use App\Models\Dish;
use App\Models\Hotel;
use App\Models\User;
use App\Services\JwtService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class JwtServiceTest extends TestCase
{
    use RefreshDatabase;

    private JwtService $jwt;

    protected function setUp(): void
    {
        parent::setUp();
        config(['jwt.secret' => 'test-secret-key-for-unit-testing-1234567890']);
        $this->jwt = new JwtService();
    }

    public function test_generate_and_decode_access_token(): void
    {
        $payload = ['sub' => 1, 'hotel_id' => 1, 'role' => 'hotel_admin', 'guard' => 'user'];
        $token   = $this->jwt->generateAccessToken($payload);

        $this->assertIsString($token);
        $decoded = $this->jwt->decode($token);

        $this->assertEquals(1, $decoded['sub']);
        $this->assertEquals('access', $decoded['type']);
    }

    public function test_generate_refresh_token(): void
    {
        $payload = ['sub' => 1, 'hotel_id' => 1, 'role' => 'chef', 'guard' => 'user'];
        $token   = $this->jwt->generateRefreshToken($payload);

        $decoded = $this->jwt->decode($token);
        $this->assertEquals('refresh', $decoded['type']);
    }

    public function test_expired_token_throws_exception(): void
    {
        config(['jwt.ttl' => -1]); // already expired
        $jwt = new JwtService();
        $token = $jwt->generateAccessToken(['sub' => 1]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('expired');

        $jwt->decode($token);
    }

    public function test_tampered_token_throws_exception(): void
    {
        $token  = $this->jwt->generateAccessToken(['sub' => 1]);
        $parts  = explode('.', $token);
        // Tamper the payload
        $parts[1] = base64_encode(json_encode(['sub' => 999, 'exp' => time() + 9999, 'type' => 'access']));
        $tampered = implode('.', $parts);

        $this->expectException(\RuntimeException::class);
        $this->jwt->decode($tampered);
    }
}
