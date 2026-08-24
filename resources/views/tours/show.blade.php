@extends('layouts.app')

<!-- 
 * @file show.blade.php
 * @description Vista Blade para los detalles de un tour individual. Incluye galería interactiva, acordeón de itinerario dinámico, inclusiones/exclusiones personalizadas y widget lateral de reserva con calendario estilo Airbnb. Rediseñado a tema claro con colores corporativos.
 * @date 2026-06-29
 * @author Antigravity
 -->

@section('title', $tour->nombre . ' - Attitour')

@section('content')
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-10">
        
        <!-- ENLACE VOLVER -->
        <div class="mb-6">
            <a href="{{ route('catalog') }}" class="inline-flex items-center gap-1.5 text-xs font-semibold uppercase tracking-wider text-slate-500 hover:text-brand-teal transition-colors">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Volver al catálogo
            </a>
        </div>

        <!-- CABECERA DEL TOUR -->
        <div class="mb-8">
            <div class="flex flex-wrap gap-2 mb-3">
                <span class="px-2.5 py-1 text-[9px] font-bold uppercase tracking-wider rounded bg-brand-teal/10 text-brand-teal border border-brand-teal/20 shadow-xs">
                    {{ $tour->ubicacion }}
                </span>
                @foreach($tour->tags as $tag)
                    <span class="text-[9px] font-bold text-slate-500 bg-slate-50 border border-slate-200 px-2.5 py-1 rounded">
                        #{{ $tag }}
                    </span>
                @endforeach
            </div>
            <h1 class="text-2xl sm:text-4xl font-black text-slate-800 leading-tight">
                {{ $tour->nombre }}
            </h1>
            <p class="text-xs text-slate-500 mt-2 flex items-center gap-1.5 font-semibold">
                <svg class="h-4 w-4 text-brand-teal" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                {{ $tour->ubicacion }}, {{ $tour->pais }}
            </p>
        </div>

        <!-- SECCIÓN DE GALERÍA + RESERVA (Hero) -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 lg:gap-8 items-start mb-10">
            <!-- Imagen Principal -->
            <div class="lg:col-span-2 overflow-hidden rounded-3xl border border-slate-200 shadow-md aspect-[16/10]">
                <img id="main-gallery-img" src="{{ $tour->imagen_destacada }}" alt="{{ $tour->nombre }}" class="h-full w-full object-cover transition-all duration-300">
            </div>

            <!-- Columna Derecha: Miniaturas + Panel de Reserva (ocupa ambas filas) -->
            <div class="lg:col-span-1 lg:row-span-2 flex flex-col gap-4">

                <!-- Miniaturas -->
                <div class="flex lg:flex-col gap-3 overflow-x-auto lg:overflow-y-auto lg:h-[400px] pb-2 lg:pb-0">
                    <!-- Miniatura 1 (Destacada) -->
                    <button onclick="changeGalleryImage('{{ $tour->imagen_destacada }}', this)" class="gallery-thumb-btn shrink-0 w-28 lg:w-full aspect-[16/10] overflow-hidden rounded-2xl border-2 border-brand-teal shadow-xs cursor-pointer">
                        <img src="{{ $tour->imagen_destacada }}" alt="Gallery 1" class="h-full w-full object-cover">
                    </button>
                    <!-- Galería Restante -->
                    @foreach($tour->galeria as $index => $gImg)
                        @if($gImg !== $tour->imagen_destacada)
                            <button onclick="changeGalleryImage('{{ $gImg }}', this)" class="gallery-thumb-btn shrink-0 w-28 lg:w-full aspect-[16/10] overflow-hidden rounded-2xl border-2 border-transparent hover:border-slate-300 shadow-xs transition-all cursor-pointer">
                                <img src="{{ $gImg }}" alt="Gallery {{ $index + 2 }}" class="h-full w-full object-cover">
                            </button>
                        @endif
                    @endforeach
                </div>

                <!-- PANEL DE RESERVA FLOTANTE -->
                <aside class="h-fit p-6 rounded-2xl border border-slate-200 bg-white shadow-lg lg:sticky lg:top-24">
                <div class="border-b border-slate-200 pb-4 mb-5 flex justify-between items-baseline">
                    <span class="text-xs font-bold text-slate-500 uppercase tracking-widest" id="price-type-label">
                        {{ $tour->tipo_modalidad === 'privado' ? 'Tour Privado (Desde)' : 'Precio por Persona' }}
                    </span>
                    <span class="text-right">
                        <span class="text-xl font-black text-brand-teal" id="price-value-label">
                            ${{ number_format($tour->precio_base_usd) }} USD
                        </span>
                        <span id="price-value-conversion" class="block text-[10px] font-medium text-slate-400 mt-0.5"></span>
                    </span>
                </div>

                <form id="reserva-form" action="{{ route('cart.add') }}" method="POST" class="flex flex-col gap-4">
                    @csrf
                    <input type="hidden" name="tour_id" value="{{ $tour->id }}">

                    <!-- Selector de Modalidad (Compartido vs Privado) -->
                    @if($tour->tipo_modalidad === 'ambos')
                        <div class="flex flex-col gap-1.5 mb-2">
                            <label class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Modalidad de Experiencia</label>
                            <div class="grid grid-cols-2 gap-2 bg-slate-100 p-1 rounded-xl">
                                <button type="button" id="modalidad-compartido-btn" onclick="setModalidad('compartido')" class="py-1.5 rounded-lg text-xs font-bold text-slate-800 bg-white shadow-xs transition-all cursor-pointer">
                                    Compartido
                                </button>
                                <button type="button" id="modalidad-privado-btn" onclick="setModalidad('privado')" class="py-1.5 rounded-lg text-xs font-semibold text-slate-500 hover:text-slate-800 transition-all cursor-pointer">
                                    Privado 🔒
                                </button>
                            </div>
                        </div>
                        <input type="hidden" name="es_privado" id="reserva-es-privado" value="0">
                    @else
                        <input type="hidden" name="es_privado" id="reserva-es-privado" value="{{ $tour->tipo_modalidad === 'privado' ? '1' : '0' }}">
                    @endif

                    <!-- Selector de Fecha (Calendario Estilo Airbnb Premium) -->
                    <div class="flex flex-col gap-2">
                        <label class="text-[10px] font-bold uppercase tracking-wider text-slate-500">
                            {{ __('availableDates') }}
                        </label>
                        <input type="hidden" name="fecha" id="reserva-fecha">

                        <!-- Contenedor del Calendario Visual (Estilo Airbnb Premium) -->
                        <div class="p-4 rounded-3xl border border-slate-200 bg-white shadow-xs">
                            <!-- Cabecera Mes/Año -->
                            <div class="flex items-center justify-between mb-4 pb-2 border-b border-slate-100">
                                <button type="button" id="cal-prev-month" class="p-1.5 rounded-full hover:bg-slate-100 text-slate-600 transition-colors cursor-pointer">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/></svg>
                                </button>
                                <span id="cal-month-label" class="text-xs font-black text-slate-800 uppercase tracking-widest"></span>
                                <button type="button" id="cal-next-month" class="p-1.5 rounded-full hover:bg-slate-100 text-slate-600 transition-colors cursor-pointer">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
                                </button>
                            </div>

                            <!-- Días de la semana -->
                            <div class="grid grid-cols-7 gap-1.5 text-center text-[9px] font-bold text-slate-400 uppercase tracking-widest mb-3">
                                <div>D</div>
                                <div>L</div>
                                <div>M</div>
                                <div>M</div>
                                <div>J</div>
                                <div>V</div>
                                <div>S</div>
                            </div>

                            <!-- Grid del Mes -->
                            <div id="cal-days-grid" class="grid grid-cols-7 gap-1.5">
                                <!-- Generado dinámicamente -->
                            </div>
                        </div>
                    </div>

                    <!-- Selector de Horario (Cargado Dinámicamente) -->
                    <div class="flex flex-col gap-1.5" id="horario-container">
                        <label class="text-[10px] font-bold uppercase tracking-wider text-slate-500">
                            Horario de Salida
                        </label>
                        <select name="horario" id="reserva-horario" required class="w-full h-10 rounded-lg border border-slate-200 bg-slate-50 px-3 text-xs text-slate-700 focus:border-brand-teal focus:ring-0 focus:outline-none cursor-pointer">
                            <option value="">Selecciona una fecha</option>
                        </select>
                    </div>

                    @if($tour->origen === 'api_externa')
                        <!-- Selector de Personas por categoría (tours de API externa: Unique pide adultos/menores/infantes por separado) -->
                        <input type="hidden" name="cantidad" id="reserva-qty" value="1" min="1" max="{{ $tour->cupo_maximo }}">
                        <div class="flex flex-col gap-1.5">
                            <label class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Pasajeros</label>
                            <div class="grid grid-cols-3 gap-2">
                                <div class="flex flex-col gap-1">
                                    <span class="text-[9px] text-slate-500 font-semibold text-center">Adultos</span>
                                    <input type="number" name="cantidad_adultos" id="reserva-adultos" value="1" min="1" max="{{ $tour->cupo_maximo }}" oninput="syncPaxTotal()" class="w-full h-9 rounded-lg border border-slate-200 bg-slate-50 text-center text-xs font-bold text-slate-800 focus:border-brand-teal focus:ring-0 focus:outline-none">
                                </div>
                                <div class="flex flex-col gap-1">
                                    <span class="text-[9px] text-slate-500 font-semibold text-center">Menores</span>
                                    <input type="number" name="cantidad_menores" id="reserva-menores" value="0" min="0" max="{{ $tour->cupo_maximo }}" oninput="syncPaxTotal()" class="w-full h-9 rounded-lg border border-slate-200 bg-slate-50 text-center text-xs font-bold text-slate-800 focus:border-brand-teal focus:ring-0 focus:outline-none">
                                </div>
                                <div class="flex flex-col gap-1">
                                    <span class="text-[9px] text-slate-500 font-semibold text-center">Infantes</span>
                                    <input type="number" name="cantidad_infantes" id="reserva-infantes" value="0" min="0" max="{{ $tour->cupo_maximo }}" oninput="syncPaxTotal()" class="w-full h-9 rounded-lg border border-slate-200 bg-slate-50 text-center text-xs font-bold text-slate-800 focus:border-brand-teal focus:ring-0 focus:outline-none">
                                </div>
                            </div>
                        </div>

                        <!-- Idioma del tour -->
                        <div class="flex flex-col gap-1.5">
                            <label class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Idioma del Tour</label>
                            <select name="idioma_seleccionado" id="reserva-idioma" required class="w-full h-10 rounded-lg border border-slate-200 bg-slate-50 px-3 text-xs text-slate-700 focus:border-brand-teal focus:ring-0 focus:outline-none cursor-pointer">
                                <option value="">Cargando idiomas…</option>
                            </select>
                        </div>

                        <!-- Hotel y hora de recogida -->
                        <div class="flex flex-col gap-1.5">
                            <label class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Hotel (para tu recogida)</label>
                            <input type="text" id="reserva-hotel-filtro" placeholder="Escribe para buscar tu hotel…" oninput="filtrarHoteles()" class="w-full h-9 rounded-lg border border-slate-200 bg-slate-50 px-3 text-xs text-slate-700 focus:border-brand-teal focus:ring-0 focus:outline-none">
                            <select name="hotel_nombre" id="reserva-hotel" required onchange="onHotelSeleccionado()" class="w-full h-10 rounded-lg border border-slate-200 bg-slate-50 px-3 text-xs text-slate-700 focus:border-brand-teal focus:ring-0 focus:outline-none cursor-pointer">
                                <option value="">Cargando hoteles…</option>
                            </select>
                            <select name="hotel_lobby" id="reserva-hotel-lobby" class="hidden w-full h-10 rounded-lg border border-slate-200 bg-slate-50 px-3 text-xs text-slate-700 focus:border-brand-teal focus:ring-0 focus:outline-none cursor-pointer">
                                <option value="">Selecciona el lobby</option>
                            </select>
                            <input type="hidden" name="pickup_horario" id="reserva-pickup-horario">
                            <p id="reserva-pickup-info" class="hidden text-[10px] text-brand-teal font-bold"></p>
                        </div>
                    @else
                        <!-- Selector de Personas -->
                        <div class="flex flex-col gap-1.5">
                            <label class="text-[10px] font-bold uppercase tracking-wider text-slate-500">
                                {{ __('selectPeople') }}
                            </label>
                            <div class="flex h-10 rounded-lg border border-slate-200 bg-slate-50 overflow-hidden">
                                <button type="button" onclick="adjustQty(-1)" class="w-10 flex items-center justify-center text-slate-500 hover:text-slate-800 hover:bg-slate-100 border-r border-slate-200 transition-colors cursor-pointer font-bold">-</button>
                                <input type="number" name="cantidad" id="reserva-qty" value="1" min="1" max="{{ $tour->cupo_maximo }}" class="w-full text-center bg-transparent border-0 text-xs font-bold text-slate-800 focus:ring-0 focus:outline-none">
                                <button type="button" onclick="adjustQty(1)" class="w-10 flex items-center justify-center text-slate-500 hover:text-slate-800 hover:bg-slate-100 border-l border-slate-200 transition-colors cursor-pointer font-bold">+</button>
                            </div>
                        </div>
                    @endif

                    <!-- AJAX Alerta de Disponibilidad -->
                    <div id="availability-status" class="hidden text-[10px] p-2.5 rounded-lg border"></div>

                    <!-- Desglose de Precios (Calculador) -->
                    <div id="price-summary-container" class="mt-2 p-3.5 rounded-xl border border-slate-200 bg-slate-50 text-xs flex flex-col gap-2 font-semibold">
                        <!-- Detalles de Compartido -->
                        <div id="shared-details" class="flex flex-col gap-2">
                            <div class="flex items-center justify-between text-slate-500">
                                <span>Base:</span>
                                <span>${{ number_format($tour->precio_base_usd) }} USD x <span id="summary-qty">1</span></span>
                            </div>
                        </div>

                        <!-- Detalles de Privado -->
                        <div id="private-details" class="hidden flex flex-col gap-2 border-t border-slate-200 pt-2">
                            <div class="flex items-center justify-between text-slate-500">
                                <span>Monto total del tour:</span>
                                <span id="private-total-raw">$0 USD</span>
                            </div>
                            <div class="flex items-center justify-between text-brand-orange font-bold">
                                <span id="private-deposit-label">Pagar online hoy:</span>
                                <span class="text-right">
                                    <span id="private-deposit-val">$0 USD</span>
                                    <span id="private-deposit-conversion" class="block text-[10px] font-medium text-slate-400 mt-0.5"></span>
                                </span>
                            </div>
                            <div class="flex items-center justify-between text-slate-600">
                                <span id="private-balance-label">Liquidar en persona:</span>
                                <span id="private-balance-val">$0 USD</span>
                            </div>
                        </div>

                        <!-- Total Final (a pagar hoy en checkout) -->
                        <div class="flex items-center justify-between border-t border-slate-200 pt-2 font-bold text-slate-700">
                            <span id="total-payment-label">Total a pagar hoy:</span>
                            <span class="text-right">
                                <span class="text-sm text-brand-teal font-black" id="summary-total">${{ number_format($tour->precio_base_usd) }} USD</span>
                                <span id="summary-total-conversion" class="block text-[10px] font-medium text-slate-400 mt-0.5"></span>
                            </span>
                        </div>
                    </div>

                    <!-- Botón de Envío -->
                    <button type="submit" class="w-full h-11 inline-flex items-center justify-center rounded-xl bg-gradient-to-r from-brand-orange to-brand-orange-hover hover:opacity-95 text-xs font-bold uppercase tracking-widest text-white shadow-md shadow-brand-orange/15 cursor-pointer transition-all hover:scale-[1.01]">
                        {{ __('addToCart') }}
                    </button>
                </form>
                </aside>
            </div>

            <!-- CONTENIDO DE DETALLES -->
            <div class="lg:col-span-2 flex flex-col gap-8">

                <!-- Pestañas de Info Clave -->
                <div class="grid grid-cols-2 gap-4 p-2 rounded-2xl border border-slate-200 bg-slate-100/50 backdrop-blur-xs">
                    <div class="text-center py-4 border-r border-slate-200">
                        <p class="text-[8px] font-bold text-slate-500 uppercase tracking-widest">{{ __('duration') }}</p>
                        <p class="text-sm font-bold text-slate-800 mt-1">{{ $tour->duracion }}</p>
                    </div>
                    <div class="text-center py-4">
                        <p class="text-[8px] font-bold text-slate-500 uppercase tracking-widest">{{ __('maxCapacity') }}</p>
                        <p class="text-sm font-bold text-slate-800 mt-1">{{ $tour->cupo_maximo }} {{ __('people') }}</p>
                    </div>
                </div>

                <!-- Descripción Larga -->
                <div>
                    <h2 class="text-lg font-bold text-slate-800 mb-3">Descripción General</h2>
                    <p class="text-sm text-slate-650 leading-relaxed font-medium">
                        {{ $tour->detalle }}
                    </p>
                </div>

                <!-- ITINERARIO ACORDEÓN INTERACTIVO -->
                <div>
                    <h2 class="text-lg font-bold text-slate-800 mb-4">Itinerario del Viaje</h2>
                    <div class="flex flex-col gap-3">
                        @if(!empty($tour->itinerario) && is_array($tour->itinerario))
                            @foreach($tour->itinerario as $index => $paso)
                                <div class="border border-slate-200 rounded-xl bg-white shadow-xs overflow-hidden">
                                    <button onclick="toggleAccordion('itinerary-{{ $index }}')" class="w-full flex items-center justify-between p-4 text-left font-bold text-xs uppercase tracking-wider text-slate-700 hover:text-brand-teal transition-colors cursor-pointer">
                                        <span>Paso {{ $index + 1 }}: {{ $paso['titulo'] }}</span>
                                        <svg id="icon-itinerary-{{ $index }}" class="h-4 w-4 opacity-70 transform {{ $index === 0 ? 'rotate-180' : '' }} transition-transform duration-200" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </button>
                                    <div id="content-itinerary-{{ $index }}" class="{{ $index === 0 ? '' : 'hidden' }} p-4 pt-0 text-xs text-slate-500 leading-relaxed font-semibold">
                                        {{ $paso['descripcion'] }}
                                    </div>
                                </div>
                            @endforeach
                        @else
                            <!-- Fallback si no hay itinerario -->
                            <!-- Punto 1 -->
                            <div class="border border-slate-200 rounded-xl bg-white shadow-xs overflow-hidden">
                                <button onclick="toggleAccordion('itinerary-1')" class="w-full flex items-center justify-between p-4 text-left font-bold text-xs uppercase tracking-wider text-slate-700 hover:text-brand-teal transition-colors cursor-pointer">
                                    <span>Paso 1: Punto de Partida y Registro</span>
                                    <svg id="icon-itinerary-1" class="h-4 w-4 opacity-70 transform rotate-180 transition-transform duration-200" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </button>
                                <div id="content-itinerary-1" class="p-4 pt-0 text-xs text-slate-500 leading-relaxed font-semibold">
                                    Nos reuniremos en el punto de encuentro seleccionado 30 minutos antes de la hora programada de salida. Tras el registro y firma de exenciones de responsabilidad, conocerás a tus guías y recibirás una sesión informativa sobre seguridad.
                                </div>
                            </div>

                            <!-- Punto 2 -->
                            <div class="border border-slate-200 rounded-xl bg-white shadow-xs overflow-hidden">
                                <button onclick="toggleAccordion('itinerary-2')" class="w-full flex items-center justify-between p-4 text-left font-bold text-xs uppercase tracking-wider text-slate-700 hover:text-brand-teal transition-colors cursor-pointer">
                                    <span>Paso 2: Inicio de la Actividad</span>
                                    <svg id="icon-itinerary-2" class="h-4 w-4 opacity-70 transform transition-transform duration-200" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </button>
                                <div id="content-itinerary-2" class="hidden p-4 pt-0 text-xs text-slate-500 leading-relaxed font-semibold">
                                    Abordaremos el transporte especializado (embarcación de lujo o van climatizada). Iniciaremos el trayecto disfrutando de paisajes hermosos en el camino. Los guías te compartirán datos históricos sobre la zona.
                                </div>
                            </div>

                            <!-- Punto 3 -->
                            <div class="border border-slate-200 rounded-xl bg-white shadow-xs overflow-hidden">
                                <button onclick="toggleAccordion('itinerary-3')" class="w-full flex items-center justify-between p-4 text-left font-bold text-xs uppercase tracking-wider text-slate-700 hover:text-brand-teal transition-colors cursor-pointer">
                                    <span>Paso 3: Almuerzo y Tiempo Libre</span>
                                    <svg id="icon-itinerary-3" class="h-4 w-4 opacity-70 transform transition-transform duration-200" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </button>
                                <div id="content-itinerary-3" class="hidden p-4 pt-0 text-xs text-slate-500 leading-relaxed font-semibold">
                                    Disfrutaremos de una deliciosa comida local o buffet (según incluya el tour). Posteriormente, dispondrás de tiempo libre para descansar en los camastros, nadar, hacer fotos del paraíso o comprar souvenirs.
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- QUÉ INCLUYE / QUÉ NO INCLUYE -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Incluye -->
                    <div class="p-6 rounded-2xl border border-emerald-100 bg-emerald-50/50">
                        <h3 class="text-sm font-bold text-emerald-700 uppercase tracking-wider mb-4 flex items-center gap-2">
                            <svg class="h-5 w-5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Qué Incluye
                        </h3>
                        <ul class="flex flex-col gap-2.5 text-xs text-slate-650 font-semibold">
                            @if(!empty($tour->incluye) && is_array($tour->incluye))
                                @foreach($tour->incluye as $inc)
                                    <li class="flex items-start gap-2">
                                        <span class="text-emerald-600 font-bold mt-0.5">&check;</span>
                                        {{ $inc }}
                                    </li>
                                @endforeach
                            @else
                                <li class="flex items-start gap-2">
                                    <span class="text-emerald-600 font-bold mt-0.5">&check;</span>
                                    Guía certificado bilingüe
                                </li>
                                <li class="flex items-start gap-2">
                                    <span class="text-emerald-600 font-bold mt-0.5">&check;</span>
                                    Transportación ida y vuelta con aire acondicionado
                                </li>
                                <li class="flex items-start gap-2">
                                    <span class="text-emerald-600 font-bold mt-0.5">&check;</span>
                                    Entradas oficiales y chaleco salvavidas
                                </li>
                                <li class="flex items-start gap-2">
                                    <span class="text-emerald-600 font-bold mt-0.5">&check;</span>
                                    Bebidas refrescantes a bordo
                                </li>
                            @endif
                        </ul>
                    </div>

                    <!-- No Incluye -->
                    <div class="p-6 rounded-2xl border border-rose-100 bg-rose-50/50">
                        <h3 class="text-sm font-bold text-rose-700 uppercase tracking-wider mb-4 flex items-center gap-2">
                            <svg class="h-5 w-5 text-rose-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            No Incluye
                        </h3>
                        <ul class="flex flex-col gap-2.5 text-xs text-slate-650 font-semibold">
                            @if(!empty($tour->no_incluye) && is_array($tour->no_incluye))
                                @foreach($tour->no_incluye as $ninc)
                                    <li class="flex items-start gap-2">
                                        <span class="text-rose-600 font-bold mt-0.5">&times;</span>
                                        {{ $ninc }}
                                    </li>
                                @endforeach
                            @else
                                <li class="flex items-start gap-2">
                                    <span class="text-rose-600 font-bold mt-0.5">&times;</span>
                                    Propinas para los guías locales
                                </li>
                                <li class="flex items-start gap-2">
                                    <span class="text-rose-600 font-bold mt-0.5">&times;</span>
                                    Fotos y videos profesionales de recuerdo
                                </li>
                                <li class="flex items-start gap-2">
                                    <span class="text-rose-600 font-bold mt-0.5">&times;</span>
                                    Impuesto de muelle/arrecife (si aplica)
                                </li>
                            @endif
                        </ul>
                    </div>
                </div>

                <!-- PUNTO DE ENCUENTRO MAPA SIMULADO -->
                <div>
                    <h2 class="text-lg font-bold text-slate-800 mb-4">Punto de Encuentro</h2>
                    <div class="p-4 rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden flex flex-col gap-4">
                        <div class="flex items-start gap-3">
                            <div class="p-2 rounded bg-brand-orange/10 text-brand-orange mt-0.5">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                            </div>
                            <div>
                                <h4 class="text-xs font-bold text-slate-800">Lugar de Partida / Reunión</h4>
                                <p class="text-[11px] text-slate-650 leading-relaxed mt-1 font-semibold">
                                    {{ $tour->punto_encuentro ?: 'Oficina Principal de Attitour: Boulevard Kukulcán Km 9.5, Zona Hotelera, Cancún, Q.R., México. (Frente al Centro de Convenciones).' }}
                                </p>
                            </div>
                        </div>
                        
                        <!-- Mapa real (Leaflet / OpenStreetMap) con la ubicación guardada desde el formulario del Admin -->
                        @php
                            $tieneUbicacion = $tour->punto_encuentro_lat !== null && $tour->punto_encuentro_lng !== null;
                            $mapaLat = $tieneUbicacion ? $tour->punto_encuentro_lat : 21.1619;
                            $mapaLng = $tieneUbicacion ? $tour->punto_encuentro_lng : -86.8515;
                        @endphp
                        <div id="tour-punto-encuentro-map" class="w-full h-48 rounded-xl border border-slate-200 overflow-hidden z-0" data-lat="{{ $mapaLat }}" data-lng="{{ $mapaLng }}" data-tiene-ubicacion="{{ $tieneUbicacion ? '1' : '0' }}"></div>

                        @if($tieneUbicacion)
                            <a href="https://www.google.com/maps/search/?api=1&query={{ $mapaLat }},{{ $mapaLng }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1.5 text-[10px] font-bold text-brand-teal hover:text-brand-teal-hover transition-colors self-start">
                                🧭 Abrir en Google Maps
                            </a>
                        @else
                            <p class="text-[10px] text-slate-400 italic">Este tour todavía no tiene una ubicación exacta configurada; se muestra el área general de Cancún.</p>
                        @endif
                    </div>
                <!-- GALERÍA DE EXPERIENCIAS DE VIAJEROS (solo se muestra si hay fotos reales subidas) -->
                @php $expFotos = $tour->experiencias_fotos; @endphp
                @if(count($expFotos) > 0)
                    <div class="mt-8 pt-8 border-t border-slate-200">
                        <div class="flex items-center justify-between mb-4">
                            <div>
                                <h2 class="text-lg font-bold text-slate-800">Galería de Experiencias</h2>
                                <p class="text-xs text-slate-500 font-semibold mt-0.5">Fotos reales capturadas por viajeros que han realizado este tour.</p>
                            </div>
                            <button type="button" onclick="openExperienceModal(0)" class="px-3 py-1.5 rounded-xl border border-brand-teal/30 bg-brand-teal/10 hover:bg-brand-teal text-brand-teal hover:text-white text-xs font-bold transition-colors cursor-pointer">
                                VER MÁS 📸
                            </button>
                        </div>

                        <!-- Mosaico / Grid de 3 Fotos de viajeros -->
                        <div class="grid grid-cols-3 gap-3">
                            @foreach(array_slice($expFotos, 0, 3) as $idx => $foto)
                                <div class="relative h-32 sm:h-36 rounded-2xl overflow-hidden group border border-slate-200 shadow-xs bg-slate-100 cursor-pointer" onclick="openExperienceModal({{ $idx }})">
                                    <img src="{{ $foto }}" alt="Experiencia de viajero" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                                    <div class="absolute inset-0 bg-black/10 group-hover:bg-transparent transition-colors"></div>
                                    @if($idx === 2 && count($expFotos) > 3)
                                        <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-xs flex flex-col items-center justify-center text-white text-xs font-black uppercase tracking-wider">
                                            <span>+{{ count($expFotos) - 3 }}</span>
                                            <span class="text-[9px] font-bold">Ver Más</span>
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- SCRIPTS JS DETALLES -->
    <!-- Leaflet (OpenStreetMap) — mapa de solo lectura del punto de encuentro -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        // Renderiza el mapa del punto de encuentro con la ubicación guardada desde el Admin.
        (function initTourPuntoEncuentroMap() {
            const contenedor = document.getElementById('tour-punto-encuentro-map');
            if (!contenedor) return;

            const lat = parseFloat(contenedor.dataset.lat);
            const lng = parseFloat(contenedor.dataset.lng);
            const tieneUbicacion = contenedor.dataset.tieneUbicacion === '1';

            const mapa = L.map('tour-punto-encuentro-map', {
                scrollWheelZoom: false,
            }).setView([lat, lng], tieneUbicacion ? 15 : 12);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
                maxZoom: 19,
            }).addTo(mapa);

            if (tieneUbicacion) {
                L.marker([lat, lng]).addTo(mapa);
            }
        })();

        // Cambiar Imagen de la Galería
        function changeGalleryImage(url, buttonEl) {
            const mainImg = document.getElementById('main-gallery-img');
            if (mainImg) {
                // Efecto de parpadeo suave en transición
                mainImg.style.opacity = '0.3';
                setTimeout(() => {
                    mainImg.src = url;
                    mainImg.style.opacity = '1';
                }, 100);
            }

            // Actualizar borde en miniatura activa
            const buttons = document.querySelectorAll('.gallery-thumb-btn');
            buttons.forEach(btn => btn.classList.remove('border-brand-teal'));
            buttons.forEach(btn => btn.classList.add('border-transparent'));
            
            buttonEl.classList.remove('border-transparent');
            buttonEl.classList.add('border-brand-teal');
        }

        // Acordeón de Itinerario
        function toggleAccordion(id) {
            const content = document.getElementById(`content-${id}`);
            const icon = document.getElementById(`icon-${id}`);
            
            if (content.classList.contains('hidden')) {
                content.classList.remove('hidden');
                icon.classList.add('rotate-180');
            } else {
                content.classList.add('hidden');
                icon.classList.remove('rotate-180');
            }
        }

        // Calculador de Precios en tiempo real
        const basePrice = {{ $tour->precio_base_usd }};
        const tarifasPrivadas = @json($tour->tarifas_privadas ?? []);
        const tipoModalidad = "{{ $tour->tipo_modalidad }}";
        const anticipoPorcentaje = {{ $tour->anticipo_porcentaje ?? 20 }};
        let esPrivadoSelected = {{ $tour->tipo_modalidad === 'privado' ? 'true' : 'false' }};

        // Tasas de cambio de referencia (USD -> MXN/EUR). El cobro real en Stripe siempre es en USD.
        const exchangeRates = @json($exchangeRates ?? []);
        function conversionText(usd) {
            if (!exchangeRates.MXN || !exchangeRates.EUR) return '';
            const mxn = Math.round(usd * exchangeRates.MXN).toLocaleString();
            const eur = Math.round(usd * exchangeRates.EUR).toLocaleString();
            return `≈ $${mxn} MXN · €${eur} EUR`;
        }

        const qtyInput = document.getElementById('reserva-qty');
        const summaryQty = document.getElementById('summary-qty');
        const summaryTotal = document.getElementById('summary-total');
        const summaryTotalConversion = document.getElementById('summary-total-conversion');
        const priceValueConversion = document.getElementById('price-value-conversion');
        const privateDepositConversion = document.getElementById('private-deposit-conversion');

        function setModalidad(tipo) {
            const btnCompartido = document.getElementById('modalidad-compartido-btn');
            const btnPrivado = document.getElementById('modalidad-privado-btn');
            const esPrivadoInput = document.getElementById('reserva-es-privado');
            const priceTypeLabel = document.getElementById('price-type-label');
            const priceValueLabel = document.getElementById('price-value-label');

            if (tipo === 'privado') {
                esPrivadoSelected = true;
                esPrivadoInput.value = "1";
                if (typeof availableDatesPrivate !== 'undefined') {
                    availableDates = availableDatesPrivate;
                }
                if (btnCompartido) {
                    btnCompartido.classList.remove('bg-white', 'shadow-xs', 'text-slate-800');
                    btnCompartido.classList.add('text-slate-500', 'font-semibold');
                }
                if (btnPrivado) {
                    btnPrivado.classList.add('bg-white', 'shadow-xs', 'text-slate-800');
                    btnPrivado.classList.remove('text-slate-500', 'font-semibold');
                }
                if (priceTypeLabel) priceTypeLabel.textContent = 'Tour Privado (Desde)';
            } else {
                esPrivadoSelected = false;
                esPrivadoInput.value = "0";
                if (typeof availableDatesShared !== 'undefined') {
                    availableDates = availableDatesShared;
                }
                if (btnPrivado) {
                    btnPrivado.classList.remove('bg-white', 'shadow-xs', 'text-slate-800');
                    btnPrivado.classList.add('text-slate-500', 'font-semibold');
                }
                if (btnCompartido) {
                    btnCompartido.classList.add('bg-white', 'shadow-xs', 'text-slate-800');
                    btnCompartido.classList.remove('text-slate-500', 'font-semibold');
                }
                if (priceTypeLabel) priceTypeLabel.textContent = 'Precio por Persona';
                if (priceValueLabel) priceValueLabel.textContent = `$${basePrice.toLocaleString()} USD`;
            }

            // Limpiar fecha y horario seleccionados ya que los calendarios son independientes
            if (fechaSelect) fechaSelect.value = '';
            if (statusBox) statusBox.classList.add('hidden');
            if (horarioSelect) horarioSelect.innerHTML = '<option value="">Selecciona una fecha</option>';
            cachedHorarios = [];

            if (typeof renderCalendar === 'function') {
                renderCalendar();
            }
            calculateTotal();
        }

        function adjustQty(amount) {
            let val = parseInt(qtyInput.value) + amount;
            const maxVal = parseInt(qtyInput.getAttribute('max')) || 20;
            if (val < 1) val = 1;
            if (val > maxVal) val = maxVal;
            qtyInput.value = val;
            calculateTotal();
            verifyAvailability();
        }

        function calculateTotal() {
            const qty = parseInt(qtyInput.value) || 1;
            const sharedDetails = document.getElementById('shared-details');
            const privateDetails = document.getElementById('private-details');
            const privateTotalRaw = document.getElementById('private-total-raw');
            const privateDepositLabel = document.getElementById('private-deposit-label');
            const privateDepositVal = document.getElementById('private-deposit-val');
            const privateBalanceLabel = document.getElementById('private-balance-label');
            const privateBalanceVal = document.getElementById('private-balance-val');
            const totalPaymentLabel = document.getElementById('total-payment-label');
            const priceValueLabel = document.getElementById('price-value-label');

            if (esPrivadoSelected) {
                // Calcular precio según tarifas_privadas
                let total = basePrice * qty; // fallback
                if (Array.isArray(tarifasPrivadas) && tarifasPrivadas.length > 0) {
                    const tarifaCoincidente = tarifasPrivadas.find(t => {
                        const min = parseInt(t.min_pax) || 0;
                        const max = parseInt(t.max_pax) || 999;
                        return qty >= min && qty <= max;
                    });
                    if (tarifaCoincidente) {
                        total = parseFloat(tarifaCoincidente.precio_total_usd);
                    }
                }

                if (sharedDetails) sharedDetails.classList.add('hidden');
                if (privateDetails) privateDetails.classList.remove('hidden');
                
                const pct = anticipoPorcentaje;
                if (totalPaymentLabel) totalPaymentLabel.textContent = `Anticipo online hoy (${pct}%):`;
                if (priceValueLabel) priceValueLabel.textContent = `Desde $${total.toLocaleString()} USD`;
                if (priceValueConversion) priceValueConversion.textContent = conversionText(total);

                const deposit = total * (pct / 100);
                const balance = total * ((100 - pct) / 100);

                if (privateTotalRaw) privateTotalRaw.textContent = `$${total.toLocaleString()} USD`;
                if (privateDepositLabel) privateDepositLabel.textContent = `Pagar online hoy (${pct}%):`;
                if (privateDepositVal) privateDepositVal.textContent = `$${deposit.toLocaleString()} USD`;
                if (privateDepositConversion) privateDepositConversion.textContent = conversionText(deposit);
                if (privateBalanceLabel) privateBalanceLabel.textContent = `Liquidar en persona (${100 - pct}%):`;
                if (privateBalanceVal) privateBalanceVal.textContent = `$${balance.toLocaleString()} USD`;
                summaryTotal.textContent = `$${deposit.toLocaleString()} USD`;
                if (summaryTotalConversion) summaryTotalConversion.textContent = conversionText(deposit);
            } else {
                if (privateDetails) privateDetails.classList.add('hidden');
                if (sharedDetails) sharedDetails.classList.remove('hidden');
                if (totalPaymentLabel) totalPaymentLabel.textContent = 'Total a pagar hoy:';

                summaryQty.textContent = qty;
                const total = qty * basePrice;
                summaryTotal.textContent = `$${total.toLocaleString()} USD`;
                if (summaryTotalConversion) summaryTotalConversion.textContent = conversionText(total);
                if (priceValueConversion) priceValueConversion.textContent = conversionText(basePrice);
            }
        }

        calculateTotal();

        qtyInput.addEventListener('input', () => {
            if (parseInt(qtyInput.value) < 1 || isNaN(qtyInput.value)) {
                qtyInput.value = 1;
            }
            calculateTotal();
            updateAvailabilityUI();
        });

        const fechaSelect = document.getElementById('reserva-fecha');
        const statusBox = document.getElementById('availability-status');
        const horarioSelect = document.getElementById('reserva-horario');
        let cachedHorarios = [];

        function verifyAvailability() {
            const fechaVal = fechaSelect.value;
            if (!fechaVal) {
                statusBox.classList.add('hidden');
                horarioSelect.innerHTML = '<option value="">Selecciona una fecha</option>';
                cachedHorarios = [];
                return;
            }

            // Consultar disponibilidad vía AJAX
            fetch(`/tours/{{ $tour->id }}/availability?fecha=${fechaVal}&es_privado=${esPrivadoSelected ? 1 : 0}`)
                .then(res => {
                    if (!res.ok) throw new Error('Error al consultar disponibilidad');
                    return res.json();
                })
                .then(data => {
                    cachedHorarios = data.horarios || [];
                    
                    // Limpiar y llenar horarios
                    horarioSelect.innerHTML = '';
                    if (cachedHorarios.length === 0) {
                        horarioSelect.innerHTML = '<option value="">Sin salidas para esta fecha</option>';
                    } else {
                        cachedHorarios.forEach(h => {
                            const option = document.createElement('option');
                            option.value = h.horario;
                            option.textContent = `${h.horario} hrs (${h.cupo_disponible} cupos)`;
                            if (h.cupo_disponible <= 0) {
                                option.disabled = true;
                                option.textContent += ' [LLENO]';
                            }
                            horarioSelect.appendChild(option);
                        });
                    }
                    
                    updateAvailabilityUI();
                })
                .catch(err => {
                    console.error(err);
                    statusBox.classList.add('hidden');
                });
        }

        function updateAvailabilityUI() {
            const qtyVal = parseInt(qtyInput.value) || 1;
            const selectedHora = horarioSelect.value;

            if (!selectedHora || cachedHorarios.length === 0) {
                statusBox.classList.add('hidden');
                qtyInput.setAttribute('max', 20);
                return;
            }

            const activeHora = cachedHorarios.find(h => h.horario === selectedHora);

            if (!activeHora) {
                statusBox.classList.add('hidden');
                return;
            }

            statusBox.classList.remove('hidden');
            const cupoDisp = activeHora.cupo_disponible;

            if (cupoDisp >= qtyVal) {
                statusBox.className = 'text-[10px] p-2.5 rounded-lg border border-emerald-250 bg-emerald-50 text-emerald-700 font-bold';
                statusBox.textContent = `Salida disponible. Quedan ${cupoDisp} lugares libres a las ${selectedHora} hrs.`;
                qtyInput.setAttribute('max', cupoDisp);
            } else {
                statusBox.className = 'text-[10px] p-2.5 rounded-lg border border-rose-250 bg-rose-50 text-rose-700 font-bold';
                if (cupoDisp > 0) {
                    statusBox.textContent = `Cupo insuficiente. Solo quedan ${cupoDisp} lugares libres para las ${selectedHora} hrs.`;
                    qtyInput.setAttribute('max', cupoDisp);
                } else {
                    statusBox.textContent = `Lo sentimos, este horario (${selectedHora} hrs) está completo.`;
                    qtyInput.setAttribute('max', 0);
                }
            }
        }

        horarioSelect.addEventListener('change', updateAvailabilityUI);

        // --- LÓGICA DE CALENDARIO VISUAL ESTILO AIRBNB ---
        const availableDatesShared = {
            @foreach($fechas->where('es_privado', false) as $f)
                "{{ $f->fecha->format('Y-m-d') }}": true,
            @endforeach
        };
        const availableDatesPrivate = {
            @foreach($fechas->where('es_privado', true) as $f)
                "{{ $f->fecha->format('Y-m-d') }}": true,
            @endforeach
        };
        
        let availableDates = esPrivadoSelected ? availableDatesPrivate : availableDatesShared;

        let currentYear = new Date().getFullYear();
        let currentMonth = new Date().getMonth() + 1; // 1-12
        const todayStr = new Date().toISOString().split('T')[0];
        const today = new Date(todayStr + 'T00:00:00');

        // Enfocar mes en la primera fecha disponible futura
        const datesArray = Object.keys(availableDates).sort();
        if (datesArray.length > 0) {
            const firstDate = new Date(datesArray[0] + 'T00:00:00');
            if (firstDate >= today) {
                currentYear = firstDate.getFullYear();
                currentMonth = firstDate.getMonth() + 1;
            }
        }

        const calMonthLabel = document.getElementById('cal-month-label');
        const calDaysGrid = document.getElementById('cal-days-grid');
        const calPrevBtn = document.getElementById('cal-prev-month');
        const calNextBtn = document.getElementById('cal-next-month');

        function renderCalendar() {
            calDaysGrid.innerHTML = '';
            
            const monthNames = [
                'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
                'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'
            ];
            calMonthLabel.textContent = `${monthNames[currentMonth - 1]} ${currentYear}`;

            const totalDays = new Date(currentYear, currentMonth, 0).getDate();
            const startDay = new Date(currentYear, currentMonth - 1, 1).getDay(); // Domingo = 0
            const emptySlots = startDay;

            // Días vacíos iniciales
            for (let i = 0; i < emptySlots; i++) {
                const emptyCell = document.createElement('div');
                emptyCell.className = 'aspect-square';
                calDaysGrid.appendChild(emptyCell);
            }

            // Pintar celdas de días
            for (let day = 1; day <= totalDays; day++) {
                const dateStr = `${currentYear}-${String(currentMonth).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
                const cellDate = new Date(dateStr + 'T00:00:00');
                const isPast = cellDate < today;
                const isAvailable = availableDates[dateStr] === true;
                const isSelected = fechaSelect.value === dateStr;

                const dayBtn = document.createElement('button');
                dayBtn.type = 'button';
                dayBtn.className = 'calendar-airbnb-day-btn';
                dayBtn.textContent = day;

                if (isPast) {
                    dayBtn.classList.add('disabled');
                    dayBtn.disabled = true;
                } else if (isAvailable) {
                    if (isSelected) {
                        dayBtn.classList.add('selected');
                    } else {
                        // Resaltado sutil en verde para fechas con disponibilidad
                        dayBtn.classList.add('border', 'border-brand-teal/20', 'bg-brand-teal/5', 'text-brand-teal', 'hover:bg-brand-teal', 'hover:text-white');
                    }
                    if (cellDate.getTime() === today.getTime()) {
                        dayBtn.classList.add('today');
                    }
                    
                    dayBtn.addEventListener('click', () => {
                        fechaSelect.value = dateStr;
                        renderCalendar(); // Redibujar selección
                        verifyAvailability(); // Cargar horarios y disponibilidad
                    });
                } else {
                    dayBtn.classList.add('disabled');
                    dayBtn.disabled = true;
                }

                calDaysGrid.appendChild(dayBtn);
            }
        }

        calPrevBtn.addEventListener('click', () => {
            currentMonth--;
            if (currentMonth < 1) {
                currentMonth = 12;
                currentYear--;
            }
            renderCalendar();
        });

        calNextBtn.addEventListener('click', () => {
            currentMonth++;
            if (currentMonth > 12) {
                currentMonth = 1;
                currentYear++;
            }
            renderCalendar();
        });

        // Inicialización al cargar la página
        renderCalendar();
        if (tipoModalidad === 'privado') {
            setModalidad('privado');
        } else {
            calculateTotal();
        }

        // Validaciones de formulario personalizadas (evita fallas con inputs ocultos required)
        const reservaForm = document.getElementById('reserva-form');
        if (reservaForm) {
            reservaForm.addEventListener('submit', (e) => {
                const fechaVal = fechaSelect.value;
                const horarioVal = horarioSelect.value;

                if (!fechaVal) {
                    e.preventDefault();
                    statusBox.classList.remove('hidden');
                    statusBox.className = 'text-[10px] p-2.5 rounded-lg border border-rose-250 bg-rose-50 text-rose-700 font-bold animate-pulse';
                    statusBox.textContent = 'Por favor, selecciona una fecha disponible en el calendario.';
                    
                    // Desplazar suavemente al calendario
                    const calLabel = document.getElementById('cal-month-label');
                    if (calLabel) {
                        calLabel.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                    return;
                }

                if (!horarioVal || horarioVal.includes('Selecciona')) {
                    e.preventDefault();
                    statusBox.classList.remove('hidden');
                    statusBox.className = 'text-[10px] p-2.5 rounded-lg border border-rose-250 bg-rose-50 text-rose-700 font-bold animate-pulse';
                    statusBox.textContent = 'Por favor, selecciona un horario de salida.';
                    horarioSelect.focus();
                    return;
                }

                if (esApiExterna) {
                    const idiomaSel = document.getElementById('reserva-idioma');
                    const hotelSel = document.getElementById('reserva-hotel');

                    if (!idiomaSel.value) {
                        e.preventDefault();
                        statusBox.classList.remove('hidden');
                        statusBox.className = 'text-[10px] p-2.5 rounded-lg border border-rose-250 bg-rose-50 text-rose-700 font-bold animate-pulse';
                        statusBox.textContent = 'Por favor, selecciona el idioma del tour.';
                        idiomaSel.focus();
                        return;
                    }

                    if (!hotelSel.value) {
                        e.preventDefault();
                        statusBox.classList.remove('hidden');
                        statusBox.className = 'text-[10px] p-2.5 rounded-lg border border-rose-250 bg-rose-50 text-rose-700 font-bold animate-pulse';
                        statusBox.textContent = 'Por favor, selecciona tu hotel para la recogida.';
                        hotelSel.focus();
                        return;
                    }
                }
            });
        }

        // ========================================================
        // TOURS DE API EXTERNA: pax por categoría, idioma y hotel/pickup
        // ========================================================
        const esApiExterna = {{ $tour->origen === 'api_externa' ? 'true' : 'false' }};

        function syncPaxTotal() {
            const adultos = parseInt(document.getElementById('reserva-adultos').value) || 0;
            const menores = parseInt(document.getElementById('reserva-menores').value) || 0;
            const infantes = parseInt(document.getElementById('reserva-infantes').value) || 0;
            const total = Math.max(1, adultos + menores + infantes);

            qtyInput.value = total;
            calculateTotal();
            verifyAvailability();
        }

        if (esApiExterna) {
            let hotelesDisponibles = [];

            // Idiomas
            fetch(`/cart/tour-api/idiomas?tour_id={{ $tour->id }}`)
                .then(r => r.json())
                .then(data => {
                    const select = document.getElementById('reserva-idioma');
                    select.innerHTML = '<option value="">Selecciona un idioma</option>';
                    (data.idiomas || []).forEach(idioma => {
                        const option = document.createElement('option');
                        option.value = idioma.Id;
                        option.textContent = idioma.Idioma;
                        if (idioma.Id === 'ESP') option.selected = true;
                        select.appendChild(option);
                    });
                })
                .catch(() => {
                    document.getElementById('reserva-idioma').innerHTML = '<option value="">No se pudo cargar</option>';
                });

            // Hoteles
            fetch(`/cart/tour-api/hoteles?tour_id={{ $tour->id }}`)
                .then(r => r.json())
                .then(data => {
                    hotelesDisponibles = data.hoteles || [];
                    renderHotelOptions(hotelesDisponibles);
                })
                .catch(() => {
                    document.getElementById('reserva-hotel').innerHTML = '<option value="">No se pudo cargar</option>';
                });

            function renderHotelOptions(lista) {
                const select = document.getElementById('reserva-hotel');
                select.innerHTML = '<option value="">Selecciona tu hotel</option>';
                lista.forEach(hotel => {
                    const option = document.createElement('option');
                    option.value = hotel.Hotel;
                    option.dataset.hotelId = hotel.Id;
                    option.textContent = `${hotel.Hotel}${hotel.Zona ? ' — ' + hotel.Zona : ''}`;
                    select.appendChild(option);
                });
            }

            window.filtrarHoteles = function () {
                const query = document.getElementById('reserva-hotel-filtro').value.toLowerCase().trim();
                if (!query) {
                    renderHotelOptions(hotelesDisponibles);
                    return;
                }
                renderHotelOptions(hotelesDisponibles.filter(h => h.Hotel.toLowerCase().includes(query)));
            };

            window.onHotelSeleccionado = function () {
                const select = document.getElementById('reserva-hotel');
                const hotel = hotelesDisponibles.find(h => h.Hotel === select.value);
                const lobbySelect = document.getElementById('reserva-hotel-lobby');

                if (hotel && Array.isArray(hotel.lobbys) && hotel.lobbys.length > 0) {
                    lobbySelect.innerHTML = '<option value="">Selecciona el lobby</option>';
                    hotel.lobbys.forEach(l => {
                        const option = document.createElement('option');
                        option.value = l.lobby || l.Id_lobby || '';
                        option.textContent = l.lobby || l.Id_lobby || '';
                        lobbySelect.appendChild(option);
                    });
                    lobbySelect.classList.remove('hidden');
                } else {
                    lobbySelect.classList.add('hidden');
                    lobbySelect.value = '';
                }

                actualizarPickup();
            };

            function actualizarPickup() {
                const hotel = document.getElementById('reserva-hotel').value;
                const horario = horarioSelect.value;
                const lobby = document.getElementById('reserva-hotel-lobby').value;
                const infoBox = document.getElementById('reserva-pickup-info');
                const pickupInput = document.getElementById('reserva-pickup-horario');

                if (!hotel || !horario || horario.includes('Selecciona')) {
                    infoBox.classList.add('hidden');
                    pickupInput.value = '';
                    return;
                }

                fetch(`/cart/tour-api/pickup`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    body: JSON.stringify({ tour_id: '{{ $tour->id }}', hotel, horario, lobby })
                })
                    .then(r => r.json())
                    .then(data => {
                        if (data.pickup && data.pickup.hora) {
                            pickupInput.value = data.pickup.hora;
                            infoBox.textContent = `🕐 Hora estimada de recogida: ${data.pickup.hora}`;
                            infoBox.classList.remove('hidden');
                        } else {
                            pickupInput.value = '';
                            infoBox.classList.add('hidden');
                        }
                    })
                    .catch(() => {
                        pickupInput.value = '';
                        infoBox.classList.add('hidden');
                    });
            }

            // Recalcular la hora de recogida si el cliente cambia el horario de salida
            horarioSelect.addEventListener('change', actualizarPickup);
        }

        // --- MODAL LIGHTBOX: GALERÍA DE EXPERIENCIAS DE VIAJEROS ---
        @if(count($tour->experiencias_fotos) > 0)
        const expPhotosList = @json($tour->experiencias_fotos);
        let currentExpIndex = 0;

        function openExperienceModal(index = 0) {
            currentExpIndex = index;
            updateExpModalImage();
            document.getElementById('exp-modal').classList.remove('hidden');
        }

        function closeExperienceModal() {
            document.getElementById('exp-modal').classList.add('hidden');
        }

        function navigateExperience(direction) {
            currentExpIndex += direction;
            if (currentExpIndex < 0) currentExpIndex = expPhotosList.length - 1;
            if (currentExpIndex >= expPhotosList.length) currentExpIndex = 0;
            updateExpModalImage();
        }

        function updateExpModalImage() {
            const img = document.getElementById('exp-modal-img');
            const counter = document.getElementById('exp-modal-counter');
            if (img && expPhotosList[currentExpIndex]) {
                img.src = expPhotosList[currentExpIndex];
            }
            if (counter) {
                counter.textContent = `${currentExpIndex + 1} de ${expPhotosList.length}`;
            }
        }
        @endif
    </script>

    <!-- MODAL LIGHTBOX GALERÍA DE EXPERIENCIAS DE VIAJEROS (solo si hay fotos reales) -->
    @if(count($tour->experiencias_fotos) > 0)
    <div id="exp-modal" class="hidden fixed inset-0 z-[60] flex items-center justify-center bg-black/80 backdrop-blur-md animate-fade-in p-4">
        <div class="relative max-w-4xl w-full flex flex-col items-center gap-4">
            <!-- Botón cerrar -->
            <button onclick="closeExperienceModal()" class="absolute -top-10 right-0 text-white hover:text-brand-orange text-2xl font-bold cursor-pointer">
                ✕ Cerrar
            </button>

            <!-- Cabecera modal -->
            <div class="text-center text-white space-y-1">
                <h3 class="text-base font-black uppercase tracking-wider text-brand-teal">Galería de Experiencias</h3>
                <p class="text-xs text-slate-300 font-semibold">Fotos compartidas por viajeros en este tour · <span id="exp-modal-counter">1 de 6</span></p>
            </div>

            <!-- Imagen principal -->
            <div class="relative w-full max-h-[70vh] flex items-center justify-center overflow-hidden rounded-3xl border border-white/20 bg-black/40 shadow-2xl">
                <img id="exp-modal-img" src="" alt="Experiencia real" class="max-h-[70vh] w-auto object-contain rounded-2xl">

                <!-- Flecha previa -->
                <button onclick="navigateExperience(-1)" class="absolute left-4 top-1/2 -translate-y-1/2 h-12 w-12 rounded-full bg-black/50 hover:bg-brand-teal text-white flex items-center justify-center text-xl font-bold transition-colors cursor-pointer backdrop-blur-sm">
                    ❮
                </button>
                <!-- Flecha siguiente -->
                <button onclick="navigateExperience(1)" class="absolute right-4 top-1/2 -translate-y-1/2 h-12 w-12 rounded-full bg-black/50 hover:bg-brand-teal text-white flex items-center justify-center text-xl font-bold transition-colors cursor-pointer backdrop-blur-sm">
                    ❯
                </button>
            </div>

            <!-- Tira de miniaturas -->
            <div class="flex items-center gap-2 overflow-x-auto max-w-full py-2 px-1">
                @foreach($tour->experiencias_fotos as $idx => $f)
                    <img src="{{ $f }}"
                         alt="Thumb"
                         onclick="openExperienceModal({{ $idx }})"
                         class="h-14 w-20 rounded-xl object-cover border-2 border-transparent hover:border-brand-teal cursor-pointer opacity-70 hover:opacity-100 transition-all">
                @endforeach
            </div>
        </div>
    </div>
    @endif
@endsection

