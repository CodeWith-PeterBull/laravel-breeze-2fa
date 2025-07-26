<?php

declare(strict_types=1);

namespace MetaSoftDevs\LaravelBreeze2FA\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Recovery Codes Regenerated Event
 *
 * This event is fired when recovery codes are regenerated (old ones replaced).
 *
 * @package MetaSoftDevs\LaravelBreeze2FA\Events
 * @author MetaSoft Developers <info@metasoftdevs.com>
 * @version 1.0.0
 */
class RecoveryCodesRegenerated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * The user for whom codes were regenerated.
     */
    public Authenticatable $user;

    /**
     * The number of new codes generated.
     */
    public int $newCodesCount;

    /**
     * Statistics about the old codes.
     */
    public array $oldCodesStats;

    /**
     * Additional metadata about the regeneration.
     */
    public array $metadata;

    /**
     * Create a new event instance.
     *
     * @param Authenticatable $user
     * @param int $newCodesCount
     * @param array $oldCodesStats
     * @param array $metadata
     */
    public function __construct(Authenticatable $user, int $newCodesCount, array $oldCodesStats = [], array $metadata = [])
    {
        $this->user = $user;
        $this->newCodesCount = $newCodesCount;
        $this->oldCodesStats = $oldCodesStats;
        $this->metadata = array_merge([
            'regenerated_at' => now(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ], $metadata);
    }
}
