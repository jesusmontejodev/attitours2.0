@extends('layouts.app')

<!-- 
 * @file home.blade.php
 * @description Vista Blade para la página de inicio. Incluye Hero, barra de búsqueda, destinos y tours destacados. Adaptada a tema claro con colores corporativos e identidad de la marca.
 * @date 2026-06-29
 * @author Antigravity
 -->

@section('title', 'Atti Tours - Descubre Experiencias Inolvidables')

@section('content')
    <!-- HERO SECTION -->
    <section class="relative pt-24 pb-20 lg:pt-32 lg:pb-28 flex items-center justify-center z-20">
        <!-- Fondos con gradientes decorativos -->
        <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_top,rgba(0,122,99,0.06),transparent_60%)]"></div>
        <div class="absolute top-20 left-10 w-72 h-72 rounded-full bg-brand-teal/5 blur-3xl animate-float"></div>
        <div class="absolute bottom-10 right-10 w-96 h-96 rounded-full bg-brand-orange/5 blur-3xl animate-float" style="animation-delay: -2s;"></div>

        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 relative text-center">
            <h1 class="text-4xl font-extrabold tracking-tight sm:text-6xl text-slate-800 leading-tight">
                {{ __('heroTitle') }}
            </h1>
            <p class="mx-auto mt-6 max-w-2xl text-base sm:text-lg text-slate-500 font-semibold">
                {{ __('heroSubtitle') }}
            </p>

            <!-- BUSCADOR INTEGRADO -->
            <div class="mx-auto mt-10 max-w-4xl p-2 rounded-2xl border border-slate-200 bg-white shadow-xl backdrop-blur-md relative z-30">
                <form action="{{ route('catalog') }}" method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-2 items-center">
                    
                    <!-- Destino -->
                    <div class="flex flex-col items-start px-4 py-2 border-b md:border-b-0 md:border-r border-slate-200">
                        <label class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">
                            {{ __('searchLocation') }}
                        </label>
                        <select name="location" class="w-full bg-transparent border-0 p-0 text-sm font-semibold text-slate-700 focus:ring-0 focus:outline-none cursor-pointer">
                            <option value="all">Todos los destinos</option>
                            @foreach($destinos as $dest)
                                <option value="{{ $dest }}">{{ $dest }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Fecha (Selector de Calendario Moderno Tipo Airbnb) -->
                    <div class="relative flex flex-col items-start px-4 py-2 border-b md:border-b-0 md:border-r border-slate-200 cursor-pointer" id="filter-date-wrapper">
                        <label class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">
                            {{ __('searchDate') }}
                        </label>
                        <input type="text" id="filter-date-display" readonly placeholder="¿Cuándo viajas?" class="w-full bg-transparent border-0 p-0 text-sm font-semibold text-slate-700 focus:ring-0 focus:outline-none cursor-pointer">
                        <input type="hidden" name="date" id="filter-date-value">
                        <input type="hidden" name="flexibility" id="filter-flexibility-value" value="0">
                        <input type="hidden" name="flexible_months" id="filter-flexible-months-value" value="">

                        <!-- Dropdown del Calendario Moderno Tipo Airbnb -->
                        <div id="airbnb-filter-calendar" class="hidden absolute top-full left-1/2 -translate-x-1/2 md:translate-x-0 md:left-0 mt-3 z-[100] w-[92vw] sm:w-[480px] md:w-[650px] p-5 rounded-3xl bg-white border border-slate-200 shadow-2xl animate-fade-in text-left cursor-default">
                            <!-- Pestañas Superiores -->
                            <div class="flex justify-center mb-5">
                                <div class="inline-flex bg-slate-100 p-1 rounded-full border border-slate-200">
                                    <button type="button" id="tab-fechas" class="px-5 py-1.5 rounded-full text-xs font-bold text-slate-800 bg-white shadow-xs transition-all duration-200 cursor-pointer">
                                        Fechas
                                    </button>
                                    <button type="button" id="tab-flexible" class="px-5 py-1.5 rounded-full text-xs font-semibold text-slate-500 hover:text-slate-800 transition-all duration-200 cursor-pointer">
                                        Flexible
                                    </button>
                                </div>
                            </div>

                            <!-- Pestaña Fechas (Dos Meses Lado a Lado) -->
                            <div id="content-fechas" class="block">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <!-- Mes 1 -->
                                    <div>
                                        <div class="flex items-center justify-between mb-4">
                                            <button type="button" id="prev-month-airbnb" class="p-1.5 rounded-full hover:bg-slate-100 text-slate-650 hover:text-brand-teal transition-colors cursor-pointer">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/></svg>
                                            </button>
                                            <span id="label-month-1" class="text-xs font-black text-slate-850 uppercase tracking-widest"></span>
                                            <!-- Botón siguiente exclusivo para móviles -->
                                            <button type="button" id="next-month-airbnb-mobile" class="p-1.5 rounded-full hover:bg-slate-100 text-slate-655 hover:text-brand-teal transition-colors cursor-pointer md:hidden">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
                                            </button>
                                            <div class="w-7 hidden md:block"></div> <!-- Spacer en desktop -->
                                        </div>
                                        <div class="grid grid-cols-7 gap-1 text-center text-[9px] font-bold text-slate-400 uppercase tracking-widest mb-3">
                                            <div>D</div><div>L</div><div>M</div><div>M</div><div>J</div><div>V</div><div>S</div>
                                        </div>
                                        <div id="grid-month-1" class="grid grid-cols-7 gap-1"></div>
                                    </div>
                                    
                                    <!-- Mes 2 (Oculto en celular) -->
                                    <div class="hidden md:block">
                                        <div class="flex items-center justify-between mb-4">
                                            <div class="w-7"></div> <!-- Spacer -->
                                            <span id="label-month-2" class="text-xs font-black text-slate-850 uppercase tracking-widest"></span>
                                            <button type="button" id="next-month-airbnb" class="p-1.5 rounded-full hover:bg-slate-100 text-slate-650 hover:text-brand-teal transition-colors cursor-pointer">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
                                            </button>
                                        </div>
                                        <div class="grid grid-cols-7 gap-1 text-center text-[9px] font-bold text-slate-455 uppercase tracking-widest mb-3">
                                            <div>D</div><div>L</div><div>M</div><div>M</div><div>J</div><div>V</div><div>S</div>
                                        </div>
                                        <div id="grid-month-2" class="grid grid-cols-7 gap-1"></div>
                                    </div>
                                </div>

                                <!-- Tira de Flexibilidad -->
                                <div class="mt-6 pt-4 border-t border-slate-100">
                                    <span class="text-[9px] font-black uppercase tracking-widest text-slate-450 block mb-2">Tolerancia de fechas</span>
                                    <div class="flex flex-wrap gap-1.5">
                                        <button type="button" data-flex="0" class="flexibility-btn active text-[10px] font-bold border border-slate-200 bg-white px-3 py-1.5 rounded-full hover:border-slate-800 cursor-pointer">Fechas exactas</button>
                                        <button type="button" data-flex="1" class="flexibility-btn text-[10px] font-bold border border-slate-200 bg-white px-3 py-1.5 rounded-full hover:border-slate-800 cursor-pointer">± 1 día</button>
                                        <button type="button" data-flex="2" class="flexibility-btn text-[10px] font-bold border border-slate-200 bg-white px-3 py-1.5 rounded-full hover:border-slate-800 cursor-pointer">± 2 días</button>
                                        <button type="button" data-flex="3" class="flexibility-btn text-[10px] font-bold border border-slate-200 bg-white px-3 py-1.5 rounded-full hover:border-slate-800 cursor-pointer">± 3 días</button>
                                        <button type="button" data-flex="7" class="flexibility-btn text-[10px] font-bold border border-slate-200 bg-white px-3 py-1.5 rounded-full hover:border-slate-800 cursor-pointer">± 7 días</button>
                                        <button type="button" data-flex="14" class="flexibility-btn text-[10px] font-bold border border-slate-200 bg-white px-3 py-1.5 rounded-full hover:border-slate-800 cursor-pointer">± 14 días</button>
                                    </div>
                                </div>
                            </div>

                            <!-- Pestaña Flexible (Selección de Meses Completos) -->
                            <div id="content-flexible" class="hidden">
                                <span class="text-[9px] font-black uppercase tracking-widest text-slate-450 block text-center mb-3">¿Cuándo quieres viajar?</span>
                                <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 mb-5" id="flexible-months-container">
                                    <!-- Se generará dinámicamente con JS para los próximos 6 meses -->
                                </div>
                                <div class="p-3 bg-slate-50 border border-slate-200 rounded-2xl text-[10px] text-slate-500 font-semibold text-center leading-relaxed">
                                    ✈️ Selecciona uno o más meses para explorar todos los viajes programados de ese período.
                                </div>
                            </div>

                            <!-- Botones de Acción del Calendario -->
                            <div class="flex items-center justify-between border-t border-slate-150 pt-4 mt-5">
                                <button type="button" id="clear-airbnb-calendar" class="text-xs font-bold text-slate-500 hover:text-slate-800 hover:underline cursor-pointer">
                                    Limpiar
                                </button>
                                <button type="button" id="apply-airbnb-calendar" class="h-9 px-5 rounded-xl bg-slate-900 text-xs font-bold text-white hover:bg-slate-800 transition-colors shadow-md shadow-slate-950/15 cursor-pointer">
                                    Aplicar
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Precio Máximo -->
                    <div class="flex flex-col items-start px-4 py-2 border-b md:border-b-0 md:border-r border-slate-200">
                        <label class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">
                            {{ __('searchPrice') }}
                        </label>
                        <select name="price" class="w-full bg-transparent border-0 p-0 text-sm font-semibold text-slate-700 focus:ring-0 focus:outline-none cursor-pointer">
                            <option value="">Cualquier precio</option>
                            <option value="1000">Hasta $1,000 USD</option>
                            <option value="2000">Hasta $2,000 USD</option>
                            <option value="3000">Hasta $3,000 USD</option>
                        </select>
                    </div>

                    <!-- Botón Buscar -->
                    <div class="px-2 py-2">
                        <button type="submit" class="w-full inline-flex h-11 items-center justify-center rounded-xl bg-gradient-to-r from-brand-teal to-brand-teal-hover hover:opacity-95 font-bold text-xs uppercase tracking-wider text-white shadow-md shadow-brand-teal/15 transition-all cursor-pointer">
                            {{ __('searchBtn') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </section>

    <!-- SECCIÓN DESTINOS POPULARES -->
    @if($destinosDestacados->isNotEmpty())
    <section class="py-16 bg-slate-100/50 border-y border-slate-200/60">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <h2 class="text-2xl font-bold tracking-tight sm:text-3xl text-slate-800">Explora Destinos Increíbles</h2>
                <p class="mt-2 text-xs uppercase tracking-widest font-bold text-brand-teal">Elige tu propia aventura</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                @foreach($destinosDestacados as $destino)
                    <a href="{{ route('catalog', ['location' => $destino->ubicacion]) }}" class="group relative overflow-hidden rounded-2xl aspect-[4/5] border border-slate-200 shadow-md transition-all hover:border-slate-350 hover:shadow-lg">
                        <img src="{{ $destino->imagen_destacada }}" alt="{{ $destino->ubicacion }}" class="absolute inset-0 h-full w-full object-cover transition-transform duration-500 group-hover:scale-105">
                        <div class="absolute inset-0 bg-gradient-to-t from-slate-900/80 via-slate-900/20 to-transparent"></div>
                        <div class="absolute bottom-5 left-5 right-5">
                            <p class="text-[10px] font-bold tracking-widest text-brand-teal uppercase">{{ $destino->pais }}</p>
                            <h3 class="text-lg font-bold text-white mt-1">{{ ucwords(strtolower($destino->ubicacion)) }}</h3>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    </section>
    @endif

    <!-- SECCIÓN TOURS DESTACADOS -->
    <section class="py-16">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <h2 class="text-2xl font-bold tracking-tight sm:text-3xl text-slate-800">{{ __('featuredTours') }}</h2>
                <p class="mt-2 text-xs font-semibold text-slate-500">{{ __('featuredSub') }}</p>
            </div>

            <!-- GRID DE TARJETAS -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                @foreach($tours as $tour)
                    <div class="group flex flex-col rounded-2xl border border-slate-200 bg-white overflow-hidden shadow-md hover:border-slate-300 hover:shadow-lg transition-all duration-300">
                        
                        <!-- Imagen con efecto hover y precio flotante -->
                        <div class="relative aspect-[16/10] overflow-hidden">
                            <img src="{{ $tour->imagen_destacada }}" alt="{{ $tour->nombre }}" class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105">
                            <div class="absolute inset-0 bg-gradient-to-t from-slate-950/20 to-transparent"></div>
                            
                            <!-- Badges flotantes -->
                            <div class="absolute top-4 left-4 flex gap-2">
                                <span class="px-2.5 py-1 text-[9px] font-bold uppercase tracking-wider rounded-md bg-white/95 backdrop-blur-xs text-brand-teal border border-slate-200/80 shadow-xs">
                                    {{ $tour->ubicacion }}
                                </span>
                            </div>
                            
                            <div class="absolute bottom-4 right-4 px-3 py-1.5 rounded-xl bg-white/95 backdrop-blur-xs border border-slate-200/80 text-right shadow-xs">
                                <p class="text-[8px] font-bold text-slate-500 uppercase tracking-widest">{{ __('priceFrom') }}</p>
                                <p class="text-sm font-black text-brand-teal">${{ number_format($tour->precio_base_usd) }} USD</p>
                                <x-currency-note :usd="$tour->precio_base_usd" />
                            </div>
                        </div>

                        <!-- Info del Tour -->
                        <div class="flex flex-col flex-grow p-6">
                            <div class="flex items-center gap-3 text-[10px] text-slate-500 font-semibold mb-2">
                                <span class="flex items-center gap-1">
                                    <svg class="h-3.5 w-3.5 text-brand-teal" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    {{ $tour->duracion }}
                                </span>
                                <span>&bull;</span>
                                <span class="flex items-center gap-1">
                                    <svg class="h-3.5 w-3.5 text-brand-teal" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                    </svg>
                                    {{ __('maxCapacity') }}: {{ $tour->cupo_maximo }}
                                </span>
                            </div>

                            <h3 class="text-base font-bold text-slate-800 group-hover:text-brand-teal transition-colors line-clamp-1">
                                {{ $tour->nombre }}
                            </h3>
                            <p class="text-xs text-slate-600 mt-2 line-clamp-2 leading-relaxed flex-grow font-semibold">
                                {{ $tour->resumen }}
                            </p>

                            <!-- Tags -->
                            <div class="flex flex-wrap gap-1.5 mt-4 mb-5">
                                @foreach($tour->tags as $tag)
                                    <span class="text-[9px] font-bold text-slate-505 bg-slate-50 border border-slate-200 px-2 py-0.5 rounded">
                                        #{{ $tag }}
                                    </span>
                                @endforeach
                            </div>

                            <!-- Botón Acción -->
                            <a href="{{ route('tours.show', $tour->id) }}" class="w-full inline-flex h-10 items-center justify-center rounded-xl bg-slate-50 hover:bg-slate-100 border border-slate-200 text-xs font-bold text-slate-650 hover:text-slate-850 transition-colors shadow-xs cursor-pointer">
                                {{ __('viewDetails') }}
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <!-- PROPUESTA VALOR -->
    <section class="py-16 bg-slate-50 border-y border-slate-200">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <!-- Cancelación Flexible -->
                <div class="flex flex-col items-center text-center p-6 bg-white rounded-2xl border border-slate-200 shadow-xs">
                    <div class="p-3 rounded-full bg-brand-teal/10 text-brand-teal mb-4 animate-float">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                        </svg>
                    </div>
                    <h3 class="text-sm font-bold text-slate-800">Reserva Flexible</h3>
                    <p class="text-xs text-slate-600 mt-2 leading-relaxed font-semibold">
                        Modifica o cancela tu itinerario sin cargos ocultos hasta 24 horas antes del tour.
                    </p>
                </div>

                <!-- Guías Locales -->
                <div class="flex flex-col items-center text-center p-6 bg-white rounded-2xl border border-slate-200 shadow-xs">
                    <div class="p-3 rounded-full bg-brand-orange/10 text-brand-orange mb-4 animate-float" style="animation-delay:-1.3s;">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                    </div>
                    <h3 class="text-sm font-bold text-slate-800">Guías Expertos</h3>
                    <p class="text-xs text-slate-600 mt-2 leading-relaxed font-semibold">
                        Explora la cultura, historia y bellezas naturales con guías expertos bilingües.
                    </p>
                </div>

                <!-- Precios Oficiales -->
                <div class="flex flex-col items-center text-center p-6 bg-white rounded-2xl border border-slate-200 shadow-xs">
                    <div class="p-3 rounded-full bg-brand-teal/10 text-brand-teal mb-4 animate-float" style="animation-delay:-2.6s;">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <h3 class="text-sm font-bold text-slate-800">Mejor Precio Garantizado</h3>
                    <p class="text-xs text-slate-600 mt-2 leading-relaxed font-semibold">
                        Operamos directamente nuestros tours de forma local, ofreciendo las mejores tarifas del mercado.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- OPINIONES DE CLIENTES (CAROUSEL NATIVO) -->
    <section class="py-16">
        <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <h2 class="text-2xl font-bold tracking-tight sm:text-3xl text-slate-800 font-black">Lo que Dicen Nuestros Viajeros</h2>
                <p class="mt-2 text-xs uppercase tracking-widest font-bold text-brand-orange">Opiniones Reales</p>
            </div>

            <!-- Contenedor del Testimonio Activo -->
            <div class="relative bg-white rounded-3xl border border-slate-200 p-8 md:p-12 shadow-md overflow-hidden">
                <div class="absolute top-0 right-0 p-8 text-8xl text-brand-orange/10 font-serif leading-none select-none">“</div>
                
                <div id="testimonial-container" class="transition-all duration-300">
                    <p id="t-text" class="text-sm md:text-base text-slate-650 italic leading-relaxed font-semibold">
                        "El tour de snorkel en Cancún fue extraordinario. Los instructores fueron súper pacientes y pudimos ver tres tortugas marinas gigantescas de cerca. El catamarán e Isla Mujeres también son un sueño. ¡Volveré seguro con Atti Tours!"
                    </p>
                    <div class="mt-8 flex items-center gap-4">
                        <div class="h-10 w-10 rounded-full bg-brand-teal/10 text-brand-teal border border-brand-teal/20 flex items-center justify-center font-bold text-xs">
                            MP
                        </div>
                        <div>
                            <p id="t-author" class="text-xs font-bold text-slate-800">María Prieto</p>
                            <p id="t-location" class="text-[9px] text-slate-500 uppercase tracking-wider font-bold">España</p>
                        </div>
                    </div>
                </div>

                <!-- Botones Navegación Carousel -->
                <div class="absolute bottom-8 right-8 flex gap-2">
                    <button onclick="prevTestimonial()" class="p-2 rounded-lg bg-white border border-slate-200 text-slate-600 hover:text-brand-teal hover:bg-slate-50 transition-all shadow-xs cursor-pointer">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                    </button>
                    <button onclick="nextTestimonial()" class="p-2 rounded-lg bg-white border border-slate-200 text-slate-600 hover:text-brand-teal hover:bg-slate-50 transition-all shadow-xs cursor-pointer">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </section>

    <!-- SCRIPT DEL CAROUSEL NATIVO -->
    <script>
        const testimonials = [
            {
                text: '"El tour de snorkel en Cancún fue extraordinario. Los instructores fueron súper pacientes y pudimos ver tres tortugas marinas gigantescas de cerca. El catamarán e Isla Mujeres también son un sueño. ¡Volveré seguro con Atti Tours!"',
                author: 'María Prieto',
                location: 'España',
                initials: 'MP'
            },
            {
                text: '"图伦古城和圣井的行程太棒了！导游讲解非常详细，午餐自助非常美味。我们还在神秘的天然井中游泳，这是一次超凡的体验。强烈推荐 Atti Tours！"',
                author: 'Chen Wei',
                location: 'China',
                initials: 'CW'
            },
            {
                text: '"Contoy Island is an absolute paradise! It is completely untouched and wild. The number of nesting birds was incredible to see. Big thanks to the biologist guide for explaining the ecosystem so well. Worth every dollar."',
                author: 'John Miller',
                location: 'Estados Unidos',
                initials: 'JM'
            }
        ];

        let currentIndex = 0;

        function updateTestimonial() {
            const container = document.getElementById('testimonial-container');
            const textEl = document.getElementById('t-text');
            const authorEl = document.getElementById('t-author');
            const locEl = document.getElementById('t-location');
            const initialsEl = container.querySelector('div.h-10');

            container.style.opacity = '0';
            container.style.transform = 'translateX(5px)';

            setTimeout(() => {
                const item = testimonials[currentIndex];
                textEl.textContent = item.text;
                authorEl.textContent = item.author;
                locEl.textContent = item.location;
                initialsEl.textContent = item.initials;
                
                container.style.opacity = '1';
                container.style.transform = 'translateX(0)';
            }, 200);
        }

        function nextTestimonial() {
            currentIndex = (currentIndex + 1) % testimonials.length;
            updateTestimonial();
        }

        function prevTestimonial() {
            currentIndex = (currentIndex - 1 + testimonials.length) % testimonials.length;
            updateTestimonial();
        }

        // Cambio automático cada 6 segundos
        setInterval(nextTestimonial, 6000);

        // ============================================================
        // LÓGICA DEL CALENDARIO DE BÚSQUEDA ESTILO AIRBNB (HOME)
        // ============================================================
        // Autor: Antigravity
        // Última modificación: 2026-07-24
        
        let filterActiveTab = 'fechas'; // 'fechas' o 'flexible'
        let filterSelectedDate = null;  // Objeto Date o string Y-m-d
        let filterFlexibility = 0;      // En días
        let filterFlexibleMonths = [];  // Array de meses seleccionados ["2026-07"]
        
        let filterCalYear = new Date().getFullYear();
        let filterCalMonth = new Date().getMonth() + 1; // 1-12
        const todayStr = new Date().toISOString().split('T')[0];
        const filterToday = new Date(todayStr + 'T00:00:00');
        
        const filterWrapper = document.getElementById('filter-date-wrapper');
        const filterDisplay = document.getElementById('filter-date-display');
        const filterDropdown = document.getElementById('airbnb-filter-calendar');
        
        const tabFechas = document.getElementById('tab-fechas');
        const tabFlexible = document.getElementById('tab-flexible');
        const contentFechas = document.getElementById('content-fechas');
        const contentFlexible = document.getElementById('content-flexible');
        
        const labelMonth1 = document.getElementById('label-month-1');
        const labelMonth2 = document.getElementById('label-month-2');
        const gridMonth1 = document.getElementById('grid-month-1');
        const gridMonth2 = document.getElementById('grid-month-2');
        const prevMonthBtn = document.getElementById('prev-month-airbnb');
        const nextMonthBtn = document.getElementById('next-month-airbnb');
        const nextMonthBtnMobile = document.getElementById('next-month-airbnb-mobile');
        
        const clearCalBtn = document.getElementById('clear-airbnb-calendar');
        const applyCalBtn = document.getElementById('apply-airbnb-calendar');
        const hiddenDate = document.getElementById('filter-date-value');
        const hiddenFlex = document.getElementById('filter-flexibility-value');
        const hiddenMonths = document.getElementById('filter-flexible-months-value');
        
        // Alternar visualización del dropdown
        filterDisplay.addEventListener('click', (e) => {
            e.stopPropagation();
            filterDropdown.classList.toggle('hidden');
            if (!filterDropdown.classList.contains('hidden')) {
                renderFilterCalendars();
                renderFlexibleMonths();
            }
        });
        
        filterWrapper.addEventListener('click', (e) => {
            e.stopPropagation();
        });
        
        document.addEventListener('click', () => {
            filterDropdown.classList.add('hidden');
        });
        
        // Cambio de pestañas
        tabFechas.addEventListener('click', () => {
            filterActiveTab = 'fechas';
            tabFechas.classList.add('bg-white', 'text-slate-800', 'shadow-xs');
            tabFechas.classList.remove('text-slate-500');
            tabFlexible.classList.remove('bg-white', 'text-slate-800', 'shadow-xs');
            tabFlexible.classList.add('text-slate-500');
            contentFechas.classList.remove('hidden');
            contentFlexible.classList.add('hidden');
        });
        
        tabFlexible.addEventListener('click', () => {
            filterActiveTab = 'flexible';
            tabFlexible.classList.add('bg-white', 'text-slate-800', 'shadow-xs');
            tabFlexible.classList.remove('text-slate-500');
            tabFechas.classList.remove('bg-white', 'text-slate-800', 'shadow-xs');
            tabFechas.classList.add('text-slate-500');
            contentFlexible.classList.remove('hidden');
            contentFechas.classList.add('hidden');
        });
        
        // Renderizar ambos meses lado a lado
        function renderFilterCalendars() {
            renderSingleCalendar(filterCalYear, filterCalMonth, gridMonth1, labelMonth1);
            
            // Segundo mes
            let year2 = filterCalYear;
            let month2 = filterCalMonth + 1;
            if (month2 > 12) {
                month2 = 1;
                year2++;
            }
            renderSingleCalendar(year2, month2, gridMonth2, labelMonth2);
        }
        
        function renderSingleCalendar(year, month, gridEl, labelEl) {
            gridEl.innerHTML = '';
            
            const monthNames = [
                'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
                'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'
            ];
            labelEl.textContent = `${monthNames[month - 1]} ${year}`;
            
            const totalDays = new Date(year, month, 0).getDate();
            const startDay = new Date(year, month - 1, 1).getDay(); // Domingo = 0
            
            // Celdas vacías para alineación
            for (let i = 0; i < startDay; i++) {
                const empty = document.createElement('div');
                empty.className = 'aspect-square';
                gridEl.appendChild(empty);
            }
            
            // Crear días
            for (let d = 1; d <= totalDays; d++) {
                const dateStr = `${year}-${String(month).padStart(2, '0')}-${String(d).padStart(2, '0')}`;
                const cellDate = new Date(dateStr + 'T00:00:00');
                const isPast = cellDate < filterToday;
                const isSelected = filterSelectedDate === dateStr;
                
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'calendar-airbnb-day-btn';
                btn.textContent = d;
                
                if (isPast) {
                    btn.classList.add('disabled');
                    btn.disabled = true;
                } else {
                    if (isSelected) {
                        btn.classList.add('selected');
                    }
                    if (cellDate.getTime() === filterToday.getTime()) {
                        btn.classList.add('today');
                    }
                    btn.addEventListener('click', () => {
                        filterSelectedDate = dateStr;
                        renderFilterCalendars();
                    });
                }
                gridEl.appendChild(btn);
            }
        }
        
        // Navegación de meses
        prevMonthBtn.addEventListener('click', () => {
            filterCalMonth--;
            if (filterCalMonth < 1) {
                filterCalMonth = 12;
                filterCalYear--;
            }
            renderFilterCalendars();
        });
        
        const advanceMonth = () => {
            filterCalMonth++;
            if (filterCalMonth > 12) {
                filterCalMonth = 1;
                filterCalYear++;
            }
            renderFilterCalendars();
        };

        if (nextMonthBtn) {
            nextMonthBtn.addEventListener('click', advanceMonth);
        }
        if (nextMonthBtnMobile) {
            nextMonthBtnMobile.addEventListener('click', advanceMonth);
        }
        
        // Control de botones de flexibilidad
        document.querySelectorAll('.flexibility-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                document.querySelectorAll('.flexibility-btn').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                filterFlexibility = parseInt(this.getAttribute('data-flex'));
            });
        });
        
        // Renderizar meses flexibles
        function renderFlexibleMonths() {
            const container = document.getElementById('flexible-months-container');
            container.innerHTML = '';
            
            const monthNames = [
                'Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun',
                'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'
            ];
            
            const hoy = new Date();
            for (let i = 0; i < 6; i++) {
                const tempDate = new Date(hoy.getFullYear(), hoy.getMonth() + i, 1);
                const yearVal = tempDate.getFullYear();
                const monthVal = tempDate.getMonth() + 1;
                const dateKey = `${yearVal}-${String(monthVal).padStart(2, '0')}`;
                const labelStr = `${monthNames[monthVal - 1]} ${yearVal}`;
                
                const card = document.createElement('div');
                card.className = 'flexible-month-card p-3 rounded-2xl border text-center cursor-pointer select-none';
                if (filterFlexibleMonths.includes(dateKey)) {
                    card.classList.add('active');
                }
                
                // Iconos simpáticos según estación
                let icon = '🌴';
                if ([11, 0, 1].includes(tempDate.getMonth())) icon = '🏂';
                else if ([2, 3, 4].includes(tempDate.getMonth())) icon = '🌸';
                else if ([8, 9, 10].includes(tempDate.getMonth())) icon = '🍁';
                
                card.innerHTML = `
                    <span class="block text-xl mb-1">${icon}</span>
                    <span class="text-[10px] font-black uppercase tracking-wider text-slate-700">${labelStr}</span>
                `;
                
                card.addEventListener('click', () => {
                    if (filterFlexibleMonths.includes(dateKey)) {
                        filterFlexibleMonths = filterFlexibleMonths.filter(m => m !== dateKey);
                    } else {
                        filterFlexibleMonths.push(dateKey);
                    }
                    renderFlexibleMonths();
                });
                
                container.appendChild(card);
            }
        }
        
        // Limpiar filtros
        clearCalBtn.addEventListener('click', () => {
            filterSelectedDate = null;
            filterFlexibility = 0;
            filterFlexibleMonths = [];
            
            document.querySelectorAll('.flexibility-btn').forEach(b => b.classList.remove('active'));
            const defaultFlex = document.querySelector('.flexibility-btn[data-flex="0"]');
            if (defaultFlex) defaultFlex.classList.add('active');
            
            hiddenDate.value = '';
            hiddenFlex.value = '0';
            hiddenMonths.value = '';
            filterDisplay.value = '';
            
            renderFilterCalendars();
            renderFlexibleMonths();
        });
        
        // Aplicar filtros
        applyCalBtn.addEventListener('click', () => {
            if (filterActiveTab === 'fechas') {
                if (filterSelectedDate) {
                    hiddenDate.value = filterSelectedDate;
                    hiddenFlex.value = filterFlexibility;
                    hiddenMonths.value = '';
                    
                    const partes = filterSelectedDate.split('-');
                    const mesesCortos = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];
                    const labelMes = mesesCortos[parseInt(partes[1]) - 1];
                    
                    let displayText = `${partes[2]} ${labelMes}`;
                    if (filterFlexibility > 0) {
                        displayText += ` ±${filterFlexibility}d`;
                    }
                    filterDisplay.value = displayText;
                } else {
                    hiddenDate.value = '';
                    hiddenFlex.value = '0';
                    hiddenMonths.value = '';
                    filterDisplay.value = '';
                }
            } else {
                // Flexible
                if (filterFlexibleMonths.length > 0) {
                    hiddenDate.value = '';
                    hiddenFlex.value = '0';
                    hiddenMonths.value = filterFlexibleMonths.join(',');
                    
                    const mesesNombres = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
                    const labels = filterFlexibleMonths.map(m => {
                        const p = m.split('-');
                        return mesesNombres[parseInt(p[1]) - 1];
                    });
                    filterDisplay.value = `Flexible: ${labels.join(', ')}`;
                } else {
                    hiddenDate.value = '';
                    hiddenFlex.value = '0';
                    hiddenMonths.value = '';
                    filterDisplay.value = '';
                }
            }
            filterDropdown.classList.add('hidden');
        });
    </script>
@endsection
