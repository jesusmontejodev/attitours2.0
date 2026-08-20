<?php
/**
 * @file ApiConexionController.php
 * @description Controlador para gestionar las conexiones a APIs externas de terceros (ej. Unique Reservaciones) desde el panel de administración: CRUD de conexiones, sincronización de catálogo, y aprobación/rechazo de cambios de precio detectados.
 * @date 2026-08-06
 * @author Claude
 */

namespace App\Http\Controllers;

use App\Jobs\NotificarReservaApiExternaJob;
use App\Models\ApiConexion;
use App\Models\TourApiNotificacion;
use App\Models\TourCambioPrecioApi;
use App\Models\TourImportado;
use App\Services\TourCatalogSyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Throwable;

class ApiConexionController extends Controller
{
    /**
     * Registra una nueva Conexión API.
     */
    public function store(Request $request): RedirectResponse
    {
        $user = Auth::user();
        if (!$user || !$user->isAdmin()) {
            return redirect()->route('home')->with('error', __('Acceso no autorizado.'));
        }

        $request->validate([
            'nombre' => 'required|string|max:150',
            'proveedor_id' => 'nullable|exists:proveedores,id',
            'base_url' => 'required|url|max:255',
            'usuario' => 'required|string|max:150',
            'password' => 'required|string|max:255',
            'uniqid_empresa' => 'required|string|max:150',
            'modo' => ['required', Rule::in($this->modosPermitidos())],
            'activa' => 'nullable|boolean',
        ]);

        ApiConexion::create([
            'nombre' => $request->input('nombre'),
            'proveedor_id' => $request->input('proveedor_id') ?: null,
            'base_url' => rtrim($request->input('base_url'), '/'),
            'usuario' => $request->input('usuario'),
            'password' => $request->input('password'),
            'uniqid_empresa' => $request->input('uniqid_empresa'),
            'modo' => $request->input('modo'),
            'activa' => $request->boolean('activa', true),
        ]);

        return redirect()->route('dashboard')
            ->with('success', __('Conexión API creada correctamente.'))
            ->with('active_tab', 'apis');
    }

    /**
     * Actualiza una Conexión API existente. La contraseña solo se sobrescribe si se envía una nueva.
     */
    public function update(Request $request, int $id): RedirectResponse
    {
        $user = Auth::user();
        if (!$user || !$user->isAdmin()) {
            return redirect()->route('home')->with('error', __('Acceso no autorizado.'));
        }

        $conexion = ApiConexion::find($id);
        if (!$conexion) {
            return redirect()->route('dashboard')->with('error', __('Conexión API no encontrada.'));
        }

        $request->validate([
            'nombre' => 'required|string|max:150',
            'proveedor_id' => 'nullable|exists:proveedores,id',
            'base_url' => 'required|url|max:255',
            'usuario' => 'required|string|max:150',
            'password' => 'nullable|string|max:255',
            'uniqid_empresa' => 'required|string|max:150',
            'modo' => ['required', Rule::in($this->modosPermitidos())],
            'activa' => 'nullable|boolean',
        ]);

        $datos = [
            'nombre' => $request->input('nombre'),
            'proveedor_id' => $request->input('proveedor_id') ?: null,
            'base_url' => rtrim($request->input('base_url'), '/'),
            'usuario' => $request->input('usuario'),
            'uniqid_empresa' => $request->input('uniqid_empresa'),
            'modo' => $request->input('modo'),
            'activa' => $request->boolean('activa', true),
        ];

        if ($request->filled('password')) {
            $datos['password'] = $request->input('password');
        }

        $conexion->update($datos);

        return redirect()->route('dashboard')
            ->with('success', __('Conexión API actualizada correctamente.'))
            ->with('active_tab', 'apis');
    }

    /**
     * Elimina una Conexión API. Se bloquea si ya tiene tours publicados asociados,
     * para no dejar tours huérfanos de su origen (se pide desvincular manualmente primero).
     */
    public function destroy(int $id): JsonResponse
    {
        $user = Auth::user();
        if (!$user || !$user->isAdmin()) {
            return response()->json(['success' => false, 'message' => 'No autorizado.'], 403);
        }

        $conexion = ApiConexion::find($id);
        if (!$conexion) {
            return response()->json(['success' => false, 'message' => 'Conexión API no encontrada.'], 404);
        }

        $toursPublicados = $conexion->tours()->count();
        if ($toursPublicados > 0) {
            return response()->json([
                'success' => false,
                'message' => "No se puede eliminar la conexión porque tiene {$toursPublicados} tour(s) publicado(s) asociado(s)."
            ], 422);
        }

        $conexion->toursImportados()->delete();
        $conexion->delete();

        return response()->json(['success' => true, 'message' => 'Conexión API eliminada correctamente.']);
    }

    /**
     * Dispara la sincronización de catálogo contra la API externa: trae tours nuevos a la
     * bandeja de importados y detecta cambios de precio en tours ya publicados (sin aplicarlos).
     */
    public function sync(int $id): RedirectResponse
    {
        $user = Auth::user();
        if (!$user || !$user->isAdmin()) {
            return redirect()->route('home')->with('error', __('Acceso no autorizado.'));
        }

        $conexion = ApiConexion::find($id);
        if (!$conexion) {
            return redirect()->route('dashboard')->with('error', __('Conexión API no encontrada.'));
        }

        try {
            $resumen = app(TourCatalogSyncService::class)->sync($conexion);

            $mensaje = "Catálogo actualizado: {$resumen['nuevos']} tour(s) nuevo(s), {$resumen['actualizados']} actualizado(s).";
            if ($resumen['cambios_precio_detectados'] > 0) {
                $mensaje .= " {$resumen['cambios_precio_detectados']} cambio(s) de precio detectado(s), pendiente(s) de tu aprobación.";
            }
            if (!empty($resumen['errores'])) {
                $mensaje .= ' Hubo errores en algunas locaciones: ' . implode(' | ', $resumen['errores']);
            }

            return redirect()->route('dashboard')->with('success', $mensaje)->with('active_tab', 'apis');
        } catch (Throwable $e) {
            return redirect()->route('dashboard')
                ->with('error', __('Error al sincronizar el catálogo: ') . $e->getMessage())
                ->with('active_tab', 'apis');
        }
    }

    /**
     * Devuelve los datos de un tour importado para precargar el modal de "Completar y Publicar".
     */
    public function showImportado(int $id): JsonResponse
    {
        $user = Auth::user();
        if (!$user || !$user->isAdmin()) {
            return response()->json(['success' => false, 'message' => 'No autorizado.'], 403);
        }

        $importado = TourImportado::with('apiConexion')->find($id);
        if (!$importado) {
            return response()->json(['success' => false, 'message' => 'Tour importado no encontrado.'], 404);
        }

        // No usamos imágenes de la API si el valor no es una URL válida (se ha visto basura como
        // "TAC" en el campo "imagen" del catálogo de Unique) — no se corrige el dato, solo no se
        // ofrece como imagen destacada por defecto; el Admin puede subir la suya.
        $imagenValida = $importado->imagen_preview && filter_var($importado->imagen_preview, FILTER_VALIDATE_URL);

        return response()->json([
            'success' => true,
            'importado' => [
                'id' => $importado->id,
                'estado' => $importado->estado,
                'titulo_preview' => $importado->titulo_preview,
                'descripcion_corta_preview' => $importado->descripcion_corta_preview,
                'descripcion_larga_preview' => $importado->descripcion_larga_preview,
                'precio_preview' => $importado->precio_preview,
                'imagen_preview' => $imagenValida ? $importado->imagen_preview : null,
                'locacion_externa_id' => $importado->locacion_externa_id,
                'proveedor_id' => $importado->apiConexion->proveedor_id,
                'conexion_nombre' => $importado->apiConexion->nombre,
            ],
        ]);
    }

    /**
     * Descarta un tour importado (deja de aparecer en la bandeja de pendientes).
     */
    public function descartarImportado(int $id): JsonResponse
    {
        $user = Auth::user();
        if (!$user || !$user->isAdmin()) {
            return response()->json(['success' => false, 'message' => 'No autorizado.'], 403);
        }

        $importado = TourImportado::find($id);
        if (!$importado) {
            return response()->json(['success' => false, 'message' => 'Tour importado no encontrado.'], 404);
        }

        $importado->update(['estado' => 'descartado']);

        return response()->json(['success' => true, 'message' => 'Tour descartado correctamente.']);
    }

    /**
     * Aprueba un cambio de precio detectado: actualiza el precio de referencia del Tour.
     * Nunca modifica el precio de venta al cliente (precio_base_usd) automáticamente.
     */
    public function aprobarCambioPrecio(int $id): JsonResponse
    {
        $user = Auth::user();
        if (!$user || !$user->isAdmin()) {
            return response()->json(['success' => false, 'message' => 'No autorizado.'], 403);
        }

        $cambio = TourCambioPrecioApi::with('tour')->find($id);
        if (!$cambio || $cambio->estado !== 'pendiente') {
            return response()->json(['success' => false, 'message' => 'Cambio de precio no encontrado o ya resuelto.'], 404);
        }

        DB::transaction(function () use ($cambio, $user) {
            $cambio->tour->update([
                'precio_api_referencia_usd' => $cambio->precio_referencia_nuevo,
                'precio_api_actualizado_at' => now(),
            ]);

            $cambio->update([
                'estado' => 'aprobado',
                'resuelto_por_user_id' => $user->id,
                'resuelto_at' => now(),
            ]);
        });

        return response()->json(['success' => true, 'message' => 'Cambio de precio aprobado y aplicado al precio de referencia.']);
    }

    /**
     * Rechaza un cambio de precio detectado: no se modifica nada en el Tour.
     */
    public function rechazarCambioPrecio(int $id): JsonResponse
    {
        $user = Auth::user();
        if (!$user || !$user->isAdmin()) {
            return response()->json(['success' => false, 'message' => 'No autorizado.'], 403);
        }

        $cambio = TourCambioPrecioApi::find($id);
        if (!$cambio || $cambio->estado !== 'pendiente') {
            return response()->json(['success' => false, 'message' => 'Cambio de precio no encontrado o ya resuelto.'], 404);
        }

        $cambio->update([
            'estado' => 'rechazado',
            'resuelto_por_user_id' => $user->id,
            'resuelto_at' => now(),
        ]);

        return response()->json(['success' => true, 'message' => 'Cambio de precio rechazado.']);
    }

    /**
     * Reintenta manualmente una notificación fallida a la API externa (ej. un "SOLD OUT" real
     * que el Admin quiere reintentar tras liberar cupo del lado de Unique, o un error transitorio
     * que no vale la pena esperar el backoff automático). Vuelve a encolar el mismo Job.
     */
    public function reintentarNotificacion(int $id): JsonResponse
    {
        $user = Auth::user();
        if (!$user || !$user->isAdmin()) {
            return response()->json(['success' => false, 'message' => 'No autorizado.'], 403);
        }

        $notificacion = TourApiNotificacion::find($id);
        if (!$notificacion) {
            return response()->json(['success' => false, 'message' => 'Notificación no encontrada.'], 404);
        }

        if ($notificacion->estado !== 'fallido') {
            return response()->json(['success' => false, 'message' => 'Solo se pueden reintentar notificaciones en estado "fallido".'], 422);
        }

        NotificarReservaApiExternaJob::dispatch($notificacion->reserva_tour_id);

        return response()->json(['success' => true, 'message' => 'Reintento encolado. El resultado se reflejará aquí en unos momentos.']);
    }

    /**
     * Determina los valores de "modo" permitidos según la bandera global de Sandbox forzado.
     */
    private function modosPermitidos(): array
    {
        return config('services.tours_api.forzar_sandbox', true)
            ? ['sandbox']
            : ['sandbox', 'produccion'];
    }
}
