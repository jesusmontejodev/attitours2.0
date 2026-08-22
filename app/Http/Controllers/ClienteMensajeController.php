<?php
/**
 * @file ClienteMensajeController.php
 * @description Controlador del chat interno del cliente con el proveedor de su reserva. El
 *              proveedor nunca participa directamente: sus mensajes los redacta el admin desde
 *              AdminMensajeController, y el contacto real del proveedor nunca se expone aquí.
 * @date 2026-08-21
 * @author Antigravity
 */

namespace App\Http\Controllers;

use App\Models\Mensaje;
use App\Models\Reserva;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ClienteMensajeController extends Controller
{
    /**
     * Busca la reserva y valida que pertenece al cliente autenticado (por user_id o correo).
     */
    private function reservaDelCliente(int $reservaId): ?Reserva
    {
        $user = Auth::user();
        if (!$user) {
            return null;
        }

        return Reserva::with(['detalles.tour.proveedor'])
            ->where('id', $reservaId)
            ->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                  ->orWhere('correo_cliente', $user->email);
            })
            ->first();
    }

    /**
     * Lista los mensajes de una reserva (agrupados por tour cuando hay varios proveedores),
     * junto con la etiqueta visible de cada proveedor. Nunca incluye datos de contacto reales.
     */
    public function index(int $reserva): JsonResponse
    {
        $reservaModel = $this->reservaDelCliente($reserva);
        if (!$reservaModel) {
            return response()->json(['success' => false, 'message' => 'Reserva no encontrada.'], 404);
        }

        $mensajes = Mensaje::where('reserva_id', $reservaModel->id)
            ->orderBy('created_at')
            ->get()
            ->map(fn (Mensaje $m) => [
                'id'              => $m->id,
                'reserva_tour_id' => $m->reserva_tour_id,
                'remitente_tipo'  => $m->remitente_tipo,
                'cuerpo'          => $m->cuerpo,
                'created_at'      => $m->created_at->format('d/m/Y H:i'),
            ]);

        $proveedores = $reservaModel->detalles
            ->map(function ($detalle) {
                $proveedor = $detalle->tour?->proveedor;
                if (!$proveedor) {
                    return null;
                }
                return [
                    'reserva_tour_id' => $detalle->id,
                    'tour_nombre'     => is_array($detalle->tour->titulo) ? ($detalle->tour->titulo['es'] ?? reset($detalle->tour->titulo)) : $detalle->tour->titulo,
                    'contacto_visible'=> $proveedor->contacto_visible_cliente ?: 'Operador local de Attitour',
                ];
            })
            ->filter()
            ->values();

        return response()->json([
            'success'     => true,
            'mensajes'    => $mensajes,
            'proveedores' => $proveedores,
        ]);
    }

    /**
     * Crea un mensaje nuevo del cliente hacia el proveedor de la reserva.
     */
    public function store(Request $request, int $reserva): JsonResponse
    {
        $reservaModel = $this->reservaDelCliente($reserva);
        if (!$reservaModel) {
            return response()->json(['success' => false, 'message' => 'Reserva no encontrada.'], 404);
        }

        $validated = $request->validate([
            'cuerpo'          => 'required|string|max:2000',
            'reserva_tour_id' => 'nullable|integer',
        ]);

        if (!empty($validated['reserva_tour_id']) && !$reservaModel->detalles->contains('id', $validated['reserva_tour_id'])) {
            return response()->json(['success' => false, 'message' => 'El tour indicado no pertenece a esta reserva.'], 422);
        }

        $mensaje = Mensaje::create([
            'reserva_id'       => $reservaModel->id,
            'reserva_tour_id'  => $validated['reserva_tour_id'] ?? null,
            'remitente_tipo'   => 'cliente',
            'autor_user_id'    => Auth::id(),
            'cuerpo'           => $validated['cuerpo'],
            'leido_por_cliente'=> true,
            'leido_por_admin'  => false,
        ]);

        return response()->json([
            'success' => true,
            'mensaje' => [
                'id'              => $mensaje->id,
                'reserva_tour_id' => $mensaje->reserva_tour_id,
                'remitente_tipo'  => $mensaje->remitente_tipo,
                'cuerpo'          => $mensaje->cuerpo,
                'created_at'      => $mensaje->created_at->format('d/m/Y H:i'),
            ],
        ]);
    }

    /**
     * Marca como leídos (por el cliente) todos los mensajes del admin en esta reserva.
     */
    public function marcarLeido(int $reserva): JsonResponse
    {
        $reservaModel = $this->reservaDelCliente($reserva);
        if (!$reservaModel) {
            return response()->json(['success' => false, 'message' => 'Reserva no encontrada.'], 404);
        }

        Mensaje::where('reserva_id', $reservaModel->id)
            ->where('remitente_tipo', 'admin_como_proveedor')
            ->update(['leido_por_cliente' => true]);

        return response()->json(['success' => true]);
    }
}
