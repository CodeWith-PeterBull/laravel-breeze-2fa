<?php

declare(strict_types=1);

namespace MetaSoftDevs\LaravelBreeze2FA\Services;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use MetaSoftDevs\LaravelBreeze2FA\Contracts\EmailOTPServiceInterface;
use MetaSoftDevs\LaravelBreeze2FA\Mail\TwoFactorCodeMail;
use MetaSoftDevs\LaravelBreeze2FA\Exceptions\TwoFactorException;

/**
 * Email One-Time Password (OTP) Service
 *
 * This service handles email-based OTP functionality including code generation,
 * email sending, and verification. Codes are temporary and time-limited for security.
 *
 * @package MetaSoftDevs\LaravelBreeze2FA\Services
 * @author Meta Software Developers <info@metasoftdevs.com>
 * @version 1.0.0
 */
class EmailOTPService implements EmailOTPServiceInterface
{
    /**
     * Cache key prefix for email OTP codes.
     */
    protected const CACHE_PREFIX = 'two_factor_email_otp';

    /**
     * Cache key prefix for rate limiting.
     */
    protected const RATE_LIMIT_PREFIX = 'two_factor_email_rate_limit';

    /**
     * Send an OTP code via email to the user.
     *
     * @param Authenticatable $user
     * @return bool
     * @throws TwoFactorException
     */
    public function send(Authenticatable $user): bool
    {
        if (!Config::get('two-factor.methods.email.enabled', true)) {
            throw new TwoFactorException('Email OTP method is not enabled.');
        }

        $email = $this->getUserEmail($user);

        if (!$email) {
            throw new TwoFactorException('User does not have a valid email address.');
        }

        // Check rate limiting
        if ($this->isRateLimited($user)) {
            throw new TwoFactorException('Too many email requests. Please wait before requesting another code.');
        }

        // Generate a new code
        $code = $this->generateCode();

        // Store the code in cache
        $this->storeCode($user, $code);

        // Record the send attempt for rate limiting
        $this->recordSendAttempt($user);

        try {
            // Send the email
            $this->sendEmail($user, $email, $code);

            return true;
        } catch (\Exception $e) {
            // Remove the stored code if email sending failed
            $this->clearCode($user);

            throw new TwoFactorException('Failed to send verification email: ' . $e->getMessage());
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
        if (!Config::get('two-factor.methods.email.enabled', true)) {
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
        $length = Config::get('two-factor.methods.email.length', 6);

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
     * Check if the user is rate limited for sending emails.
     *
     * @param Authenticatable $user
     * @return bool
     */
    public function isRateLimited(Authenticatable $user): bool
    {
        $rateLimitKey = $this->getRateLimitKey($user);
        $maxAttempts = Config::get('two-factor.methods.email.max_sends_per_hour', 5);

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
     * Get configuration for email OTP.
     *
     * @return array
     */
    public function getConfiguration(): array
    {
        return [
            'enabled' => Config::get('two-factor.methods.email.enabled', true),
            'expiry' => Config::get('two-factor.methods.email.expiry', 300),
            'length' => Config::get('two-factor.methods.email.length', 6),
            'template' => Config::get('two-factor.methods.email.template', 'two-factor::emails.otp'),
            'subject' => Config::get('two-factor.methods.email.subject', 'Your verification code'),
            'max_sends_per_hour' => Config::get('two-factor.methods.email.max_sends_per_hour', 5),
        ];
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
        $expiry = Config::get('two-factor.methods.email.expiry', 300); // 5 minutes

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
     * Send the OTP email to the user.
     *
     * @param Authenticatable $user
     * @param string $email
     * @param string $code
     * @return void
     * @throws \Exception
     */
    protected function sendEmail(Authenticatable $user, string $email, string $code): void
    {
        $queueName = Config::get('two-factor.methods.email.queue');

        $mailable = new TwoFactorCodeMail($user, $code);

        if ($queueName) {
            Mail::to($email)->queue($mailable->onQueue($queueName));
        } else {
            Mail::to($email)->send($mailable);
        }
    }

    /**
     * Get the user's email address.
     *
     * @param Authenticatable $user
     * @return string|null
     */
    protected function getUserEmail(Authenticatable $user): ?string
    {
        // Try different common email field names
        $emailFields = ['email', 'email_address', 'user_email'];

        foreach ($emailFields as $field) {
            if (isset($user->{$field}) && !empty($user->{$field})) {
                $email = $user->{$field};

                // Validate email format
                if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    return $email;
                }
            }
        }

        return null;
    }

    /**
     * Format code for display in email (add spaces for readability).
     *
     * @param string $code
     * @return string
     */
    public function formatCodeForDisplay(string $code): string
    {
        // Add spaces every 3 digits for 6-digit codes, every 2 for others
        $length = strlen($code);

        if ($length === 6) {
            return implode(' ', str_split($code, 3));
        } elseif ($length === 8) {
            return implode(' ', str_split($code, 4));
        } else {
            return implode(' ', str_split($code, 2));
        }
    }

    /**
     * Get email delivery status information.
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
            'user_email' => $this->getUserEmail($user),
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

    /**
     * Test email configuration by sending a test email.
     *
     * @param string $testEmail
     * @return bool
     * @throws TwoFactorException
     */
    public function testEmailConfiguration(string $testEmail): bool
    {
        if (!filter_var($testEmail, FILTER_VALIDATE_EMAIL)) {
            throw new TwoFactorException('Invalid test email address.');
        }

        try {
            $testCode = $this->generateCode();

            // Create a mock user object for testing
            $testUser = new class($testEmail) implements Authenticatable {
                public function __construct(public string $email) {}
                public function getAuthIdentifierName()
                {
                    return 'id';
                }
                public function getAuthIdentifier()
                {
                    return 'test';
                }
                public function getAuthPassword()
                {
                    return '';
                }
                public function getRememberToken()
                {
                    return '';
                }
                public function setRememberToken($value) {}
                public function getRememberTokenName()
                {
                    return '';
                }
            };

            $this->sendEmail($testUser, $testEmail, $testCode);

            return true;
        } catch (\Exception $e) {
            throw new TwoFactorException('Email configuration test failed: ' . $e->getMessage());
        }
    }
}
