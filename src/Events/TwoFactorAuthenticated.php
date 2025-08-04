<?php

declare(strict_types=1);

namespace MetaSoftDevs\LaravelBreeze2FA\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Two-Factor Authentication Successful Event
 *
 * This event is fired when a user successfully completes 2FA verification.
 *
 * @package MetaSoftDevs\LaravelBreeze2FA\Events
 * @author Meta Software Developers <info@metasoftdevs.com>
 * @version 1.0.0
 */
class TwoFactorAuthenticated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * The user who was authenticated.
     */
    public Authenticatable $user;

    /**
     * The method used for authentication.
     */
    public string $method;

    /**
     * Whether the device was remembered.
     */
    public bool $deviceRemembered;

    /**
     * Additional metadata about the authentication.
     */
    public array $metadata;

    /**
     * Create a new event instance.
     *
     * @param Authenticatable $user
     * @param string $method
     * @param bool $deviceRemembered
     * @param array $metadata
     */
    public function __construct(Authenticatable $user, string $method, bool $deviceRemembered = false, array $metadata = [])
    {
        $this->user = $user;
        $this->method = $method;
        $this->deviceRemembered = $deviceRemembered;
        $this->metadata = array_merge([
            'authenticated_at' => now(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ], $metadata);
    }
}
