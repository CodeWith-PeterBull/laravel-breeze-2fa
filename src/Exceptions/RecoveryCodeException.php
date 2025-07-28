<?php

declare(strict_types=1);

namespace MetaSoftDevs\LaravelBreeze2FA\Exceptions;

/**
 * Recovery Code Exception
 *
 * Thrown when there's an error with recovery codes.
 *
 * @package MetaSoftDevs\LaravelBreeze2FA\Exceptions
 * @author MetaSoft Developers <developers@metasoft.dev>
 * @version 1.0.0
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