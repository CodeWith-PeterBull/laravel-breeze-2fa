<?php

declare(strict_types=1);

namespace MetaSoftDevs\LaravelBreeze2FA\Contracts;

use Illuminate\Contracts\Auth\Authenticatable;
use MetaSoftDevs\LaravelBreeze2FA\Exceptions\TwoFactorException;
use MetaSoftDevs\LaravelBreeze2FA\Exceptions\InvalidCodeException;
use MetaSoftDevs\LaravelBreeze2FA\Exceptions\RateLimitExceededException;

/**
 * Two-Factor Authentication Manager Interface
 *
 * This interface defines the contract for the main two-factor authentication
 * manager service. It provides all the necessary methods for managing 2FA
 * functionality including setup, verification, and user management.
 *
 * @package MetaSoftDevs\LaravelBreeze2FA\Contracts
 * @author MetaSoft Developers <info@metasoftdevs.com>
 * @version 1.0.0
 */
interface TwoFactorManagerInterface
{
    /**
     * Check if two-factor authentication is enabled globally.
     *
     * @return bool
     */
    public function isEnabled(): bool;

    /**
     * Check if two-factor authentication is required for all users.
     *
     * @return bool
     */
    public function isRequired(): bool;

    /**
     * Check if a user has two-factor authentication enabled.
     *
     * @param Authenticatable $user
     * @return bool
     */
    public function isEnabledForUser(Authenticatable $user): bool;

    /**
     * Enable two-factor authentication for a user.
     *
     * This method initiates the 2FA setup process for a user. Depending on the
     * method chosen, it will generate secrets, send codes, or prepare QR codes.
     * The setup must be confirmed before 2FA is fully activated.
     *
     * @param Authenticatable $user The user to enable 2FA for
     * @param string $method The 2FA method to enable (totp, email, sms)
     * @param array $options Additional options for the specific method
     * @return array Setup information including secrets, QR codes, or confirmation messages
     * @throws TwoFactorException When 2FA is disabled or method is invalid
     */
    public function enable(Authenticatable $user, string $method = 'totp', array $options = []): array;

    /**
     * Confirm and activate two-factor authentication for a user.
     *
     * This method verifies the confirmation code and fully activates 2FA
     * for the user. After confirmation, the user will be required to
     * provide 2FA codes on subsequent logins.
     *
     * @param Authenticatable $user The user confirming their 2FA setup
     * @param string $code The verification code provided by the user
     * @return bool True if confirmation was successful
     * @throws TwoFactorException When no pending setup is found
     * @throws InvalidCodeException When the provided code is invalid
     */
    public function confirm(Authenticatable $user, string $code): bool;

    /**
     * Disable two-factor authentication for a user.
     *
     * This method completely disables 2FA for a user, removing all associated
     * data including secrets, recovery codes, and remembered devices.
     *
     * @param Authenticatable $user The user to disable 2FA for
     * @return bool True if 2FA was successfully disabled
     */
    public function disable(Authenticatable $user): bool;

    /**
     * Verify a two-factor authentication code.
     *
     * This method verifies a 2FA code provided during login. It supports
     * verification of TOTP codes, email/SMS OTP codes, and recovery codes.
     * Rate limiting is applied to prevent brute force attacks.
     *
     * @param Authenticatable $user The user attempting to verify
     * @param string $code The 2FA code to verify
     * @param bool $rememberDevice Whether to remember this device to skip future 2FA
     * @return bool True if the code is valid and verification successful
     * @throws TwoFactorException When 2FA is not enabled for the user
     * @throws InvalidCodeException When the provided code is invalid
     * @throws RateLimitExceededException When too many attempts have been made
     */
    public function verify(Authenticatable $user, string $code, bool $rememberDevice = false): bool;

    /**
     * Send a new OTP code to the user.
     *
     * This method sends a fresh OTP code via email or SMS, depending on the
     * user's configured method or the method specified. Used for methods
     * that require code delivery.
     *
     * @param Authenticatable $user The user to send the code to
     * @param string|null $method The method to use (null for user's default)
     * @return bool True if the code was sent successfully
     * @throws TwoFactorException When 2FA is not enabled or method doesn't support sending
     */
    public function sendCode(Authenticatable $user, ?string $method = null): bool;

    /**
     * Check if a device is remembered for the user.
     *
     * When device remembering is enabled, this method checks if the current
     * device/browser has been marked as trusted and can skip 2FA verification.
     *
     * @param Authenticatable $user The user to check
     * @return bool True if the current device is remembered and trusted
     */
    public function isDeviceRemembered(Authenticatable $user): bool;

    /**
     * Get available two-factor methods for a user.
     *
     * Returns a list of 2FA methods that are enabled in the configuration
     * and available for the specific user (e.g., SMS requires a phone number).
     *
     * @param Authenticatable $user The user to get methods for
     * @return array Associative array of method => details
     */
    public function getAvailableMethods(Authenticatable $user): array;

    /**
     * Get the user's current two-factor authentication status and settings.
     *
     * This method returns comprehensive information about the user's 2FA
     * configuration including enabled status, method, confirmation status,
     * and recovery code information.
     *
     * @param Authenticatable $user The user to get status for
     * @return array Status information including enabled, method, confirmed, etc.
     */
    public function getStatus(Authenticatable $user): array;
}
