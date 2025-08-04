<?php

declare(strict_types=1);

namespace MetaSoftDevs\LaravelBreeze2FA\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Recovery Code Used Event
 *
 * This event is fired when a user successfully uses a recovery code.
 *
 * @package MetaSoftDevs\LaravelBreeze2FA\Events
 * @author Meta Software Developers <info@metasoftdevs.com>
 * @version 1.0.0
 */
class RecoveryCodeUsed
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * The user who used the recovery code.
     */
    public Authenticatable $user;

    /**
     * The recovery code that was used (hashed for security).
     */
    public string $codeHash;

    /**
     * Remaining recovery codes count.
     */
    public int $remainingCodes;

    /**
     * Additional metadata about the usage.
     */
    public array $metadata;

    /**
     * Create a new event instance.
     *
     * @param Authenticatable $user
     * @param string $code
     * @param array $metadata
     */
    public function __construct(Authenticatable $user, string $code, array $metadata = [])
    {
        $this->user = $user;
        $this->codeHash = hash('sha256', $code); // Hash for security
        $this->remainingCodes = $this->calculateRemainingCodes($user);
        $this->metadata = array_merge([
            'used_at' => now(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ], $metadata);
    }

    /**
     * Calculate remaining recovery codes for the user.
     *
     * @param Authenticatable $user
     * @return int
     */
    protected function calculateRemainingCodes(Authenticatable $user): int
    {
        return \MetaSoftDevs\LaravelBreeze2FA\Models\TwoFactorRecoveryCode::forUser($user->getAuthIdentifier())
            ->unused()
            ->count();
    }
}
