<?php

declare(strict_types=1);

namespace Laravel\Passkeys;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Laravel\Passkeys\Contracts\PasskeyConfirmationResponse as PasskeyConfirmationResponseContract;
use Laravel\Passkeys\Contracts\PasskeyDeletedResponse as PasskeyDeletedResponseContract;
use Laravel\Passkeys\Contracts\PasskeyLoginResponse as PasskeyLoginResponseContract;
use Laravel\Passkeys\Contracts\PasskeyRegistrationResponse as PasskeyRegistrationResponseContract;
use Laravel\Passkeys\Http\Controllers\PasskeyLoginController;
use Laravel\Passkeys\Http\Controllers\PasskeyRegistrationController;
use Laravel\Passkeys\Http\Responses\PasskeyConfirmationResponse;
use Laravel\Passkeys\Http\Responses\PasskeyDeletedResponse;
use Laravel\Passkeys\Http\Responses\PasskeyLoginResponse;
use Laravel\Passkeys\Http\Responses\PasskeyRegistrationResponse;

class PasskeysServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/passkeys.php', 'passkeys');

        $this->app->singleton(PasskeyLoginResponseContract::class, PasskeyLoginResponse::class);
        $this->app->singleton(PasskeyConfirmationResponseContract::class, PasskeyConfirmationResponse::class);
        $this->app->singleton(PasskeyRegistrationResponseContract::class, PasskeyRegistrationResponse::class);
        $this->app->singleton(PasskeyDeletedResponseContract::class, PasskeyDeletedResponse::class);
    }

    /**
     * Bootstrap any package services.
     */
    public function boot(): void
    {
        $this->registerPublishing();
        $this->registerRoutes();
        $this->registerRouteBindings();
        $this->registerRouteMacro();
    }

    /**
     * Register the Route::passkeys($guard, $opts) macro for per-guard route registration.
     */
    protected function registerRouteMacro(): void
    {
        Route::macro('passkeys', function (string $guard, array $opts = []) {
            $prefix = $opts['prefix'] ?? '/passkeys';
            $middleware = $opts['middleware']
                ?? config("passkeys.guards.{$guard}.middleware", ['web']);
            $managementMiddleware = $opts['management_middleware']
                ?? config("passkeys.guards.{$guard}.management_middleware", ['password.confirm']);

            Route::prefix($prefix)
                ->middleware($middleware)
                ->group(function () use ($guard, $managementMiddleware): void {
                    // Authentication (login) — no management middleware
                    Route::post('login/options', [
                        PasskeyLoginController::class,
                        'index',
                    ])->name("passkeys.{$guard}.login.options")
                        ->defaults('passkey_guard', $guard);

                    Route::post('login', [
                        PasskeyLoginController::class,
                        'store',
                    ])->name("passkeys.{$guard}.login")
                        ->defaults('passkey_guard', $guard);

                    // Registration + management — gated by management middleware
                    Route::middleware($managementMiddleware)->group(function () use ($guard): void {
                        Route::post('register/options', [
                            PasskeyRegistrationController::class,
                            'index',
                        ])->name("passkeys.{$guard}.register.options")
                            ->defaults('passkey_guard', $guard);

                        Route::post('register', [
                            PasskeyRegistrationController::class,
                            'store',
                        ])->name("passkeys.{$guard}.register")
                            ->defaults('passkey_guard', $guard);

                        Route::delete('{passkey}', [
                            PasskeyRegistrationController::class,
                            'destroy',
                        ])->name("passkeys.{$guard}.destroy")
                            ->defaults('passkey_guard', $guard);
                    });
                });
        });
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

        $this->publishesMigrations([
            Passkeys::migrationPath() => database_path('migrations'),
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

    /**
     * Register the package route bindings.
     */
    protected function registerRouteBindings(): void
    {
        Route::bind('passkey', function (string $value): Passkey {
            $model = Passkeys::passkeyModel();

            $passkey = app($model)->resolveRouteBinding($value);

            if (! $passkey instanceof Passkey) {
                throw (new ModelNotFoundException)->setModel($model, [$value]);
            }

            return $passkey;
        });
    }
}
