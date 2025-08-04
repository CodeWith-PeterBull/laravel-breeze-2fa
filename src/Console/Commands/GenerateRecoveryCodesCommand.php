<?php

declare(strict_types=1);

namespace MetaSoftDevs\LaravelBreeze2FA\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Contracts\Auth\Authenticatable;
use MetaSoftDevs\LaravelBreeze2FA\Contracts\RecoveryCodeServiceInterface;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;

/**
 * Generate Recovery Codes Command
 *
 * This command generates new recovery codes for a specified user.
 * Usage: php artisan two-factor:generate-recovery-codes {user}
 *
 * @package MetaSoftDevs\LaravelBreeze2FA\Console\Commands
 * @author Meta Software Developers <info@metasoftdevs.com>
 * @version 1.0.0
 */
class GenerateRecoveryCodesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'two-factor:generate-recovery-codes {user : The user ID or email} {--count= : Number of codes to generate}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate new two-factor recovery codes for a user.';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $userIdentifier = $this->argument('user');
        $count = $this->option('count') ? (int)$this->option('count') : null;

        // Resolve the user model class from config
        $userModel = Config::get('auth.providers.users.model', \App\Models\User::class);
        /** @var Authenticatable|null $user */
        $user = $userModel::where('id', $userIdentifier)
            ->orWhere('email', $userIdentifier)
            ->first();

        if (!$user) {
            $this->error('User not found: ' . $userIdentifier);
            return 1;
        }

        /** @var RecoveryCodeServiceInterface $recoveryCodeService */
        $recoveryCodeService = App::make(RecoveryCodeServiceInterface::class);

        // Generate new recovery codes
        $codes = $recoveryCodeService->generate($user, $count);

        $this->info('New recovery codes generated for user: ' . ($user->email ?? $user->id));
        $this->line('-----------------------------');
        foreach ($codes as $code) {
            $this->line($code);
        }
        $this->line('-----------------------------');
        $this->comment('Store these codes in a safe place. Each code can only be used once.');

        // TODO: Add options for output format, file export, etc.
        return 0;
    }
}
