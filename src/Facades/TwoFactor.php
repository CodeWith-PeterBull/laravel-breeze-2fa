<?php

declare(strict_types=1);

namespace MetaSoftDevs\LaravelBreeze2FA\Facades;

use Illuminate\Support\Facades\Facade;
use MetaSoftDevs\LaravelBreeze2FA\Contracts\TwoFactorManagerInterface;

/**
 * Two-Factor Authentication Facade
 *
 * This facade provides a convenient way to access the TwoFactorManager
 * throughout the application using static method calls.
 *
 * @package MetaSoftDevs\LaravelBreeze2FA\Facades
 * @author MetaSoft Developers <developers@metasoft.dev>
 * @version 1.0.0
 *
 * @method static bool isEnabled()
 * @method static bool isRequired()
 * @method static bool isEnabledForUser(\Illuminate\Contracts\Auth\Authenticatable $user)
 * @method static array enable(\Illuminate\Contracts\Auth\Authenticatable $user, string $method = 'totp', array $options = [])
 * @method static bool confirm(\Illuminate\Contracts\Auth\Authenticatable $user, string $code)
 * @method static bool disable(\Illuminate\Contracts\Auth\Authenticatable $user)
 * @method static bool verify(\Illuminate\Contracts\Auth\Authenticatable $user, string $code, bool $rememberDevice = false)
 * @method static bool sendCode(\Illuminate\Contracts\Auth\Authenticatable $user, string|null $method = null)
 * @method static bool isDeviceRemembered(\Illuminate\Contracts\Auth\Authenticatable $user)
 * @method static array getAvailableMethods(\Illuminate\Contracts\Auth\Authenticatable $user)
 * @method static array getStatus(\Illuminate\Contracts\Auth\Authenticatable $user)
 *
 * @see \MetaSoftDevs\LaravelBreeze2FA\Services\TwoFactorManager
 */
class TwoFactor extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return 'two-factor';
    }

    /**
     * Get the TwoFactorManager instance from the container.
     *
     * @return TwoFactorManagerInterface
     */
    public static function manager(): TwoFactorManagerInterface
    {
        return static::getFacadeRoot();
    }

    /**
     * Get the TOTP service instance.
     *
     * @return \MetaSoftDevs\LaravelBreeze2FA\Contracts\TOTPServiceInterface
     */
    public static function totp(): \MetaSoftDevs\LaravelBreeze2FA\Contracts\TOTPServiceInterface
    {
        return app(\MetaSoftDevs\LaravelBreeze2FA\Contracts\TOTPServiceInterface::class);
    }

    /**
     * Get the Email OTP service instance.
     *
     * @return \MetaSoftDevs\LaravelBreeze2FA\Contracts\EmailOTPServiceInterface
     */
    public static function emailOtp(): \MetaSoftDevs\LaravelBreeze2FA\Contracts\EmailOTPServiceInterface
    {
        return app(\MetaSoftDevs\LaravelBreeze2FA\Contracts\EmailOTPServiceInterface::class);
    }

    /**
     * Get the SMS OTP service instance.
     *
     * @return \MetaSoftDevs\LaravelBreeze2FA\Contracts\SMSOTPServiceInterface
     */
    public static function smsOtp(): \MetaSoftDevs\LaravelBreeze2FA\Contracts\SMSOTPServiceInterface
    {
        return app(\MetaSoftDevs\LaravelBreeze2FA\Contracts\SMSOTPServiceInterface::class);
    }

    /**
     * Get the recovery code service instance.
     *
     * @return \MetaSoftDevs\LaravelBreeze2FA\Contracts\RecoveryCodeServiceInterface
     */
    public static function recoveryCodes(): \MetaSoftDevs\LaravelBreeze2FA\Contracts\RecoveryCodeServiceInterface
    {
        return app(\MetaSoftDevs\LaravelBreeze2FA\Contracts\RecoveryCodeServiceInterface::class);
    }

    /**
     * Get the device remember service instance.
     *
     * @return \MetaSoftDevs\LaravelBreeze2FA\Contracts\DeviceRememberServiceInterface
     */
    public static function deviceRemember(): \MetaSoftDevs\LaravelBreeze2FA\Contracts\DeviceRememberServiceInterface
    {
        return app(\MetaSoftDevs\LaravelBreeze2FA\Contracts\DeviceRememberServiceInterface::class);
    }

    /**
     * Convenient method to check if 2FA is enabled and required for a user.
     *
     * @param \Illuminate\Contracts\Auth\Authenticatable $user
     * @return bool
     */
    public static function isRequiredForUser(\Illuminate\Contracts\Auth\Authenticatable $user): bool
    {
        return static::isRequired() || static::isEnabledForUser($user);
    }

    /**
     * Convenient method to check if a user needs to set up 2FA.
     *
     * @param \Illuminate\Contracts\Auth\Authenticatable $user
     * @return bool
     */
    public static function needsSetup(\Illuminate\Contracts\Auth\Authenticatable $user): bool
    {
        return static::isRequired() && !static::isEnabledForUser($user);
    }

    /**
     * Convenient method to check if a user needs 2FA challenge.
     *
     * @param \Illuminate\Contracts\Auth\Authenticatable $user
     * @return bool
     */
    public static function needsChallenge(\Illuminate\Contracts\Auth\Authenticatable $user): bool
    {
        return static::isEnabledForUser($user) && !static::isDeviceRemembered($user);
    }

    /**
     * Get comprehensive 2FA information for a user.
     *
     * @param \Illuminate\Contracts\Auth\Authenticatable $user
     * @return array
     */
    public static function getUserInfo(\Illuminate\Contracts\Auth\Authenticatable $user): array
    {
        $status = static::getStatus($user);
        $availableMethods = static::getAvailableMethods($user);

        return [
            'status' => $status,
            'available_methods' => $availableMethods,
            'is_enabled_globally' => static::isEnabled(),
            'is_required' => static::isRequired(),
            'device_remembered' => static::isDeviceRemembered($user),
            'needs_setup' => static::needsSetup($user),
            'needs_challenge' => static::needsChallenge($user),
            'is_required_for_user' => static::isRequiredForUser($user),
        ];
    }

    /**
     * Get recovery code service for backwards compatibility.
     *
     * @return \MetaSoftDevs\LaravelBreeze2FA\Contracts\RecoveryCodeServiceInterface
     */
    public static function getRecoveryCodeService(): \MetaSoftDevs\LaravelBreeze2FA\Contracts\RecoveryCodeServiceInterface
    {
        return static::recoveryCodes();
    }

    /**
     * Quick method to generate recovery codes for a user.
     *
     * @param \Illuminate\Contracts\Auth\Authenticatable $user
     * @param int|null $count
     * @return array
     */
    public static function generateRecoveryCodes(\Illuminate\Contracts\Auth\Authenticatable $user, ?int $count = null): array
    {
        return static::recoveryCodes()->generate($user, $count);
    }

    /**
     * Quick method to regenerate recovery codes for a user.
     *
     * @param \Illuminate\Contracts\Auth\Authenticatable $user
     * @param int|null $count
     * @return array
     */
    public static function regenerateRecoveryCodes(\Illuminate\Contracts\Auth\Authenticatable $user, ?int $count = null): array
    {
        return static::recoveryCodes()->regenerate($user, $count);
    }

    /**
     * Quick method to get QR code for TOTP setup.
     *
     * @param \Illuminate\Contracts\Auth\Authenticatable $user
     * @param string $secret
     * @return string
     */
    public static function getQrCodeUrl(\Illuminate\Contracts\Auth\Authenticatable $user, string $secret): string
    {
        return static::totp()->generateQrCodeUrl($user, $secret);
    }

    /**
     * Quick method to get QR code SVG for TOTP setup.
     *
     * @param \Illuminate\Contracts\Auth\Authenticatable $user
     * @param string $secret
     * @return string
     */
    public static function getQrCodeSvg(\Illuminate\Contracts\Auth\Authenticatable $user, string $secret): string
    {
        $qrCodeUrl = static::getQrCodeUrl($user, $secret);
        return static::totp()->generateQrCodeSvg($qrCodeUrl);
    }

    /**
     * Quick method to get QR code data URI for TOTP setup.
     *
     * @param \Illuminate\Contracts\Auth\Authenticatable $user
     * @param string $secret
     * @return string
     */
    public static function getQrCodeDataUri(\Illuminate\Contracts\Auth\Authenticatable $user, string $secret): string
    {
        $qrCodeUrl = static::getQrCodeUrl($user, $secret);
        return static::totp()->generateQrCodeDataUri($qrCodeUrl);
    }

    /**
     * Test if a TOTP code would be valid (for debugging/testing).
     *
     * @param string $secret
     * @param string $code
     * @return array
     */
    public static function testTotp(string $secret, string $code): array
    {
        return static::totp()->testTOTP($secret, $code);
    }

    /**
     * Get configuration for all 2FA methods.
     *
     * @return array
     */
    public static function getConfiguration(): array
    {
        return [
            'enabled' => static::isEnabled(),
            'required' => static::isRequired(),
            'totp' => static::totp()->getConfiguration(),
            'email' => static::emailOtp()->getConfiguration(),
            'sms' => static::smsOtp()->getConfiguration(),
            'recovery_codes' => static::recoveryCodes()->getConfiguration(),
            'device_remember' => static::deviceRemember()->getConfiguration(),
        ];
    }

    /**
     * Validate that the facade is properly configured.
     *
     * @return bool
     * @throws \RuntimeException
     */
    public static function validateConfiguration(): bool
    {
        $manager = static::getFacadeRoot();

        if (!$manager instanceof TwoFactorManagerInterface) {
            throw new \RuntimeException('TwoFactor facade is not properly configured. Expected TwoFactorManagerInterface.');
        }

        return true;
    }
}
