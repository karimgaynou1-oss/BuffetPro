<?php

namespace Tests;

use App\Models\Hotel;
use App\Models\User;
use App\Services\JwtService;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected ?Hotel $hotel = null;
    protected ?User $adminUser = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createTestHotelAndUser();
    }

    protected function createTestHotelAndUser(): void
    {
        $this->hotel = Hotel::create([
            'name'                => 'Test Hotel',
            'slug'                => 'test-hotel',
            'email'               => 'test@test-hotel.com',
            'country'             => 'MA',
            'locale'              => 'fr',
            'plan'                => 'pro',
            'subscription_status' => 'active',
            'max_users'           => 10,
            'is_active'           => true,
            'repetition_alert_hours' => 48,
        ]);

        $this->adminUser = User::create([
            'hotel_id' => $this->hotel->id,
            'name'     => 'Admin User',
            'email'    => 'admin@test-hotel.com',
            'password' => Hash::make('password123'),
            'role'     => 'hotel_admin',
            'is_active' => true,
        ]);
    }

    protected function getAuthHeaders(User $user = null): array
    {
        $user ??= $this->adminUser;
        $jwt = app(JwtService::class);
        $token = $jwt->generateAccessToken([
            'sub'      => $user->id,
            'hotel_id' => $user->hotel_id,
            'role'     => $user->role,
            'guard'    => 'user',
        ]);
        return ['Authorization' => 'Bearer ' . $token];
    }
}
