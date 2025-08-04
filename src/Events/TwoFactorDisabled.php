<?php

declare(strict_types=1);

namespace MetaSoftDevs\LaravelBreeze2FA\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Two-Factor Authentication Disabled Event
 *
 * This event is fired when a user disables two-factor authentication.
 *
 * @package MetaSoftDevs\LaravelBreeze2FA\Events
 * @author Meta Software Developers <info@metasoftdevs.com>
 * @version 1.0.0
 */
class TwoFactorDisabled
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * The user who disabled 2FA.
     */
    public Authenticatable $user;

    /**
     * Additional metadata about the disabling.
     */
    public array $metadata;

    /**
     * Create a new event instance.
     *
     * @param Authenticatable $user
     * @param array $metadata
     */
    public function __construct(Authenticatable $user, array $metadata = [])
    {
        $this->user = $user;
        $this->metadata = array_merge([
            'disabled_at' => now(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ], $metadata);
    }
}
