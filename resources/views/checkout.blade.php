@extends('layouts.app')

<!-- 
 * @file checkout.blade.php
 * @description Vista Blade para el Checkout. Incluye el formulario de datos de pasajeros, pago simulado y pantalla de procesamiento premium. Adaptado a tema claro con colores corporativos e identidad de la marca.
 * @date 2026-06-29
 * @author Antigravity
 -->

@section('title', 'Pasarela de Pago - Atti Tours')

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
            <h3 class="text-base font-extrabold text-slate-800 tracking-wider uppercase">Procesando Transacción</h3>
            <p class="text-xs text-slate-500 mt-2 max-w-xs text-center leading-relaxed font-semibold">
                Por favor no cierres la ventana ni recargues la página. Conectando con el gateway de pago seguro...
            </p>
        </div>

        <!-- CABECERA -->
        <div class="border-b border-slate-200 pb-6 mb-8">
            <h1 class="text-2xl sm:text-3xl font-black text-slate-800">
                {{ __('checkoutTitle') }}
            </h1>
            <p class="text-xs text-slate-505 mt-1 font-semibold">
                Completa tus datos personales y de facturación para recibir tus pases de viaje.
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
                            1. Datos del Pasajero Principal
                        </h2>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <!-- Nombre completo -->
                            <div class="flex flex-col gap-1.5">
                                <label class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Nombre Completo</label>
                                <input type="text" name="nombre" required value="{{ old('nombre', Auth::check() ? Auth::user()->name : '') }}" placeholder="Ej. Juan Pérez" class="w-full h-10 rounded-lg border border-slate-200 bg-slate-55 px-3 text-xs text-slate-800 placeholder-slate-400 focus:border-brand-teal focus:bg-white focus:ring-0 focus:outline-none transition-colors">
                            </div>

                            <!-- Correo -->
                            <div class="flex flex-col gap-1.5">
                                <label class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Correo Electrónico</label>
                                <input type="email" name="email" required value="{{ old('email', Auth::check() ? Auth::user()->email : '') }}" placeholder="juan@correo.com" class="w-full h-10 rounded-lg border border-slate-200 bg-slate-55 px-3 text-xs text-slate-800 placeholder-slate-400 focus:border-brand-teal focus:bg-white focus:ring-0 focus:outline-none transition-colors">
                            </div>

                            <!-- Teléfono -->
                            <div class="flex flex-col gap-1.5 sm:col-span-2">
                                <label class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Número de Teléfono (con código de país)</label>
                                <input type="text" name="telefono" required value="{{ old('telefono', Auth::check() ? Auth::user()->telefono : '') }}" placeholder="Ej. +52 55 1234 5678" class="w-full h-10 rounded-lg border border-slate-200 bg-slate-55 px-3 text-xs text-slate-800 placeholder-slate-400 focus:border-brand-teal focus:bg-white focus:ring-0 focus:outline-none transition-colors">
                            </div>
                        </div>
                    </div>

                    <!-- Datos Tarjeta Ficticios -->
                    <div class="p-6 rounded-2xl border border-slate-200 bg-white shadow-md">
                        <h2 class="text-xs font-bold uppercase tracking-widest text-brand-teal mb-4 border-b border-slate-200 pb-2">
                            2. Detalles de Pago (Simulación Segura)
                        </h2>
                        
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <!-- Titular de Tarjeta -->
                            <div class="flex flex-col gap-1.5 sm:col-span-2">
                                <label class="text-[10px] font-bold uppercase tracking-wider text-slate-500">{{ __('cardName') }}</label>
                                <input type="text" name="card_name" required value="{{ old('card_name') }}" placeholder="Nombre del titular" class="w-full h-10 rounded-lg border border-slate-200 bg-slate-55 px-3 text-xs text-slate-800 placeholder-slate-400 focus:border-brand-teal focus:bg-white focus:ring-0 focus:outline-none transition-colors">
                            </div>

                            <!-- Número de Tarjeta -->
                            <div class="flex flex-col gap-1.5 sm:col-span-2">
                                <label class="text-[10px] font-bold uppercase tracking-wider text-slate-500">{{ __('cardNumber') }}</label>
                                <input type="text" id="card_number_input" name="card_number" required value="{{ old('card_number') }}" placeholder="4111 2222 3333 4444" minlength="16" maxlength="19" class="w-full h-10 rounded-lg border border-slate-200 bg-slate-55 px-3 text-xs text-slate-800 placeholder-slate-400 focus:border-brand-teal focus:bg-white focus:ring-0 focus:outline-none transition-colors">
                            </div>

                            <!-- Expiración -->
                            <div class="flex flex-col gap-1.5">
                                <label class="text-[10px] font-bold uppercase tracking-wider text-slate-500">{{ __('cardExpiry') }}</label>
                                <input type="text" id="card_expiry_input" name="card_expiry" required value="{{ old('card_expiry') }}" placeholder="MM/AA" maxlength="5" class="w-full h-10 rounded-lg border border-slate-200 bg-slate-55 px-3 text-xs text-slate-800 placeholder-slate-400 focus:border-brand-teal focus:bg-white focus:ring-0 focus:outline-none transition-colors">
                            </div>

                            <!-- CVV -->
                            <div class="flex flex-col gap-1.5">
                                <label class="text-[10px] font-bold uppercase tracking-wider text-slate-500">{{ __('cardCvv') }}</label>
                                <input type="password" name="card_cvv" required value="{{ old('card_cvv') }}" placeholder="123" minlength="3" maxlength="4" class="w-full h-10 rounded-lg border border-slate-200 bg-slate-55 px-3 text-xs text-slate-800 placeholder-slate-400 focus:border-brand-teal focus:bg-white focus:ring-0 focus:outline-none transition-colors">
                            </div>
                        </div>
                    </div>

                    <!-- Botón Enviar -->
                    <button type="submit" class="w-full h-12 inline-flex items-center justify-center rounded-xl bg-gradient-to-r from-brand-orange to-brand-orange-hover hover:opacity-95 text-xs font-bold uppercase tracking-widest text-white shadow-md shadow-brand-orange/15 cursor-pointer transition-all hover:scale-[1.01]">
                        {{ __('placeOrder') }}
                    </button>
                </form>
            </main>

            <!-- RESUMEN DE LA RESERVA (Derecha) -->
            <aside class="lg:col-span-1 flex flex-col gap-6">
                <div class="p-6 rounded-2xl border border-slate-200 bg-white shadow-lg">
                    <h2 class="text-xs font-bold uppercase tracking-widest text-slate-800 border-b border-slate-200 pb-3 mb-4">
                        Resumen de Reserva
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
                                    <p class="text-[9px] text-slate-500 font-bold mt-0.5">{{ $item['cantidad'] }} {{ $item['cantidad'] > 1 ? __('people') : __('person') }} &bull; {{ \Carbon\Carbon::parse($item['fecha'])->format('d M') }}</p>
                                </div>
                                <span class="text-xs font-bold text-slate-650 shrink-0">${{ number_format($item['subtotal']) }}</span>
                            </div>
                        @endforeach
                    </div>

                    <!-- Desglose Financiero -->
                    <div class="flex flex-col gap-3 text-xs mb-2 font-semibold">
                        <div class="flex justify-between text-slate-550">
                            <span>Subtotal:</span>
                            <span>${{ number_format($total) }} MXN</span>
                        </div>
                        <div class="flex justify-between text-slate-550">
                            <span>Comisión de Transacción:</span>
                            <span class="text-emerald-605 font-bold">Gratis</span>
                        </div>
                        <div class="flex justify-between border-t border-slate-200 pt-3 font-bold text-slate-700 text-sm">
                            <span>Total a pagar:</span>
                            <span class="text-brand-teal font-black">${{ number_format($total) }} MXN</span>
                        </div>
                    </div>
                </div>
            </aside>
        </div>
    </div>

    <!-- SCRIPT PARA SIMULACIÓN DE CARGA Y COMPORTAMIENTO DE TARJETA -->
    <script>
        const form = document.getElementById('checkout-form');
        const overlay = document.getElementById('payment-overlay');

        if (form && overlay) {
            form.addEventListener('submit', (e) => {
                e.preventDefault(); // Evitar envío inmediato
                
                // Mostrar overlay con fade-in
                overlay.classList.remove('hidden');
                
                // Retrasar el envío real 2.5 segundos para simular el procesamiento bancario
                setTimeout(() => {
                    form.submit();
                }, 2500);
            });
        }

        // Formatear automáticamente el número de tarjeta (4 en 4 dígitos)
        const cardNumInput = document.getElementById('card_number_input');
        if (cardNumInput) {
            cardNumInput.addEventListener('input', (e) => {
                let v = e.target.value.replace(/\s+/g, '').replace(/[^0-9]/gi, '');
                let matches = v.match(/\d{4,16}/g);
                let match = matches && matches[0] || '';
                let parts = [];

                for (let i=0, len=match.length; i<len; i+=4) {
                    parts.push(match.substring(i, i+4));
                }

                if (parts.length > 0) {
                    e.target.value = parts.join(' ');
                } else {
                    e.target.value = v;
                }
            });
        }

        // Formatear expiración de tarjeta (MM/AA)
        const cardExpiryInput = document.getElementById('card_expiry_input');
        if (cardExpiryInput) {
            cardExpiryInput.addEventListener('input', (e) => {
                let v = e.target.value.replace(/[^0-9]/gi, '');
                if (v.length >= 2) {
                    e.target.value = v.substring(0, 2) + '/' + v.substring(2, 4);
                } else {
                    e.target.value = v;
                }
            });
        }
    </script>
@endsection
