<?php
/**
 * @file QrScanController.php
 * @description Controlador dedicado a la gestión y validación de códigos QR de asistencia para proveedores y administradores.
 * @date 2026-06-10
 * @author Antigravity
 */

namespace App\Http\Controllers;

use App\Models\Proveedor;
use App\Models\QrEscaneo;
use App\Models\Reserva;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class QrScanController extends Controller
{
    public function showScanner()
    {
        $user = Auth::user();
        if (!$user || (!$user->isAdmin() && !$user->isProveedor())) {
            return redirect()->route('home')->with('error', __('Acceso no autorizado.'));
        }

        // Cargar el historial de escaneos de hoy (exitosos y fallidos)
        $escaneosHoy = QrEscaneo::with(['reserva', 'usuario'])
            ->whereDate('created_at', today())
            ->when($user->isProveedor(), function ($q) use ($user) {
                $provId = $user->proveedor_id;
                $q->whereHas('reserva.detalles.tour', fn($tq) => $tq->where('proveedor_id', $provId));
            })
            ->orderByDesc('created_at')
            ->limit(100)
            ->get();

        return view('dashboard.qr', compact('escaneosHoy'));
    }

    /**
     * API AJAX: Valida el contenido del QR escaneado.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function scanQr(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (!$user || (!$user->isProveedor() && !$user->isAdmin())) {
            return response()->json(['success' => false, 'message' => 'Acceso no autorizado.'], 403);
        }

        $request->validate(['qr_content' => 'required|string']);

        $qrContent = trim($request->input('qr_content'));

        if (str_starts_with($qrContent, 'ATTITOURS:')) {
            $token = substr($qrContent, strlen('ATTITOURS:'));
        } elseif (str_contains($qrContent, '/qr/verify/')) {
            $token = basename(parse_url($qrContent, PHP_URL_PATH));
        } else {
            $token = $qrContent;
        }

        $token = trim($token);

        $reserva = Reserva::where('qr_token', $token)
            ->with(['detalles.tour.proveedor'])
            ->first();

        if (!$reserva) {
            $this->registrarEscaneo($user, null, $token, 'invalid', 'QR no válido o no encontrado.');

            return response()->json([
                'success' => false,
                'status'  => 'invalid',
                'message' => 'QR no válido o no encontrado.',
            ]);
        }

        if ($reserva->estado !== 'Pagada') {
            $mensaje = 'Reserva no confirmada (estado: ' . $reserva->estado . ').';
            $this->registrarEscaneo($user, $reserva, $token, 'not_paid', $mensaje);

            return response()->json([
                'success' => false,
                'status'  => 'not_paid',
                'message' => 'Esta reserva no está confirmada (estado: ' . $reserva->estado . ').',
            ]);
        }

        if ($user->isProveedor()) {
            $proveedorId = $user->proveedor_id;
            $tourDelProveedor = $reserva->detalles->first(fn($d) =>
                $d->tour && $d->tour->proveedor_id == $proveedorId
            );

            if (!$tourDelProveedor) {
                $this->registrarEscaneo($user, $reserva, $token, 'wrong_provider', 'QR no corresponde a los tours de este proveedor.');

                return response()->json([
                    'success' => false,
                    'status'  => 'wrong_provider',
                    'message' => 'Este QR no corresponde a ninguno de tus tours.',
                ]);
            }
        }

        if ($reserva->asistencia_confirmada) {
            $this->registrarEscaneo($user, $reserva, $token, 'already_confirmed', 'Asistencia ya confirmada anteriormente.');

            return response()->json([
                'success'   => true,
                'status'    => 'already_confirmed',
                'message'   => 'Asistencia ya confirmada anteriormente.',
                'confirmed_at' => $reserva->asistencia_confirmada_at?->format('d/m/Y H:i'),
                'reserva'   => $this->formatReservaQr($reserva),
            ]);
        }

        $proveedorConfirmadorId = $user->isProveedor() ? Proveedor::where('id', $user->proveedor_id)->value('id') : null;
        $reserva->update([
            'asistencia_confirmada'    => true,
            'asistencia_confirmada_at' => now(),
            'asistencia_confirmada_por'=> $proveedorConfirmadorId,
        ]);

        $this->registrarEscaneo($user, $reserva, $token, 'confirmed', '¡Asistencia confirmada exitosamente!');

        return response()->json([
            'success' => true,
            'status'  => 'confirmed',
            'message' => '¡Asistencia confirmada exitosamente!',
            'reserva' => $this->formatReservaQr($reserva),
        ]);
    }

    /**
     * Deja constancia en el historial de auditoría de cada intento de escaneo, sin importar el resultado.
     */
    private function registrarEscaneo(User $user, ?Reserva $reserva, string $token, string $resultado, ?string $mensaje): void
    {
        QrEscaneo::create([
            'reserva_id'      => $reserva?->id,
            'user_id'         => $user->id,
            'resultado'       => $resultado,
            'mensaje'         => $mensaje,
            'token_escaneado' => $token,
            'ip_address'      => request()->ip(),
        ]);
    }

    /**
     * Redirige al escáner con el token precargado.
     *
     * @param string $token
     * @return RedirectResponse
     */
    public function verifyQrDirect(string $token): RedirectResponse
    {
        $user = Auth::user();

        if (!$user) {
            return redirect()->route('login')->with('error', 'Inicia sesión como proveedor o administrador para escanear el QR.');
        }

        if (!$user->isProveedor() && !$user->isAdmin()) {
            return redirect()->route('home')->with('error', 'Acceso no autorizado.');
        }

        return redirect()->route('dashboard.qr.scanner')->with('qr_to_verify', $token);
    }

    /**
     * Formatea los datos de la reserva para la respuesta JSON.
     */
    private function formatReservaQr(Reserva $reserva): array
    {
        return [
            'ticket_codigo'  => $reserva->ticket_codigo,
            'nombre_cliente' => $reserva->nombre_cliente,
            'telefono'       => $reserva->telefono_cliente,
            'correo'         => $reserva->correo_cliente,
            'estado'         => $reserva->estado,
            'tours'          => $reserva->detalles->map(function ($d) {
                $titulo = $d->tour->titulo ?? 'Tour';
                if (is_array($titulo)) $titulo = $titulo['es'] ?? reset($titulo);
                return [
                    'nombre'   => $titulo,
                    'fecha'    => \Carbon\Carbon::parse($d->fecha_seleccionada)->locale('es')->translatedFormat('d M Y'),
                    'horario'  => $d->horario,
                    'personas' => $d->cantidad_personas,
                ];
            })->toArray(),
        ];
    }
}
