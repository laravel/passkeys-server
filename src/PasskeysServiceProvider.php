<?php

declare(strict_types=1);

namespace Laravel\Passkeys;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Laravel\Passkeys\Contracts\PasskeyDeletedResponse as PasskeyDeletedResponseContract;
use Laravel\Passkeys\Contracts\PasskeyRegistrationResponse as PasskeyRegistrationResponseContract;
use Laravel\Passkeys\Contracts\PasskeyVerificationResponse as PasskeyVerificationResponseContract;
use Laravel\Passkeys\Http\Responses\PasskeyDeletedResponse;
use Laravel\Passkeys\Http\Responses\PasskeyRegistrationResponse;
use Laravel\Passkeys\Http\Responses\PasskeyVerificationResponse;

class PasskeysServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/passkeys.php', 'passkeys');

        $this->app->singleton(PasskeyRegistrationResponseContract::class, PasskeyRegistrationResponse::class);
        $this->app->singleton(PasskeyDeletedResponseContract::class, PasskeyDeletedResponse::class);
        $this->app->singleton(PasskeyVerificationResponseContract::class, PasskeyVerificationResponse::class);
    }

    /**
     * Bootstrap any package services.
     */
    public function boot(): void
    {
        $this->registerPublishing();
        $this->registerRoutes();

        Route::model('passkey', Passkeys::passkeyModel());
    }

    /**
     * Register the package's publishable resources.
     */
    protected function registerPublishing(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->publishes([
            __DIR__.'/../config/passkeys.php' => config_path('passkeys.php'),
        ], 'passkeys-config');

        $this->publishes([
            __DIR__.'/../database/migrations/2024_01_01_000000_create_passkeys_table.php' => database_path('migrations/2024_01_01_000000_create_passkeys_table.php'),
        ], 'passkeys-migrations');
    }

    /**
     * Register the package routes.
     */
    protected function registerRoutes(): void
    {
        if (Passkeys::shouldRegisterRoutes()) {
            $this->loadRoutesFrom(__DIR__.'/../routes/routes.php');
        }
    }
}
