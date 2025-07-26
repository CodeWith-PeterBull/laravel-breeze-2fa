<?php

declare(strict_types=1);

namespace MetaSoftDevs\LaravelBreeze2FA\Contracts;

use Illuminate\Contracts\Auth\Authenticatable;
use MetaSoftDevs\LaravelBreeze2FA\Exceptions\TwoFactorException;

/**
 * SMS OTP Service Interface
 *
 * Interface for SMS One-Time Password service implementation.
 */
interface SMSOTPServiceInterface
{
    /**
     * Send an OTP code via SMS to the user.
     *
     * @param Authenticatable $user
     * @return bool
     * @throws TwoFactorException
     */
    public function send(Authenticatable $user): bool;

    /**
     * Verify an OTP code for a user.
     *
     * @param Authenticatable $user
     * @param string $code
     * @return bool
     */
    public function verify(Authenticatable $user, string $code): bool;

    /**
     * Generate a random numeric OTP code.
     *
     * @return string
     */
    public function generateCode(): string;

    /**
     * Check if there's a valid stored code for the user.
     *
     * @param Authenticatable $user
     * @return bool
     */
    public function hasValidCode(Authenticatable $user): bool;

    /**
     * Clear any stored code for the user.
     *
     * @param Authenticatable $user
     * @return bool
     */
    public function clearCode(Authenticatable $user): bool;

    /**
     * Get configuration for SMS OTP.
     *
     * @return array
     */
    public function getConfiguration(): array;
}
