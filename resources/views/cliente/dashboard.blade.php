@extends('layouts.app')

<!--
 * @file dashboard.blade.php
 * @description Sección "Mi Cuenta" para el cliente final.
 *              Incluye 3 apartados interactivos: Mis reservas (con QR), Tours anteriores (historial sin QR)
 *              y Configuración (perfil, foto, contraseña, borrar cuenta).
 * @date 2026-07-21
 * @author Antigravity
-->

@section('title', 'Mi Cuenta - Attitour')

@section('content')
<div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-8 space-y-6">

    {{-- ===== ALERTAS FLASH ===== --}}
    @if(session('success'))
    <div id="flash-ok" class="flex items-center gap-3 rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-3.5 text-xs font-bold text-emerald-700 shadow-sm animate-fade-in">
        <span class="text-base">✅</span>
        <span class="flex-1">{{ session('success') }}</span>
        <button onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-800 cursor-pointer">✕</button>
    </div>
    @endif
    @if(session('error') || $errors->any())
    <div id="flash-err" class="flex items-center gap-3 rounded-2xl border border-rose-200 bg-rose-50 px-5 py-3.5 text-xs font-bold text-rose-700 shadow-sm animate-fade-in">
        <span class="text-base">⚠️</span>
        <span class="flex-1">{{ session('error') ?: $errors->first() }}</span>
        <button onclick="this.parentElement.remove()" class="text-rose-500 hover:text-rose-800 cursor-pointer">✕</button>
    </div>
    @endif

    {{-- ===== HEADER DEL CLIENTE ===== --}}
    <div class="rounded-3xl border border-slate-200 bg-white p-6 sm:p-8 shadow-sm flex flex-col sm:flex-row items-center justify-between gap-6">
        <div class="flex items-center gap-4">
            {{-- Foto de perfil o iniciales --}}
            <div class="relative group">
                @if($user->foto_perfil)
                    <img src="{{ $user->foto_perfil }}" alt="{{ $user->name }}" class="h-16 w-16 sm:h-20 sm:w-20 rounded-full object-cover border-2 border-brand-teal shadow-sm">
                @else
                    <div class="h-16 w-16 sm:h-20 sm:w-20 rounded-full bg-gradient-to-br from-brand-teal to-brand-teal-hover flex items-center justify-center text-xl sm:text-2xl font-black text-white shadow-sm border-2 border-white">
                        {{ strtoupper(substr($user->name, 0, 1)) }}{{ strtoupper(substr(explode(' ', $user->name)[1] ?? '', 0, 1)) }}
                    </div>
                @endif
            </div>

            <div>
                <span class="text-[10px] font-black uppercase tracking-widest text-brand-teal bg-brand-teal/10 px-2.5 py-1 rounded-md">{{ __('myAccountBadge') }}</span>
                <h1 class="text-2xl sm:text-3xl font-black text-slate-800 mt-1">
                    {{ __('helloGreeting', ['name' => explode(' ', $user->name)[0]]) }}
                </h1>
                <p class="text-xs font-semibold text-slate-500 mt-0.5">
                    {{ $user->email }}
                    @if($user->pais) · <span class="text-slate-700 font-bold">🇲🇽 {{ $user->pais }}</span>@endif
                </p>
            </div>
        </div>

        <a href="{{ route('catalog') }}" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-brand-teal hover:bg-brand-teal-hover text-xs font-black uppercase tracking-wider text-white shadow-md transition-all hover:scale-[1.02] cursor-pointer">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            {{ __('exploreToursBtn') }}
        </a>
    </div>

    {{-- ===== ESTRUCTURA DUAL: SIDEBAR NAVEGACIÓN + CONTENIDO ===== --}}
    @php
        $activeTab = session('active_tab', 'reservas');
        $estadoLabels = [
            'pendiente' => __('statusPending'),
            'pagada' => __('statusPaid'),
            'cancelada' => __('statusCancelled'),
        ];
    @endphp

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">

        <!-- ================================================================== -->
        <!-- COLUMNA IZQUIERDA: SIDEBAR CON PERFIL Y MENÚ DE APARTADOS          -->
        <!-- ================================================================== -->
        <div class="lg:col-span-4 flex flex-col gap-6">
            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm flex flex-col gap-6">

                <!-- Info Usuario Sidebar -->
                <div class="flex flex-col items-center text-center pb-6 border-b border-slate-100">
                    <div class="relative mb-3">
                        @if($user->foto_perfil)
                            <img src="{{ $user->foto_perfil }}" alt="{{ $user->name }}" class="h-24 w-24 rounded-full object-cover border-4 border-slate-50 shadow-md">
                        @else
                            <div class="h-24 w-24 rounded-full bg-gradient-to-br from-brand-teal to-brand-teal-hover flex items-center justify-center text-3xl font-black text-white shadow-md border-4 border-slate-50">
                                {{ strtoupper(substr($user->name, 0, 1)) }}{{ strtoupper(substr(explode(' ', $user->name)[1] ?? '', 0, 1)) }}
                            </div>
                        @endif
                    </div>
                    <h2 class="text-lg font-black text-slate-800">{{ $user->name }}</h2>
                    <p class="text-xs font-semibold text-slate-500 truncate max-w-full">{{ $user->email }}</p>
                </div>

                <!-- Botones de Navegación entre Apartados -->
                <div class="flex flex-col gap-2">
                    <button type="button"
                            onclick="switchClientTab('reservas')"
                            id="tab-btn-reservas"
                            class="client-tab-btn flex items-center justify-between w-full p-4 rounded-2xl border text-sm font-bold transition-all cursor-pointer">
                        <div class="flex items-center gap-3">
                            <span class="tab-icon text-lg">🎟️</span>
                            <span>{{ __('myBookingsNav') }}</span>
                        </div>
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-black bg-brand-teal/10 text-brand-teal">
                            {{ $reservasActivas->count() }}
                        </span>
                    </button>

                    <button type="button"
                            onclick="switchClientTab('anteriores')"
                            id="tab-btn-anteriores"
                            class="client-tab-btn flex items-center justify-between w-full p-4 rounded-2xl border text-sm font-bold transition-all cursor-pointer">
                        <div class="flex items-center gap-3">
                            <span class="tab-icon text-lg">📜</span>
                            <span>{{ __('previousToursNav') }}</span>
                        </div>
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-slate-100 text-slate-600">
                            {{ $reservasAnteriores->count() }}
                        </span>
                    </button>

                    <button type="button"
                            onclick="switchClientTab('configuracion')"
                            id="tab-btn-configuracion"
                            class="client-tab-btn flex items-center justify-between w-full p-4 rounded-2xl border text-sm font-bold transition-all cursor-pointer">
                        <div class="flex items-center gap-3">
                            <span class="tab-icon text-lg">⚙️</span>
                            <span>{{ __('settingsNav') }}</span>
                        </div>
                        <span class="text-slate-400">›</span>
                    </button>
                </div>

                <!-- Resumen rápido de cuenta -->
                <div class="pt-4 border-t border-slate-100 flex flex-col gap-2.5 text-xs text-slate-500 font-semibold">
                    <div class="flex justify-between items-center">
                        <span>{{ __('totalInvestedLabel') }}</span>
                        <span class="font-black text-brand-teal">${{ number_format($totalGastado, 2) }} USD</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span>{{ __('memberSinceLabel') }}</span>
                        <span class="font-bold text-slate-700">{{ $user->created_at->locale(app()->getLocale())->translatedFormat('M Y') }}</span>
                    </div>
                </div>

            </div>
        </div>

        <!-- ================================================================== -->
        <!-- COLUMNA DERECHA: CONTENIDO DEL APARTADO SELECCIONADO              -->
        <!-- ================================================================== -->
        <div class="lg:col-span-8 flex flex-col gap-6">

            <!-- ============================================================== -->
            <!-- APARTADO 1: MIS RESERVAS (ACTIVAS / FUTURAS CON QR)            -->
            <!-- ============================================================== -->
            <div id="tab-content-reservas" class="tab-pane hidden animate-fade-in space-y-5">
                <div class="flex items-center justify-between border-b border-slate-200 pb-4">
                    <div>
                        <h2 class="text-base font-black uppercase tracking-wider text-slate-800">
                            {{ __('activeBookingsTitle') }}
                        </h2>
                        <p class="text-xs text-slate-500 font-semibold mt-0.5">
                            {{ __('activeBookingsSubtitle') }}
                        </p>
                    </div>
                    <a href="{{ route('catalog') }}" class="text-xs font-bold text-brand-teal hover:underline flex items-center gap-1">
                        {{ __('newBookingLink') }}
                    </a>
                </div>

                @if($reservasActivas->isEmpty())
                <div class="rounded-3xl border border-dashed border-slate-300 bg-white p-12 text-center flex flex-col items-center gap-4">
                    <div class="h-16 w-16 rounded-2xl bg-brand-teal/10 flex items-center justify-center text-3xl">
                        ⛵
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-slate-700">{{ __('noActiveBookingsTitle') }}</h3>
                        <p class="text-xs text-slate-500 mt-1 max-w-sm">{{ __('noActiveBookingsBody') }}</p>
                    </div>
                    <a href="{{ route('catalog') }}" class="px-5 py-2.5 rounded-xl bg-brand-teal text-white text-xs font-bold uppercase shadow hover:bg-brand-teal-hover transition-all">
                        {{ __('exploreToursBtn') }}
                    </a>
                </div>
                @else
                <div class="space-y-4">
                    @foreach($reservasActivas as $reserva)
                        @php
                            $primerDetalle = $reserva->detalles->first();
                            $tourModel = $primerDetalle ? ($toursVistos[$primerDetalle->tour_id] ?? null) : null;
                        @endphp

                        <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm hover:shadow-md transition-all flex flex-col md:flex-row items-stretch gap-6">
                            
                            <!-- Foto miniatura del tour -->
                            <div class="h-32 md:h-auto md:w-40 rounded-2xl bg-slate-100 overflow-hidden shrink-0 relative">
                                @if($tourModel && $tourModel->imagen_destacada)
                                    <img src="{{ $tourModel->imagen_destacada }}" alt="Tour" class="w-full h-full object-cover">
                                @else
                                    <div class="w-full h-full bg-brand-teal/10 flex items-center justify-center text-3xl text-brand-teal">
                                        🏝️
                                    </div>
                                @endif
                                <span class="absolute top-2 left-2 bg-white/90 backdrop-blur-md px-2 py-0.5 rounded-md text-[10px] font-black uppercase text-brand-teal border border-slate-200">
                                    {{ $reserva->ticket_codigo }}
                                </span>
                            </div>

                            <!-- Información principal de la reserva -->
                            <div class="flex-1 flex flex-col justify-between gap-3 min-w-0">
                                <div>
                                    <div class="flex items-start justify-between gap-2">
                                        <div>
                                            <h3 class="text-sm font-black text-slate-800 leading-snug">
                                                @if($tourModel)
                                                    {{ is_array($tourModel->nombre) ? ($tourModel->nombre['es'] ?? reset($tourModel->nombre)) : $tourModel->nombre }}
                                                @else
                                                    {{ __('bookingFallbackName', ['id' => $reserva->id]) }}
                                                @endif
                                            </h3>
                                            @php
                                                $contienePrivado = $reserva->detalles->contains('es_privado', true);
                                            @endphp
                                            @if($contienePrivado)
                                                <span class="inline-flex items-center gap-1 mt-1 px-2 py-0.5 rounded bg-brand-orange/10 text-brand-orange border border-brand-orange/20 text-[9px] font-black uppercase tracking-wider">
                                                    {{ __('privateTourBadge') }}
                                                </span>
                                            @else
                                                <span class="inline-flex items-center gap-1 mt-1 px-2 py-0.5 rounded bg-brand-teal/10 text-brand-teal border border-brand-teal/20 text-[9px] font-black uppercase tracking-wider">
                                                    {{ __('sharedTourBadge') }}
                                                </span>
                                            @endif
                                        </div>
                                        <span class="px-2.5 py-1 rounded-lg text-[10px] font-black uppercase tracking-wider bg-emerald-50 text-emerald-700 border border-emerald-200 shrink-0">
                                            ✓ {{ $estadoLabels[strtolower($reserva->estado)] ?? $reserva->estado }}
                                        </span>
                                    </div>

                                    @if($primerDetalle)
                                    <div class="flex flex-wrap items-center gap-3 text-xs text-slate-500 mt-2 font-semibold">
                                        <span class="flex items-center gap-1 text-slate-700 font-bold">
                                            📅 {{ \Carbon\Carbon::parse($primerDetalle->fecha_seleccionada)->locale(app()->getLocale())->translatedFormat('d M, Y') }}
                                        </span>
                                        @if($primerDetalle->horario)
                                        <span>⏰ {{ $primerDetalle->horario }} hrs</span>
                                        @endif
                                        <span>👤 {{ $primerDetalle->cantidad_personas }} {{ $primerDetalle->cantidad_personas == 1 ? __('person') : __('people') }}</span>
                                    </div>
                                    @endif
                                </div>

                                <div class="pt-3 border-t border-slate-100 flex flex-wrap gap-4 items-center justify-between">
                                    @if($contienePrivado && $reserva->monto_pendiente_destino_usd > 0)
                                        <div>
                                            <span class="text-[9px] font-bold uppercase tracking-wider text-slate-400 block">{{ __('tripTotalLabel') }}</span>
                                            <span class="text-xs font-bold text-slate-700">${{ number_format($reserva->precio_total_usd, 2) }} USD</span>
                                        </div>
                                        <div>
                                            <span class="text-[9px] font-bold uppercase tracking-wider text-brand-orange block">{{ __('paidOnlineLabel') }}</span>
                                            <span class="text-xs font-black text-brand-teal">${{ number_format($reserva->monto_pagado_online_usd, 2) }} USD</span>
                                        </div>
                                        <div>
                                            <span class="text-[9px] font-black uppercase tracking-wider text-slate-500 block">{{ __('remainingAtDestinationLabel') }}</span>
                                            <span class="text-sm font-black text-slate-800 bg-slate-100 px-2 py-0.5 rounded">${{ number_format($reserva->monto_pendiente_destino_usd, 2) }} USD</span>
                                        </div>
                                    @else
                                        <div>
                                            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">{{ __('totalPaidLabel') }}</span>
                                            <span class="text-sm font-black text-brand-teal">${{ number_format($reserva->precio_total_usd, 2) }} USD</span>
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <!-- Código QR de la reserva para escaneo -->
                            <div class="shrink-0 flex flex-col items-center justify-center p-3 rounded-2xl bg-slate-50 border border-slate-200 text-center gap-1.5 md:w-36">
                                @if($reserva->qr_token)
                                    <img src="{{ $reserva->getQrImageUrl(130) }}"
                                         alt="QR {{ $reserva->ticket_codigo }}"
                                         class="w-24 h-24 rounded-xl bg-white p-1 border border-slate-200 shadow-xs cursor-pointer hover:scale-105 transition-transform"
                                         onclick="openQrModal('{{ $reserva->getQrImageUrl(300) }}', '{{ $reserva->ticket_codigo }}')">
                                    <button type="button"
                                            onclick="openQrModal('{{ $reserva->getQrImageUrl(300) }}', '{{ $reserva->ticket_codigo }}')"
                                            class="text-[10px] font-bold text-brand-teal hover:underline cursor-pointer">
                                        {{ __('enlargeQrBtn') }}
                                    </button>
                                @else
                                    <span class="text-[10px] text-slate-400 font-semibold">{{ __('qrNotAvailable') }}</span>
                                @endif
                                <button type="button"
                                        onclick="openChatModal({{ $reserva->id }}, '{{ $reserva->ticket_codigo }}')"
                                        class="mt-1 text-[10px] font-bold text-brand-teal hover:underline cursor-pointer flex items-center gap-1">
                                    {{ __('chatWithProviderBtn') }}
                                </button>
                            </div>

                        </div>
                    @endforeach
                </div>
                @endif
            </div>

            <!-- ============================================================== -->
            <!-- APARTADO 2: TOURS ANTERIORES (HISTORIAL SIN QR)                -->
            <!-- ============================================================== -->
            <div id="tab-content-anteriores" class="tab-pane hidden animate-fade-in space-y-5">
                <div class="border-b border-slate-200 pb-4">
                    <h2 class="text-base font-black uppercase tracking-wider text-slate-800">
                        {{ __('previousExperiencesTitle') }}
                    </h2>
                    <p class="text-xs text-slate-500 font-semibold mt-0.5">
                        {{ __('previousExperiencesSubtitle') }}
                    </p>
                </div>

                @if($reservasAnteriores->isEmpty())
                <div class="rounded-3xl border border-dashed border-slate-300 bg-white p-12 text-center flex flex-col items-center gap-3">
                    <span class="text-4xl">📜</span>
                    <h3 class="text-sm font-bold text-slate-700">{{ __('noPreviousToursTitle') }}</h3>
                    <p class="text-xs text-slate-500">{{ __('noPreviousToursBody') }}</p>
                </div>
                @else
                <div class="space-y-4">
                    @foreach($reservasAnteriores as $reserva)
                        @php
                            $primerDetalle = $reserva->detalles->first();
                            $tourModel = $primerDetalle ? ($toursVistos[$primerDetalle->tour_id] ?? null) : null;
                        @endphp

                        <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm opacity-90 hover:opacity-100 transition-opacity flex flex-col sm:flex-row items-center gap-5">
                            <!-- Foto miniatura -->
                            <div class="h-24 sm:w-32 w-full rounded-2xl bg-slate-100 overflow-hidden shrink-0">
                                @if($tourModel && $tourModel->imagen_destacada)
                                    <img src="{{ $tourModel->imagen_destacada }}" alt="Tour" class="w-full h-full object-cover grayscale opacity-80 hover:grayscale-0 transition-all">
                                @else
                                    <div class="w-full h-full bg-slate-200 flex items-center justify-center text-2xl text-slate-400">
                                        🏖️
                                    </div>
                                @endif
                            </div>

                            <!-- Info del tour -->
                            <div class="flex-1 min-w-0 w-full space-y-1">
                                <div class="flex items-center justify-between gap-2">
                                    <span class="text-[10px] font-bold text-slate-400 font-mono">CODE: {{ $reserva->ticket_codigo }}</span>
                                    <span class="px-2 py-0.5 rounded text-[9px] font-bold uppercase tracking-wider {{ strtolower($reserva->estado) === 'cancelada' ? 'bg-rose-50 text-rose-600 border border-rose-200' : 'bg-slate-100 text-slate-600' }}">
                                        {{ $estadoLabels[strtolower($reserva->estado)] ?? $reserva->estado }}
                                    </span>
                                </div>
                                <h3 class="text-sm font-bold text-slate-800 truncate">
                                    @if($tourModel)
                                        {{ is_array($tourModel->nombre) ? ($tourModel->nombre['es'] ?? reset($tourModel->nombre)) : $tourModel->nombre }}
                                    @else
                                        {{ __('bookingFallbackName', ['id' => $reserva->id]) }}
                                    @endif
                                </h3>
                                @if($primerDetalle)
                                <p class="text-xs text-slate-500 font-semibold">
                                    {{ __('takenOnLabel') }} <span class="text-slate-700 font-bold">{{ \Carbon\Carbon::parse($primerDetalle->fecha_seleccionada)->locale(app()->getLocale())->translatedFormat('d M, Y') }}</span>
                                    · {{ $primerDetalle->cantidad_personas }} {{ __('paxShort') }}
                                </p>
                                @endif
                            </div>

                            <div class="text-right shrink-0">
                                <span class="text-[10px] text-slate-400 uppercase font-bold block">{{ __('totalLabel') }}</span>
                                <span class="text-sm font-bold text-slate-700">${{ number_format($reserva->precio_total_usd, 2) }} USD</span>
                            </div>
                        </div>
                    @endforeach
                </div>
                @endif
            </div>

            <!-- ============================================================== -->
            <!-- APARTADO 3: CONFIGURACIÓN (PERFIL, FOTO, CLAVE, BORRAR CUENTA)  -->
            <!-- ============================================================== -->
            <div id="tab-content-configuracion" class="tab-pane hidden animate-fade-in space-y-6">
                <div class="border-b border-slate-200 pb-4">
                    <h2 class="text-base font-black uppercase tracking-wider text-slate-800">
                        {{ __('accountSettingsTitle') }}
                    </h2>
                    <p class="text-xs text-slate-500 font-semibold mt-0.5">
                        {{ __('accountSettingsSubtitle') }}
                    </p>
                </div>

                <div class="rounded-3xl border border-slate-200 bg-white p-6 sm:p-8 shadow-sm">
                    <form action="{{ route('cliente.perfil.update') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
                        @csrf

                        <!-- Subida / Cambio de Foto de Perfil -->
                        <div class="flex items-center gap-5 p-4 rounded-2xl bg-slate-50 border border-slate-200">
                            <div class="relative shrink-0">
                                @if($user->foto_perfil)
                                    <img id="avatar-preview" src="{{ $user->foto_perfil }}" alt="Preview" class="h-20 w-20 rounded-full object-cover border-2 border-brand-teal shadow-xs">
                                @else
                                    <div id="avatar-preview-placeholder" class="h-20 w-20 rounded-full bg-gradient-to-br from-brand-teal to-brand-teal-hover flex items-center justify-center text-2xl font-black text-white shadow-xs">
                                        {{ strtoupper(substr($user->name, 0, 1)) }}
                                    </div>
                                @endif
                            </div>
                            <div class="space-y-1.5 flex-1">
                                <label class="text-xs font-bold text-slate-800 block">{{ __('profilePhotoLabel') }}</label>
                                <p class="text-[11px] text-slate-500">{{ __('profilePhotoHelper') }}</p>
                                <label for="foto_perfil" class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-xl border border-brand-teal/40 bg-brand-teal/10 hover:bg-brand-teal hover:text-white text-xs font-bold text-brand-teal transition-colors cursor-pointer shadow-xs">
                                    {{ __('changePhotoBtn') }}
                                </label>
                                <input type="file" id="foto_perfil" name="foto_perfil" accept="image/*" class="hidden" onchange="previewAvatar(this)">
                            </div>
                        </div>

                        <!-- Campos Personales -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="space-y-1.5">
                                <label class="text-xs font-bold text-slate-700">{{ __('fullNameLabel') }}</label>
                                <input type="text" name="name" value="{{ old('name', $user->name) }}" required
                                       class="w-full h-11 rounded-xl border border-slate-300 bg-white px-3.5 text-xs text-slate-800 font-semibold focus:border-brand-teal focus:ring-1 focus:ring-brand-teal focus:outline-none transition-colors">
                            </div>

                            <div class="space-y-1.5">
                                <label class="text-xs font-bold text-slate-700">{{ __('emailLabel') }}</label>
                                <input type="email" value="{{ $user->email }}" disabled
                                       class="w-full h-11 rounded-xl border border-slate-200 bg-slate-100 px-3.5 text-xs text-slate-500 font-semibold cursor-not-allowed">
                            </div>

                            <div class="space-y-1.5">
                                <label class="text-xs font-bold text-slate-700">{{ __('phoneLabel') }}</label>
                                <input type="text" name="telefono" value="{{ old('telefono', $user->telefono) }}" placeholder="+52 999 123 4567"
                                       class="w-full h-11 rounded-xl border border-slate-300 bg-white px-3.5 text-xs text-slate-800 font-semibold focus:border-brand-teal focus:ring-1 focus:ring-brand-teal focus:outline-none transition-colors">
                            </div>

                            <div class="space-y-1.5">
                                <label class="text-xs font-bold text-slate-700">{{ __('countryLabel') }}</label>
                                <select name="pais" class="w-full h-11 rounded-xl border border-slate-300 bg-white px-3.5 text-xs text-slate-800 font-semibold focus:border-brand-teal focus:ring-1 focus:ring-brand-teal focus:outline-none transition-colors cursor-pointer">
                                    <option value="">{{ __('countrySelectPlaceholder') }}</option>
                                    @foreach([
                                        'México' => __('countryMexico'),
                                        'Estados Unidos' => __('countryUSA'),
                                        'Canadá' => __('countryCanada'),
                                        'España' => __('countrySpain'),
                                        'Colombia' => __('countryColombia'),
                                        'Argentina' => __('countryArgentina'),
                                        'Brasil' => __('countryBrazil'),
                                        'Chile' => __('countryChile'),
                                        'Otro' => __('countryOther'),
                                    ] as $valorPais => $etiquetaPais)
                                        <option value="{{ $valorPais }}" {{ old('pais', $user->pais) === $valorPais ? 'selected' : '' }}>{{ $etiquetaPais }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <!-- Cambiar Contraseña -->
                        <div class="pt-4 border-t border-slate-100 space-y-4">
                            <h3 class="text-xs font-black uppercase tracking-wider text-slate-700">
                                {{ __('changePasswordTitle') }} <span class="text-slate-400 font-normal lowercase">{{ __('optionalLabel') }}</span>
                            </h3>

                            <div class="space-y-1.5">
                                <label class="text-xs font-semibold text-slate-600">{{ __('currentPasswordLabel') }}</label>
                                <input type="password" name="current_password" placeholder="••••••••"
                                       class="w-full h-11 rounded-xl border border-slate-300 bg-white px-3.5 text-xs text-slate-800 focus:border-brand-teal focus:ring-1 focus:ring-brand-teal focus:outline-none transition-colors">
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div class="space-y-1.5">
                                    <label class="text-xs font-semibold text-slate-600">{{ __('newPasswordLabel') }}</label>
                                    <input type="password" name="password" placeholder="••••••••"
                                           class="w-full h-11 rounded-xl border border-slate-300 bg-white px-3.5 text-xs text-slate-800 focus:border-brand-teal focus:ring-1 focus:ring-brand-teal focus:outline-none transition-colors">
                                </div>
                                <div class="space-y-1.5">
                                    <label class="text-xs font-semibold text-slate-600">{{ __('confirmNewPasswordLabel') }}</label>
                                    <input type="password" name="password_confirmation" placeholder="••••••••"
                                           class="w-full h-11 rounded-xl border border-slate-300 bg-white px-3.5 text-xs text-slate-800 focus:border-brand-teal focus:ring-1 focus:ring-brand-teal focus:outline-none transition-colors">
                                </div>
                            </div>
                        </div>

                        <!-- Botón Guardar -->
                        <div class="pt-2">
                            <button type="submit"
                                    class="w-full h-12 inline-flex items-center justify-center gap-2 rounded-xl bg-brand-teal hover:bg-brand-teal-hover text-xs font-black uppercase tracking-wider text-white shadow-md transition-all hover:scale-[1.01] cursor-pointer">
                                {{ __('saveChangesBtn') }}
                            </button>
                        </div>
                    </form>
                </div>

                <!-- ZONA PELIGROSA: BORRAR CUENTA -->
                <div class="rounded-3xl border border-rose-200 bg-rose-50/50 p-6 shadow-sm flex flex-col sm:flex-row items-center justify-between gap-4">
                    <div>
                        <h4 class="text-xs font-black uppercase tracking-wider text-rose-700">{{ __('deleteAccountTitle') }}</h4>
                        <p class="text-xs text-slate-600 mt-0.5">{{ __('deleteAccountWarning') }}</p>
                    </div>
                    <button type="button"
                            onclick="openDeleteAccountModal()"
                            class="px-4 py-2.5 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-xs font-black uppercase tracking-wider shadow transition-colors cursor-pointer shrink-0">
                        {{ __('deleteAccountBtn') }}
                    </button>
                </div>

            </div>

        </div>{{-- END col derech --}}

    </div>{{-- END grid principal --}}

</div>

<!-- ====================================================================== -->
<!-- MODAL: VER QR AMPLIADO                                                  -->
<!-- ====================================================================== -->
<div id="qr-modal" class="hidden fixed inset-0 z-[60] flex items-center justify-center bg-black/40 backdrop-blur-sm animate-fade-in">
    <div class="w-full max-w-sm mx-4 p-6 rounded-3xl border border-slate-200 bg-white shadow-2xl relative text-center space-y-4">
        <button onclick="closeQrModal()" class="absolute top-4 right-4 text-slate-400 hover:text-slate-700 text-lg cursor-pointer">✕</button>
        <span class="text-xs font-black tracking-widest text-brand-teal uppercase block">{{ __('virtualTicketLabel') }}</span>
        <h3 id="qr-modal-code" class="text-sm font-bold text-slate-800"></h3>
        <div class="p-4 bg-slate-50 border border-slate-200 rounded-2xl inline-block shadow-inner">
            <img id="qr-modal-img" src="" alt="QR" class="w-56 h-56 block">
        </div>
        <p class="text-[11px] text-slate-500 font-semibold">{{ __('qrModalHelper') }}</p>
    </div>
</div>

<!-- ====================================================================== -->
<!-- MODAL: CHAT CON EL PROVEEDOR (vía admin, contacto real nunca visible)   -->
<!-- ====================================================================== -->
<div id="chat-modal" class="hidden fixed inset-0 z-[60] flex items-center justify-center bg-black/40 backdrop-blur-sm animate-fade-in">
    <div class="w-full max-w-md mx-4 p-6 rounded-3xl border border-slate-200 bg-white shadow-2xl relative flex flex-col gap-4" style="max-height: 85vh;">
        <button onclick="closeChatModal()" class="absolute top-4 right-4 text-slate-400 hover:text-slate-700 text-lg cursor-pointer">✕</button>
        <div>
            <span class="text-xs font-black tracking-widest text-brand-teal uppercase block">{{ __('chatWithOperatorLabel') }}</span>
            <h3 id="chat-modal-code" class="text-sm font-bold text-slate-800"></h3>
        </div>

        <div id="chat-proveedores" class="flex flex-col gap-1"></div>

        <div id="chat-mensajes" class="flex-1 flex flex-col gap-2 overflow-y-auto min-h-[180px] max-h-[40vh] pr-1"></div>

        <div class="flex gap-2 border-t border-slate-100 pt-3">
            <input type="text" id="chat-input-cuerpo" placeholder="{{ __('chatInputPlaceholder') }}"
                   class="flex-1 h-10 rounded-xl border border-slate-200 bg-slate-50 px-3 text-xs text-slate-800 placeholder-slate-400 focus:border-brand-teal focus:bg-white focus:outline-none transition-colors">
            <button onclick="enviarMensajeChat()"
                    class="px-4 h-10 rounded-xl bg-brand-teal text-white text-xs font-bold hover:bg-brand-teal-hover cursor-pointer transition-all">
                {{ __('sendBtn') }}
            </button>
        </div>
    </div>
</div>

<!-- ====================================================================== -->
<!-- MODAL: CONFIRMAR BORRADO DE CUENTA                                      -->
<!-- ====================================================================== -->
<div id="delete-account-modal" class="hidden fixed inset-0 z-[60] flex items-center justify-center bg-black/40 backdrop-blur-sm animate-fade-in">
    <div class="w-full max-w-md mx-4 p-6 rounded-3xl border border-rose-200 bg-white shadow-2xl space-y-4">
        <div class="flex items-center gap-3 text-rose-600">
            <span class="text-2xl">⚠️</span>
            <h3 class="text-sm font-black uppercase tracking-wider text-rose-700">{{ __('deleteAccountModalTitle') }}</h3>
        </div>
        <p class="text-xs text-slate-600 leading-relaxed">
            {!! __('deleteAccountConfirmText', ['word' => '<strong>' . __('deleteConfirmWord') . '</strong>']) !!}
        </p>
        <form action="{{ route('cliente.cuenta.delete') }}" method="POST" class="space-y-4">
            @csrf
            <input type="text" name="confirm_delete" placeholder="{{ __('typeWordPlaceholder', ['word' => __('deleteConfirmWord')]) }}" required
                   class="w-full h-11 rounded-xl border border-rose-300 bg-rose-50/50 px-3.5 text-xs text-rose-900 font-bold focus:outline-none focus:border-rose-600">
            <div class="flex gap-3">
                <button type="button" onclick="closeDeleteAccountModal()" class="flex-1 h-10 rounded-xl border border-slate-200 hover:bg-slate-100 text-xs font-bold text-slate-600">
                    {{ __('cancelBtn') }}
                </button>
                <button type="submit" class="flex-1 h-10 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-xs font-black uppercase shadow">
                    {{ __('confirmDeleteBtn') }}
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ====================================================================== -->
<!-- JAVASCRIPT NAVEGACIÓN TAB DE APARTADOS Y PREVIEW                       -->
<!-- ====================================================================== -->
<script>
let currentTab = "{{ $activeTab }}";
const i18nNoChatMessages = @json(__('noChatMessages'));

function switchClientTab(tabName) {
    currentTab = tabName;
    
    // Ocultar todos los panes
    document.querySelectorAll('.tab-pane').forEach(el => el.classList.add('hidden'));
    
    // Resetear estilos de todos los botones de tab
    document.querySelectorAll('.client-tab-btn').forEach(btn => {
        btn.classList.remove('border-brand-teal', 'bg-brand-teal/5', 'text-brand-teal', 'shadow-xs');
        btn.classList.add('border-slate-200', 'bg-white', 'text-slate-700', 'hover:bg-slate-50');
    });

    // Mostrar el pane seleccionado
    const selectedPane = document.getElementById(`tab-content-${tabName}`);
    if (selectedPane) selectedPane.classList.remove('hidden');

    // Resaltar el botón seleccionado
    const selectedBtn = document.getElementById(`tab-btn-${tabName}`);
    if (selectedBtn) {
        selectedBtn.classList.remove('border-slate-200', 'bg-white', 'text-slate-700', 'hover:bg-slate-50');
        selectedBtn.classList.add('border-brand-teal', 'bg-brand-teal/5', 'text-brand-teal', 'shadow-xs');
    }
}

function openQrModal(imgUrl, code) {
    document.getElementById('qr-modal-img').src = imgUrl;
    document.getElementById('qr-modal-code').textContent = `TICKET: ${code}`;
    document.getElementById('qr-modal').classList.remove('hidden');
}

function closeQrModal() {
    document.getElementById('qr-modal').classList.add('hidden');
}

let chatReservaId = null;

function openChatModal(reservaId, code) {
    chatReservaId = reservaId;
    document.getElementById('chat-modal-code').textContent = code;
    document.getElementById('chat-modal').classList.remove('hidden');
    cargarMensajesChat();
}

function closeChatModal() {
    document.getElementById('chat-modal').classList.add('hidden');
    chatReservaId = null;
}

function cargarMensajesChat() {
    if (!chatReservaId) return;

    fetch(`/mi-cuenta/mensajes/${chatReservaId}`, {
        headers: { 'Accept': 'application/json' },
    })
    .then(res => res.json())
    .then(data => {
        if (!data.success) return;

        const proveedoresEl = document.getElementById('chat-proveedores');
        proveedoresEl.innerHTML = data.proveedores.map(p => `
            <span class="text-[10px] font-semibold text-slate-500">${p.tour_nombre}: ${p.contacto_visible}</span>
        `).join('');

        const mensajesEl = document.getElementById('chat-mensajes');
        if (data.mensajes.length === 0) {
            mensajesEl.innerHTML = `<p class="text-[11px] text-slate-400 font-semibold text-center py-6">${i18nNoChatMessages}</p>`;
        } else {
            mensajesEl.innerHTML = data.mensajes.map(m => {
                const esCliente = m.remitente_tipo === 'cliente';
                return `
                    <div class="flex ${esCliente ? 'justify-end' : 'justify-start'}">
                        <div class="max-w-[80%] px-3 py-2 rounded-xl text-xs font-semibold ${esCliente ? 'bg-brand-teal/10 text-brand-teal' : 'bg-slate-100 text-slate-800'}">
                            <p>${m.cuerpo}</p>
                            <span class="block text-[9px] mt-1 opacity-60">${m.created_at}</span>
                        </div>
                    </div>
                `;
            }).join('');
        }
        mensajesEl.scrollTop = mensajesEl.scrollHeight;

        fetch(`/mi-cuenta/mensajes/${chatReservaId}/marcar-leido`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
            },
        });
    });
}

function enviarMensajeChat() {
    const input = document.getElementById('chat-input-cuerpo');
    const cuerpo = input.value.trim();
    if (!chatReservaId || !cuerpo) return;

    fetch(`/mi-cuenta/mensajes/${chatReservaId}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json',
        },
        body: JSON.stringify({ cuerpo: cuerpo }),
    })
    .then(res => res.json())
    .then(data => {
        if (!data.success) return;
        input.value = '';
        cargarMensajesChat();
    });
}

function openDeleteAccountModal() {
    document.getElementById('delete-account-modal').classList.remove('hidden');
}

function closeDeleteAccountModal() {
    document.getElementById('delete-account-modal').classList.add('hidden');
}

function previewAvatar(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const preview = document.getElementById('avatar-preview');
            const placeholder = document.getElementById('avatar-preview-placeholder');
            if (preview) {
                preview.src = e.target.result;
            } else if (placeholder) {
                const newImg = document.createElement('img');
                newImg.id = 'avatar-preview';
                newImg.src = e.target.result;
                newImg.className = 'h-20 w-20 rounded-full object-cover border-2 border-brand-teal shadow-xs';
                placeholder.parentNode.replaceChild(newImg, placeholder);
            }
        };
        reader.readAsDataURL(input.files[0]);
    }
}

// Inicializar la pestaña al cargar el documento
document.addEventListener('DOMContentLoaded', () => {
    switchClientTab(currentTab || 'reservas');
});
</script>
@endsection
