<?php

declare(strict_types=1);

namespace MetaSoftDevs\LaravelBreeze2FA\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Recovery Codes Generated Event
 *
 * This event is fired when new recovery codes are generated for a user.
 *
 * @package MetaSoftDevs\LaravelBreeze2FA\Events
 * @author Meta Software Developers <info@metasoftdevs.com>
 * @version 1.0.0
 */
class RecoveryCodesGenerated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * The user for whom codes were generated.
     */
    public Authenticatable $user;

    /**
     * The number of codes generated.
     */
    public int $codesCount;

    /**
     * Whether this was a regeneration (replacing existing codes).
     */
    public bool $isRegeneration;

    /**
     * Additional metadata about the generation.
     */
    public array $metadata;

    /**
     * Create a new event instance.
     *
     * @param Authenticatable $user
     * @param int $codesCount
     * @param bool $isRegeneration
     * @param array $metadata
     */
    public function __construct(Authenticatable $user, int $codesCount, bool $isRegeneration = false, array $metadata = [])
    {
        $this->user = $user;
        $this->codesCount = $codesCount;
        $this->isRegeneration = $isRegeneration;
        $this->metadata = array_merge([
            'generated_at' => now(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ], $metadata);
    }
}
