@extends('layouts.app')

<!-- 
 * @file catalog.blade.php
 * @description Vista Blade para el catálogo de tours. Contiene filtros avanzados y el listado dinámico de tours disponibles. Adaptado a tema claro con colores corporativos e identidad de la marca. Se corrigió el filtro para que no quede fijo (sticky) en dispositivos móviles y PC, permitiendo el scroll correcto.
 * @date 2026-07-24
 * @author Antigravity
 -->

@section('title', 'Tours y Excursiones - Atti Tours')

@section('content')
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-10">
        
        <!-- CABECERA -->
        <div class="flex flex-col md:flex-row md:items-end justify-between gap-4 border-b border-slate-200 pb-6 mb-8">
            <div>
                <h1 class="text-3xl font-black tracking-tight text-slate-800">
                    {{ __('navTours') }}
                </h1>
                <p class="text-xs text-slate-500 mt-1 font-semibold">
                    Encuentra la aventura perfecta para tus vacaciones en el Caribe Mexicano.
                </p>
            </div>
            
            <!-- Contador de resultados -->
            <div class="text-[10px] font-bold uppercase tracking-wider text-slate-500 bg-slate-50 border border-slate-200 px-3 py-1.5 rounded-lg w-fit shadow-xs">
                Resultados: <span class="text-brand-teal font-black">{{ $tours->count() }}</span>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
            
            <!-- PANEL DE FILTROS LATERAL -->
            <aside class="lg:col-span-1 h-fit p-6 rounded-2xl border border-slate-200 bg-white shadow-md">
                <div class="flex items-center justify-between border-b border-slate-200 pb-4 mb-5">
                    <h2 class="text-sm font-bold uppercase tracking-wider text-slate-800">Filtros</h2>
                    <a href="{{ route('catalog') }}" class="text-[10px] font-bold uppercase tracking-widest text-brand-orange hover:text-brand-orange-hover transition-colors">
                        Limpiar
                    </a>
                </div>

                <form action="{{ route('catalog') }}" method="GET" class="flex flex-col gap-5">
                    
                    <!-- Búsqueda -->
                    <div class="flex flex-col gap-1.5">
                        <label class="text-[10px] font-bold uppercase tracking-wider text-slate-500">
                            Palabra Clave
                        </label>
                        <div class="relative">
                            <input type="text" name="search" value="{{ request('search') }}" placeholder="{{ __('searchPlaceholder') }}" class="w-full h-9 rounded-lg border border-slate-200 bg-slate-50 px-3 text-xs text-slate-800 placeholder-slate-400 focus:border-brand-teal focus:bg-white focus:ring-0 focus:outline-none transition-colors">
                        </div>
                    </div>

                    <!-- Destino / Ubicación -->
                    <div class="flex flex-col gap-1.5">
                        <label class="text-[10px] font-bold uppercase tracking-wider text-slate-500">
                            {{ __('location') }}
                        </label>
                        <select name="location" class="w-full h-9 rounded-lg border border-slate-200 bg-slate-50 px-3 text-xs text-slate-700 focus:border-brand-teal focus:bg-white focus:ring-0 focus:outline-none cursor-pointer">
                            <option value="all">Todos los destinos</option>
                            @foreach($destinos as $dest)
                                <option value="{{ $dest }}" {{ request('location') === $dest ? 'selected' : '' }}>
                                    {{ $dest }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Fecha (Selector de Calendario Moderno Tipo Airbnb) -->
                    <div class="relative flex flex-col gap-1.5 cursor-pointer" id="filter-date-wrapper">
                        <label class="text-[10px] font-bold uppercase tracking-wider text-slate-500">
                            {{ __('searchDate') }}
                        </label>
                        <input type="text" id="filter-date-display" readonly placeholder="¿Cuándo viajas?" class="w-full h-9 rounded-lg border border-slate-200 bg-slate-50 px-3 text-xs text-slate-750 focus:border-brand-teal focus:bg-white focus:ring-0 focus:outline-none cursor-pointer">
                        <input type="hidden" name="date" id="filter-date-value" value="{{ request('date') }}">
                        <input type="hidden" name="flexibility" id="filter-flexibility-value" value="{{ request('flexibility', '0') }}">
                        <input type="hidden" name="flexible_months" id="filter-flexible-months-value" value="{{ request('flexible_months', '') }}">

                        <!-- Dropdown del Calendario Moderno Tipo Airbnb -->
                        <div id="airbnb-filter-calendar" class="hidden absolute top-full left-0 mt-2 z-[100] w-[92vw] sm:w-[480px] md:w-[600px] p-5 rounded-3xl bg-white border border-slate-200 shadow-2xl animate-fade-in text-left cursor-default">
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
                                            <button type="button" id="prev-month-airbnb" class="p-1.5 rounded-full hover:bg-slate-100 text-slate-655 hover:text-brand-teal transition-colors cursor-pointer">
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
                                            <button type="button" id="next-month-airbnb" class="p-1.5 rounded-full hover:bg-slate-100 text-slate-655 hover:text-brand-teal transition-colors cursor-pointer">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
                                            </button>
                                        </div>
                                        <div class="grid grid-cols-7 gap-1 text-center text-[9px] font-bold text-slate-400 uppercase tracking-widest mb-3">
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

                    <!-- Rango de Precio -->
                    <div class="flex flex-col gap-1.5">
                        <div class="flex items-center justify-between">
                            <label class="text-[10px] font-bold uppercase tracking-wider text-slate-500">
                                {{ __('searchPrice') }}
                            </label>
                            <span id="price-val" class="text-xs font-black text-brand-teal">
                                ${{ request('price', $precioMaximo) }} USD
                            </span>
                        </div>
                        <input type="range" name="price" id="price-range" min="40" max="{{ $precioMaximo }}" step="5" value="{{ request('price', $precioMaximo) }}" class="w-full accent-brand-teal cursor-pointer">
                    </div>

                    <!-- Botón Aplicar -->
                    <button type="submit" class="w-full h-10 inline-flex items-center justify-center rounded-lg bg-gradient-to-r from-brand-teal to-brand-teal-hover hover:opacity-95 text-xs font-bold uppercase tracking-wider text-white shadow-md shadow-brand-teal/15 cursor-pointer transition-all">
                        Filtrar
                    </button>
                </form>
            </aside>

            <!-- LISTADO DE TOURS -->
            <main class="lg:col-span-3">
                @if($tours->isEmpty())
                    <!-- Estado vacío -->
                    <div class="flex flex-col items-center justify-center text-center p-12 rounded-3xl border border-slate-200 bg-white shadow-md">
                        <div class="p-4 rounded-full bg-slate-50 border border-slate-200 text-slate-400 mb-4 animate-float">
                            <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <h3 class="text-base font-bold text-slate-800">No se encontraron tours</h3>
                        <p class="text-xs text-slate-500 mt-2 max-w-sm leading-relaxed font-semibold">
                            Prueba ajustando los filtros de búsqueda, modificando las fechas o el precio máximo.
                        </p>
                        <a href="{{ route('catalog') }}" class="mt-5 inline-flex h-9 items-center justify-center px-4 rounded-lg bg-slate-50 hover:bg-slate-100 border border-slate-200 text-xs font-bold text-slate-655 hover:text-slate-850 shadow-xs transition-colors">
                            Ver Todos los Tours
                        </a>
                    </div>
                @else
                    <!-- Grid de tarjetas -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        @foreach($tours as $tour)
                            <div class="group flex flex-col rounded-2xl border border-slate-200 bg-white overflow-hidden shadow-md hover:border-slate-300 hover:shadow-lg transition-all duration-300">
                                
                                <!-- Imagen -->
                                <div class="relative aspect-[16/10] overflow-hidden">
                                    <img src="{{ $tour->imagen_destacada }}" alt="{{ $tour->nombre }}" class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105">
                                    <div class="absolute inset-0 bg-gradient-to-t from-slate-950/20 to-transparent"></div>
                                    
                                    <!-- Badges -->
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
                                    <a href="{{ route('tours.show', $tour->id) }}" class="w-full inline-flex h-10 items-center justify-center rounded-xl bg-slate-50 hover:bg-slate-100 border border-slate-200 text-xs font-bold text-slate-655 hover:text-slate-850 transition-colors shadow-xs cursor-pointer">
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
                priceVal.textContent = `$${e.target.value} USD`;
            });
        }

        // ============================================================
        // LÓGICA DEL CALENDARIO DE BÚSQUEDA ESTILO AIRBNB (CATÁLOGO)
        // ============================================================
        // Autor: Antigravity
        // Última modificación: 2026-07-24
        
        let filterActiveTab = '{{ request('flexible_months') ? 'flexible' : 'fechas' }}';
        let filterSelectedDate = '{{ request('date') }}' || null;
        let filterFlexibility = parseInt('{{ request('flexibility', '0') }}') || 0;
        let filterFlexibleMonths = '{{ request('flexible_months') }}' ? '{{ request('flexible_months') }}'.split(',') : [];
        
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

        // Autoejecutar para pre-poblar display si ya hay valores de búsqueda activos
        (function initPrepopulatedValues() {
            if (filterSelectedDate) {
                const partes = filterSelectedDate.split('-');
                const mesesCortos = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];
                const labelMes = mesesCortos[parseInt(partes[1]) - 1];
                let displayText = `${partes[2]} ${labelMes}`;
                if (filterFlexibility > 0) {
                    displayText += ` ±${filterFlexibility}d`;
                }
                filterDisplay.value = displayText;
                // Activar boton flexibilidad correcto
                document.querySelectorAll('.flexibility-btn').forEach(b => {
                    b.classList.remove('active');
                    if (parseInt(b.getAttribute('data-flex')) === filterFlexibility) {
                        b.classList.add('active');
                    }
                });
            } else if (filterFlexibleMonths.length > 0) {
                const mesesNombres = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
                const labels = filterFlexibleMonths.map(m => {
                    const p = m.split('-');
                    return mesesNombres[parseInt(p[1]) - 1];
                });
                filterDisplay.value = `Flexible: ${labels.join(', ')}`;
                // Activar pestaña Flexible
                if (tabFlexible) tabFlexible.click();
            }
        })();
    </script>
@endsection
