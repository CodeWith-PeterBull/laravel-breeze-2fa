<?php

declare(strict_types=1);

namespace MetaSoftDevs\LaravelBreeze2FA\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Device Forgotten Event
 *
 * This event is fired when a remembered device is forgotten/removed.
 *
 * @package MetaSoftDevs\LaravelBreeze2FA\Events
 * @author MetaSoft Developers <info@metasoftdevs.com>
 * @version 1.0.0
 */
class DeviceForgotten
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * The user whose device was forgotten.
     */
    public Authenticatable $user;

    /**
     * The device identifier/token that was forgotten.
     */
    public string $deviceToken;

    /**
     * Reason for forgetting the device.
     */
    public string $reason;

    /**
     * Additional metadata.
     */
    public array $metadata;

    /**
     * Create a new event instance.
     *
     * @param Authenticatable $user
     * @param string $deviceToken
     * @param string $reason
     * @param array $metadata
     */
    public function __construct(Authenticatable $user, string $deviceToken, string $reason = 'manual', array $metadata = [])
    {
        $this->user = $user;
        $this->deviceToken = $deviceToken;
        $this->reason = $reason;
        $this->metadata = array_merge([
            'forgotten_at' => now(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ], $metadata);
    }
}
