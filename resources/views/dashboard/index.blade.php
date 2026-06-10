@extends('layouts.app')

<!-- 
 * @file index.blade.php
 * @description Vista rediseñada del Dashboard. Soporta pestañas interactivas estilo SPA. Rediseñado el módulo de Tours a un layout dual Master-Detail y añadido un Calendario Global de Reservas interactivo con filtros reactivos integrados.
 * @date 2026-06-10
 * @author Antigravity
-->

@section('title', 'Dashboard de Gestión - Atti Tours')

@section('content')
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-10">
        
        <!-- ENCABEZADO PANEL -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-900 pb-6 mb-8">
            <div>
                <h1 class="text-2xl sm:text-3xl font-black text-white">
                    Panel de Gestión
                </h1>
                <p class="text-xs text-slate-400 mt-1">
                    @if(Auth::user()->isAdmin())
                        Consola de control global para la administración de la plataforma.
                    @else
                        Operadora Local: <span class="text-cyan-400 font-bold">{{ Auth::user()->proveedor->nombre_empresa }}</span>
                    @endif
                </p>
            </div>

            <!-- Botones de Acción Globales -->
            <div class="flex items-center gap-3">
                @if(Auth::user()->isAdmin() || Auth::user()->isProveedor())
                    <a href="{{ route('dashboard.qr.scanner') }}" class="inline-flex h-9 items-center justify-center gap-1.5 px-4 rounded-lg bg-gradient-to-r from-cyan-500 to-indigo-600 text-xs font-bold text-white shadow-lg hover:opacity-95 transition-opacity">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/>
                        </svg>
                        Lector QR
                    </a>
                @endif
            </div>
        </div>

        {{-- ========= BANNER DE NOTIFICACIONES FLASH ========= --}}
        @if(session('success'))
        <div id="flash-success" class="mb-6 flex items-center gap-3 rounded-xl border border-emerald-500/30 bg-emerald-500/10 px-5 py-3.5 text-sm text-emerald-300 shadow-lg backdrop-blur-md" role="alert">
            <svg class="h-5 w-5 shrink-0 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <span class="font-medium">{{ session('success') }}</span>
            <button onclick="document.getElementById('flash-success').remove()" class="ml-auto text-emerald-400 hover:text-white cursor-pointer">✕</button>
        </div>
        @endif
        @if(session('error'))
        <div id="flash-error" class="mb-6 flex items-center gap-3 rounded-xl border border-rose-500/30 bg-rose-500/10 px-5 py-3.5 text-sm text-rose-300 shadow-lg backdrop-blur-md" role="alert">
            <svg class="h-5 w-5 shrink-0 text-rose-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <span class="font-medium">{{ session('error') }}</span>
            <button onclick="document.getElementById('flash-error').remove()" class="ml-auto text-rose-400 hover:text-white cursor-pointer">✕</button>
        </div>
        @endif
        @if($errors->any())
        <div id="flash-validation" class="mb-6 rounded-xl border border-amber-500/30 bg-amber-500/10 px-5 py-3.5 text-sm text-amber-300 shadow-lg backdrop-blur-md" role="alert">
            <div class="flex items-center gap-2 mb-2">
                <svg class="h-5 w-5 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856C18.41 19 19 18.105 19 17V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10c0 1.105.59 2 1.062 2z"/></svg>
                <span class="font-bold">Por favor corrige los siguientes errores:</span>
                <button onclick="document.getElementById('flash-validation').remove()" class="ml-auto text-amber-400 hover:text-white cursor-pointer">✕</button>
            </div>
            <ul class="list-disc list-inside space-y-0.5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        {{-- Pestaña activa desde sesión (enviada por el controlador tras un POST) --}}
        @php
            $initialTab = session('active_tab', null);
            // Si hay errores de validación, inferir la pestaña según el campo
            if (!$initialTab && $errors->any()) {
                $allFields = $errors->keys();
                $provFields = ['nombre_empresa','descripcion','rfc','correo','representante_nombre','representante_telefono','comision_porcentaje','password','foto_url'];
                $tourFields = ['titulo','descripcion_tour','duracion','precio_adulto','precio_nino','destino','proveedor_id'];
                if (array_intersect($allFields, $provFields)) $initialTab = 'proveedores';
                elseif (array_intersect($allFields, $tourFields)) $initialTab = 'tours';
            }
        @endphp

        @if(Auth::user()->isAdmin())
            <!-- PESTAÑAS DE NAVEGACIÓN (Solo Administrador) -->
            <div class="flex border-b border-slate-900 mb-8 overflow-x-auto">
                <button onclick="switchTab('metrics')" id="tab-btn-metrics" class="tab-btn px-6 py-3 border-b-2 border-cyan-400 text-cyan-400 text-xs font-black uppercase tracking-wider transition-all cursor-pointer">
                    📈 Métricas & Reservas
                </button>
                <button onclick="switchTab('proveedores')" id="tab-btn-proveedores" class="tab-btn px-6 py-3 border-b-2 border-transparent text-slate-400 hover:text-white text-xs font-black uppercase tracking-wider transition-all cursor-pointer">
                    🏢 Proveedores
                </button>
                <button onclick="switchTab('tours')" id="tab-btn-tours" class="tab-btn px-6 py-3 border-b-2 border-transparent text-slate-400 hover:text-white text-xs font-black uppercase tracking-wider transition-all cursor-pointer">
                    ⛵ Tours
                </button>
                <button onclick="switchTab('usuarios')" id="tab-btn-usuarios" class="tab-btn px-6 py-3 border-b-2 border-transparent text-slate-400 hover:text-white text-xs font-black uppercase tracking-wider transition-all cursor-pointer">
                    👥 Usuarios
                </button>
            </div>
        @endif

        <!-- CONTENIDO PESTAÑA: MÉTRICAS Y RESERVAS (Compartido o Administrador) -->
        <div id="tab-content-metrics" class="tab-pane animate-fade-in">
            @if(Auth::user()->isAdmin())
                <!-- PANEL DE FILTROS INTERACTIVOS -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 mb-8 p-4 rounded-2xl border border-slate-900 bg-slate-950/20 backdrop-blur-md">
                    <!-- Búsqueda general -->
                    <div class="flex flex-col gap-1.5">
                        <label class="text-[9px] font-bold uppercase tracking-wider text-slate-500">Buscar Cliente / Ticket</label>
                        <input type="text" id="filter-search" oninput="applyReservasFilters()" placeholder="Ej. Juan o TKT-..." class="w-full h-9 rounded-lg border border-slate-800 bg-slate-900 px-3 text-xs text-white placeholder-slate-600 focus:border-cyan-500 focus:outline-none">
                    </div>
                    
                    <!-- Proveedor -->
                    <div class="flex flex-col gap-1.5">
                        <label class="text-[9px] font-bold uppercase tracking-wider text-slate-500">Proveedor</label>
                        <select id="filter-proveedor" onchange="applyReservasFilters()" class="w-full h-9 rounded-lg border border-slate-800 bg-slate-900 px-2 text-xs text-white focus:border-cyan-500 cursor-pointer">
                            <option value="">Todos</option>
                            @foreach($proveedores as $prov)
                                <option value="{{ $prov->id }}">{{ $prov->nombre_empresa }}</option>
                            @endforeach
                        </select>
                    </div>
                    
                    <!-- Tour -->
                    <div class="flex flex-col gap-1.5">
                        <label class="text-[9px] font-bold uppercase tracking-wider text-slate-500">Tour</label>
                        <select id="filter-tour" onchange="applyReservasFilters()" class="w-full h-9 rounded-lg border border-slate-800 bg-slate-900 px-2 text-xs text-white focus:border-cyan-500 cursor-pointer">
                            <option value="">Todos</option>
                            @foreach($tours as $t)
                                <option value="{{ $t->id }}">{{ $t->nombre }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Rango Fechas (Desde) -->
                    <div class="flex flex-col gap-1.5">
                        <label class="text-[9px] font-bold uppercase tracking-wider text-slate-500">Desde (Fecha Compra)</label>
                        <input type="date" id="filter-desde" onchange="applyReservasFilters()" class="w-full h-9 rounded-lg border border-slate-800 bg-slate-900 px-3 text-xs text-white focus:border-cyan-500 cursor-pointer" style="color-scheme: dark;">
                    </div>

                    <!-- Rango Fechas (Hasta) -->
                    <div class="flex flex-col gap-1.5">
                        <label class="text-[9px] font-bold uppercase tracking-wider text-slate-500">Hasta (Fecha Compra)</label>
                        <input type="date" id="filter-hasta" onchange="applyReservasFilters()" class="w-full h-9 rounded-lg border border-slate-800 bg-slate-900 px-3 text-xs text-white focus:border-cyan-500 cursor-pointer" style="color-scheme: dark;">
                    </div>
                </div>
            @endif

            <!-- TARJETAS DE MÉTRICAS -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-10">
                <!-- Ventas Totales -->
                <div class="p-6 rounded-2xl border border-slate-900 bg-slate-950/40 shadow-xl relative overflow-hidden group">
                    <div class="absolute -right-4 -bottom-4 text-slate-900/40 text-7xl select-none font-black opacity-30 group-hover:scale-105 transition-transform duration-300">$</div>
                    <p class="text-[9px] font-bold text-slate-500 uppercase tracking-widest">{{ __('totalSales') }}</p>
                    <p class="text-2xl font-black text-white mt-2">$<span id="metric-total-sales">{{ number_format($totalSales, 2) }}</span> <span class="text-[10px] text-slate-400 font-bold">MXN</span></p>
                </div>

                <!-- Reservas Totales -->
                <div class="p-6 rounded-2xl border border-slate-900 bg-slate-950/40 shadow-xl relative overflow-hidden group">
                    <div class="absolute -right-4 -bottom-4 text-slate-900/40 text-7xl select-none font-black opacity-30 group-hover:scale-105 transition-transform duration-300">#</div>
                    <p class="text-[9px] font-bold text-slate-500 uppercase tracking-widest">{{ __('totalBookings') }}</p>
                    <p class="text-2xl font-black text-white mt-2"><span id="metric-total-bookings">{{ $totalBookings }}</span> <span class="text-[10px] text-slate-400 font-bold">viajes</span></p>
                </div>

                <!-- Comisiones -->
                <div class="p-6 rounded-2xl border border-slate-900 bg-slate-950/40 shadow-xl relative overflow-hidden group">
                    <div class="absolute -right-4 -bottom-4 text-slate-900/40 text-7xl select-none font-black opacity-30 group-hover:scale-105 transition-transform duration-300">%</div>
                    <p class="text-[9px] font-bold text-slate-500 uppercase tracking-widest">
                        {{ Auth::user()->isAdmin() ? __('commissions') : 'Comisión Plataforma' }}
                    </p>
                    <p class="text-2xl font-black text-rose-400 mt-2">$<span id="metric-total-commissions">{{ number_format($totalCommissions, 2) }}</span> <span class="text-[10px] text-slate-500 font-bold">MXN</span></p>
                </div>

                <!-- Ganancia Neta -->
                <div class="p-6 rounded-2xl border border-slate-900 bg-slate-950/40 shadow-xl relative overflow-hidden group">
                    <div class="absolute -right-4 -bottom-4 text-slate-900/40 text-7xl select-none font-black opacity-30 group-hover:scale-105 transition-transform duration-300">✔️</div>
                    <p class="text-[9px] font-bold text-slate-500 uppercase tracking-widest">
                        {{ Auth::user()->isAdmin() ? 'Ganancia Proveedores' : __('netEarnings') }}
                    </p>
                    <p class="text-2xl font-black text-emerald-400 mt-2">$<span id="metric-net-earnings">{{ number_format($netEarnings, 2) }}</span> <span class="text-[10px] text-slate-400 font-bold">MXN</span></p>
                </div>
            </div>

            <!-- GRÁFICO SVG SIMULADO -->
            <div class="p-6 rounded-3xl border border-slate-900 bg-slate-950/60 shadow-2xl mb-10">
                <h2 class="text-xs font-black uppercase tracking-widest text-slate-200 mb-6 border-b border-slate-900 pb-3">
                    Tendencia de Ventas (Últimos Meses)
                </h2>
                <div class="w-full h-48 flex items-end justify-between px-4">
                    <div class="flex flex-col items-center gap-2 w-12 group/bar">
                        <div class="w-full bg-slate-900 rounded-t-lg h-24 flex items-end overflow-hidden border border-slate-800">
                            <div class="w-full bg-gradient-to-t from-indigo-600 to-indigo-500 group-hover/bar:from-cyan-400 group-hover/bar:to-cyan-400 h-[30%] transition-all duration-500"></div>
                        </div>
                        <span class="text-[9px] font-bold text-slate-500 group-hover/bar:text-white transition-colors">Mar</span>
                    </div>
                    <div class="flex flex-col items-center gap-2 w-12 group/bar">
                        <div class="w-full bg-slate-900 rounded-t-lg h-24 flex items-end overflow-hidden border border-slate-800">
                            <div class="w-full bg-gradient-to-t from-indigo-600 to-indigo-500 group-hover/bar:from-cyan-400 group-hover/bar:to-cyan-400 h-[45%] transition-all duration-500"></div>
                        </div>
                        <span class="text-[9px] font-bold text-slate-500 group-hover/bar:text-white transition-colors">Abr</span>
                    </div>
                    <div class="flex flex-col items-center gap-2 w-12 group/bar">
                        <div class="w-full bg-slate-900 rounded-t-lg h-24 flex items-end overflow-hidden border border-slate-800">
                            <div class="w-full bg-gradient-to-t from-indigo-600 to-indigo-500 group-hover/bar:from-cyan-400 group-hover/bar:to-cyan-400 h-[65%] transition-all duration-500"></div>
                        </div>
                        <span class="text-[9px] font-bold text-slate-500 group-hover/bar:text-white transition-colors">May</span>
                    </div>
                    <div class="flex flex-col items-center gap-2 w-12 group/bar">
                        <div class="w-full bg-slate-900 rounded-t-lg h-24 flex items-end overflow-hidden border border-slate-800">
                            <div class="w-full bg-gradient-to-t from-indigo-600 to-indigo-500 group-hover/bar:from-cyan-400 group-hover/bar:to-cyan-400 h-[90%] transition-all duration-500"></div>
                        </div>
                        <span class="text-[9px] font-bold text-slate-500 group-hover/bar:text-white transition-colors">Jun</span>
                    </div>
                </div>
            </div>

            <!-- CALENDARIO GLOBAL DE RESERVAS -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-10">
                <!-- Panel Izquierdo: Calendario mensual -->
                <div class="lg:col-span-2 p-6 rounded-3xl border border-slate-900 bg-slate-950/80 backdrop-blur-md shadow-2xl">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-900 pb-4 mb-5">
                        <div>
                            <h2 class="text-xs font-black uppercase tracking-widest text-cyan-400">
                                Calendario de Operación (Viajes Programados)
                            </h2>
                            <span class="text-[9px] text-slate-500 block mt-1">Visualización de reservas por fecha de realización del tour</span>
                        </div>
                        
                        <!-- Controles de Navegación del Mes -->
                        <div class="flex items-center gap-3 bg-slate-900/40 p-1 rounded-xl border border-slate-800/60">
                            <button type="button" id="res-cal-prev" class="px-2.5 py-1 rounded bg-slate-950 border border-slate-800 text-slate-400 hover:text-white hover:bg-slate-900 transition-colors cursor-pointer text-xs font-black">
                                &larr;
                            </button>
                            <span id="res-cal-month-label" class="text-[10px] font-black uppercase tracking-widest text-slate-200 min-w-[100px] text-center"></span>
                            <button type="button" id="res-cal-next" class="px-2.5 py-1 rounded bg-slate-950 border border-slate-800 text-slate-400 hover:text-white hover:bg-slate-900 transition-colors cursor-pointer text-xs font-black">
                                &rarr;
                            </button>
                        </div>
                    </div>

                    <!-- Días de la semana -->
                    <div class="grid grid-cols-7 gap-1 text-center text-[9px] font-bold text-slate-500 uppercase tracking-wider mb-2">
                        <div>Lun</div>
                        <div>Mar</div>
                        <div>Mié</div>
                        <div>Jue</div>
                        <div>Vie</div>
                        <div>Sáb</div>
                        <div>Dom</div>
                    </div>

                    <!-- Celdas de días -->
                    <div id="res-cal-days-grid" class="grid grid-cols-7 gap-2">
                        <!-- Renderizado vía Javascript -->
                    </div>
                </div>

                <!-- Panel Derecho: Detalle de Operación Diaria -->
                <div class="lg:col-span-1 p-6 rounded-3xl border border-slate-900 bg-slate-950/40 shadow-xl flex flex-col gap-4">
                    <h2 class="text-xs font-black uppercase tracking-widest text-slate-200 border-b border-slate-900 pb-3">
                        Detalle del Día
                    </h2>
                    
                    <!-- Contenedor del listado de operación diaria -->
                    <div id="res-cal-day-details" class="flex flex-col gap-3 max-h-[350px] overflow-y-auto pr-1">
                        <!-- Empty State inicial -->
                        <div class="text-center py-10">
                            <span class="text-3xl block mb-3">📅</span>
                            <p class="text-xs font-semibold text-slate-300">Selecciona una fecha</p>
                            <p class="text-[10px] text-slate-500 mt-1 max-w-[200px] mx-auto">Haz clic sobre cualquier día con reservas para consultar los clientes y tours programados.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- LISTADO DE RESERVAS -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <div class="lg:col-span-3">
                    <div class="p-6 rounded-3xl border border-slate-900 bg-slate-950/40 shadow-xl overflow-hidden">
                        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-slate-900 pb-3 mb-5">
                            <h2 class="text-xs font-black uppercase tracking-widest text-slate-200">
                                {{ Auth::user()->isAdmin() ? __('globalBookingsList') : __('vendorBookingsList') }}
                            </h2>
                            @if(Auth::user()->isAdmin())
                                <button onclick="clearReservasFilters()" class="text-[10px] font-bold uppercase tracking-widest text-cyan-400 hover:text-cyan-300 transition-colors cursor-pointer">
                                    Limpiar Filtros
                                </button>
                            @endif
                        </div>

                        @if(Auth::user()->isAdmin())
                            @if($reservas->isEmpty())
                                <p class="text-xs text-slate-400 py-6 text-center">{{ __('noBookings') }}</p>
                            @else
                                <div class="overflow-x-auto">
                                    <div id="no-reservas-alert" class="hidden text-xs text-slate-400 py-6 text-center">
                                        No se encontraron reservas que coincidan con los filtros aplicados.
                                    </div>
                                    <table class="w-full text-left text-xs border-collapse">
                                        <thead>
                                            <tr class="border-b border-slate-900 text-slate-400 font-bold uppercase tracking-wider">
                                                <th class="py-3 px-2">Ticket</th>
                                                <th class="py-3 px-2">Cliente / Tours</th>
                                                <th class="py-3 px-2">Compra / Operador</th>
                                                <th class="py-3 px-2 text-right">Venta</th>
                                                <th class="py-3 px-2 text-right">Comisión</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-900/60">
                                            @foreach($reservas as $res)
                                                @php
                                                    $provIds = $res->detalles->map(function($d) { return $d->tour->proveedor_id ?? ''; })->filter()->unique()->implode(',');
                                                    $tourIds = $res->detalles->pluck('tour_id')->filter()->unique()->implode(',');
                                                    $fechaCompra = $res->fecha_reserva->format('Y-m-d');
                                                    $toursNombres = $res->detalles->map(function($d) { return $d->tour->nombre ?? ''; })->implode(', ');
                                                    $proveedoresNombres = $res->detalles->map(function($d) { return $d->tour->proveedor->nombre_empresa ?? ''; })->filter()->unique()->implode(', ');
                                                @endphp
                                                <tr class="reserva-row hover:bg-slate-900/40 transition-colors text-slate-300"
                                                    data-proveedores="{{ $provIds }}"
                                                    data-tours="{{ $tourIds }}"
                                                    data-fecha-compra="{{ $fechaCompra }}"
                                                    data-cliente="{{ strtolower($res->nombre_cliente) }}"
                                                    data-ticket="{{ strtolower($res->ticket_codigo) }}"
                                                    data-venta="{{ $res->precio_total_usd }}"
                                                    data-comision="{{ $res->comision_total_usd }}">
                                                    <td class="py-3.5 px-2 font-black text-cyan-400 font-mono">{{ $res->ticket_codigo }}</td>
                                                    <td class="py-3.5 px-2 max-w-[200px]">
                                                        <span class="font-bold text-white block">{{ $res->nombre_cliente }}</span>
                                                        <span class="text-[10px] text-slate-500 block truncate mt-0.5" title="{{ $toursNombres }}">{{ $toursNombres }}</span>
                                                    </td>
                                                    <td class="py-3.5 px-2 max-w-[200px]">
                                                        <span class="block text-slate-300">{{ $res->fecha_reserva->format('Y-m-d H:i') }}</span>
                                                        <span class="text-[10px] text-slate-500 block truncate mt-0.5" title="{{ $proveedoresNombres }}">{{ $proveedoresNombres }}</span>
                                                    </td>
                                                    <td class="py-3.5 px-2 text-right font-bold text-white">${{ number_format($res->precio_total_usd) }}</td>
                                                    <td class="py-3.5 px-2 text-right text-rose-400 font-bold">${{ number_format($res->comision_total_usd, 2) }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                        @else
                            @if(empty($reservaDetalles) || $reservaDetalles->isEmpty())
                                <p class="text-xs text-slate-400 py-6 text-center">{{ __('noBookings') }}</p>
                            @else
                                <div class="overflow-x-auto">
                                    <table class="w-full text-left text-xs border-collapse">
                                        <thead>
                                            <tr class="border-b border-slate-900 text-slate-400 font-bold uppercase tracking-wider">
                                                <th class="py-3 px-2">Ticket</th>
                                                <th class="py-3 px-2">Tour</th>
                                                <th class="py-3 px-2">Fecha Act.</th>
                                                <th class="py-3 px-2 text-center">Pax</th>
                                                <th class="py-3 px-2 text-right">Neto</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-900/60">
                                            @foreach($reservaDetalles as $det)
                                                <tr class="hover:bg-slate-900/40 transition-colors text-slate-300">
                                                    <td class="py-3.5 px-2 font-black text-cyan-400">{{ $det->reserva->ticket_codigo }}</td>
                                                    <td class="py-3.5 px-2 truncate max-w-[150px]" title="{{ $det->tour->nombre }}">{{ $det->tour->nombre }}</td>
                                                    <td class="py-3.5 px-2 text-[10px]">{{ $det->fecha_seleccionada->format('Y-m-d') }}</td>
                                                    <td class="py-3.5 px-2 text-center font-bold text-white">{{ $det->cantidad_personas }}</td>
                                                    <td class="py-3.5 px-2 text-right text-emerald-400">${{ number_format(($det->precio_unitario_usd * $det->cantidad_personas) - $det->comision_usd, 2) }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                        @endif
                    </div>
                </div>
            </div>
            
            @if(Auth::user()->isProveedor())
                <!-- SECCIÓN DE CALENDARIO DEL PROVEEDOR (Estilo Airbnb Premium) -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mt-10">
                    <!-- Calendario de Disponibilidad -->
                    <div class="lg:col-span-2 p-6 rounded-3xl border border-slate-900 bg-slate-950/80 backdrop-blur-md shadow-2xl">
                        <h2 class="text-xs font-black uppercase tracking-widest text-cyan-400 border-b border-slate-900 pb-3 mb-4">
                            Calendario de Disponibilidad (Modo Anfitrión)
                        </h2>

                        <div class="flex flex-col gap-4">
                            <div class="flex flex-col gap-1.5">
                                <label class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Seleccionar Tour</label>
                                <select class="calendar-tour-select w-full h-10 rounded-lg border border-slate-800 bg-slate-900 px-3 text-xs text-white focus:border-cyan-500 cursor-pointer">
                                    <option value="">-- Elige un Tour --</option>
                                    @foreach($tours as $t)
                                        <option value="{{ $t->id }}">{{ $t->nombre }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Contenedor del Calendario -->
                            <div class="calendar-widget-container hidden mt-4">
                                <div class="flex justify-between items-center mb-4">
                                    <button type="button" class="prev-month-btn px-2.5 py-1.5 rounded-lg bg-slate-900 border border-slate-800 hover:bg-slate-800 text-slate-400 hover:text-white transition-colors cursor-pointer text-[10px] font-bold">
                                        &larr; Ant.
                                    </button>
                                    <h3 class="current-month-label text-xs font-black uppercase tracking-widest text-white"></h3>
                                    <button type="button" class="next-month-btn px-2.5 py-1.5 rounded-lg bg-slate-900 border border-slate-800 hover:bg-slate-800 text-slate-400 hover:text-white transition-colors cursor-pointer text-[10px] font-bold">
                                        Sig. &rarr;
                                    </button>
                                </div>
                                
                                <!-- Días de la semana -->
                                <div class="grid grid-cols-7 gap-1 text-center text-[9px] font-bold text-slate-500 uppercase tracking-wider mb-2">
                                    <div>Lun</div>
                                    <div>Mar</div>
                                    <div>Mié</div>
                                    <div>Jue</div>
                                    <div>Vie</div>
                                    <div>Sáb</div>
                                    <div>Dom</div>
                                </div>
                                
                                <!-- Grid del mes -->
                                <div class="calendar-days-grid grid grid-cols-7 gap-1.5">
                                    <!-- Se llena dinámicamente -->
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tarjeta de Instrucciones del Calendario -->
                    <div class="lg:col-span-1 p-6 rounded-3xl border border-slate-900 bg-slate-950/40 shadow-xl flex flex-col gap-4">
                        <h2 class="text-xs font-black uppercase tracking-widest text-slate-200 border-b border-slate-900 pb-3">
                            Instrucciones de Uso
                        </h2>
                        <div class="flex flex-col gap-3 text-xs text-slate-400 leading-relaxed">
                            <p>
                                <strong class="text-cyan-400">1. Selecciona un tour:</strong> Elige uno de tus tours en el menú desplegable para cargar su calendario de disponibilidad.
                            </p>
                            <p>
                                <strong class="text-cyan-400">2. Estado de los días:</strong>
                            </p>
                            <div class="flex items-center gap-2 pl-2">
                                <span class="w-3.5 h-3.5 rounded bg-emerald-500/10 border border-emerald-500/30 block"></span>
                                <span>Habilitado (tiene salidas programadas)</span>
                            </div>
                            <div class="flex items-center gap-2 pl-2">
                                <span class="w-3.5 h-3.5 rounded bg-slate-900/60 border border-slate-800/40 block"></span>
                                <span>Deshabilitado (sin salidas programadas)</span>
                            </div>
                            <p>
                                <strong class="text-cyan-400">3. Configurar un día:</strong> Haz clic sobre cualquier día del calendario (que no esté en el pasado) para abrir el modal de configuración de disponibilidad.
                            </p>
                            <p>
                                <strong class="text-cyan-400">4. Múltiples salidas:</strong> En el modal podrás habilitar/deshabilitar el día, añadir múltiples horarios y definir el cupo para cada salida de manera atómica.
                            </p>
                        </div>
                    </div>


            @endif

        </div>

        @if(Auth::user()->isAdmin())
            <!-- PESTAÑA: PROVEEDORES (Solo Administrador) -->
            <div id="tab-content-proveedores" class="tab-pane hidden animate-fade-in">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    
                    <!-- Formulario de creación de Proveedor -->
                    <div class="lg:col-span-1 p-6 rounded-3xl border border-slate-900 bg-slate-950/80 backdrop-blur-md shadow-2xl">
                        <h2 class="text-xs font-black uppercase tracking-widest text-cyan-400 border-b border-slate-900 pb-3 mb-4">
                            Registrar Nuevo Proveedor
                        </h2>

                        <form action="{{ route('dashboard.proveedor') }}" method="POST" class="flex flex-col gap-4">
                            @csrf
                            
                            <div class="flex flex-col gap-1.5">
                                <label class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Nombre de la Empresa</label>
                                <input type="text" name="nombre_empresa" required placeholder="Ej. Amigo Tours SA" class="w-full h-9 rounded-lg border border-slate-800 bg-slate-900 px-3 text-xs text-white placeholder-slate-600 focus:border-cyan-500 focus:ring-0 focus:outline-none">
                            </div>

                            <div class="flex flex-col gap-1.5">
                                <label class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Descripción / Especialidad</label>
                                <textarea name="descripcion" required placeholder="Describe las operaciones..." rows="3" class="w-full rounded-lg border border-slate-800 bg-slate-900 p-3 text-xs text-white placeholder-slate-600 focus:border-cyan-500 focus:ring-0 focus:outline-none"></textarea>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div class="flex flex-col gap-1.5">
                                    <label class="text-[10px] font-bold uppercase tracking-wider text-slate-400">RFC (Opcional)</label>
                                    <input type="text" name="rfc" placeholder="RFC123456" class="w-full h-9 rounded-lg border border-slate-800 bg-slate-900 px-3 text-xs text-white placeholder-slate-600 focus:border-cyan-500">
                                </div>
                                <div class="flex flex-col gap-1.5">
                                    <label class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Comisión %</label>
                                    <input type="number" name="comision_porcentaje" required value="15" min="0" max="100" class="w-full h-9 rounded-lg border border-slate-800 bg-slate-900 px-3 text-xs text-white focus:border-cyan-500">
                                </div>
                            </div>

                            <div class="flex flex-col gap-1.5">
                                <label class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Correo Electrónico de Contacto</label>
                                <input type="email" name="correo" required placeholder="proveedor@correo.com" class="w-full h-9 rounded-lg border border-slate-800 bg-slate-900 px-3 text-xs text-white placeholder-slate-600 focus:border-cyan-500">
                            </div>

                            <div class="flex flex-col gap-1.5">
                                <label class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Nombre del Representante</label>
                                <input type="text" name="representante_nombre" required placeholder="Carlos Gómez" class="w-full h-9 rounded-lg border border-slate-800 bg-slate-900 px-3 text-xs text-white placeholder-slate-600 focus:border-cyan-500">
                            </div>

                            <div class="flex flex-col gap-1.5">
                                <label class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Teléfono del Representante</label>
                                <input type="text" name="representante_telefono" required placeholder="+52 999 123 4567" class="w-full h-9 rounded-lg border border-slate-800 bg-slate-900 px-3 text-xs text-white placeholder-slate-600 focus:border-cyan-500 focus:ring-0 focus:outline-none">
                            </div>

                            <div class="flex flex-col gap-1.5">
                                <label class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Contraseña de Acceso (Usuario)</label>
                                <input type="password" name="password" required placeholder="Mínimo 6 caracteres" minlength="6" class="w-full h-9 rounded-lg border border-slate-800 bg-slate-900 px-3 text-xs text-white placeholder-slate-600 focus:border-cyan-500">
                            </div>

                            <div class="flex flex-col gap-1.5">
                                <label class="text-[10px] font-bold uppercase tracking-wider text-slate-400">URL Foto / Logo del Proveedor (Opcional)</label>
                                <input type="url" name="foto_url" placeholder="https://..." class="w-full h-9 rounded-lg border border-slate-800 bg-slate-900 px-3 text-xs text-white placeholder-slate-600 focus:border-cyan-500">
                            </div>

                            <button type="submit" class="w-full h-10 inline-flex items-center justify-center rounded-lg bg-gradient-to-r from-cyan-500 to-indigo-600 text-xs font-bold uppercase text-white shadow-lg cursor-pointer">
                                Guardar Proveedor
                            </button>
                        </form>
                    </div>

                    <!-- Lista de Proveedores Registrados -->
                    <div class="lg:col-span-2 p-6 rounded-3xl border border-slate-900 bg-slate-950/40 shadow-xl overflow-hidden">
                        <h2 class="text-xs font-black uppercase tracking-widest text-slate-200 border-b border-slate-900 pb-3 mb-5">
                            Operadores Registrados
                        </h2>

                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-xs border-collapse">
                                <thead>
                                    <tr class="border-b border-slate-900 text-slate-400 font-bold uppercase tracking-wider">
                                        <th class="py-3 px-2 w-10"></th>
                                        <th class="py-3 px-2">Empresa</th>
                                        <th class="py-3 px-2">Representante</th>
                                        <th class="py-3 px-2">Contacto</th>
                                        <th class="py-3 px-2 text-center">Tours</th>
                                        <th class="py-3 px-2 text-center">Comisión</th>
                                        <th class="py-3 px-2 text-center">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-900/60">
                                    @foreach($proveedores as $prov)
                                        <tr class="hover:bg-slate-900/40 transition-colors text-slate-300">
                                            <td class="py-3.5 px-2">
                                                @if($prov->foto_url)
                                                    <img src="{{ $prov->foto_url }}" alt="{{ $prov->nombre_empresa }}" class="h-8 w-8 rounded-full object-cover border border-slate-800">
                                                @else
                                                    <div class="h-8 w-8 rounded-full bg-slate-900 border border-slate-800 flex items-center justify-center text-[9px] font-black text-cyan-400">
                                                        {{ substr($prov->nombre_empresa, 0, 2) }}
                                                    </div>
                                                @endif
                                            </td>
                                            <td class="py-3.5 px-2 font-bold text-white">{{ $prov->nombre_empresa }}</td>
                                            <td class="py-3.5 px-2">{{ $prov->representante_nombre }}</td>
                                            <td class="py-3.5 px-2 font-mono text-[10px] text-slate-400">
                                                {{ $prov->correo }}<br>{{ $prov->representante_telefono }}
                                            </td>
                                            <td class="py-3.5 px-2 text-center font-bold text-cyan-400">{{ $prov->tours_count }}</td>
                                            <td class="py-3.5 px-2 text-center text-rose-400">{{ $prov->comision_percentage ?? $prov->comision_porcentaje }}%</td>
                                            <td class="py-3.5 px-2 text-center">
                                                <button onclick="openEditProveedorModal({{ $prov->id }}, '{{ addslashes($prov->nombre_empresa) }}', '{{ addslashes($prov->descripcion) }}', '{{ $prov->rfc }}', '{{ $prov->correo }}', '{{ addslashes($prov->representante_nombre) }}', '{{ $prov->representante_telefono }}', {{ $prov->comision_percentage ?? $prov->comision_porcentaje }}, '{{ $prov->foto_url }}')" 
                                                        class="px-2.5 py-1 rounded-md bg-slate-900 hover:bg-slate-800 border border-slate-800 text-[10px] font-bold uppercase text-slate-300 hover:text-cyan-400 transition-colors cursor-pointer">
                                                    Editar
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- PESTAÑA: TOURS (Solo Administrador) -->
            <div id="tab-content-tours" class="tab-pane hidden animate-fade-in">
                <!-- LAYOUT DUAL PRINCIPAL (Master-Detail) -->
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
                    
                    <!-- COLUMNA IZQUIERDA (Catálogo & Selector de Tours - lg:col-span-5) -->
                    <div class="lg:col-span-5 flex flex-col gap-6">
                        <!-- Tarjeta Contenedora del Catálogo -->
                        <div class="p-6 rounded-3xl border border-slate-900 bg-slate-950/40 shadow-xl flex flex-col gap-5">
                            <div class="flex items-center justify-between gap-4 border-b border-slate-900 pb-4">
                                <div>
                                    <h2 class="text-xs font-black uppercase tracking-widest text-cyan-400">
                                        Catálogo de Tours
                                    </h2>
                                    <p class="text-[10px] text-slate-500 mt-0.5">Selecciona un tour para ver su disponibilidad</p>
                                </div>
                                
                                <!-- Botón Crear Nuevo Tour -->
                                <button type="button" onclick="openCreateTourModal()" class="h-8 px-3 inline-flex items-center justify-center rounded-lg bg-gradient-to-r from-cyan-500 to-indigo-600 text-[10px] font-black uppercase text-white shadow-lg cursor-pointer transition-transform hover:scale-[1.02]">
                                    + Nuevo
                                </button>
                            </div>

                            <!-- Buscador de Tours en la lista -->
                            <div class="relative">
                                <input type="text" id="tour-list-search" oninput="filterTourList()" placeholder="Buscar tour por nombre..." class="w-full h-9 rounded-lg border border-slate-800 bg-slate-900/60 px-3 text-xs text-white placeholder-slate-600 focus:border-cyan-500 focus:outline-none">
                                <div class="absolute right-3 top-2.5 text-slate-600">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                    </svg>
                                </div>
                            </div>

                            <!-- Lista de Tours Vertical Interactiva -->
                            <div class="flex flex-col gap-3 max-h-[600px] overflow-y-auto pr-1" id="admin-tours-list">
                                @foreach($tours as $t)
                                    <div id="tour-card-{{ $t->id }}" 
                                         onclick="selectTourForCalendar('{{ $t->id }}')" 
                                         class="tour-list-card p-3 rounded-2xl border border-slate-900 bg-slate-950/60 hover:bg-slate-900/40 hover:border-slate-800 transition-all duration-200 cursor-pointer flex gap-3 group relative animate-fade-in"
                                         data-titulo="{{ $t->nombre }}"
                                         data-resumen="{{ $t->resumen }}"
                                         data-detalle="{{ $t->detalle }}"
                                         data-precio="{{ $t->precio_base_usd }}"
                                         data-duracion="{{ $t->duracion }}"
                                         data-ubicacion="{{ $t->ubicacion }}"
                                         data-pais="{{ $t->pais }}"
                                         data-proveedor-id="{{ $t->proveedor_id }}"
                                         data-tags="{{ implode(', ', $t->tags ?: []) }}"
                                         data-imagen="{{ $t->imagen_destacada }}"
                                         data-galeria="{{ implode(', ', array_slice($t->galeria ?: [], 1)) }}"
                                         data-punto-encuentro="{{ $t->punto_encuentro }}"
                                         data-itinerario="{{ json_encode($t->itinerario ?: []) }}"
                                         data-incluye="{{ implode(', ', $t->incluye ?: []) }}"
                                         data-no-incluye="{{ implode(', ', $t->no_incluye ?: []) }}">
                                        
                                        <!-- Foto miniatura -->
                                        <div class="h-16 w-16 rounded-xl bg-slate-900 overflow-hidden flex-shrink-0">
                                            <img src="{{ $t->imagen_destacada }}" alt="{{ $t->nombre }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                                        </div>

                                        <!-- Información central -->
                                        <div class="flex-grow min-w-0 flex flex-col justify-between py-0.5">
                                            <div>
                                                <h4 class="font-bold text-white text-xs truncate tour-card-name" title="{{ $t->nombre }}">
                                                    {{ $t->nombre }}
                                                </h4>
                                                <span class="text-[9px] text-slate-500 block truncate mt-0.5">Operador: {{ $t->proveedor->nombre_empresa }}</span>
                                            </div>
                                            <div class="flex items-center justify-between mt-1">
                                                <span class="text-[10px] font-black text-cyan-400">${{ number_format($t->precio_base_usd) }} MXN</span>
                                                <span class="text-[9px] text-slate-400 font-semibold uppercase tracking-wider">{{ $t->ubicacion }}</span>
                                            </div>
                                        </div>

                                        <!-- Acciones rápidas (Modificadores) -->
                                        <div class="flex flex-col gap-1.5 justify-center pl-2 border-l border-slate-900">
                                            <button type="button" 
                                                    onclick="event.stopPropagation(); openEditTourModal('{{ $t->id }}')"
                                                    class="h-6 w-6 inline-flex items-center justify-center rounded bg-slate-900 hover:bg-slate-800 border border-slate-800 text-[10px] text-indigo-400 hover:text-indigo-300 transition-colors cursor-pointer" 
                                                    title="Editar Ficha">
                                                ✏️
                                            </button>
                                            <a href="{{ route('tours.show', $t->id) }}" 
                                               target="_blank" 
                                               onclick="event.stopPropagation();" 
                                               class="h-6 w-6 inline-flex items-center justify-center rounded bg-slate-900 hover:bg-slate-800 border border-slate-800 text-[10px] text-cyan-400 hover:text-cyan-300 transition-colors" 
                                               title="Ver Ficha">
                                                🔗
                                            </a>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <!-- COLUMNA DERECHA (Calendario y Control de Salidas - lg:col-span-7) -->
                    <div class="lg:col-span-7 flex flex-col gap-6">
                        
                        <!-- Selector oculto o sincronizado para mantener compatibilidad con el JS del calendario -->
                        <select class="calendar-tour-select hidden">
                            <option value="">-- Elige un Tour --</option>
                            @foreach($tours as $t)
                                <option value="{{ $t->id }}">{{ $t->nombre }}</option>
                            @endforeach
                        </select>

                        <!-- CONTENEDOR PRINCIPAL CALENDARIO -->
                        <div class="p-6 rounded-3xl border border-slate-900 bg-slate-950/80 backdrop-blur-md shadow-2xl min-h-[400px] flex flex-col justify-between" id="calendar-main-panel">
                            
                            <!-- Estado Vacío (Empty State) cuando no hay tour seleccionado -->
                            <div id="calendar-empty-state" class="flex flex-col items-center justify-center text-center py-20 flex-grow animate-fade-in">
                                <div class="h-16 w-16 rounded-full bg-slate-900/60 border border-slate-800/80 flex items-center justify-center text-slate-500 mb-4 animate-pulse">
                                    <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                </div>
                                <h3 class="text-sm font-black text-slate-300 uppercase tracking-widest">Gestión de Disponibilidad</h3>
                                <p class="text-xs text-slate-500 max-w-xs mt-2 leading-relaxed">
                                    Selecciona uno de los tours de la columna izquierda para cargar y gestionar sus salidas en el calendario.
                                </p>
                            </div>

                            <!-- Contenedor del Calendario (Se muestra al seleccionar un tour) -->
                            <div class="calendar-widget-container hidden flex-grow flex flex-col gap-4 animate-fade-in">
                                <div class="flex items-center justify-between border-b border-slate-900 pb-4 mb-2">
                                    <div>
                                        <span class="text-[9px] font-black uppercase tracking-widest text-cyan-400 block">Calendario Activo</span>
                                        <h3 class="text-xs font-black text-white mt-0.5 truncate max-w-[280px]" id="calendar-active-tour-title">Nombre del Tour</h3>
                                    </div>

                                    <!-- Botones de navegación de mes -->
                                    <div class="flex items-center gap-2">
                                        <button type="button" class="prev-month-btn h-8 w-8 rounded-lg bg-slate-900 border border-slate-800 hover:bg-slate-800 text-slate-400 hover:text-white flex items-center justify-center transition-colors cursor-pointer text-xs">
                                            &larr;
                                        </button>
                                        <span class="current-month-label text-[10px] font-black uppercase tracking-wider text-slate-300 min-w-[100px] text-center"></span>
                                        <button type="button" class="next-month-btn h-8 w-8 rounded-lg bg-slate-900 border border-slate-800 hover:bg-slate-800 text-slate-400 hover:text-white flex items-center justify-center transition-colors cursor-pointer text-xs">
                                            &rarr;
                                        </button>
                                    </div>
                                </div>

                                <!-- Días de la semana -->
                                <div class="grid grid-cols-7 gap-1.5 text-center text-[9px] font-bold text-slate-500 uppercase tracking-wider mb-1">
                                    <div>Lun</div>
                                    <div>Mar</div>
                                    <div>Mié</div>
                                    <div>Jue</div>
                                    <div>Vie</div>
                                    <div>Sáb</div>
                                    <div>Dom</div>
                                </div>
                                
                                <!-- Grid del mes -->
                                <div class="calendar-days-grid grid grid-cols-7 gap-1.5">
                                    <!-- Se llena dinámicamente con JS -->
                                </div>

                                <!-- Leyenda/Guía Rápida Compacta -->
                                <div class="border-t border-slate-900 pt-4 mt-4 flex flex-wrap items-center justify-between gap-4 text-[10px] text-slate-500">
                                    <div class="flex items-center gap-4">
                                        <div class="flex items-center gap-1.5">
                                            <span class="w-2.5 h-2.5 rounded bg-emerald-500/10 border border-emerald-500/30 block"></span>
                                            <span>Salidas programadas</span>
                                        </div>
                                        <div class="flex items-center gap-1.5">
                                            <span class="w-2.5 h-2.5 rounded bg-slate-900/60 border border-slate-800/40 block"></span>
                                            <span>Día Cerrado</span>
                                        </div>
                                    </div>
                                    <span class="text-slate-400">💡 Haz clic sobre un día futuro para abrir el editor de salidas.</span>
                                </div>

                            </div>

                        </div>

                    </div>

                </div>
            </div>

            <!-- PESTAÑA: USUARIOS Y ROLES (Solo Administrador) -->
            <div id="tab-content-usuarios" class="tab-pane hidden animate-fade-in">
                <div class="p-6 rounded-3xl border border-slate-900 bg-slate-950/40 shadow-xl overflow-hidden">
                    <h2 class="text-xs font-black uppercase tracking-widest text-slate-200 border-b border-slate-900 pb-3 mb-5">
                        Usuarios Registrados & Permisos de Acceso
                    </h2>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs border-collapse">
                            <thead>
                                <tr class="border-b border-slate-900 text-slate-400 font-bold uppercase tracking-wider">
                                    <th class="py-3 px-2">Nombre</th>
                                    <th class="py-3 px-2">Email</th>
                                    <th class="py-3 px-2 text-center">Rol</th>
                                    <th class="py-3 px-2">Operador Asociado</th>
                                    <th class="py-3 px-2 text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-900/60">
                                @foreach($usuarios as $usr)
                                    <tr class="hover:bg-slate-900/40 transition-colors text-slate-300">
                                        <td class="py-3.5 px-2 font-bold text-white">{{ $usr->name }}</td>
                                        <td class="py-3.5 px-2 font-mono text-[11px]">{{ $usr->email }}</td>
                                        <td class="py-3.5 px-2 text-center">
                                            <span class="px-2 py-0.5 text-[9px] font-black uppercase tracking-wider rounded
                                                @if($usr->isAdmin()) bg-red-900/30 text-red-400 border border-red-500/20
                                                @elseif($usr->isProveedor()) bg-cyan-900/30 text-cyan-400 border border-cyan-500/20
                                                @else bg-slate-900 text-slate-400 border border-slate-800/60
                                                @endif">
                                                {{ $usr->tipo }}
                                            </span>
                                        </td>
                                        <td class="py-3.5 px-2 text-xs text-slate-400">
                                            {{ $usr->proveedor ? $usr->proveedor->nombre_empresa : 'Ninguno' }}
                                        </td>
                                        <td class="py-3.5 px-2 text-center flex items-center justify-center gap-2">
                                            
                                            <!-- Botón Rol Modal -->
                                            <button onclick="openRoleModal('{{ $usr->id }}', '{{ $usr->name }}', '{{ $usr->tipo }}', '{{ $usr->proveedor_id }}')" class="px-2.5 py-1 rounded bg-slate-900 hover:bg-slate-800 border border-slate-800 text-[9px] font-bold text-slate-200 transition-colors cursor-pointer" title="Cambiar Rol">
                                                🔑 Rol
                                            </button>

                                            <!-- Botón Clave Modal -->
                                            <button onclick="openPasswordModal('{{ $usr->id }}', '{{ $usr->name }}')" class="px-2.5 py-1 rounded bg-slate-900 hover:bg-slate-800 border border-slate-800 text-[9px] font-bold text-rose-400 hover:text-rose-300 transition-colors cursor-pointer" title="Reset Clave">
                                                🔄 Clave
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- MODAL: CAMBIAR ROL DE USUARIO -->
            <div id="role-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-slate-950/80 backdrop-blur-sm transition-all duration-200">
                <div class="w-full max-w-sm p-6 rounded-3xl border border-slate-900 bg-slate-950 shadow-2xl relative max-h-[90vh] overflow-y-auto">
                    <button onclick="closeRoleModal()" class="absolute top-4 right-4 text-slate-500 hover:text-white transition-colors cursor-pointer">✕</button>
                    
                    <h3 class="text-xs font-black uppercase tracking-widest text-cyan-400 mb-2">Cambiar Rol & Sucursal</h3>
                    <p class="text-[11px] text-slate-400 mb-5">Usuario: <span id="role-modal-username" class="text-white font-bold"></span></p>

                    <form id="role-modal-form" method="POST" class="flex flex-col gap-4">
                        @csrf
                        
                        <div class="flex flex-col gap-1.5">
                            <label class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Nuevo Rol</label>
                            <select name="tipo" id="role-modal-tipo" onchange="toggleProveedorDropdown(this.value)" required class="w-full h-9 rounded-lg border border-slate-800 bg-slate-900 px-3 text-xs text-white focus:border-cyan-500 cursor-pointer">
                                <option value="Cliente">Cliente (Viajero)</option>
                                <option value="PT">PT (Proveedor de Tours)</option>
                                <option value="Admin">Administrador</option>
                            </select>
                        </div>

                        <!-- Dropdown de Proveedores (Sólo visible para PT) -->
                        <div id="role-modal-prov-group" class="hidden flex flex-col gap-1.5">
                            <label class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Asociar Operadora (Proveedor)</label>
                            <select name="proveedor_id" id="role-modal-prov-id" class="w-full h-9 rounded-lg border border-slate-800 bg-slate-900 px-3 text-xs text-white focus:border-cyan-500 cursor-pointer">
                                <option value="">Selecciona Operadora...</option>
                                @foreach($proveedores as $prov)
                                    <option value="{{ $prov->id }}">{{ $prov->nombre_empresa }}</option>
                                @endforeach
                            </select>
                        </div>

                        <button type="submit" class="w-full h-10 inline-flex items-center justify-center rounded-lg bg-gradient-to-r from-cyan-500 to-indigo-600 text-xs font-bold uppercase text-white shadow-lg cursor-pointer transition-all mt-2">
                            Actualizar Permisos
                        </button>
                    </form>
                </div>
            </div>

            <!-- MODAL: RESTABLECER CONTRASEÑA -->
            <div id="password-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-slate-950/80 backdrop-blur-sm transition-all duration-200">
                <div class="w-full max-w-sm p-6 rounded-3xl border border-slate-900 bg-slate-950 shadow-2xl relative max-h-[90vh] overflow-y-auto">
                    <button onclick="closePasswordModal()" class="absolute top-4 right-4 text-slate-500 hover:text-white transition-colors cursor-pointer">✕</button>
                    
                    <h3 class="text-xs font-black uppercase tracking-widest text-rose-400 mb-2">Restablecer Contraseña</h3>
                    <p class="text-[11px] text-slate-400 mb-5">Usuario: <span id="pass-modal-username" class="text-white font-bold"></span></p>

                    <form id="pass-modal-form" method="POST" class="flex flex-col gap-4">
                        @csrf
                        
                        <div class="flex flex-col gap-1.5">
                            <label class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Nueva Contraseña</label>
                            <input type="password" name="new_password" required placeholder="Ingresa mínimo 6 caracteres" minlength="6" class="w-full h-10 rounded-lg border border-slate-800 bg-slate-900 px-3 text-xs text-white placeholder-slate-700 focus:border-cyan-500">
                        </div>

                        <button type="submit" class="w-full h-10 inline-flex items-center justify-center rounded-lg bg-gradient-to-r from-rose-500 to-pink-600 text-xs font-bold uppercase text-white shadow-lg cursor-pointer transition-all mt-2">
                            Confirmar Nueva Contraseña
                        </button>
                    </form>
                </div>
            </div>

            <!-- MODAL: EDITAR PROVEEDOR -->
            <div id="edit-proveedor-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-slate-950/80 backdrop-blur-sm transition-all duration-200">
                <div class="w-full max-w-lg p-6 rounded-3xl border border-slate-900 bg-slate-950 shadow-2xl relative max-h-[90vh] overflow-y-auto">
                    <button onclick="closeEditProveedorModal()" class="absolute top-4 right-4 text-slate-500 hover:text-white transition-colors cursor-pointer">✕</button>
                    
                    <h3 class="text-xs font-black uppercase tracking-widest text-cyan-400 mb-4 border-b border-slate-900 pb-2">Editar Proveedor</h3>

                    <form id="edit-prov-form" method="POST" class="flex flex-col gap-4">
                        @csrf
                        
                        <div class="flex flex-col gap-1.5">
                            <label class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Nombre de la Empresa</label>
                            <input type="text" name="nombre_empresa" id="edit-prov-name" required class="w-full h-9 rounded-lg border border-slate-800 bg-slate-900 px-3 text-xs text-white focus:border-cyan-500">
                        </div>

                        <div class="flex flex-col gap-1.5">
                            <label class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Descripción / Especialidad</label>
                            <textarea name="descripcion" id="edit-prov-desc" required rows="3" class="w-full rounded-lg border border-slate-800 bg-slate-900 p-3 text-xs text-white focus:border-cyan-500"></textarea>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div class="flex flex-col gap-1.5">
                                <label class="text-[10px] font-bold uppercase tracking-wider text-slate-400">RFC (Opcional)</label>
                                <input type="text" name="rfc" id="edit-prov-rfc" class="w-full h-9 rounded-lg border border-slate-800 bg-slate-900 px-3 text-xs text-white focus:border-cyan-500">
                            </div>
                            <div class="flex flex-col gap-1.5">
                                <label class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Comisión %</label>
                                <input type="number" name="comision_porcentaje" id="edit-prov-commission" required min="0" max="100" class="w-full h-9 rounded-lg border border-slate-800 bg-slate-900 px-3 text-xs text-white focus:border-cyan-500">
                            </div>
                        </div>

                        <div class="flex flex-col gap-1.5">
                            <label class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Correo Electrónico de Contacto</label>
                            <input type="email" name="correo" id="edit-prov-email" required class="w-full h-9 rounded-lg border border-slate-800 bg-slate-900 px-3 text-xs text-white focus:border-cyan-500">
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div class="flex flex-col gap-1.5">
                                <label class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Nombre del Representante</label>
                                <input type="text" name="representante_nombre" id="edit-prov-rep-name" required class="w-full h-9 rounded-lg border border-slate-800 bg-slate-900 px-3 text-xs text-white focus:border-cyan-500">
                            </div>
                            <div class="flex flex-col gap-1.5">
                                <label class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Teléfono del Representante</label>
                                <input type="text" name="representante_telefono" id="edit-prov-rep-tel" required class="w-full h-9 rounded-lg border border-slate-800 bg-slate-900 px-3 text-xs text-white focus:border-cyan-500">
                            </div>
                        </div>

                        <div class="flex flex-col gap-1.5">
                            <label class="text-[10px] font-bold uppercase tracking-wider text-slate-400">URL Foto / Logo del Proveedor (Opcional)</label>
                            <input type="url" name="foto_url" id="edit-prov-foto" placeholder="https://..." class="w-full h-9 rounded-lg border border-slate-800 bg-slate-900 px-3 text-xs text-white placeholder-slate-700 focus:border-cyan-500">
                        </div>

                        <button type="submit" class="w-full h-10 inline-flex items-center justify-center rounded-lg bg-gradient-to-r from-cyan-500 to-indigo-600 text-xs font-bold uppercase text-white shadow-lg cursor-pointer transition-all mt-2">
                            Guardar Cambios
                        </button>
                    </form>
                </div>
            </div>

            <!-- Modal de Creación de Tour (Solo Administrador) -->
            <div id="create-tour-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm transition-all duration-200">
                <div class="relative w-full max-w-2xl rounded-3xl border border-slate-900 bg-slate-950 p-6 shadow-2xl overflow-y-auto max-h-[90vh]">
                    <!-- Botón Cerrar -->
                    <button type="button" onclick="closeCreateTourModal()" class="absolute top-4 right-4 text-slate-400 hover:text-white text-lg cursor-pointer">
                        &times;
                    </button>
                    
                    <h3 class="text-xs font-black uppercase tracking-widest text-cyan-400 border-b border-slate-900 pb-3 mb-5">
                        Crear Nuevo Tour
                    </h3>

                    <form action="{{ route('dashboard.tour') }}" method="POST" enctype="multipart/form-data" class="flex flex-col gap-5">
                        @csrf
                        
                        <!-- Proveedor Asignado -->
                        <div class="flex flex-col gap-1.5">
                            <label class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Asignar Operador (Proveedor)</label>
                            <select name="proveedor_id" required class="w-full h-9 rounded-lg border border-slate-800 bg-slate-900 px-3 text-xs text-white focus:border-cyan-500 cursor-pointer">
                                <option value="">-- Selecciona Operador --</option>
                                @foreach($proveedores as $prov)
                                    <option value="{{ $prov->id }}">{{ $prov->nombre_empresa }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Título del Tour -->
                        <div class="flex flex-col gap-1.5">
                            <label class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Título del Tour</label>
                            <input type="text" name="titulo" required placeholder="Ej. Excursión a Chichén Itzá Premium" class="w-full h-9 rounded-lg border border-slate-800 bg-slate-900 px-3 text-xs text-white focus:border-cyan-500">
                        </div>

                        <!-- Resumen del Tour -->
                        <div class="flex flex-col gap-1.5">
                            <label class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Resumen (Descripción Corta)</label>
                            <input type="text" name="descripcion_corta" required placeholder="Ej. Vive una experiencia inolvidable visitando una de las maravillas..." class="w-full h-9 rounded-lg border border-slate-800 bg-slate-900 px-3 text-xs text-white focus:border-cyan-500">
                        </div>

                        <!-- Descripción Larga -->
                        <div class="flex flex-col gap-1.5">
                            <label class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Descripción Detallada (Larga)</label>
                            <textarea name="descripcion_larga" required placeholder="Describe paso a paso el itinerario y detalles del tour..." rows="4" class="w-full rounded-lg border border-slate-800 bg-slate-900 p-3 text-xs text-white focus:border-cyan-500"></textarea>
                        </div>

                        <!-- Condiciones Financieras y Logística -->
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                            <div class="flex flex-col gap-1.5">
                                <label class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Precio MXN</label>
                                <input type="number" name="precio_base_usd" required min="1" placeholder="1200" class="w-full h-9 rounded-lg border border-slate-800 bg-slate-900 px-3 text-xs text-white focus:border-cyan-500">
                            </div>
                            <div class="flex flex-col gap-1.5">
                                <label class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Duración</label>
                                <input type="text" name="duracion" required placeholder="Ej. 12 horas" class="w-full h-9 rounded-lg border border-slate-800 bg-slate-900 px-3 text-xs text-white focus:border-cyan-500">
                            </div>
                            <div class="flex flex-col gap-1.5">
                                <label class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Ubicación</label>
                                <input type="text" name="ubicacion" required placeholder="Ej. Chichén Itzá" class="w-full h-9 rounded-lg border border-slate-800 bg-slate-900 px-3 text-xs text-white focus:border-cyan-500">
                            </div>
                            <div class="flex flex-col gap-1.5">
                                <label class="text-[10px] font-bold uppercase tracking-wider text-slate-400">País</label>
                                <input type="text" name="pais" required value="México" class="w-full h-9 rounded-lg border border-slate-800 bg-slate-900 px-3 text-xs text-white focus:border-cyan-500">
                            </div>
                        </div>

                        <!-- Tags -->
                        <div class="flex flex-col gap-1.5">
                            <label class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Tags (separados por coma)</label>
                            <input type="text" name="tags" placeholder="Cultura, Aventura, Arqueología" class="w-full h-9 rounded-lg border border-slate-800 bg-slate-900 px-3 text-xs text-white focus:border-cyan-500">
                        </div>

                        <!-- Punto de encuentro y fotos -->
                        <div class="flex flex-col gap-1.5">
                            <label class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Punto de Encuentro (Dirección y detalles)</label>
                            <input type="text" name="punto_encuentro" placeholder="Ej. Lobby de su hotel o Marina de Cancún" class="w-full h-9 rounded-lg border border-slate-800 bg-slate-900 px-3 text-xs text-white focus:border-cyan-500">
                        </div>

                        <!-- Carga Dual de Imagen Destacada (Portada) -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="flex flex-col gap-1.5">
                                <label class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Subir Imagen de Portada (Destacada)</label>
                                <input type="file" name="imagen_destacada_file" accept="image/*" class="w-full text-xs text-slate-400 file:mr-4 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-[10px] file:font-semibold file:bg-slate-900 file:text-cyan-400 hover:file:bg-slate-800 cursor-pointer">
                            </div>
                            <div class="flex flex-col gap-1.5">
                                <label class="text-[10px] font-bold uppercase tracking-wider text-slate-400">O URL de Portada (Opcional)</label>
                                <input type="url" name="imagen_destacada" placeholder="https://..." class="w-full h-9 rounded-lg border border-slate-800 bg-slate-900 px-3 text-xs text-white focus:border-cyan-500">
                            </div>
                        </div>

                        <!-- Carga Dual de Galería Adicional -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="flex flex-col gap-1.5">
                                <label class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Subir Fotos a Galería Adicional</label>
                                <input type="file" name="galeria_files[]" multiple accept="image/*" class="w-full text-xs text-slate-400 file:mr-4 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-[10px] file:font-semibold file:bg-slate-900 file:text-cyan-400 hover:file:bg-slate-800 cursor-pointer">
                            </div>
                            <div class="flex flex-col gap-1.5">
                                <label class="text-[10px] font-bold uppercase tracking-wider text-slate-400">O URLs de Galería (coma, Opcional)</label>
                                <textarea name="galeria" placeholder="https://url1.com, https://url2.com" rows="1" class="w-full rounded-lg border border-slate-800 bg-slate-900 p-2 text-xs text-white focus:border-cyan-500"></textarea>
                            </div>
                        </div>

                        <!-- Inclusiones y Exclusiones -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="flex flex-col gap-1.5">
                                <label class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Qué Incluye (separados por coma)</label>
                                <textarea name="incluye" placeholder="Ej. Guía certificado, Transportación climatizada, Entradas oficiales" rows="2" class="w-full rounded-lg border border-slate-800 bg-slate-900 p-2.5 text-xs text-white placeholder-slate-600 focus:border-cyan-500 focus:ring-0 focus:outline-none"></textarea>
                            </div>
                            <div class="flex flex-col gap-1.5">
                                <label class="text-[10px] font-bold uppercase tracking-wider text-slate-400">No Incluye (separados por coma)</label>
                                <textarea name="no_incluye" placeholder="Ej. Propinas, Souvenirs, Almuerzo" rows="2" class="w-full rounded-lg border border-slate-800 bg-slate-900 p-2.5 text-xs text-white placeholder-slate-600 focus:border-cyan-500 focus:ring-0 focus:outline-none"></textarea>
                            </div>
                        </div>

                        <!-- Itinerario de Viaje -->
                        <div class="flex flex-col gap-3 border-t border-slate-900 pt-4">
                            <div class="flex items-center justify-between">
                                <h4 class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Itinerario de Viaje (Pasos)</h4>
                                <button type="button" onclick="addItineraryRow('create')" class="px-2 py-1 rounded bg-slate-900 hover:bg-slate-800 border border-slate-800 text-[9px] font-bold text-cyan-400 transition-colors cursor-pointer">
                                    + Añadir Paso
                                </button>
                            </div>
                            <div id="create-itinerary-container" class="flex flex-col gap-3 max-h-[200px] overflow-y-auto pr-1">
                                <!-- Filas de itinerario dinámicas -->
                            </div>
                        </div>

                        <!-- Botones de Acción -->
                        <div class="flex gap-3 mt-2">
                            <button type="button" onclick="closeCreateTourModal()" class="w-1/3 h-10 rounded-lg border border-slate-800 hover:bg-slate-900 text-xs font-bold text-slate-400 transition-colors cursor-pointer">
                                Cancelar
                            </button>
                            <button type="submit" class="w-2/3 h-10 rounded-lg bg-gradient-to-r from-cyan-500 to-indigo-600 text-xs font-black uppercase text-white shadow-lg cursor-pointer">
                                Crear Tour
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Modal de Edición de Tour (Solo Administrador) -->
            <div id="edit-tour-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm transition-all duration-200">
                <div class="relative w-full max-w-2xl rounded-3xl border border-slate-900 bg-slate-950 p-6 shadow-2xl overflow-y-auto max-h-[90vh]">
                    <!-- Botón Cerrar -->
                    <button type="button" onclick="closeEditTourModal()" class="absolute top-4 right-4 text-slate-400 hover:text-white text-lg cursor-pointer">
                        &times;
                    </button>
                    
                    <h3 class="text-xs font-black uppercase tracking-widest text-cyan-400 border-b border-slate-900 pb-3 mb-5">
                        Editar Tour
                    </h3>

                    <form id="edit-tour-form" method="POST" enctype="multipart/form-data" class="flex flex-col gap-5">
                        @csrf
                        
                        <!-- Proveedor Asignado -->
                        <div class="flex flex-col gap-1.5">
                            <label class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Asignar Operador (Proveedor)</label>
                            <select name="proveedor_id" id="edit-tour-proveedor" required class="w-full h-9 rounded-lg border border-slate-800 bg-slate-900 px-3 text-xs text-white focus:border-cyan-500 cursor-pointer">
                                @foreach($proveedores as $prov)
                                    <option value="{{ $prov->id }}">{{ $prov->nombre_empresa }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Título del Tour -->
                        <div class="flex flex-col gap-1.5">
                            <label class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Título del Tour</label>
                            <input type="text" name="titulo" id="edit-tour-titulo" required class="w-full h-9 rounded-lg border border-slate-800 bg-slate-900 px-3 text-xs text-white focus:border-cyan-500">
                        </div>

                        <!-- Resumen del Tour -->
                        <div class="flex flex-col gap-1.5">
                            <label class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Resumen (Descripción Corta)</label>
                            <input type="text" name="descripcion_corta" id="edit-tour-resumen" required class="w-full h-9 rounded-lg border border-slate-800 bg-slate-900 px-3 text-xs text-white focus:border-cyan-500">
                        </div>

                        <!-- Descripción Larga -->
                        <div class="flex flex-col gap-1.5">
                            <label class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Descripción Detallada (Larga)</label>
                            <textarea name="descripcion_larga" id="edit-tour-detalle" required rows="4" class="w-full rounded-lg border border-slate-800 bg-slate-900 p-3 text-xs text-white focus:border-cyan-500"></textarea>
                        </div>

                        <!-- Condiciones Financieras y Logística -->
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                            <div class="flex flex-col gap-1.5">
                                <label class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Precio MXN</label>
                                <input type="number" name="precio_base_usd" id="edit-tour-precio" required min="1" class="w-full h-9 rounded-lg border border-slate-800 bg-slate-900 px-3 text-xs text-white focus:border-cyan-500">
                            </div>
                            <div class="flex flex-col gap-1.5">
                                <label class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Duración</label>
                                <input type="text" name="duracion" id="edit-tour-duracion" required class="w-full h-9 rounded-lg border border-slate-800 bg-slate-900 px-3 text-xs text-white focus:border-cyan-500">
                            </div>
                            <div class="flex flex-col gap-1.5">
                                <label class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Ubicación</label>
                                <input type="text" name="ubicacion" id="edit-tour-ubicacion" required class="w-full h-9 rounded-lg border border-slate-800 bg-slate-900 px-3 text-xs text-white focus:border-cyan-500">
                            </div>
                            <div class="flex flex-col gap-1.5">
                                <label class="text-[10px] font-bold uppercase tracking-wider text-slate-400">País</label>
                                <input type="text" name="pais" id="edit-tour-pais" required class="w-full h-9 rounded-lg border border-slate-800 bg-slate-900 px-3 text-xs text-white focus:border-cyan-500">
                            </div>
                        </div>

                        <!-- Tags -->
                        <div class="flex flex-col gap-1.5">
                            <label class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Tags (separados por coma)</label>
                            <input type="text" name="tags" id="edit-tour-tags" class="w-full h-9 rounded-lg border border-slate-800 bg-slate-900 px-3 text-xs text-white focus:border-cyan-500">
                        </div>

                        <!-- Punto de encuentro y fotos -->
                        <div class="flex flex-col gap-1.5">
                            <label class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Punto de Encuentro (Dirección y detalles)</label>
                            <input type="text" name="punto_encuentro" id="edit-tour-punto-encuentro" class="w-full h-9 rounded-lg border border-slate-800 bg-slate-900 px-3 text-xs text-white focus:border-cyan-500">
                        </div>

                        <!-- Carga Dual de Imagen Destacada (Portada) -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="flex flex-col gap-1.5">
                                <label class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Subir Nueva Imagen de Portada (Destacada)</label>
                                <input type="file" name="imagen_destacada_file" accept="image/*" class="w-full text-xs text-slate-400 file:mr-4 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-[10px] file:font-semibold file:bg-slate-900 file:text-cyan-400 hover:file:bg-slate-800 cursor-pointer">
                            </div>
                            <div class="flex flex-col gap-1.5">
                                <label class="text-[10px] font-bold uppercase tracking-wider text-slate-400">O URL de Portada Actual</label>
                                <input type="url" name="imagen_destacada" id="edit-tour-imagen" class="w-full h-9 rounded-lg border border-slate-800 bg-slate-900 px-3 text-xs text-white focus:border-cyan-500">
                            </div>
                        </div>

                        <!-- Carga Dual de Galería Adicional -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="flex flex-col gap-1.5">
                                <label class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Subir Nuevas Fotos a Galería Adicional</label>
                                <input type="file" name="galeria_files[]" multiple accept="image/*" class="w-full text-xs text-slate-400 file:mr-4 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-[10px] file:font-semibold file:bg-slate-900 file:text-cyan-400 hover:file:bg-slate-800 cursor-pointer">
                            </div>
                            <div class="flex flex-col gap-1.5">
                                <label class="text-[10px] font-bold uppercase tracking-wider text-slate-400">O URLs de Galería Actuales (coma)</label>
                                <textarea name="galeria" id="edit-tour-galeria" rows="1" class="w-full rounded-lg border border-slate-800 bg-slate-900 p-2 text-xs text-white focus:border-cyan-500"></textarea>
                            </div>
                        </div>

                        <!-- Inclusiones y Exclusiones -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="flex flex-col gap-1.5">
                                <label class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Qué Incluye (separados por coma)</label>
                                <textarea name="incluye" id="edit-tour-incluye" rows="2" class="w-full rounded-lg border border-slate-800 bg-slate-900 p-2.5 text-xs text-white focus:border-cyan-500 focus:ring-0 focus:outline-none"></textarea>
                            </div>
                            <div class="flex flex-col gap-1.5">
                                <label class="text-[10px] font-bold uppercase tracking-wider text-slate-400">No Incluye (separados por coma)</label>
                                <textarea name="no_incluye" id="edit-tour-no-incluye" rows="2" class="w-full rounded-lg border border-slate-800 bg-slate-900 p-2.5 text-xs text-white focus:border-cyan-500 focus:ring-0 focus:outline-none"></textarea>
                            </div>
                        </div>

                        <!-- Itinerario de Viaje -->
                        <div class="flex flex-col gap-3 border-t border-slate-900 pt-4">
                            <div class="flex items-center justify-between">
                                <h4 class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Itinerario de Viaje (Pasos)</h4>
                                <button type="button" onclick="addItineraryRow('edit')" class="px-2 py-1 rounded bg-slate-900 hover:bg-slate-800 border border-slate-800 text-[9px] font-bold text-cyan-400 transition-colors cursor-pointer">
                                    + Añadir Paso
                                </button>
                            </div>
                            <div id="edit-itinerary-container" class="flex flex-col gap-3 max-h-[200px] overflow-y-auto pr-1">
                                <!-- Filas de itinerario dinámicas -->
                            </div>
                        </div>

                        <!-- Botones de Acción -->
                        <div class="flex gap-3 mt-2">
                            <button type="button" onclick="closeEditTourModal()" class="w-1/3 h-10 rounded-lg border border-slate-800 hover:bg-slate-900 text-xs font-bold text-slate-400 transition-colors cursor-pointer">
                                Cancelar
                            </button>
                            <button type="submit" class="w-2/3 h-10 rounded-lg bg-gradient-to-r from-cyan-500 to-indigo-600 text-xs font-black uppercase text-white shadow-lg cursor-pointer">
                                Guardar Cambios
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        @endif
    </div>

    <!-- Modal de Disponibilidad por Día (Estilo Airbnb Premium) -->
    <div id="edit-day-availability-modal" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm animate-fade-in">
        <div class="relative w-full max-w-md rounded-3xl border border-slate-900 bg-slate-950 p-6 shadow-2xl max-h-[90vh] overflow-y-auto">
            <!-- Botón Cerrar -->
            <button type="button" onclick="closeDayModal()" class="absolute top-4 right-4 text-slate-400 hover:text-white text-lg cursor-pointer">
                &times;
            </button>
            
            <h3 class="text-xs font-black uppercase tracking-widest text-cyan-400 border-b border-slate-900 pb-3 mb-5" id="modal-date-title">
                Disponibilidad: -
            </h3>
            
            <form id="day-availability-form" class="flex flex-col gap-4">
                <input type="hidden" id="modal-tour-id" name="tour_id">
                <input type="hidden" id="modal-fecha" name="fecha">
                
                <!-- Toggle Habilitado -->
                <div class="flex items-center justify-between p-3 rounded-xl border border-slate-900 bg-slate-900/10">
                    <span class="text-xs font-bold text-white">¿Habilitar salidas para este día?</span>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" id="modal-habilitado" name="habilitado" class="sr-only peer" onchange="toggleModalSalidas(this.checked)">
                        <div class="w-9 h-5 bg-slate-800 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-slate-400 after:border-slate-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-cyan-500 peer-checked:after:bg-white"></div>
                    </label>
                </div>
                
                <!-- Contenedor de Salidas -->
                <div id="modal-salidas-wrapper" class="hidden flex flex-col gap-3">
                    <div class="flex justify-between items-center">
                        <h4 class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Salidas Programadas</h4>
                        <button type="button" onclick="addSalidaRow()" class="px-2 py-1 rounded bg-slate-900 hover:bg-slate-800 border border-slate-800 text-[9px] font-bold text-cyan-400 transition-colors cursor-pointer">
                            + Añadir Salida
                        </button>
                    </div>
                    
                    <div id="modal-salidas-list" class="flex flex-col gap-2 max-h-[200px] overflow-y-auto pr-1">
                        <!-- Filas dinámicas de salidas -->
                    </div>
                </div>
                
                <!-- Alertas de advertencia en el modal -->
                <div id="modal-warning-alert" class="hidden p-3 rounded-xl border border-amber-500/30 bg-amber-500/10 text-amber-400 text-[10px] font-medium leading-relaxed">
                    <!-- Advertencias dinámicas -->
                </div>

                <!-- Botones de Acción -->
                <div class="flex gap-3 mt-4">
                    <button type="button" onclick="closeDayModal()" class="w-1/3 h-10 rounded-lg border border-slate-800 hover:bg-slate-900 text-xs font-bold text-slate-400 transition-colors cursor-pointer">
                        Cancelar
                    </button>
                    <button type="submit" class="w-2/3 h-10 rounded-lg bg-gradient-to-r from-cyan-500 to-indigo-600 text-xs font-black uppercase text-white shadow-lg cursor-pointer">
                        Guardar Cambios
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- DATOS DE RESERVAS PARA EL CALENDARIO GLOBAL -->
    @if(Auth::user()->isAdmin())
        <script>
            window.allReservas = [
                @foreach($reservas as $res)
                    {
                        ticket: "{{ $res->ticket_codigo }}",
                        cliente: "{{ addslashes($res->nombre_cliente) }}",
                        correo: "{{ addslashes($res->correo_cliente) }}",
                        telefono: "{{ addslashes($res->telefono_cliente) }}",
                        fecha_compra: "{{ $res->fecha_reserva->format('Y-m-d') }}",
                        venta: {{ $res->precio_total_usd }},
                        comision: {{ $res->comision_total_usd }},
                        detalles: [
                            @foreach($res->detalles as $det)
                                @if($det->tour)
                                {
                                    tour_id: "{{ $det->tour_id }}",
                                    tour_nombre: "{{ addslashes($det->tour->nombre) }}",
                                    proveedor_id: "{{ $det->tour->proveedor_id }}",
                                    proveedor_nombre: "{{ addslashes($det->tour->proveedor->nombre_empresa) }}",
                                    fecha_viaje: "{{ $det->fecha_seleccionada->format('Y-m-d') }}",
                                    horario: "{{ $det->horario }}",
                                    pax: {{ $det->cantidad_personas }}
                                },
                                @endif
                            @endforeach
                        ]
                    },
                @endforeach
            ];
        </script>
    @elseif(Auth::user()->isProveedor())
        <script>
            window.allReservas = [
                @foreach($reservaDetalles as $det)
                    {
                        ticket: "{{ $det->reserva->ticket_codigo }}",
                        cliente: "{{ addslashes($det->reserva->nombre_cliente) }}",
                        correo: "{{ addslashes($det->reserva->correo_cliente) }}",
                        telefono: "{{ addslashes($det->reserva->telefono_cliente) }}",
                        fecha_compra: "{{ $det->reserva->fecha_reserva->format('Y-m-d') }}",
                        venta: {{ $det->precio_unitario_usd * $det->cantidad_personas }},
                        comision: {{ $det->comision_usd }},
                        detalles: [
                            {
                                tour_id: "{{ $det->tour_id }}",
                                tour_nombre: "{{ addslashes($det->tour->nombre) }}",
                                proveedor_id: "{{ $det->tour->proveedor_id }}",
                                proveedor_nombre: "{{ addslashes($det->tour->proveedor->nombre_empresa) }}",
                                fecha_viaje: "{{ $det->fecha_seleccionada->format('Y-m-d') }}",
                                horario: "{{ $det->horario }}",
                                pax: {{ $det->cantidad_personas }}
                            }
                        ]
                    },
                @endforeach
            ];
        </script>
    @else
        <script>
            window.allReservas = [];
        </script>
    @endif

    <!-- SCRIPTS JS DASHBOARD INTERACTIVO -->
    <script>
        // --- ESTADO Y VARIABLES DEL CALENDARIO GLOBAL DE RESERVAS ---
        let resCalYear = new Date().getFullYear();
        let resCalMonth = new Date().getMonth() + 1; // 1-12

        // Lógica de Pestañas SPA para el Administrador
        function switchTab(tabId) {
            // Ocultar todos los contenidos de pestaña
            const panes = document.querySelectorAll('.tab-pane');
            panes.forEach(pane => pane.classList.add('hidden'));

            // Quitar resalte de botones
            const buttons = document.querySelectorAll('.tab-btn');
            buttons.forEach(btn => {
                btn.classList.remove('border-cyan-400', 'text-cyan-400');
                btn.classList.add('border-transparent', 'text-slate-400');
            });

            // Mostrar el seleccionado
            const activePane = document.getElementById(`tab-content-${tabId}`);
            if (activePane) activePane.classList.remove('hidden');

            const activeBtn = document.getElementById(`tab-btn-${tabId}`);
            if (activeBtn) {
                activeBtn.classList.remove('border-transparent', 'text-slate-400');
                activeBtn.classList.add('border-cyan-400', 'text-cyan-400');
            }

            // Guardar pestaña activa en localStorage
            localStorage.setItem('admin_active_tab', tabId);
        }

        // Restaurar pestaña activa al cargar la página
        // Prioridad: sesión del servidor (tras POST) > localStorage (última pestaña visitada)
        document.addEventListener('DOMContentLoaded', function() {
            const serverTab = @json($initialTab ?? null); // Enviada por el controlador tras un POST
            const savedTab  = localStorage.getItem('admin_active_tab');
            const tabToShow = serverTab || savedTab;

            if (tabToShow) {
                const activeBtn = document.getElementById(`tab-btn-${tabToShow}`);
                if (activeBtn) {
                    switchTab(tabToShow);
                }
            }

            // Auto-dismiss flash messages
            ['flash-success','flash-error','flash-validation'].forEach(id => {
                const el = document.getElementById(id);
                if (el) setTimeout(() => el.style.transition = 'opacity 0.5s', 4500) || setTimeout(() => el.remove(), 5000);
            });

            // Inicializar navegación del calendario de reservas
            const prevBtn = document.getElementById('res-cal-prev');
            const nextBtn = document.getElementById('res-cal-next');

            if (prevBtn) {
                prevBtn.addEventListener('click', () => {
                    resCalMonth--;
                    if (resCalMonth < 1) {
                        resCalMonth = 12;
                        resCalYear--;
                    }
                    renderReservasCalendar();
                });
            }

            if (nextBtn) {
                nextBtn.addEventListener('click', () => {
                    resCalMonth++;
                    if (resCalMonth > 12) {
                        resCalMonth = 1;
                        resCalYear++;
                    }
                    renderReservasCalendar();
                });
            }

            // Primer renderizado
            if (typeof renderReservasCalendar === 'function') {
                renderReservasCalendar();
            }
        });

        // Lógica Modales Cambio de Rol
        function openRoleModal(id, name, tipo, proveedorId) {
            const modal = document.getElementById('role-modal');
            const nameEl = document.getElementById('role-modal-username');
            const form = document.getElementById('role-modal-form');
            const tipoSelect = document.getElementById('role-modal-tipo');
            
            nameEl.textContent = name;
            form.action = `/dashboard/user/${id}/role`;
            tipoSelect.value = tipo;

            toggleProveedorDropdown(tipo);

            const provSelect = document.getElementById('role-modal-prov-id');
            if (provSelect) provSelect.value = proveedorId || '';

            modal.classList.remove('hidden');
        }

        function closeRoleModal() {
            document.getElementById('role-modal').classList.add('hidden');
        }

        function toggleProveedorDropdown(role) {
            const dropdown = document.getElementById('role-modal-prov-group');
            if (role === 'PT') {
                dropdown.classList.remove('hidden');
                document.getElementById('role-modal-prov-id').required = true;
            } else {
                dropdown.classList.add('hidden');
                document.getElementById('role-modal-prov-id').required = false;
            }
        }

        // Lógica Modales Contraseña
        function openPasswordModal(id, name) {
            const modal = document.getElementById('password-modal');
            const nameEl = document.getElementById('pass-modal-username');
            const form = document.getElementById('pass-modal-form');

            nameEl.textContent = name;
            form.action = `/dashboard/user/${id}/reset-password`;

            modal.classList.remove('hidden');
        }

        function closePasswordModal() {
            document.getElementById('password-modal').classList.add('hidden');
        }

        // Alternar visibilidad de cupo máximo según la operación
        function toggleCupoField(selectEl) {
            const form = selectEl.closest('form');
            const cupoContainer = form.querySelector('.cupo-container');
            if (selectEl.value === 'deshabilitar') {
                cupoContainer.classList.add('hidden');
            } else {
                cupoContainer.classList.remove('hidden');
            }
        }

        // Filtros del listado de reservas para Administrador
        function applyReservasFilters() {
            const searchInput = document.getElementById('filter-search');
            const proveedorSelect = document.getElementById('filter-proveedor');
            const tourSelect = document.getElementById('filter-tour');
            const desdeInput = document.getElementById('filter-desde');
            const hastaInput = document.getElementById('filter-hasta');

            if (!searchInput || !proveedorSelect || !tourSelect || !desdeInput || !hastaInput) {
                return;
            }

            const searchValue = searchInput.value.toLowerCase().trim();
            const proveedorValue = proveedorSelect.value;
            const tourValue = tourSelect.value;
            const desdeValue = desdeInput.value;
            const hastaValue = hastaInput.value;

            const rows = document.querySelectorAll('.reserva-row');
            let visibleCount = 0;
            let salesSum = 0;
            let commissionsSum = 0;

            rows.forEach(row => {
                const clientName = row.getAttribute('data-cliente') || '';
                const ticketCode = row.getAttribute('data-ticket') || '';
                const rowProveedores = (row.getAttribute('data-proveedores') || '').split(',');
                const rowTours = (row.getAttribute('data-tours') || '').split(',');
                const purchaseDate = row.getAttribute('data-fecha-compra') || '';

                // 1. Filtro Búsqueda (Cliente o Ticket)
                let matchSearch = true;
                if (searchValue) {
                    matchSearch = clientName.includes(searchValue) || ticketCode.includes(searchValue);
                }

                // 2. Filtro Proveedor
                let matchProveedor = true;
                if (proveedorValue) {
                    matchProveedor = rowProveedores.includes(proveedorValue);
                }

                // 3. Filtro Tour
                let matchTour = true;
                if (tourValue) {
                    matchTour = rowTours.includes(tourValue);
                }

                // 4. Filtro Fecha Desde
                let matchDesde = true;
                if (desdeValue) {
                    matchDesde = purchaseDate >= desdeValue;
                }

                // 5. Filtro Fecha Hasta
                let matchHasta = true;
                if (hastaValue) {
                    matchHasta = purchaseDate <= hastaValue;
                }

                // Evaluar todas las condiciones juntas
                if (matchSearch && matchProveedor && matchTour && matchDesde && matchHasta) {
                    row.classList.remove('hidden');
                    visibleCount++;

                    // Sumar métricas
                    salesSum += parseFloat(row.getAttribute('data-venta') || 0);
                    commissionsSum += parseFloat(row.getAttribute('data-comision') || 0);
                } else {
                    row.classList.add('hidden');
                }
            });

            // Actualizar tarjetas de métricas
            const formatter = new Intl.NumberFormat('en-US', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });

            const salesEl = document.getElementById('metric-total-sales');
            const bookingsEl = document.getElementById('metric-total-bookings');
            const commissionsEl = document.getElementById('metric-total-commissions');
            const earningsEl = document.getElementById('metric-net-earnings');

            if (salesEl) salesEl.textContent = formatter.format(salesSum);
            if (bookingsEl) bookingsEl.textContent = visibleCount;
            if (commissionsEl) commissionsEl.textContent = formatter.format(commissionsSum);
            if (earningsEl) earningsEl.textContent = formatter.format(salesSum - commissionsSum);

            // Mostrar/ocultar alerta si no hay coincidencias
            const alertEl = document.getElementById('no-reservas-alert');
            if (alertEl) {
                if (visibleCount === 0 && rows.length > 0) {
                    alertEl.classList.remove('hidden');
                } else {
                    alertEl.classList.add('hidden');
                }
            }

            // Redibujar el calendario con los datos filtrados
            if (typeof renderReservasCalendar === 'function') {
                renderReservasCalendar();
            }
        }

        // --- FUNCIONES DEL CALENDARIO GLOBAL DE RESERVAS ---
        function getFilteredReservasData() {
            const searchInput = document.getElementById('filter-search');
            const proveedorSelect = document.getElementById('filter-proveedor');
            const tourSelect = document.getElementById('filter-tour');
            const desdeInput = document.getElementById('filter-desde');
            const hastaInput = document.getElementById('filter-hasta');

            const searchValue = searchInput ? searchInput.value.toLowerCase().trim() : '';
            const proveedorValue = proveedorSelect ? proveedorSelect.value : '';
            const tourValue = tourSelect ? tourSelect.value : '';
            const desdeValue = desdeInput ? desdeInput.value : '';
            const hastaValue = hastaInput ? hastaInput.value : '';

            if (!window.allReservas) return [];

            return window.allReservas.filter(res => {
                // 1. Filtro Búsqueda
                let matchSearch = true;
                if (searchValue) {
                    matchSearch = res.cliente.toLowerCase().includes(searchValue) || res.ticket.toLowerCase().includes(searchValue);
                }

                // 2. Filtro Proveedor
                let matchProveedor = true;
                if (proveedorValue) {
                    matchProveedor = res.detalles.some(d => d.proveedor_id == proveedorValue);
                }

                // 3. Filtro Tour
                let matchTour = true;
                if (tourValue) {
                    matchTour = res.detalles.some(d => d.tour_id == tourValue);
                }

                // 4. Filtro Fecha Desde
                let matchDesde = true;
                if (desdeValue) {
                    matchDesde = res.fecha_compra >= desdeValue;
                }

                // 5. Filtro Fecha Hasta
                let matchHasta = true;
                if (hastaValue) {
                    matchHasta = res.fecha_compra <= hastaValue;
                }

                return matchSearch && matchProveedor && matchTour && matchDesde && matchHasta;
            });
        }

        function renderReservasCalendar() {
            const grid = document.getElementById('res-cal-days-grid');
            const label = document.getElementById('res-cal-month-label');
            if (!grid || !label) return;

            const monthNames = [
                'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
                'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'
            ];
            label.textContent = `${monthNames[resCalMonth - 1]} ${resCalYear}`;

            grid.innerHTML = '';

            const totalDays = new Date(resCalYear, resCalMonth, 0).getDate();
            let startDay = new Date(resCalYear, resCalMonth - 1, 1).getDay();
            // Ajustar inicio de semana a Lunes (Lunes = 0, Domingo = 6)
            let emptySlots = startDay === 0 ? 6 : startDay - 1;

            // Días vacíos iniciales
            for (let i = 0; i < emptySlots; i++) {
                const emptyCell = document.createElement('div');
                emptyCell.className = 'aspect-square';
                grid.appendChild(emptyCell);
            }

            // Obtener reservas que cumplen con los filtros actuales
            const filteredReservas = getFilteredReservasData();

            // Pintar celdas de días
            for (let day = 1; day <= totalDays; day++) {
                const dateStr = `${resCalYear}-${String(resCalMonth).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
                
                // Buscar viajes programados para este día en la lista filtrada
                let dayDetails = [];
                let totalPax = 0;

                filteredReservas.forEach(res => {
                    res.detalles.forEach(det => {
                        if (det.fecha_viaje === dateStr) {
                            dayDetails.push({
                                ticket: res.ticket,
                                cliente: res.cliente,
                                correo: res.correo,
                                telefono: res.telefono,
                                tour_nombre: det.tour_nombre,
                                proveedor_nombre: det.proveedor_nombre,
                                horario: det.horario,
                                pax: det.pax
                            });
                            totalPax += det.pax;
                        }
                    });
                });

                const dayCell = document.createElement('div');
                dayCell.className = 'aspect-square rounded-2xl border flex flex-col items-center justify-between p-2 select-none transition-all duration-200 min-h-[50px]';
                
                const dayNumSpan = document.createElement('span');
                dayNumSpan.className = 'text-xs font-mono font-bold';
                dayNumSpan.textContent = day;
                dayCell.appendChild(dayNumSpan);

                if (dayDetails.length > 0) {
                    // Celda con reservas
                    dayCell.className += ' border-cyan-500/30 bg-cyan-500/5 hover:bg-cyan-500/10 cursor-pointer hover:border-cyan-500/60 text-cyan-400';
                    dayNumSpan.className += ' text-cyan-300';

                    // Badge de pasajeros
                    const badge = document.createElement('span');
                    badge.className = 'text-[9px] bg-cyan-950/60 border border-cyan-900 text-cyan-400 px-1 py-0.5 rounded font-mono font-bold w-full text-center truncate';
                    badge.innerHTML = `👥 ${totalPax}`;
                    dayCell.appendChild(badge);

                    // Evento Click
                    dayCell.addEventListener('click', () => {
                        document.querySelectorAll('#res-cal-days-grid > div').forEach(c => {
                            c.classList.remove('ring-2', 'ring-cyan-500', 'scale-105');
                        });
                        dayCell.classList.add('ring-2', 'ring-cyan-500', 'scale-105');
                        
                        showDailyReservasDetails(dateStr, dayDetails);
                    });
                } else {
                    // Celda sin reservas
                    dayCell.className += ' border-slate-900 bg-slate-950/10 text-slate-500 hover:border-slate-800 hover:text-slate-300';
                }

                grid.appendChild(dayCell);
            }
        }

        function showDailyReservasDetails(dateStr, details) {
            const container = document.getElementById('res-cal-day-details');
            if (!container) return;

            const dateParts = dateStr.split('-');
            const dateObj = new Date(dateParts[0], dateParts[1] - 1, dateParts[2]);
            const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
            const formattedDate = dateObj.toLocaleDateString('es-ES', options);

            let html = `
                <div class="mb-2 border-b border-slate-900 pb-2">
                    <p class="text-[9px] font-bold text-slate-500 uppercase tracking-wider">Fecha Seleccionada</p>
                    <p class="text-xs font-black text-cyan-400 capitalize">${formattedDate}</p>
                </div>
                <div class="flex flex-col gap-3">
            `;

            details.forEach(item => {
                html += `
                    <div class="p-3 rounded-2xl border border-slate-900 bg-slate-950/60 flex flex-col gap-2 shadow-lg hover:border-slate-800 transition-colors">
                        <div class="flex items-center justify-between border-b border-slate-900 pb-1.5">
                            <span class="text-[10px] font-black text-cyan-400 font-mono">${item.ticket}</span>
                            <span class="text-[9px] font-bold bg-cyan-950 text-cyan-400 border border-cyan-800/40 px-1.5 py-0.5 rounded-lg">👥 ${item.pax} pax</span>
                        </div>
                        <div>
                            <p class="text-[11px] font-black text-white leading-snug">${item.tour_nombre}</p>
                            <p class="text-[9px] text-slate-500 mt-0.5">Operado por: ${item.proveedor_nombre}</p>
                        </div>
                        <div class="grid grid-cols-2 gap-2 text-[10px] bg-slate-900/20 p-2 rounded-xl border border-slate-900/60 mt-1">
                            <div>
                                <span class="text-slate-500 block text-[8px] uppercase font-bold">Cliente</span>
                                <span class="text-slate-300 font-semibold truncate block">${item.cliente}</span>
                            </div>
                            <div>
                                <span class="text-slate-500 block text-[8px] uppercase font-bold">Salida</span>
                                <span class="text-slate-300 font-mono font-bold block">${item.horario}</span>
                            </div>
                        </div>
                        <div class="flex flex-col gap-1 text-[9px] text-slate-400 mt-1 border-t border-slate-900/60 pt-1.5">
                            <div class="flex items-center gap-1.5 truncate">
                                <span>📧</span>
                                <span class="truncate" title="${item.correo}">${item.correo}</span>
                            </div>
                            <div class="flex items-center gap-1.5">
                                <span>📞</span>
                                <span>${item.telefono}</span>
                            </div>
                        </div>
                    </div>
                `;
            });

            html += `</div>`;
            container.innerHTML = html;
        }

        function clearReservasFilters() {
            const searchInput = document.getElementById('filter-search');
            const proveedorSelect = document.getElementById('filter-proveedor');
            const tourSelect = document.getElementById('filter-tour');
            const desdeInput = document.getElementById('filter-desde');
            const hastaInput = document.getElementById('filter-hasta');

            if (searchInput) searchInput.value = '';
            if (proveedorSelect) proveedorSelect.value = '';
            if (tourSelect) tourSelect.value = '';
            if (desdeInput) desdeInput.value = '';
            if (hastaInput) hastaInput.value = '';

            applyReservasFilters();
        }

        // Lógica Modales Editar Proveedor
        function openEditProveedorModal(id, name, desc, rfc, email, repName, repTel, commission, fotoUrl) {
            const modal = document.getElementById('edit-proveedor-modal');
            const form = document.getElementById('edit-prov-form');

            document.getElementById('edit-prov-name').value = name;
            document.getElementById('edit-prov-desc').value = desc;
            document.getElementById('edit-prov-rfc').value = rfc || '';
            document.getElementById('edit-prov-email').value = email;
            document.getElementById('edit-prov-rep-name').value = repName;
            document.getElementById('edit-prov-rep-tel').value = repTel;
            document.getElementById('edit-prov-commission').value = commission;
            document.getElementById('edit-prov-foto').value = fotoUrl || '';

            form.action = `/dashboard/proveedor/${id}/update`;
            modal.classList.remove('hidden');
        }

        function closeEditProveedorModal() {
            document.getElementById('edit-proveedor-modal').classList.add('hidden');
        }

        // ========================================================
        // LÓGICA DE CALENDARIO DE DISPONIBILIDAD ESTILO AIRBNB
        // ========================================================
        
        // Asignar Token CSRF global de Laravel para fetch
        window.Laravel = {
            csrfToken: '{{ csrf_token() }}'
        };

        let currentTourId = '';
        let currentYear = new Date().getFullYear();
        let currentMonth = new Date().getMonth() + 1; // 1-indexed (1-12)
        let calendarData = {}; // Guardará la disponibilidad del mes actual

        // Registrar los selectores de tours
        const tourSelects = document.querySelectorAll('.calendar-tour-select');
        
        tourSelects.forEach(selectEl => {
            selectEl.addEventListener('change', function() {
                const tourId = this.value;
                // Sincronizar todos los selectores de tour en la página
                tourSelects.forEach(sel => sel.value = tourId);
                
                if (tourId) {
                    currentTourId = tourId;
                    loadCalendar();
                } else {
                    currentTourId = '';
                    document.querySelectorAll('.calendar-widget-container').forEach(c => c.classList.add('hidden'));
                }
            });
        });

        // Registrar navegación de meses
        document.querySelectorAll('.prev-month-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                currentMonth--;
                if (currentMonth < 1) {
                    currentMonth = 12;
                    currentYear--;
                }
                loadCalendar();
            });
        });

        document.querySelectorAll('.next-month-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                currentMonth++;
                if (currentMonth > 12) {
                    currentMonth = 1;
                    currentYear++;
                }
                loadCalendar();
            });
        });

        // Cargar datos y renderizar calendario
        function loadCalendar() {
            if (!currentTourId) return;

            // Mostrar el widget del calendario en todos los contenedores de la página
            document.querySelectorAll('.calendar-widget-container').forEach(container => {
                container.classList.remove('hidden');
                
                // Actualizar etiqueta del mes
                const monthNames = [
                    'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
                    'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'
                ];
                const label = container.querySelector('.current-month-label');
                if (label) {
                    label.textContent = `${monthNames[currentMonth - 1]} ${currentYear}`;
                }
            });

            // Llamada AJAX
            fetch(`/dashboard/tour/${currentTourId}/fechas?year=${currentYear}&month=${currentMonth}`)
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        calendarData = data.fechas || {};
                        renderCalendar();
                    } else {
                        console.error('Error al cargar disponibilidad:', data.error);
                    }
                })
                .catch(err => console.error('Error de red al cargar disponibilidad:', err));
        }

        function renderCalendar() {
            const todayStr = new Date().toISOString().split('T')[0];
            const today = new Date(todayStr + 'T00:00:00'); // Evitar problemas de zona horaria

            // Renderizar el grid para todos los calendarios presentes
            document.querySelectorAll('.calendar-days-grid').forEach(grid => {
                grid.innerHTML = '';

                // Obtener datos del mes actual
                const totalDays = new Date(currentYear, currentMonth, 0).getDate();
                
                // Primer día del mes (0 = Domingo, 1 = Lunes, etc.)
                let startDay = new Date(currentYear, currentMonth - 1, 1).getDay();
                // Ajustar para iniciar en Lunes (Lunes = 0, Domingo = 6)
                let emptySlots = startDay === 0 ? 6 : startDay - 1;

                // Celdas vacías al inicio
                for (let i = 0; i < emptySlots; i++) {
                    const emptyCell = document.createElement('div');
                    emptyCell.className = 'min-h-[56px] bg-slate-900/10 rounded-lg opacity-20 border border-slate-950';
                    grid.appendChild(emptyCell);
                }

                // Pintar los días
                for (let day = 1; day <= totalDays; day++) {
                    const dateStr = `${currentYear}-${String(currentMonth).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
                    const cellDate = new Date(dateStr + 'T00:00:00');
                    const isPast = cellDate < today;

                    const dayCell = document.createElement('div');
                    dayCell.className = `min-h-[64px] p-1.5 rounded-xl border flex flex-col justify-between transition-all select-none ${isPast ? 'bg-slate-900/30 border-slate-900/50 opacity-40 cursor-not-allowed' : 'bg-slate-900/40 border-slate-900 hover:border-cyan-500/50 cursor-pointer'}`;
                    
                    // Número del día
                    const dayNum = document.createElement('span');
                    dayNum.className = `text-[10px] font-bold ${isPast ? 'text-slate-600' : 'text-slate-400'}`;
                    dayNum.textContent = day;
                    dayCell.appendChild(dayNum);

                    // Contenedor de salidas para este día
                    const salidas = calendarData[dateStr] || [];
                    const infoContainer = document.createElement('div');
                    infoContainer.className = 'flex flex-col gap-1 mt-1 overflow-hidden';

                    if (salidas.length > 0) {
                        // Cambiar estilo de día habilitado
                        if (!isPast) {
                            dayCell.className = 'min-h-[64px] p-1.5 rounded-xl border bg-emerald-500/5 border-emerald-500/20 hover:border-emerald-400 transition-all cursor-pointer select-none';
                            dayNum.className = 'text-[10px] font-bold text-emerald-400';
                        }
                        
                        // Agregar micro-indicadores para cada salida
                        salidas.forEach(sal => {
                            const badge = document.createElement('div');
                            badge.className = `text-[8px] font-semibold px-1 py-0.5 rounded truncate ${sal.cupo_reservado > 0 ? 'bg-indigo-950 text-indigo-400' : 'bg-emerald-950 text-emerald-400'}`;
                            badge.textContent = `${sal.horario} (${sal.cupo_reservado}/${sal.cupo_maximo})`;
                            infoContainer.appendChild(badge);
                        });
                    } else {
                        // Indicador de "Sin Disponibilidad" en gris sutil
                        if (!isPast) {
                            const indicator = document.createElement('span');
                            indicator.className = 'text-[7px] text-slate-700 uppercase font-black tracking-wider block mt-auto text-right';
                            indicator.textContent = 'Cerrado';
                            infoContainer.appendChild(indicator);
                        }
                    }

                    dayCell.appendChild(infoContainer);

                    // Evento click para abrir el modal
                    if (!isPast) {
                        dayCell.addEventListener('click', () => {
                            openDayModal(dateStr, salidas);
                        });
                    }

                    grid.appendChild(dayCell);
                }
            });
        }

        // --- MANEJO DEL MODAL ---
        
        function openDayModal(fechaStr, salidas) {
            const modal = document.getElementById('edit-day-availability-modal');
            const dateTitle = document.getElementById('modal-date-title');
            const formTourId = document.getElementById('modal-tour-id');
            const formFecha = document.getElementById('modal-fecha');
            const checkboxHabilitado = document.getElementById('modal-habilitado');
            const warningAlert = document.getElementById('modal-warning-alert');

            // Formatear título del modal de forma amigable (Ej: 15 de Junio, 2026)
            const partes = fechaStr.split('-');
            const meses = [
                'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
                'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'
            ];
            const tituloLimpio = `Disponibilidad: ${partes[2]} de ${meses[parseInt(partes[1]) - 1]}, ${partes[0]}`;
            
            dateTitle.textContent = tituloLimpio;
            formTourId.value = currentTourId;
            formFecha.value = fechaStr;
            warningAlert.classList.add('hidden');
            warningAlert.innerHTML = '';

            // Limpiar la lista del modal
            const listContainer = document.getElementById('modal-salidas-list');
            listContainer.innerHTML = '';

            if (salidas.length > 0) {
                checkboxHabilitado.checked = true;
                toggleModalSalidas(true);
                
                salidas.forEach(sal => {
                    addSalidaRow(sal.horario, sal.cupo_maximo, sal.cupo_reservado);
                });
            } else {
                checkboxHabilitado.checked = false;
                toggleModalSalidas(false);
            }

            modal.classList.remove('hidden');
        }

        function closeDayModal() {
            document.getElementById('edit-day-availability-modal').classList.add('hidden');
        }

        function toggleModalSalidas(isHabilitado) {
            const wrapper = document.getElementById('modal-salidas-wrapper');
            if (isHabilitado) {
                wrapper.classList.remove('hidden');
                // Si la lista está vacía, agregar una salida predeterminada
                const list = document.getElementById('modal-salidas-list');
                if (list.children.length === 0) {
                    addSalidaRow('09:00', 20, 0);
                }
            } else {
                wrapper.classList.add('hidden');
            }
        }

        function addSalidaRow(horario = '09:00', cupoMaximo = 20, cupoReservado = 0) {
            const listContainer = document.getElementById('modal-salidas-list');
            
            const row = document.createElement('div');
            row.className = 'salida-row flex gap-2 items-center p-2 rounded-xl border border-slate-900 bg-slate-900/20';
            
            row.innerHTML = `
                <div class="w-1/2 flex flex-col gap-1">
                    <label class="text-[8px] font-bold text-slate-500 uppercase tracking-wider">Horario de Salida</label>
                    <input type="time" class="salida-horario w-full h-8 rounded-lg border border-slate-800 bg-slate-950 px-2 text-xs text-white focus:border-cyan-500" value="${horario}" required ${cupoReservado > 0 ? 'readonly title="Este horario ya tiene reservas y no se puede modificar"' : ''}>
                </div>
                <div class="w-1/3 flex flex-col gap-1">
                    <label class="text-[8px] font-bold text-slate-500 uppercase tracking-wider">Cupo Máx.</label>
                    <input type="number" class="salida-cupo-maximo w-full h-8 rounded-lg border border-slate-800 bg-slate-950 px-2 text-xs text-white focus:border-cyan-500" value="${cupoMaximo}" min="${cupoReservado || 1}" required>
                </div>
                <div class="w-1/4 text-center flex flex-col gap-1 justify-center">
                    <span class="text-[8px] font-bold text-slate-500 uppercase tracking-wider">Reservados</span>
                    <span class="text-xs font-black text-cyan-400 ${cupoReservado > 0 ? 'text-indigo-400' : 'text-slate-500'}">
                        <span class="salida-reservados-label">${cupoReservado}</span>
                    </span>
                </div>
                <div class="pt-4">
                    <button type="button" class="salida-remove-btn text-red-500 hover:text-red-400 text-lg font-bold px-1 transition-colors cursor-pointer" onclick="removeSalidaRow(this)">
                        &times;
                    </button>
                </div>
            `;
            
            listContainer.appendChild(row);
        }

        function removeSalidaRow(btn) {
            const row = btn.closest('.salida-row');
            const cupoReservado = parseInt(row.querySelector('.salida-reservados-label').textContent || 0);
            
            if (cupoReservado > 0) {
                alert('No se puede eliminar esta salida porque ya cuenta con reservas activas.');
                return;
            }
            
            row.remove();
        }

        // Guardar la disponibilidad por AJAX
        document.getElementById('day-availability-form').addEventListener('submit', function(e) {
            e.preventDefault();

            const tour_id = document.getElementById('modal-tour-id').value;
            const fecha = document.getElementById('modal-fecha').value;
            const habilitado = document.getElementById('modal-habilitado').checked;
            
            const salidas = [];
            let isValid = true;
            
            if (habilitado) {
                const rows = document.querySelectorAll('.salida-row');
                rows.forEach(row => {
                    const horario = row.querySelector('.salida-horario').value;
                    const cupo_maximo = parseInt(row.querySelector('.salida-cupo-maximo').value || 0);
                    const cupo_reservado = parseInt(row.querySelector('.salida-reservados-label').textContent || 0);

                    if (!horario || cupo_maximo < 1) {
                        isValid = false;
                        return;
                    }

                    if (cupo_maximo < cupo_reservado) {
                        alert(`El cupo máximo para el horario ${horario} no puede ser menor a los cupos ya reservados (${cupo_reservado}).`);
                        isValid = false;
                        return;
                    }

                    salidas.push({ horario, cupo_maximo });
                });
            }

            if (!isValid) return;

            // Envío AJAX
            fetch('/dashboard/dates/update-single-day', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': window.Laravel.csrfToken
                },
                body: JSON.stringify({
                    tour_id: tour_id,
                    fecha: fecha,
                    habilitado: habilitado,
                    salidas: salidas
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    // Si hay advertencias de reservas, mostrarlas
                    if (data.warning) {
                        alert(data.warning);
                    } else if (data.warnings && data.warnings.length > 0) {
                        alert(data.warnings.join('\n'));
                    }
                    
                    closeDayModal();
                    loadCalendar(); // Refrescar el calendario
                } else {
                    alert('Error: ' + (data.error || 'No se pudo guardar la disponibilidad.'));
                }
            })
            .catch(err => {
                console.error(err);
                alert('Ocurrió un error al procesar la solicitud.');
            });
        });

        // --- DINÁMICA DE PASOS DE ITINERARIO ---
        function addItineraryRow(type, titulo = '', descripcion = '') {
            const container = document.getElementById(`${type}-itinerary-container`);
            if (!container) return;

            const row = document.createElement('div');
            row.className = 'itinerary-row flex gap-2.5 items-start p-3 rounded-xl border border-slate-900 bg-slate-900/20';
            
            row.innerHTML = `
                <div class="flex-grow flex flex-col gap-2">
                    <div class="flex gap-2">
                        <span class="step-num text-[10px] font-mono text-cyan-400 mt-2 font-bold"></span>
                        <input type="text" name="itinerario_titulos[]" placeholder="Título del paso (Ej. Punto de partida)" class="w-full h-8 rounded-lg border border-slate-800 bg-slate-950 px-2.5 text-xs text-white focus:border-cyan-500" value="${titulo}" required>
                    </div>
                    <textarea name="itinerario_descripciones[]" placeholder="Descripción detallada de lo que se realiza en este paso..." rows="2" class="w-full rounded-lg border border-slate-800 bg-slate-950 p-2 text-xs text-white focus:border-cyan-500" required>${descripcion}</textarea>
                </div>
                <div class="pt-1.5">
                    <button type="button" class="text-red-500 hover:text-red-400 text-lg font-bold px-1 transition-colors cursor-pointer" onclick="this.closest('.itinerary-row').remove(); updateStepNumbers('${type}');">
                        &times;
                    </button>
                </div>
            `;

            container.appendChild(row);
            updateStepNumbers(type);
        }

        function updateStepNumbers(type) {
            const container = document.getElementById(`${type}-itinerary-container`);
            if (!container) return;
            const rows = container.querySelectorAll('.itinerary-row');
            rows.forEach((row, index) => {
                row.querySelector('.step-num').textContent = `#${index + 1}`;
            });
        }

        // --- MANEJO DEL MODAL DE EDICIÓN DE TOUR ---
        function openEditTourModal(id) {
            const modal = document.getElementById('edit-tour-modal');
            const form = document.getElementById('edit-tour-form');
            const card = document.getElementById(`tour-card-${id}`);

            if (!card || !modal) return;

            // Leer todos los atributos de la tarjeta
            const titulo = card.getAttribute('data-titulo') || '';
            const resumen = card.getAttribute('data-resumen') || '';
            const detalle = card.getAttribute('data-detalle') || '';
            const precio = card.getAttribute('data-precio') || '';
            const duracion = card.getAttribute('data-duracion') || '';
            const ubicacion = card.getAttribute('data-ubicacion') || '';
            const pais = card.getAttribute('data-pais') || '';
            const proveedorId = card.getAttribute('data-proveedor-id') || '';
            const tags = card.getAttribute('data-tags') || '';
            const imagen = card.getAttribute('data-imagen') || '';
            const galeria = card.getAttribute('data-galeria') || '';
            const puntoEncuentro = card.getAttribute('data-punto-encuentro') || '';
            const incluye = card.getAttribute('data-incluye') || '';
            const noIncluye = card.getAttribute('data-no-incluye') || '';
            
            let itinerario = [];
            try {
                itinerario = JSON.parse(card.getAttribute('data-itinerario') || '[]');
            } catch(e) {
                console.error("Error al parsear itinerario", e);
            }

            // Población de campos estándar
            document.getElementById('edit-tour-titulo').value = titulo;
            document.getElementById('edit-tour-resumen').value = resumen;
            document.getElementById('edit-tour-detalle').value = detalle;
            document.getElementById('edit-tour-precio').value = precio;
            document.getElementById('edit-tour-duracion').value = duracion;
            document.getElementById('edit-tour-ubicacion').value = ubicacion;
            document.getElementById('edit-tour-pais').value = pais;
            document.getElementById('edit-tour-proveedor').value = proveedorId;
            document.getElementById('edit-tour-tags').value = tags;
            document.getElementById('edit-tour-imagen').value = imagen;
            document.getElementById('edit-tour-galeria').value = galeria;
            document.getElementById('edit-tour-punto-encuentro').value = puntoEncuentro;
            document.getElementById('edit-tour-incluye').value = incluye;
            document.getElementById('edit-tour-no-incluye').value = noIncluye;

            // Poblar itinerario
            const container = document.getElementById('edit-itinerary-container');
            container.innerHTML = '';
            
            if (itinerario.length > 0) {
                itinerario.forEach(paso => {
                    addItineraryRow('edit', paso.titulo, paso.descripcion);
                });
            } else {
                addItineraryRow('edit'); // uno vacío por defecto
            }

            form.action = `/dashboard/tour/${id}/update`;
            modal.classList.remove('hidden');
        }

        function closeEditTourModal() {
            document.getElementById('edit-tour-modal').classList.add('hidden');
        }

        // --- MANEJO DEL MODAL DE CREACIÓN DE TOUR ---
        function openCreateTourModal() {
            const modal = document.getElementById('create-tour-modal');
            if (modal) {
                // Limpiar itinerario
                document.getElementById('create-itinerary-container').innerHTML = '';
                addItineraryRow('create'); // Añadir un paso inicial vacío
                modal.classList.remove('hidden');
            }
        }

        function closeCreateTourModal() {
            const modal = document.getElementById('create-tour-modal');
            if (modal) modal.classList.add('hidden');
        }

        // --- BUSCADOR DEL LISTADO DE TOURS ---
        function filterTourList() {
            const searchInput = document.getElementById('tour-list-search');
            if (!searchInput) return;
            const query = searchInput.value.toLowerCase().trim();
            const cards = document.querySelectorAll('.tour-list-card');
            
            cards.forEach(card => {
                const tourName = card.querySelector('.tour-card-name').textContent.toLowerCase();
                if (tourName.includes(query)) {
                    card.classList.remove('hidden');
                } else {
                    card.classList.add('hidden');
                }
            });
        }

        // --- SELECCIONAR TOUR PARA MOSTRAR CALENDARIO ---
        function selectTourForCalendar(tourId) {
            // Sincronizar selectores ocultos
            const tourSelects = document.querySelectorAll('.calendar-tour-select');
            tourSelects.forEach(selectEl => {
                selectEl.value = tourId;
                selectEl.dispatchEvent(new Event('change'));
            });

            // Resaltar la tarjeta seleccionada
            document.querySelectorAll('.tour-list-card').forEach(card => {
                card.classList.remove('border-cyan-500', 'ring-1', 'ring-cyan-500/20', 'bg-slate-900/30');
                card.classList.add('border-slate-900', 'bg-slate-950/60');
            });

            const selectedCard = document.getElementById(`tour-card-${tourId}`);
            if (selectedCard) {
                selectedCard.classList.remove('border-slate-900', 'bg-slate-950/60');
                selectedCard.classList.add('border-cyan-500', 'ring-1', 'ring-cyan-500/20', 'bg-slate-900/30');

                // Actualizar título de calendario activo
                const tourName = selectedCard.querySelector('.tour-card-name').textContent.trim();
                const activeTitleEl = document.getElementById('calendar-active-tour-title');
                if (activeTitleEl) activeTitleEl.textContent = tourName;

                // Mostrar calendario y ocultar empty state
                const emptyState = document.getElementById('calendar-empty-state');
                if (emptyState) emptyState.classList.add('hidden');
                
                const widgetContainer = document.querySelector('#calendar-main-panel .calendar-widget-container');
                if (widgetContainer) widgetContainer.classList.remove('hidden');

                // Si es dispositivo móvil, hacer scroll suave al panel del calendario
                if (window.innerWidth < 1024) {
                    const calendarPanel = document.getElementById('calendar-main-panel');
                    if (calendarPanel) {
                        setTimeout(() => {
                            calendarPanel.scrollIntoView({ behavior: 'smooth', block: 'start' });
                        }, 100);
                    }
                }
            }
        }
        </script>

@endsection
