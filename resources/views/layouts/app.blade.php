<!DOCTYPE html>
<!--
 * @file app.blade.php
 * @description Layout base de la aplicación. Navbar con orden: Logo, Inicio, Tours, Idioma, Carrito, Auth.
 *              Sin sesión: ícono de persona → dropdown con login/registro.
 *              Con sesión: Avatar circular + "Mi cuenta" → dropdown completo.
 * @date 2026-07-21
 * @author Antigravity
-->
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full light bg-slate-50 text-slate-800 font-sans antialiased selection:bg-brand-teal selection:text-white">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Attitour - Experiencias Inolvidables')</title>
    <meta name="description" content="Reserva los mejores tours en Cancún, Riviera Maya, Isla Mujeres y Contoy. La mejor experiencia local garantizada.">

    <!-- Favicon -->
    <link rel="icon" href="/favicon.ico" type="image/x-icon">

    <!-- Vite Assets -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex flex-col min-h-screen bg-slate-50 bg-grid-pattern overflow-x-hidden">

    <!-- ====================================================================
         HEADER / NAVBAR
         Orden: Logo · Inicio · Tours · (Dashboard si admin/proveedor) · Idioma · Carrito · Auth
    ===================================================================== -->
    <header class="sticky top-0 z-50 w-full border-b border-slate-200 bg-white/90 backdrop-blur-md transition-all duration-300 shadow-xs">
        <div class="mx-auto flex max-w-7xl h-20 items-center justify-between px-4 sm:px-6 lg:px-8">

            <!-- 1. LOGO -->
            <a href="{{ route('home') }}" class="flex items-center gap-2 group shrink-0">
                <img src="/images/logo/LOGO 3.png" alt="Attitour"
                     class="h-20 w-auto object-contain scale-[1.85] origin-left transition-transform duration-250 group-hover:scale-[1.9]" />
            </a>

            <!-- 2. NAVEGACIÓN ESCRITORIO -->
            <nav class="hidden md:flex items-center gap-8 text-base font-bold text-slate-600">
                <a href="{{ route('home') }}"
                   class="relative pb-0.5 hover:text-brand-teal transition-colors
                          {{ Route::is('home') ? 'text-brand-teal after:absolute after:bottom-0 after:left-0 after:w-full after:h-0.5 after:bg-brand-teal after:rounded-full' : '' }}">
                    {{ __('navHome') }}
                </a>
                <a href="{{ route('catalog') }}"
                   class="relative pb-0.5 hover:text-brand-teal transition-colors
                          {{ Route::is('catalog') || Route::is('tours.show') ? 'text-brand-teal after:absolute after:bottom-0 after:left-0 after:w-full after:h-0.5 after:bg-brand-teal after:rounded-full' : '' }}">
                    {{ __('navTours') }}
                </a>
                @auth
                    @if(!Auth::user()->isCliente())
                        <a href="{{ route('dashboard') }}"
                           class="relative pb-0.5 hover:text-brand-teal transition-colors
                                  {{ Route::is('dashboard') ? 'text-brand-teal after:absolute after:bottom-0 after:left-0 after:w-full after:h-0.5 after:bg-brand-teal after:rounded-full' : '' }}">
                            {{ __('navDashboard') }}
                        </a>
                    @endif
                @endauth
            </nav>

            <!-- 3. ACCIONES DERECHA: Idioma · Carrito · Auth · Hamburguesa -->
            <div class="flex items-center gap-3">

                <!-- SELECTOR DE IDIOMA (oculto en móvil) -->
                @php
                    $langFlags = [
                        'es' => '🇲🇽', 'en' => '🇺🇸', 'zh' => '🇨🇳',
                    ];
                    $currentFlag = $langFlags[app()->getLocale()] ?? '🌐';
                @endphp
                <div class="relative hidden sm:block">
                    <button id="lang-btn"
                            class="flex items-center gap-1.5 px-3 py-2 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 hover:border-slate-300 text-sm font-bold uppercase tracking-wider text-slate-600 transition-all cursor-pointer shadow-xs">
                        <span class="text-base leading-none">{{ $currentFlag }}</span>
                        <span>{{ strtoupper(app()->getLocale()) }}</span>
                        <svg class="h-3 w-3 opacity-50" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <div id="lang-menu"
                         class="absolute right-0 mt-2 w-48 origin-top-right rounded-2xl border border-slate-200 bg-white p-1.5 shadow-2xl opacity-0 scale-95 pointer-events-none transition-all duration-200 z-50">
                        @foreach([
                            ['code'=>'es','label'=>'Español',    'flag'=>'🇲🇽'],
                            ['code'=>'en','label'=>'English',    'flag'=>'🇺🇸'],
                            ['code'=>'zh','label'=>'中文',       'flag'=>'🇨🇳'],
                        ] as $lang)
                            <a href="{{ route('lang.switch', $lang['code']) }}"
                               class="flex items-center justify-between px-3 py-2 rounded-xl text-sm font-semibold hover:bg-slate-50 text-slate-700 transition-colors
                                      {{ app()->getLocale() === $lang['code'] ? 'bg-brand-teal/5 text-brand-teal font-bold' : '' }}">
                                <span>{{ $lang['label'] }}</span>
                                <span class="text-base">{{ $lang['flag'] }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>

                <!-- CARRITO -->
                <a href="{{ route('cart.index') }}"
                   class="relative p-2.5 text-slate-600 hover:text-brand-teal rounded-xl hover:bg-slate-50 border border-transparent hover:border-slate-200 transition-all">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                              d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                    </svg>
                    @php $cartCount = count(session()->get('cart', [])); @endphp
                    @if($cartCount > 0)
                        <span class="absolute -top-1 -right-1 flex h-5 w-5 items-center justify-center rounded-full bg-brand-orange text-[10px] font-black text-white shadow-md animate-pulse">
                            {{ $cartCount }}
                        </span>
                    @endif
                </a>

                <!-- AUTH ================================================= -->
                @auth
                    {{-- USUARIO AUTENTICADO: Avatar circular + "Mi cuenta" + dropdown --}}
                    <div class="relative">
                        <button id="user-btn"
                                class="flex items-center gap-2 px-3 py-2 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 hover:border-slate-300 transition-all cursor-pointer shadow-xs group"
                                title="{{ Auth::user()->name }}">
                            {{-- Avatar: foto real si el usuario ya subió una, si no iniciales con color según rol --}}
                            @if(Auth::user()->foto_perfil)
                                <img src="{{ Auth::user()->foto_perfil }}" alt="{{ Auth::user()->name }}" class="h-8 w-8 shrink-0 rounded-full object-cover shadow-sm">
                            @else
                                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-sm font-black text-white shadow-sm
                                    {{ Auth::user()->isCliente() ? 'bg-emerald-500' : (Auth::user()->isAdmin() ? 'bg-brand-teal' : 'bg-brand-orange') }}">
                                    {{ Str::upper(Str::substr(Auth::user()->name, 0, 2)) }}
                                </span>
                            @endif
                            {{-- Texto "Mi cuenta" en desktop --}}
                            <span class="hidden sm:block text-sm font-bold text-slate-700 group-hover:text-brand-teal transition-colors">
                                Mi cuenta
                            </span>
                            <svg class="hidden sm:block h-3 w-3 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                        {{-- Dropdown usuario autenticado --}}
                        <div id="user-menu"
                             class="absolute right-0 mt-2 w-56 origin-top-right rounded-2xl border border-slate-200 bg-white p-1.5 shadow-2xl opacity-0 scale-95 pointer-events-none transition-all duration-200 z-50">
                            {{-- Cabecera con info del usuario --}}
                            <div class="px-3 py-3 border-b border-slate-100 mb-1 flex items-center gap-3">
                                @if(Auth::user()->foto_perfil)
                                    <img src="{{ Auth::user()->foto_perfil }}" alt="{{ Auth::user()->name }}" class="h-10 w-10 shrink-0 rounded-full object-cover">
                                @else
                                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full text-sm font-black text-white
                                        {{ Auth::user()->isCliente() ? 'bg-emerald-500' : (Auth::user()->isAdmin() ? 'bg-brand-teal' : 'bg-brand-orange') }}">
                                        {{ Str::upper(Str::substr(Auth::user()->name, 0, 2)) }}
                                    </span>
                                @endif
                                <div class="min-w-0">
                                    <p class="text-xs font-bold text-slate-800 truncate">{{ Auth::user()->name }}</p>
                                    <p class="text-[10px] text-slate-500 truncate mt-0.5">{{ Auth::user()->email }}</p>
                                    @if(Auth::user()->isCliente())
                                        <span class="mt-1 inline-block text-[9px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-md bg-emerald-50 text-emerald-700 border border-emerald-200">Cliente</span>
                                    @elseif(Auth::user()->isAdmin())
                                        <span class="mt-1 inline-block text-[9px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-md bg-brand-teal/10 text-brand-teal border border-brand-teal/20">Administrador</span>
                                    @else
                                        <span class="mt-1 inline-block text-[9px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-md bg-brand-orange/10 text-brand-orange border border-brand-orange/20">Proveedor</span>
                                    @endif
                                </div>
                            </div>
                            {{-- Links según rol --}}
                            @if(Auth::user()->isCliente())
                                <a href="{{ route('cliente.dashboard') }}"
                                   class="flex items-center gap-2 px-3 py-2 rounded-xl text-sm font-semibold hover:bg-slate-50 text-slate-700 transition-colors">
                                    <svg class="h-4 w-4 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                    </svg>
                                    Mi Cuenta
                                    @if(($mensajesNoLeidosCliente ?? 0) > 0)
                                        <span class="ml-auto px-1.5 py-0.5 rounded-full bg-rose-500 text-white text-[9px] font-black">{{ $mensajesNoLeidosCliente }}</span>
                                    @endif
                                </a>
                                <a href="{{ route('catalog') }}"
                                   class="flex items-center gap-2 px-3 py-2 rounded-xl text-sm font-semibold hover:bg-slate-50 text-slate-700 transition-colors">
                                    <svg class="h-4 w-4 text-brand-teal" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                    </svg>
                                    Explorar Tours
                                </a>
                            @else
                                <a href="{{ route('dashboard') }}"
                                   class="flex items-center gap-2 px-3 py-2 rounded-xl text-sm font-semibold hover:bg-slate-50 text-slate-700 transition-colors">
                                    <svg class="h-4 w-4 text-brand-teal" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2m0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                                    </svg>
                                    Panel de Control
                                </a>
                                <a href="{{ route('dashboard.qr.scanner') }}"
                                   class="flex items-center gap-2 px-3 py-2 rounded-xl text-sm font-semibold hover:bg-slate-50 text-slate-700 transition-colors">
                                    <svg class="h-4 w-4 text-brand-teal" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/>
                                    </svg>
                                    Lector QR
                                </a>
                                @if(Auth::user()->isAdmin())
                                    <a href="{{ route('dashboard.mensajes.index') }}"
                                       class="flex items-center gap-2 px-3 py-2 rounded-xl text-sm font-semibold hover:bg-slate-50 text-slate-700 transition-colors">
                                        <svg class="h-4 w-4 text-brand-teal" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                                        </svg>
                                        Mensajes
                                        @if(($mensajesNoLeidosAdmin ?? 0) > 0)
                                            <span class="ml-auto px-1.5 py-0.5 rounded-full bg-rose-500 text-white text-[9px] font-black">{{ $mensajesNoLeidosAdmin }}</span>
                                        @endif
                                    </a>
                                @endif
                            @endif
                            <div class="border-t border-slate-100 mt-1 pt-1">
                                <form action="{{ route('logout') }}" method="POST" class="w-full">
                                    @csrf
                                    <button type="submit"
                                            class="w-full text-left flex items-center gap-2 px-3 py-2 rounded-xl text-sm font-semibold hover:bg-rose-50 text-rose-600 cursor-pointer transition-colors">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                                        </svg>
                                        {{ __('navLogout') }}
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                @else
                    {{-- SIN SESIÓN: solo ícono de persona → dropdown con login y registro --}}
                    <div class="relative">
                        <button id="guest-btn"
                                class="flex items-center justify-center h-10 w-10 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 hover:border-brand-teal/40 text-slate-500 hover:text-brand-teal transition-all cursor-pointer shadow-xs"
                                title="Iniciar sesión">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                        </button>
                        <div id="guest-menu"
                             class="absolute right-0 mt-2 w-52 origin-top-right rounded-2xl border border-slate-200 bg-white p-2 shadow-2xl opacity-0 scale-95 pointer-events-none transition-all duration-200 z-50">
                            <p class="px-3 pt-1 pb-2 text-[10px] font-bold uppercase tracking-wider text-slate-400 border-b border-slate-100 mb-2">
                                Acceso
                            </p>
                            <a href="{{ route('login') }}"
                               class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-bold text-slate-700 hover:bg-brand-teal/5 hover:text-brand-teal transition-colors">
                                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-brand-teal/10 text-brand-teal">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
                                    </svg>
                                </span>
                                {{ __('navLogin') }}
                            </a>
                            <a href="{{ route('register') }}"
                               class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-bold text-slate-700 hover:bg-brand-orange/5 hover:text-brand-orange transition-colors">
                                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-brand-orange/10 text-brand-orange">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                                    </svg>
                                </span>
                                Crear cuenta
                            </a>
                        </div>
                    </div>
                @endauth
                <!-- ===================================================== -->

                <!-- HAMBURGUESA MÓVIL -->
                <button id="mobile-menu-btn"
                        class="md:hidden p-2 text-slate-600 hover:text-slate-900 rounded-lg hover:bg-slate-100 transition-colors">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7"/>
                    </svg>
                </button>
            </div>
        </div>

        <!-- MENÚ MÓVIL PANEL -->
        <div id="mobile-menu" class="hidden md:hidden border-b border-slate-200 bg-white px-4 py-3 flex flex-col gap-1 shadow-xs">
            <a href="{{ route('home') }}"
               class="px-3 py-2.5 rounded-xl text-sm font-bold hover:bg-slate-50 text-slate-700 {{ Route::is('home') ? 'bg-brand-teal/5 text-brand-teal' : '' }}">
                {{ __('navHome') }}
            </a>
            <a href="{{ route('catalog') }}"
               class="px-3 py-2.5 rounded-xl text-sm font-bold hover:bg-slate-50 text-slate-700 {{ Route::is('catalog') ? 'bg-brand-teal/5 text-brand-teal' : '' }}">
                {{ __('navTours') }}
            </a>
            {{-- Selector de idioma en móvil --}}
            <div class="border-t border-slate-100 mt-1 py-2">
                <p class="text-[9px] font-bold uppercase tracking-wider text-slate-400 px-1 mb-2">Idioma</p>
                <div class="grid grid-cols-3 gap-1.5">
                    @foreach([
                        ['code'=>'es','label'=>'ES','flag'=>'🇲🇽'],
                        ['code'=>'en','label'=>'EN','flag'=>'🇺🇸'],
                        ['code'=>'zh','label'=>'ZH','flag'=>'🇨🇳'],
                    ] as $lang)
                        <a href="{{ route('lang.switch', $lang['code']) }}"
                           class="flex flex-col items-center gap-0.5 py-1.5 rounded-lg text-[10px] font-bold transition-colors
                                  {{ app()->getLocale() === $lang['code'] ? 'bg-brand-teal/10 text-brand-teal ring-1 ring-brand-teal/30' : 'bg-slate-50 text-slate-600 hover:bg-slate-100' }}">
                            <span class="text-base leading-none">{{ $lang['flag'] }}</span>
                            <span>{{ $lang['label'] }}</span>
                        </a>
                    @endforeach
                </div>
            </div>
            @auth
                @if(Auth::user()->isCliente())
                    <a href="{{ route('cliente.dashboard') }}"
                       class="px-3 py-2.5 rounded-xl text-sm font-bold hover:bg-slate-50 text-slate-700 flex items-center justify-between {{ Route::is('cliente.dashboard') ? 'bg-brand-teal/5 text-brand-teal' : '' }}">
                        Mi Cuenta
                        @if(($mensajesNoLeidosCliente ?? 0) > 0)
                            <span class="px-1.5 py-0.5 rounded-full bg-rose-500 text-white text-[9px] font-black">{{ $mensajesNoLeidosCliente }}</span>
                        @endif
                    </a>
                @else
                    <a href="{{ route('dashboard') }}"
                       class="px-3 py-2.5 rounded-xl text-sm font-bold hover:bg-slate-50 text-slate-700 {{ Route::is('dashboard') ? 'bg-brand-teal/5 text-brand-teal' : '' }}">
                        {{ __('navDashboard') }}
                    </a>
                    <a href="{{ route('dashboard.qr.scanner') }}"
                       class="px-3 py-2.5 rounded-xl text-sm font-bold hover:bg-slate-50 text-slate-700">
                        Lector QR
                    </a>
                    @if(Auth::user()->isAdmin())
                        <a href="{{ route('dashboard.mensajes.index') }}"
                           class="px-3 py-2.5 rounded-xl text-sm font-bold hover:bg-slate-50 text-slate-700 flex items-center justify-between">
                            Mensajes
                            @if(($mensajesNoLeidosAdmin ?? 0) > 0)
                                <span class="px-1.5 py-0.5 rounded-full bg-rose-500 text-white text-[9px] font-black">{{ $mensajesNoLeidosAdmin }}</span>
                            @endif
                        </a>
                    @endif
                @endif
                <form action="{{ route('logout') }}" method="POST" class="w-full border-t border-slate-100 mt-1 pt-1">
                    @csrf
                    <button type="submit"
                            class="w-full text-left px-3 py-2.5 rounded-xl text-sm font-bold hover:bg-rose-50 text-rose-600 cursor-pointer">
                        {{ __('navLogout') }}
                    </button>
                </form>
            @else
                <div class="flex gap-2 border-t border-slate-100 mt-1 pt-2">
                    <a href="{{ route('login') }}"
                       class="flex-1 py-2.5 rounded-xl text-sm font-bold text-center border border-slate-200 text-slate-700 hover:bg-slate-50 transition-colors">
                        {{ __('navLogin') }}
                    </a>
                    <a href="{{ route('register') }}"
                       class="flex-1 py-2.5 rounded-xl text-sm font-bold text-center bg-brand-teal text-white hover:opacity-90 transition-colors">
                        Crear cuenta
                    </a>
                </div>
            @endauth
        </div>
    </header>

    <!-- MENSAJES FLASH DE ALERTA -->
    <div class="fixed bottom-5 right-5 z-50 flex flex-col gap-3 max-w-sm w-full pointer-events-none">
        @if(session('success'))
            <div class="flash-alert flex items-start gap-3 p-4 rounded-xl border border-emerald-200 bg-white shadow-2xl pointer-events-auto transition-all duration-300 transform translate-y-0 text-emerald-700" role="alert">
                <svg class="h-5 w-5 shrink-0 mt-0.5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <div class="text-xs font-bold">
                    {{ session('success') }}
                </div>
            </div>
        @endif

        @if(session('error') || $errors->any())
            <div class="flash-alert flex items-start gap-3 p-4 rounded-xl border border-rose-200 bg-white shadow-2xl pointer-events-auto transition-all duration-300 transform translate-y-0 text-rose-700" role="alert">
                <svg class="h-5 w-5 shrink-0 mt-0.5 text-rose-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                <div class="text-xs font-bold">
                    {{ session('error') ?: $errors->first() }}
                </div>
            </div>
        @endif
    </div>

    <!-- CONTENIDO PRINCIPAL -->
    <main class="flex-grow animate-fade-in">
        @yield('content')
    </main>

    <!-- FOOTER -->
    <footer class="mt-auto border-t border-slate-200 bg-slate-100 py-12">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8 mb-8">
                <div class="flex flex-col gap-4">
                    <img src="/images/logo/LOGO 3.png" alt="Attitour" class="h-20 w-auto object-contain self-start" />
                    <p class="text-xs text-slate-500 leading-relaxed font-medium">
                        {{ __('heroSubtitle') }}
                    </p>
                </div>
                <div>
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-700 mb-4">{{ __('navTours') }}</h3>
                    <ul class="flex flex-col gap-2 text-xs font-semibold text-slate-500">
                        <li><a href="{{ route('catalog', ['location' => 'CANCUN']) }}" class="hover:text-brand-teal transition-colors">Cancún</a></li>
                        <li><a href="{{ route('catalog', ['location' => 'RIVERA MAYA']) }}" class="hover:text-brand-teal transition-colors">Riviera Maya</a></li>
                        <li><a href="{{ route('catalog', ['location' => 'ISLA MUJERES']) }}" class="hover:text-brand-teal transition-colors">Isla Mujeres</a></li>
                        <li><a href="{{ route('catalog', ['location' => 'CONTOY']) }}" class="hover:text-brand-teal transition-colors">Isla Contoy</a></li>
                    </ul>
                </div>
                <div>
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-700 mb-4">Ayuda</h3>
                    <ul class="flex flex-col gap-2 text-xs font-semibold text-slate-500">
                        <li><a href="#" class="hover:text-brand-teal transition-colors">{{ __('faq') }}</a></li>
                        <li><a href="#" class="hover:text-brand-teal transition-colors">{{ __('aboutUs') }}</a></li>
                    </ul>
                </div>
                <div>
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-700 mb-4">Legal</h3>
                    <ul class="flex flex-col gap-2 text-xs font-semibold text-slate-500">
                        <li><a href="#" class="hover:text-brand-teal transition-colors">{{ __('terms') }}</a></li>
                        <li><a href="#" class="hover:text-brand-teal transition-colors">{{ __('privacy') }}</a></li>
                    </ul>
                </div>
            </div>

            <div class="border-t border-slate-200 pt-8 flex flex-col sm:flex-row items-center justify-between gap-4 text-[10px] text-slate-400 font-bold uppercase tracking-wider">
                <span>&copy; {{ date('Y') }} <strong>Attitour</strong>. All rights reserved.</span>
                <div class="flex gap-5 items-center">
                    {{-- Facebook --}}
                    <a href="#" class="text-slate-400 hover:text-brand-teal transition-colors" aria-label="Facebook">
                        <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                        </svg>
                    </a>
                    {{-- Instagram --}}
                    <a href="#" class="text-slate-400 hover:text-brand-orange transition-colors" aria-label="Instagram">
                        <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/>
                        </svg>
                    </a>
                    {{-- Twitter / X --}}
                    <a href="#" class="text-slate-400 hover:text-slate-700 transition-colors" aria-label="Twitter / X">
                        <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-4.714-6.231-5.401 6.231H2.744l7.737-8.835L1.254 2.25H8.08l4.253 5.622zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>
                        </svg>
                    </a>
                </div>
            </div>
        </div>
    </footer>

    <!-- INTERACTIVIDAD JS GENERAL -->
    <script>
        // Toggle Menú Móvil
        const mobileBtn = document.getElementById('mobile-menu-btn');
        const mobileMenu = document.getElementById('mobile-menu');
        if (mobileBtn && mobileMenu) {
            mobileBtn.addEventListener('click', () => {
                mobileMenu.classList.toggle('hidden');
            });
        }

        // Dropdowns (idioma, usuario autenticado, invitado)
        const langBtn   = document.getElementById('lang-btn');
        const langMenu  = document.getElementById('lang-menu');
        const userBtn   = document.getElementById('user-btn');
        const userMenu  = document.getElementById('user-menu');
        const guestBtn  = document.getElementById('guest-btn');
        const guestMenu = document.getElementById('guest-menu');

        function openDropdown(menu) {
            menu.classList.remove('opacity-0', 'scale-95', 'pointer-events-none');
            menu.classList.add('opacity-100', 'scale-100', 'pointer-events-auto');
        }

        function closeDropdown(menu) {
            if (!menu) return;
            menu.classList.add('opacity-0', 'scale-95', 'pointer-events-none');
            menu.classList.remove('opacity-100', 'scale-100', 'pointer-events-auto');
        }

        function closeAllDropdowns() {
            closeDropdown(langMenu);
            closeDropdown(userMenu);
            closeDropdown(guestMenu);
        }

        function toggleDropdown(menu) {
            const isClosed = menu.classList.contains('opacity-0');
            closeAllDropdowns();
            if (isClosed) openDropdown(menu);
        }

        if (langBtn && langMenu) {
            langBtn.addEventListener('click', (e) => { e.stopPropagation(); toggleDropdown(langMenu); });
        }
        if (userBtn && userMenu) {
            userBtn.addEventListener('click', (e) => { e.stopPropagation(); toggleDropdown(userMenu); });
        }
        if (guestBtn && guestMenu) {
            guestBtn.addEventListener('click', (e) => { e.stopPropagation(); toggleDropdown(guestMenu); });
        }

        // Cerrar dropdowns al hacer click fuera
        document.addEventListener('click', () => { closeAllDropdowns(); });

        // Auto-descartar alertas flash
        document.querySelectorAll('.flash-alert').forEach(alert => {
            setTimeout(() => {
                alert.style.opacity = '0';
                alert.style.transform = 'translateY(15px)';
                setTimeout(() => alert.remove(), 300);
            }, 3500);
        });
    </script>
</body>
</html>
