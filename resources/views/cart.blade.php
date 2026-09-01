@extends('layouts.app')

<!-- 
 * @file cart.blade.php
 * @description Vista Blade para gestionar el carrito de compras. Muestra los tours seleccionados, diferencias entre compartidos y privados, y el resumen de totales desglosado. Adaptado a tema claro con colores corporativos e identidad de la marca.
 * @date 2026-07-31
 * @author Antigravity
 -->

@section('title', __('cartTitle') . ' - Attitour')

@section('content')
    @php
        $totalGeneral = 0;
        foreach($cart as $item) {
            $totalGeneral += (float) $item['subtotal'];
        }
        $totalOnline = $totalGeneral;
    @endphp
    <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8 py-10">
        
        <!-- CABECERA -->
        <div class="border-b border-slate-200 pb-6 mb-8">
            <h1 class="text-2xl sm:text-3xl font-black text-slate-800">
                {{ __('cartTitle') }}
            </h1>
            <p class="text-xs text-slate-500 mt-1 font-semibold">
                {{ __('cartSubtitle') }}
            </p>
        </div>

        @if(empty($cart))
            <!-- CARRITO VACÍO -->
            <div class="flex flex-col items-center justify-center text-center p-16 rounded-3xl border border-slate-200 bg-white shadow-md">
                <div class="p-4 rounded-full bg-slate-50 border border-slate-200 text-slate-400 mb-4 animate-float">
                    <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                    </svg>
                </div>
                <h3 class="text-base font-bold text-slate-800">{{ __('cartEmpty') }}</h3>
                <p class="text-xs text-slate-500 mt-2 max-w-xs leading-relaxed font-semibold">
                    {{ __('cartEmptyBody') }}
                </p>
                <a href="{{ route('catalog') }}" class="mt-6 inline-flex h-10 items-center justify-center px-6 rounded-xl bg-gradient-to-r from-brand-teal to-brand-teal-hover font-bold text-xs uppercase tracking-wider text-white shadow-md shadow-brand-teal/15 transition-all">
                    {{ __('viewCatalogBtn') }}
                </a>
            </div>
        @else
            <!-- TABLA DE ITEMS / GRID -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                
                <!-- LISTADO DE ITEMS -->
                <div class="lg:col-span-2 flex flex-col gap-4">
                    @foreach($cart as $key => $item)
                        <div class="flex flex-col sm:flex-row gap-4 p-4 rounded-2xl border border-slate-200 bg-white shadow-md">
                            
                            <!-- Imagen -->
                            <div class="shrink-0 w-full sm:w-32 aspect-[16/10] sm:aspect-square overflow-hidden rounded-xl border border-slate-200">
                                <img src="{{ $item['imagen'] }}" alt="{{ $item['nombre'] }}" class="h-full w-full object-cover">
                            </div>

                            <!-- Info -->
                            <div class="flex-grow flex flex-col justify-between py-1">
                                <div>
                                    <div class="flex items-start justify-between gap-4">
                                        <div>
                                            <h3 class="text-sm font-bold text-slate-800 line-clamp-1">
                                                {{ $item['nombre'] }}
                                            </h3>
                                            @if(!empty($item['es_privado']))
                                                <span class="inline-flex items-center gap-1 mt-1 px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-brand-orange/10 text-brand-orange border border-brand-orange/20">
                                                    {{ __('privateTourBadge') }}
                                                </span>
                                            @else
                                                <span class="inline-flex items-center gap-1 mt-1 px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-brand-teal/10 text-brand-teal border border-brand-teal/20">
                                                    {{ __('sharedTourBadge') }}
                                                </span>
                                            @endif
                                        </div>
                                        <!-- Botón Quitar -->
                                        <a href="{{ route('cart.remove', $key) }}" class="text-slate-400 hover:text-rose-600 transition-colors cursor-pointer" title="{{ __('remove') }}">
                                            <svg class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-4v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </a>
                                    </div>
                                    <p class="text-[10px] text-slate-500 font-bold mt-1.5 flex items-center gap-1">
                                        <svg class="h-3.5 w-3.5 text-brand-teal" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                        {{ \Carbon\Carbon::parse($item['fecha'])->locale(app()->getLocale())->translatedFormat('d M, Y') }}
                                        @if(!empty($item['horario']))
                                            · {{ $item['horario'] }} {{ __('hoursSuffix') }}
                                        @endif
                                    </p>
                                    @if(isset($item['cantidad_adultos']))
                                        <p class="text-[9px] text-slate-500 font-semibold mt-1">
                                            🏨 {{ $item['hotel_nombre'] ?? '—' }}
                                            @if(!empty($item['pickup_horario'])) · {{ __('pickupLabel') }} {{ $item['pickup_horario'] }} @endif
                                            @if(!empty($item['idioma_seleccionado'])) · {{ __('languageInlineLabel') }} {{ $item['idioma_seleccionado'] }} @endif
                                        </p>
                                    @endif
                                </div>

                                <!-- Controles Cantidad y Subtotal -->
                                <div class="flex flex-wrap items-end justify-between gap-4 mt-4">

                                    @if(isset($item['cantidad_adultos']))
                                        <!-- Desglose de pasajeros (no editable aquí; quitar y volver a agregar para cambiar) -->
                                        <div class="flex items-center gap-1.5 h-8 rounded-lg border border-slate-200 bg-slate-50 px-2.5 text-[9px] font-bold text-slate-600">
                                            👤 {{ $item['cantidad_adultos'] }} {{ __('adultsAbbrev') }}
                                            @if(($item['cantidad_menores'] ?? 0) > 0) · {{ $item['cantidad_menores'] }} {{ __('minorsAbbrev') }} @endif
                                            @if(($item['cantidad_infantes'] ?? 0) > 0) · {{ $item['cantidad_infantes'] }} {{ __('infantsAbbrev') }} @endif
                                        </div>
                                    @else
                                        <!-- Formulario actualización de personas -->
                                        <form action="{{ route('cart.update') }}" method="POST" class="flex items-center gap-1.5 h-8 rounded-lg border border-slate-200 bg-slate-50 px-2">
                                            @csrf
                                            <input type="hidden" name="key" value="{{ $key }}">
                                            <label class="text-[9px] font-bold uppercase text-slate-500 tracking-wider pr-1">
                                                {{ __('passengersLabel') }}
                                            </label>
                                            <input type="number" name="cantidad" value="{{ $item['cantidad'] }}" min="1" onchange="this.form.submit()" class="w-10 text-center bg-transparent border-0 p-0 text-xs font-bold text-slate-800 focus:ring-0 focus:outline-none">
                                        </form>
                                    @endif

                                    <!-- Precios -->
                                    <div class="text-right font-semibold">
                                        @if(!empty($item['es_privado']))
                                            <p class="text-[9px] text-slate-500">{{ __('groupTotalLabel', ['pax' => $item['cantidad']]) }}</p>
                                            <p class="text-sm font-black text-brand-teal">${{ number_format($item['subtotal']) }} USD</p>
                                            <x-currency-note :usd="$item['subtotal']" />
                                        @else
                                            <p class="text-[9px] text-slate-500">${{ number_format($item['precio_unitario']) }} x {{ $item['cantidad'] }}</p>
                                            <p class="text-sm font-black text-brand-teal">${{ number_format($item['subtotal']) }} USD</p>
                                            <x-currency-note :usd="$item['subtotal']" />
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                    
                    <!-- Botones de compra -->
                    <div class="flex items-center justify-between mt-2">
                        <a href="{{ route('catalog') }}" class="inline-flex h-9 items-center justify-center px-4 rounded-lg bg-slate-50 hover:bg-slate-100 border border-slate-200 text-xs font-bold text-slate-650 hover:text-slate-850 transition-colors shadow-xs">
                            {{ __('continueShoppingBtn') }}
                        </a>
                        <a href="{{ route('cart.clear') }}" class="inline-flex h-9 items-center justify-center px-4 rounded-lg hover:bg-rose-50 border border-transparent text-slate-500 hover:text-rose-600 transition-colors">
                            {{ __('clearCartBtn') }}
                        </a>
                    </div>
                </div>

                <!-- RESUMEN DE COMPRA (Derecha) -->
                <div class="lg:col-span-1 h-fit p-6 rounded-2xl border border-slate-200 bg-white shadow-lg">
                    <h2 class="text-sm font-bold uppercase tracking-wider text-slate-800 border-b border-slate-200 pb-4 mb-4">
                        {{ __('orderSummaryTitle') }}
                    </h2>

                    <div class="flex flex-col gap-3 text-xs mb-6 font-semibold">
                        <div class="flex justify-between text-slate-500">
                            <span>{{ __('totalTripAmountLabel') }}</span>
                            <span>${{ number_format($totalGeneral) }} USD</span>
                        </div>

                        <div class="flex justify-between text-slate-500 border-b border-slate-100 pb-2">
                            <span>{{ __('onlineChargesLabel') }}</span>
                            <span>100%</span>
                        </div>

                        <div class="flex justify-between text-slate-500">
                            <span>{{ __('taxesLabel') }}</span>
                            <span class="text-emerald-600 font-bold">{{ __('freeLabel') }}</span>
                        </div>

                        <div class="flex justify-between border-t border-slate-200 pt-3 font-bold text-slate-700 text-sm">
                            <span>{{ __('totalToPayTodayLabel') }}</span>
                            <span class="text-right">
                                <span class="text-brand-teal font-black">${{ number_format($totalOnline) }} USD</span>
                                <x-currency-note :usd="$totalOnline" />
                            </span>
                        </div>
                    </div>

                    <!-- Botón Hacia Checkout -->
                    <a href="{{ route('checkout.index') }}" class="w-full h-11 inline-flex items-center justify-center rounded-xl bg-gradient-to-r from-brand-orange to-brand-orange-hover hover:opacity-95 text-xs font-bold uppercase tracking-widest text-white shadow-md shadow-brand-orange/15 cursor-pointer transition-all hover:scale-[1.01]">
                        {{ __('checkoutBtn') }}
                    </a>
                </div>
            </div>
        @endif
    </div>
@endsection
