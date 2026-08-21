<?php
/**
 * @file web.php
 * @description Registro de todas las rutas HTTP de la plataforma Atti Tours 2.0.
 * @date 2026-06-29
 * @author Antigravity
 */

use App\Http\Controllers\ApiConexionController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LanguageController;
use App\Http\Controllers\TourController;
use App\Http\Controllers\QrScanController;
use App\Http\Controllers\StripeWebhookController;
use App\Http\Controllers\TourApiProxyController;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// ==========================================
// RUTAS PÚBLICAS Y DE LOCALIZACIÓN
// ==========================================
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/lang/{locale}', [LanguageController::class, 'switchLanguage'])->name('lang.switch');
Route::get('/tours', [HomeController::class, 'catalog'])->name('catalog');
Route::get('/tours/{id}', [TourController::class, 'show'])->name('tours.show');
Route::get('/tours/{id}/availability', [TourController::class, 'checkAvailability'])->name('tours.availability');

// ==========================================
// RUTAS DEL CARRITO DE COMPRAS
// ==========================================
Route::prefix('cart')->group(function () {
    Route::get('/', [CartController::class, 'index'])->name('cart.index');
    Route::post('/add', [CartController::class, 'add'])->name('cart.add');
    Route::post('/update', [CartController::class, 'update'])->name('cart.update');
    Route::get('/remove/{key}', [CartController::class, 'remove'])->name('cart.remove');
    Route::get('/clear', [CartController::class, 'clear'])->name('cart.clear');

    // Proxy hacia la API externa (Unique) para tours con origen = api_externa.
    // El navegador nunca llama a Unique directamente ni ve sus credenciales.
    Route::prefix('tour-api')->group(function () {
        Route::get('/idiomas', [TourApiProxyController::class, 'idiomas'])->name('cart.tour-api.idiomas');
        Route::get('/hoteles', [TourApiProxyController::class, 'hoteles'])->name('cart.tour-api.hoteles');
        Route::post('/pickup', [TourApiProxyController::class, 'pickup'])->name('cart.tour-api.pickup');
    });
});

// ==========================================
// RUTAS DEL CHECKOUT Y PAGO
// ==========================================
Route::prefix('checkout')->group(function () {
    Route::get('/', [CheckoutController::class, 'index'])->name('checkout.index');
    Route::post('/pay', [CheckoutController::class, 'placeOrder'])->name('checkout.pay');
    Route::get('/success', [CheckoutController::class, 'success'])->name('checkout.success');
    Route::post('/notify', [CheckoutController::class, 'sendSimulatedNotification'])->name('checkout.notify');
});

// ==========================================
// WEBHOOK DE STRIPE (llamado por Stripe, no por el usuario)
// ==========================================
Route::post('/webhooks/stripe', [StripeWebhookController::class, 'handle'])->name('checkout.stripe.webhook');

// ==========================================
// AUTENTICACIÓN (LOGIN / LOGOUT / REGISTRO)
// ==========================================
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
Route::post('/register', [AuthController::class, 'register'])->name('register.submit');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// ==========================================
// VERIFICACIÓN DE CORREO (al crear una cuenta)
// ==========================================
Route::middleware('auth')->group(function () {
    Route::get('/email/verify', function () {
        return view('auth.verify-email');
    })->name('verification.notice');

    Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
        $request->fulfill();

        return redirect()->route('home')->with('success', '¡Tu correo ha sido verificado exitosamente!');
    })->middleware('signed')->name('verification.verify');

    Route::post('/email/verification-notification', function (Request $request) {
        $request->user()->sendEmailVerificationNotification();

        return back()->with('success', 'Te hemos enviado un nuevo enlace de verificación a tu correo.');
    })->middleware('throttle:6,1')->name('verification.send');
});

// ==========================================
// DASHBOARD DEL CLIENTE FINAL (MI CUENTA)
// ==========================================
Route::middleware(['auth'])->prefix('mi-cuenta')->group(function () {
    Route::get('/', [ClienteController::class, 'dashboard'])->name('cliente.dashboard');
    Route::post('/perfil', [ClienteController::class, 'updatePerfil'])->name('cliente.perfil.update');
    Route::post('/eliminar-cuenta', [ClienteController::class, 'deleteCuenta'])->name('cliente.cuenta.delete');
});

// ==========================================
// PANEL DE CONTROL (ADMIN / PROVEEDOR)
// ==========================================
Route::middleware(['auth'])->prefix('dashboard')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/dates', [DashboardController::class, 'updateDates'])->name('dashboard.dates');
    Route::get('/tour/{id}/fechas', [DashboardController::class, 'getTourFechasJson'])->name('dashboard.tour.fechas.json');
    Route::post('/dates/update-single-day', [DashboardController::class, 'updateSingleDayAvailability'])->name('dashboard.dates.update-single-day');
    Route::post('/dates/update-batch', [DashboardController::class, 'updateBatchAvailability'])->name('dashboard.dates.update-batch');
    
    // Rutas Administrativas Nuevas
    Route::post('/proveedor', [DashboardController::class, 'storeProveedor'])->name('dashboard.proveedor');
    Route::post('/proveedor/{id}/update', [DashboardController::class, 'updateProveedor'])->name('dashboard.proveedor.update');
    Route::post('/tour', [DashboardController::class, 'storeTour'])->name('dashboard.tour');
    Route::post('/tour/{id}/update', [DashboardController::class, 'updateTour'])->name('dashboard.tour.update');
    Route::post('/tour/{id}/gallery-image', [DashboardController::class, 'addGalleryImage'])->name('dashboard.tour.gallery-image.add');
    Route::delete('/tour/{id}/gallery-image', [DashboardController::class, 'removeGalleryImage'])->name('dashboard.tour.gallery-image.remove');
    Route::delete('/tour/{id}', [DashboardController::class, 'destroyTour'])->name('dashboard.tour.destroy');
    Route::post('/tour-fechas', [DashboardController::class, 'storeTourFechas'])->name('dashboard.tour-fechas');
    Route::post('/user/{id}/role', [DashboardController::class, 'updateUserRole'])->name('dashboard.user.role');
    Route::post('/user/{id}/reset-password', [DashboardController::class, 'resetUserPassword'])->name('dashboard.user.reset-password');
    Route::delete('/proveedor/{id}', [DashboardController::class, 'destroyProveedor'])->name('dashboard.proveedor.destroy');

    // ── QR de Asistencia ──────────────────────────────────────────────────────
    Route::get('/qr', [QrScanController::class, 'showScanner'])->name('dashboard.qr.scanner');
    Route::post('/qr/scan', [QrScanController::class, 'scanQr'])->name('dashboard.qr.scan');
    Route::get('/qr/verify/{token}', [QrScanController::class, 'verifyQrDirect'])->name('dashboard.qr.verify');

    // ── Conexiones a APIs externas y sincronización de catálogo ────────────────
    Route::prefix('api-conexiones')->group(function () {
        Route::post('/', [ApiConexionController::class, 'store'])->name('dashboard.api.store');
        Route::post('/{id}/update', [ApiConexionController::class, 'update'])->name('dashboard.api.update');
        Route::delete('/{id}', [ApiConexionController::class, 'destroy'])->name('dashboard.api.destroy');
        Route::post('/{id}/sync', [ApiConexionController::class, 'sync'])->name('dashboard.api.sync');

        Route::get('/importados/{id}', [ApiConexionController::class, 'showImportado'])->name('dashboard.api.importados.show');
        Route::post('/importados/{id}/descartar', [ApiConexionController::class, 'descartarImportado'])->name('dashboard.api.importados.discard');

        Route::post('/cambios-precio/{id}/aprobar', [ApiConexionController::class, 'aprobarCambioPrecio'])->name('dashboard.api.cambios-precio.approve');
        Route::post('/cambios-precio/{id}/rechazar', [ApiConexionController::class, 'rechazarCambioPrecio'])->name('dashboard.api.cambios-precio.reject');

        Route::post('/notificaciones/{id}/reintentar', [ApiConexionController::class, 'reintentarNotificacion'])->name('dashboard.api.notificaciones.retry');
    });
});
