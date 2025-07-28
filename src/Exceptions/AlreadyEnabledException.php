<?php

declare(strict_types=1);

namespace MetaSoftDevs\LaravelBreeze2FA\Exceptions;

/**
 * Already Enabled Exception
 *
 * Thrown when trying to enable two-factor authentication that is already enabled.
 *
 * @package MetaSoftDevs\LaravelBreeze2FA\Exceptions
 * @author MetaSoft Developers <developers@metasoft.dev>
 * @version 1.0.0
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