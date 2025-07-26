<?php

declare(strict_types=1);

namespace MetaSoftDevs\LaravelBreeze2FA\Contracts;

use Illuminate\Contracts\Auth\Authenticatable;
use MetaSoftDevs\LaravelBreeze2FA\Exceptions\TwoFactorException;

/**
 * Recovery Code Service Interface
 *
 * Interface for recovery code service implementation.
 */
interface RecoveryCodeServiceInterface
{
    /**
     * Generate recovery codes for a user.
     *
     * @param Authenticatable $user
     * @param int|null $count
     * @return array
     * @throws TwoFactorException
     */
    public function generate(Authenticatable $user, ?int $count = null): array;

    /**
     * Verify a recovery code for a user.
     *
     * @param Authenticatable $user
     * @param string $code
     * @return bool
     */
    public function verify(Authenticatable $user, string $code): bool;

    /**
     * Check if a code looks like a recovery code format.
     *
     * @param string $code
     * @return bool
     */
    public function isRecoveryCode(string $code): bool;

    /**
     * Get the count of unused recovery codes for a user.
     *
     * @param Authenticatable $user
     * @return int
     */
    public function getUnusedCount(Authenticatable $user): int;

    /**
     * Regenerate all recovery codes for a user.
     *
     * @param Authenticatable $user
     * @param int|null $count
     * @return array
     * @throws TwoFactorException
     */
    public function regenerate(Authenticatable $user, ?int $count = null): array;

    /**
     * Delete all recovery codes for a user.
     *
     * @param Authenticatable $user
     * @return int
     */
    public function deleteAll(Authenticatable $user): int;

    /**
     * Get configuration for recovery codes.
     *
     * @return array
     */
    public function getConfiguration(): array;
}
