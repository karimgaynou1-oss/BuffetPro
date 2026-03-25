<?php

namespace Database\Seeders;

use App\Models\SuperAdmin;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        SuperAdmin::firstOrCreate(
            ['email' => 'admin@buffetpro.com'],
            [
                'name'     => 'BuffetPro Admin',
                'email'    => 'admin@buffetpro.com',
                'password' => Hash::make('Admin@123456'),
                'is_active' => true,
            ]
        );

        $this->command->info('Super admin created: admin@buffetpro.com / Admin@123456');
    }
}
