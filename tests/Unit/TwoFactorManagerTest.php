<?php

declare(strict_types=1);

namespace MetaSoftDevs\LaravelBreeze2FA\Tests\Unit;

use Orchestra\Testbench\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use MetaSoftDevs\LaravelBreeze2FA\TwoFactorServiceProvider;
use MetaSoftDevs\LaravelBreeze2FA\Services\TwoFactorManager;
use MetaSoftDevs\LaravelBreeze2FA\Facades\TwoFactor;
use MetaSoftDevs\LaravelBreeze2FA\Tests\Fixtures\User;

/**
 * Two-Factor Manager Test
 *
 * This test class covers the core functionality of the TwoFactorManager
 * service including enabling, disabling, verifying, and managing 2FA
 * for users.
 *
 * @package MetaSoftDevs\LaravelBreeze2FA\Tests\Unit
 * @author MetaSoft Developers <developers@metasoft.dev>
 * @version 1.0.0
 */
class TwoFactorManagerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Setup the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');

        // Set up test configuration
        Config::set('two-factor.enabled', true);
        Config::set('two-factor.methods.totp.enabled', true);
        Config::set('two-factor.methods.email.enabled', true);
        Config::set('two-factor.recovery_codes.enabled', true);
    }

    /**
     * Get package providers.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return array
     */
    protected function getPackageProviders($app): array
    {
        return [
            TwoFactorServiceProvider::class,
        ];
    }

    /**
     * Get package aliases.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return array
     */
    protected function getPackageAliases($app): array
    {
        return [
            'TwoFactor' => TwoFactor::class,
        ];
    }

    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    protected function defineEnvironment($app): void
    {
        // Setup the database
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);

        // Setup user model
        $app['config']->set('auth.providers.users.model', User::class);
    }

    /**
     * Test that the service is bound correctly.
     */
    public function test_service_is_bound(): void
    {
        $this->assertInstanceOf(TwoFactorManager::class, app('two-factor'));
    }

    /**
     * Test that the facade works correctly.
     */
    public function test_facade_works(): void
    {
        $this->assertTrue(TwoFactor::isEnabled());
    }

    /**
     * Test checking if 2FA is enabled globally.
     */
    public function test_is_enabled_globally(): void
    {
        $this->assertTrue(TwoFactor::isEnabled());

        Config::set('two-factor.enabled', false);
        $this->assertFalse(TwoFactor::isEnabled());
    }

    /**
     * Test checking if 2FA is required globally.
     */
    public function test_is_required_globally(): void
    {
        $this->assertFalse(TwoFactor::isRequired());

        Config::set('two-factor.required', true);
        $this->assertTrue(TwoFactor::isRequired());
    }

    /**
     * Test checking if 2FA is enabled for a user.
     */
    public function test_is_enabled_for_user(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        $this->assertFalse(TwoFactor::isEnabledForUser($user));

        // Enable 2FA for the user
        TwoFactor::enable($user, 'totp');

        // Should still be false until confirmed
        $this->assertFalse(TwoFactor::isEnabledForUser($user));
    }

    /**
     * Test enabling TOTP for a user.
     */
    public function test_enable_totp_for_user(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        $setup = TwoFactor::enable($user, 'totp');

        $this->assertIsArray($setup);
        $this->assertArrayHasKey('method', $setup);
        $this->assertArrayHasKey('qr_code_url', $setup);
        $this->assertArrayHasKey('secret', $setup);
        $this->assertArrayHasKey('recovery_codes', $setup);
        $this->assertEquals('totp', $setup['method']);
        $this->assertIsString($setup['secret']);
        $this->assertIsArray($setup['recovery_codes']);
    }

    /**
     * Test enabling email OTP for a user.
     */
    public function test_enable_email_for_user(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        $setup = TwoFactor::enable($user, 'email');

        $this->assertIsArray($setup);
        $this->assertArrayHasKey('method', $setup);
        $this->assertArrayHasKey('message', $setup);
        $this->assertEquals('email', $setup['method']);
    }

    /**
     * Test getting available methods for a user.
     */
    public function test_get_available_methods(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        $methods = TwoFactor::getAvailableMethods($user);

        $this->assertIsArray($methods);
        $this->assertArrayHasKey('totp', $methods);
        $this->assertArrayHasKey('email', $methods);

        // SMS should not be available without phone number
        $this->assertArrayNotHasKey('sms', $methods);
    }

    /**
     * Test getting user status.
     */
    public function test_get_user_status(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        $status = TwoFactor::getStatus($user);

        $this->assertIsArray($status);
        $this->assertArrayHasKey('enabled', $status);
        $this->assertArrayHasKey('method', $status);
        $this->assertArrayHasKey('confirmed', $status);
        $this->assertArrayHasKey('recovery_codes_count', $status);
        $this->assertFalse($status['enabled']);
        $this->assertNull($status['method']);
        $this->assertFalse($status['confirmed']);
        $this->assertEquals(0, $status['recovery_codes_count']);
    }

    /**
     * Test disabling 2FA for a user.
     */
    public function test_disable_for_user(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        // Enable 2FA first
        TwoFactor::enable($user, 'totp');

        // Disable 2FA
        $disabled = TwoFactor::disable($user);

        $this->assertTrue($disabled);
        $this->assertFalse(TwoFactor::isEnabledForUser($user));
    }

    /**
     * Test that 2FA is disabled when globally disabled.
     */
    public function test_disabled_when_globally_disabled(): void
    {
        Config::set('two-factor.enabled', false);

        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        $this->expectException(\MetaSoftDevs\LaravelBreeze2FA\Exceptions\TwoFactorException::class);
        TwoFactor::enable($user, 'totp');
    }

    /**
     * Test that invalid methods throw exceptions.
     */
    public function test_invalid_method_throws_exception(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        $this->expectException(\MetaSoftDevs\LaravelBreeze2FA\Exceptions\TwoFactorException::class);
        TwoFactor::enable($user, 'invalid_method');
    }

    /**
     * Test device remembering functionality.
     */
    public function test_device_remembering(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        $this->assertFalse(TwoFactor::isDeviceRemembered($user));

        // Device remembering is tested through the service
        $deviceService = app(\MetaSoftDevs\LaravelBreeze2FA\Contracts\DeviceRememberServiceInterface::class);
        $token = $deviceService->rememberDevice($user);

        $this->assertIsString($token);
        $this->assertNotEmpty($token);
    }

    /**
     * Test configuration retrieval.
     */
    public function test_get_configuration(): void
    {
        $config = TwoFactor::getConfiguration();

        $this->assertIsArray($config);
        $this->assertArrayHasKey('enabled', $config);
        $this->assertArrayHasKey('required', $config);
        $this->assertArrayHasKey('totp', $config);
        $this->assertArrayHasKey('email', $config);
        $this->assertArrayHasKey('recovery_codes', $config);
    }

    /**
     * Test that SMS requires phone number.
     */
    public function test_sms_requires_phone_number(): void
    {
        Config::set('two-factor.methods.sms.enabled', true);

        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        $this->expectException(\MetaSoftDevs\LaravelBreeze2FA\Exceptions\TwoFactorException::class);
        TwoFactor::enable($user, 'sms');
    }

    /**
     * Test recovery code generation.
     */
    public function test_recovery_code_generation(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        $codes = TwoFactor::generateRecoveryCodes($user);

        $this->assertIsArray($codes);
        $this->assertCount(8, $codes); // Default count

        foreach ($codes as $code) {
            $this->assertIsString($code);
            $this->assertNotEmpty($code);
        }
    }

    /**
     * Test facade validation.
     */
    public function test_facade_validation(): void
    {
        $this->assertTrue(TwoFactor::validateConfiguration());
    }

    /**
     * Test user info retrieval.
     */
    public function test_get_user_info(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        $info = TwoFactor::getUserInfo($user);

        $this->assertIsArray($info);
        $this->assertArrayHasKey('status', $info);
        $this->assertArrayHasKey('available_methods', $info);
        $this->assertArrayHasKey('is_enabled_globally', $info);
        $this->assertArrayHasKey('is_required', $info);
        $this->assertArrayHasKey('device_remembered', $info);
        $this->assertArrayHasKey('needs_setup', $info);
        $this->assertArrayHasKey('needs_challenge', $info);
    }
}
