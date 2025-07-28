<?php

declare(strict_types=1);

namespace MetaSoftDevs\LaravelBreeze2FA\Exceptions;

/**
 * Setup Required Exception
 *
 * Thrown when two-factor authentication setup is required but not completed.
 *
 * @package MetaSoftDevs\LaravelBreeze2FA\Exceptions
 * @author MetaSoft Developers <developers@metasoft.dev>
 * @version 1.0.0
 */
class SetupRequiredException extends TwoFactorException
{
    protected string $errorCode = 'setup_required';

    public function __construct(
        string $message = 'Two-factor authentication setup is required.',
        int $code = 423,
        ?\Throwable $previous = null,
        array $context = []
    ) {
        parent::__construct($message, $code, $previous, $this->errorCode, $context);
    }
}