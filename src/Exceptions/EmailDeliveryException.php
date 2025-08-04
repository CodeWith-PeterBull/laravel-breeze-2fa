<?php

declare(strict_types=1);

namespace MetaSoftDevs\LaravelBreeze2FA\Exceptions;

/**
 * Email Delivery Exception
 *
 * Thrown when there's an error delivering the email OTP.
 *
 * @package MetaSoftDevs\LaravelBreeze2FA\Exceptions
 * @author Meta Software Developers <info@metasoftdevs.com>
 * @version 1.0.0
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
