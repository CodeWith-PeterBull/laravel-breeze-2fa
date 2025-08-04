<?php

declare(strict_types=1);

namespace MetaSoftDevs\LaravelBreeze2FA\Exceptions;

/**
 * Method Not Enabled Exception
 *
 * Thrown when trying to use a two-factor authentication method that is not enabled.
 *
 * @package MetaSoftDevs\LaravelBreeze2FA\Exceptions
 * @author Meta Software Developers <info@metasoftdevs.com>
 * @version 1.0.0
 */
class MethodNotEnabledException extends TwoFactorException
{
    protected string $errorCode = 'method_not_enabled';

    /**
     * The method that was attempted.
     */
    protected string $method;

    public function __construct(
        string $method,
        string $message = null,
        int $code = 400,
        ?\Throwable $previous = null,
        array $context = []
    ) {
        $this->method = $method;

        $message = $message ?: "Two-factor authentication method '{$method}' is not enabled.";

        parent::__construct($message, $code, $previous, $this->errorCode, $context);
    }

    /**
     * Get the method that was attempted.
     *
     * @return string
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * Convert exception to array for API responses.
     *
     * @return array
     */
    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'method' => $this->method,
        ]);
    }
}
