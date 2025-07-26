<?php

declare(strict_types=1);

namespace MetaSoftDevs\LaravelBreeze2FA\Services;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Config;
use PragmaRX\Google2FA\Google2FA;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use MetaSoftDevs\LaravelBreeze2FA\Contracts\TOTPServiceInterface;
use MetaSoftDevs\LaravelBreeze2FA\Models\TwoFactorAuth;
use MetaSoftDevs\LaravelBreeze2FA\Exceptions\TwoFactorException;

/**
 * Time-based One-Time Password (TOTP) Service
 *
 * This service handles TOTP (Time-based One-Time Password) functionality
 * including secret generation, QR code creation, and code verification
 * for authenticator apps like Google Authenticator, Authy, etc.
 *
 * @package MetaSoftDevs\LaravelBreeze2FA\Services
 * @author MetaSoft Developers <metasoftdevs.com>
 * @version 1.0.0
 */
class TOTPService implements TOTPServiceInterface
{
    /**
     * Google2FA instance for TOTP operations.
     */
    protected Google2FA $google2fa;

    /**
     * QR code writer instance.
     */
    protected Writer $qrCodeWriter;

    /**
     * Create a new TOTP service instance.
     */
    public function __construct()
    {
        $this->google2fa = new Google2FA();
        $this->initializeQrCodeWriter();
    }

    /**
     * Setup TOTP for a user by generating a secret and QR code.
     *
     * @param Authenticatable $user
     * @return array Setup data including secret and QR code URL
     * @throws TwoFactorException
     */
    public function setup(Authenticatable $user): array
    {
        if (!Config::get('two-factor.methods.totp.enabled', true)) {
            throw new TwoFactorException('TOTP method is not enabled.');
        }

        // Generate a new secret
        $secret = $this->google2fa->generateSecretKey();

        // Create QR code URL
        $qrCodeUrl = $this->generateQrCodeUrl($user, $secret);

        // Generate QR code SVG
        $qrCodeSvg = $this->generateQrCodeSvg($qrCodeUrl);

        return [
            'secret' => $secret,
            'qr_code_url' => $qrCodeUrl,
            'qr_code_svg' => $qrCodeSvg,
            'manual_entry_key' => $this->formatSecretForManualEntry($secret),
        ];
    }

    /**
     * Verify a TOTP code for a user.
     *
     * @param Authenticatable $user
     * @param string $code
     * @return bool
     */
    public function verify(Authenticatable $user, string $code): bool
    {
        $twoFactorAuth = TwoFactorAuth::where('user_id', $user->getAuthIdentifier())->first();

        if (!$twoFactorAuth || !$twoFactorAuth->secret) {
            return false;
        }

        $secret = $twoFactorAuth->decrypted_secret;

        if (!$secret) {
            return false;
        }

        // Clean the code (remove spaces, dashes, etc.)
        $cleanCode = preg_replace('/[^0-9]/', '', $code);

        // Get configuration
        $window = Config::get('two-factor.methods.totp.window', 1);

        return $this->google2fa->verifyKey($secret, $cleanCode, $window);
    }

    /**
     * Generate a QR code URL for TOTP setup.
     *
     * @param Authenticatable $user
     * @param string $secret
     * @return string
     */
    public function generateQrCodeUrl(Authenticatable $user, string $secret): string
    {
        $issuer = Config::get('two-factor.methods.totp.issuer', Config::get('app.name', 'Laravel'));
        $email = $this->getUserEmail($user);
        $algorithm = strtoupper(Config::get('two-factor.methods.totp.algorithm', 'sha1'));
        $digits = Config::get('two-factor.methods.totp.digits', 6);
        $period = Config::get('two-factor.methods.totp.period', 30);

        $parameters = http_build_query([
            'secret' => $secret,
            'issuer' => $issuer,
            'algorithm' => $algorithm,
            'digits' => $digits,
            'period' => $period,
        ]);

        return "otpauth://totp/{$issuer}:{$email}?{$parameters}";
    }

    /**
     * Generate a QR code SVG from a URL.
     *
     * @param string $url
     * @return string
     */
    public function generateQrCodeSvg(string $url): string
    {
        return $this->qrCodeWriter->writeString($url);
    }

    /**
     * Generate a QR code data URI (base64 encoded SVG).
     *
     * @param string $url
     * @return string
     */
    public function generateQrCodeDataUri(string $url): string
    {
        $svg = $this->generateQrCodeSvg($url);
        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }

    /**
     * Format secret for manual entry (with spaces every 4 characters).
     *
     * @param string $secret
     * @return string
     */
    public function formatSecretForManualEntry(string $secret): string
    {
        return implode(' ', str_split($secret, 4));
    }

    /**
     * Get the current TOTP code for a secret (for testing purposes).
     *
     * @param string $secret
     * @return string
     */
    public function getCurrentCode(string $secret): string
    {
        return $this->google2fa->getCurrentOtp($secret);
    }

    /**
     * Get multiple current codes around the current time window (for debugging).
     *
     * @param string $secret
     * @param int $windows Number of time windows to include (before and after current)
     * @return array
     */
    public function getCodesAroundCurrentTime(string $secret, int $windows = 2): array
    {
        $timestamp = $this->google2fa->getTimestamp();
        $period = Config::get('two-factor.methods.totp.period', 30);
        $codes = [];

        for ($i = -$windows; $i <= $windows; $i++) {
            $timeWindow = $timestamp + ($i * $period);
            $codes[$i] = [
                'window' => $i,
                'code' => $this->google2fa->oathTotp($secret, $timeWindow),
                'time' => date('Y-m-d H:i:s', $timeWindow),
                'is_current' => $i === 0,
            ];
        }

        return $codes;
    }

    /**
     * Validate a secret key format.
     *
     * @param string $secret
     * @return bool
     */
    public function isValidSecret(string $secret): bool
    {
        // Check if secret is valid base32
        return $this->google2fa->verifyKey($secret, '000000', 0) !== null;
    }

    /**
     * Get backup codes that would be valid at a specific time.
     *
     * @param string $secret
     * @param int|null $timestamp
     * @return array
     */
    public function getBackupCodesForTime(string $secret, ?int $timestamp = null): array
    {
        $timestamp = $timestamp ?? time();
        $period = Config::get('two-factor.methods.totp.period', 30);
        $window = Config::get('two-factor.methods.totp.window', 1);

        $codes = [];

        // Generate codes for the time window
        for ($i = -$window; $i <= $window; $i++) {
            $timeSlot = $timestamp + ($i * $period);
            $codes[] = $this->google2fa->oathTotp($secret, $timeSlot);
        }

        return array_unique($codes);
    }

    /**
     * Initialize the QR code writer with proper settings.
     *
     * @return void
     */
    protected function initializeQrCodeWriter(): void
    {
        $size = Config::get('two-factor.methods.totp.qr_code.size', 200);
        $margin = Config::get('two-factor.methods.totp.qr_code.margin', 4);
        $errorCorrection = Config::get('two-factor.methods.totp.qr_code.error_correction', 'M');

        $renderer = new ImageRenderer(
            new RendererStyle($size, $margin),
            new SvgImageBackEnd()
        );

        $this->qrCodeWriter = new Writer($renderer);
    }

    /**
     * Get user's email address.
     *
     * @param Authenticatable $user
     * @return string
     */
    protected function getUserEmail(Authenticatable $user): string
    {
        // Try different common email field names
        $emailFields = ['email', 'email_address', 'user_email'];

        foreach ($emailFields as $field) {
            if (isset($user->{$field}) && !empty($user->{$field})) {
                return $user->{$field};
            }
        }

        // Fallback to user identifier or generic email
        $identifier = $user->getAuthIdentifier();
        return is_string($identifier) && filter_var($identifier, FILTER_VALIDATE_EMAIL)
            ? $identifier
            : "user-{$identifier}@" . parse_url(config('app.url'), PHP_URL_HOST);
    }

    /**
     * Get TOTP configuration for frontend use.
     *
     * @return array
     */
    public function getConfiguration(): array
    {
        return [
            'issuer' => Config::get('two-factor.methods.totp.issuer', Config::get('app.name', 'Laravel')),
            'algorithm' => Config::get('two-factor.methods.totp.algorithm', 'sha1'),
            'digits' => Config::get('two-factor.methods.totp.digits', 6),
            'period' => Config::get('two-factor.methods.totp.period', 30),
            'window' => Config::get('two-factor.methods.totp.window', 1),
            'qr_code' => [
                'size' => Config::get('two-factor.methods.totp.qr_code.size', 200),
                'margin' => Config::get('two-factor.methods.totp.qr_code.margin', 4),
            ],
        ];
    }

    /**
     * Test TOTP setup with a known secret and code (for development/testing).
     *
     * @param string $secret
     * @param string $code
     * @return array Test results
     */
    public function testTOTP(string $secret, string $code): array
    {
        $isValid = $this->google2fa->verifyKey($secret, $code);
        $currentCode = $this->getCurrentCode($secret);
        $timestamp = time();

        return [
            'is_valid' => $isValid,
            'provided_code' => $code,
            'current_code' => $currentCode,
            'timestamp' => $timestamp,
            'time_remaining' => 30 - ($timestamp % 30),
            'codes_around_time' => $this->getCodesAroundCurrentTime($secret, 1),
        ];
    }
}
