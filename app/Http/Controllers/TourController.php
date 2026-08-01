<?php
/**
 * @file TourController.php
 * @description Controlador para gestionar la visualización de tours y verificación de disponibilidad, adaptado para soportar calendarios independientes en las modalidades compartida y privada.
 * @date 2026-07-31
 * @author Antigravity
 */

namespace App\Http\Controllers;

use App\Models\Tour;
use App\Models\TourFecha;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TourController extends Controller
{
    /**
     * Muestra la página de detalles de un tour específico.
     *
     * @param string $id
     * @return View
     */
    public function show(string $id): View
    {
        $tour = Tour::with(['proveedor'])->findOrFail($id);
        
        // Obtener todas las fechas de salida disponibles (tanto compartidas como privadas)
        $fechas = TourFecha::where('tour_id', $id)
            ->whereColumn('cupo_reservado', '<', 'cupo_maximo')
            ->orderBy('fecha', 'asc')
            ->get();

        return view('tours.show', compact('tour', 'fechas'));
    }

    /**
     * Verifica la disponibilidad de cupo para un tour y fecha específicos a través de una petición AJAX.
     *
     * @param Request $request
     * @param string $id
     * @return JsonResponse
     */
    public function checkAvailability(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'fecha' => 'required|date_format:Y-m-d',
            'es_privado' => 'nullable|boolean'
        ]);

        $fechaStr = $request->input('fecha');
        $esPrivado = (bool) $request->input('es_privado', false);

        $salidas = TourFecha::where('tour_id', $id)
            ->where('fecha', $fechaStr)
            ->where('es_privado', $esPrivado)
            ->get();

        if ($salidas->isEmpty()) {
            return response()->json([
                'available' => false,
                'horarios' => [],
                'message' => __('No hay salidas programadas para esta fecha.')
            ]);
        }

        $horariosDisponibles = [];
        $hayDisponibilidadGeneral = false;

        foreach ($salidas as $salida) {
            $cupoDisp = $salida->cupo_disponible;
            if ($cupoDisp > 0) {
                $hayDisponibilidadGeneral = true;
            }
            $horariosDisponibles[] = [
                'horario' => $salida->horario,
                'cupo_disponible' => $cupoDisp,
                'cupo_maximo' => $salida->cupo_maximo,
                'cupo_reservado' => $salida->cupo_reservado
            ];
        }

        return response()->json([
            'available' => $hayDisponibilidadGeneral,
            'horarios' => $horariosDisponibles
        ]);
    }
}
