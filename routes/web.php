<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Config;
use MetaSoftDevs\LaravelBreeze2FA\Http\Controllers\TwoFactorController;
use MetaSoftDevs\LaravelBreeze2FA\Http\Controllers\TwoFactorRecoveryController;
use MetaSoftDevs\LaravelBreeze2FA\Http\Controllers\TwoFactorDeviceController;

/*
|--------------------------------------------------------------------------
| Two-Factor Authentication Routes
|--------------------------------------------------------------------------
|
| Here are the routes for the Two-Factor Authentication package. These routes
| handle setup, challenge, verification, and management of 2FA. They can be
| customized via the package configuration.
|
*/

// Only register routes if they are enabled in config
if (Config::get('two-factor.routes.enabled', true)) {

    $prefix = Config::get('two-factor.routes.prefix', 'two-factor');
    $middleware = Config::get('two-factor.routes.middleware', ['web', 'auth']);
    $namePrefix = Config::get('two-factor.routes.name_prefix', 'two-factor.');

    Route::prefix($prefix)
        ->middleware($middleware)
        ->name($namePrefix)
        ->group(function () {

            /*
            |--------------------------------------------------------------------------
            | Two-Factor Setup Routes
            |--------------------------------------------------------------------------
            |
            | These routes handle the initial setup and configuration of 2FA for users.
            |
            */

            // Show 2FA setup page
            Route::get('/setup', [TwoFactorController::class, 'showSetup'])
                ->name('setup');

            // Enable 2FA for user
            Route::post('/enable', [TwoFactorController::class, 'enable'])
                ->name('enable');

            // Show confirmation page after enabling
            Route::get('/confirm', [TwoFactorController::class, 'showConfirm'])
                ->name('confirm');

            // Confirm 2FA setup with verification code
            Route::post('/confirm', [TwoFactorController::class, 'confirm'])
                ->name('confirm.store');

            // Disable 2FA
            Route::delete('/disable', [TwoFactorController::class, 'disable'])
                ->name('disable');

            /*
            |--------------------------------------------------------------------------
            | Two-Factor Challenge Routes
            |--------------------------------------------------------------------------
            |
            | These routes handle the 2FA challenge during login flow.
            |
            */

            // Show 2FA challenge page (during login)
            Route::get('/challenge', [TwoFactorController::class, 'showChallenge'])
                ->withoutMiddleware('auth') // Users might not be fully authenticated yet
                ->middleware(['web', 'throttle:60,1'])
                ->name('challenge');

            // Verify 2FA code during login
            Route::post('/challenge', [TwoFactorController::class, 'verify'])
                ->withoutMiddleware('auth') // Users might not be fully authenticated yet
                ->middleware(['web', 'throttle:5,1'])
                ->name('verify');

            // Resend verification code
            Route::post('/resend', [TwoFactorController::class, 'resend'])
                ->withoutMiddleware('auth') // Users might not be fully authenticated yet
                ->middleware(['web', 'throttle:3,1'])
                ->name('resend');

            /*
            |--------------------------------------------------------------------------
            | API/Status Routes
            |--------------------------------------------------------------------------
            |
            | These routes provide API endpoints for 2FA status and information.
            |
            */

            // Get current 2FA status
            Route::get('/status', [TwoFactorController::class, 'status'])
                ->name('status');

            /*
            |--------------------------------------------------------------------------
            | Recovery Code Routes
            |--------------------------------------------------------------------------
            |
            | These routes handle backup recovery codes management.
            |
            */

            // Show recovery codes
            Route::get('/recovery-codes', [TwoFactorController::class, 'showRecoveryCodes'])
                ->name('recovery-codes');

            // Generate new recovery codes
            Route::post('/recovery-codes', [TwoFactorController::class, 'generateRecoveryCodes'])
                ->name('recovery-codes.generate');

            // Download recovery codes as text file
            Route::get('/recovery-codes/download', [TwoFactorRecoveryController::class, 'download'])
                ->name('recovery-codes.download');

            // Show recovery codes as printable page
            Route::get('/recovery-codes/print', [TwoFactorRecoveryController::class, 'print'])
                ->name('recovery-codes.print');

            /*
            |--------------------------------------------------------------------------
            | Device Management Routes
            |--------------------------------------------------------------------------
            |
            | These routes handle remembered device management.
            |
            */

            // Show remembered devices
            Route::get('/devices', [TwoFactorDeviceController::class, 'index'])
                ->name('devices');

            // Forget a specific device
            Route::delete('/devices/{device}', [TwoFactorDeviceController::class, 'forget'])
                ->name('devices.forget');

            // Forget all devices
            Route::delete('/devices', [TwoFactorDeviceController::class, 'forgetAll'])
                ->name('devices.forget-all');

            // Forget current device
            Route::post('/forget-device', [TwoFactorDeviceController::class, 'forgetCurrent'])
                ->name('forget-device');
        });

    /*
    |--------------------------------------------------------------------------
    | Additional API Routes (Optional)
    |--------------------------------------------------------------------------
    |
    | These routes provide a RESTful API for 2FA management. They can be
    | enabled separately for API-based applications.
    |
    */

    if (Config::get('two-factor.routes.api_enabled', false)) {
        Route::prefix('api/' . $prefix)
            ->middleware(['api', 'auth:sanctum'])
            ->name($namePrefix . 'api.')
            ->group(function () {

                // API endpoints for 2FA management
                Route::get('/', [TwoFactorController::class, 'status']);
                Route::post('/enable', [TwoFactorController::class, 'enable']);
                Route::post('/confirm', [TwoFactorController::class, 'confirm']);
                Route::post('/verify', [TwoFactorController::class, 'verify']);
                Route::post('/resend', [TwoFactorController::class, 'resend']);
                Route::delete('/disable', [TwoFactorController::class, 'disable']);

                // Recovery codes API
                Route::get('/recovery-codes', [TwoFactorRecoveryController::class, 'index']);
                Route::post('/recovery-codes', [TwoFactorRecoveryController::class, 'generate']);

                // Devices API
                Route::get('/devices', [TwoFactorDeviceController::class, 'index']);
                Route::delete('/devices/{device}', [TwoFactorDeviceController::class, 'forget']);
                Route::delete('/devices', [TwoFactorDeviceController::class, 'forgetAll']);
            });
    }

    /*
    |--------------------------------------------------------------------------
    | Webhook Routes (Optional)
    |--------------------------------------------------------------------------
    |
    | These routes handle webhooks from SMS providers and other external services.
    |
    */

    if (Config::get('two-factor.routes.webhooks_enabled', false)) {
        Route::prefix('webhooks/' . $prefix)
            ->middleware(['web'])
            ->name($namePrefix . 'webhooks.')
            ->group(function () {

                // SMS provider webhooks
                Route::post('/sms/twilio', [\MetaSoftDevs\LaravelBreeze2FA\Http\Controllers\WebhookController::class, 'twilioSms'])
                    ->name('sms.twilio');

                Route::post('/sms/vonage', [\MetaSoftDevs\LaravelBreeze2FA\Http\Controllers\WebhookController::class, 'vonageSms'])
                    ->name('sms.vonage');

                // Email delivery webhooks
                Route::post('/email/delivery', [\MetaSoftDevs\LaravelBreeze2FA\Http\Controllers\WebhookController::class, 'emailDelivery'])
                    ->name('email.delivery');
            });
    }

    /*
    |--------------------------------------------------------------------------
    | Admin Routes (Optional)
    |--------------------------------------------------------------------------
    |
    | These routes provide administrative functions for managing 2FA across
    | all users. They require special permissions.
    |
    */

    if (Config::get('two-factor.routes.admin_enabled', false)) {
        Route::prefix('admin/' . $prefix)
            ->middleware(['web', 'auth', 'can:admin-two-factor'])
            ->name($namePrefix . 'admin.')
            ->group(function () {

                // Admin dashboard
                Route::get('/', [\MetaSoftDevs\LaravelBreeze2FA\Http\Controllers\AdminController::class, 'dashboard'])
                    ->name('dashboard');

                // User management
                Route::get('/users', [\MetaSoftDevs\LaravelBreeze2FA\Http\Controllers\AdminController::class, 'users'])
                    ->name('users');

                Route::post('/users/{user}/disable', [\MetaSoftDevs\LaravelBreeze2FA\Http\Controllers\AdminController::class, 'disableUser'])
                    ->name('users.disable');

                Route::post('/users/{user}/reset', [\MetaSoftDevs\LaravelBreeze2FA\Http\Controllers\AdminController::class, 'resetUser'])
                    ->name('users.reset');

                // System settings
                Route::get('/settings', [\MetaSoftDevs\LaravelBreeze2FA\Http\Controllers\AdminController::class, 'settings'])
                    ->name('settings');

                Route::post('/settings', [\MetaSoftDevs\LaravelBreeze2FA\Http\Controllers\AdminController::class, 'updateSettings'])
                    ->name('settings.update');

                // Analytics and reports
                Route::get('/analytics', [\MetaSoftDevs\LaravelBreeze2FA\Http\Controllers\AdminController::class, 'analytics'])
                    ->name('analytics');

                Route::get('/logs', [\MetaSoftDevs\LaravelBreeze2FA\Http\Controllers\AdminController::class, 'logs'])
                    ->name('logs');
            });
    }
}

/*
|--------------------------------------------------------------------------
| Route Model Binding
|--------------------------------------------------------------------------
|
| Define route model bindings for the package routes.
|
*/

// Bind device parameter to TwoFactorSession model
Route::bind('device', function ($value) {
    $deviceModel = \MetaSoftDevs\LaravelBreeze2FA\Models\TwoFactorSession::class;
    return $deviceModel::where('token', $value)->firstOrFail();
});

// Bind user parameter for admin routes
Route::bind('user', function ($value) {
    $userModel = Config::get('auth.providers.users.model', \App\Models\User::class);
    return $userModel::findOrFail($value);
});
