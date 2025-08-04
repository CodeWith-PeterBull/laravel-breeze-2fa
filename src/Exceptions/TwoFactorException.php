<?php

declare(strict_types=1);

namespace MetaSoftDevs\LaravelBreeze2FA\Exceptions;

use Exception;

/**
 * Base Two-Factor Authentication Exception
 *
 * This is the base exception class for all two-factor authentication
 * related exceptions in the package.
 *
 * @package MetaSoftDevs\LaravelBreeze2FA\Exceptions
 * @author Meta Software Developers <info@metasoftdevs.com>
 * @version 1.0.0
 */
class TwoFactorException extends Exception
{
    /**
     * Error code for the exception.
     */
    protected string $errorCode = 'two_factor_error';

    /**
     * Additional context data.
     */
    protected array $context = [];

    /**
     * Create a new TwoFactorException instance.
     *
     * @param string $message
     * @param int $code
     * @param \Throwable|null $previous
     * @param string|null $errorCode
     * @param array $context
     */
    public function __construct(
        string $message = '',
        int $code = 0,
        ?\Throwable $previous = null,
        ?string $errorCode = null,
        array $context = []
    ) {
        parent::__construct($message, $code, $previous);

        if ($errorCode) {
            $this->errorCode = $errorCode;
        }

        $this->context = $context;
    }

    /**
     * Get the error code.
     *
     * @return string
     */
    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    /**
     * Get the context data.
     *
     * @return array
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * Set context data.
     *
     * @param array $context
     * @return $this
     */
    public function setContext(array $context): self
    {
        $this->context = $context;

        return $this;
    }

    /**
     * Add context data.
     *
     * @param string $key
     * @param mixed $value
     * @return $this
     */
    public function addContext(string $key, mixed $value): self
    {
        $this->context[$key] = $value;

        return $this;
    }

    /**
     * Convert exception to array for API responses.
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'error' => $this->errorCode,
            'message' => $this->getMessage(),
            'context' => $this->context,
        ];
    }
}
