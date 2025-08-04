<?php

declare(strict_types=1);

namespace MetaSoftDevs\LaravelBreeze2FA\Exceptions;

/**
 * Configuration Exception
 *
 * Thrown when there's a configuration error in the two-factor authentication setup.
 *
 * @package MetaSoftDevs\LaravelBreeze2FA\Exceptions
 * @author Meta Software Developers <info@metasoftdevs.com>
 * @version 1.0.0
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
