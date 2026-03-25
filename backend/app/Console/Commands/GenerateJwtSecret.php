<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

class GenerateJwtSecret extends Command
{
    protected $signature   = 'jwt:generate-secret {--show : Show the generated secret without saving}';
    protected $description = 'Generate a new JWT secret key and store it in .env';

    public function handle(): int
    {
        $secret = base64_encode(random_bytes(64));

        if ($this->option('show')) {
            $this->line("<comment>{$secret}</comment>");
            return self::SUCCESS;
        }

        $envPath = base_path('.env');
        if (!file_exists($envPath)) {
            $this->error('.env file not found. Run: cp .env.example .env');
            return self::FAILURE;
        }

        $envContent = file_get_contents($envPath);

        if (str_contains($envContent, 'JWT_SECRET=')) {
            $envContent = preg_replace('/^JWT_SECRET=.*$/m', "JWT_SECRET={$secret}", $envContent);
        } else {
            $envContent .= "\nJWT_SECRET={$secret}\n";
        }

        file_put_contents($envPath, $envContent);

        $this->info("JWT secret key set successfully.");

        return self::SUCCESS;
    }
}
