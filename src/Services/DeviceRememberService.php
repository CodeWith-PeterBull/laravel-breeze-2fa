<?php

declare(strict_types=1);

namespace MetaSoftDevs\LaravelBreeze2FA\Services;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Str;
use Carbon\Carbon;
use MetaSoftDevs\LaravelBreeze2FA\Contracts\DeviceRememberServiceInterface;
use MetaSoftDevs\LaravelBreeze2FA\Models\TwoFactorSession;
use MetaSoftDevs\LaravelBreeze2FA\Events\DeviceRemembered;
use MetaSoftDevs\LaravelBreeze2FA\Events\DeviceForgotten;

/**
 * Device Remember Service
 *
 * This service handles the "remember this device" functionality, allowing
 * users to skip 2FA verification on trusted devices for a specified period.
 * It uses secure tokens and device fingerprinting for security.
 *
 * @package MetaSoftDevs\LaravelBreeze2FA\Services
 * @author MetaSoft Developers <developers@metasoft.dev>
 * @version 1.0.0
 */
class DeviceRememberService implements DeviceRememberServiceInterface
{
    /**
     * Cookie name for storing the device token.
     */
    protected const COOKIE_NAME = 'two_factor_remember';

    /**
     * Check if device remembering is enabled.
     *
     * @return bool
     */
    public function isEnabled(): bool
    {
        return Config::get('two-factor.remember_device.enabled', true);
    }

    /**
     * Remember the current device for a user.
     *
     * @param Authenticatable $user
     * @param int|null $duration Duration in minutes
     * @return string The device token
     */
    public function rememberDevice(Authenticatable $user, ?int $duration = null): string
    {
        if (!$this->isEnabled()) {
            return '';
        }

        $duration = $duration ?? Config::get('two-factor.remember_device.duration', 30 * 24 * 60);
        $token = $this->generateSecureToken();
        $expiresAt = now()->addMinutes($duration);
        $deviceInfo = $this->getDeviceInfo();

        // Create session record
        TwoFactorSession::create([
            'user_id' => $user->getAuthIdentifier(),
            'token' => $token,
            'device_name' => $this->generateDeviceName($deviceInfo),
            'ip_address' => $deviceInfo['ip_address'],
            'user_agent' => $deviceInfo['user_agent'],
            'device_fingerprint' => $this->generateDeviceFingerprint($deviceInfo),
            'expires_at' => $expiresAt,
            'last_used_at' => now(),
        ]);

        // Set cookie
        $this->setRememberCookie($token, $duration);

        // Fire event
        if (Config::get('two-factor.events.enabled', true)) {
            event(new DeviceRemembered($user, $token, $deviceInfo, $expiresAt));
        }

        return $token;
    }

    /**
     * Check if the current device is remembered for a user.
     *
     * @param Authenticatable $user
     * @return bool
     */
    public function isDeviceRemembered(Authenticatable $user): bool
    {
        if (!$this->isEnabled()) {
            return false;
        }

        $token = $this->getRememberToken();

        if (!$token) {
            return false;
        }

        $session = $this->findValidSession($user, $token);

        if (!$session) {
            // Clean up invalid cookie
            $this->forgetDevice();
            return false;
        }

        // Update last used timestamp
        $session->update(['last_used_at' => now()]);

        return true;
    }

    /**
     * Forget the current device for a user.
     *
     * @param Authenticatable|null $user
     * @return bool
     */
    public function forgetDevice(?Authenticatable $user = null): bool
    {
        $token = $this->getRememberToken();

        if (!$token) {
            return true;
        }

        // Remove cookie
        $this->clearRememberCookie();

        // Remove session if user is provided
        if ($user) {
            $session = TwoFactorSession::where('user_id', $user->getAuthIdentifier())
                ->where('token', $token)
                ->first();

            if ($session) {
                $session->delete();

                // Fire event
                if (Config::get('two-factor.events.enabled', true)) {
                    event(new DeviceForgotten($user, $token, 'manual'));
                }
            }
        }

        return true;
    }

    /**
     * Forget all devices for a user.
     *
     * @param Authenticatable $user
     * @return int Number of devices forgotten
     */
    public function forgetAllDevices(Authenticatable $user): int
    {
        $sessions = TwoFactorSession::where('user_id', $user->getAuthIdentifier())->get();
        $count = $sessions->count();

        foreach ($sessions as $session) {
            // Fire event for each device
            if (Config::get('two-factor.events.enabled', true)) {
                event(new DeviceForgotten($user, $session->token, 'forget_all'));
            }

            $session->delete();
        }

        // Clear current device cookie if it belongs to this user
        $currentToken = $this->getRememberToken();
        if ($currentToken && $sessions->where('token', $currentToken)->isNotEmpty()) {
            $this->clearRememberCookie();
        }

        return $count;
    }

    /**
     * Get all remembered devices for a user.
     *
     * @param Authenticatable $user
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getRememberedDevices(Authenticatable $user)
    {
        return TwoFactorSession::where('user_id', $user->getAuthIdentifier())
            ->where('expires_at', '>', now())
            ->orderBy('last_used_at', 'desc')
            ->get();
    }

    /**
     * Forget a specific device by token.
     *
     * @param Authenticatable $user
     * @param string $token
     * @return bool
     */
    public function forgetSpecificDevice(Authenticatable $user, string $token): bool
    {
        $session = TwoFactorSession::where('user_id', $user->getAuthIdentifier())
            ->where('token', $token)
            ->first();

        if (!$session) {
            return false;
        }

        $session->delete();

        // Fire event
        if (Config::get('two-factor.events.enabled', true)) {
            event(new DeviceForgotten($user, $token, 'specific'));
        }

        // Clear cookie if it's the current device
        if ($this->getRememberToken() === $token) {
            $this->clearRememberCookie();
        }

        return true;
    }

    /**
     * Clean up expired device sessions.
     *
     * @return int Number of expired sessions cleaned up
     */
    public function cleanupExpiredSessions(): int
    {
        return TwoFactorSession::where('expires_at', '<', now())->delete();
    }

    /**
     * Get device statistics for a user.
     *
     * @param Authenticatable $user
     * @return array
     */
    public function getDeviceStatistics(Authenticatable $user): array
    {
        $userId = $user->getAuthIdentifier();

        $total = TwoFactorSession::where('user_id', $userId)->count();
        $active = TwoFactorSession::where('user_id', $userId)
            ->where('expires_at', '>', now())
            ->count();
        $recentlyUsed = TwoFactorSession::where('user_id', $userId)
            ->where('last_used_at', '>', now()->subDays(7))
            ->count();

        return [
            'total_devices' => $total,
            'active_devices' => $active,
            'recently_used' => $recentlyUsed,
            'current_device_remembered' => $this->isDeviceRemembered($user),
        ];
    }

    /**
     * Get configuration for device remembering.
     *
     * @return array
     */
    public function getConfiguration(): array
    {
        return [
            'enabled' => $this->isEnabled(),
            'duration' => Config::get('two-factor.remember_device.duration', 30 * 24 * 60),
            'cookie_name' => $this->getCookieName(),
        ];
    }

    /**
     * Generate a secure token for device identification.
     *
     * @return string
     */
    protected function generateSecureToken(): string
    {
        return Str::random(64);
    }

    /**
     * Get device information from the current request.
     *
     * @return array
     */
    protected function getDeviceInfo(): array
    {
        return [
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent() ?: 'Unknown',
            'accept_language' => request()->header('Accept-Language', ''),
            'accept_encoding' => request()->header('Accept-Encoding', ''),
        ];
    }

    /**
     * Generate a device fingerprint for additional security.
     *
     * @param array $deviceInfo
     * @return string
     */
    protected function generateDeviceFingerprint(array $deviceInfo): string
    {
        $fingerprint = implode('|', [
            $deviceInfo['user_agent'],
            $deviceInfo['accept_language'],
            $deviceInfo['accept_encoding'],
            request()->header('Accept', ''),
        ]);

        return hash('sha256', $fingerprint);
    }

    /**
     * Generate a human-readable device name.
     *
     * @param array $deviceInfo
     * @return string
     */
    protected function generateDeviceName(array $deviceInfo): string
    {
        $userAgent = $deviceInfo['user_agent'];

        // Simple browser detection
        if (str_contains($userAgent, 'Chrome')) {
            $browser = 'Chrome';
        } elseif (str_contains($userAgent, 'Firefox')) {
            $browser = 'Firefox';
        } elseif (str_contains($userAgent, 'Safari')) {
            $browser = 'Safari';
        } elseif (str_contains($userAgent, 'Edge')) {
            $browser = 'Edge';
        } else {
            $browser = 'Unknown Browser';
        }

        // Simple OS detection
        if (str_contains($userAgent, 'Windows')) {
            $os = 'Windows';
        } elseif (str_contains($userAgent, 'Mac')) {
            $os = 'macOS';
        } elseif (str_contains($userAgent, 'Linux')) {
            $os = 'Linux';
        } elseif (str_contains($userAgent, 'Android')) {
            $os = 'Android';
        } elseif (str_contains($userAgent, 'iOS')) {
            $os = 'iOS';
        } else {
            $os = 'Unknown OS';
        }

        return "{$browser} on {$os}";
    }

    /**
     * Find a valid session for the user and token.
     *
     * @param Authenticatable $user
     * @param string $token
     * @return TwoFactorSession|null
     */
    protected function findValidSession(Authenticatable $user, string $token): ?TwoFactorSession
    {
        return TwoFactorSession::where('user_id', $user->getAuthIdentifier())
            ->where('token', $token)
            ->where('expires_at', '>', now())
            ->first();
    }

    /**
     * Get the remember token from the cookie.
     *
     * @return string|null
     */
    protected function getRememberToken(): ?string
    {
        $cookieName = $this->getCookieName();

        return request()->cookie($cookieName);
    }

    /**
     * Set the remember cookie.
     *
     * @param string $token
     * @param int $duration Duration in minutes
     * @return void
     */
    protected function setRememberCookie(string $token, int $duration): void
    {
        $cookieName = $this->getCookieName();

        Cookie::queue(Cookie::make(
            $cookieName,
            $token,
            $duration,
            null, // path
            null, // domain
            request()->isSecure(), // secure
            true, // httpOnly
            false, // raw
            'lax' // sameSite
        ));
    }

    /**
     * Clear the remember cookie.
     *
     * @return void
     */
    protected function clearRememberCookie(): void
    {
        $cookieName = $this->getCookieName();

        Cookie::queue(Cookie::forget($cookieName));
    }

    /**
     * Get the cookie name for device remembering.
     *
     * @return string
     */
    protected function getCookieName(): string
    {
        return Config::get('two-factor.remember_device.name', self::COOKIE_NAME);
    }

    /**
     * Validate device fingerprint for additional security.
     *
     * @param TwoFactorSession $session
     * @return bool
     */
    protected function validateDeviceFingerprint(TwoFactorSession $session): bool
    {
        $currentFingerprint = $this->generateDeviceFingerprint($this->getDeviceInfo());

        // Allow some flexibility in fingerprint matching
        return $session->device_fingerprint === $currentFingerprint;
    }

    /**
     * Check if device fingerprint validation is enabled.
     *
     * @return bool
     */
    protected function isFingerprintValidationEnabled(): bool
    {
        return Config::get('two-factor.remember_device.fingerprint_validation', true);
    }

    /**
     * Get security information about the current device session.
     *
     * @param Authenticatable $user
     * @return array|null
     */
    public function getCurrentDeviceInfo(Authenticatable $user): ?array
    {
        $token = $this->getRememberToken();

        if (!$token) {
            return null;
        }

        $session = $this->findValidSession($user, $token);

        if (!$session) {
            return null;
        }

        return [
            'device_name' => $session->device_name,
            'ip_address' => $session->ip_address,
            'last_used_at' => $session->last_used_at,
            'expires_at' => $session->expires_at,
            'created_at' => $session->created_at,
            'is_current_ip' => $session->ip_address === request()->ip(),
            'days_until_expiry' => $session->expires_at->diffInDays(now()),
        ];
    }
}
