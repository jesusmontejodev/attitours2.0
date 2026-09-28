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
        // externa en cada request. Se omite en consola para no depender de la tabla `cache`
        // durante comandos como `migrate` en una base de datos nueva.
        if (!$this->app->runningInConsole()) {
            View::share('exchangeRates', app(ExchangeRateService::class)->rates());
        }

        // Badge de mensajes no leídos en el nav (chat interno cliente-proveedor). Se evalúa
        // por request porque depende del usuario autenticado, a diferencia del View::share de arriba.
        View::composer('layouts.app', function ($view) {
            $user = Auth::user();
            if (!$user) {
                return;
            }

            if ($user->isAdmin()) {
                $view->with('mensajesNoLeidosPanel', Mensaje::where('remitente_tipo', 'cliente')
                    ->where('leido_por_admin', false)
                    ->count());
            } elseif ($user->isProveedor() && $user->proveedor_id) {
                // Mismo criterio de visibilidad que AdminMensajeController: mensajes generales o
                // ligados a un tour del proveedor, en reservas que incluyen tours suyos.
                $proveedorId = $user->proveedor_id;
                $view->with('mensajesNoLeidosPanel', Mensaje::where('remitente_tipo', 'cliente')
                    ->where('leido_por_proveedor', false)
                    ->whereHas('reserva.detalles.tour', fn ($q) => $q->where('proveedor_id', $proveedorId))
                    ->where(fn ($q) => $q->whereNull('reserva_tour_id')
                        ->orWhereHas('reservaTour.tour', fn ($t) => $t->where('proveedor_id', $proveedorId)))
                    ->count());
            } elseif ($user->isCliente()) {
                $view->with('mensajesNoLeidosCliente', Mensaje::whereIn('remitente_tipo', Mensaje::REMITENTES_PROVEEDOR)
                    ->where('leido_por_cliente', false)
                    ->whereHas('reserva', function ($q) use ($user) {
                        $q->where('user_id', $user->id)->orWhere('correo_cliente', $user->email);
                    })
                    ->count());
            }
        });
    }
}
