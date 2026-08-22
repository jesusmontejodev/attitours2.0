<?php

namespace App\Providers;

use App\Models\Mensaje;
use App\Services\ExchangeRateService;
use Illuminate\Support\Facades\Auth;
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

        // Badge de mensajes no leídos en el nav (chat interno cliente-proveedor). Se evalúa
        // por request porque depende del usuario autenticado, a diferencia del View::share de arriba.
        View::composer('layouts.app', function ($view) {
            $user = Auth::user();
            if (!$user) {
                return;
            }

            if ($user->isAdmin()) {
                $view->with('mensajesNoLeidosAdmin', Mensaje::where('remitente_tipo', 'cliente')
                    ->where('leido_por_admin', false)
                    ->count());
            } elseif ($user->isCliente()) {
                $view->with('mensajesNoLeidosCliente', Mensaje::where('remitente_tipo', 'admin_como_proveedor')
                    ->where('leido_por_cliente', false)
                    ->whereHas('reserva', function ($q) use ($user) {
                        $q->where('user_id', $user->id)->orWhere('correo_cliente', $user->email);
                    })
                    ->count());
            }
        });
    }
}
