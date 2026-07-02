<?php

namespace App\Providers;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Illuminate\Auth\Notifications\ResetPassword;

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
        Schema::defaultStringLength(191);
        Vite::prefetch(concurrency: 3);

        ResetPassword::createUrlUsing(function ($user, string $token) {
            return request()->schemeAndHttpHost() . '/reset-password/' . $token . '?email=' . urlencode($user->email);
        });

        \Laravel\Sanctum\Sanctum::getAccessTokenFromRequestUsing(function ($request) {
            return $request->query('token') ?? $request->bearerToken();
        });
    }
}
