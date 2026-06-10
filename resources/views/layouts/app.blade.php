<!DOCTYPE html>
<!-- 
 * @file app.blade.php
 * @description Layout base de la aplicación con Navbar responsivo, selector de idioma, footer y soporte Tailwind v4.
 * @date 2026-06-10
 * @author Antigravity
-->
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-slate-950 text-slate-100 font-sans antialiased selection:bg-indigo-500 selection:text-white">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Atti Tours - Experiencias Inolvidables')</title>
    <meta name="description" content="Reserva los mejores tours en Cancún, Riviera Maya, Isla Mujeres y Contoy. La mejor experiencia local garantizada.">
    
    <!-- Favicon -->
    <link rel="icon" href="/favicon.ico" type="image/x-icon">

    <!-- Fonts e Inter conectividad de Vite -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex flex-col min-h-screen bg-slate-950 bg-grid-pattern">

    <!-- HEADER / NAVBAR -->
    <header class="sticky top-0 z-50 w-full border-b border-slate-800 bg-slate-950/80 backdrop-blur-md transition-all duration-300">
        <div class="mx-auto flex max-w-7xl h-16 items-center justify-between px-4 sm:px-6 lg:px-8">
            <!-- Logo -->
            <a href="{{ route('home') }}" class="flex items-center gap-2 group">
                <span class="text-2xl font-black tracking-wider bg-gradient-to-r from-cyan-400 via-indigo-400 to-purple-500 bg-clip-text text-transparent group-hover:opacity-85 transition-opacity">
                    ATTI TOURS
                </span>
            </a>

            <!-- Navegación Escritorio -->
            <nav class="hidden md:flex items-center gap-8 text-sm font-medium text-slate-300">
                <a href="{{ route('home') }}" class="hover:text-cyan-400 transition-colors {{ Route::is('home') ? 'text-cyan-400' : '' }}">
                    {{ __('navHome') }}
                </a>
                <a href="{{ route('catalog') }}" class="hover:text-cyan-400 transition-colors {{ Route::is('catalog') || Route::is('tours.show') ? 'text-cyan-400' : '' }}">
                    {{ __('navTours') }}
                </a>
                @auth
                    @if(Auth::user()->isCliente())
                        <a href="{{ route('cliente.dashboard') }}" class="hover:text-cyan-400 transition-colors {{ Route::is('cliente.dashboard') ? 'text-cyan-400' : '' }}">
                            Mi Cuenta
                        </a>
                    @else
                        <a href="{{ route('dashboard') }}" class="hover:text-cyan-400 transition-colors {{ Route::is('dashboard') ? 'text-cyan-400' : '' }}">
                            {{ __('navDashboard') }}
                        </a>
                        <a href="{{ route('dashboard.qr.scanner') }}" class="hover:text-cyan-400 transition-colors {{ Route::is('dashboard.qr.scanner') ? 'text-cyan-400' : '' }}">
                            Lector QR
                        </a>
                    @endif
                @endauth
            </nav>

            <!-- Acciones Derecha (Idioma, Carrito, Auth) -->
            <div class="flex items-center gap-4">
                
                <!-- Selector de Idioma -->
                <div class="relative">
                    <button id="lang-btn" class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-slate-800 bg-slate-900 hover:bg-slate-800 hover:border-slate-700 text-xs font-semibold uppercase tracking-wider text-slate-300 transition-all cursor-pointer">
                        <span>{{ app()->getLocale() }}</span>
                        <svg class="h-3 w-3 opacity-60 transition-transform duration-200" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                    <!-- Dropdown -->
                    <div id="lang-menu" class="absolute right-0 mt-2 w-28 origin-top-right rounded-xl border border-slate-800 bg-slate-900 p-1 shadow-2xl opacity-0 scale-95 pointer-events-none transition-all duration-200 z-50">
                        <a href="{{ route('lang.switch', 'es') }}" class="flex items-center justify-between px-3 py-2 rounded-lg text-xs font-medium hover:bg-slate-800 text-slate-200 hover:text-white {{ app()->getLocale() === 'es' ? 'bg-slate-800 text-cyan-400 font-bold' : '' }}">
                            <span>Español</span>
                            <span>🇲🇽</span>
                        </a>
                        <a href="{{ route('lang.switch', 'en') }}" class="flex items-center justify-between px-3 py-2 rounded-lg text-xs font-medium hover:bg-slate-800 text-slate-200 hover:text-white {{ app()->getLocale() === 'en' ? 'bg-slate-800 text-cyan-400 font-bold' : '' }}">
                            <span>English</span>
                            <span>🇺🇸</span>
                        </a>
                        <a href="{{ route('lang.switch', 'zh') }}" class="flex items-center justify-between px-3 py-2 rounded-lg text-xs font-medium hover:bg-slate-800 text-slate-200 hover:text-white {{ app()->getLocale() === 'zh' ? 'bg-slate-800 text-cyan-400 font-bold' : '' }}">
                            <span>中文</span>
                            <span>🇨🇳</span>
                        </a>
                    </div>
                </div>

                <!-- Botón Carrito -->
                <a href="{{ route('cart.index') }}" class="relative p-2 text-slate-300 hover:text-cyan-400 rounded-lg hover:bg-slate-900 border border-transparent hover:border-slate-800 transition-all">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                    </svg>
                    @php
                        $cart = session()->get('cart', []);
                        $cartCount = count($cart);
                    @endphp
                    @if($cartCount > 0)
                        <span class="absolute -top-1.5 -right-1.5 flex h-5 w-5 items-center justify-center rounded-full bg-gradient-to-r from-pink-500 to-rose-500 text-[10px] font-black text-white shadow-lg animate-pulse">
                            {{ $cartCount }}
                        </span>
                    @endif
                </a>

                <!-- Autenticación / Acceso -->
                @auth
                    <div class="relative">
                        <button id="user-btn" class="flex h-9 w-9 items-center justify-center rounded-full border border-slate-800 bg-slate-900 text-sm font-semibold hover:bg-slate-800 transition-colors {{ Auth::user()->isCliente() ? 'text-emerald-400' : 'text-cyan-400' }} cursor-pointer" title="{{ Auth::user()->name }}">
                            {{ Str::upper(Str::substr(Auth::user()->name, 0, 2)) }}
                        </button>
                        <!-- Dropdown usuario -->
                        <div id="user-menu" class="absolute right-0 mt-2 w-52 origin-top-right rounded-xl border border-slate-800 bg-slate-900 p-1.5 shadow-2xl opacity-0 scale-95 pointer-events-none transition-all duration-200 z-50">
                            <div class="px-3 py-2.5 border-b border-slate-800 mb-1">
                                <p class="text-xs font-bold text-white truncate">{{ Auth::user()->name }}</p>
                                <p class="text-[10px] text-slate-500 truncate mt-0.5">{{ Auth::user()->email }}</p>
                                @if(Auth::user()->isCliente())
                                    <span class="mt-1.5 inline-block text-[9px] font-black uppercase tracking-wider px-2 py-0.5 rounded-md bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">Cliente</span>
                                @elseif(Auth::user()->isAdmin())
                                    <span class="mt-1.5 inline-block text-[9px] font-black uppercase tracking-wider px-2 py-0.5 rounded-md bg-cyan-500/10 text-cyan-400 border border-cyan-500/20">Administrador</span>
                                @else
                                    <span class="mt-1.5 inline-block text-[9px] font-black uppercase tracking-wider px-2 py-0.5 rounded-md bg-indigo-500/10 text-indigo-400 border border-indigo-500/20">Proveedor</span>
                                @endif
                            </div>
                            @if(Auth::user()->isCliente())
                                <a href="{{ route('cliente.dashboard') }}" class="flex items-center gap-2 px-3 py-2 rounded-lg text-xs font-medium hover:bg-slate-800 text-slate-200 transition-colors">
                                    <svg class="h-3.5 w-3.5 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                    Mi Cuenta
                                </a>
                                <a href="{{ route('catalog') }}" class="flex items-center gap-2 px-3 py-2 rounded-lg text-xs font-medium hover:bg-slate-800 text-slate-200 transition-colors">
                                    <svg class="h-3.5 w-3.5 text-cyan-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                                    Explorar Tours
                                </a>
                            @else
                                <a href="{{ route('dashboard') }}" class="flex items-center gap-2 px-3 py-2 rounded-lg text-xs font-medium hover:bg-slate-800 text-slate-200 transition-colors">
                                    <svg class="h-3.5 w-3.5 text-cyan-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                                    Panel de Control
                                </a>
                                <a href="{{ route('dashboard.qr.scanner') }}" class="flex items-center gap-2 px-3 py-2 rounded-lg text-xs font-medium hover:bg-slate-800 text-slate-200 transition-colors">
                                    <svg class="h-3.5 w-3.5 text-cyan-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
                                    Lector QR
                                </a>
                            @endif
                            <div class="border-t border-slate-800 mt-1 pt-1">
                                <form action="{{ route('logout') }}" method="POST" class="w-full">
                                    @csrf
                                    <button type="submit" class="w-full text-left flex items-center gap-2 px-3 py-2 rounded-lg text-xs font-medium hover:bg-rose-900/30 text-rose-400 cursor-pointer transition-colors">
                                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                                        {{ __('navLogout') }}
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="hidden sm:flex items-center gap-2">
                        <a href="{{ route('login') }}" class="inline-flex items-center justify-center px-4 py-2 rounded-lg bg-slate-900 hover:bg-slate-800 border border-slate-800 hover:border-slate-700 text-xs font-bold text-slate-200 transition-all">
                            {{ __('navLogin') }}
                        </a>
                        <a href="{{ route('register') }}" class="inline-flex items-center justify-center px-4 py-2 rounded-lg bg-gradient-to-r from-emerald-500 to-cyan-600 hover:from-emerald-400 hover:to-cyan-500 text-xs font-bold text-white shadow-md transition-all hover:scale-[1.02]">
                            Crear Cuenta
                        </a>
                    </div>
                @endauth

                <!-- Menú Móvil Botón -->
                <button id="mobile-menu-btn" class="md:hidden p-2 text-slate-400 hover:text-white">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7" />
                    </svg>
                </button>
            </div>
        </div>

        <!-- Menú Móvil Panel -->
        <div id="mobile-menu" class="hidden md:hidden border-b border-slate-800 bg-slate-950 px-4 py-3 flex flex-col gap-2">
            <a href="{{ route('home') }}" class="px-3 py-2 rounded-lg text-sm font-medium hover:bg-slate-900 text-slate-300 {{ Route::is('home') ? 'bg-slate-900 text-cyan-400' : '' }}">
                {{ __('navHome') }}
            </a>
            <a href="{{ route('catalog') }}" class="px-3 py-2 rounded-lg text-sm font-medium hover:bg-slate-900 text-slate-300 {{ Route::is('catalog') ? 'bg-slate-900 text-cyan-400' : '' }}">
                {{ __('navTours') }}
            </a>
            @auth
                @if(Auth::user()->isCliente())
                    <a href="{{ route('cliente.dashboard') }}" class="px-3 py-2 rounded-lg text-sm font-medium hover:bg-slate-900 text-slate-300 {{ Route::is('cliente.dashboard') ? 'bg-slate-900 text-emerald-400' : '' }}">
                        Mi Cuenta
                    </a>
                @else
                    <a href="{{ route('dashboard') }}" class="px-3 py-2 rounded-lg text-sm font-medium hover:bg-slate-900 text-slate-300 {{ Route::is('dashboard') ? 'bg-slate-900 text-cyan-400' : '' }}">
                        {{ __('navDashboard') }}
                    </a>
                    <a href="{{ route('dashboard.qr.scanner') }}" class="px-3 py-2 rounded-lg text-sm font-medium hover:bg-slate-900 text-slate-300 {{ Route::is('dashboard.qr.scanner') ? 'bg-slate-900 text-cyan-400' : '' }}">
                        Lector QR
                    </a>
                @endif
                <form action="{{ route('logout') }}" method="POST" class="w-full">
                    @csrf
                    <button type="submit" class="w-full text-left px-3 py-2 rounded-lg text-sm font-medium hover:bg-red-950/45 text-red-400 cursor-pointer">
                        {{ __('navLogout') }}
                    </button>
                </form>
            @else
                <a href="{{ route('login') }}" class="px-3 py-2 rounded-lg text-sm font-medium hover:bg-slate-900 text-slate-300">
                    {{ __('navLogin') }}
                </a>
                <a href="{{ route('register') }}" class="px-3 py-2 rounded-lg text-sm font-medium bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                    Crear Cuenta
                </a>
            @endauth
        </div>
    </header>

    <!-- MENSAJES FLASH DE ALERTA -->
    <div class="fixed bottom-5 right-5 z-50 flex flex-col gap-3 max-w-sm w-full pointer-events-none">
        @if(session('success'))
            <div class="flash-alert flex items-start gap-3 p-4 rounded-xl border border-emerald-500/30 bg-slate-900/90 backdrop-blur-lg text-emerald-400 shadow-2xl pointer-events-auto transition-all duration-300 transform translate-y-0" role="alert">
                <svg class="h-5 w-5 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <div class="text-xs font-semibold">
                    {{ session('success') }}
                </div>
            </div>
        @endif

        @if(session('error') || $errors->any())
            <div class="flash-alert flex items-start gap-3 p-4 rounded-xl border border-rose-500/30 bg-slate-900/90 backdrop-blur-lg text-rose-400 shadow-2xl pointer-events-auto transition-all duration-300 transform translate-y-0" role="alert">
                <svg class="h-5 w-5 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                <div class="text-xs font-semibold">
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
    <footer class="mt-auto border-t border-slate-900 bg-slate-950/70 backdrop-blur-sm py-12">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8 mb-8">
                <div class="flex flex-col gap-4">
                    <span class="text-xl font-black tracking-wider bg-gradient-to-r from-cyan-400 to-indigo-400 bg-clip-text text-transparent">
                        ATTI TOURS
                    </span>
                    <p class="text-xs text-slate-400 leading-relaxed">
                        {{ __('heroSubtitle') }}
                    </p>
                </div>
                <div>
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-200 mb-4">{{ __('navTours') }}</h3>
                    <ul class="flex flex-col gap-2 text-xs text-slate-400">
                        <li><a href="{{ route('catalog', ['location' => 'CANCUN']) }}" class="hover:text-cyan-400 transition-colors">Cancún</a></li>
                        <li><a href="{{ route('catalog', ['location' => 'RIVERA MAYA']) }}" class="hover:text-cyan-400 transition-colors">Riviera Maya</a></li>
                        <li><a href="{{ route('catalog', ['location' => 'ISLA MUJERES']) }}" class="hover:text-cyan-400 transition-colors">Isla Mujeres</a></li>
                        <li><a href="{{ route('catalog', ['location' => 'CONTOY']) }}" class="hover:text-cyan-400 transition-colors">Isla Contoy</a></li>
                    </ul>
                </div>
                <div>
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-200 mb-4">Ayuda</h3>
                    <ul class="flex flex-col gap-2 text-xs text-slate-400">
                        <li><a href="#" class="hover:text-cyan-400 transition-colors">{{ __('faq') }}</a></li>
                        <li><a href="#" class="hover:text-cyan-400 transition-colors">{{ __('aboutUs') }}</a></li>
                    </ul>
                </div>
                <div>
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-200 mb-4">Legal</h3>
                    <ul class="flex flex-col gap-2 text-xs text-slate-400">
                        <li><a href="#" class="hover:text-cyan-400 transition-colors">{{ __('terms') }}</a></li>
                        <li><a href="#" class="hover:text-cyan-400 transition-colors">{{ __('privacy') }}</a></li>
                    </ul>
                </div>
            </div>
            
            <div class="border-t border-slate-900/60 pt-8 flex flex-col sm:flex-row items-center justify-between gap-4 text-[10px] text-slate-500 font-semibold uppercase tracking-wider">
                <span>&copy; {{ date('Y') }} Atti Tours Operadora Local. All rights reserved.</span>
                <div class="flex gap-4">
                    <a href="#" class="hover:text-cyan-400 transition-colors">Facebook</a>
                    <a href="#" class="hover:text-cyan-400 transition-colors">Instagram</a>
                    <a href="#" class="hover:text-cyan-400 transition-colors">Twitter</a>
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

        // Lógica de Dropdowns Navbar (Idioma y Usuario)
        const langBtn = document.getElementById('lang-btn');
        const langMenu = document.getElementById('lang-menu');
        const userBtn = document.getElementById('user-btn');
        const userMenu = document.getElementById('user-menu');

        function toggleDropdown(menu) {
            const isClosed = menu.classList.contains('opacity-0');
            closeAllDropdowns();
            if (isClosed) {
                menu.classList.remove('opacity-0', 'scale-95', 'pointer-events-none');
                menu.classList.add('opacity-100', 'scale-100', 'pointer-events-auto');
            }
        }

        function closeAllDropdowns() {
            if (langMenu) {
                langMenu.classList.add('opacity-0', 'scale-95', 'pointer-events-none');
                langMenu.classList.remove('opacity-100', 'scale-100', 'pointer-events-auto');
            }
            if (userMenu) {
                userMenu.classList.add('opacity-0', 'scale-95', 'pointer-events-none');
                userMenu.classList.remove('opacity-100', 'scale-100', 'pointer-events-auto');
            }
        }

        if (langBtn && langMenu) {
            langBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                toggleDropdown(langMenu);
            });
        }

        if (userBtn && userMenu) {
            userBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                toggleDropdown(userMenu);
            });
        }

        // Cerrar dropdowns al hacer click fuera
        document.addEventListener('click', () => {
            closeAllDropdowns();
        });

        // Auto-descartar alertas flash
        const alerts = document.querySelectorAll('.flash-alert');
        alerts.forEach(alert => {
            setTimeout(() => {
                alert.style.opacity = '0';
                alert.style.transform = 'translateY(15px)';
                setTimeout(() => alert.remove(), 300);
            }, 3500);
        });
    </script>
</body>
</html>
