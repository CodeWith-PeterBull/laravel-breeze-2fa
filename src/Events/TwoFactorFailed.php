<?php

declare(strict_types=1);

namespace MetaSoftDevs\LaravelBreeze2FA\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Two-Factor Authentication Failed Event
 *
 * This event is fired when a 2FA verification attempt fails.
 *
 * @package MetaSoftDevs\LaravelBreeze2FA\Events
 * @author MetaSoft Developers <info@metasoftdevs.com>
 * @version 1.0.0
 */
class TwoFactorFailed
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * The user who failed authentication.
     */
    public Authenticatable $user;

    /**
     * The code that was attempted.
     */
    public string $attemptedCode;

    /**
     * The reason for failure.
     */
    public string $reason;

    /**
     * Additional metadata about the failure.
     */
    public array $metadata;

    /**
     * Create a new event instance.
     *
     * @param Authenticatable $user
     * @param string $attemptedCode
     * @param string $reason
     * @param array $metadata
     */
    public function __construct(Authenticatable $user, string $attemptedCode, string $reason = 'invalid_code', array $metadata = [])
    {
        $this->user = $user;
        $this->attemptedCode = $attemptedCode;
        $this->reason = $reason;
        $this->metadata = array_merge([
            'failed_at' => now(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ], $metadata);
    }
}
