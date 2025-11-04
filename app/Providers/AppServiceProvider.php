<?php

namespace App\Providers;

use App\Services\Naver\ClovaOcrService;
use App\Services\Naver\ClovaSpeechService;
use App\Services\Naver\NaverMapsService;
use App\Services\Naver\PapagoService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register NAVER Cloud Platform services as singletons
        $this->app->singleton(NaverMapsService::class, function () {
            return new NaverMapsService();
        });

        $this->app->singleton(PapagoService::class, function () {
            return new PapagoService();
        });

        $this->app->singleton(ClovaOcrService::class, function () {
            return new ClovaOcrService();
        });

        $this->app->singleton(ClovaSpeechService::class, function () {
            return new ClovaSpeechService();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
