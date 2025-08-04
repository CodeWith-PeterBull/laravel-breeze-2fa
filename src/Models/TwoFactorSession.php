<?php

declare(strict_types=1);

namespace MetaSoftDevs\LaravelBreeze2FA\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Config;
use Carbon\Carbon;

/**
 * Two-Factor Session Model
 *
 * This model stores remembered device sessions for the "remember this device"
 * functionality. When users choose to remember their device, a secure token
 * is generated and stored to skip 2FA on future logins.
 *
 * @package MetaSoftDevs\LaravelBreeze2FA\Models
 * @author Meta Software Developers <info@metasoftdevs.com>
 * @version 1.0.0
 *
 * @property int $id
 * @property int $user_id
 * @property string $token
 * @property string|null $device_name
 * @property string $ip_address
 * @property string $user_agent
 * @property string|null $device_fingerprint
 * @property Carbon $expires_at
 * @property Carbon $last_used_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * @property-read \Illuminate\Contracts\Auth\Authenticatable $user
 * @property-read bool $is_expired
 * @property-read bool $is_active
 * @property-read int $days_until_expiry
 * @property-read string $formatted_device_name
 */
class TwoFactorSession extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'two_factor_sessions';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'token',
        'device_name',
        'ip_address',
        'user_agent',
        'device_fingerprint',
        'expires_at',
        'last_used_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'token',
        'device_fingerprint',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'expires_at' => 'datetime',
        'last_used_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Boot the model.
     *
     * @return void
     */
    protected static function boot(): void
    {
        parent::boot();

        // Automatically set the table name from config if available
        if (Config::has('two-factor.database.tables.two_factor_sessions')) {
            static::setTableName(Config::get('two-factor.database.tables.two_factor_sessions'));
        }
    }

    /**
     * Get the user that owns the session.
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(
            Config::get('auth.providers.users.model', \App\Models\User::class),
            'user_id'
        );
    }

    /**
     * Check if the session is expired.
     *
     * @return bool
     */
    public function getIsExpiredAttribute(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * Check if the session is active (not expired).
     *
     * @return bool
     */
    public function getIsActiveAttribute(): bool
    {
        return !$this->is_expired;
    }

    /**
     * Get the number of days until expiry.
     *
     * @return int
     */
    public function getDaysUntilExpiryAttribute(): int
    {
        if ($this->is_expired) {
            return 0;
        }

        return (int) $this->expires_at->diffInDays(now());
    }

    /**
     * Get the formatted device name with fallback.
     *
     * @return string
     */
    public function getFormattedDeviceNameAttribute(): string
    {
        if ($this->device_name) {
            return $this->device_name;
        }

        // Generate a name from user agent if device_name is not available
        return $this->generateDeviceNameFromUserAgent();
    }

    /**
     * Get the masked IP address for display.
     *
     * @return string
     */
    public function getMaskedIpAddressAttribute(): string
    {
        return $this->maskIpAddress($this->ip_address);
    }

    /**
     * Get the time since last used in human readable format.
     *
     * @return string
     */
    public function getLastUsedHumanAttribute(): string
    {
        return $this->last_used_at->diffForHumans();
    }

    /**
     * Get the expiry time in human readable format.
     *
     * @return string
     */
    public function getExpiresHumanAttribute(): string
    {
        if ($this->is_expired) {
            return 'Expired ' . $this->expires_at->diffForHumans();
        }

        return 'Expires ' . $this->expires_at->diffForHumans();
    }

    /**
     * Scope query to active sessions (not expired).
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('expires_at', '>', now());
    }

    /**
     * Scope query to expired sessions.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<=', now());
    }

    /**
     * Scope query to sessions for a specific user.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $userId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope query to recently used sessions.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $hours
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeRecentlyUsed($query, int $hours = 24)
    {
        return $query->where('last_used_at', '>=', now()->subHours($hours));
    }

    /**
     * Scope query to sessions expiring soon.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $days
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeExpiringSoon($query, int $days = 7)
    {
        return $query->whereBetween('expires_at', [
            now(),
            now()->addDays($days)
        ]);
    }

    /**
     * Scope query by IP address.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $ipAddress
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByIpAddress($query, string $ipAddress)
    {
        return $query->where('ip_address', $ipAddress);
    }

    /**
     * Mark the session as used.
     *
     * @return bool
     */
    public function markAsUsed(): bool
    {
        return $this->update(['last_used_at' => now()]);
    }

    /**
     * Extend the session expiry.
     *
     * @param int $minutes
     * @return bool
     */
    public function extend(int $minutes): bool
    {
        return $this->update([
            'expires_at' => $this->expires_at->addMinutes($minutes),
        ]);
    }

    /**
     * Check if the session matches the current request.
     *
     * @return bool
     */
    public function matchesCurrentRequest(): bool
    {
        return $this->ip_address === request()->ip() &&
            $this->user_agent === request()->userAgent();
    }

    /**
     * Get session security score (0-100).
     *
     * @return int
     */
    public function getSecurityScore(): int
    {
        $score = 100;

        // Deduct points for old sessions
        $ageInDays = $this->created_at->diffInDays(now());
        if ($ageInDays > 30) {
            $score -= 20;
        } elseif ($ageInDays > 14) {
            $score -= 10;
        }

        // Deduct points for inactivity
        $inactiveDays = $this->last_used_at->diffInDays(now());
        if ($inactiveDays > 7) {
            $score -= 15;
        } elseif ($inactiveDays > 3) {
            $score -= 5;
        }

        // Deduct points if IP doesn't match current
        if ($this->ip_address !== request()->ip()) {
            $score -= 25;
        }

        // Deduct points if user agent doesn't match current
        if ($this->user_agent !== request()->userAgent()) {
            $score -= 25;
        }

        return max(0, $score);
    }

    /**
     * Generate device name from user agent.
     *
     * @return string
     */
    protected function generateDeviceNameFromUserAgent(): string
    {
        $userAgent = $this->user_agent;

        // Simple browser detection
        if (str_contains($userAgent, 'Chrome')) {
            $browser = 'Chrome';
        } elseif (str_contains($userAgent, 'Firefox')) {
            $browser = 'Firefox';
        } elseif (str_contains($userAgent, 'Safari')) {
            $browser = 'Safari';
        } elseif (str_contains($userAgent, 'Edge')) {
            $browser = 'Edge';
        } else {
            $browser = 'Unknown Browser';
        }

        // Simple OS detection
        if (str_contains($userAgent, 'Windows')) {
            $os = 'Windows';
        } elseif (str_contains($userAgent, 'Mac')) {
            $os = 'macOS';
        } elseif (str_contains($userAgent, 'Linux')) {
            $os = 'Linux';
        } elseif (str_contains($userAgent, 'Android')) {
            $os = 'Android';
        } elseif (str_contains($userAgent, 'iOS')) {
            $os = 'iOS';
        } else {
            $os = 'Unknown OS';
        }

        return "{$browser} on {$os}";
    }

    /**
     * Mask IP address for privacy.
     *
     * @param string $ipAddress
     * @return string
     */
    protected function maskIpAddress(string $ipAddress): string
    {
        if (filter_var($ipAddress, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            // IPv4: Show first 3 octets, mask last one
            $parts = explode('.', $ipAddress);
            return $parts[0] . '.' . $parts[1] . '.' . $parts[2] . '.xxx';
        } elseif (filter_var($ipAddress, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            // IPv6: Show first 4 groups, mask the rest
            $parts = explode(':', $ipAddress);
            return implode(':', array_slice($parts, 0, 4)) . ':xxxx:xxxx:xxxx:xxxx';
        }

        return 'xxx.xxx.xxx.xxx';
    }

    /**
     * Get the connection name for the model.
     *
     * @return string|null
     */
    public function getConnectionName()
    {
        return Config::get('two-factor.database.connection') ?: parent::getConnectionName();
    }

    /**
     * Set the table name dynamically.
     *
     * @param string $tableName
     * @return void
     */
    public static function setTableName(string $tableName): void
    {
        (new static)->setTable($tableName);
    }

    /**
     * Clean up expired sessions.
     *
     * @return int Number of expired sessions deleted
     */
    public static function cleanupExpired(): int
    {
        return static::expired()->delete();
    }

    /**
     * Get statistics for all sessions.
     *
     * @return array
     */
    public static function getGlobalStatistics(): array
    {
        $total = static::count();
        $active = static::active()->count();
        $expired = static::expired()->count();
        $expiringSoon = static::expiringSoon()->count();
        $recentlyUsed = static::recentlyUsed()->count();

        return [
            'total' => $total,
            'active' => $active,
            'expired' => $expired,
            'expiring_soon' => $expiringSoon,
            'recently_used' => $recentlyUsed,
            'usage_rate' => $total > 0 ? round(($recentlyUsed / $total) * 100, 2) : 0,
        ];
    }

    /**
     * Get statistics for a specific user.
     *
     * @param int $userId
     * @return array
     */
    public static function getUserStatistics(int $userId): array
    {
        $total = static::forUser($userId)->count();
        $active = static::forUser($userId)->active()->count();
        $recentlyUsed = static::forUser($userId)->recentlyUsed()->count();

        return [
            'total' => $total,
            'active' => $active,
            'recently_used' => $recentlyUsed,
            'has_current_session' => static::forUser($userId)
                ->byIpAddress(request()->ip())
                ->active()
                ->exists(),
        ];
    }

    /**
     * Convert the model to its array representation for safe API responses.
     *
     * @return array
     */
    public function toSafeArray(): array
    {
        return [
            'id' => $this->id,
            'device_name' => $this->formatted_device_name,
            'masked_ip_address' => $this->masked_ip_address,
            'last_used_at' => $this->last_used_at->toISOString(),
            'last_used_human' => $this->last_used_human,
            'expires_at' => $this->expires_at->toISOString(),
            'expires_human' => $this->expires_human,
            'is_active' => $this->is_active,
            'is_expired' => $this->is_expired,
            'days_until_expiry' => $this->days_until_expiry,
            'security_score' => $this->getSecurityScore(),
            'is_current_device' => $this->matchesCurrentRequest(),
            'created_at' => $this->created_at->toISOString(),
        ];
    }
}
