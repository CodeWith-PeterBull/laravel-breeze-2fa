<?php

declare(strict_types=1);

namespace MetaSoftDevs\LaravelBreeze2FA\Exceptions;

/**
 * TOTP Exception
 *
 * Thrown when there's an error with TOTP operations.
 *
 * @package MetaSoftDevs\LaravelBreeze2FA\Exceptions
 * @author MetaSoft Developers <developers@metasoft.dev>
 * @version 1.0.0
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