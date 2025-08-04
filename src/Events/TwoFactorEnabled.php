<?php

declare(strict_types=1);

namespace MetaSoftDevs\LaravelBreeze2FA\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Two-Factor Authentication Enabled Event
 *
 * This event is fired when a user successfully enables two-factor authentication.
 *
 * @package MetaSoftDevs\LaravelBreeze2FA\Events
 * @author Meta Software Developers <info@metasoftdevs.com>
 * @version 1.0.0
 */
class TwoFactorEnabled
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * The user who enabled 2FA.
     */
    public Authenticatable $user;

    /**
     * The 2FA method that was enabled.
     */
    public string $method;

    /**
     * Additional metadata about the enablement.
     */
    public array $metadata;

    /**
     * Create a new event instance.
     *
     * @param Authenticatable $user
     * @param string $method
     * @param array $metadata
     */
    public function __construct(Authenticatable $user, string $method, array $metadata = [])
    {
        $this->user = $user;
        $this->method = $method;
        $this->metadata = array_merge([
            'enabled_at' => now(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ], $metadata);
    }
}
