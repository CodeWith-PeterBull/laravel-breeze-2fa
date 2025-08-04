<?php

declare(strict_types=1);

namespace MetaSoftDevs\LaravelBreeze2FA;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Application;
use Illuminate\Contracts\Container\BindingResolutionException;
use MetaSoftDevs\LaravelBreeze2FA\Services\TwoFactorManager;
use MetaSoftDevs\LaravelBreeze2FA\Services\TOTPService;
use MetaSoftDevs\LaravelBreeze2FA\Services\EmailOTPService;
use MetaSoftDevs\LaravelBreeze2FA\Services\SMSOTPService;
use MetaSoftDevs\LaravelBreeze2FA\Services\RecoveryCodeService;
use MetaSoftDevs\LaravelBreeze2FA\Services\DeviceRememberService;
use MetaSoftDevs\LaravelBreeze2FA\Contracts\TwoFactorManagerInterface;
use MetaSoftDevs\LaravelBreeze2FA\Contracts\TOTPServiceInterface;
use MetaSoftDevs\LaravelBreeze2FA\Contracts\EmailOTPServiceInterface;
use MetaSoftDevs\LaravelBreeze2FA\Contracts\SMSOTPServiceInterface;
use MetaSoftDevs\LaravelBreeze2FA\Contracts\RecoveryCodeServiceInterface;
use MetaSoftDevs\LaravelBreeze2FA\Contracts\DeviceRememberServiceInterface;
use MetaSoftDevs\LaravelBreeze2FA\Console\Commands\InstallTwoFactorCommand;
use MetaSoftDevs\LaravelBreeze2FA\Console\Commands\GenerateRecoveryCodesCommand;
use MetaSoftDevs\LaravelBreeze2FA\Console\Commands\CleanupExpiredSessionsCommand;

/**
 * Two-Factor Authentication Service Provider
 *
 * This service provider handles the registration and bootstrapping of all
 * two-factor authentication services, middleware, routes, and publishable assets.
 *
 * @package MetaSoftDevs\LaravelBreeze2FA
 * @author Meta Software Developers <info@metasoftdevs.com>
 * @version 1.0.0
 */
class TwoFactorServiceProvider extends ServiceProvider
{
    /**
     * All of the container singletons that should be registered.
     *
     * @var array<string, string>
     */
    public array $singletons = [
        TwoFactorManagerInterface::class => TwoFactorManager::class,
        TOTPServiceInterface::class => TOTPService::class,
        EmailOTPServiceInterface::class => EmailOTPService::class,
        SMSOTPServiceInterface::class => SMSOTPService::class,
        RecoveryCodeServiceInterface::class => RecoveryCodeService::class,
        DeviceRememberServiceInterface::class => DeviceRememberService::class,
    ];

    /**
     * Bootstrap any package services.
     *
     * This method is called after all other service providers have been registered,
     * meaning you have access to all other services that have been registered.
     *
     * @return void
     */
    public function boot(): void
    {
        $this->bootConfiguration();
        $this->bootMigrations();
        $this->bootViews();
        $this->bootTranslations();
        $this->bootRoutes();
        $this->bootCommands();
        $this->bootPublishing();
    }

    /**
     * Register any package services.
     *
     * This method is used to register services with the container. All services
     * that need to be available to the application should be registered here.
     *
     * @return void
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/two-factor.php',
            'two-factor'
        );

        $this->registerServices();
        $this->registerMiddleware();
    }

    /**
     * Bootstrap the package configuration.
     *
     * @return void
     */
    protected function bootConfiguration(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/two-factor.php' => config_path('two-factor.php'),
            ], 'two-factor-config');
        }
    }

    /**
     * Bootstrap the package migrations.
     *
     * @return void
     */
    protected function bootMigrations(): void
    {
        if ($this->app->runningInConsole()) {
            $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

            $this->publishes([
                __DIR__ . '/../database/migrations' => database_path('migrations'),
            ], 'two-factor-migrations');
        }
    }

    /**
     * Bootstrap the package views.
     *
     * @return void
     */
    protected function bootViews(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'two-factor');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../resources/views' => resource_path('views/vendor/two-factor'),
            ], 'two-factor-views');
        }
    }

    /**
     * Bootstrap the package translations.
     *
     * @return void
     */
    protected function bootTranslations(): void
    {
        $this->loadTranslationsFrom(__DIR__ . '/../resources/lang', 'two-factor');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../resources/lang' => $this->app->langPath('vendor/two-factor'),
            ], 'two-factor-lang');
        }
    }

    /**
     * Bootstrap the package routes.
     *
     * @return void
     */
    protected function bootRoutes(): void
    {
        if (! $this->app->routesAreCached()) {
            $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
        }
    }

    /**
     * Bootstrap the package commands.
     *
     * @return void
     */
    protected function bootCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallTwoFactorCommand::class,
                GenerateRecoveryCodesCommand::class,
                CleanupExpiredSessionsCommand::class,
            ]);
        }
    }

    /**
     * Bootstrap publishing of package assets.
     *
     * @return void
     */
    protected function bootPublishing(): void
    {
        if ($this->app->runningInConsole()) {
            // Publish all package assets at once
            $this->publishes([
                __DIR__ . '/../config/two-factor.php' => config_path('two-factor.php'),
                __DIR__ . '/../database/migrations' => database_path('migrations'),
                __DIR__ . '/../resources/views' => resource_path('views/vendor/two-factor'),
                __DIR__ . '/../resources/lang' => $this->app->langPath('vendor/two-factor'),
                __DIR__ . '/../resources/js' => resource_path('js/vendor/two-factor'),
                __DIR__ . '/../public' => public_path('vendor/two-factor'),
            ], 'two-factor');

            // Publish stubs for customization
            $this->publishes([
                __DIR__ . '/../stubs' => base_path('stubs/two-factor'),
            ], 'two-factor-stubs');
        }
    }

    /**
     * Register package services with the container.
     *
     * @return void
     */
    protected function registerServices(): void
    {
        // The singletons array will handle the main service registrations
        // Here we can register any additional services or factory patterns

        $this->app->singleton('two-factor', function (Application $app): TwoFactorManagerInterface {
            return $app->make(TwoFactorManagerInterface::class);
        });
    }

    /**
     * Register package middleware.
     *
     * @return void
     */
    protected function registerMiddleware(): void
    {
        $router = $this->app['router'];

        // Register middleware aliases
        $router->aliasMiddleware('two-factor', \MetaSoftDevs\LaravelBreeze2FA\Http\Middleware\RequiresTwoFactor::class);
        $router->aliasMiddleware('two-factor.setup', \MetaSoftDevs\LaravelBreeze2FA\Http\Middleware\TwoFactorSetup::class);
        $router->aliasMiddleware('two-factor.rate-limit', \MetaSoftDevs\LaravelBreeze2FA\Http\Middleware\RateLimitTwoFactor::class);

        // Register middleware groups
        $router->middlewareGroup('two-factor.challenge', [
            'two-factor.rate-limit',
            'two-factor',
        ]);
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array<int, string>
     */
    public function provides(): array
    {
        return [
            'two-factor',
            TwoFactorManagerInterface::class,
            TOTPServiceInterface::class,
            EmailOTPServiceInterface::class,
            SMSOTPServiceInterface::class,
            RecoveryCodeServiceInterface::class,
            DeviceRememberServiceInterface::class,
        ];
    }
}
