<?php

declare(strict_types=1);

namespace MetaSoftDevs\LaravelBreeze2FA\Exceptions;

/**
 * User Not Found Exception
 *
 * Thrown when a user is not found during two-factor authentication operations.
 *
 * @package MetaSoftDevs\LaravelBreeze2FA\Exceptions
 * @author Meta Software Developers <info@metasoftdevs.com>
 * @version 1.0.0
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
