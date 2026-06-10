<?php
/**
 * @file CartController.php
 * @description Controlador para gestionar el carrito de compras alojado en la sesión de Laravel, incluyendo validaciones de cupos.
 * @date 2026-06-08
 * @author Antigravity
 */

namespace App\Http\Controllers;

use App\Models\Tour;
use App\Models\TourFecha;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CartController extends Controller
{
    /**
     * Muestra el contenido del carrito.
     *
     * @return View
     */
    public function index(): View
    {
        $cart = session()->get('cart', []);
        
        $total = 0;
        foreach ($cart as $item) {
            $total += $item['subtotal'];
        }

        return view('cart', compact('cart', 'total'));
    }

    /**
     * Agrega un tour al carrito tras validar disponibilidad.
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function add(Request $request): RedirectResponse
    {
        $request->validate([
            'tour_id' => 'required|exists:tours,id',
            'fecha' => 'required|date_format:Y-m-d',
            'cantidad' => 'required|integer|min:1',
            'horario' => 'nullable|string'
        ]);

        $tourId = $request->input('tour_id');
        $fechaStr = $request->input('fecha');
        $cantidad = (int) $request->input('cantidad');
        $horario = $request->input('horario');

        $tour = Tour::findOrFail($tourId);
        $tourFecha = TourFecha::where('tour_id', $tourId)
            ->where('fecha', $fechaStr)
            ->first();

        if (!$tourFecha) {
            return redirect()->back()->with('error', __('La fecha seleccionada no está disponible.'));
        }

        // Validar cupos
        if ($tourFecha->cupo_disponible < $cantidad) {
            return redirect()->back()->with('error', __('No hay suficientes cupos disponibles. Solo quedan :cupos cupos.', ['cupos' => $tourFecha->cupo_disponible]));
        }

        $cart = session()->get('cart', []);
        $key = $tourId . '_' . $fechaStr . '_' . \Illuminate\Support\Str::slug($horario ?: 'default');

        if (isset($cart[$key])) {
            // Si ya existe en el carrito, sumamos y validamos el total acumulado
            $nuevaCantidad = $cart[$key]['cantidad'] + $cantidad;
            if ($tourFecha->cupo_disponible < $nuevaCantidad) {
                return redirect()->back()->with('error', __('No puedes añadir más personas. Supera el cupo disponible.'));
            }
            $cart[$key]['cantidad'] = $nuevaCantidad;
            $cart[$key]['subtotal'] = $nuevaCantidad * $cart[$key]['precio_unitario'];
        } else {
            // Añadir nuevo item
            $cart[$key] = [
                'tour_id' => $tourId,
                'nombre' => $tour->nombre, // Usa el accesor dinámico para traducción
                'imagen' => $tour->imagen_destacada,
                'fecha' => $fechaStr,
                'horario' => $horario,
                'cantidad' => $cantidad,
                'precio_unitario' => $tour->precio_base_usd,
                'subtotal' => $cantidad * $tour->precio_base_usd
            ];
        }

        session()->put('cart', $cart);

        return redirect()->route('cart.index')->with('success', __('Tour añadido al carrito exitosamente.'));
    }

    /**
     * Actualiza la cantidad de personas de un item del carrito.
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'key' => 'required|string',
            'cantidad' => 'required|integer|min:1'
        ]);

        $key = $request->input('key');
        $cantidad = (int) $request->input('cantidad');

        $cart = session()->get('cart', []);

        if (!isset($cart[$key])) {
            return redirect()->route('cart.index')->with('error', __('El tour no está en el carrito.'));
        }

        $tourId = $cart[$key]['tour_id'];
        $fechaStr = $cart[$key]['fecha'];

        $tourFecha = TourFecha::where('tour_id', $tourId)
            ->where('fecha', $fechaStr)
            ->first();

        if ($tourFecha && $tourFecha->cupo_disponible < $cantidad) {
            return redirect()->route('cart.index')->with('error', __('No hay suficientes cupos disponibles.'));
        }

        $cart[$key]['cantidad'] = $cantidad;
        $cart[$key]['subtotal'] = $cantidad * $cart[$key]['precio_unitario'];

        session()->put('cart', $cart);

        return redirect()->route('cart.index')->with('success', __('Carrito actualizado.'));
    }

    /**
     * Elimina un item del carrito.
     *
     * @param string $key
     * @return RedirectResponse
     */
    public function remove(string $key): RedirectResponse
    {
        $cart = session()->get('cart', []);

        if (isset($cart[$key])) {
            unset($cart[$key]);
            session()->put('cart', $cart);
        }

        return redirect()->route('cart.index')->with('success', __('Tour removido del carrito.'));
    }

    /**
     * Vacía el carrito.
     *
     * @return RedirectResponse
     */
    public function clear(): RedirectResponse
    {
        session()->forget('cart');
        return redirect()->route('cart.index')->with('success', __('Carrito vaciado.'));
    }
}
