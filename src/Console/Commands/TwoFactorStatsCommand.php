<?php

declare(strict_types=1);

namespace MetaSoftDevs\LaravelBreeze2FA\Console\Commands;

use Illuminate\Console\Command;
use MetaSoftDevs\LaravelBreeze2FA\Models\TwoFactorAttempt;

/**
 * Two-Factor Stats Command
 *
 * This command displays 2FA statistics and analytics for the application.
 * Usage: php artisan two-factor:stats
 *
 * @package MetaSoftDevs\LaravelBreeze2FA\Console\Commands
 * @author MetaSoft Developers <developers@metasoft.dev>
 * @version 1.0.0
 */
class TwoFactorStatsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'two-factor:stats {--days=7 : Number of days to include in stats}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Display two-factor authentication statistics and analytics.';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $days = (int) $this->option('days') ?: 7;

        $this->info('Two-Factor Authentication Statistics');
        $this->line('Period: Last ' . $days . ' days');
        $this->line(str_repeat('-', 40));

        // Global stats
        $global = TwoFactorAttempt::getGlobalStats($days);
        $this->section('Global');
        $this->table(
            ['Total', 'Successful', 'Failed', 'Success Rate (%)', 'Unique Users', 'Unique IPs'],
            [[
                $global['total'],
                $global['successful'],
                $global['failed'],
                $global['success_rate'],
                $global['unique_users'],
                $global['unique_ips'],
            ]]
        );

        // Method stats
        $methodStats = TwoFactorAttempt::getMethodStats($days);
        $this->section('By Method');
        $rows = [];
        foreach ($methodStats as $method => $data) {
            $rows[] = [
                ucfirst($method),
                $data['total'],
                $data['successful'],
                $data['failed'],
                $data['success_rate'],
            ];
        }
        $this->table(['Method', 'Total', 'Successful', 'Failed', 'Success Rate (%)'], $rows);

        // TODO: Add per-user, per-IP, and time-series analytics
        // TODO: Add export options (CSV, JSON)

        $this->info('Done.');
        return 0;
    }

    /**
     * Output a section header.
     *
     * @param string $title
     * @return void
     */
    protected function section(string $title): void
    {
        $this->line('');
        $this->info($title);
        $this->line(str_repeat('-', 40));
    }
}
