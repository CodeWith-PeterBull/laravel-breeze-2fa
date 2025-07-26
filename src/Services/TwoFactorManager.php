<?php

declare(strict_types=1);

namespace MetaSoftDevs\LaravelBreeze2FA\Services;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use MetaSoftDevs\LaravelBreeze2FA\Contracts\TwoFactorManagerInterface;
use MetaSoftDevs\LaravelBreeze2FA\Contracts\TOTPServiceInterface;
use MetaSoftDevs\LaravelBreeze2FA\Contracts\EmailOTPServiceInterface;
use MetaSoftDevs\LaravelBreeze2FA\Contracts\SMSOTPServiceInterface;
use MetaSoftDevs\LaravelBreeze2FA\Contracts\RecoveryCodeServiceInterface;
use MetaSoftDevs\LaravelBreeze2FA\Contracts\DeviceRememberServiceInterface;
use MetaSoftDevs\LaravelBreeze2FA\Events\TwoFactorEnabled;
use MetaSoftDevs\LaravelBreeze2FA\Events\TwoFactorDisabled;
use MetaSoftDevs\LaravelBreeze2FA\Events\TwoFactorAuthenticated;
use MetaSoftDevs\LaravelBreeze2FA\Events\TwoFactorFailed;
use MetaSoftDevs\LaravelBreeze2FA\Events\RecoveryCodeUsed;
use MetaSoftDevs\LaravelBreeze2FA\Models\TwoFactorAuth;
use MetaSoftDevs\LaravelBreeze2FA\Exceptions\TwoFactorException;
use MetaSoftDevs\LaravelBreeze2FA\Exceptions\InvalidCodeException;
use MetaSoftDevs\LaravelBreeze2FA\Exceptions\RateLimitExceededException;

/**
 * Two-Factor Authentication Manager
 *
 * This service orchestrates all two-factor authentication functionality,
 * including enabling/disabling 2FA, verifying codes, and managing different
 * authentication methods (TOTP, Email OTP, SMS OTP, Recovery Codes).
 *
 * @package MetaSoftDevs\LaravelBreeze2FA\Services
 * @author MetaSoft Developers <developers@metasoft.dev>
 * @version 1.0.0
 */
class TwoFactorManager implements TwoFactorManagerInterface
{
    /**
     * TOTP service instance.
     */
    protected TOTPServiceInterface $totpService;

    /**
     * Email OTP service instance.
     */
    protected EmailOTPServiceInterface $emailOtpService;

    /**
     * SMS OTP service instance.
     */
    protected SMSOTPServiceInterface $smsOtpService;

    /**
     * Recovery code service instance.
     */
    protected RecoveryCodeServiceInterface $recoveryCodeService;

    /**
     * Device remember service instance.
     */
    protected DeviceRememberServiceInterface $deviceRememberService;

    /**
     * Create a new TwoFactorManager instance.
     *
     * @param TOTPServiceInterface $totpService
     * @param EmailOTPServiceInterface $emailOtpService
     * @param SMSOTPServiceInterface $smsOtpService
     * @param RecoveryCodeServiceInterface $recoveryCodeService
     * @param DeviceRememberServiceInterface $deviceRememberService
     */
    public function __construct(
        TOTPServiceInterface $totpService,
        EmailOTPServiceInterface $emailOtpService,
        SMSOTPServiceInterface $smsOtpService,
        RecoveryCodeServiceInterface $recoveryCodeService,
        DeviceRememberServiceInterface $deviceRememberService
    ) {
        $this->totpService = $totpService;
        $this->emailOtpService = $emailOtpService;
        $this->smsOtpService = $smsOtpService;
        $this->recoveryCodeService = $recoveryCodeService;
        $this->deviceRememberService = $deviceRememberService;
    }

    /**
     * Check if two-factor authentication is enabled globally.
     *
     * @return bool
     */
    public function isEnabled(): bool
    {
        return Config::get('two-factor.enabled', true);
    }

    /**
     * Check if two-factor authentication is required for all users.
     *
     * @return bool
     */
    public function isRequired(): bool
    {
        return Config::get('two-factor.required', false);
    }

    /**
     * Check if a user has two-factor authentication enabled.
     *
     * @param Authenticatable $user
     * @return bool
     */
    public function isEnabledForUser(Authenticatable $user): bool
    {
        if (!$this->isEnabled()) {
            return false;
        }

        return TwoFactorAuth::where('user_id', $user->getAuthIdentifier())
            ->where('enabled', true)
            ->exists();
    }

    /**
     * Enable two-factor authentication for a user.
     *
     * @param Authenticatable $user
     * @param string $method The 2FA method to enable (totp, email, sms)
     * @param array $options Additional options for the method
     * @return array Setup information (e.g., QR code URL for TOTP)
     * @throws TwoFactorException
     */
    public function enable(Authenticatable $user, string $method = 'totp', array $options = []): array
    {
        if (!$this->isEnabled()) {
            throw new TwoFactorException('Two-factor authentication is not enabled.');
        }

        if (!$this->isMethodEnabled($method)) {
            throw new TwoFactorException("Two-factor method '{$method}' is not enabled.");
        }

        // Get or create the user's 2FA record
        $twoFactorAuth = TwoFactorAuth::firstOrCreate(
            ['user_id' => $user->getAuthIdentifier()],
            [
                'enabled' => false,
                'method' => $method,
                'backup_codes_generated_at' => null,
            ]
        );

        $setupData = [];

        switch ($method) {
            case 'totp':
                $setupData = $this->enableTOTP($user, $twoFactorAuth, $options);
                break;
            case 'email':
                $setupData = $this->enableEmailOTP($user, $twoFactorAuth, $options);
                break;
            case 'sms':
                $setupData = $this->enableSMSOTP($user, $twoFactorAuth, $options);
                break;
            default:
                throw new TwoFactorException("Unknown two-factor method: {$method}");
        }

        // Generate recovery codes if enabled
        if (Config::get('two-factor.recovery_codes.enabled', true)) {
            $recoveryCodes = $this->recoveryCodeService->generate($user);
            $setupData['recovery_codes'] = $recoveryCodes;
        }

        // Fire event
        if (Config::get('two-factor.events.enabled', true)) {
            Event::dispatch(new TwoFactorEnabled($user, $method));
        }

        return $setupData;
    }

    /**
     * Confirm and activate two-factor authentication for a user.
     *
     * @param Authenticatable $user
     * @param string $code The verification code
     * @return bool
     * @throws TwoFactorException
     */
    public function confirm(Authenticatable $user, string $code): bool
    {
        $twoFactorAuth = TwoFactorAuth::where('user_id', $user->getAuthIdentifier())
            ->where('enabled', false) // Only confirm if not already enabled
            ->first();

        if (!$twoFactorAuth) {
            throw new TwoFactorException('No pending two-factor authentication setup found.');
        }

        $isValid = false;

        switch ($twoFactorAuth->method) {
            case 'totp':
                $isValid = $this->totpService->verify($user, $code);
                break;
            case 'email':
                $isValid = $this->emailOtpService->verify($user, $code);
                break;
            case 'sms':
                $isValid = $this->smsOtpService->verify($user, $code);
                break;
        }

        if ($isValid) {
            $twoFactorAuth->update([
                'enabled' => true,
                'confirmed_at' => now(),
            ]);

            return true;
        }

        throw new InvalidCodeException('The provided two-factor authentication code is invalid.');
    }

    /**
     * Disable two-factor authentication for a user.
     *
     * @param Authenticatable $user
     * @return bool
     */
    public function disable(Authenticatable $user): bool
    {
        $twoFactorAuth = TwoFactorAuth::where('user_id', $user->getAuthIdentifier())->first();

        if (!$twoFactorAuth) {
            return true; // Already disabled
        }

        // Delete the 2FA record and all associated data
        $twoFactorAuth->delete();

        // Clean up recovery codes
        $this->recoveryCodeService->deleteAll($user);

        // Clean up remembered devices
        $this->deviceRememberService->forgetAllDevices($user);

        // Fire event
        if (Config::get('two-factor.events.enabled', true)) {
            Event::dispatch(new TwoFactorDisabled($user));
        }

        return true;
    }

    /**
     * Verify a two-factor authentication code.
     *
     * @param Authenticatable $user
     * @param string $code
     * @param bool $rememberDevice Whether to remember this device
     * @return bool
     * @throws TwoFactorException|InvalidCodeException|RateLimitExceededException
     */
    public function verify(Authenticatable $user, string $code, bool $rememberDevice = false): bool
    {
        if (!$this->isEnabledForUser($user)) {
            throw new TwoFactorException('Two-factor authentication is not enabled for this user.');
        }

        $twoFactorAuth = TwoFactorAuth::where('user_id', $user->getAuthIdentifier())->first();

        // Check rate limiting
        if ($this->isRateLimited($user)) {
            throw new RateLimitExceededException('Too many verification attempts. Please try again later.');
        }

        $isValid = false;
        $methodUsed = null;

        // Try recovery code first
        if ($this->recoveryCodeService->isRecoveryCode($code)) {
            $isValid = $this->recoveryCodeService->verify($user, $code);
            $methodUsed = 'recovery';

            if ($isValid && Config::get('two-factor.events.enabled', true)) {
                Event::dispatch(new RecoveryCodeUsed($user, $code));
            }
        } else {
            // Try the user's configured method
            switch ($twoFactorAuth->method) {
                case 'totp':
                    $isValid = $this->totpService->verify($user, $code);
                    $methodUsed = 'totp';
                    break;
                case 'email':
                    $isValid = $this->emailOtpService->verify($user, $code);
                    $methodUsed = 'email';
                    break;
                case 'sms':
                    $isValid = $this->smsOtpService->verify($user, $code);
                    $methodUsed = 'sms';
                    break;
            }
        }

        if ($isValid) {
            // Clear rate limiting
            $this->clearRateLimit($user);

            // Remember device if requested
            if ($rememberDevice && Config::get('two-factor.remember_device.enabled', true)) {
                $this->deviceRememberService->rememberDevice($user);
            }

            // Fire success event
            if (Config::get('two-factor.events.enabled', true)) {
                Event::dispatch(new TwoFactorAuthenticated($user, $methodUsed));
            }

            return true;
        }

        // Record failed attempt
        $this->recordFailedAttempt($user);

        // Fire failure event
        if (Config::get('two-factor.events.enabled', true)) {
            Event::dispatch(new TwoFactorFailed($user, $code));
        }

        throw new InvalidCodeException('The provided two-factor authentication code is invalid.');
    }

    /**
     * Send a new OTP code to the user (for email/SMS methods).
     *
     * @param Authenticatable $user
     * @param string|null $method The method to send OTP (null to use user's preferred method)
     * @return bool
     * @throws TwoFactorException
     */
    public function sendCode(Authenticatable $user, ?string $method = null): bool
    {
        if (!$this->isEnabledForUser($user)) {
            throw new TwoFactorException('Two-factor authentication is not enabled for this user.');
        }

        $twoFactorAuth = TwoFactorAuth::where('user_id', $user->getAuthIdentifier())->first();
        $sendMethod = $method ?? $twoFactorAuth->method;

        switch ($sendMethod) {
            case 'email':
                return $this->emailOtpService->send($user);
            case 'sms':
                return $this->smsOtpService->send($user);
            default:
                throw new TwoFactorException("Cannot send code for method: {$sendMethod}");
        }
    }

    /**
     * Check if a device is remembered for the user.
     *
     * @param Authenticatable $user
     * @return bool
     */
    public function isDeviceRemembered(Authenticatable $user): bool
    {
        if (!Config::get('two-factor.remember_device.enabled', true)) {
            return false;
        }

        return $this->deviceRememberService->isDeviceRemembered($user);
    }

    /**
     * Get available two-factor methods for a user.
     *
     * @param Authenticatable $user
     * @return array
     */
    public function getAvailableMethods(Authenticatable $user): array
    {
        $methods = [];

        if ($this->isMethodEnabled('totp')) {
            $methods['totp'] = [
                'name' => 'Authenticator App',
                'description' => 'Use an authenticator app like Google Authenticator or Authy',
                'icon' => 'smartphone',
            ];
        }

        if ($this->isMethodEnabled('email')) {
            $methods['email'] = [
                'name' => 'Email',
                'description' => 'Receive codes via email',
                'icon' => 'mail',
            ];
        }

        if ($this->isMethodEnabled('sms') && $this->userHasPhoneNumber($user)) {
            $methods['sms'] = [
                'name' => 'SMS',
                'description' => 'Receive codes via text message',
                'icon' => 'message-circle',
            ];
        }

        return $methods;
    }

    /**
     * Get the user's current two-factor authentication status and settings.
     *
     * @param Authenticatable $user
     * @return array
     */
    public function getStatus(Authenticatable $user): array
    {
        $twoFactorAuth = TwoFactorAuth::where('user_id', $user->getAuthIdentifier())->first();

        if (!$twoFactorAuth) {
            return [
                'enabled' => false,
                'method' => null,
                'confirmed' => false,
                'recovery_codes_count' => 0,
                'can_generate_recovery_codes' => false,
            ];
        }

        return [
            'enabled' => $twoFactorAuth->enabled,
            'method' => $twoFactorAuth->method,
            'confirmed' => !is_null($twoFactorAuth->confirmed_at),
            'recovery_codes_count' => $this->recoveryCodeService->getUnusedCount($user),
            'can_generate_recovery_codes' => Config::get('two-factor.recovery_codes.enabled', true),
        ];
    }

    /**
     * Enable TOTP for a user.
     *
     * @param Authenticatable $user
     * @param TwoFactorAuth $twoFactorAuth
     * @param array $options
     * @return array
     */
    protected function enableTOTP(Authenticatable $user, TwoFactorAuth $twoFactorAuth, array $options): array
    {
        $setupData = $this->totpService->setup($user);

        $twoFactorAuth->update([
            'method' => 'totp',
            'secret' => $setupData['secret'],
            'enabled' => false, // Will be enabled after confirmation
        ]);

        return [
            'method' => 'totp',
            'qr_code_url' => $setupData['qr_code_url'],
            'secret' => $setupData['secret'],
            'backup_codes' => [], // Will be added by the calling method
        ];
    }

    /**
     * Enable Email OTP for a user.
     *
     * @param Authenticatable $user
     * @param TwoFactorAuth $twoFactorAuth
     * @param array $options
     * @return array
     */
    protected function enableEmailOTP(Authenticatable $user, TwoFactorAuth $twoFactorAuth, array $options): array
    {
        $twoFactorAuth->update([
            'method' => 'email',
            'enabled' => false, // Will be enabled after confirmation
        ]);

        // Send confirmation code
        $this->emailOtpService->send($user);

        return [
            'method' => 'email',
            'message' => 'A verification code has been sent to your email address.',
        ];
    }

    /**
     * Enable SMS OTP for a user.
     *
     * @param Authenticatable $user
     * @param TwoFactorAuth $twoFactorAuth
     * @param array $options
     * @return array
     */
    protected function enableSMSOTP(Authenticatable $user, TwoFactorAuth $twoFactorAuth, array $options): array
    {
        if (!$this->userHasPhoneNumber($user)) {
            throw new TwoFactorException('User must have a phone number to enable SMS two-factor authentication.');
        }

        $twoFactorAuth->update([
            'method' => 'sms',
            'enabled' => false, // Will be enabled after confirmation
        ]);

        // Send confirmation code
        $this->smsOtpService->send($user);

        return [
            'method' => 'sms',
            'message' => 'A verification code has been sent to your phone number.',
        ];
    }

    /**
     * Check if a specific two-factor method is enabled.
     *
     * @param string $method
     * @return bool
     */
    protected function isMethodEnabled(string $method): bool
    {
        return Config::get("two-factor.methods.{$method}.enabled", false);
    }

    /**
     * Check if a user has a phone number for SMS.
     *
     * @param Authenticatable $user
     * @return bool
     */
    protected function userHasPhoneNumber(Authenticatable $user): bool
    {
        // Check common phone number fields
        $phoneFields = ['phone', 'phone_number', 'mobile', 'mobile_number'];

        foreach ($phoneFields as $field) {
            if (isset($user->{$field}) && !empty($user->{$field})) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if a user is rate limited.
     *
     * @param Authenticatable $user
     * @return bool
     */
    protected function isRateLimited(Authenticatable $user): bool
    {
        // Implementation would check rate limiting logic
        // This is a placeholder - actual implementation would use cache/database
        return false;
    }

    /**
     * Clear rate limiting for a user.
     *
     * @param Authenticatable $user
     * @return void
     */
    protected function clearRateLimit(Authenticatable $user): void
    {
        // Implementation would clear rate limiting
        // This is a placeholder
    }

    /**
     * Record a failed authentication attempt.
     *
     * @param Authenticatable $user
     * @return void
     */
    protected function recordFailedAttempt(Authenticatable $user): void
    {
        // Implementation would record failed attempt for rate limiting
        // This is a placeholder
    }
}
