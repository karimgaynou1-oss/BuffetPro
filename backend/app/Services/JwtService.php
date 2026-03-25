<?php

namespace App\Services;

use RuntimeException;

class JwtService
{
    private string $secret;
    private int $ttl; // minutes
    private int $refreshTtl; // minutes

    public function __construct()
    {
        $this->secret = (string) config('jwt.secret', env('JWT_SECRET', ''));
        $this->ttl = (int) config('jwt.ttl', 15);
        $this->refreshTtl = (int) config('jwt.refresh_ttl', 10080);
    }

    /**
     * Generate an access token.
     */
    public function generateAccessToken(array $payload): string
    {
        $now = time();
        $claims = array_merge($payload, [
            'iat' => $now,
            'exp' => $now + ($this->ttl * 60),
            'type' => 'access',
        ]);
        return $this->encode($claims);
    }

    /**
     * Generate a refresh token.
     */
    public function generateRefreshToken(array $payload): string
    {
        $now = time();
        $claims = array_merge($payload, [
            'iat' => $now,
            'exp' => $now + ($this->refreshTtl * 60),
            'type' => 'refresh',
        ]);
        return $this->encode($claims);
    }

    /**
     * Decode and validate a token.
     *
     * @throws RuntimeException
     */
    public function decode(string $token): array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            throw new RuntimeException('Invalid token format');
        }

        [$header64, $payload64, $sig64] = $parts;

        $expectedSig = $this->sign($header64 . '.' . $payload64);
        if (!hash_equals($expectedSig, $sig64)) {
            throw new RuntimeException('Token signature mismatch');
        }

        $payload = json_decode(self::base64UrlDecode($payload64), true);
        if (!is_array($payload)) {
            throw new RuntimeException('Token payload decode error');
        }

        if (isset($payload['exp']) && $payload['exp'] < time()) {
            throw new RuntimeException('Token has expired');
        }

        return $payload;
    }

    // ---------- private helpers ----------

    private function encode(array $claims): string
    {
        $header = self::base64UrlEncode((string) json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
        $payload = self::base64UrlEncode((string) json_encode($claims));
        $sig = $this->sign($header . '.' . $payload);
        return $header . '.' . $payload . '.' . $sig;
    }

    private function sign(string $data): string
    {
        return self::base64UrlEncode(hash_hmac('sha256', $data, $this->secret, true));
    }

    private static function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private static function base64UrlDecode(string $data): string
    {
        return base64_decode(strtr($data, '-_', '+/') . str_repeat('=', 4 - strlen($data) % 4));
    }
}
