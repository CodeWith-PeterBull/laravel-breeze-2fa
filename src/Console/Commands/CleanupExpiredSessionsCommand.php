<?php

declare(strict_types=1);

namespace MetaSoftDevs\LaravelBreeze2FA\Console\Commands;

use Illuminate\Console\Command;
use MetaSoftDevs\LaravelBreeze2FA\Models\TwoFactorSession;
use MetaSoftDevs\LaravelBreeze2FA\Models\TwoFactorRecoveryCode;
use MetaSoftDevs\LaravelBreeze2FA\Models\TwoFactorAttempt;
use Illuminate\Support\Facades\DB;

/**
 * Cleanup Expired Sessions Command
 *
 * This command cleans up expired two-factor authentication sessions,
 * old recovery codes, and stale authentication attempts to maintain
 * database performance and security.
 *
 * @package MetaSoftDevs\LaravelBreeze2FA\Console\Commands
 * @author MetaSoft Developers <developers@metasoft.dev>
 * @version 1.0.0
 */
class CleanupExpiredSessionsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'two-factor:cleanup
                            {--sessions : Clean up expired device sessions}
                            {--attempts : Clean up old authentication attempts}
                            {--recovery : Clean up old used recovery codes}
                            {--all : Clean up all expired data}
                            {--dry-run : Show what would be cleaned without actually deleting}
                            {--days=30 : Number of days to keep data}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up expired two-factor authentication data';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $this->info('🧹 Starting Two-Factor Authentication cleanup...');
        $this->newLine();

        $isDryRun = $this->option('dry-run');
        $days = (int) $this->option('days');

        if ($isDryRun) {
            $this->warn('🔍 DRY RUN MODE - No data will be deleted');
            $this->newLine();
        }

        $totalCleaned = 0;

        // Clean up based on options
        if ($this->option('all') || $this->option('sessions')) {
            $totalCleaned += $this->cleanupExpiredSessions($isDryRun);
        }

        if ($this->option('all') || $this->option('attempts')) {
            $totalCleaned += $this->cleanupOldAttempts($isDryRun, $days);
        }

        if ($this->option('all') || $this->option('recovery')) {
            $totalCleaned += $this->cleanupOldRecoveryCodes($isDryRun, $days);
        }

        // If no specific options, prompt user
        if (
            !$this->option('all') && !$this->option('sessions') &&
            !$this->option('attempts') && !$this->option('recovery')
        ) {
            $totalCleaned += $this->interactiveCleanup($isDryRun, $days);
        }

        $this->newLine();

        if ($isDryRun) {
            $this->info("✅ Dry run completed. {$totalCleaned} records would be cleaned.");
        } else {
            $this->info("✅ Cleanup completed. {$totalCleaned} records cleaned.");
        }

        // Show current statistics
        $this->showStatistics();

        return Command::SUCCESS;
    }

    /**
     * Clean up expired device sessions.
     *
     * @param bool $isDryRun
     * @return int
     */
    protected function cleanupExpiredSessions(bool $isDryRun): int
    {
        $this->info('🗑️  Cleaning up expired device sessions...');

        $expiredCount = TwoFactorSession::expired()->count();

        if ($expiredCount === 0) {
            $this->line('   ✓ No expired sessions found');
            return 0;
        }

        if (!$isDryRun) {
            $cleaned = TwoFactorSession::cleanupExpired();
        } else {
            $cleaned = $expiredCount;
        }

        $this->line("   ✓ {$cleaned} expired sessions " . ($isDryRun ? 'would be' : '') . " cleaned");

        return $cleaned;
    }

    /**
     * Clean up old authentication attempts.
     *
     * @param bool $isDryRun
     * @param int $days
     * @return int
     */
    protected function cleanupOldAttempts(bool $isDryRun, int $days): int
    {
        $this->info("🗑️  Cleaning up authentication attempts older than {$days} days...");

        if (!class_exists(TwoFactorAttempt::class)) {
            $this->warn('   ! TwoFactorAttempt model not available');
            return 0;
        }

        $cutoffDate = now()->subDays($days);
        $oldCount = TwoFactorAttempt::where('attempted_at', '<', $cutoffDate)->count();

        if ($oldCount === 0) {
            $this->line('   ✓ No old attempts found');
            return 0;
        }

        if (!$isDryRun) {
            $cleaned = TwoFactorAttempt::where('attempted_at', '<', $cutoffDate)->delete();
        } else {
            $cleaned = $oldCount;
        }

        $this->line("   ✓ {$cleaned} old attempts " . ($isDryRun ? 'would be' : '') . " cleaned");

        return $cleaned;
    }

    /**
     * Clean up old used recovery codes.
     *
     * @param bool $isDryRun
     * @param int $days
     * @return int
     */
    protected function cleanupOldRecoveryCodes(bool $isDryRun, int $days): int
    {
        $this->info("🗑️  Cleaning up used recovery codes older than {$days} days...");

        $cutoffDate = now()->subDays($days);
        $oldCount = TwoFactorRecoveryCode::used()
            ->where('used_at', '<', $cutoffDate)
            ->count();

        if ($oldCount === 0) {
            $this->line('   ✓ No old recovery codes found');
            return 0;
        }

        if (!$isDryRun) {
            $cleaned = TwoFactorRecoveryCode::used()
                ->where('used_at', '<', $cutoffDate)
                ->delete();
        } else {
            $cleaned = $oldCount;
        }

        $this->line("   ✓ {$cleaned} old recovery codes " . ($isDryRun ? 'would be' : '') . " cleaned");

        return $cleaned;
    }

    /**
     * Interactive cleanup with user prompts.
     *
     * @param bool $isDryRun
     * @param int $days
     * @return int
     */
    protected function interactiveCleanup(bool $isDryRun, int $days): int
    {
        $totalCleaned = 0;

        if ($this->confirm('Clean up expired device sessions?', true)) {
            $totalCleaned += $this->cleanupExpiredSessions($isDryRun);
        }

        if ($this->confirm("Clean up authentication attempts older than {$days} days?", true)) {
            $totalCleaned += $this->cleanupOldAttempts($isDryRun, $days);
        }

        if ($this->confirm("Clean up used recovery codes older than {$days} days?", true)) {
            $totalCleaned += $this->cleanupOldRecoveryCodes($isDryRun, $days);
        }

        return $totalCleaned;
    }

    /**
     * Show current statistics.
     *
     * @return void
     */
    protected function showStatistics(): void
    {
        $this->newLine();
        $this->info('📊 Current Statistics:');

        // Device sessions
        $activeSessions = TwoFactorSession::active()->count();
        $expiredSessions = TwoFactorSession::expired()->count();
        $this->line("   Device Sessions: {$activeSessions} active, {$expiredSessions} expired");

        // Recovery codes
        $unusedCodes = TwoFactorRecoveryCode::unused()->count();
        $usedCodes = TwoFactorRecoveryCode::used()->count();
        $this->line("   Recovery Codes: {$unusedCodes} unused, {$usedCodes} used");

        // Attempts (if available)
        if (class_exists(TwoFactorAttempt::class)) {
            $totalAttempts = TwoFactorAttempt::count();
            $successfulAttempts = TwoFactorAttempt::where('successful', true)->count();
            $failedAttempts = TwoFactorAttempt::where('successful', false)->count();
            $this->line("   Auth Attempts: {$totalAttempts} total ({$successfulAttempts} successful, {$failedAttempts} failed)");
        }
    }

    /**
     * Get detailed cleanup report.
     *
     * @param int $days
     * @return array
     */
    protected function getCleanupReport(int $days): array
    {
        $cutoffDate = now()->subDays($days);

        $report = [
            'expired_sessions' => TwoFactorSession::expired()->count(),
            'old_attempts' => 0,
            'old_recovery_codes' => TwoFactorRecoveryCode::used()
                ->where('used_at', '<', $cutoffDate)
                ->count(),
        ];

        if (class_exists(TwoFactorAttempt::class)) {
            $report['old_attempts'] = TwoFactorAttempt::where('attempted_at', '<', $cutoffDate)->count();
        }

        return $report;
    }

    /**
     * Display cleanup report table.
     *
     * @param array $report
     * @param bool $isDryRun
     * @return void
     */
    protected function displayReport(array $report, bool $isDryRun): void
    {
        $headers = ['Type', 'Count', 'Action'];
        $rows = [];

        foreach ($report as $type => $count) {
            $action = $count > 0 ? ($isDryRun ? 'Would Clean' : 'Clean') : 'None';
            $rows[] = [
                str_replace('_', ' ', ucwords($type)),
                $count,
                $action,
            ];
        }

        $this->table($headers, $rows);
    }

    /**
     * Optimize database after cleanup.
     *
     * @return void
     */
    protected function optimizeDatabase(): void
    {
        if ($this->confirm('Would you like to optimize the database tables?', false)) {
            $this->info('🔧 Optimizing database tables...');

            try {
                $tables = [
                    'two_factor_sessions',
                    'two_factor_recovery_codes',
                    'two_factor_attempts',
                ];

                foreach ($tables as $table) {
                    DB::statement("OPTIMIZE TABLE {$table}");
                    $this->line("   ✓ Optimized {$table}");
                }
            } catch (\Exception $e) {
                $this->warn('   ! Database optimization failed: ' . $e->getMessage());
            }
        }
    }

    /**
     * Schedule automatic cleanup.
     *
     * @return void
     */
    protected function scheduleCleanup(): void
    {
        $this->newLine();
        $this->info('💡 Tip: Add this command to your scheduler for automatic cleanup:');
        $this->newLine();
        $this->comment('// In app/Console/Kernel.php');
        $this->comment('$schedule->command(\'two-factor:cleanup --all\')->daily();');
        $this->newLine();

        if ($this->confirm('Would you like to see more scheduling options?', false)) {
            $this->info('📅 Scheduling Options:');
            $this->comment('// Clean expired sessions daily');
            $this->comment('$schedule->command(\'two-factor:cleanup --sessions\')->daily();');
            $this->newLine();
            $this->comment('// Clean old attempts weekly');
            $this->comment('$schedule->command(\'two-factor:cleanup --attempts --days=7\')->weekly();');
            $this->newLine();
            $this->comment('// Full cleanup monthly');
            $this->comment('$schedule->command(\'two-factor:cleanup --all --days=90\')->monthly();');
        }
    }
}
