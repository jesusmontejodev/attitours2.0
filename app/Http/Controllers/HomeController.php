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

        return view('home', compact('tours', 'destinos'));
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

        // 4. Filtrar por fecha disponible
        if ($request->filled('date')) {
            $date = $request->input('date');
            
            // Buscar los IDs de los tours que tienen esa fecha con cupos libres
            $tourIdsConFecha = TourFecha::where('fecha', $date)
                ->whereColumn('cupo_reservado', '<', 'cupo_maximo')
                ->pluck('tour_id');

            $query->whereIn('id', $tourIdsConFecha);
        }

        $tours = $query->get();

        // Obtener destinos y rangos para filtros dinámicos en la vista
        $destinos = Tour::select('ubicacion')->distinct()->pluck('ubicacion');
        $precioMaximo = Tour::max('precio_base_usd') ?: 200;

        return view('catalog', compact('tours', 'destinos', 'precioMaximo'));
    }
}
