<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

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
        // Automatically force HTTPS only when accessed via an HTTPS tunnel/proxy or when APP_URL is https
        if (
            str_starts_with(config('app.url'), 'https://') ||
            (request()->header('x-forwarded-proto') === 'https') ||
            request()->isSecure()
        ) {
            URL::forceScheme('https');
        }

        // Rate limiter to prevent message spamming (60 messages per minute per user)
        RateLimiter::for('messages', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });
    }
}
