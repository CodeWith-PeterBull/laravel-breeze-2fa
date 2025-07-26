<?php

declare(strict_types=1);

namespace MetaSoftDevs\LaravelBreeze2FA\Tests\Feature;

use Orchestra\Testbench\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Mail;
use MetaSoftDevs\LaravelBreeze2FA\TwoFactorServiceProvider;
use MetaSoftDevs\LaravelBreeze2FA\Facades\TwoFactor;
use MetaSoftDevs\LaravelBreeze2FA\Tests\Fixtures\User;
use MetaSoftDevs\LaravelBreeze2FA\Models\TwoFactorAuth;
use MetaSoftDevs\LaravelBreeze2FA\Models\TwoFactorRecoveryCode;

/**
 * Two-Factor Workflow Feature Test
 *
 * This test class covers the complete two-factor authentication workflow
 * including setup, confirmation, login challenge, and device management.
 *
 * @package MetaSoftDevs\LaravelBreeze2FA\Tests\Feature
 * @author MetaSoft Developers <developers@metasoft.dev>
 * @version 1.0.0
 */
class TwoFactorWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    /**
     * Setup the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
        User::createTable();

        // Create test user
        $this->user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        // Set up test configuration
        Config::set('two-factor.enabled', true);
        Config::set('two-factor.methods.totp.enabled', true);
        Config::set('two-factor.methods.email.enabled', true);
        Config::set('two-factor.recovery_codes.enabled', true);
        Config::set('two-factor.remember_device.enabled', true);
        Config::set('two-factor.routes.enabled', true);

        Mail::fake();
    }

    /**
     * Get package providers.
     */
    protected function getPackageProviders($app): array
    {
        return [TwoFactorServiceProvider::class];
    }

    /**
     * Define environment setup.
     */
    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);
        $app['config']->set('auth.providers.users.model', User::class);
    }

    /**
     * Test complete TOTP setup workflow.
     */
    public function test_complete_totp_setup_workflow(): void
    {
        $this->actingAs($this->user);

        // 1. Visit setup page
        $response = $this->get('/two-factor/setup');
        $response->assertStatus(200);
        $response->assertViewIs('two-factor::setup');

        // 2. Enable TOTP
        $response = $this->post('/two-factor/enable', [
            'method' => 'totp',
        ]);
        $response->assertRedirect('/two-factor/confirm');

        // 3. Check that 2FA record was created but not confirmed
        $twoFactorAuth = TwoFactorAuth::where('user_id', $this->user->id)->first();
        $this->assertNotNull($twoFactorAuth);
        $this->assertEquals('totp', $twoFactorAuth->method);
        $this->assertFalse($twoFactorAuth->enabled);
        $this->assertNull($twoFactorAuth->confirmed_at);

        // 4. Check that recovery codes were generated
        $recoveryCodes = TwoFactorRecoveryCode::forUser($this->user->id)->get();
        $this->assertCount(8, $recoveryCodes);

        // 5. Visit confirmation page
        $response = $this->get('/two-factor/confirm');
        $response->assertStatus(200);
        $response->assertViewIs('two-factor::confirm');

        // 6. Get TOTP code and confirm
        $totpService = app(\MetaSoftDevs\LaravelBreeze2FA\Contracts\TOTPServiceInterface::class);
        $currentCode = $totpService->getCurrentCode($twoFactorAuth->decrypted_secret);

        $response = $this->post('/two-factor/confirm', [
            'code' => $currentCode,
        ]);
        $response->assertRedirect('/two-factor/setup');
        $response->assertSessionHas('success');

        // 7. Verify 2FA is now enabled and confirmed
        $twoFactorAuth->refresh();
        $this->assertTrue($twoFactorAuth->enabled);
        $this->assertNotNull($twoFactorAuth->confirmed_at);
    }

    /**
     * Test complete email OTP setup workflow.
     */
    public function test_complete_email_otp_setup_workflow(): void
    {
        $this->actingAs($this->user);

        // 1. Enable Email OTP
        $response = $this->post('/two-factor/enable', [
            'method' => 'email',
        ]);
        $response->assertRedirect('/two-factor/confirm');

        // 2. Check that email was queued
        Mail::assertQueued(\MetaSoftDevs\LaravelBreeze2FA\Mail\TwoFactorCodeMail::class);

        // 3. Get the stored code and confirm
        $emailService = app(\MetaSoftDevs\LaravelBreeze2FA\Contracts\EmailOTPServiceInterface::class);
        $this->assertTrue($emailService->hasValidCode($this->user));

        // For testing, we'll manually get the code from cache
        $cacheKey = 'two_factor:two_factor_email_otp:' . $this->user->id;
        $code = cache()->get($cacheKey);

        $response = $this->post('/two-factor/confirm', [
            'code' => $code,
        ]);
        $response->assertRedirect('/two-factor/setup');

        // 4. Verify 2FA is enabled
        $this->assertTrue(TwoFactor::isEnabledForUser($this->user));
    }

    /**
     * Test 2FA challenge during login.
     */
    public function test_two_factor_challenge_workflow(): void
    {
        // Setup TOTP for user
        $setup = TwoFactor::enable($this->user, 'totp');
        $secret = $setup['secret'];

        // Confirm setup
        $totpService = app(\MetaSoftDevs\LaravelBreeze2FA\Contracts\TOTPServiceInterface::class);
        $code = $totpService->getCurrentCode($secret);
        TwoFactor::confirm($this->user, $code);

        // Simulate login challenge
        Session::put('two_factor_user_id', $this->user->id);

        // 1. Visit challenge page
        $response = $this->get('/two-factor/challenge');
        $response->assertStatus(200);
        $response->assertViewIs('two-factor::challenge');

        // 2. Submit correct code
        $currentCode = $totpService->getCurrentCode($secret);
        $response = $this->post('/two-factor/challenge', [
            'code' => $currentCode,
            'remember_device' => true,
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($this->user);
    }

    /**
     * Test recovery code usage.
     */
    public function test_recovery_code_usage(): void
    {
        // Setup and confirm TOTP
        $setup = TwoFactor::enable($this->user, 'totp');
        $totpService = app(\MetaSoftDevs\LaravelBreeze2FA\Contracts\TOTPServiceInterface::class);
        $code = $totpService->getCurrentCode($setup['secret']);
        TwoFactor::confirm($this->user, $code);

        // Get a recovery code
        $recoveryCodes = $setup['recovery_codes'];
        $recoveryCode = $recoveryCodes[0];

        // Simulate login challenge
        Session::put('two_factor_user_id', $this->user->id);

        // Use recovery code
        $response = $this->post('/two-factor/challenge', [
            'code' => $recoveryCode,
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($this->user);

        // Verify recovery code was marked as used
        $usedCode = TwoFactorRecoveryCode::forUser($this->user->id)->used()->first();
        $this->assertNotNull($usedCode);
    }

    /**
     * Test 2FA middleware protection.
     */
    public function test_middleware_protection(): void
    {
        // User without 2FA
        $this->actingAs($this->user);

        // Create a protected route for testing
        \Route::get('/protected', function () {
            return 'Protected content';
        })->middleware(['auth', 'two-factor']);

        $response = $this->get('/protected');
        $response->assertStatus(200); // Should pass since 2FA not required

        // Enable 2FA for user
        $setup = TwoFactor::enable($this->user, 'totp');
        $totpService = app(\MetaSoftDevs\LaravelBreeze2FA\Contracts\TOTPServiceInterface::class);
        $code = $totpService->getCurrentCode($setup['secret']);
        TwoFactor::confirm($this->user, $code);

        // Now should be redirected to challenge
        $response = $this->get('/protected');
        $response->assertRedirect('/two-factor/challenge');
    }

    /**
     * Test device remembering.
     */
    public function test_device_remembering(): void
    {
        // Setup and confirm TOTP
        $setup = TwoFactor::enable($this->user, 'totp');
        $totpService = app(\MetaSoftDevs\LaravelBreeze2FA\Contracts\TOTPServiceInterface::class);
        $code = $totpService->getCurrentCode($setup['secret']);
        TwoFactor::confirm($this->user, $code);

        // Remember device during verification
        Session::put('two_factor_user_id', $this->user->id);
        $currentCode = $totpService->getCurrentCode($setup['secret']);

        $response = $this->post('/two-factor/challenge', [
            'code' => $currentCode,
            'remember_device' => true,
        ]);

        $response->assertRedirect('/dashboard');

        // Verify device is remembered
        $this->assertTrue(TwoFactor::isDeviceRemembered($this->user));

        // Should not need 2FA on next login
        \Route::get('/test-protected', function () {
            return 'success';
        })->middleware(['auth', 'two-factor']);

        $response = $this->get('/test-protected');
        $response->assertStatus(200);
        $response->assertSee('success');
    }

    /**
     * Test 2FA disabling.
     */
    public function test_two_factor_disabling(): void
    {
        // Setup and confirm TOTP
        $setup = TwoFactor::enable($this->user, 'totp');
        $totpService = app(\MetaSoftDevs\LaravelBreeze2FA\Contracts\TOTPServiceInterface::class);
        $code = $totpService->getCurrentCode($setup['secret']);
        TwoFactor::confirm($this->user, $code);

        $this->assertTrue(TwoFactor::isEnabledForUser($this->user));

        // Disable 2FA
        $this->actingAs($this->user);
        $response = $this->delete('/two-factor/disable', [
            'password' => 'password',
        ]);

        $response->assertRedirect('/two-factor/setup');
        $this->assertFalse(TwoFactor::isEnabledForUser($this->user));

        // Verify all related data was cleaned up
        $this->assertEquals(0, TwoFactorAuth::where('user_id', $this->user->id)->count());
        $this->assertEquals(0, TwoFactorRecoveryCode::forUser($this->user->id)->count());
    }

    /**
     * Test invalid code handling.
     */
    public function test_invalid_code_handling(): void
    {
        // Setup and confirm TOTP
        $setup = TwoFactor::enable($this->user, 'totp');
        $totpService = app(\MetaSoftDevs\LaravelBreeze2FA\Contracts\TOTPServiceInterface::class);
        $code = $totpService->getCurrentCode($setup['secret']);
        TwoFactor::confirm($this->user, $code);

        // Simulate login challenge with invalid code
        Session::put('two_factor_user_id', $this->user->id);

        $response = $this->post('/two-factor/challenge', [
            'code' => '000000', // Invalid code
        ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors(['code']);
        $this->assertGuest();
    }

    /**
     * Test resend functionality.
     */
    public function test_resend_functionality(): void
    {
        // Setup email OTP
        TwoFactor::enable($this->user, 'email');

        Session::put('two_factor_user_id', $this->user->id);

        // Clear previous emails
        Mail::fake();

        // Resend code
        $response = $this->post('/two-factor/resend');
        $response->assertStatus(302);
        $response->assertSessionHas('status');

        // Verify email was sent
        Mail::assertQueued(\MetaSoftDevs\LaravelBreeze2FA\Mail\TwoFactorCodeMail::class);
    }

    /**
     * Test status API endpoint.
     */
    public function test_status_api_endpoint(): void
    {
        $this->actingAs($this->user);

        $response = $this->get('/two-factor/status');
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status' => [
                'enabled',
                'method',
                'confirmed',
                'recovery_codes_count',
            ],
            'available_methods',
            'is_enabled_globally',
            'is_required',
            'device_remembered',
        ]);
    }
}
