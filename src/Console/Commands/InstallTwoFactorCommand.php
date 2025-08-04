<?php

declare(strict_types=1);

namespace MetaSoftDevs\LaravelBreeze2FA\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Artisan;

/**
 * Install Two-Factor Authentication Command
 *
 * This command handles the installation and setup of the two-factor
 * authentication package, including publishing assets, running migrations,
 * and setting up configuration.
 *
 * @package MetaSoftDevs\LaravelBreeze2FA\Console\Commands
 * @author Meta Software Developers <info@metasoftdevs.com>
 * @version 1.0.0
 */
class InstallTwoFactorCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'two-factor:install
                            {--force : Overwrite existing files}
                            {--migrate : Run migrations after installation}
                            {--views : Publish views}
                            {--config : Publish configuration}
                            {--lang : Publish language files}
                            {--all : Publish all assets}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install the Two-Factor Authentication package';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $this->info('Installing Two-Factor Authentication package...');
        $this->newLine();

        // Check if this is a fresh install or update
        $isUpdate = $this->checkExistingInstallation();

        if ($isUpdate) {
            $this->warn('Existing installation detected.');

            if (!$this->option('force') && !$this->confirm('Do you want to continue and overwrite existing files?')) {
                $this->info('Installation cancelled.');
                return Command::SUCCESS;
            }
        }

        // Publish assets based on options
        $this->publishAssets();

        // Run migrations if requested
        if ($this->option('migrate') || $this->confirm('Would you like to run the migrations now?')) {
            $this->runMigrations();
        }

        // Display setup instructions
        $this->displaySetupInstructions($isUpdate);

        $this->newLine();
        $this->info('✅ Two-Factor Authentication package installed successfully!');

        return Command::SUCCESS;
    }

    /**
     * Check if there's an existing installation.
     *
     * @return bool
     */
    protected function checkExistingInstallation(): bool
    {
        return File::exists(config_path('two-factor.php')) ||
            File::exists(database_path('migrations/2024_01_01_000001_create_two_factor_auths_table.php'));
    }

    /**
     * Publish package assets.
     *
     * @return void
     */
    protected function publishAssets(): void
    {
        $options = ['--provider' => 'MetaSoftDevs\LaravelBreeze2FA\TwoFactorServiceProvider'];

        if ($this->option('force')) {
            $options['--force'] = true;
        }

        // Publish based on options
        if ($this->option('all')) {
            $this->publishAllAssets($options);
        } else {
            $this->publishSelectiveAssets($options);
        }
    }

    /**
     * Publish all assets.
     *
     * @param array $options
     * @return void
     */
    protected function publishAllAssets(array $options): void
    {
        $this->info('📦 Publishing all assets...');

        $this->call('vendor:publish', array_merge($options, ['--tag' => 'two-factor']));

        $this->line('   ✓ Configuration file');
        $this->line('   ✓ Database migrations');
        $this->line('   ✓ Views');
        $this->line('   ✓ Language files');
        $this->line('   ✓ Public assets');
    }

    /**
     * Publish assets selectively based on options.
     *
     * @param array $options
     * @return void
     */
    protected function publishSelectiveAssets(array $options): void
    {
        // Always publish migrations
        $this->info('📦 Publishing database migrations...');
        $this->call('vendor:publish', array_merge($options, ['--tag' => 'two-factor-migrations']));
        $this->line('   ✓ Database migrations');

        // Publish config if requested or if it doesn't exist
        if ($this->option('config') || !File::exists(config_path('two-factor.php'))) {
            $this->info('📦 Publishing configuration...');
            $this->call('vendor:publish', array_merge($options, ['--tag' => 'two-factor-config']));
            $this->line('   ✓ Configuration file');
        }

        // Publish views if requested
        if ($this->option('views') || $this->confirm('Would you like to publish the views for customization?', false)) {
            $this->info('📦 Publishing views...');
            $this->call('vendor:publish', array_merge($options, ['--tag' => 'two-factor-views']));
            $this->line('   ✓ Views');
        }

        // Publish language files if requested
        if ($this->option('lang') || $this->confirm('Would you like to publish the language files?', false)) {
            $this->info('📦 Publishing language files...');
            $this->call('vendor:publish', array_merge($options, ['--tag' => 'two-factor-lang']));
            $this->line('   ✓ Language files');
        }
    }

    /**
     * Run the package migrations.
     *
     * @return void
     */
    protected function runMigrations(): void
    {
        $this->info('🗄️  Running migrations...');

        try {
            $this->call('migrate', ['--force' => true]);
            $this->line('   ✓ Migrations completed');
        } catch (\Exception $e) {
            $this->error('   ✗ Migration failed: ' . $e->getMessage());
            $this->warn('You can run migrations manually with: php artisan migrate');
        }
    }

    /**
     * Display setup instructions.
     *
     * @param bool $isUpdate
     * @return void
     */
    protected function displaySetupInstructions(bool $isUpdate): void
    {
        $this->newLine();

        if ($isUpdate) {
            $this->info('📝 Update completed! Please review your configuration.');
        } else {
            $this->info('📝 Next steps:');
            $this->newLine();

            $this->line('1. Configure your 2FA settings in config/two-factor.php');
            $this->line('2. For SMS OTP, add your provider credentials to .env:');
            $this->newLine();

            $this->comment('   # Twilio SMS');
            $this->comment('   TWILIO_ACCOUNT_SID=your_account_sid');
            $this->comment('   TWILIO_AUTH_TOKEN=your_auth_token');
            $this->comment('   TWILIO_PHONE_NUMBER=your_phone_number');
            $this->newLine();

            $this->comment('   # Vonage SMS');
            $this->comment('   VONAGE_API_KEY=your_api_key');
            $this->comment('   VONAGE_API_SECRET=your_api_secret');
            $this->comment('   VONAGE_PHONE_NUMBER=your_sender_id');
            $this->newLine();

            $this->line('3. Add the middleware to your routes:');
            $this->comment('   Route::middleware([\'auth\', \'two-factor\'])->group(function () {');
            $this->comment('       // Your protected routes');
            $this->comment('   });');
            $this->newLine();

            $this->line('4. Update your authentication flow to redirect to 2FA challenge:');
            $this->comment('   if (TwoFactor::isEnabledForUser($user)) {');
            $this->comment('       return redirect()->route(\'two-factor.challenge\');');
            $this->comment('   }');
        }

        $this->newLine();
        $this->line('🔗 Useful commands:');
        $this->comment('   php artisan two-factor:cleanup    # Clean up expired sessions');
        $this->comment('   php artisan two-factor:stats      # View 2FA statistics');
        $this->newLine();

        $this->line('📚 Documentation: https://github.com/metasoftdevs/laravel-breeze-2fa');
    }

    /**
     * Get the stub file content.
     *
     * @param string $stub
     * @return string
     */
    protected function getStub(string $stub): string
    {
        return File::get(__DIR__ . "/../../stubs/{$stub}.stub");
    }

    /**
     * Create a file from stub.
     *
     * @param string $stub
     * @param string $destination
     * @param array $replacements
     * @return bool
     */
    protected function createFromStub(string $stub, string $destination, array $replacements = []): bool
    {
        $content = $this->getStub($stub);

        foreach ($replacements as $search => $replace) {
            $content = str_replace($search, $replace, $content);
        }

        $directory = dirname($destination);
        if (!File::isDirectory($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        return File::put($destination, $content) !== false;
    }

    /**
     * Check system requirements.
     *
     * @return bool
     */
    protected function checkRequirements(): bool
    {
        $phpVersion = PHP_VERSION;
        $laravelVersion = app()->version();

        $this->info("🔍 Checking requirements...");
        $this->line("   PHP Version: {$phpVersion}");
        $this->line("   Laravel Version: {$laravelVersion}");

        if (version_compare($phpVersion, '8.1.0', '<')) {
            $this->error('   ✗ PHP 8.1+ is required');
            return false;
        }

        if (version_compare($laravelVersion, '10.0.0', '<')) {
            $this->error('   ✗ Laravel 10.0+ is required');
            return false;
        }

        $this->line('   ✓ Requirements met');
        return true;
    }

    /**
     * Check if specific extensions are loaded.
     *
     * @return void
     */
    protected function checkExtensions(): void
    {
        $this->info('🔍 Checking PHP extensions...');

        $requiredExtensions = ['openssl', 'gd'];
        $recommendedExtensions = ['curl', 'json'];

        foreach ($requiredExtensions as $extension) {
            if (extension_loaded($extension)) {
                $this->line("   ✓ {$extension}");
            } else {
                $this->error("   ✗ {$extension} (required)");
            }
        }

        foreach ($recommendedExtensions as $extension) {
            if (extension_loaded($extension)) {
                $this->line("   ✓ {$extension}");
            } else {
                $this->warn("   ! {$extension} (recommended)");
            }
        }
    }

    /**
     * Test the installation.
     *
     * @return void
     */
    protected function testInstallation(): void
    {
        $this->info('🧪 Testing installation...');

        try {
            // Test configuration loading
            $config = config('two-factor');
            if ($config) {
                $this->line('   ✓ Configuration loaded');
            } else {
                $this->error('   ✗ Configuration not loaded');
            }

            // Test facade
            if (class_exists(\MetaSoftDevs\LaravelBreeze2FA\Facades\TwoFactor::class)) {
                $this->line('   ✓ Facade available');
            } else {
                $this->error('   ✗ Facade not available');
            }

            // Test service provider
            if (app()->bound('two-factor')) {
                $this->line('   ✓ Service provider registered');
            } else {
                $this->error('   ✗ Service provider not registered');
            }
        } catch (\Exception $e) {
            $this->error('   ✗ Installation test failed: ' . $e->getMessage());
        }
    }
}
