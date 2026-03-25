<?php

namespace App\Providers;

use App\Services\JwtService;
use App\Services\TranslationService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(JwtService::class);
        $this->app->singleton(TranslationService::class);
    }

    public function boot(): void
    {
        // Rate limiters
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(100)->by(
                optional($request->attributes->get('auth_user'))->id ?: $request->ip()
            );
        });

        RateLimiter::for('auth', function (Request $request) {
            return Limit::perMinute(10)->by($request->ip());
        });
    }
}
