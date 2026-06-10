@extends('layouts.app')

<!-- 
 * @file show.blade.php
 * @description Vista Blade para los detalles de un tour individual. Incluye galería interactiva, acordeón de itinerario dinámico, inclusiones/exclusiones personalizadas y widget lateral de reserva con calendario estilo Airbnb.
 * @date 2026-06-09
 * @author Antigravity
 -->

@section('title', $tour->nombre . ' - Atti Tours')

@section('content')
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-10">
        
        <!-- ENLACE VOLVER -->
        <div class="mb-6">
            <a href="{{ route('catalog') }}" class="inline-flex items-center gap-1.5 text-xs font-semibold uppercase tracking-wider text-slate-400 hover:text-cyan-400 transition-colors">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Volver al catálogo
            </a>
        </div>

        <!-- CABECERA DEL TOUR -->
        <div class="mb-8">
            <div class="flex flex-wrap gap-2 mb-3">
                <span class="px-2.5 py-1 text-[9px] font-black uppercase tracking-wider rounded bg-slate-900 text-cyan-400 border border-slate-800">
                    {{ $tour->ubicacion }}
                </span>
                @foreach($tour->tags as $tag)
                    <span class="text-[9px] font-semibold text-slate-400 bg-slate-900 border border-slate-800/60 px-2.5 py-1 rounded">
                        #{{ $tag }}
                    </span>
                @endforeach
            </div>
            <h1 class="text-2xl sm:text-4xl font-black text-white leading-tight">
                {{ $tour->nombre }}
            </h1>
            <p class="text-xs text-slate-400 mt-2 flex items-center gap-1.5 font-medium">
                <svg class="h-4 w-4 text-cyan-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                {{ $tour->ubicacion }}, {{ $tour->pais }}
            </p>
        </div>

        <!-- SECCIÓN DE GALERÍA -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-10">
            <!-- Imagen Principal -->
            <div class="lg:col-span-2 overflow-hidden rounded-3xl border border-slate-900 shadow-2xl aspect-[16/10]">
                <img id="main-gallery-img" src="{{ $tour->imagen_destacada }}" alt="{{ $tour->nombre }}" class="h-full w-full object-cover transition-all duration-300">
            </div>

            <!-- Miniaturas -->
            <div class="flex lg:flex-col gap-3 overflow-x-auto lg:overflow-y-auto lg:h-[400px] pb-2 lg:pb-0">
                <!-- Miniatura 1 (Destacada) -->
                <button onclick="changeGalleryImage('{{ $tour->imagen_destacada }}', this)" class="gallery-thumb-btn shrink-0 w-28 lg:w-full aspect-[16/10] overflow-hidden rounded-2xl border-2 border-cyan-400 shadow-md">
                    <img src="{{ $tour->imagen_destacada }}" alt="Gallery 1" class="h-full w-full object-cover">
                </button>
                <!-- Galería Restante -->
                @foreach($tour->galeria as $index => $gImg)
                    @if($gImg !== $tour->imagen_destacada)
                        <button onclick="changeGalleryImage('{{ $gImg }}', this)" class="gallery-thumb-btn shrink-0 w-28 lg:w-full aspect-[16/10] overflow-hidden rounded-2xl border-2 border-transparent hover:border-slate-800 shadow-md transition-all">
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
                <div class="grid grid-cols-2 gap-4 p-2 rounded-2xl border border-slate-900 bg-slate-900/20 backdrop-blur-sm">
                    <div class="text-center py-4 border-r border-slate-900">
                        <p class="text-[8px] font-bold text-slate-500 uppercase tracking-widest">{{ __('duration') }}</p>
                        <p class="text-sm font-bold text-white mt-1">{{ $tour->duracion }}</p>
                    </div>
                    <div class="text-center py-4">
                        <p class="text-[8px] font-bold text-slate-500 uppercase tracking-widest">{{ __('maxCapacity') }}</p>
                        <p class="text-sm font-bold text-white mt-1">{{ $tour->cupo_maximo }} {{ __('people') }}</p>
                    </div>
                </div>

                <!-- Descripción Larga -->
                <div>
                    <h2 class="text-lg font-bold text-slate-200 mb-3">Descripción General</h2>
                    <p class="text-sm text-slate-400 leading-relaxed">
                        {{ $tour->detalle }}
                    </p>
                </div>

                <!-- ITINERARIO ACORDEÓN INTERACTIVO -->
                <div>
                    <h2 class="text-lg font-bold text-slate-200 mb-4">Itinerario del Viaje</h2>
                    <div class="flex flex-col gap-3">
                        @if(!empty($tour->itinerario) && is_array($tour->itinerario))
                            @foreach($tour->itinerario as $index => $paso)
                                <div class="border border-slate-900 rounded-xl bg-slate-950/40 overflow-hidden">
                                    <button onclick="toggleAccordion('itinerary-{{ $index }}')" class="w-full flex items-center justify-between p-4 text-left font-bold text-xs uppercase tracking-wider text-slate-200 hover:text-white transition-colors cursor-pointer">
                                        <span>Paso {{ $index + 1 }}: {{ $paso['titulo'] }}</span>
                                        <svg id="icon-itinerary-{{ $index }}" class="h-4 w-4 opacity-70 transform {{ $index === 0 ? 'rotate-180' : '' }} transition-transform duration-200" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </button>
                                    <div id="content-itinerary-{{ $index }}" class="{{ $index === 0 ? '' : 'hidden' }} p-4 pt-0 text-xs text-slate-400 leading-relaxed">
                                        {{ $paso['descripcion'] }}
                                    </div>
                                </div>
                            @endforeach
                        @else
                            <!-- Fallback si no hay itinerario -->
                            <!-- Punto 1 -->
                            <div class="border border-slate-900 rounded-xl bg-slate-950/40 overflow-hidden">
                                <button onclick="toggleAccordion('itinerary-1')" class="w-full flex items-center justify-between p-4 text-left font-bold text-xs uppercase tracking-wider text-slate-200 hover:text-white transition-colors cursor-pointer">
                                    <span>Paso 1: Punto de Partida y Registro</span>
                                    <svg id="icon-itinerary-1" class="h-4 w-4 opacity-70 transform rotate-180 transition-transform duration-200" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </button>
                                <div id="content-itinerary-1" class="p-4 pt-0 text-xs text-slate-400 leading-relaxed">
                                    Nos reuniremos en el punto de encuentro seleccionado 30 minutos antes de la hora programada de salida. Tras el registro y firma de exenciones de responsabilidad, conocerás a tus guías y recibirás una sesión informativa sobre seguridad.
                                </div>
                            </div>

                            <!-- Punto 2 -->
                            <div class="border border-slate-900 rounded-xl bg-slate-950/40 overflow-hidden">
                                <button onclick="toggleAccordion('itinerary-2')" class="w-full flex items-center justify-between p-4 text-left font-bold text-xs uppercase tracking-wider text-slate-200 hover:text-white transition-colors cursor-pointer">
                                    <span>Paso 2: Inicio de la Actividad</span>
                                    <svg id="icon-itinerary-2" class="h-4 w-4 opacity-70 transform transition-transform duration-200" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </button>
                                <div id="content-itinerary-2" class="hidden p-4 pt-0 text-xs text-slate-400 leading-relaxed">
                                    Abordaremos el transporte especializado (embarcación de lujo o van climatizada). Iniciaremos el trayecto disfrutando de paisajes hermosos en el camino. Los guías te compartirán datos históricos sobre la zona.
                                </div>
                            </div>

                            <!-- Punto 3 -->
                            <div class="border border-slate-900 rounded-xl bg-slate-950/40 overflow-hidden">
                                <button onclick="toggleAccordion('itinerary-3')" class="w-full flex items-center justify-between p-4 text-left font-bold text-xs uppercase tracking-wider text-slate-200 hover:text-white transition-colors cursor-pointer">
                                    <span>Paso 3: Almuerzo y Tiempo Libre</span>
                                    <svg id="icon-itinerary-3" class="h-4 w-4 opacity-70 transform transition-transform duration-200" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </button>
                                <div id="content-itinerary-3" class="hidden p-4 pt-0 text-xs text-slate-400 leading-relaxed">
                                    Disfrutaremos de una deliciosa comida local o buffet (según incluya el tour). Posteriormente, dispondrás de tiempo libre para descansar en los camastros, nadar, hacer fotos del paraíso o comprar souvenirs.
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- QUÉ INCLUYE / QUÉ NO INCLUYE -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Incluye -->
                    <div class="p-6 rounded-2xl border border-slate-900 bg-slate-900/10">
                        <h3 class="text-sm font-bold text-emerald-400 uppercase tracking-wider mb-4 flex items-center gap-2">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Qué Incluye
                        </h3>
                        <ul class="flex flex-col gap-2.5 text-xs text-slate-300">
                            @if(!empty($tour->incluye) && is_array($tour->incluye))
                                @foreach($tour->incluye as $inc)
                                    <li class="flex items-start gap-2">
                                        <span class="text-emerald-500 font-bold mt-0.5">&check;</span>
                                        {{ $inc }}
                                    </li>
                                @endforeach
                            @else
                                <li class="flex items-start gap-2">
                                    <span class="text-emerald-500 font-bold mt-0.5">&check;</span>
                                    Guía certificado bilingüe
                                </li>
                                <li class="flex items-start gap-2">
                                    <span class="text-emerald-500 font-bold mt-0.5">&check;</span>
                                    Transportación ida y vuelta con aire acondicionado
                                </li>
                                <li class="flex items-start gap-2">
                                    <span class="text-emerald-500 font-bold mt-0.5">&check;</span>
                                    Entradas oficiales y chaleco salvavidas
                                </li>
                                <li class="flex items-start gap-2">
                                    <span class="text-emerald-500 font-bold mt-0.5">&check;</span>
                                    Bebidas refrescantes a bordo
                                </li>
                            @endif
                        </ul>
                    </div>

                    <!-- No Incluye -->
                    <div class="p-6 rounded-2xl border border-slate-900 bg-slate-900/10">
                        <h3 class="text-sm font-bold text-rose-400 uppercase tracking-wider mb-4 flex items-center gap-2">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            No Incluye
                        </h3>
                        <ul class="flex flex-col gap-2.5 text-xs text-slate-300">
                            @if(!empty($tour->no_incluye) && is_array($tour->no_incluye))
                                @foreach($tour->no_incluye as $ninc)
                                    <li class="flex items-start gap-2">
                                        <span class="text-rose-500 font-bold mt-0.5">&times;</span>
                                        {{ $ninc }}
                                    </li>
                                @endforeach
                            @else
                                <li class="flex items-start gap-2">
                                    <span class="text-rose-500 font-bold mt-0.5">&times;</span>
                                    Propinas para los guías locales
                                </li>
                                <li class="flex items-start gap-2">
                                    <span class="text-rose-500 font-bold mt-0.5">&times;</span>
                                    Fotos y videos profesionales de recuerdo
                                </li>
                                <li class="flex items-start gap-2">
                                    <span class="text-rose-500 font-bold mt-0.5">&times;</span>
                                    Impuesto de muelle/arrecife (si aplica)
                                </li>
                            @endif
                        </ul>
                    </div>
                </div>

                <!-- PUNTO DE ENCUENTRO MAPA SIMULADO -->
                <div>
                    <h2 class="text-lg font-bold text-slate-200 mb-4">Punto de Encuentro</h2>
                    <div class="p-4 rounded-2xl border border-slate-900 bg-slate-950/40 shadow-xl overflow-hidden flex flex-col gap-4">
                        <div class="flex items-start gap-3">
                            <div class="p-2 rounded bg-indigo-500/10 text-indigo-400 mt-0.5">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                            </div>
                            <div>
                                <h4 class="text-xs font-bold text-white">Lugar de Partida / Reunión</h4>
                                <p class="text-[11px] text-slate-400 leading-relaxed mt-1">
                                    {{ $tour->punto_encuentro ?: 'Oficina Principal de Atti Tours: Boulevard Kukulcán Km 9.5, Zona Hotelera, Cancún, Q.R., México. (Frente al Centro de Convenciones).' }}
                                </p>
                            </div>
                        </div>
                        
                        <!-- Mapa Virtual (Estilo Premium) -->
                        <div class="relative w-full h-48 rounded-xl bg-slate-900 border border-slate-800 flex items-center justify-center overflow-hidden group">
                            <!-- Fondo abstracto simulando mapa con grid y radar -->
                            <div class="absolute inset-0 bg-slate-950 bg-grid-pattern opacity-80"></div>
                            
                            <!-- Radar animado -->
                            <div class="absolute h-32 w-32 rounded-full border border-cyan-500/20 bg-cyan-500/5 animate-ping duration-2000"></div>
                            <div class="absolute h-16 w-16 rounded-full border border-cyan-500/35 bg-cyan-500/10"></div>
                            
                            <!-- Pin de ubicación -->
                            <div class="relative z-10 flex flex-col items-center gap-1 group-hover:scale-105 transition-transform duration-300">
                                <div class="h-5 w-5 rounded-full bg-cyan-400 border-2 border-white shadow-2xl flex items-center justify-center animate-bounce">
                                    <span class="h-2 w-2 rounded-full bg-slate-950"></span>
                                </div>
                                <span class="text-[9px] font-black uppercase tracking-widest text-cyan-400 bg-slate-950/80 px-2 py-0.5 rounded border border-slate-800 backdrop-blur">
                                    Punto de Encuentro
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- PANEL DE RESERVA FLOTANTE (Derecha) -->
            <aside class="lg:col-span-1 h-fit p-6 rounded-2xl border border-slate-900 bg-slate-950/85 backdrop-blur-md shadow-2xl sticky top-24">
                <div class="border-b border-slate-900 pb-4 mb-5 flex justify-between items-baseline">
                    <span class="text-xs font-bold text-slate-400 uppercase tracking-widest">Precio por Persona</span>
                    <span class="text-xl font-black text-cyan-400">${{ number_format($tour->precio_base_usd) }} MXN</span>
                </div>

                <form id="reserva-form" action="{{ route('cart.add') }}" method="POST" class="flex flex-col gap-4">
                    @csrf
                    <input type="hidden" name="tour_id" value="{{ $tour->id }}">

                    <!-- Selector de Fecha (Calendario Estilo Airbnb Premium) -->
                    <div class="flex flex-col gap-2">
                        <label class="text-[10px] font-bold uppercase tracking-wider text-slate-400">
                            {{ __('availableDates') }}
                        </label>
                        <input type="hidden" name="fecha" id="reserva-fecha">
                        
                        <!-- Contenedor del Calendario Visual -->
                        <div class="p-3.5 rounded-xl border border-slate-800 bg-slate-900/40">
                            <!-- Cabecera Mes/Año -->
                            <div class="flex items-center justify-between mb-3 border-b border-slate-900 pb-2">
                                <button type="button" id="cal-prev-month" class="p-1 rounded bg-slate-950 border border-slate-800 text-slate-400 hover:text-white hover:bg-slate-900 transition-colors cursor-pointer text-xs font-black">
                                    &larr;
                                </button>
                                <span id="cal-month-label" class="text-[10px] font-black uppercase tracking-widest text-slate-200"></span>
                                <button type="button" id="cal-next-month" class="p-1 rounded bg-slate-950 border border-slate-800 text-slate-400 hover:text-white hover:bg-slate-900 transition-colors cursor-pointer text-xs font-black">
                                    &rarr;
                                </button>
                            </div>
                            
                            <!-- Días de la semana -->
                            <div class="grid grid-cols-7 gap-1 text-center text-[9px] font-bold text-slate-500 uppercase tracking-wider mb-2">
                                <div>Lu</div>
                                <div>Ma</div>
                                <div>Mi</div>
                                <div>Ju</div>
                                <div>Vi</div>
                                <div>Sá</div>
                                <div>Do</div>
                            </div>
                            
                            <!-- Grid del Mes -->
                            <div id="cal-days-grid" class="grid grid-cols-7 gap-1">
                                <!-- Generado dinámicamente -->
                            </div>
                        </div>
                    </div>

                    <!-- Selector de Horario (Cargado Dinámicamente) -->
                    <div class="flex flex-col gap-1.5" id="horario-container">
                        <label class="text-[10px] font-bold uppercase tracking-wider text-slate-400">
                            Horario de Salida
                        </label>
                        <select name="horario" id="reserva-horario" required class="w-full h-10 rounded-lg border border-slate-800 bg-slate-900 px-3 text-xs text-white focus:border-cyan-500 focus:ring-0 focus:outline-none cursor-pointer">
                            <option value="">Selecciona una fecha</option>
                        </select>
                    </div>

                    <!-- Selector de Personas -->
                    <div class="flex flex-col gap-1.5">
                        <label class="text-[10px] font-bold uppercase tracking-wider text-slate-400">
                            {{ __('selectPeople') }}
                        </label>
                        <div class="flex h-10 rounded-lg border border-slate-800 bg-slate-900 overflow-hidden">
                            <button type="button" onclick="adjustQty(-1)" class="w-10 flex items-center justify-center text-slate-400 hover:text-white hover:bg-slate-800 border-r border-slate-800 transition-colors cursor-pointer">-</button>
                            <input type="number" name="cantidad" id="reserva-qty" value="1" min="1" max="{{ $tour->cupo_maximo }}" class="w-full text-center bg-transparent border-0 text-xs font-bold text-white focus:ring-0 focus:outline-none">
                            <button type="button" onclick="adjustQty(1)" class="w-10 flex items-center justify-center text-slate-400 hover:text-white hover:bg-slate-800 border-l border-slate-800 transition-colors cursor-pointer">+</button>
                        </div>
                    </div>

                    <!-- AJAX Alerta de Disponibilidad -->
                    <div id="availability-status" class="hidden text-[10px] p-2.5 rounded-lg border"></div>

                    <!-- Desglose de Precios (Calculador) -->
                    <div class="mt-2 p-3.5 rounded-xl border border-slate-900/60 bg-slate-900/20 text-xs flex flex-col gap-2">
                        <div class="flex items-center justify-between text-slate-400">
                            <span>Base:</span>
                            <span>${{ number_format($tour->precio_base_usd) }} MXN x <span id="summary-qty">1</span></span>
                        </div>
                        <div class="flex items-center justify-between border-t border-slate-900 pt-2 font-bold text-white">
                            <span>Total:</span>
                            <span class="text-sm text-cyan-400" id="summary-total">${{ number_format($tour->precio_base_usd) }} MXN</span>
                        </div>
                    </div>

                    <!-- Botón de Envío -->
                    <button type="submit" class="w-full h-11 inline-flex items-center justify-center rounded-xl bg-gradient-to-r from-cyan-500 to-indigo-600 hover:opacity-95 text-xs font-bold uppercase tracking-widest text-white shadow-lg cursor-pointer transition-all">
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
            buttons.forEach(btn => btn.classList.replace('border-cyan-400', 'border-transparent'));
            buttonEl.classList.replace('border-transparent', 'border-cyan-400');
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
                statusBox.className = 'text-[10px] p-2.5 rounded-lg border border-emerald-500/20 bg-emerald-500/5 text-emerald-400 font-semibold';
                statusBox.textContent = `Salida disponible. Quedan ${cupoDisp} lugares libres a las ${selectedHora} hrs.`;
                qtyInput.setAttribute('max', cupoDisp);
            } else {
                statusBox.className = 'text-[10px] p-2.5 rounded-lg border border-rose-500/20 bg-rose-500/5 text-rose-400 font-semibold';
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
            let startDay = new Date(currentYear, currentMonth - 1, 1).getDay();
            // Ajustar inicio de semana a Lunes (Lunes = 0, Domingo = 6)
            let emptySlots = startDay === 0 ? 6 : startDay - 1;

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
                dayBtn.className = 'aspect-square rounded-full text-[10px] font-bold flex items-center justify-center transition-all select-none ';
                dayBtn.textContent = day;

                if (isPast) {
                    dayBtn.className += 'text-slate-700 opacity-25 cursor-not-allowed';
                    dayBtn.disabled = true;
                } else if (isAvailable) {
                    if (isSelected) {
                        dayBtn.className += 'bg-gradient-to-r from-cyan-500 to-indigo-600 text-white shadow-lg scale-105';
                    } else {
                        dayBtn.className += 'bg-slate-900/60 border border-slate-800 text-cyan-400 hover:border-cyan-400 hover:text-white cursor-pointer';
                    }
                    
                    dayBtn.addEventListener('click', () => {
                        fechaSelect.value = dateStr;
                        renderCalendar(); // Redibujar selección
                        verifyAvailability(); // Cargar horarios y disponibilidad
                    });
                } else {
                    dayBtn.className += 'text-slate-600 opacity-30 cursor-not-allowed';
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
                    statusBox.className = 'text-[10px] p-2.5 rounded-lg border border-rose-500/20 bg-rose-500/5 text-rose-400 font-semibold animate-pulse';
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
                    statusBox.className = 'text-[10px] p-2.5 rounded-lg border border-rose-500/20 bg-rose-500/5 text-rose-400 font-semibold animate-pulse';
                    statusBox.textContent = 'Por favor, selecciona un horario de salida.';
                    horarioSelect.focus();
                    return;
                }
            });
        }
    </script>
@endsection
