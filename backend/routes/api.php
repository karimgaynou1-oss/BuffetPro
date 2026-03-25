<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BuffetController;
use App\Http\Controllers\Api\DishController;
use App\Http\Controllers\Api\HotelSettingsController;
use App\Http\Controllers\Api\StripeWebhookController;
use App\Http\Controllers\Api\SuperAdminAuthController;
use App\Http\Controllers\Api\SuperAdminController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — BuffetPro
|--------------------------------------------------------------------------
*/

// ─── Authentication (public) ──────────────────────────────────────────────
Route::prefix('auth')->group(function () {
    Route::post('/login',   [AuthController::class, 'login']);
    Route::post('/refresh', [AuthController::class, 'refresh']);

    // Protected auth routes
    Route::middleware('jwt.auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me',      [AuthController::class, 'me']);
    });
});

// ─── Stripe Webhook (public, raw body) ────────────────────────────────────
Route::post('/stripe/webhook', [StripeWebhookController::class, 'handle']);

// ─── Hotel User Routes (JWT protected) ───────────────────────────────────
Route::middleware('jwt.auth')->group(function () {

    // Dishes (chef + hotel_admin)
    Route::prefix('dishes')->group(function () {
        Route::get('/check-repetition', [BuffetController::class, 'checkRepetition']);
        Route::post('/translate',        [DishController::class, 'translate'])
            ->middleware('role:chef,hotel_admin');

        Route::get('/',        [DishController::class, 'index']);
        Route::get('/{id}',    [DishController::class, 'show']);
        Route::post('/',       [DishController::class, 'store'])->middleware('role:chef,hotel_admin');
        Route::put('/{id}',    [DishController::class, 'update'])->middleware('role:chef,hotel_admin');
        Route::delete('/{id}', [DishController::class, 'destroy'])->middleware('role:chef,hotel_admin');
    });

    // Buffets (chef + hotel_admin + coordinator)
    Route::prefix('buffets')->group(function () {
        Route::get('/check-repetition', [BuffetController::class, 'checkRepetition']);

        Route::get('/',        [BuffetController::class, 'index']);
        Route::get('/{id}',    [BuffetController::class, 'show']);
        Route::post('/',       [BuffetController::class, 'store'])->middleware('role:chef,hotel_admin');
        Route::put('/{id}',    [BuffetController::class, 'update'])->middleware('role:chef,hotel_admin');
        Route::delete('/{id}', [BuffetController::class, 'destroy'])->middleware('role:chef,hotel_admin');

        Route::post('/{id}/publish',           [BuffetController::class, 'publish'])->middleware('role:chef,hotel_admin');
        Route::get('/{id}/cost-report',        [BuffetController::class, 'costReport']);
        Route::get('/{id}/production-sheet',   [BuffetController::class, 'productionSheet']);
    });

    // Users (hotel_admin only)
    Route::prefix('users')->middleware('role:hotel_admin')->group(function () {
        Route::get('/',        [UserController::class, 'index']);
        Route::post('/',       [UserController::class, 'store']);
        Route::put('/{id}',    [UserController::class, 'update']);
        Route::delete('/{id}', [UserController::class, 'destroy']);
    });

    // Hotel Settings (hotel_admin only)
    Route::prefix('settings')->middleware('role:hotel_admin')->group(function () {
        Route::get('/',             [HotelSettingsController::class, 'show']);
        Route::put('/',             [HotelSettingsController::class, 'update']);
        Route::post('/upload-logo', [HotelSettingsController::class, 'uploadLogo']);
    });
});

// ─── Super Admin Routes ───────────────────────────────────────────────────
Route::prefix('admin')->group(function () {
    Route::post('/auth/login', [SuperAdminAuthController::class, 'login']);

    Route::middleware('jwt.auth:super_admin')->group(function () {
        Route::get('/hotels',           [SuperAdminController::class, 'listHotels']);
        Route::post('/hotels',          [SuperAdminController::class, 'createHotel']);
        Route::put('/hotels/{id}',      [SuperAdminController::class, 'updateHotel']);
        Route::delete('/hotels/{id}',   [SuperAdminController::class, 'deactivateHotel']);
        Route::post('/hotels/{id}/customize', [SuperAdminController::class, 'customizeHotel']);
        Route::get('/analytics',        [SuperAdminController::class, 'analytics']);
    });
});
