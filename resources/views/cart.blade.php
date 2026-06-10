@extends('layouts.app')

<!-- 
 * @file cart.blade.php
 * @description Vista Blade para gestionar el carrito de compras. Muestra los tours seleccionados y el resumen de totales.
 * @date 2026-06-08
 * @author Antigravity
-->

@section('title', 'Tu Carrito - Atti Tours')

@section('content')
    <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8 py-10">
        
        <!-- CABECERA -->
        <div class="border-b border-slate-900 pb-6 mb-8">
            <h1 class="text-2xl sm:text-3xl font-black text-white">
                {{ __('cartTitle') }}
            </h1>
            <p class="text-xs text-slate-400 mt-1">
                Revisa los tours seleccionados antes de confirmar tus boletos.
            </p>
        </div>

        @if(empty($cart))
            <!-- CARRITO VACÍO -->
            <div class="flex flex-col items-center justify-center text-center p-16 rounded-3xl border border-slate-900 bg-slate-950/40 shadow-xl">
                <div class="p-4 rounded-full bg-slate-900 border border-slate-800 text-slate-500 mb-4 animate-float">
                    <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                    </svg>
                </div>
                <h3 class="text-base font-bold text-white">{{ __('cartEmpty') }}</h3>
                <p class="text-xs text-slate-400 mt-2 max-w-xs leading-relaxed">
                    Añade tours espectaculares a tu carrito y comienza a planificar tus vacaciones soñadas.
                </p>
                <a href="{{ route('catalog') }}" class="mt-6 inline-flex h-10 items-center justify-center px-6 rounded-xl bg-gradient-to-r from-cyan-500 to-indigo-600 font-bold text-xs uppercase tracking-wider text-white shadow-lg shadow-indigo-500/20 transition-all">
                    Ver Catálogo de Tours
                </a>
            </div>
        @else
            <!-- TABLA DE ITEMS / GRID -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                
                <!-- LISTADO DE ITEMS -->
                <div class="lg:col-span-2 flex flex-col gap-4">
                    @foreach($cart as $key => $item)
                        <div class="flex flex-col sm:flex-row gap-4 p-4 rounded-2xl border border-slate-900 bg-slate-950/40 shadow-xl">
                            
                            <!-- Imagen -->
                            <div class="shrink-0 w-full sm:w-32 aspect-[16/10] sm:aspect-square overflow-hidden rounded-xl border border-slate-900">
                                <img src="{{ $item['imagen'] }}" alt="{{ $item['nombre'] }}" class="h-full w-full object-cover">
                            </div>

                            <!-- Info -->
                            <div class="flex-grow flex flex-col justify-between py-1">
                                <div>
                                    <div class="flex items-start justify-between gap-4">
                                        <h3 class="text-sm font-bold text-white line-clamp-1">
                                            {{ $item['nombre'] }}
                                        </h3>
                                        <!-- Botón Quitar -->
                                        <a href="{{ route('cart.remove', $key) }}" class="text-slate-500 hover:text-rose-400 transition-colors" title="{{ __('remove') }}">
                                            <svg class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-4v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </a>
                                    </div>
                                    <p class="text-[10px] text-slate-500 font-semibold mt-1 flex items-center gap-1">
                                        <svg class="h-3.5 w-3.5 text-cyan-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                        {{ \Carbon\Carbon::parse($item['fecha'])->format('d M, Y') }}
                                    </p>
                                </div>

                                <!-- Controles Cantidad y Subtotal -->
                                <div class="flex flex-wrap items-end justify-between gap-4 mt-4">
                                    
                                    <!-- Formulario actualización de personas -->
                                    <form action="{{ route('cart.update') }}" method="POST" class="flex items-center gap-1.5 h-8 rounded-lg border border-slate-900 bg-slate-900/50 px-2">
                                        @csrf
                                        <input type="hidden" name="key" value="{{ $key }}">
                                        <label class="text-[9px] font-black uppercase text-slate-500 tracking-wider pr-1">
                                            Pasajeros:
                                        </label>
                                        <input type="number" name="cantidad" value="{{ $item['cantidad'] }}" min="1" onchange="this.form.submit()" class="w-10 text-center bg-transparent border-0 p-0 text-xs font-bold text-white focus:ring-0 focus:outline-none">
                                    </form>

                                    <!-- Precios -->
                                    <div class="text-right">
                                        <p class="text-[9px] text-slate-500 font-semibold">${{ number_format($item['precio_unitario']) }} x {{ $item['cantidad'] }}</p>
                                        <p class="text-sm font-black text-cyan-400">${{ number_format($item['subtotal']) }} MXN</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                    
                    <!-- Botones de compra -->
                    <div class="flex items-center justify-between mt-2">
                        <a href="{{ route('catalog') }}" class="inline-flex h-9 items-center justify-center px-4 rounded-lg bg-slate-900 hover:bg-slate-800 border border-slate-800 text-xs font-bold text-slate-200 transition-colors">
                            Seguir Comprando
                        </a>
                        <a href="{{ route('cart.clear') }}" class="inline-flex h-9 items-center justify-center px-4 rounded-lg hover:bg-slate-900 border border-transparent hover:border-slate-800 text-xs font-bold text-slate-400 hover:text-rose-400 transition-colors">
                            Vaciar Carrito
                        </a>
                    </div>
                </div>

                <!-- RESUMEN DE COMPRA (Derecha) -->
                <div class="lg:col-span-1 h-fit p-6 rounded-2xl border border-slate-900 bg-slate-950/80 backdrop-blur-md shadow-2xl">
                    <h2 class="text-sm font-bold uppercase tracking-wider text-slate-200 border-b border-slate-900 pb-4 mb-4">
                        Resumen de Compra
                    </h2>

                    <div class="flex flex-col gap-3 text-xs mb-6">
                        <div class="flex justify-between text-slate-400">
                            <span>Subtotal:</span>
                            <span>${{ number_format($total) }} MXN</span>
                        </div>
                        <div class="flex justify-between text-slate-400">
                            <span>Impuestos / Cargos:</span>
                            <span class="text-emerald-400 font-semibold">Gratis</span>
                        </div>
                        <div class="flex justify-between border-t border-slate-900 pt-3 font-bold text-white text-sm">
                            <span>{{ __('cartTotal') }}:</span>
                            <span class="text-cyan-400">${{ number_format($total) }} MXN</span>
                        </div>
                    </div>

                    <!-- Botón Hacia Checkout -->
                    <a href="{{ route('checkout.index') }}" class="w-full h-11 inline-flex items-center justify-center rounded-xl bg-gradient-to-r from-cyan-500 to-indigo-600 hover:opacity-95 text-xs font-bold uppercase tracking-widest text-white shadow-lg cursor-pointer transition-all">
                        {{ __('checkoutBtn') }}
                    </a>
                </div>
            </div>
        @endif
    </div>
@endsection
