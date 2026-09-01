@extends('layouts.app')

<!-- 
 * @file checkout.blade.php
 * @description Vista Blade para el Checkout. Incluye el formulario de datos de pasajeros, pago simulado y pantalla de procesamiento premium. Adaptado a tema claro con colores corporativos e identidad de la marca.
 * @date 2026-06-29
 * @author Antigravity
 -->

@section('title', __('checkoutPageTitle') . ' - Attitour')

@section('content')
    <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8 py-10 relative">
        
        <!-- PANTALLA DE CARGA / PROCESAMIENTO PREMIUM (GLASSMORPHISM OVERLAY) -->
        <div id="payment-overlay" class="hidden fixed inset-0 z-50 flex flex-col items-center justify-center bg-white/95 backdrop-blur-md transition-all duration-300">
            <!-- Spinner -->
            <div class="relative flex items-center justify-center mb-6">
                <div class="h-20 w-20 rounded-full border-4 border-slate-200 border-t-brand-teal animate-spin"></div>
                <div class="absolute h-10 w-10 rounded-full bg-white flex items-center justify-center shadow-xs">
                    <svg class="h-5 w-5 text-brand-teal animate-pulse" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                </div>
            </div>
            <h3 class="text-base font-extrabold text-slate-800 tracking-wider uppercase">{{ __('processingTransactionTitle') }}</h3>
            <p class="text-xs text-slate-500 mt-2 max-w-xs text-center leading-relaxed font-semibold">
                {{ __('processingTransactionBody') }}
            </p>
        </div>

        <!-- CABECERA -->
        <div class="border-b border-slate-200 pb-6 mb-8">
            <h1 class="text-2xl sm:text-3xl font-black text-slate-800">
                {{ __('checkoutTitle') }}
            </h1>
            <p class="text-xs text-slate-505 mt-1 font-semibold">
                {{ __('checkoutSubtitle') }}
            </p>
        </div>

        <!-- GRID DE FORMULARIO / RESUMEN -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <!-- FORMULARIO DE PAGO (Izquierda) -->
            <main class="lg:col-span-2">
                <form id="checkout-form" action="{{ route('checkout.pay') }}" method="POST" class="flex flex-col gap-6">
                    @csrf
                    
                    <!-- Datos Personales -->
                    <div class="p-6 rounded-2xl border border-slate-200 bg-white shadow-md">
                        <h2 class="text-xs font-bold uppercase tracking-widest text-brand-teal mb-4 border-b border-slate-200 pb-2">
                            {{ __('mainPassengerDataTitle') }}
                        </h2>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <!-- Nombre completo -->
                            <div class="flex flex-col gap-1.5">
                                <label class="text-[10px] font-bold uppercase tracking-wider text-slate-500">{{ __('fullNameLabel') }}</label>
                                <input type="text" name="nombre" required value="{{ old('nombre', Auth::check() ? Auth::user()->name : '') }}" placeholder="{{ __('namePlaceholderExample') }}" class="w-full h-10 rounded-lg border border-slate-200 bg-slate-55 px-3 text-xs text-slate-800 placeholder-slate-400 focus:border-brand-teal focus:bg-white focus:ring-0 focus:outline-none transition-colors">
                            </div>

                            <!-- Correo -->
                            <div class="flex flex-col gap-1.5">
                                <label class="text-[10px] font-bold uppercase tracking-wider text-slate-500">{{ __('emailLabel') }}</label>
                                <input type="email" name="email" required value="{{ old('email', Auth::check() ? Auth::user()->email : '') }}" placeholder="{{ __('emailPlaceholderExample') }}" class="w-full h-10 rounded-lg border border-slate-200 bg-slate-55 px-3 text-xs text-slate-800 placeholder-slate-400 focus:border-brand-teal focus:bg-white focus:ring-0 focus:outline-none transition-colors">
                            </div>

                            <!-- Teléfono -->
                            <div class="flex flex-col gap-1.5 sm:col-span-2">
                                <label class="text-[10px] font-bold uppercase tracking-wider text-slate-500">{{ __('phoneNumberWhatsappLabel') }}</label>
                                <div class="flex gap-2">
                                    @php
                                        $codigosPais = [
                                            ['code' => '+52', 'flag' => '🇲🇽', 'name' => 'México'],
                                            ['code' => '+1', 'flag' => '🇺🇸', 'name' => 'Estados Unidos'],
                                            ['code' => '+1', 'flag' => '🇨🇦', 'name' => 'Canadá'],
                                            ['code' => '+34', 'flag' => '🇪🇸', 'name' => 'España'],
                                            ['code' => '+54', 'flag' => '🇦🇷', 'name' => 'Argentina'],
                                            ['code' => '+55', 'flag' => '🇧🇷', 'name' => 'Brasil'],
                                            ['code' => '+56', 'flag' => '🇨🇱', 'name' => 'Chile'],
                                            ['code' => '+57', 'flag' => '🇨🇴', 'name' => 'Colombia'],
                                            ['code' => '+506', 'flag' => '🇨🇷', 'name' => 'Costa Rica'],
                                            ['code' => '+593', 'flag' => '🇪🇨', 'name' => 'Ecuador'],
                                            ['code' => '+503', 'flag' => '🇸🇻', 'name' => 'El Salvador'],
                                            ['code' => '+502', 'flag' => '🇬🇹', 'name' => 'Guatemala'],
                                            ['code' => '+504', 'flag' => '🇭🇳', 'name' => 'Honduras'],
                                            ['code' => '+505', 'flag' => '🇳🇮', 'name' => 'Nicaragua'],
                                            ['code' => '+507', 'flag' => '🇵🇦', 'name' => 'Panamá'],
                                            ['code' => '+595', 'flag' => '🇵🇾', 'name' => 'Paraguay'],
                                            ['code' => '+51', 'flag' => '🇵🇪', 'name' => 'Perú'],
                                            ['code' => '+1', 'flag' => '🇩🇴', 'name' => 'República Dominicana'],
                                            ['code' => '+598', 'flag' => '🇺🇾', 'name' => 'Uruguay'],
                                            ['code' => '+58', 'flag' => '🇻🇪', 'name' => 'Venezuela'],
                                            ['code' => '+44', 'flag' => '🇬🇧', 'name' => 'Reino Unido'],
                                            ['code' => '+33', 'flag' => '🇫🇷', 'name' => 'Francia'],
                                            ['code' => '+49', 'flag' => '🇩🇪', 'name' => 'Alemania'],
                                        ];
                                        $codigoPaisSeleccionado = old('codigo_pais', '+52');
                                    @endphp
                                    <select name="codigo_pais" required class="h-10 w-28 shrink-0 rounded-lg border border-slate-200 bg-slate-55 px-2 text-xs text-slate-800 focus:border-brand-teal focus:bg-white focus:ring-0 focus:outline-none transition-colors">
                                        @foreach($codigosPais as $pais)
                                            <option value="{{ $pais['code'] }}" {{ $codigoPaisSeleccionado === $pais['code'] ? 'selected' : '' }}>{{ $pais['flag'] }} {{ $pais['code'] }}</option>
                                        @endforeach
                                    </select>
                                    <input type="tel" name="telefono" required value="{{ old('telefono', Auth::check() ? Auth::user()->telefono : '') }}" placeholder="{{ __('phonePlaceholderExample') }}" class="flex-1 min-w-0 h-10 rounded-lg border border-slate-200 bg-slate-55 px-3 text-xs text-slate-800 placeholder-slate-400 focus:border-brand-teal focus:bg-white focus:ring-0 focus:outline-none transition-colors">
                                </div>
                                <p class="text-[9px] text-slate-400 font-semibold">{{ __('phoneHelperText') }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Pago con Stripe -->
                    <div class="p-6 rounded-2xl border border-slate-200 bg-white shadow-md">
                        <h2 class="text-xs font-bold uppercase tracking-widest text-brand-teal mb-4 border-b border-slate-200 pb-2">
                            {{ __('securePaymentTitle') }}
                        </h2>
                        <p class="text-xs text-slate-500 font-semibold leading-relaxed">
                            {{ __('securePaymentBody') }}
                        </p>
                    </div>

                    <!-- Botón Enviar -->
                    <button type="submit" class="w-full h-12 inline-flex items-center justify-center rounded-xl bg-gradient-to-r from-brand-orange to-brand-orange-hover hover:opacity-95 text-xs font-bold uppercase tracking-widest text-white shadow-md shadow-brand-orange/15 cursor-pointer transition-all hover:scale-[1.01]">
                        {{ __('payWithStripeBtn') }}
                    </button>
                </form>
            </main>

            <!-- RESUMEN DE LA RESERVA (Derecha) -->
            <aside class="lg:col-span-1 flex flex-col gap-6">
                <div class="p-6 rounded-2xl border border-slate-200 bg-white shadow-lg">
                    <h2 class="text-xs font-bold uppercase tracking-widest text-slate-800 border-b border-slate-200 pb-3 mb-4">
                        {{ __('bookingSummaryTitle') }}
                    </h2>

                    <!-- Items del carrito -->
                    <div class="flex flex-col gap-4 border-b border-slate-200 pb-4 mb-4">
                        @foreach($cart as $item)
                            <div class="flex items-start gap-3">
                                <div class="shrink-0 h-10 w-10 overflow-hidden rounded bg-slate-50 border border-slate-200">
                                    <img src="{{ $item['imagen'] }}" alt="{{ $item['nombre'] }}" class="h-full w-full object-cover">
                                </div>
                                <div class="flex-grow min-w-0">
                                    <h4 class="text-xs font-bold text-slate-800 truncate">{{ $item['nombre'] }}</h4>
                                    <p class="text-[9px] text-slate-500 font-bold mt-0.5">{{ $item['cantidad'] }} {{ $item['cantidad'] > 1 ? __('people') : __('person') }} &bull; {{ \Carbon\Carbon::parse($item['fecha'])->locale(app()->getLocale())->translatedFormat('d M') }}</p>
                                </div>
                                <span class="text-xs font-bold text-slate-650 shrink-0">${{ number_format($item['subtotal']) }}</span>
                            </div>
                        @endforeach
                    </div>

                    <!-- Desglose Financiero -->
                    <div class="flex flex-col gap-3 text-xs mb-2 font-semibold">
                        <div class="flex justify-between text-slate-550">
                            <span>{{ __('subtotalLabel') }}</span>
                            <span>${{ number_format($total) }} USD</span>
                        </div>
                        <div class="flex justify-between text-slate-550">
                            <span>{{ __('transactionFeeLabel') }}</span>
                            <span class="text-emerald-605 font-bold">{{ __('freeLabel') }}</span>
                        </div>
                        <div class="flex justify-between border-t border-slate-200 pt-3 font-bold text-slate-700 text-sm">
                            <span>{{ __('totalToPayLabel') }}</span>
                            <span class="text-right">
                                <span class="text-brand-teal font-black">${{ number_format($total) }} USD</span>
                                <x-currency-note :usd="$total" />
                            </span>
                        </div>
                    </div>
                </div>
            </aside>
        </div>
    </div>

    <!-- SCRIPT: overlay de "redirigiendo a Stripe" mientras se crea la Checkout Session -->
    <script>
        const form = document.getElementById('checkout-form');
        const overlay = document.getElementById('payment-overlay');

        if (form && overlay) {
            form.addEventListener('submit', () => {
                // El overlay se queda visible hasta que el navegador salga hacia Stripe
                // (o vuelva con un error, en cuyo caso la recarga de página lo oculta solo).
                overlay.classList.remove('hidden');
            });
        }
    </script>
@endsection
