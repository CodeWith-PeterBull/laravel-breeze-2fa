<?php

declare(strict_types=1);

namespace MetaSoftDevs\LaravelBreeze2FA\Exceptions;

/**
 * Device Remember Exception
 *
 * Thrown when there's an error with device remembering functionality.
 *
 * @package MetaSoftDevs\LaravelBreeze2FA\Exceptions
 * @author Meta Software Developers <info@metasoftdevs.com>
 * @version 1.0.0
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
