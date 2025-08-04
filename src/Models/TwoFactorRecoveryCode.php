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
 * Two-Factor Recovery Code Model
 *
 * This model stores backup recovery codes that users can use when they
 * don't have access to their primary 2FA method. Each code is single-use
 * and securely hashed for storage.
 *
 * @package MetaSoftDevs\LaravelBreeze2FA\Models
 * @author Meta Software Developers <info@metasoftdevs.com>
 * @version 1.0.0
 *
 * @property int $id
 * @property int $user_id
 * @property string $code_hash
 * @property Carbon|null $used_at
 * @property string|null $used_ip
 * @property string|null $used_user_agent
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * @property-read \Illuminate\Contracts\Auth\Authenticatable $user
 * @property-read bool $is_used
 * @property-read bool $is_unused
 */
class TwoFactorRecoveryCode extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'two_factor_recovery_codes';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'code_hash',
        'used_at',
        'used_ip',
        'used_user_agent',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'code_hash',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'used_at' => 'datetime',
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
        if (Config::has('two-factor.database.tables.two_factor_recovery_codes')) {
            static::setTableName(Config::get('two-factor.database.tables.two_factor_recovery_codes'));
        }
    }

    /**
     * Get the user that owns the recovery code.
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
     * Check if the recovery code has been used.
     *
     * @return bool
     */
    public function getIsUsedAttribute(): bool
    {
        return !is_null($this->used_at);
    }

    /**
     * Check if the recovery code is unused.
     *
     * @return bool
     */
    public function getIsUnusedAttribute(): bool
    {
        return is_null($this->used_at);
    }

    /**
     * Create a new recovery code with hashed value.
     *
     * @param int $userId
     * @param string $plainCode
     * @return static
     */
    public static function createFromPlainCode(int $userId, string $plainCode): static
    {
        return static::create([
            'user_id' => $userId,
            'code_hash' => static::hashCode($plainCode),
        ]);
    }

    /**
     * Verify if a plain code matches this recovery code.
     *
     * @param string $plainCode
     * @return bool
     */
    public function verify(string $plainCode): bool
    {
        if ($this->is_used) {
            return false;
        }

        return Hash::check($plainCode, $this->code_hash);
    }

    /**
     * Mark the recovery code as used.
     *
     * @param string|null $ipAddress
     * @param string|null $userAgent
     * @return bool
     */
    public function markAsUsed(?string $ipAddress = null, ?string $userAgent = null): bool
    {
        return $this->update([
            'used_at' => now(),
            'used_ip' => $ipAddress,
            'used_user_agent' => $userAgent,
        ]);
    }

    /**
     * Hash a plain recovery code.
     *
     * @param string $plainCode
     * @return string
     */
    public static function hashCode(string $plainCode): string
    {
        if (Config::get('two-factor.security.hash_recovery_codes', true)) {
            return Hash::make($plainCode);
        }

        // If hashing is disabled, store as-is (not recommended for production)
        return $plainCode;
    }

    /**
     * Generate a random recovery code.
     *
     * @param int|null $length
     * @return string
     */
    public static function generateCode(?int $length = null): string
    {
        $length = $length ?? Config::get('two-factor.recovery_codes.length', 10);

        // Generate a random string with alphanumeric characters (excluding confusing ones)
        $characters = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';
        $code = '';

        for ($i = 0; $i < $length; $i++) {
            $code .= $characters[random_int(0, strlen($characters) - 1)];
        }

        // Format with dashes for readability (e.g., ABCD-EFGH-IJ)
        if ($length >= 8) {
            $code = implode('-', str_split($code, 4));
        }

        return $code;
    }

    /**
     * Generate multiple recovery codes for a user.
     *
     * @param int $userId
     * @param int|null $count
     * @return array Array of plain codes (before hashing)
     */
    public static function generateCodesForUser(int $userId, ?int $count = null): array
    {
        $count = $count ?? Config::get('two-factor.recovery_codes.count', 8);
        $codes = [];

        for ($i = 0; $i < $count; $i++) {
            $plainCode = static::generateCode();
            $codes[] = $plainCode;

            // Create the hashed record
            static::createFromPlainCode($userId, $plainCode);
        }

        return $codes;
    }

    /**
     * Scope query to unused recovery codes.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeUnused($query)
    {
        return $query->whereNull('used_at');
    }

    /**
     * Scope query to used recovery codes.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeUsed($query)
    {
        return $query->whereNotNull('used_at');
    }

    /**
     * Scope query to codes for a specific user.
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
     * Scope query to recently created codes.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $hours
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeRecentlyCreated($query, int $hours = 24)
    {
        return $query->where('created_at', '>=', now()->subHours($hours));
    }

    /**
     * Scope query to recently used codes.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $hours
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeRecentlyUsed($query, int $hours = 24)
    {
        return $query->whereNotNull('used_at')
            ->where('used_at', '>=', now()->subHours($hours));
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
     * Get statistics for recovery codes.
     *
     * @param int $userId
     * @return array
     */
    public static function getStatistics(int $userId): array
    {
        $total = static::forUser($userId)->count();
        $used = static::forUser($userId)->used()->count();
        $unused = static::forUser($userId)->unused()->count();
        $recentlyUsed = static::forUser($userId)->recentlyUsed(7)->count();

        return [
            'total' => $total,
            'used' => $used,
            'unused' => $unused,
            'usage_percentage' => $total > 0 ? round(($used / $total) * 100, 2) : 0,
            'recently_used' => $recentlyUsed,
            'needs_regeneration' => $unused <= Config::get('two-factor.recovery_codes.regenerate_threshold', 3),
        ];
    }

    /**
     * Delete all recovery codes for a user.
     *
     * @param int $userId
     * @return int Number of deleted codes
     */
    public static function deleteAllForUser(int $userId): int
    {
        return static::forUser($userId)->delete();
    }

    /**
     * Regenerate all recovery codes for a user.
     *
     * @param int $userId
     * @param int|null $count
     * @return array New plain codes
     */
    public static function regenerateForUser(int $userId, ?int $count = null): array
    {
        // Delete existing codes
        static::deleteAllForUser($userId);

        // Generate new codes
        return static::generateCodesForUser($userId, $count);
    }

    /**
     * Find an unused recovery code for a user by plain code.
     *
     * @param int $userId
     * @param string $plainCode
     * @return static|null
     */
    public static function findUnusedByPlainCode(int $userId, string $plainCode): ?static
    {
        $codes = static::forUser($userId)->unused()->get();

        foreach ($codes as $code) {
            if ($code->verify($plainCode)) {
                return $code;
            }
        }

        return null;
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
            'is_used' => $this->is_used,
            'used_at' => $this->used_at?->toISOString(),
            'created_at' => $this->created_at->toISOString(),
            // Never include the actual code or hash in API responses
        ];
    }
}
