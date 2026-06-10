@extends('layouts.app')

<!-- 
 * @file catalog.blade.php
 * @description Vista Blade para el catálogo de tours. Contiene filtros avanzados y el listado dinámico de tours disponibles.
 * @date 2026-06-08
 * @author Antigravity
-->

@section('title', 'Tours y Excursiones - Atti Tours')

@section('content')
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-10">
        
        <!-- CABECERA -->
        <div class="flex flex-col md:flex-row md:items-end justify-between gap-4 border-b border-slate-900 pb-6 mb-8">
            <div>
                <h1 class="text-3xl font-black tracking-tight text-white">
                    {{ __('navTours') }}
                </h1>
                <p class="text-xs text-slate-400 mt-1">
                    Encuentra la aventura perfecta para tus vacaciones en el Caribe Mexicano.
                </p>
            </div>
            
            <!-- Contador de resultados -->
            <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400 bg-slate-900 border border-slate-800 px-3 py-1.5 rounded-lg w-fit">
                Resultados: <span class="text-cyan-400 font-extrabold">{{ $tours->count() }}</span>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
            
            <!-- PANEL DE FILTROS LATERAL -->
            <aside class="lg:col-span-1 h-fit p-6 rounded-2xl border border-slate-900 bg-slate-950/80 backdrop-blur-md shadow-2xl sticky top-24">
                <div class="flex items-center justify-between border-b border-slate-900 pb-4 mb-5">
                    <h2 class="text-sm font-bold uppercase tracking-wider text-slate-200">Filtros</h2>
                    <a href="{{ route('catalog') }}" class="text-[10px] font-bold uppercase tracking-widest text-indigo-400 hover:text-indigo-300 transition-colors">
                        Limpiar
                    </a>
                </div>

                <form action="{{ route('catalog') }}" method="GET" class="flex flex-col gap-5">
                    
                    <!-- Búsqueda -->
                    <div class="flex flex-col gap-1.5">
                        <label class="text-[10px] font-bold uppercase tracking-wider text-slate-400">
                            Palabra Clave
                        </label>
                        <div class="relative">
                            <input type="text" name="search" value="{{ request('search') }}" placeholder="{{ __('searchPlaceholder') }}" class="w-full h-9 rounded-lg border border-slate-800 bg-slate-900 px-3 text-xs text-white placeholder-slate-500 focus:border-cyan-500 focus:ring-0 focus:outline-none">
                        </div>
                    </div>

                    <!-- Destino / Ubicación -->
                    <div class="flex flex-col gap-1.5">
                        <label class="text-[10px] font-bold uppercase tracking-wider text-slate-400">
                            {{ __('location') }}
                        </label>
                        <select name="location" class="w-full h-9 rounded-lg border border-slate-800 bg-slate-900 px-3 text-xs text-white focus:border-cyan-500 focus:ring-0 focus:outline-none cursor-pointer">
                            <option value="all">Todos los destinos</option>
                            @foreach($destinos as $dest)
                                <option value="{{ $dest }}" {{ request('location') === $dest ? 'selected' : '' }}>
                                    {{ $dest }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Fecha -->
                    <div class="flex flex-col gap-1.5">
                        <label class="text-[10px] font-bold uppercase tracking-wider text-slate-400">
                            {{ __('searchDate') }}
                        </label>
                        <input type="date" name="date" value="{{ request('date') }}" class="w-full h-9 rounded-lg border border-slate-800 bg-slate-900 px-3 text-xs text-white focus:border-cyan-500 focus:ring-0 focus:outline-none cursor-pointer" style="color-scheme: dark;">
                    </div>

                    <!-- Rango de Precio -->
                    <div class="flex flex-col gap-1.5">
                        <div class="flex items-center justify-between">
                            <label class="text-[10px] font-bold uppercase tracking-wider text-slate-400">
                                {{ __('searchPrice') }}
                            </label>
                            <span id="price-val" class="text-xs font-black text-cyan-400">
                                ${{ request('price', $precioMaximo) }} MXN
                            </span>
                        </div>
                        <input type="range" name="price" id="price-range" min="40" max="{{ $precioMaximo }}" step="5" value="{{ request('price', $precioMaximo) }}" class="w-full accent-cyan-500 cursor-pointer">
                    </div>

                    <!-- Botón Aplicar -->
                    <button type="submit" class="w-full h-10 inline-flex items-center justify-center rounded-lg bg-gradient-to-r from-cyan-500 to-indigo-600 hover:opacity-95 text-xs font-bold uppercase tracking-wider text-white shadow-lg cursor-pointer transition-all">
                        Filtrar
                    </button>
                </form>
            </aside>

            <!-- LISTADO DE TOURS -->
            <main class="lg:col-span-3">
                @if($tours->isEmpty())
                    <!-- Estado vacío -->
                    <div class="flex flex-col items-center justify-center text-center p-12 rounded-3xl border border-slate-900 bg-slate-950/40 shadow-xl">
                        <div class="p-4 rounded-full bg-slate-900 border border-slate-800 text-slate-500 mb-4 animate-float">
                            <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <h3 class="text-base font-bold text-white">No se encontraron tours</h3>
                        <p class="text-xs text-slate-400 mt-2 max-w-sm leading-relaxed">
                            Prueba ajustando los filtros de búsqueda, modificando las fechas o el precio máximo.
                        </p>
                        <a href="{{ route('catalog') }}" class="mt-5 inline-flex h-9 items-center justify-center px-4 rounded-lg bg-slate-900 hover:bg-slate-800 border border-slate-800 text-xs font-bold text-slate-200 transition-colors">
                            Ver Todos los Tours
                        </a>
                    </div>
                @else
                    <!-- Grid de tarjetas -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        @foreach($tours as $tour)
                            <div class="group flex flex-col rounded-2xl border border-slate-900 bg-slate-950/60 overflow-hidden shadow-2xl hover:border-slate-800 transition-all duration-300">
                                
                                <!-- Imagen -->
                                <div class="relative aspect-[16/10] overflow-hidden">
                                    <img src="{{ $tour->imagen_destacada }}" alt="{{ $tour->nombre }}" class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105">
                                    <div class="absolute inset-0 bg-gradient-to-t from-slate-950/50 to-transparent"></div>
                                    
                                    <!-- Badges -->
                                    <div class="absolute top-4 left-4 flex gap-2">
                                        <span class="px-2.5 py-1 text-[9px] font-black uppercase tracking-wider rounded-md bg-slate-950/80 backdrop-blur-md text-cyan-400 border border-slate-800">
                                            {{ $tour->ubicacion }}
                                        </span>
                                    </div>
                                    
                                    <div class="absolute bottom-4 right-4 px-3 py-1.5 rounded-xl bg-slate-950/80 backdrop-blur-md border border-slate-800 text-right">
                                        <p class="text-[8px] font-bold text-slate-400 uppercase tracking-widest">{{ __('priceFrom') }}</p>
                                        <p class="text-sm font-black text-white">${{ number_format($tour->precio_base_usd) }} MXN</p>
                                    </div>
                                </div>

                                <!-- Info del Tour -->
                                <div class="flex flex-col flex-grow p-6">
                                    <div class="flex items-center gap-3 text-[10px] text-slate-400 font-semibold mb-2">
                                        <span class="flex items-center gap-1">
                                            <svg class="h-3.5 w-3.5 text-cyan-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            {{ $tour->duracion }}
                                        </span>
                                        <span>&bull;</span>
                                        <span class="flex items-center gap-1">
                                            <svg class="h-3.5 w-3.5 text-cyan-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                            </svg>
                                            {{ __('maxCapacity') }}: {{ $tour->cupo_maximo }}
                                        </span>
                                    </div>

                                    <h3 class="text-base font-bold text-white group-hover:text-cyan-400 transition-colors line-clamp-1">
                                        {{ $tour->nombre }}
                                    </h3>
                                    <p class="text-xs text-slate-400 mt-2 line-clamp-2 leading-relaxed flex-grow">
                                        {{ $tour->resumen }}
                                    </p>

                                    <!-- Tags -->
                                    <div class="flex flex-wrap gap-1.5 mt-4 mb-5">
                                        @foreach($tour->tags as $tag)
                                            <span class="text-[9px] font-semibold text-slate-400 bg-slate-900 border border-slate-800/60 px-2 py-0.5 rounded">
                                                #{{ $tag }}
                                            </span>
                                        @endforeach
                                    </div>

                                    <!-- Botón Acción -->
                                    <a href="{{ route('tours.show', $tour->id) }}" class="w-full inline-flex h-10 items-center justify-center rounded-xl bg-slate-900 hover:bg-slate-800 border border-slate-800 hover:border-slate-700 text-xs font-bold text-slate-200 transition-colors cursor-pointer">
                                        {{ __('viewDetails') }}
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </main>
        </div>
    </div>

    <!-- SCRIPT DE FILTROS INTERACTIVOS -->
    <script>
        // Actualizar el valor de precio en tiempo real
        const range = document.getElementById('price-range');
        const priceVal = document.getElementById('price-val');
        if (range && priceVal) {
            range.addEventListener('input', (e) => {
                priceVal.textContent = `$${e.target.value} MXN`;
            });
        }
    </script>
@endsection
