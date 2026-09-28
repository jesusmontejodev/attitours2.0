<?php
/**
 * @file AdminMensajeController.php
 * @description Panel de mensajería del dashboard. El admin ve todos los hilos cliente-proveedor
 *              y responde en nombre del proveedor (proxy), viendo su contacto real. Los usuarios
 *              tipo proveedor (PT) ven solo los hilos de reservas de sus propios tours y
 *              responden directamente al cliente; nunca ven respuestas dirigidas a tours de
 *              otros proveedores dentro de la misma reserva.
 * @date 2026-09-28
 * @author Antigravity
 */

namespace App\Http\Controllers;

use App\Mail\RespuestaMensajeCliente;
use App\Models\Mensaje;
use App\Models\Reserva;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class AdminMensajeController extends Controller
{
    /**
     * Devuelve el usuario si puede usar el panel de mensajes (admin, o proveedor con
     * proveedor_id asignado); null en cualquier otro caso.
     */
    private function usuarioPanel(): ?User
    {
        $user = Auth::user();
        if (!$user) {
            return null;
        }
        if ($user->isAdmin() || ($user->isProveedor() && $user->proveedor_id)) {
            return $user;
        }
        return null;
    }

    /**
     * Restringe una consulta de Reserva a las que incluyen al menos un tour del proveedor.
     */
    private function scopeReservasDelProveedor(Builder $query, int $proveedorId): Builder
    {
        return $query->whereHas('detalles.tour', fn ($q) => $q->where('proveedor_id', $proveedorId));
    }

    /**
     * Busca la reserva validando el acceso: el admin accede a cualquiera, el proveedor solo
     * a las que incluyen tours suyos.
     */
    private function reservaAccesible(User $user, int $reservaId): ?Reserva
    {
        $query = Reserva::with(['detalles.tour.proveedor'])->where('id', $reservaId);

        if (!$user->isAdmin()) {
            $this->scopeReservasDelProveedor($query, (int) $user->proveedor_id);
        }

        return $query->first();
    }

    /**
     * IDs de los detalles (ReservaTour) de la reserva que pertenecen al proveedor.
     */
    private function detallesDelProveedor(Reserva $reserva, int $proveedorId): Collection
    {
        return $reserva->detalles
            ->filter(fn ($detalle) => (int) $detalle->tour?->proveedor_id === $proveedorId)
            ->pluck('id');
    }

    /**
     * Mensajes de la reserva visibles para el usuario. El proveedor ve los mensajes generales
     * (sin reserva_tour_id, como los que escribe el cliente) y los de sus propios tours.
     */
    private function mensajesVisibles(User $user, Reserva $reserva): Builder
    {
        $query = Mensaje::where('reserva_id', $reserva->id);

        if (!$user->isAdmin()) {
            $detalleIds = $this->detallesDelProveedor($reserva, (int) $user->proveedor_id);
            $query->where(fn ($q) => $q->whereNull('reserva_tour_id')->orWhereIn('reserva_tour_id', $detalleIds));
        }

        return $query;
    }

    private function columnaLeido(User $user): string
    {
        return $user->isAdmin() ? 'leido_por_admin' : 'leido_por_proveedor';
    }

    private function serializarMensaje(Mensaje $m): array
    {
        return [
            'id'              => $m->id,
            'reserva_tour_id' => $m->reserva_tour_id,
            'remitente_tipo'  => $m->remitente_tipo,
            'cuerpo'          => $m->cuerpo,
            'created_at'      => $m->created_at->format('d/m/Y H:i'),
        ];
    }

    /**
     * Lista de hilos: una fila por reserva con mensajes, con el conteo de mensajes del cliente
     * no leídos por quien consulta. Al proveedor también se le listan las reservas pagadas de
     * sus tours con fecha próxima aunque aún no tengan mensajes, para que pueda escribirle
     * primero al cliente.
     */
    public function index(): View|RedirectResponse
    {
        $user = $this->usuarioPanel();
        if (!$user) {
            return redirect()->route('home')->with('error', __('Acceso no autorizado.'));
        }

        $reservaIdsConMensajes = Mensaje::select('reserva_id')->distinct()->pluck('reserva_id');

        $query = Reserva::with(['detalles.tour.proveedor']);

        if ($user->isAdmin()) {
            $query->whereIn('id', $reservaIdsConMensajes);
        } else {
            $proveedorId = (int) $user->proveedor_id;
            $this->scopeReservasDelProveedor($query, $proveedorId)
                ->where(function ($q) use ($reservaIdsConMensajes, $proveedorId) {
                    $q->whereIn('id', $reservaIdsConMensajes)
                      ->orWhere(function ($q) use ($proveedorId) {
                          $q->where('estado', 'Pagada')
                            ->whereHas('detalles', fn ($d) => $d
                                ->whereDate('fecha_seleccionada', '>=', now()->toDateString())
                                ->whereHas('tour', fn ($t) => $t->where('proveedor_id', $proveedorId)));
                      });
                });
        }

        $columnaLeido = $this->columnaLeido($user);

        $hilos = $query->get()
            ->map(function (Reserva $reserva) use ($user, $columnaLeido) {
                $visibles = $this->mensajesVisibles($user, $reserva);

                return (object) [
                    'reserva'    => $reserva,
                    'no_leidos'  => (clone $visibles)->where('remitente_tipo', 'cliente')->where($columnaLeido, false)->count(),
                    'ultimo_msg' => (clone $visibles)->latest('created_at')->first(),
                ];
            })
            ->sortByDesc(fn ($h) => $h->ultimo_msg?->created_at ?? $h->reserva->fecha_reserva)
            ->values();

        $esAdmin = $user->isAdmin();

        return view('dashboard.mensajes', compact('hilos', 'esAdmin'));
    }

    /**
     * JSON del hilo de una reserva. Al admin se le incluye el contacto REAL de cada proveedor
     * involucrado; al proveedor, solo sus propios tours y los datos del cliente.
     */
    public function show(int $reserva): JsonResponse
    {
        $user = $this->usuarioPanel();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Acceso no autorizado.'], 403);
        }

        $reservaModel = $this->reservaAccesible($user, $reserva);
        if (!$reservaModel) {
            return response()->json(['success' => false, 'message' => 'Reserva no encontrada.'], 404);
        }

        $mensajes = $this->mensajesVisibles($user, $reservaModel)
            ->orderBy('created_at')
            ->get()
            ->map(fn (Mensaje $m) => $this->serializarMensaje($m));

        $proveedores = $reservaModel->detalles
            ->filter(fn ($detalle) => $user->isAdmin() || (int) $detalle->tour?->proveedor_id === (int) $user->proveedor_id)
            ->map(function ($detalle) use ($user) {
                $proveedor = $detalle->tour?->proveedor;
                if (!$proveedor) {
                    return null;
                }
                $tourNombre = is_array($detalle->tour->titulo) ? ($detalle->tour->titulo['es'] ?? reset($detalle->tour->titulo)) : $detalle->tour->titulo;

                if (!$user->isAdmin()) {
                    return [
                        'reserva_tour_id' => $detalle->id,
                        'tour_nombre'     => $tourNombre,
                        'fecha'           => $detalle->fecha_seleccionada->format('d/m/Y'),
                        'horario'         => $detalle->horario,
                        'personas'        => $detalle->cantidad_personas,
                    ];
                }

                return [
                    'reserva_tour_id'         => $detalle->id,
                    'tour_nombre'             => $tourNombre,
                    'proveedor_nombre'        => $proveedor->nombre_empresa,
                    'representante_telefono'  => $proveedor->representante_telefono,
                    'correo'                  => $proveedor->correo,
                ];
            })
            ->filter()
            ->values();

        $this->mensajesVisibles($user, $reservaModel)
            ->where('remitente_tipo', 'cliente')
            ->update([$this->columnaLeido($user) => true]);

        return response()->json([
            'success'        => true,
            'es_admin'       => $user->isAdmin(),
            'reserva'        => [
                'id'               => $reservaModel->id,
                'ticket_codigo'    => $reservaModel->ticket_codigo,
                'nombre_cliente'   => $reservaModel->nombre_cliente,
                'correo_cliente'   => $reservaModel->correo_cliente,
                'telefono_cliente' => $reservaModel->telefono_cliente,
            ],
            'mensajes'       => $mensajes,
            'proveedores'    => $proveedores,
        ]);
    }

    /**
     * Responde al cliente. El admin lo hace en nombre del proveedor (registrando a qué contacto
     * real se reenvió, auditoría interna); el proveedor responde directamente y su mensaje queda
     * ligado a su tour dentro de la reserva. En ambos casos se avisa al cliente por correo.
     */
    public function responder(Request $request, int $reserva): JsonResponse
    {
        $user = $this->usuarioPanel();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Acceso no autorizado.'], 403);
        }

        $reservaModel = $this->reservaAccesible($user, $reserva);
        if (!$reservaModel) {
            return response()->json(['success' => false, 'message' => 'Reserva no encontrada.'], 404);
        }

        $validated = $request->validate([
            'cuerpo'                  => 'required|string|max:2000',
            'reserva_tour_id'         => 'nullable|integer',
            'contacto_destino_usado'  => 'nullable|string|max:255',
        ]);

        if ($user->isAdmin()) {
            $datos = [
                'remitente_tipo'         => 'admin_como_proveedor',
                'reserva_tour_id'        => $validated['reserva_tour_id'] ?? null,
                'contacto_destino_usado' => $validated['contacto_destino_usado'] ?? null,
                'leido_por_admin'        => true,
            ];
        } else {
            $detalleIds = $this->detallesDelProveedor($reservaModel, (int) $user->proveedor_id);
            $reservaTourId = $validated['reserva_tour_id'] ?? null;
            if ($reservaTourId && !$detalleIds->contains($reservaTourId)) {
                return response()->json(['success' => false, 'message' => 'El tour indicado no es tuyo.'], 422);
            }

            $datos = [
                'remitente_tipo'      => 'proveedor',
                'reserva_tour_id'     => $reservaTourId ?? $detalleIds->first(),
                'leido_por_proveedor' => true,
            ];
        }

        $mensaje = Mensaje::create($datos + [
            'reserva_id'        => $reservaModel->id,
            'autor_user_id'     => $user->id,
            'cuerpo'            => $validated['cuerpo'],
            'leido_por_cliente' => false,
        ]);

        if ($reservaModel->correo_cliente) {
            try {
                Mail::to($reservaModel->correo_cliente)->send(new RespuestaMensajeCliente($mensaje));
            } catch (\Throwable $e) {
                Log::warning('Error enviando correo de respuesta de mensaje: ' . $e->getMessage());
            }
        }

        return response()->json([
            'success' => true,
            'mensaje' => $this->serializarMensaje($mensaje),
        ]);
    }

    /**
     * Marca como leídos (por quien consulta) los mensajes del cliente visibles en esta reserva.
     */
    public function marcarLeido(int $reserva): JsonResponse
    {
        $user = $this->usuarioPanel();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Acceso no autorizado.'], 403);
        }

        $reservaModel = $this->reservaAccesible($user, $reserva);
        if (!$reservaModel) {
            return response()->json(['success' => false, 'message' => 'Reserva no encontrada.'], 404);
        }

        $this->mensajesVisibles($user, $reservaModel)
            ->where('remitente_tipo', 'cliente')
            ->update([$this->columnaLeido($user) => true]);

        return response()->json(['success' => true]);
    }
}
