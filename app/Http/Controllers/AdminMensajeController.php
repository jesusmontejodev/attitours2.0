<?php
/**
 * @file AdminMensajeController.php
 * @description Panel de mensajería admin: el admin ve todos los hilos cliente-proveedor y
 *              responde en nombre del proveedor (proxy), reenviando manualmente por el
 *              contacto real del proveedor. Solo administradores acceden a esta sección —
 *              los usuarios tipo proveedor (PT) no tienen esta funcionalidad.
 * @date 2026-08-21
 * @author Antigravity
 */

namespace App\Http\Controllers;

use App\Mail\RespuestaMensajeCliente;
use App\Models\Mensaje;
use App\Models\Reserva;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class AdminMensajeController extends Controller
{
    private function gate(): ?RedirectResponse
    {
        $user = Auth::user();
        if (!$user || !$user->isAdmin()) {
            return redirect()->route('home')->with('error', __('Acceso no autorizado.'));
        }
        return null;
    }

    /**
     * Lista de hilos: una fila por reserva que tiene al menos un mensaje, con el conteo de
     * mensajes del cliente aún no leídos por el admin.
     */
    public function index(): View|RedirectResponse
    {
        if ($redirect = $this->gate()) {
            return $redirect;
        }

        $reservaIds = Mensaje::select('reserva_id')->distinct()->pluck('reserva_id');

        $hilos = Reserva::with(['detalles.tour.proveedor'])
            ->whereIn('id', $reservaIds)
            ->get()
            ->map(function (Reserva $reserva) {
                $noLeidos = Mensaje::where('reserva_id', $reserva->id)
                    ->where('remitente_tipo', 'cliente')
                    ->where('leido_por_admin', false)
                    ->count();
                $ultimo = Mensaje::where('reserva_id', $reserva->id)->latest('created_at')->first();

                return (object) [
                    'reserva'    => $reserva,
                    'no_leidos'  => $noLeidos,
                    'ultimo_msg' => $ultimo,
                ];
            })
            ->sortByDesc(fn ($h) => $h->ultimo_msg?->created_at)
            ->values();

        return view('dashboard.mensajes', compact('hilos'));
    }

    /**
     * JSON del hilo completo de una reserva, incluyendo el contacto REAL de cada proveedor
     * involucrado (solo visible aquí, nunca en los endpoints del lado cliente).
     */
    public function show(int $reserva): JsonResponse
    {
        if ($redirect = $this->gate()) {
            return response()->json(['success' => false, 'message' => 'Acceso no autorizado.'], 403);
        }

        $reservaModel = Reserva::with(['detalles.tour.proveedor'])->find($reserva);
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
                    'reserva_tour_id'         => $detalle->id,
                    'tour_nombre'             => is_array($detalle->tour->titulo) ? ($detalle->tour->titulo['es'] ?? reset($detalle->tour->titulo)) : $detalle->tour->titulo,
                    'proveedor_nombre'        => $proveedor->nombre_empresa,
                    'representante_telefono'  => $proveedor->representante_telefono,
                    'correo'                  => $proveedor->correo,
                ];
            })
            ->filter()
            ->values();

        Mensaje::where('reserva_id', $reservaModel->id)
            ->where('remitente_tipo', 'cliente')
            ->update(['leido_por_admin' => true]);

        return response()->json([
            'success'        => true,
            'reserva'        => [
                'id'             => $reservaModel->id,
                'ticket_codigo'  => $reservaModel->ticket_codigo,
                'nombre_cliente' => $reservaModel->nombre_cliente,
            ],
            'mensajes'       => $mensajes,
            'proveedores'    => $proveedores,
        ]);
    }

    /**
     * El admin responde en nombre del proveedor. Guarda el mensaje, registra a qué contacto
     * real se reenvió (auditoría interna) y avisa al cliente por correo.
     */
    public function responder(Request $request, int $reserva): JsonResponse
    {
        if ($redirect = $this->gate()) {
            return response()->json(['success' => false, 'message' => 'Acceso no autorizado.'], 403);
        }

        $reservaModel = Reserva::find($reserva);
        if (!$reservaModel) {
            return response()->json(['success' => false, 'message' => 'Reserva no encontrada.'], 404);
        }

        $validated = $request->validate([
            'cuerpo'                  => 'required|string|max:2000',
            'reserva_tour_id'         => 'nullable|integer',
            'contacto_destino_usado'  => 'nullable|string|max:255',
        ]);

        $mensaje = Mensaje::create([
            'reserva_id'              => $reservaModel->id,
            'reserva_tour_id'         => $validated['reserva_tour_id'] ?? null,
            'remitente_tipo'          => 'admin_como_proveedor',
            'autor_user_id'           => Auth::id(),
            'cuerpo'                  => $validated['cuerpo'],
            'contacto_destino_usado'  => $validated['contacto_destino_usado'] ?? null,
            'leido_por_admin'         => true,
            'leido_por_cliente'       => false,
        ]);

        if ($reservaModel->correo_cliente) {
            try {
                Mail::to($reservaModel->correo_cliente)->send(new RespuestaMensajeCliente($mensaje));
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('Error enviando correo de respuesta de mensaje: ' . $e->getMessage());
            }
        }

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
     * Marca como leídos (por el admin) todos los mensajes del cliente en esta reserva.
     */
    public function marcarLeido(int $reserva): JsonResponse
    {
        if ($redirect = $this->gate()) {
            return response()->json(['success' => false, 'message' => 'Acceso no autorizado.'], 403);
        }

        Mensaje::where('reserva_id', $reserva)
            ->where('remitente_tipo', 'cliente')
            ->update(['leido_por_admin' => true]);

        return response()->json(['success' => true]);
    }
}
