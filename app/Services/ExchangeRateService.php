<?php
/**
 * @file ExchangeRateService.php
 * @description Obtiene y cachea los tipos de cambio de USD a MXN/EUR para mostrar al cliente un
 * precio de referencia en su moneda junto al precio real, que siempre se cobra en USD vía Stripe.
 */

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ExchangeRateService
{
    private const CACHE_KEY = 'exchange_rates_usd';
    private const CACHE_KEY_FALLBACK = 'exchange_rates_usd_last_known';

    /**
     * Tipos de cambio aproximados usados únicamente si la API externa falla y nunca hubo
     * una respuesta previa cacheada (primer arranque de la app sin conexión).
     */
    private const TASAS_RESPALDO = [
        'MXN' => 18.5,
        'EUR' => 0.92,
    ];

    /**
     * Devuelve las tasas de cambio de 1 USD a MXN y EUR, cacheadas por unas horas.
     *
     * @return array{MXN: float, EUR: float}
     */
    public function rates(): array
    {
        return Cache::remember(self::CACHE_KEY, now()->addHours((int) config('services.exchange_rate.cache_ttl_horas', 6)), function () {
            return $this->fetchRates();
        });
    }

    private function fetchRates(): array
    {
        try {
            $response = Http::timeout(5)->get(config('services.exchange_rate.url'));

            if ($response->successful()) {
                $data = $response->json('rates', []);

                if (isset($data['MXN'], $data['EUR'])) {
                    $rates = [
                        'MXN' => (float) $data['MXN'],
                        'EUR' => (float) $data['EUR'],
                    ];

                    Cache::forever(self::CACHE_KEY_FALLBACK, $rates);

                    return $rates;
                }
            }
        } catch (\Throwable $e) {
            Log::warning('ExchangeRateService: no se pudo obtener el tipo de cambio: ' . $e->getMessage());
        }

        return Cache::get(self::CACHE_KEY_FALLBACK, self::TASAS_RESPALDO);
    }
}
