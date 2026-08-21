<?php
/**
 * @file AuthController.php
 * @description Controlador para gestionar el inicio de sesión, cierre de sesión y registro de clientes finales (tipo C). Admin y proveedores solo pueden ingresar, no registrarse aquí.
 * @date 2026-06-10
 * @author Antigravity
 */

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class AuthController extends Controller
{
    /**
     * Muestra la vista de login.
     *
     * @return View|RedirectResponse
     */
    public function showLogin()
    {
        if (Auth::check()) {
            $user = Auth::user();
            if ($user->isCliente()) {
                return redirect()->route('cliente.dashboard');
            }
            return redirect()->route('dashboard');
        }
        return view('auth.login');
    }

    /**
     * Procesa la solicitud de inicio de sesión.
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        if (Auth::attempt($credentials, $request->filled('remember'))) {
            $request->session()->regenerate();

            // Si es un cliente común, lo mandamos a la Home.
            // Si es admin o proveedor, al Dashboard.
            $user = Auth::user();
            if ($user->isAdmin() || $user->isProveedor()) {
                return redirect()->intended(route('dashboard'));
            } else {
                return redirect()->route('home')->with('success', __('Sesión iniciada exitosamente.'));
            }
        }

        return back()->withErrors([
            'email' => __('Credenciales incorrectas o usuario no encontrado.'),
        ])->onlyInput('email');
    }

    /**
     * Cierra la sesión activa del usuario.
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home')->with('success', __('Sesión cerrada exitosamente.'));
    }

    /**
     * Muestra la vista de registro de clientes finales.
     *
     * @return View|RedirectResponse
     */
    public function showRegister()
    {
        if (Auth::check()) {
            $user = Auth::user();
            if ($user->isAdmin() || $user->isProveedor()) {
                return redirect()->route('dashboard');
            }
            return redirect()->route('cliente.dashboard');
        }
        return view('auth.login', ['showRegister' => true]);
    }

    /**
     * Procesa el registro de un nuevo cliente final (tipo C).
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function register(Request $request): RedirectResponse
    {
        $request->validate([
            'name'                  => 'required|string|max:100',
            'email'                 => 'required|email|max:150|unique:users,email',
            'telefono'              => 'nullable|string|max:30',
            'pais'                  => 'nullable|string|max:80',
            'password'              => 'required|string|min:6|confirmed',
        ], [
            'name.required'         => 'El nombre es obligatorio.',
            'email.required'        => 'El correo es obligatorio.',
            'email.unique'          => 'Este correo ya está registrado. Inicia sesión.',
            'password.min'          => 'La contraseña debe tener al menos 6 caracteres.',
            'password.confirmed'    => 'Las contraseñas no coinciden.',
        ]);

        $user = User::create([
            'name'      => $request->input('name'),
            'email'     => $request->input('email'),
            'password'  => Hash::make($request->input('password')),
            'tipo'      => 'C',
            'telefono'  => $request->input('telefono'),
            'pais'      => $request->input('pais'),
        ]);

        Auth::login($user);
        $request->session()->regenerate();

        try {
            $user->sendEmailVerificationNotification();
        } catch (\Throwable $mailEx) {
            Log::warning('Error enviando correo de verificación: ' . $mailEx->getMessage());
        }

        return redirect()->route('home')->with('success', '¡Bienvenido a Atti Tours, ' . $user->name . '! Tu cuenta ha sido creada exitosamente. Revisa tu correo para verificarla.');
    }
}
