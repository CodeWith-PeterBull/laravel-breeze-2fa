<?php

declare(strict_types=1);

namespace MetaSoftDevs\LaravelBreeze2FA\Services;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use MetaSoftDevs\LaravelBreeze2FA\Contracts\SMSOTPServiceInterface;
use MetaSoftDevs\LaravelBreeze2FA\Services\SMS\TwilioSMSProvider;
use MetaSoftDevs\LaravelBreeze2FA\Services\SMS\VonageSMSProvider;
use MetaSoftDevs\LaravelBreeze2FA\Services\SMS\MessageBirdSMSProvider;
use MetaSoftDevs\LaravelBreeze2FA\Exceptions\TwoFactorException;
use MetaSoftDevs\LaravelBreeze2FA\Exceptions\SMSProviderException;

/**
 * SMS One-Time Password (OTP) Service
 *
 * This service handles SMS-based OTP functionality including code generation,
 * SMS sending via multiple providers, and verification. It supports multiple
 * SMS providers with a driver-based architecture.
 *
 * @package MetaSoftDevs\LaravelBreeze2FA\Services
 * @author Meta Software Developers <info@metasoftdevs.com>
 * @version 1.0.0
 */
class SMSOTPService implements SMSOTPServiceInterface
{
    /**
     * Cache key prefix for SMS OTP codes.
     */
    protected const CACHE_PREFIX = 'two_factor_sms_otp';

    /**
     * Cache key prefix for rate limiting.
     */
    protected const RATE_LIMIT_PREFIX = 'two_factor_sms_rate_limit';

    /**
     * Available SMS providers.
     */
    protected array $providers = [
        'twilio' => TwilioSMSProvider::class,
        'vonage' => VonageSMSProvider::class,
        'messagebird' => MessageBirdSMSProvider::class,
    ];

    /**
     * Send an OTP code via SMS to the user.
     *
     * @param Authenticatable $user
     * @return bool
     * @throws TwoFactorException
     */
    public function send(Authenticatable $user): bool
    {
        if (!Config::get('two-factor.methods.sms.enabled', false)) {
            throw new TwoFactorException('SMS OTP method is not enabled.');
        }

        $phoneNumber = $this->getUserPhoneNumber($user);

        if (!$phoneNumber) {
            throw new TwoFactorException('User does not have a valid phone number.');
        }

        // Check rate limiting
        if ($this->isRateLimited($user)) {
            throw new TwoFactorException('Too many SMS requests. Please wait before requesting another code.');
        }

        // Generate a new code
        $code = $this->generateCode();

        // Store the code in cache
        $this->storeCode($user, $code);

        // Record the send attempt for rate limiting
        $this->recordSendAttempt($user);

        try {
            // Send the SMS
            $this->sendSMS($user, $phoneNumber, $code);

            return true;
        } catch (SMSProviderException $e) {
            // Remove the stored code if SMS sending failed
            $this->clearCode($user);

            Log::error('Failed to send SMS OTP', [
                'user_id' => $user->getAuthIdentifier(),
                'phone_number' => $this->maskPhoneNumber($phoneNumber),
                'error' => $e->getMessage(),
            ]);

            throw new TwoFactorException('Failed to send verification SMS: ' . $e->getMessage());
        }
    }

    /**
     * Verify an OTP code for a user.
     *
     * @param Authenticatable $user
     * @param string $code
     * @return bool
     */
    public function verify(Authenticatable $user, string $code): bool
    {
        if (!Config::get('two-factor.methods.sms.enabled', false)) {
            return false;
        }

        $storedCode = $this->getStoredCode($user);

        if (!$storedCode) {
            return false;
        }

        // Clean the provided code
        $cleanCode = preg_replace('/[^0-9]/', '', $code);

        if ($cleanCode === $storedCode) {
            // Clear the code after successful verification
            $this->clearCode($user);
            return true;
        }

        return false;
    }

    /**
     * Generate a random numeric OTP code.
     *
     * @return string
     */
    public function generateCode(): string
    {
        $length = Config::get('two-factor.methods.sms.length', 6);

        // Generate a random numeric code
        $min = (int) str_repeat('1', $length);
        $max = (int) str_repeat('9', $length);

        return str_pad((string) random_int($min, $max), $length, '0', STR_PAD_LEFT);
    }

    /**
     * Check if there's a valid stored code for the user.
     *
     * @param Authenticatable $user
     * @return bool
     */
    public function hasValidCode(Authenticatable $user): bool
    {
        return !is_null($this->getStoredCode($user));
    }

    /**
     * Get the time remaining for the current code.
     *
     * @param Authenticatable $user
     * @return int Seconds remaining (0 if no code or expired)
     */
    public function getTimeRemaining(Authenticatable $user): int
    {
        $cacheKey = $this->getCacheKey($user);
        $ttl = Cache::getStore()->getPrefix() ?
            Cache::getStore()->get($cacheKey . '_ttl') :
            0;

        return max(0, $ttl - time());
    }

    /**
     * Clear any stored code for the user.
     *
     * @param Authenticatable $user
     * @return bool
     */
    public function clearCode(Authenticatable $user): bool
    {
        $cacheKey = $this->getCacheKey($user);

        return Cache::forget($cacheKey);
    }

    /**
     * Check if the user is rate limited for sending SMS.
     *
     * @param Authenticatable $user
     * @return bool
     */
    public function isRateLimited(Authenticatable $user): bool
    {
        $rateLimitKey = $this->getRateLimitKey($user);
        $maxAttempts = Config::get('two-factor.methods.sms.max_sends_per_hour', 3);

        $attempts = Cache::get($rateLimitKey, 0);

        return $attempts >= $maxAttempts;
    }

    /**
     * Get the number of send attempts for the user in the current window.
     *
     * @param Authenticatable $user
     * @return int
     */
    public function getSendAttempts(Authenticatable $user): int
    {
        $rateLimitKey = $this->getRateLimitKey($user);

        return Cache::get($rateLimitKey, 0);
    }

    /**
     * Get configuration for SMS OTP.
     *
     * @return array
     */
    public function getConfiguration(): array
    {
        return [
            'enabled' => Config::get('two-factor.methods.sms.enabled', false),
            'provider' => Config::get('two-factor.methods.sms.provider', 'twilio'),
            'expiry' => Config::get('two-factor.methods.sms.expiry', 300),
            'length' => Config::get('two-factor.methods.sms.length', 6),
            'message' => Config::get('two-factor.methods.sms.message', 'Your verification code is: {code}'),
            'max_sends_per_hour' => Config::get('two-factor.methods.sms.max_sends_per_hour', 3),
        ];
    }

    /**
     * Test SMS configuration with a given phone number.
     *
     * @param string $phoneNumber
     * @return bool
     * @throws TwoFactorException
     */
    public function testSMSConfiguration(string $phoneNumber): bool
    {
        if (!$this->isValidPhoneNumber($phoneNumber)) {
            throw new TwoFactorException('Invalid phone number format.');
        }

        try {
            $testCode = $this->generateCode();
            $message = str_replace('{code}', $testCode, 'Test message: {code}');

            $provider = $this->getSMSProvider();
            $provider->send($phoneNumber, $message);

            return true;
        } catch (SMSProviderException $e) {
            throw new TwoFactorException('SMS configuration test failed: ' . $e->getMessage());
        }
    }

    /**
     * Store the OTP code in cache.
     *
     * @param Authenticatable $user
     * @param string $code
     * @return bool
     */
    protected function storeCode(Authenticatable $user, string $code): bool
    {
        $cacheKey = $this->getCacheKey($user);
        $expiry = Config::get('two-factor.methods.sms.expiry', 300); // 5 minutes

        return Cache::put($cacheKey, $code, $expiry);
    }

    /**
     * Get the stored OTP code for a user.
     *
     * @param Authenticatable $user
     * @return string|null
     */
    protected function getStoredCode(Authenticatable $user): ?string
    {
        $cacheKey = $this->getCacheKey($user);

        return Cache::get($cacheKey);
    }

    /**
     * Get the cache key for storing the user's OTP code.
     *
     * @param Authenticatable $user
     * @return string
     */
    protected function getCacheKey(Authenticatable $user): string
    {
        $prefix = Config::get('two-factor.cache.prefix', 'two_factor');
        $userId = $user->getAuthIdentifier();

        return "{$prefix}:" . self::CACHE_PREFIX . ":{$userId}";
    }

    /**
     * Get the rate limiting cache key for a user.
     *
     * @param Authenticatable $user
     * @return string
     */
    protected function getRateLimitKey(Authenticatable $user): string
    {
        $prefix = Config::get('two-factor.cache.prefix', 'two_factor');
        $userId = $user->getAuthIdentifier();

        return "{$prefix}:" . self::RATE_LIMIT_PREFIX . ":{$userId}";
    }

    /**
     * Record a send attempt for rate limiting.
     *
     * @param Authenticatable $user
     * @return void
     */
    protected function recordSendAttempt(Authenticatable $user): void
    {
        $rateLimitKey = $this->getRateLimitKey($user);
        $attempts = Cache::get($rateLimitKey, 0);

        // Increment attempts and store for 1 hour
        Cache::put($rateLimitKey, $attempts + 1, 3600);
    }

    /**
     * Send the SMS to the user.
     *
     * @param Authenticatable $user
     * @param string $phoneNumber
     * @param string $code
     * @return void
     * @throws SMSProviderException
     */
    protected function sendSMS(Authenticatable $user, string $phoneNumber, string $code): void
    {
        $message = $this->formatMessage($user, $code);
        $provider = $this->getSMSProvider();

        $provider->send($phoneNumber, $message);

        Log::info('SMS OTP sent successfully', [
            'user_id' => $user->getAuthIdentifier(),
            'phone_number' => $this->maskPhoneNumber($phoneNumber),
            'provider' => Config::get('two-factor.methods.sms.provider'),
        ]);
    }

    /**
     * Format the SMS message with the code.
     *
     * @param Authenticatable $user
     * @param string $code
     * @return string
     */
    protected function formatMessage(Authenticatable $user, string $code): string
    {
        $template = Config::get('two-factor.methods.sms.message', 'Your verification code is: {code}');
        $appName = Config::get('app.name', 'Laravel');

        $placeholders = [
            '{code}' => $code,
            '{app_name}' => $appName,
            '{user_name}' => $this->getUserName($user),
        ];

        return str_replace(array_keys($placeholders), array_values($placeholders), $template);
    }

    /**
     * Get the SMS provider instance.
     *
     * @return mixed
     * @throws SMSProviderException
     */
    protected function getSMSProvider()
    {
        $providerName = Config::get('two-factor.methods.sms.provider', 'twilio');

        if (!isset($this->providers[$providerName])) {
            throw new SMSProviderException("Unknown SMS provider: {$providerName}");
        }

        $providerClass = $this->providers[$providerName];
        $config = Config::get("two-factor.sms_providers.{$providerName}", []);

        if (empty($config)) {
            throw new SMSProviderException("SMS provider '{$providerName}' is not configured.");
        }

        return new $providerClass($config);
    }

    /**
     * Get the user's phone number.
     *
     * @param Authenticatable $user
     * @return string|null
     */
    protected function getUserPhoneNumber(Authenticatable $user): ?string
    {
        // Try different common phone number fields
        $phoneFields = ['phone', 'phone_number', 'mobile', 'mobile_number'];

        foreach ($phoneFields as $field) {
            if (isset($user->{$field}) && !empty($user->{$field})) {
                $phone = $user->{$field};

                // Basic phone number validation
                if ($this->isValidPhoneNumber($phone)) {
                    return $this->normalizePhoneNumber($phone);
                }
            }
        }

        return null;
    }

    /**
     * Get the user's name for personalization.
     *
     * @param Authenticatable $user
     * @return string
     */
    protected function getUserName(Authenticatable $user): string
    {
        // Try different common name fields
        $nameFields = ['name', 'full_name', 'first_name', 'username'];

        foreach ($nameFields as $field) {
            if (isset($user->{$field}) && !empty($user->{$field})) {
                return $user->{$field};
            }
        }

        return 'User';
    }

    /**
     * Validate phone number format.
     *
     * @param string $phoneNumber
     * @return bool
     */
    protected function isValidPhoneNumber(string $phoneNumber): bool
    {
        // Remove all non-digit characters except +
        $cleaned = preg_replace('/[^+0-9]/', '', $phoneNumber);

        // Basic E.164 format validation
        if (preg_match('/^\+[1-9]\d{1,14}$/', $cleaned)) {
            return true;
        }

        // Allow local numbers (10-15 digits)
        if (preg_match('/^\d{10,15}$/', $cleaned)) {
            return true;
        }

        return false;
    }

    /**
     * Normalize phone number to E.164 format.
     *
     * @param string $phoneNumber
     * @return string
     */
    protected function normalizePhoneNumber(string $phoneNumber): string
    {
        // Remove all non-digit characters except +
        $cleaned = preg_replace('/[^+0-9]/', '', $phoneNumber);

        // If it doesn't start with +, assume it's a local US number
        if (!str_starts_with($cleaned, '+')) {
            // Add US country code if it looks like a US number
            if (strlen($cleaned) === 10) {
                $cleaned = '+1' . $cleaned;
            } elseif (strlen($cleaned) === 11 && str_starts_with($cleaned, '1')) {
                $cleaned = '+' . $cleaned;
            }
        }

        return $cleaned;
    }

    /**
     * Mask phone number for logging.
     *
     * @param string $phoneNumber
     * @return string
     */
    protected function maskPhoneNumber(string $phoneNumber): string
    {
        if (strlen($phoneNumber) <= 4) {
            return str_repeat('*', strlen($phoneNumber));
        }

        return substr($phoneNumber, 0, 3) . str_repeat('*', strlen($phoneNumber) - 6) . substr($phoneNumber, -3);
    }

    /**
     * Get delivery status information.
     *
     * @param Authenticatable $user
     * @return array
     */
    public function getDeliveryStatus(Authenticatable $user): array
    {
        return [
            'has_valid_code' => $this->hasValidCode($user),
            'time_remaining' => $this->getTimeRemaining($user),
            'send_attempts' => $this->getSendAttempts($user),
            'is_rate_limited' => $this->isRateLimited($user),
            'user_phone' => $this->maskPhoneNumber($this->getUserPhoneNumber($user) ?? ''),
        ];
    }

    /**
     * Cleanup expired codes and rate limiting data.
     *
     * @return int Number of cleaned up entries
     */
    public function cleanup(): int
    {
        // This would typically be handled by cache TTL automatically
        // But we can implement manual cleanup if needed
        $cleaned = 0;

        // In a real implementation, you might want to scan cache keys
        // and remove expired entries, but most cache stores handle this automatically

        return $cleaned;
    }
}
