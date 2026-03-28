<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;

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
            RateLimiter::for('login', function ($request) {
                return Limit::perMinute(5)->by($request->ip());
            });

            RateLimiter::for('verify', function ($request) {
                return Limit::perMinute(3)->by(optional($request->email) ?: $request->ip());
            });


            RateLimiter::for('status', function ($request) {
                return Limit::perMinute(1)->by($request->user()->id);
            });
             RateLimiter::for('change-sensitive-info', function ($request) {
                return Limit::perMinute(3)->by($request->user()->id);
            });
             RateLimiter::for('profile', function ($request) {
                return Limit::perMinute(20)->by($request->user()->id);
            });
            RateLimiter::for('change-info', function ($request) {
                return Limit::perMinute(5)->by($request->user()->id);
            });

    }
}
