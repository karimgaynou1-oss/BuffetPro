<?php

return [
    /*
    |--------------------------------------------------------------------------
    | JWT Secret Key
    |--------------------------------------------------------------------------
    | This secret key is used to sign JWT tokens. Generate it with:
    |   php artisan jwt:generate-secret
    */
    'secret' => env('JWT_SECRET', ''),

    /*
    |--------------------------------------------------------------------------
    | JWT Access Token TTL (minutes)
    |--------------------------------------------------------------------------
    | Specifies the lifetime of access tokens in minutes.
    | Default: 15 minutes
    */
    'ttl' => env('JWT_TTL', 15),

    /*
    |--------------------------------------------------------------------------
    | JWT Refresh Token TTL (minutes)
    |--------------------------------------------------------------------------
    | Specifies the lifetime of refresh tokens in minutes.
    | Default: 7 days (10080 minutes)
    */
    'refresh_ttl' => env('JWT_REFRESH_TTL', 10080),

    /*
    |--------------------------------------------------------------------------
    | Blacklist Cache Driver
    |--------------------------------------------------------------------------
    | Used to store blacklisted / revoked tokens.
    */
    'blacklist_cache' => 'file',
];
