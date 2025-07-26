<?php

declare(strict_types=1);

namespace MetaSoftDevs\LaravelBreeze2FA\Contracts;

use Illuminate\Contracts\Auth\Authenticatable;
use MetaSoftDevs\LaravelBreeze2FA\Exceptions\TwoFactorException;

/**
 * TOTP Service Interface
 *
 * Interface for Time-based One-Time Password (TOTP) service implementation.
 */
interface TOTPServiceInterface
{
    /**
     * Setup TOTP for a user by generating a secret and QR code.
     *
     * @param Authenticatable $user
     * @return array Setup data including secret and QR code URL
     * @throws TwoFactorException
     */
    public function setup(Authenticatable $user): array;

    /**
     * Verify a TOTP code for a user.
     *
     * @param Authenticatable $user
     * @param string $code
     * @return bool
     */
    public function verify(Authenticatable $user, string $code): bool;

    /**
     * Generate a QR code URL for TOTP setup.
     *
     * @param Authenticatable $user
     * @param string $secret
     * @return string
     */
    public function generateQrCodeUrl(Authenticatable $user, string $secret): string;

    /**
     * Generate a QR code SVG from a URL.
     *
     * @param string $url
     * @return string
     */
    public function generateQrCodeSvg(string $url): string;

    /**
     * Generate a QR code data URI (base64 encoded SVG).
     *
     * @param string $url
     * @return string
     */
    public function generateQrCodeDataUri(string $url): string;

    /**
     * Get configuration for TOTP.
     *
     * @return array
     */
    public function getConfiguration(): array;
}
