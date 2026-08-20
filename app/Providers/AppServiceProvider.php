<?php

namespace App\Providers;

use App\Services\ExchangeRateService;
use Illuminate\Support\Facades\View;
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
        // Comparte las tasas de cambio USD->MXN/EUR globalmente (incluye componentes Blade
        // anónimos como <x-currency-note>, que no heredan datos de View::composer) para el
        // mensaje de conversión de referencia. El cobro real siempre es en USD.
        // El valor viene cacheado (ver ExchangeRateService), así que esto no golpea la API
        // externa en cada request.
        View::share('exchangeRates', app(ExchangeRateService::class)->rates());
    }
}
