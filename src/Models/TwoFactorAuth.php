<?php

declare(strict_types=1);

namespace MetaSoftDevs\LaravelBreeze2FA\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Config;
use Carbon\Carbon;

/**
 * Two-Factor Authentication Model
 *
 * This model stores the two-factor authentication settings and data for users.
 * It handles encryption of sensitive data like TOTP secrets and provides
 * relationships to recovery codes and authentication attempts.
 *
 * @package MetaSoftDevs\LaravelBreeze2FA\Models
 * @author Meta Software Developers <metasoftdevs.com>
 * @version 1.0.0
 *
 * @property int $id
 * @property int $user_id
 * @property bool $enabled
 * @property string $method
 * @property string|null $secret
 * @property string|null $phone_number
 * @property Carbon|null $confirmed_at
 * @property Carbon|null $backup_codes_generated_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon|null $deleted_at
 *
 * @property-read \Illuminate\Contracts\Auth\Authenticatable $user
 * @property-read \Illuminate\Database\Eloquent\Collection|\MetaSoftDevs\LaravelBreeze2FA\Models\TwoFactorRecoveryCode[] $recoveryCodes
 * @property-read \Illuminate\Database\Eloquent\Collection|\MetaSoftDevs\LaravelBreeze2FA\Models\TwoFactorAttempt[] $attempts
 */
class TwoFactorAuth extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'two_factor_auths';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'enabled',
        'method',
        'secret',
        'phone_number',
        'confirmed_at',
        'backup_codes_generated_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'secret',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'enabled' => 'boolean',
        'confirmed_at' => 'datetime',
        'backup_codes_generated_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * The two-factor authentication methods that are supported.
     *
     * @var array<string, string>
     */
    public const METHODS = [
        'totp' => 'Time-based One-Time Password',
        'email' => 'Email One-Time Password',
        'sms' => 'SMS One-Time Password',
    ];

    /**
     * Boot the model.
     *
     * @return void
     */
    protected static function boot(): void
    {
        parent::boot();

        // Set the table name from config if available
        if (Config::has('two-factor.database.tables.two_factor_auths')) {
            (new static)->setTable(Config::get('two-factor.database.tables.two_factor_auths'));
        }
    }

    /**
     * Get the user that owns the two-factor authentication record.
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
     * Get the recovery codes for this two-factor authentication.
     *
     * @return HasMany
     */
    public function recoveryCodes(): HasMany
    {
        return $this->hasMany(TwoFactorRecoveryCode::class, 'user_id', 'user_id');
    }

    /**
     * Get the authentication attempts for this user.
     *
     * @return HasMany
     */
    public function attempts(): HasMany
    {
        return $this->hasMany(TwoFactorAttempt::class, 'user_id', 'user_id');
    }

    /**
     * Get the remembered sessions for this user.
     *
     * @return HasMany
     */
    public function rememberedSessions(): HasMany
    {
        return $this->hasMany(TwoFactorSession::class, 'user_id', 'user_id');
    }

    /**
     * Get the decrypted secret.
     *
     * @return string|null
     */
    public function getDecryptedSecretAttribute(): ?string
    {
        if (is_null($this->secret)) {
            return null;
        }

        if (!Config::get('two-factor.security.encrypt_secrets', true)) {
            return $this->secret;
        }

        try {
            return Crypt::decryptString($this->secret);
        } catch (\Exception $e) {
            // If decryption fails, assume it's already decrypted (for migration purposes)
            return $this->secret;
        }
    }

    /**
     * Set the secret with encryption.
     *
     * @param string|null $value
     * @return void
     */
    public function setSecretAttribute(?string $value): void
    {
        if (is_null($value)) {
            $this->attributes['secret'] = null;
            return;
        }

        if (!Config::get('two-factor.security.encrypt_secrets', true)) {
            $this->attributes['secret'] = $value;
            return;
        }

        $this->attributes['secret'] = Crypt::encryptString($value);
    }

    /**
     * Get the formatted phone number.
     *
     * @return string|null
     */
    public function getFormattedPhoneNumberAttribute(): ?string
    {
        if (is_null($this->phone_number)) {
            return null;
        }

        // Basic phone number formatting - can be enhanced as needed
        $cleaned = preg_replace('/[^0-9]/', '', $this->phone_number);

        if (strlen($cleaned) === 10) {
            return sprintf(
                '(%s) %s-%s',
                substr($cleaned, 0, 3),
                substr($cleaned, 3, 3),
                substr($cleaned, 6, 4)
            );
        }

        if (strlen($cleaned) === 11 && substr($cleaned, 0, 1) === '1') {
            return sprintf(
                '+1 (%s) %s-%s',
                substr($cleaned, 1, 3),
                substr($cleaned, 4, 3),
                substr($cleaned, 7, 4)
            );
        }

        return $this->phone_number;
    }

    /**
     * Check if the two-factor authentication is confirmed.
     *
     * @return bool
     */
    public function isConfirmed(): bool
    {
        return !is_null($this->confirmed_at);
    }

    /**
     * Check if the method is TOTP.
     *
     * @return bool
     */
    public function isTotp(): bool
    {
        return $this->method === 'totp';
    }

    /**
     * Check if the method is Email OTP.
     *
     * @return bool
     */
    public function isEmailOtp(): bool
    {
        return $this->method === 'email';
    }

    /**
     * Check if the method is SMS OTP.
     *
     * @return bool
     */
    public function isSmsOtp(): bool
    {
        return $this->method === 'sms';
    }

    /**
     * Check if backup codes need to be regenerated.
     *
     * @return bool
     */
    public function needsBackupCodeRegeneration(): bool
    {
        if (!Config::get('two-factor.recovery_codes.enabled', true)) {
            return false;
        }

        $threshold = Config::get('two-factor.recovery_codes.regenerate_threshold', 3);
        $unusedCount = $this->recoveryCodes()->where('used_at', null)->count();

        return $unusedCount <= $threshold;
    }

    /**
     * Get the QR code URL for TOTP setup.
     *
     * @return string|null
     */
    public function getQrCodeUrlAttribute(): ?string
    {
        if (!$this->isTotp() || is_null($this->decrypted_secret)) {
            return null;
        }

        $issuer = Config::get('two-factor.methods.totp.issuer', Config::get('app.name', 'Laravel'));
        $email = $this->user->email ?? 'user@example.com';

        $parameters = http_build_query([
            'secret' => $this->decrypted_secret,
            'issuer' => $issuer,
            'algorithm' => strtoupper(Config::get('two-factor.methods.totp.algorithm', 'sha1')),
            'digits' => Config::get('two-factor.methods.totp.digits', 6),
            'period' => Config::get('two-factor.methods.totp.period', 30),
        ]);

        return "otpauth://totp/{$issuer}:{$email}?{$parameters}";
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
     * Scope query to enabled two-factor authentication records.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeEnabled($query)
    {
        return $query->where('enabled', true);
    }

    /**
     * Scope query to confirmed two-factor authentication records.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeConfirmed($query)
    {
        return $query->whereNotNull('confirmed_at');
    }

    /**
     * Scope query to unconfirmed two-factor authentication records.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeUnconfirmed($query)
    {
        return $query->whereNull('confirmed_at');
    }

    /**
     * Scope query to records by method.
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
     * Get the connection name for the model.
     *
     * @return string|null
     */
    public function getConnectionName()
    {
        return Config::get('two-factor.database.connection') ?: parent::getConnectionName();
    }

    /**
     * Mark the two-factor authentication as confirmed.
     *
     * @return bool
     */
    public function markAsConfirmed(): bool
    {
        return $this->update([
            'confirmed_at' => now(),
            'enabled' => true,
        ]);
    }

    /**
     * Mark backup codes as generated.
     *
     * @return bool
     */
    public function markBackupCodesAsGenerated(): bool
    {
        return $this->update([
            'backup_codes_generated_at' => now(),
        ]);
    }

    /**
     * Convert the model to its array representation for API responses.
     *
     * @return array
     */
    public function toSafeArray(): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'enabled' => $this->enabled,
            'method' => $this->method,
            'method_display_name' => $this->method_display_name,
            'confirmed' => $this->isConfirmed(),
            'phone_number' => $this->formatted_phone_number,
            'confirmed_at' => $this->confirmed_at?->toISOString(),
            'backup_codes_generated_at' => $this->backup_codes_generated_at?->toISOString(),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
