<?php
/**
 * @file ClienteController.php
 * @description Controlador para la sección "Mi Cuenta" del cliente final.
 *              Gestiona los 3 apartados: Mis reservas (activas con QR),
 *              Experiencias anteriores (historial sin QR), y Configuración (perfil, foto, contraseña, borrar cuenta).
 * @date 2026-07-21
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
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ClienteController extends Controller
{
    /**
     * Muestra la sección "Mi Cuenta" del cliente con los 3 apartados.
     *
     * @return View|RedirectResponse
     */
    public function dashboard(): View|RedirectResponse
    {
        $user = Auth::user();

        if (!$user) {
            return redirect()->route('login');
        }
        if ($user->isAdmin() || $user->isProveedor()) {
            return redirect()->route('dashboard');
        }

        // Obtener todas las reservas asociadas a este cliente
        $todasReservas = Reserva::with(['detalles.tour'])
            ->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                  ->orWhere('correo_cliente', $user->email);
            })
            ->orderBy('fecha_reserva', 'desc')
            ->get();

        $hoy = now()->toDateString();

        // 1. Mis reservas (activas / futuras)
        $reservasActivas = $todasReservas->filter(function ($reserva) use ($hoy) {
            $estado = strtolower($reserva->estado);
            if (in_array($estado, ['cancelada'])) {
                return false;
            }
            // Si tiene detalles con fecha futura o de hoy, o no tiene detalles aún
            $tieneDetallesFuturos = $reserva->detalles->contains(function ($det) use ($hoy) {
                return $det->fecha_seleccionada && $det->fecha_seleccionada->toDateString() >= $hoy;
            });
            return $tieneDetallesFuturos || $reserva->detalles->isEmpty();
        })->values();

        // 2. Tours anteriores / Experiencias anteriores (pasadas o canceladas)
        $reservasAnteriores = $todasReservas->reject(function ($reserva) use ($reservasActivas) {
            return $reservasActivas->contains('id', $reserva->id);
        })->values();

        // Estadísticas rápidas
        $totalReservas   = $todasReservas->count();
        $totalGastado    = $todasReservas->whereIn('estado', ['Pagada', 'pagada', 'confirmada'])->sum('precio_total_usd');
        $reservasPagadas = $todasReservas->whereIn('estado', ['Pagada', 'pagada', 'confirmada'])->count();

        // Tours vistos/reservados para thumbnails
        $toursVistos = Tour::whereIn('id', 
            $todasReservas->pluck('detalles')->flatten()->pluck('tour_id')->filter()->unique()->values()
        )->get()->keyBy('id');

        return view('cliente.dashboard', compact(
            'user',
            'reservasActivas',
            'reservasAnteriores',
            'todasReservas',
            'totalReservas',
            'totalGastado',
            'reservasPagadas',
            'toursVistos'
        ));
    }

    /**
     * Actualiza los datos del perfil (nombre, teléfono, país, foto de perfil, contraseña).
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
            'foto_perfil'      => 'nullable|image|mimes:jpeg,png,jpg,webp|max:15360',
            'password'         => 'nullable|string|min:6|confirmed',
            'current_password' => 'required_with:password|string',
        ], [
            'name.required'                  => 'El nombre es obligatorio.',
            'foto_perfil.image'              => 'El archivo de foto de perfil debe ser una imagen válida (JPG, PNG, WEBP).',
            'foto_perfil.max'                => 'La imagen no debe pesar más de 15MB.',
            'password.min'                   => 'La nueva contraseña debe tener al menos 6 caracteres.',
            'password.confirmed'             => 'Las contraseñas no coinciden.',
            'current_password.required_with' => 'Escribe tu contraseña actual para cambiarla.',
        ]);

        // Si quiere cambiar contraseña, verificar la actual
        if (!empty($validated['password'])) {
            if (!Hash::check($validated['current_password'], $user->password)) {
                return back()->withErrors(['current_password' => 'La contraseña actual es incorrecta.'])->with('active_tab', 'configuracion');
            }
        }

        $updateData = [
            'name'     => $validated['name'],
            'telefono' => $validated['telefono'] ?? $user->telefono,
            'pais'     => $validated['pais'] ?? $user->pais,
        ];

        // Procesar y optimizar subida de foto de perfil
        if ($request->hasFile('foto_perfil')) {
            $updateData['foto_perfil'] = \App\Services\ImageOptimizerService::uploadAndOptimize($request->file('foto_perfil'), 'perfiles');
        }

        if (!empty($validated['password'])) {
            $updateData['password'] = Hash::make($validated['password']);
        }

        $user->update($updateData);

        return redirect()->route('cliente.dashboard')
            ->with('success', 'Perfil actualizado correctamente.')
            ->with('active_tab', 'configuracion');
    }

    /**
     * Elimina la cuenta del cliente autenticado de forma definitiva.
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function deleteCuenta(Request $request): RedirectResponse
    {
        $user = Auth::user();

        if (!$user || $user->isAdmin() || $user->isProveedor()) {
            return redirect()->route('home')->with('error', 'Acceso no autorizado.');
        }

        $request->validate([
            'confirm_delete' => 'required|string',
        ]);

        if (strtolower(trim($request->input('confirm_delete'))) !== 'eliminar') {
            return back()->withErrors(['confirm_delete' => 'Debes escribir "eliminar" para confirmar.'])->with('active_tab', 'configuracion');
        }

        // Desvincular usuario de sus reservas para mantener historial contable pero liberar la cuenta
        Reserva::where('user_id', $user->id)->update(['user_id' => null]);

        Auth::logout();
        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home')->with('success', 'Tu cuenta ha sido eliminada correctamente.');
    }
}
