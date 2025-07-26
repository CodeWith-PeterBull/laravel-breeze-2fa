<?php

declare(strict_types=1);

namespace MetaSoftDevs\LaravelBreeze2FA\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Config;
use Carbon\Carbon;

/**
 * Two-Factor Authentication Attempt Model
 *
 * This model tracks all two-factor authentication attempts for security
 * monitoring, rate limiting, and audit purposes. It helps prevent brute
 * force attacks and provides detailed analytics.
 *
 * @package MetaSoftDevs\LaravelBreeze2FA\Models
 * @author MetaSoft Developers <info@metasoftdevs.com>
 * @version 1.0.0
 *
 * @property int $id
 * @property int|null $user_id
 * @property string|null $session_id
 * @property string $ip_address
 * @property string $user_agent
 * @property string $method
 * @property string $type
 * @property bool $successful
 * @property string|null $failure_reason
 * @property string|null $code_hash
 * @property int|null $code_length
 * @property Carbon $attempted_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * @property-read \Illuminate\Contracts\Auth\Authenticatable|null $user
 * @property-read string $masked_ip_address
 * @property-read string $attempted_at_human
 */
class TwoFactorAttempt extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'two_factor_attempts';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'session_id',
        'ip_address',
        'user_agent',
        'method',
        'type',
        'successful',
        'failure_reason',
        'code_hash',
        'code_length',
        'attempted_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'code_hash',
        'session_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'successful' => 'boolean',
        'code_length' => 'integer',
        'attempted_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Available 2FA methods.
     *
     * @var array<string, string>
     */
    public const METHODS = [
        'totp' => 'TOTP',
        'email' => 'Email OTP',
        'sms' => 'SMS OTP',
        'recovery' => 'Recovery Code',
    ];

    /**
     * Available attempt types.
     *
     * @var array<string, string>
     */
    public const TYPES = [
        'verification' => 'Verification',
        'setup' => 'Setup',
        'challenge' => 'Challenge',
    ];

    /**
     * Available failure reasons.
     *
     * @var array<string, string>
     */
    public const FAILURE_REASONS = [
        'invalid_code' => 'Invalid Code',
        'expired_code' => 'Expired Code',
        'rate_limited' => 'Rate Limited',
        'no_code' => 'No Code Provided',
        'invalid_format' => 'Invalid Format',
        'already_used' => 'Already Used',
        'method_disabled' => 'Method Disabled',
        'user_not_found' => 'User Not Found',
        'session_expired' => 'Session Expired',
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
        if (Config::has('two-factor.database.tables.two_factor_attempts')) {
            static::setTableName(Config::get('two-factor.database.tables.two_factor_attempts'));
        }

        // Automatically set attempted_at if not provided
        static::creating(function ($model) {
            if (!$model->attempted_at) {
                $model->attempted_at = now();
            }
        });
    }

    /**
     * Get the user that made the attempt.
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
     * Get the masked IP address for display.
     *
     * @return string
     */
    public function getMaskedIpAddressAttribute(): string
    {
        return $this->maskIpAddress($this->ip_address);
    }

    /**
     * Get the attempted at time in human readable format.
     *
     * @return string
     */
    public function getAttemptedAtHumanAttribute(): string
    {
        return $this->attempted_at->diffForHumans();
    }

    /**
     * Get the method display name.
     *
     * @return string
     */
    public function getMethodDisplayNameAttribute(): string
    {
        return self::METHODS[$this->method] ?? ucfirst($this->method);
    }

    /**
     * Get the type display name.
     *
     * @return string
     */
    public function getTypeDisplayNameAttribute(): string
    {
        return self::TYPES[$this->type] ?? ucfirst($this->type);
    }

    /**
     * Get the failure reason display name.
     *
     * @return string|null
     */
    public function getFailureReasonDisplayNameAttribute(): ?string
    {
        if (!$this->failure_reason) {
            return null;
        }

        return self::FAILURE_REASONS[$this->failure_reason] ?? ucfirst(str_replace('_', ' ', $this->failure_reason));
    }

    /**
     * Create a new attempt record.
     *
     * @param array $data
     * @return static
     */
    public static function createAttempt(array $data): static
    {
        // Hash the code if provided
        if (isset($data['code']) && !empty($data['code'])) {
            $data['code_hash'] = Hash::make($data['code']);
            $data['code_length'] = strlen($data['code']);
            unset($data['code']); // Remove plain code from data
        }

        // Set default values
        $data = array_merge([
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent() ?: 'Unknown',
            'session_id' => session()->getId(),
            'attempted_at' => now(),
        ], $data);

        return static::create($data);
    }

    /**
     * Record a successful attempt.
     *
     * @param int|null $userId
     * @param string $method
     * @param string $type
     * @param string|null $code
     * @return static
     */
    public static function recordSuccess(?int $userId, string $method, string $type = 'verification', ?string $code = null): static
    {
        return static::createAttempt([
            'user_id' => $userId,
            'method' => $method,
            'type' => $type,
            'successful' => true,
            'code' => $code,
        ]);
    }

    /**
     * Record a failed attempt.
     *
     * @param int|null $userId
     * @param string $method
     * @param string $failureReason
     * @param string $type
     * @param string|null $code
     * @return static
     */
    public static function recordFailure(?int $userId, string $method, string $failureReason, string $type = 'verification', ?string $code = null): static
    {
        return static::createAttempt([
            'user_id' => $userId,
            'method' => $method,
            'type' => $type,
            'successful' => false,
            'failure_reason' => $failureReason,
            'code' => $code,
        ]);
    }

    /**
     * Scope query to successful attempts.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSuccessful($query)
    {
        return $query->where('successful', true);
    }

    /**
     * Scope query to failed attempts.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFailed($query)
    {
        return $query->where('successful', false);
    }

    /**
     * Scope query to attempts for a specific user.
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
     * Scope query to attempts by method.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $method
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByMethod($query, string $method)
    {
        return $query->where('method', $method);
    }

    /**
     * Scope query to attempts by type.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $type
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope query to attempts from a specific IP.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $ipAddress
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFromIp($query, string $ipAddress)
    {
        return $query->where('ip_address', $ipAddress);
    }

    /**
     * Scope query to recent attempts.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $minutes
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeRecent($query, int $minutes = 15)
    {
        return $query->where('attempted_at', '>=', now()->subMinutes($minutes));
    }

    /**
     * Scope query to attempts within a date range.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param Carbon $from
     * @param Carbon $to
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeDateRange($query, Carbon $from, Carbon $to)
    {
        return $query->whereBetween('attempted_at', [$from, $to]);
    }

    /**
     * Get attempts statistics for a user.
     *
     * @param int $userId
     * @param int $hours
     * @return array
     */
    public static function getUserStats(int $userId, int $hours = 24): array
    {
        $since = now()->subHours($hours);

        $total = static::forUser($userId)->where('attempted_at', '>=', $since)->count();
        $successful = static::forUser($userId)->successful()->where('attempted_at', '>=', $since)->count();
        $failed = static::forUser($userId)->failed()->where('attempted_at', '>=', $since)->count();

        return [
            'total' => $total,
            'successful' => $successful,
            'failed' => $failed,
            'success_rate' => $total > 0 ? round(($successful / $total) * 100, 2) : 0,
            'period_hours' => $hours,
        ];
    }

    /**
     * Get attempts statistics by IP address.
     *
     * @param string $ipAddress
     * @param int $hours
     * @return array
     */
    public static function getIpStats(string $ipAddress, int $hours = 24): array
    {
        $since = now()->subHours($hours);

        $total = static::fromIp($ipAddress)->where('attempted_at', '>=', $since)->count();
        $successful = static::fromIp($ipAddress)->successful()->where('attempted_at', '>=', $since)->count();
        $failed = static::fromIp($ipAddress)->failed()->where('attempted_at', '>=', $since)->count();

        return [
            'total' => $total,
            'successful' => $successful,
            'failed' => $failed,
            'success_rate' => $total > 0 ? round(($successful / $total) * 100, 2) : 0,
            'period_hours' => $hours,
        ];
    }

    /**
     * Get global statistics.
     *
     * @param int $days
     * @return array
     */
    public static function getGlobalStats(int $days = 7): array
    {
        $since = now()->subDays($days);

        $total = static::where('attempted_at', '>=', $since)->count();
        $successful = static::successful()->where('attempted_at', '>=', $since)->count();
        $failed = static::failed()->where('attempted_at', '>=', $since)->count();
        $uniqueUsers = static::where('attempted_at', '>=', $since)->distinct('user_id')->count('user_id');
        $uniqueIps = static::where('attempted_at', '>=', $since)->distinct('ip_address')->count('ip_address');

        return [
            'total' => $total,
            'successful' => $successful,
            'failed' => $failed,
            'success_rate' => $total > 0 ? round(($successful / $total) * 100, 2) : 0,
            'unique_users' => $uniqueUsers,
            'unique_ips' => $uniqueIps,
            'period_days' => $days,
        ];
    }

    /**
     * Get method usage statistics.
     *
     * @param int $days
     * @return array
     */
    public static function getMethodStats(int $days = 7): array
    {
        $since = now()->subDays($days);

        return static::where('attempted_at', '>=', $since)
            ->groupBy('method')
            ->selectRaw('method, COUNT(*) as total, SUM(successful) as successful')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->method => [
                    'total' => $item->total,
                    'successful' => $item->successful,
                    'failed' => $item->total - $item->successful,
                    'success_rate' => $item->total > 0 ? round(($item->successful / $item->total) * 100, 2) : 0,
                ]];
            })
            ->toArray();
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
     * Convert the model to its array representation for safe API responses.
     *
     * @return array
     */
    public function toSafeArray(): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'masked_ip_address' => $this->masked_ip_address,
            'method' => $this->method,
            'method_display_name' => $this->method_display_name,
            'type' => $this->type,
            'type_display_name' => $this->type_display_name,
            'successful' => $this->successful,
            'failure_reason' => $this->failure_reason,
            'failure_reason_display_name' => $this->failure_reason_display_name,
            'code_length' => $this->code_length,
            'attempted_at' => $this->attempted_at->toISOString(),
            'attempted_at_human' => $this->attempted_at_human,
            'created_at' => $this->created_at->toISOString(),
        ];
    }
}
