<?php

declare(strict_types=1);

namespace MetaSoftDevs\LaravelBreeze2FA\Contracts;

use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Device Remember Service Interface
 *
 * Interface for device remember service implementation.
 */
interface DeviceRememberServiceInterface
{
    /**
     * Check if device remembering is enabled.
     *
     * @return bool
     */
    public function isEnabled(): bool;

    /**
     * Remember the current device for a user.
     *
     * @param Authenticatable $user
     * @param int|null $duration Duration in minutes
     * @return string The device token
     */
    public function rememberDevice(Authenticatable $user, ?int $duration = null): string;

    /**
     * Check if the current device is remembered for a user.
     *
     * @param Authenticatable $user
     * @return bool
     */
    public function isDeviceRemembered(Authenticatable $user): bool;

    /**
     * Forget the current device for a user.
     *
     * @param Authenticatable|null $user
     * @return bool
     */
    public function forgetDevice(?Authenticatable $user = null): bool;

    /**
     * Forget all devices for a user.
     *
     * @param Authenticatable $user
     * @return int Number of devices forgotten
     */
    public function forgetAllDevices(Authenticatable $user): int;

    /**
     * Get all remembered devices for a user.
     *
     * @param Authenticatable $user
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getRememberedDevices(Authenticatable $user);

    /**
     * Clean up expired device sessions.
     *
     * @return int Number of expired sessions cleaned up
     */
    public function cleanupExpiredSessions(): int;

    /**
     * Get configuration for device remembering.
     *
     * @return array
     */
    public function getConfiguration(): array;
}
