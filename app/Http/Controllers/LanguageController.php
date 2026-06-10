<?php
/**
 * @file LanguageController.php
 * @description Controlador para cambiar de forma dinámica el idioma de la aplicación (español, inglés, chino) y guardarlo en la sesión.
 * @date 2026-06-08
 * @author Antigravity
 */

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LanguageController extends Controller
{
    /**
     * Cambia el idioma activo de la aplicación.
     *
     * @param string $locale
     * @return RedirectResponse
     */
    public function switchLanguage(string $locale): RedirectResponse
    {
        if (in_array($locale, ['es', 'en', 'zh'])) {
            session()->put('locale', $locale);
        }

        return redirect()->back();
    }
}
