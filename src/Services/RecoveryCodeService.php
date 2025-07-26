<?php

declare(strict_types=1);

namespace MetaSoftDevs\LaravelBreeze2FA\Services;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use MetaSoftDevs\LaravelBreeze2FA\Contracts\RecoveryCodeServiceInterface;
use MetaSoftDevs\LaravelBreeze2FA\Models\TwoFactorRecoveryCode;
use MetaSoftDevs\LaravelBreeze2FA\Models\TwoFactorAuth;
use MetaSoftDevs\LaravelBreeze2FA\Events\RecoveryCodesGenerated;
use MetaSoftDevs\LaravelBreeze2FA\Events\RecoveryCodeUsed;
use MetaSoftDevs\LaravelBreeze2FA\Events\RecoveryCodesRegenerated;
use MetaSoftDevs\LaravelBreeze2FA\Exceptions\TwoFactorException;

/**
 * Recovery Code Service
 *
 * This service manages backup recovery codes that users can use when they
 * don't have access to their primary 2FA method. It handles generation,
 * verification, and management of single-use recovery codes.
 *
 * @package MetaSoftDevs\LaravelBreeze2FA\Services
 * @author MetaSoft Developers <developers@metasoft.dev>
 * @version 1.0.0
 */
class RecoveryCodeService implements RecoveryCodeServiceInterface
{
    /**
     * Generate recovery codes for a user.
     *
     * @param Authenticatable $user
     * @param int|null $count Number of codes to generate
     * @return array Array of plain text recovery codes
     * @throws TwoFactorException
     */
    public function generate(Authenticatable $user, ?int $count = null): array
    {
        if (!Config::get('two-factor.recovery_codes.enabled', true)) {
            throw new TwoFactorException('Recovery codes are not enabled.');
        }

        $count = $count ?? Config::get('two-factor.recovery_codes.count', 8);
        $userId = $user->getAuthIdentifier();

        // Delete any existing recovery codes first
        $this->deleteAll($user);

        // Generate new codes
        $plainCodes = TwoFactorRecoveryCode::generateCodesForUser($userId, $count);

        // Update the TwoFactorAuth record
        TwoFactorAuth::where('user_id', $userId)->update([
            'backup_codes_generated_at' => now(),
        ]);

        // Fire event
        if (Config::get('two-factor.events.enabled', true)) {
            Event::dispatch(new RecoveryCodesGenerated($user, count($plainCodes)));
        }

        return $plainCodes;
    }

    /**
     * Verify a recovery code for a user.
     *
     * @param Authenticatable $user
     * @param string $code
     * @return bool
     */
    public function verify(Authenticatable $user, string $code): bool
    {
        if (!Config::get('two-factor.recovery_codes.enabled', true)) {
            return false;
        }

        $userId = $user->getAuthIdentifier();

        // Clean the code (remove spaces, dashes, etc.)
        $cleanCode = strtoupper(preg_replace('/[^A-Z0-9]/', '', $code));

        // Find an unused recovery code that matches
        $recoveryCode = TwoFactorRecoveryCode::findUnusedByPlainCode($userId, $cleanCode);

        if (!$recoveryCode) {
            return false;
        }

        // Mark the code as used
        $recoveryCode->markAsUsed(
            request()->ip(),
            request()->userAgent()
        );

        // Fire event
        if (Config::get('two-factor.events.enabled', true)) {
            Event::dispatch(new RecoveryCodeUsed($user, $cleanCode));
        }

        // Check if we need to suggest regeneration
        if ($this->needsRegeneration($user)) {
            // You could queue a notification here to suggest regenerating codes
        }

        return true;
    }

    /**
     * Check if a code looks like a recovery code format.
     *
     * @param string $code
     * @return bool
     */
    public function isRecoveryCode(string $code): bool
    {
        if (!Config::get('two-factor.recovery_codes.enabled', true)) {
            return false;
        }

        // Clean the code first
        $cleanCode = strtoupper(preg_replace('/[^A-Z0-9]/', '', $code));

        $expectedLength = Config::get('two-factor.recovery_codes.length', 10);

        // Check if it matches expected length and format
        return strlen($cleanCode) === $expectedLength &&
            preg_match('/^[A-Z0-9]+$/', $cleanCode);
    }

    /**
     * Get the count of unused recovery codes for a user.
     *
     * @param Authenticatable $user
     * @return int
     */
    public function getUnusedCount(Authenticatable $user): int
    {
        $userId = $user->getAuthIdentifier();

        return TwoFactorRecoveryCode::forUser($userId)->unused()->count();
    }

    /**
     * Get the count of used recovery codes for a user.
     *
     * @param Authenticatable $user
     * @return int
     */
    public function getUsedCount(Authenticatable $user): int
    {
        $userId = $user->getAuthIdentifier();

        return TwoFactorRecoveryCode::forUser($userId)->used()->count();
    }

    /**
     * Get the total count of recovery codes for a user.
     *
     * @param Authenticatable $user
     * @return int
     */
    public function getTotalCount(Authenticatable $user): int
    {
        $userId = $user->getAuthIdentifier();

        return TwoFactorRecoveryCode::forUser($userId)->count();
    }

    /**
     * Check if recovery codes need regeneration.
     *
     * @param Authenticatable $user
     * @return bool
     */
    public function needsRegeneration(Authenticatable $user): bool
    {
        if (!Config::get('two-factor.recovery_codes.enabled', true)) {
            return false;
        }

        $threshold = Config::get('two-factor.recovery_codes.regenerate_threshold', 3);
        $unusedCount = $this->getUnusedCount($user);

        return $unusedCount <= $threshold;
    }

    /**
     * Regenerate all recovery codes for a user.
     *
     * @param Authenticatable $user
     * @param int|null $count
     * @return array New recovery codes
     * @throws TwoFactorException
     */
    public function regenerate(Authenticatable $user, ?int $count = null): array
    {
        if (!Config::get('two-factor.recovery_codes.enabled', true)) {
            throw new TwoFactorException('Recovery codes are not enabled.');
        }

        $userId = $user->getAuthIdentifier();
        $count = $count ?? Config::get('two-factor.recovery_codes.count', 8);

        // Get old statistics for the event
        $oldStats = $this->getStatistics($user);

        // Regenerate codes
        $plainCodes = TwoFactorRecoveryCode::regenerateForUser($userId, $count);

        // Update the TwoFactorAuth record
        TwoFactorAuth::where('user_id', $userId)->update([
            'backup_codes_generated_at' => now(),
        ]);

        // Fire event
        if (Config::get('two-factor.events.enabled', true)) {
            Event::dispatch(new RecoveryCodesRegenerated($user, count($plainCodes), $oldStats));
        }

        return $plainCodes;
    }

    /**
     * Delete all recovery codes for a user.
     *
     * @param Authenticatable $user
     * @return int Number of deleted codes
     */
    public function deleteAll(Authenticatable $user): int
    {
        $userId = $user->getAuthIdentifier();

        return TwoFactorRecoveryCode::deleteAllForUser($userId);
    }

    /**
     * Get recovery code statistics for a user.
     *
     * @param Authenticatable $user
     * @return array
     */
    public function getStatistics(Authenticatable $user): array
    {
        $userId = $user->getAuthIdentifier();

        return TwoFactorRecoveryCode::getStatistics($userId);
    }

    /**
     * Get all recovery codes for a user (for display purposes).
     *
     * @param Authenticatable $user
     * @param bool $includeUsed Whether to include used codes
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAllCodes(Authenticatable $user, bool $includeUsed = true)
    {
        $userId = $user->getAuthIdentifier();

        $query = TwoFactorRecoveryCode::forUser($userId);

        if (!$includeUsed) {
            $query->unused();
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    /**
     * Check if a user has any recovery codes.
     *
     * @param Authenticatable $user
     * @return bool
     */
    public function hasRecoveryCodes(Authenticatable $user): bool
    {
        return $this->getTotalCount($user) > 0;
    }

    /**
     * Check if a user has any unused recovery codes.
     *
     * @param Authenticatable $user
     * @return bool
     */
    public function hasUnusedRecoveryCodes(Authenticatable $user): bool
    {
        return $this->getUnusedCount($user) > 0;
    }

    /**
     * Get the last time recovery codes were generated.
     *
     * @param Authenticatable $user
     * @return \Carbon\Carbon|null
     */
    public function getLastGeneratedAt(Authenticatable $user)
    {
        $userId = $user->getAuthIdentifier();

        $twoFactorAuth = TwoFactorAuth::where('user_id', $userId)->first();

        return $twoFactorAuth?->backup_codes_generated_at;
    }

    /**
     * Validate recovery code format without checking if it exists.
     *
     * @param string $code
     * @return bool
     */
    public function validateFormat(string $code): bool
    {
        // Clean the code
        $cleanCode = strtoupper(preg_replace('/[^A-Z0-9]/', '', $code));

        $expectedLength = Config::get('two-factor.recovery_codes.length', 10);
        $minLength = 6; // Minimum reasonable length
        $maxLength = 20; // Maximum reasonable length

        // Check basic format requirements
        if (strlen($cleanCode) < $minLength || strlen($cleanCode) > $maxLength) {
            return false;
        }

        // Check if it contains only valid characters
        if (!preg_match('/^[A-Z0-9]+$/', $cleanCode)) {
            return false;
        }

        return true;
    }

    /**
     * Format a recovery code for display (add dashes).
     *
     * @param string $code
     * @return string
     */
    public function formatCodeForDisplay(string $code): string
    {
        $cleanCode = strtoupper(preg_replace('/[^A-Z0-9]/', '', $code));

        // Add dashes every 4 characters for readability
        return implode('-', str_split($cleanCode, 4));
    }

    /**
     * Get recent recovery code usage for audit purposes.
     *
     * @param Authenticatable $user
     * @param int $hours
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getRecentUsage(Authenticatable $user, int $hours = 24)
    {
        $userId = $user->getAuthIdentifier();

        return TwoFactorRecoveryCode::forUser($userId)
            ->recentlyUsed($hours)
            ->orderBy('used_at', 'desc')
            ->get();
    }

    /**
     * Clean up old used recovery codes.
     *
     * @param int $daysOld
     * @return int Number of deleted codes
     */
    public function cleanupOldUsedCodes(int $daysOld = 30): int
    {
        return TwoFactorRecoveryCode::used()
            ->where('used_at', '<', now()->subDays($daysOld))
            ->delete();
    }

    /**
     * Get configuration for recovery codes.
     *
     * @return array
     */
    public function getConfiguration(): array
    {
        return [
            'enabled' => Config::get('two-factor.recovery_codes.enabled', true),
            'count' => Config::get('two-factor.recovery_codes.count', 8),
            'length' => Config::get('two-factor.recovery_codes.length', 10),
            'regenerate_threshold' => Config::get('two-factor.recovery_codes.regenerate_threshold', 3),
        ];
    }
}
