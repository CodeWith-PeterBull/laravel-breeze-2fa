<?php

declare(strict_types=1);

namespace MetaSoftDevs\LaravelBreeze2FA\Exceptions;

/**
 * Rate Limit Exceeded Exception
 *
 * Thrown when too many authentication attempts have been made.
 *
 * @package MetaSoftDevs\LaravelBreeze2FA\Exceptions
 * @author MetaSoft Developers <developers@metasoft.dev>
 * @version 1.0.0
 */
class RateLimitExceededException extends TwoFactorException
{
    protected string $errorCode = 'rate_limit_exceeded';

    /**
     * Number of seconds until the rate limit resets.
     */
    protected int $retryAfter;

    public function __construct(
        string $message = 'Too many authentication attempts. Please try again later.',
        int $retryAfter = 900,
        int $code = 429,
        ?\Throwable $previous = null,
        array $context = []
    ) {
        $this->retryAfter = $retryAfter;

        parent::__construct($message, $code, $previous, $this->errorCode, $context);
    }

    /**
     * Get the retry after seconds.
     *
     * @return int
     */
    public function getRetryAfter(): int
    {
        return $this->retryAfter;
    }

    /**
     * Convert exception to array for API responses.
     *
     * @return array
     */
    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'retry_after' => $this->retryAfter,
        ]);
    }
}