<?php

declare(strict_types=1);

namespace MetaSoftDevs\LaravelBreeze2FA\Exceptions;

/**
 * Not Enabled Exception
 *
 * Thrown when trying to perform operations on two-factor authentication that is not enabled.
 *
 * @package MetaSoftDevs\LaravelBreeze2FA\Exceptions
 * @author Meta Software Developers <info@metasoftdevs.com>
 * @version 1.0.0
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
