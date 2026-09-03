<?php

namespace App\Providers;

use App\Models\User;
use App\Modules\Users\Policies\UserPolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(User::class, UserPolicy::class);

        RateLimiter::for('login', function (Request $request) {
            $key = Str::lower((string) $request->input('email')).'|'.$request->ip();

            return Limit::perMinutes(
                config('auth.throttle.login.decay_minutes'),
                config('auth.throttle.login.max_attempts'),
            )->by($key);
        });

        RateLimiter::for('password-reset', function (Request $request) {
            $key = Str::lower((string) $request->input('email')).'|'.$request->ip();

            return Limit::perMinutes(
                config('auth.throttle.password_reset.decay_minutes'),
                config('auth.throttle.password_reset.max_attempts'),
            )->by($key);
        });
    }
}
