<?php

namespace Database\Seeders;

use App\Models\Hotel;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class HotelSeeder extends Seeder
{
    public function run(): void
    {
        $hotel = Hotel::firstOrCreate(
            ['slug' => 'demo-hotel'],
            [
                'name'                => 'Demo Hotel & Resort',
                'slug'                => 'demo-hotel',
                'email'               => 'info@demo-hotel.com',
                'phone'               => '+212 522 000000',
                'country'             => 'MA',
                'locale'              => 'fr',
                'currency'            => 'MAD',
                'timezone'            => 'Africa/Casablanca',
                'plan'                => 'pro',
                'subscription_status' => 'trial',
                'trial_ends_at'       => now()->addDays(14),
                'max_users'           => 10,
                'repetition_alert_hours' => 48,
                'is_active'           => true,
            ]
        );

        $users = [
            [
                'name'  => 'Admin Hotel',
                'email' => 'admin@demo-hotel.com',
                'password' => Hash::make('Admin@123456'),
                'role'  => 'hotel_admin',
            ],
            [
                'name'  => 'Chef Hassan',
                'email' => 'chef@demo-hotel.com',
                'password' => Hash::make('Chef@123456'),
                'role'  => 'chef',
            ],
            [
                'name'  => 'Coordinateur Layla',
                'email' => 'coord@demo-hotel.com',
                'password' => Hash::make('Coord@123456'),
                'role'  => 'coordinator',
            ],
        ];

        foreach ($users as $userData) {
            User::firstOrCreate(
                ['hotel_id' => $hotel->id, 'email' => $userData['email']],
                array_merge($userData, ['hotel_id' => $hotel->id, 'is_active' => true])
            );
        }

        $this->command->info('Demo hotel created: slug=demo-hotel');
        $this->command->info('Users: admin@demo-hotel.com / chef@demo-hotel.com / coord@demo-hotel.com');
    }
}
