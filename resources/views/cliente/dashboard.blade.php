@extends('layouts.app')

<!--
 * @file dashboard.blade.php
 * @description Dashboard personal del cliente final. Muestra historial de reservas,
 *              estadísticas de viajes, y perfil editable con diseño glassmorphism premium.
 * @date 2026-06-10
 * @author Antigravity
-->

@section('title', 'Mi Cuenta - Atti Tours')

@section('content')
<div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8 py-10 space-y-8">

    {{-- ===== BANNER DE NOTIFICACIONES ===== --}}
    @if(session('success'))
    <div id="flash-ok" class="flex items-center gap-3 rounded-xl border border-emerald-500/30 bg-emerald-500/10 px-5 py-3 text-sm text-emerald-300 shadow-lg backdrop-blur-md">
        <svg class="h-5 w-5 shrink-0 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <span class="font-medium">{{ session('success') }}</span>
        <button onclick="this.parentElement.remove()" class="ml-auto text-emerald-400 hover:text-white cursor-pointer">✕</button>
    </div>
    @endif
    @if(session('error'))
    <div id="flash-err" class="flex items-center gap-3 rounded-xl border border-rose-500/30 bg-rose-500/10 px-5 py-3 text-sm text-rose-300 shadow-lg backdrop-blur-md">
        <svg class="h-5 w-5 shrink-0 text-rose-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <span class="font-medium">{{ session('error') }}</span>
        <button onclick="this.parentElement.remove()" class="ml-auto text-rose-400 hover:text-white cursor-pointer">✕</button>
    </div>
    @endif

    {{-- ===== ENCABEZADO DE BIENVENIDA ===== --}}
    <div class="relative overflow-hidden rounded-3xl border border-slate-800 bg-gradient-to-br from-slate-900 via-slate-950 to-indigo-950/40 p-8 shadow-2xl">
        {{-- Glow decorativo --}}
        <div class="absolute -top-20 -right-20 h-64 w-64 rounded-full bg-cyan-500/10 blur-3xl pointer-events-none"></div>
        <div class="absolute -bottom-10 -left-10 h-48 w-48 rounded-full bg-indigo-500/10 blur-2xl pointer-events-none"></div>

        <div class="relative flex flex-col sm:flex-row items-start sm:items-center gap-5">
            {{-- Avatar --}}
            <div class="h-16 w-16 rounded-2xl bg-gradient-to-br from-cyan-500 to-indigo-600 flex items-center justify-center text-2xl font-black text-white shadow-lg flex-shrink-0">
                {{ strtoupper(substr($user->name, 0, 1)) }}{{ strtoupper(substr(explode(' ', $user->name)[1] ?? 'X', 0, 1)) }}
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-xs font-bold uppercase tracking-widest text-cyan-400/70 mb-0.5">Mi Cuenta</p>
                <h1 class="text-2xl sm:text-3xl font-black text-white truncate">
                    ¡Hola, {{ explode(' ', $user->name)[0] }}! 👋
                </h1>
                <p class="text-sm text-slate-400 mt-1">
                    {{ $user->email }}
                    @if($user->pais)
                        · <span class="text-slate-300">{{ $user->pais }}</span>
                    @endif
                </p>
            </div>
            <a href="{{ route('catalog') }}" class="hidden sm:inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-gradient-to-r from-cyan-500 to-indigo-600 text-xs font-black uppercase tracking-wider text-white shadow-lg hover:shadow-cyan-900/40 hover:scale-[1.02] transition-all cursor-pointer">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                Explorar Tours
            </a>
        </div>
    </div>

    {{-- ===== TARJETAS DE ESTADÍSTICAS ===== --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        {{-- Total Reservas --}}
        <div class="group relative overflow-hidden rounded-2xl border border-slate-800 bg-slate-950/60 p-5 hover:border-cyan-500/40 transition-all duration-300 hover:-translate-y-0.5 shadow-lg">
            <div class="absolute inset-0 bg-gradient-to-br from-cyan-500/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
            <p class="text-[10px] font-bold uppercase tracking-widest text-slate-500 mb-3">Total Reservas</p>
            <p class="text-4xl font-black text-white">{{ $totalReservas }}</p>
            <p class="text-xs text-slate-500 mt-1">tours reservados</p>
            <div class="absolute bottom-4 right-4 h-10 w-10 rounded-xl bg-cyan-500/10 flex items-center justify-center">
                <svg class="h-5 w-5 text-cyan-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            </div>
        </div>

        {{-- Confirmadas --}}
        <div class="group relative overflow-hidden rounded-2xl border border-slate-800 bg-slate-950/60 p-5 hover:border-emerald-500/40 transition-all duration-300 hover:-translate-y-0.5 shadow-lg">
            <div class="absolute inset-0 bg-gradient-to-br from-emerald-500/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
            <p class="text-[10px] font-bold uppercase tracking-widest text-slate-500 mb-3">Confirmadas</p>
            <p class="text-4xl font-black text-white">{{ $reservasPagadas }}</p>
            <p class="text-xs text-slate-500 mt-1">pagadas exitosamente</p>
            <div class="absolute bottom-4 right-4 h-10 w-10 rounded-xl bg-emerald-500/10 flex items-center justify-center">
                <svg class="h-5 w-5 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
        </div>

        {{-- Total Gastado --}}
        <div class="group relative overflow-hidden rounded-2xl border border-slate-800 bg-slate-950/60 p-5 hover:border-indigo-500/40 transition-all duration-300 hover:-translate-y-0.5 shadow-lg">
            <div class="absolute inset-0 bg-gradient-to-br from-indigo-500/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
            <p class="text-[10px] font-bold uppercase tracking-widest text-slate-500 mb-3">Total Invertido</p>
            <p class="text-3xl font-black text-white">${{ number_format($totalGastado, 0) }}</p>
            <p class="text-xs text-slate-500 mt-1">USD en experiencias</p>
            <div class="absolute bottom-4 right-4 h-10 w-10 rounded-xl bg-indigo-500/10 flex items-center justify-center">
                <svg class="h-5 w-5 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
        </div>

        {{-- Próximo Tour --}}
        <div class="group relative overflow-hidden rounded-2xl border border-slate-800 bg-slate-950/60 p-5 hover:border-amber-500/40 transition-all duration-300 hover:-translate-y-0.5 shadow-lg">
            <div class="absolute inset-0 bg-gradient-to-br from-amber-500/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
            <p class="text-[10px] font-bold uppercase tracking-widest text-slate-500 mb-3">Próximo Tour</p>
            @if($proximaReserva)
                @php $proxFecha = $proximaReserva->detalles->where('fecha_seleccionada', '>=', now()->toDateString())->first(); @endphp
                @if($proxFecha)
                    <p class="text-2xl font-black text-white">{{ \Carbon\Carbon::parse($proxFecha->fecha_seleccionada)->format('d') }}</p>
                    <p class="text-sm font-bold text-amber-400">{{ \Carbon\Carbon::parse($proxFecha->fecha_seleccionada)->locale('es')->translatedFormat('M Y') }}</p>
                @else
                    <p class="text-2xl font-black text-slate-600">—</p>
                @endif
            @else
                <p class="text-sm text-slate-600">Sin próximos tours</p>
            @endif
            <div class="absolute bottom-4 right-4 h-10 w-10 rounded-xl bg-amber-500/10 flex items-center justify-center">
                <svg class="h-5 w-5 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
        </div>
    </div>

    {{-- ===== CONTENIDO PRINCIPAL (RESERVAS + PERFIL) ===== --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- ======= HISTORIAL DE RESERVAS (2/3) ======= --}}
        <div class="lg:col-span-2 space-y-4">
            <div class="flex items-center justify-between">
                <h2 class="text-base font-black uppercase tracking-widest text-white flex items-center gap-2">
                    <span class="h-6 w-6 rounded-lg bg-cyan-500/20 flex items-center justify-center">
                        <svg class="h-3.5 w-3.5 text-cyan-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                    </span>
                    Mis Reservas
                </h2>
                <a href="{{ route('catalog') }}" class="text-xs text-cyan-400 hover:text-cyan-300 font-semibold transition-colors flex items-center gap-1">
                    + Reservar otro
                    <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </a>
            </div>

            @if($reservas->isEmpty())
            {{-- Empty state --}}
            <div class="rounded-3xl border border-dashed border-slate-800 bg-slate-950/30 p-12 text-center">
                <div class="mx-auto mb-4 h-16 w-16 rounded-2xl bg-slate-900 flex items-center justify-center">
                    <svg class="h-8 w-8 text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                </div>
                <h3 class="text-sm font-bold text-slate-400 mb-2">Aún no tienes reservas</h3>
                <p class="text-xs text-slate-600 mb-5">Explora nuestra colección de tours y vive experiencias únicas en el Caribe Mexicano.</p>
                <a href="{{ route('catalog') }}" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-gradient-to-r from-cyan-500 to-indigo-600 text-xs font-bold uppercase text-white shadow-lg hover:scale-[1.02] transition-all">
                    Explorar Tours ⛵
                </a>
            </div>
            @else
            <div class="space-y-3">
                @foreach($reservas as $reserva)
                @php
                    $estadoConfig = [
                        'Pagada'     => ['bg' => 'bg-emerald-500/15 text-emerald-400 border-emerald-500/25', 'icon' => '✓', 'label' => 'Confirmada'],
                        'Pendiente'  => ['bg' => 'bg-amber-500/15 text-amber-400 border-amber-500/25', 'icon' => '⏳', 'label' => 'Pendiente'],
                        'Cancelada'  => ['bg' => 'bg-rose-500/15 text-rose-400 border-rose-500/25', 'icon' => '✕', 'label' => 'Cancelada'],
                    ];
                    $cfg = $estadoConfig[$reserva->estado] ?? ['bg' => 'bg-slate-700/20 text-slate-400 border-slate-700/30', 'icon' => '?', 'label' => $reserva->estado];
                @endphp

                <div class="group rounded-2xl border border-slate-800 bg-slate-950/50 hover:border-slate-700 hover:bg-slate-900/50 transition-all duration-200 overflow-hidden shadow-lg">
                    {{-- Header de la reserva --}}
                    <div class="flex items-center gap-4 p-4 pb-3">
                        {{-- Imagen / Icono del tour --}}
                        @php $primerDetalle = $reserva->detalles->first(); @endphp
                        @if($primerDetalle && isset($toursVistos[$primerDetalle->tour_id]) && $toursVistos[$primerDetalle->tour_id]->imagen_destacada)
                            <div class="h-14 w-14 rounded-xl overflow-hidden flex-shrink-0 bg-slate-800">
                                <img src="{{ $toursVistos[$primerDetalle->tour_id]->imagen_destacada }}"
                                     alt="Tour"
                                     class="h-full w-full object-cover">
                            </div>
                        @else
                            <div class="h-14 w-14 rounded-xl bg-gradient-to-br from-cyan-500/20 to-indigo-600/20 border border-cyan-500/20 flex items-center justify-center flex-shrink-0 text-2xl">
                                ⛵
                            </div>
                        @endif

                        <div class="flex-1 min-w-0">
                            <div class="flex items-start justify-between gap-2 flex-wrap">
                                <div>
                                    @if($primerDetalle && isset($toursVistos[$primerDetalle->tour_id]))
                                        @php $tourTitulo = $toursVistos[$primerDetalle->tour_id]->titulo; @endphp
                                        <h3 class="text-sm font-bold text-white leading-tight truncate max-w-xs">
                                            {{ is_array($tourTitulo) ? ($tourTitulo['es'] ?? $tourTitulo[array_key_first($tourTitulo)]) : $tourTitulo }}
                                        </h3>
                                    @else
                                        <h3 class="text-sm font-bold text-white">Reserva #{{ $reserva->id }}</h3>
                                    @endif
                                    <p class="text-[10px] text-slate-500 mt-0.5">
                                        🎫 {{ $reserva->ticket_codigo }} · {{ \Carbon\Carbon::parse($reserva->fecha_reserva)->locale('es')->translatedFormat('d M Y') }}
                                    </p>
                                </div>
                                <span class="inline-flex items-center gap-1 rounded-lg border px-2.5 py-1 text-[10px] font-bold {{ $cfg['bg'] }}">
                                    {{ $cfg['icon'] }} {{ $cfg['label'] }}
                                </span>
                            </div>
                        </div>
                    </div>

                    {{-- Detalles de los tours en la reserva --}}
                    @if($reserva->detalles->count() > 0)
                    <div class="px-4 pb-4 space-y-2 border-t border-slate-800/50 pt-3">
                        @foreach($reserva->detalles as $det)
                        <div class="flex items-center justify-between text-xs text-slate-400 py-1.5 border-b border-slate-800/30 last:border-0">
                            <div class="flex items-center gap-2">
                                <span class="h-5 w-5 rounded-md bg-slate-800 flex items-center justify-center text-[9px]">🗓</span>
                                <div>
                                    <span class="text-slate-300 font-medium">{{ \Carbon\Carbon::parse($det->fecha_seleccionada)->locale('es')->translatedFormat('d M Y') }}</span>
                                    @if($det->horario)<span class="text-slate-500"> · {{ $det->horario }}</span>@endif
                                    <span class="text-slate-500"> · {{ $det->cantidad_personas }} {{ $det->cantidad_personas == 1 ? 'persona' : 'personas' }}</span>
                                </div>
                            </div>
                            <span class="font-bold text-white">${{ number_format($det->precio_unitario_usd * $det->cantidad_personas, 2) }}</span>
                        </div>
                        @endforeach

                        {{-- Total --}}
                        <div class="flex items-center justify-between pt-1">
                            <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Total</span>
                            <span class="text-base font-black text-cyan-400">${{ number_format($reserva->precio_total_usd, 2) }} <span class="text-xs font-normal text-slate-500">USD</span></span>
                        </div>
                    </div>
                    @endif
                </div>
                @endforeach
            </div>
            @endif
        </div>

        {{-- ======= PERFIL EDITABLE (1/3) ======= --}}
        <div class="space-y-5">
            <h2 class="text-base font-black uppercase tracking-widest text-white flex items-center gap-2">
                <span class="h-6 w-6 rounded-lg bg-indigo-500/20 flex items-center justify-center">
                    <svg class="h-3.5 w-3.5 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                </span>
                Mi Perfil
            </h2>

            <div class="rounded-3xl border border-slate-800 bg-slate-950/60 p-6 shadow-xl">
                <form action="{{ route('cliente.perfil.update') }}" method="POST" class="flex flex-col gap-4">
                    @csrf

                    <div class="flex flex-col gap-1.5">
                        <label class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Nombre Completo</label>
                        <input type="text" name="name" value="{{ old('name', $user->name) }}" required
                            class="w-full h-9 rounded-xl border border-slate-800 bg-slate-900/60 px-3 text-sm text-white placeholder-slate-600 focus:border-indigo-500 focus:ring-0 focus:outline-none transition-colors {{ $errors->has('name') ? 'border-rose-500' : '' }}">
                        @error('name')<p class="text-[10px] text-rose-400">{{ $message }}</p>@enderror
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Correo Electrónico</label>
                        <input type="email" value="{{ $user->email }}" disabled
                            class="w-full h-9 rounded-xl border border-slate-800 bg-slate-900/30 px-3 text-sm text-slate-500 cursor-not-allowed">
                        <p class="text-[10px] text-slate-600">El correo no puede modificarse</p>
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Teléfono</label>
                        <input type="text" name="telefono" value="{{ old('telefono', $user->telefono) }}"
                            placeholder="+52 999 123 4567"
                            class="w-full h-9 rounded-xl border border-slate-800 bg-slate-900/60 px-3 text-sm text-white placeholder-slate-600 focus:border-indigo-500 focus:ring-0 focus:outline-none transition-colors">
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label class="text-[10px] font-bold uppercase tracking-wider text-slate-500">País</label>
                        <select name="pais" class="w-full h-9 rounded-xl border border-slate-800 bg-slate-900/60 px-3 text-sm text-white focus:border-indigo-500 focus:ring-0 focus:outline-none transition-colors cursor-pointer">
                            <option value="" class="bg-slate-900">Seleccionar...</option>
                            @foreach(['México','Estados Unidos','Canadá','España','Colombia','Argentina','Brasil','Chile','Otro'] as $pais)
                            <option value="{{ $pais }}" class="bg-slate-900" {{ old('pais', $user->pais) === $pais ? 'selected' : '' }}>{{ $pais }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="border-t border-slate-800 pt-4 space-y-3">
                        <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Cambiar Contraseña <span class="text-slate-600 font-normal">(opcional)</span></p>

                        <div class="flex flex-col gap-1.5">
                            <label class="text-[10px] text-slate-600">Contraseña Actual</label>
                            <input type="password" name="current_password" placeholder="••••••••"
                                class="w-full h-9 rounded-xl border border-slate-800 bg-slate-900/60 px-3 text-sm text-white placeholder-slate-600 focus:border-indigo-500 focus:ring-0 focus:outline-none transition-colors {{ $errors->has('current_password') ? 'border-rose-500' : '' }}">
                            @error('current_password')<p class="text-[10px] text-rose-400">{{ $message }}</p>@enderror
                        </div>

                        <div class="grid grid-cols-2 gap-2">
                            <div class="flex flex-col gap-1.5">
                                <label class="text-[10px] text-slate-600">Nueva</label>
                                <input type="password" name="password" placeholder="••••••••"
                                    class="w-full h-9 rounded-xl border border-slate-800 bg-slate-900/60 px-3 text-sm text-white placeholder-slate-600 focus:border-indigo-500 focus:ring-0 focus:outline-none transition-colors {{ $errors->has('password') ? 'border-rose-500' : '' }}">
                            </div>
                            <div class="flex flex-col gap-1.5">
                                <label class="text-[10px] text-slate-600">Confirmar</label>
                                <input type="password" name="password_confirmation" placeholder="••••••••"
                                    class="w-full h-9 rounded-xl border border-slate-800 bg-slate-900/60 px-3 text-sm text-white placeholder-slate-600 focus:border-indigo-500 focus:ring-0 focus:outline-none transition-colors">
                            </div>
                        </div>
                        @error('password')<p class="text-[10px] text-rose-400">{{ $message }}</p>@enderror
                    </div>

                    <button type="submit"
                        class="w-full h-10 inline-flex items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-indigo-500 to-purple-600 hover:from-indigo-400 hover:to-purple-500 text-xs font-bold uppercase tracking-wider text-white shadow-lg cursor-pointer transition-all hover:scale-[1.01]">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Guardar Cambios
                    </button>
                </form>
            </div>

            {{-- Card de Cuenta --}}
            <div class="rounded-3xl border border-slate-800 bg-slate-950/40 p-5 shadow-lg space-y-4">
                <h3 class="text-[10px] font-black uppercase tracking-widest text-slate-500">Mi cuenta</h3>
                <div class="space-y-2 text-xs text-slate-400">
                    <div class="flex justify-between items-center py-1.5 border-b border-slate-800/50">
                        <span class="text-slate-500">Tipo de cuenta</span>
                        <span class="font-bold text-white px-2 py-0.5 rounded-md bg-cyan-500/10 text-cyan-400 border border-cyan-500/20 text-[10px]">Cliente</span>
                    </div>
                    <div class="flex justify-between items-center py-1.5 border-b border-slate-800/50">
                        <span class="text-slate-500">Miembro desde</span>
                        <span class="font-medium text-white">{{ $user->created_at->locale('es')->translatedFormat('M Y') }}</span>
                    </div>
                    <div class="flex justify-between items-center py-1.5">
                        <span class="text-slate-500">Reservas pagadas</span>
                        <span class="font-bold text-emerald-400">{{ $reservasPagadas }}</span>
                    </div>
                </div>

                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="w-full h-9 inline-flex items-center justify-center gap-2 rounded-xl border border-slate-800 bg-slate-900/60 text-xs font-semibold text-slate-400 hover:text-rose-400 hover:border-rose-500/30 hover:bg-rose-500/5 cursor-pointer transition-all">
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                        Cerrar Sesión
                    </button>
                </form>
            </div>
        </div>

    </div>{{-- END grid --}}

    {{-- ===== CTA EXPLORAR TOURS ===== --}}
    <div class="relative overflow-hidden rounded-3xl border border-cyan-500/20 bg-gradient-to-r from-cyan-950/40 via-indigo-950/40 to-slate-950/40 p-8 text-center shadow-2xl">
        <div class="absolute inset-0 bg-gradient-to-br from-cyan-500/5 via-transparent to-indigo-500/5 pointer-events-none"></div>
        <p class="text-xs font-bold uppercase tracking-widest text-cyan-400/70 mb-2">¿Listo para tu próxima aventura?</p>
        <h2 class="text-xl sm:text-2xl font-black text-white mb-4">Explora el Caribe Mexicano con Atti Tours</h2>
        <p class="text-sm text-slate-400 mb-6 max-w-lg mx-auto">Descubre tours únicos a Isla Mujeres, Contoy, Chichén Itzá, Cenotes y más. Experiencias diseñadas para crear recuerdos inolvidables.</p>
        <a href="{{ route('catalog') }}" class="inline-flex items-center gap-2 px-6 py-3 rounded-xl bg-gradient-to-r from-cyan-500 to-indigo-600 text-sm font-black uppercase tracking-wider text-white shadow-lg shadow-cyan-900/40 hover:shadow-cyan-500/20 hover:scale-[1.02] transition-all">
            Ver Todos los Tours
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
        </a>
    </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // Auto-dismiss flash messages
    ['flash-ok','flash-err'].forEach(id => {
        const el = document.getElementById(id);
        if (el) setTimeout(() => el.remove(), 5000);
    });
});
</script>
@endsection
