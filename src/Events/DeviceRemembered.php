<?php

declare(strict_types=1);

namespace MetaSoftDevs\LaravelBreeze2FA\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Carbon\Carbon;

/**
 * Device Remembered Event
 *
 * This event is fired when a device is marked as remembered.
 *
 * @package MetaSoftDevs\LaravelBreeze2FA\Events
 * @author MetaSoft Developers <info@metasoftdevs.com>
 * @version 1.0.0
 */
class DeviceRemembered
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * The user whose device was remembered.
     */
    public Authenticatable $user;

    /**
     * The device identifier/token.
     */
    public string $deviceToken;

    /**
     * Device information.
     */
    public array $deviceInfo;

    /**
     * Expiration time for the remembered device.
     */
    public Carbon $expiresAt;

    /**
     * Create a new event instance.
     *
     * @param Authenticatable $user
     * @param string $deviceToken
     * @param array $deviceInfo
     * @param Carbon $expiresAt
     */
    public function __construct(Authenticatable $user, string $deviceToken, array $deviceInfo, Carbon $expiresAt)
    {
        $this->user = $user;
        $this->deviceToken = $deviceToken;
        $this->deviceInfo = $deviceInfo;
        $this->expiresAt = $expiresAt;
    }
}
