<?php
/**
 * @file web.php
 * @description Registro de todas las rutas HTTP de la plataforma Atti Tours 2.0.
 * @date 2026-06-10
 * @author Antigravity
 */

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LanguageController;
use App\Http\Controllers\TourController;
use App\Http\Controllers\QrScanController;
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
// AUTENTICACIÓN (LOGIN / LOGOUT / REGISTRO)
// ==========================================
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
Route::post('/register', [AuthController::class, 'register'])->name('register.submit');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// ==========================================
// DASHBOARD DEL CLIENTE FINAL (MI CUENTA)
// ==========================================
Route::middleware(['auth'])->prefix('mi-cuenta')->group(function () {
    Route::get('/', [ClienteController::class, 'dashboard'])->name('cliente.dashboard');
    Route::post('/perfil', [ClienteController::class, 'updatePerfil'])->name('cliente.perfil.update');
});

// ==========================================
// PANEL DE CONTROL (ADMIN / PROVEEDOR)
// ==========================================
Route::middleware(['auth'])->prefix('dashboard')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/dates', [DashboardController::class, 'updateDates'])->name('dashboard.dates');
    Route::get('/tour/{id}/fechas', [DashboardController::class, 'getTourFechasJson'])->name('dashboard.tour.fechas.json');
    Route::post('/dates/update-single-day', [DashboardController::class, 'updateSingleDayAvailability'])->name('dashboard.dates.update-single-day');
    
    // Rutas Administrativas Nuevas
    Route::post('/proveedor', [DashboardController::class, 'storeProveedor'])->name('dashboard.proveedor');
    Route::post('/proveedor/{id}/update', [DashboardController::class, 'updateProveedor'])->name('dashboard.proveedor.update');
    Route::post('/tour', [DashboardController::class, 'storeTour'])->name('dashboard.tour');
    Route::post('/tour/{id}/update', [DashboardController::class, 'updateTour'])->name('dashboard.tour.update');
    Route::post('/tour-fechas', [DashboardController::class, 'storeTourFechas'])->name('dashboard.tour-fechas');
    Route::post('/user/{id}/role', [DashboardController::class, 'updateUserRole'])->name('dashboard.user.role');
    Route::post('/user/{id}/reset-password', [DashboardController::class, 'resetUserPassword'])->name('dashboard.user.reset-password');

    // ── QR de Asistencia ──────────────────────────────────────────────────────
    Route::get('/qr', [QrScanController::class, 'showScanner'])->name('dashboard.qr.scanner');
    Route::post('/qr/scan', [QrScanController::class, 'scanQr'])->name('dashboard.qr.scan');
    Route::get('/qr/verify/{token}', [QrScanController::class, 'verifyQrDirect'])->name('dashboard.qr.verify');
});
