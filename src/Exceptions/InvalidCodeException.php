<?php

declare(strict_types=1);

namespace MetaSoftDevs\LaravelBreeze2FA\Exceptions;

/**
 * Invalid Code Exception
 *
 * Thrown when a provided two-factor authentication code is invalid.
 *
 * @package MetaSoftDevs\LaravelBreeze2FA\Exceptions
 * @author MetaSoft Developers <developers@metasoft.dev>
 * @version 1.0.0
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