@extends('layouts.app')

<!-- 
 * @file show.blade.php
 * @description Vista Blade para los detalles de un tour individual. Incluye galería interactiva, acordeón de itinerario dinámico, inclusiones/exclusiones personalizadas y widget lateral de reserva con calendario estilo Airbnb. Rediseñado a tema claro con colores corporativos.
 * @date 2026-06-29
 * @author Antigravity
 -->

@section('title', $tour->nombre . ' - Atti Tours')

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

        <!-- SECCIÓN DE GALERÍA -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-10">
            <!-- Imagen Principal -->
            <div class="lg:col-span-2 overflow-hidden rounded-3xl border border-slate-200 shadow-md aspect-[16/10]">
                <img id="main-gallery-img" src="{{ $tour->imagen_destacada }}" alt="{{ $tour->nombre }}" class="h-full w-full object-cover transition-all duration-300">
            </div>

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
        </div>

        <!-- GRID DE INFORMACIÓN Y RESERVA -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 items-start">
            
            <!-- CONTENIDO DE DETALLES (Izquierda) -->
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
                                    {{ $tour->punto_encuentro ?: 'Oficina Principal de Atti Tours: Boulevard Kukulcán Km 9.5, Zona Hotelera, Cancún, Q.R., México. (Frente al Centro de Convenciones).' }}
                                </p>
                            </div>
                        </div>
                        
                        <!-- Mapa Virtual (Estilo Premium) -->
                        <div class="relative w-full h-48 rounded-xl bg-slate-50 border border-slate-200 flex items-center justify-center overflow-hidden group">
                            <!-- Fondo abstracto simulando mapa con grid y radar -->
                            <div class="absolute inset-0 bg-white bg-grid-pattern opacity-85"></div>
                            
                            <!-- Radar animado -->
                            <div class="absolute h-32 w-32 rounded-full border border-brand-teal/20 bg-brand-teal/5 animate-ping duration-2000"></div>
                            <div class="absolute h-16 w-16 rounded-full border border-brand-teal/35 bg-brand-teal/10"></div>
                            
                            <!-- Pin de ubicación -->
                            <div class="relative z-10 flex flex-col items-center gap-1 group-hover:scale-105 transition-transform duration-300">
                                <div class="h-5 w-5 rounded-full bg-brand-teal border-2 border-white shadow-md flex items-center justify-center animate-bounce">
                                    <span class="h-2 w-2 rounded-full bg-white"></span>
                                </div>
                                <span class="text-[9px] font-bold uppercase tracking-widest text-white bg-brand-teal px-2 py-0.5 rounded border border-brand-teal-hover shadow-md backdrop-blur">
                                    Punto de Encuentro
                                </span>
                            </div>
                        </div>
                    </div>
                <!-- GALERÍA DE EXPERIENCIAS DE VIAJEROS (Modificación 3) -->
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
                    @php $expFotos = $tour->experiencias_fotos; @endphp
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
            </div>

            <!-- PANEL DE RESERVA FLOTANTE (Derecha) -->
            <aside class="lg:col-span-1 h-fit p-6 rounded-2xl border border-slate-200 bg-white shadow-lg sticky top-24">
                <div class="border-b border-slate-200 pb-4 mb-5 flex justify-between items-baseline">
                    <span class="text-xs font-bold text-slate-500 uppercase tracking-widest">Precio por Persona</span>
                    <span class="text-xl font-black text-brand-teal">${{ number_format($tour->precio_base_usd) }} MXN</span>
                </div>

                <form id="reserva-form" action="{{ route('cart.add') }}" method="POST" class="flex flex-col gap-4">
                    @csrf
                    <input type="hidden" name="tour_id" value="{{ $tour->id }}">

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

                    <!-- AJAX Alerta de Disponibilidad -->
                    <div id="availability-status" class="hidden text-[10px] p-2.5 rounded-lg border"></div>

                    <!-- Desglose de Precios (Calculador) -->
                    <div class="mt-2 p-3.5 rounded-xl border border-slate-200 bg-slate-50 text-xs flex flex-col gap-2 font-semibold">
                        <div class="flex items-center justify-between text-slate-500">
                            <span>Base:</span>
                            <span>${{ number_format($tour->precio_base_usd) }} MXN x <span id="summary-qty">1</span></span>
                        </div>
                        <div class="flex items-center justify-between border-t border-slate-200 pt-2 font-bold text-slate-700">
                            <span>Total:</span>
                            <span class="text-sm text-brand-teal font-black" id="summary-total">${{ number_format($tour->precio_base_usd) }} MXN</span>
                        </div>
                    </div>

                    <!-- Botón de Envío -->
                    <button type="submit" class="w-full h-11 inline-flex items-center justify-center rounded-xl bg-gradient-to-r from-brand-orange to-brand-orange-hover hover:opacity-95 text-xs font-bold uppercase tracking-widest text-white shadow-md shadow-brand-orange/15 cursor-pointer transition-all hover:scale-[1.01]">
                        {{ __('addToCart') }}
                    </button>
                </form>
            </aside>
        </div>
    </div>

    <!-- SCRIPTS JS DETALLES -->
    <script>
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
        const qtyInput = document.getElementById('reserva-qty');
        const summaryQty = document.getElementById('summary-qty');
        const summaryTotal = document.getElementById('summary-total');

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
            summaryQty.textContent = qty;
            summaryTotal.textContent = `$${(qty * basePrice).toFixed(0)} MXN`;
        }

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
            fetch(`/tours/{{ $tour->id }}/availability?fecha=${fechaVal}`)
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
        const availableDates = {
            @foreach($fechas as $f)
                "{{ $f->fecha->format('Y-m-d') }}": true,
            @endforeach
        };

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
            });
        }

        // --- MODAL LIGHTBOX: GALERÍA DE EXPERIENCIAS DE VIAJEROS ---
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
    </script>

    <!-- MODAL LIGHTBOX GALERÍA DE EXPERIENCIAS DE VIAJEROS -->
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
@endsection

