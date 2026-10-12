<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
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
        RateLimiter::for('login', fn (Request $request): Limit => Limit::perMinute(5)
            ->by($this->authenticationKey($request)));

        RateLimiter::for('registration', fn (Request $request): Limit => Limit::perMinute(5)
            ->by($request->ip()));

        RateLimiter::for('password-reset-link', fn (Request $request): Limit => Limit::perMinute(3)
            ->by($this->authenticationKey($request)));

        RateLimiter::for('password-reset', fn (Request $request): Limit => Limit::perMinute(5)
            ->by($this->authenticationKey($request)));
    }

    private function authenticationKey(Request $request): string
    {
        return Str::transliterate(
            Str::lower($request->string('email')->toString()).'|'.$request->ip()
        );
    }
}
