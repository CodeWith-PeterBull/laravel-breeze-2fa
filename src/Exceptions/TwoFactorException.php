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
 * @author MetaSoft Developers <developers@metasoft.dev>
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

/**
 * Invalid Code Exception
 *
 * Thrown when a provided two-factor authentication code is invalid.
 */
class InvalidCodeException extends TwoFactorException
{
    protected string $errorCode = 'invalid_code';

    public function __construct(
        string $message = 'The provided two-factor authentication code is invalid.',
        int $code = 422,
        ?\Throwable $previous = null,
        array $context = []
    ) {
        parent::__construct($message, $code, $previous, $this->errorCode, $context);
    }
}

/**
 * Rate Limit Exceeded Exception
 *
 * Thrown when too many authentication attempts have been made.
 */
class RateLimitExceededException extends TwoFactorException
{
    protected string $errorCode = 'rate_limit_exceeded';

    /**
     * Number of seconds until the rate limit resets.
     */
    protected int $retryAfter;

    public function __construct(
        string $message = 'Too many authentication attempts. Please try again later.',
        int $retryAfter = 900,
        int $code = 429,
        ?\Throwable $previous = null,
        array $context = []
    ) {
        $this->retryAfter = $retryAfter;

        parent::__construct($message, $code, $previous, $this->errorCode, $context);
    }

    /**
     * Get the retry after seconds.
     *
     * @return int
     */
    public function getRetryAfter(): int
    {
        return $this->retryAfter;
    }

    /**
     * Convert exception to array for API responses.
     *
     * @return array
     */
    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'retry_after' => $this->retryAfter,
        ]);
    }
}

/**
 * Setup Required Exception
 *
 * Thrown when two-factor authentication setup is required but not completed.
 */
class SetupRequiredException extends TwoFactorException
{
    protected string $errorCode = 'setup_required';

    public function __construct(
        string $message = 'Two-factor authentication setup is required.',
        int $code = 423,
        ?\Throwable $previous = null,
        array $context = []
    ) {
        parent::__construct($message, $code, $previous, $this->errorCode, $context);
    }
}

/**
 * Method Not Enabled Exception
 *
 * Thrown when trying to use a two-factor authentication method that is not enabled.
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

/**
 * Configuration Exception
 *
 * Thrown when there's a configuration error in the two-factor authentication setup.
 */
class ConfigurationException extends TwoFactorException
{
    protected string $errorCode = 'configuration_error';

    public function __construct(
        string $message = 'Two-factor authentication is not properly configured.',
        int $code = 500,
        ?\Throwable $previous = null,
        array $context = []
    ) {
        parent::__construct($message, $code, $previous, $this->errorCode, $context);
    }
}

/**
 * SMS Provider Exception
 *
 * Thrown when there's an error with the SMS provider.
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

/**
 * Email Delivery Exception
 *
 * Thrown when there's an error delivering the email OTP.
 */
class EmailDeliveryException extends TwoFactorException
{
    protected string $errorCode = 'email_delivery_error';

    public function __construct(
        string $message = 'Failed to deliver email verification code.',
        int $code = 500,
        ?\Throwable $previous = null,
        array $context = []
    ) {
        parent::__construct($message, $code, $previous, $this->errorCode, $context);
    }
}

/**
 * Recovery Code Exception
 *
 * Thrown when there's an error with recovery codes.
 */
class RecoveryCodeException extends TwoFactorException
{
    protected string $errorCode = 'recovery_code_error';

    public function __construct(
        string $message = 'Recovery code error occurred.',
        int $code = 400,
        ?\Throwable $previous = null,
        array $context = []
    ) {
        parent::__construct($message, $code, $previous, $this->errorCode, $context);
    }
}

/**
 * TOTP Exception
 *
 * Thrown when there's an error with TOTP operations.
 */
class TOTPException extends TwoFactorException
{
    protected string $errorCode = 'totp_error';

    public function __construct(
        string $message = 'TOTP error occurred.',
        int $code = 400,
        ?\Throwable $previous = null,
        array $context = []
    ) {
        parent::__construct($message, $code, $previous, $this->errorCode, $context);
    }
}

/**
 * Device Remember Exception
 *
 * Thrown when there's an error with device remembering functionality.
 */
class DeviceRememberException extends TwoFactorException
{
    protected string $errorCode = 'device_remember_error';

    public function __construct(
        string $message = 'Device remember error occurred.',
        int $code = 400,
        ?\Throwable $previous = null,
        array $context = []
    ) {
        parent::__construct($message, $code, $previous, $this->errorCode, $context);
    }
}

/**
 * User Not Found Exception
 *
 * Thrown when a user is not found during two-factor authentication operations.
 */
class UserNotFoundException extends TwoFactorException
{
    protected string $errorCode = 'user_not_found';

    public function __construct(
        string $message = 'User not found.',
        int $code = 404,
        ?\Throwable $previous = null,
        array $context = []
    ) {
        parent::__construct($message, $code, $previous, $this->errorCode, $context);
    }
}

/**
 * Session Expired Exception
 *
 * Thrown when a two-factor authentication session has expired.
 */
class SessionExpiredException extends TwoFactorException
{
    protected string $errorCode = 'session_expired';

    public function __construct(
        string $message = 'Two-factor authentication session has expired.',
        int $code = 419,
        ?\Throwable $previous = null,
        array $context = []
    ) {
        parent::__construct($message, $code, $previous, $this->errorCode, $context);
    }
}

/**
 * Already Enabled Exception
 *
 * Thrown when trying to enable two-factor authentication that is already enabled.
 */
class AlreadyEnabledException extends TwoFactorException
{
    protected string $errorCode = 'already_enabled';

    public function __construct(
        string $message = 'Two-factor authentication is already enabled.',
        int $code = 409,
        ?\Throwable $previous = null,
        array $context = []
    ) {
        parent::__construct($message, $code, $previous, $this->errorCode, $context);
    }
}

/**
 * Not Enabled Exception
 *
 * Thrown when trying to perform operations on two-factor authentication that is not enabled.
 */
class NotEnabledException extends TwoFactorException
{
    protected string $errorCode = 'not_enabled';

    public function __construct(
        string $message = 'Two-factor authentication is not enabled.',
        int $code = 400,
        ?\Throwable $previous = null,
        array $context = []
    ) {
        parent::__construct($message, $code, $previous, $this->errorCode, $context);
    }
}
