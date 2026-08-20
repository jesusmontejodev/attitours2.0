<?php
/**
 * @file TourApiProxyController.php
 * @description Rutas públicas "proxy" hacia la API externa (Unique) para el checkout de tours con origen = api_externa: idiomas, hoteles y hora de pickup. El navegador nunca llama a Unique directamente ni ve sus credenciales — siempre pasa por aquí, y la conexión se resuelve a partir del Tour, no de datos enviados por el cliente.
 * @date 2026-08-06
 * @author Claude
 */

namespace App\Http\Controllers;

use App\Models\Tour;
use App\Services\UniqueApiClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class TourApiProxyController extends Controller
{
    /**
     * Lista los idiomas disponibles para reservar el tour indicado.
     */
    public function idiomas(Request $request): JsonResponse
    {
        $tour = $this->resolverTourApiExterna($request->query('tour_id'));
        if (!$tour) {
            return response()->json(['idiomas' => []]);
        }

        try {
            $client = new UniqueApiClient($tour->apiConexion);

            return response()->json(['idiomas' => $client->idiomas()]);
        } catch (Throwable $e) {
            return response()->json(['idiomas' => [], 'error' => $e->getMessage()], 502);
        }
    }

    /**
     * Lista los hoteles (y lobbies) disponibles para pickup.
     */
    public function hoteles(Request $request): JsonResponse
    {
        $tour = $this->resolverTourApiExterna($request->query('tour_id'));
        if (!$tour) {
            return response()->json(['hoteles' => []]);
        }

        try {
            $client = new UniqueApiClient($tour->apiConexion);

            return response()->json(['hoteles' => $client->hoteles()]);
        } catch (Throwable $e) {
            return response()->json(['hoteles' => [], 'error' => $e->getMessage()], 502);
        }
    }

    /**
     * Consulta la hora estimada de recogida para el hotel/horario elegidos.
     */
    public function pickup(Request $request): JsonResponse
    {
        $request->validate([
            'tour_id' => 'required|string',
            'hotel' => 'required|string',
            'horario' => 'required|string',
            'lobby' => 'nullable|string',
        ]);

        $tour = $this->resolverTourApiExterna($request->input('tour_id'));
        if (!$tour || !$tour->api_locacion_id || !$tour->api_tour_id_externo) {
            return response()->json(['pickup' => null]);
        }

        try {
            $client = new UniqueApiClient($tour->apiConexion);
            $resultado = $client->pickup(
                $tour->api_locacion_id,
                $tour->api_tour_id_externo,
                $request->input('hotel'),
                $request->input('horario'),
                $request->input('lobby') ?: null
            );

            // Unique a veces responde un objeto y a veces una lista de un elemento; se normaliza.
            $primero = $resultado[0] ?? (array_key_exists('hora', $resultado) ? $resultado : null);

            return response()->json(['pickup' => $primero]);
        } catch (Throwable $e) {
            return response()->json(['pickup' => null, 'error' => $e->getMessage()], 502);
        }
    }

    /**
     * Resuelve el Tour solo si existe y proviene de una API externa con conexión activa;
     * en cualquier otro caso devuelve null para que el llamador responda vacío, nunca un error.
     */
    private function resolverTourApiExterna(?string $tourId): ?Tour
    {
        if (!$tourId) {
            return null;
        }

        $tour = Tour::with('apiConexion')->find($tourId);
        if (!$tour || !$tour->esApiExterna() || !$tour->apiConexion) {
            return null;
        }

        return $tour;
    }
}
