<?php
/**
 * @file HomeController.php
 * @description Controlador para la landing page (Home) y la sección de catálogo de tours con filtros interactivos.
 * @date 2026-06-08
 * @author Antigravity
 */

namespace App\Http\Controllers;

use App\Models\Tour;
use App\Models\TourFecha;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HomeController extends Controller
{
    /**
     * Muestra la página de inicio (Landing Page).
     *
     * @return View
     */
    public function index(): View
    {
        // Obtener los tours para mostrarlos en la sección destacada
        $tours = Tour::all();

        // Destinos populares (ubicaciones únicas)
        $destinos = Tour::select('ubicacion')->distinct()->pluck('ubicacion');

        // Tarjetas de "Explora Destinos": una por ubicación única, usando el tour
        // más antiguo de cada una como representante (imagen y país).
        $destinosDestacados = Tour::query()
            ->select('ubicacion', 'pais', 'imagen_destacada')
            ->orderBy('created_at')
            ->get()
            ->unique('ubicacion')
            ->take(8)
            ->values();

        return view('home', compact('tours', 'destinos', 'destinosDestacados'));
    }

    /**
     * Muestra la página de catálogo de tours con soporte para filtros de búsqueda.
     *
     * @param Request $request
     * @return View
     */
    public function catalog(Request $request): View
    {
        $query = Tour::query();

        // 1. Filtrar por término de búsqueda (búsqueda en campos JSON traducidos)
        if ($request->filled('search')) {
            $searchTerm = $request->input('search');
            $locale = app()->getLocale();
            
            $query->where(function ($q) use ($searchTerm, $locale) {
                $q->where('titulo->' . $locale, 'like', '%' . $searchTerm . '%')
                  ->orWhere('descripcion_corta->' . $locale, 'like', '%' . $searchTerm . '%')
                  ->orWhere('ubicacion', 'like', '%' . $searchTerm . '%');
            });
        }

        // 2. Filtrar por destino / ubicación
        if ($request->filled('location') && $request->input('location') !== 'all') {
            $query->where('ubicacion', $request->input('location'));
        }

        // 3. Filtrar por precio máximo
        if ($request->filled('price')) {
            $query->where('precio_base_usd', '<=', (float) $request->input('price'));
        }

        // 4. Filtrar por fecha disponible (con soporte para flexibilidad al estilo Airbnb)
        if ($request->filled('date')) {
            $date = $request->input('date');
            $flexibility = (int) $request->input('flexibility', 0);
            
            if ($flexibility > 0) {
                // Rango flexible: [date - flexibility, date + flexibility]
                $startDate = \Carbon\Carbon::parse($date)->subDays($flexibility)->toDateString();
                $endDate = \Carbon\Carbon::parse($date)->addDays($flexibility)->toDateString();
                
                $tourIdsConFecha = TourFecha::whereBetween('fecha', [$startDate, $endDate])
                    ->whereColumn('cupo_reservado', '<', 'cupo_maximo')
                    ->pluck('tour_id');
            } else {
                // Fecha exacta
                $tourIdsConFecha = TourFecha::where('fecha', $date)
                    ->whereColumn('cupo_reservado', '<', 'cupo_maximo')
                    ->pluck('tour_id');
            }

            $query->whereIn('id', $tourIdsConFecha);
        } elseif ($request->filled('flexible_months')) {
            // Filtrar por meses flexibles (ej: 2026-07)
            $months = array_filter(explode(',', $request->input('flexible_months')));
            
            if (!empty($months)) {
                $tourIdsConFecha = TourFecha::where(function ($q) use ($months) {
                    foreach ($months as $m) {
                        $q->orWhere('fecha', 'like', $m . '-%');
                    }
                })
                ->whereColumn('cupo_reservado', '<', 'cupo_maximo')
                ->pluck('tour_id');
                
                $query->whereIn('id', $tourIdsConFecha);
            }
        }

        $tours = $query->get();

        // Obtener destinos y rangos para filtros dinámicos en la vista
        $destinos = Tour::select('ubicacion')->distinct()->pluck('ubicacion');
        $precioMaximo = Tour::max('precio_base_usd') ?: 200;

        return view('catalog', compact('tours', 'destinos', 'precioMaximo'));
    }
}
