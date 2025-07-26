<?php

declare(strict_types=1);

namespace MetaSoftDevs\LaravelBreeze2FA\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Two-Factor Setup Completed Event
 *
 * This event is fired when a user completes 2FA setup confirmation.
 *
 * @package MetaSoftDevs\LaravelBreeze2FA\Events
 * @author MetaSoft Developers <info@metasoftdevs.com>
 * @version 1.0.0
 */
class TwoFactorSetupCompleted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * The user who completed setup.
     */
    public Authenticatable $user;

    /**
     * The method that was set up.
     */
    public string $method;

    /**
     * Setup duration in seconds.
     */
    public int $setupDuration;

    /**
     * Setup metadata.
     */
    public array $metadata;

    /**
     * Create a new event instance.
     *
     * @param Authenticatable $user
     * @param string $method
     * @param int $setupDuration
     * @param array $metadata
     */
    public function __construct(Authenticatable $user, string $method, int $setupDuration = 0, array $metadata = [])
    {
        $this->user = $user;
        $this->method = $method;
        $this->setupDuration = $setupDuration;
        $this->metadata = array_merge([
            'setup_completed_at' => now(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ], $metadata);
    }
}
