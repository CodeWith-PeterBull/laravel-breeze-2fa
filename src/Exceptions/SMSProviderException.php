<?php

declare(strict_types=1);

namespace MetaSoftDevs\LaravelBreeze2FA\Exceptions;

/**
 * SMS Provider Exception
 *
 * Thrown when there's an error with the SMS provider.
 *
 * @package MetaSoftDevs\LaravelBreeze2FA\Exceptions
 * @author Meta Software Developers <info@metasoftdevs.com>
 * @version 1.0.0
 */
class SMSProviderException extends TwoFactorException
{
    protected string $errorCode = 'sms_provider_error';

    /**
     * The SMS provider that failed.
     */
    protected string $provider;

    public function __construct(
        string $message,
        string $provider = 'unknown',
        int $code = 500,
        ?\Throwable $previous = null,
        array $context = []
    ) {
        $this->provider = $provider;

        parent::__construct($message, $code, $previous, $this->errorCode, $context);
    }

    /**
     * Get the SMS provider that failed.
     *
     * @return string
     */
    public function getProvider(): string
    {
        return $this->provider;
    }

    /**
     * Convert exception to array for API responses.
     *
     * @return array
     */
    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'provider' => $this->provider,
        ]);
    }
}
