<?php
/**
 * @file LanguageMiddleware.php
 * @description Middleware para interceptar las peticiones y configurar el locale de la aplicación a partir de la sesión.
 * @date 2026-06-08
 * @author Antigravity
 */

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class LanguageMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (session()->has('locale')) {
            $locale = session()->get('locale');
            if (in_array($locale, ['es', 'en', 'zh'])) {
                App::setLocale($locale);
            }
        } else {
            // Establecer español por defecto para Atti Tours si no hay sesión configurada
            App::setLocale('es');
            session()->put('locale', 'es');
        }

        return $next($request);
    }
}
