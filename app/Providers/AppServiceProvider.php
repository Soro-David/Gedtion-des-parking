<?php

namespace App\Providers;

use App\Services\Contracts\OcrProviderInterface;
use App\Services\Contracts\SmsProviderInterface;
use App\Services\PlateRecognizerService;
use App\Services\Sms\LogSmsProvider;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\Sanctum;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(OcrProviderInterface::class, function ($app) {
            $driver = config('services.ocr.driver', 'platerecognizer');

            return match ($driver) {
                'platerecognizer' => $app->make(PlateRecognizerService::class),
                // Possibilité de mapper d'autres services (e.g. Gemini/Claude) à l'avenir
                default => $app->make(PlateRecognizerService::class),
            };
        });

        $this->app->bind(SmsProviderInterface::class, function ($app) {
            $driver = config('services.sms.driver', 'log');

            return match ($driver) {
                'log' => $app->make(LogSmsProvider::class),
                default => $app->make(LogSmsProvider::class),
            };
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Schema::defaultStringLength(191);
        Vite::prefetch(concurrency: 3);

        ResetPassword::createUrlUsing(function ($user, string $token) {
            return request()->schemeAndHttpHost().'/reset-password/'.$token.'?email='.urlencode($user->email);
        });

        Sanctum::getAccessTokenFromRequestUsing(function ($request) {
            return $request->query('token') ?? $request->bearerToken();
        });
    }
}
