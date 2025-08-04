<?php

declare(strict_types=1);

namespace MetaSoftDevs\LaravelBreeze2FA\Exceptions;

/**
 * Session Expired Exception
 *
 * Thrown when a two-factor authentication session has expired.
 *
 * @package MetaSoftDevs\LaravelBreeze2FA\Exceptions
 * @author Meta Software Developers <info@metasoftdevs.com>
 * @version 1.0.0
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
