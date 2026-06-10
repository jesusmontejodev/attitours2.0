<?php
/**
 * @file ClienteController.php
 * @description Controlador para el dashboard personal del cliente final (tipo C).
 *              Gestiona el historial de reservas, perfil editable y métricas del cliente.
 * @date 2026-06-10
 * @author Antigravity
 */

namespace App\Http\Controllers;

use App\Models\Reserva;
use App\Models\Tour;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class ClienteController extends Controller
{
    /**
     * Muestra el dashboard personal del cliente autenticado.
     *
     * @return View|RedirectResponse
     */
    public function dashboard(): View|RedirectResponse
    {
        $user = Auth::user();

        // Solo clientes pueden acceder (Admin y Proveedor tienen su propio dashboard)
        if (!$user) {
            return redirect()->route('login');
        }
        if ($user->isAdmin() || $user->isProveedor()) {
            return redirect()->route('dashboard');
        }

        // Reservas del cliente con detalles de tours
        $reservas = Reserva::with(['detalles.tour'])
            ->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                  ->orWhere('correo_cliente', $user->email);
            })
            ->orderBy('fecha_reserva', 'desc')
            ->get();

        // Estadísticas rápidas
        $totalReservas   = $reservas->count();
        $totalGastado    = $reservas->where('estado', 'Pagada')->sum('precio_total_usd');
        $reservasPagadas = $reservas->where('estado', 'Pagada')->count();

        // Próxima reserva futura
        $proximaReserva = $reservas
            ->where('estado', 'Pagada')
            ->filter(fn ($r) => $r->detalles->where('fecha_seleccionada', '>=', now()->toDateString())->isNotEmpty())
            ->first();

        // Tours que ha reservado (para mostrar el historial con imágenes)
        $toursVistos = Tour::whereIn('id', 
            $reservas->pluck('detalles')->flatten()->pluck('tour_id')->unique()->values()
        )->get()->keyBy('id');

        return view('cliente.dashboard', compact(
            'user',
            'reservas',
            'totalReservas',
            'totalGastado',
            'reservasPagadas',
            'proximaReserva',
            'toursVistos'
        ));
    }

    /**
     * Actualiza los datos del perfil del cliente autenticado.
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function updatePerfil(Request $request): RedirectResponse
    {
        $user = Auth::user();

        if (!$user || $user->isAdmin() || $user->isProveedor()) {
            return redirect()->route('home')->with('error', 'Acceso no autorizado.');
        }

        $validated = $request->validate([
            'name'             => 'required|string|max:100',
            'telefono'         => 'nullable|string|max:30',
            'pais'             => 'nullable|string|max:80',
            'password'         => 'nullable|string|min:6|confirmed',
            'current_password' => 'required_with:password|string',
        ], [
            'name.required'           => 'El nombre es obligatorio.',
            'password.min'            => 'La nueva contraseña debe tener al menos 6 caracteres.',
            'password.confirmed'      => 'Las contraseñas no coinciden.',
            'current_password.required_with' => 'Escribe tu contraseña actual para cambiarla.',
        ]);

        // Si quiere cambiar contraseña, verificar la actual
        if (!empty($validated['password'])) {
            if (!Hash::check($validated['current_password'], $user->password)) {
                return back()->withErrors(['current_password' => 'La contraseña actual es incorrecta.']);
            }
        }

        $updateData = [
            'name'     => $validated['name'],
            'telefono' => $validated['telefono'] ?? $user->telefono,
            'pais'     => $validated['pais'] ?? $user->pais,
        ];

        if (!empty($validated['password'])) {
            $updateData['password'] = Hash::make($validated['password']);
        }

        $user->update($updateData);

        return redirect()->route('cliente.dashboard')
            ->with('success', 'Perfil actualizado correctamente.')
            ->with('active_section', 'perfil');
    }
}
