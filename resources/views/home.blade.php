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
    <section class="relative overflow-hidden pt-24 pb-20 lg:pt-32 lg:pb-28 flex items-center justify-center">
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
            <div class="mx-auto mt-10 max-w-4xl p-2 rounded-2xl border border-slate-200 bg-white shadow-xl backdrop-blur-md">
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

                    <!-- Fecha -->
                    <div class="flex flex-col items-start px-4 py-2 border-b md:border-b-0 md:border-r border-slate-200">
                        <label class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">
                            {{ __('searchDate') }}
                        </label>
                        <input type="date" name="date" class="w-full bg-transparent border-0 p-0 text-sm font-semibold text-slate-700 focus:ring-0 focus:outline-none cursor-pointer">
                    </div>

                    <!-- Precio Máximo -->
                    <div class="flex flex-col items-start px-4 py-2 border-b md:border-b-0 md:border-r border-slate-200">
                        <label class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">
                            {{ __('searchPrice') }}
                        </label>
                        <select name="price" class="w-full bg-transparent border-0 p-0 text-sm font-semibold text-slate-700 focus:ring-0 focus:outline-none cursor-pointer">
                            <option value="">Cualquier precio</option>
                            <option value="1000">Hasta $1,000 MXN</option>
                            <option value="2000">Hasta $2,000 MXN</option>
                            <option value="3000">Hasta $3,000 MXN</option>
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
    <section class="py-16 bg-slate-100/50 border-y border-slate-200/60">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <h2 class="text-2xl font-bold tracking-tight sm:text-3xl text-slate-800">Explora Destinos Increíbles</h2>
                <p class="mt-2 text-xs uppercase tracking-widest font-bold text-brand-teal">Elige tu propia aventura</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                <!-- Tarjeta Cancún -->
                <a href="{{ route('catalog', ['location' => 'CANCUN']) }}" class="group relative overflow-hidden rounded-2xl aspect-[4/5] border border-slate-200 shadow-md transition-all hover:border-slate-350 hover:shadow-lg">
                    <img src="https://images.unsplash.com/photo-1544735716-392fe2489ffa?auto=format&fit=crop&w=600&q=80" alt="Cancún" class="absolute inset-0 h-full w-full object-cover transition-transform duration-500 group-hover:scale-105">
                    <div class="absolute inset-0 bg-gradient-to-t from-slate-900/80 via-slate-900/20 to-transparent"></div>
                    <div class="absolute bottom-5 left-5 right-5">
                        <p class="text-[10px] font-bold tracking-widest text-brand-teal uppercase">México</p>
                        <h3 class="text-lg font-bold text-white mt-1">Cancún</h3>
                    </div>
                </a>

                <!-- Tarjeta Riviera Maya -->
                <a href="{{ route('catalog', ['location' => 'RIVERA MAYA']) }}" class="group relative overflow-hidden rounded-2xl aspect-[4/5] border border-slate-200 shadow-md transition-all hover:border-slate-350 hover:shadow-lg">
                    <img src="https://images.unsplash.com/photo-1504198453319-5ce911bafcde?auto=format&fit=crop&w=600&q=80" alt="Riviera Maya" class="absolute inset-0 h-full w-full object-cover transition-transform duration-500 group-hover:scale-105">
                    <div class="absolute inset-0 bg-gradient-to-t from-slate-900/80 via-slate-900/20 to-transparent"></div>
                    <div class="absolute bottom-5 left-5 right-5">
                        <p class="text-[10px] font-bold tracking-widest text-brand-teal uppercase">México</p>
                        <h3 class="text-lg font-bold text-white mt-1">Riviera Maya</h3>
                    </div>
                </a>

                <!-- Tarjeta Isla Mujeres -->
                <a href="{{ route('catalog', ['location' => 'ISLA MUJERES']) }}" class="group relative overflow-hidden rounded-2xl aspect-[4/5] border border-slate-200 shadow-md transition-all hover:border-slate-350 hover:shadow-lg">
                    <img src="https://images.unsplash.com/photo-1506973035872-a4ec16b8e8d9?auto=format&fit=crop&w=600&q=80" alt="Isla Mujeres" class="absolute inset-0 h-full w-full object-cover transition-transform duration-500 group-hover:scale-105">
                    <div class="absolute inset-0 bg-gradient-to-t from-slate-900/80 via-slate-900/20 to-transparent"></div>
                    <div class="absolute bottom-5 left-5 right-5">
                        <p class="text-[10px] font-bold tracking-widest text-brand-teal uppercase">México</p>
                        <h3 class="text-lg font-bold text-white mt-1">Isla Mujeres</h3>
                    </div>
                </a>

                <!-- Tarjeta Contoy -->
                <a href="{{ route('catalog', ['location' => 'CONTOY']) }}" class="group relative overflow-hidden rounded-2xl aspect-[4/5] border border-slate-200 shadow-md transition-all hover:border-slate-350 hover:shadow-lg">
                    <img src="https://images.unsplash.com/photo-1526392060635-9d6019884377?auto=format&fit=crop&w=600&q=80" alt="Contoy" class="absolute inset-0 h-full w-full object-cover transition-transform duration-500 group-hover:scale-105">
                    <div class="absolute inset-0 bg-gradient-to-t from-slate-900/80 via-slate-900/20 to-transparent"></div>
                    <div class="absolute bottom-5 left-5 right-5">
                        <p class="text-[10px] font-bold tracking-widest text-brand-teal uppercase">México</p>
                        <h3 class="text-lg font-bold text-white mt-1">Isla Contoy</h3>
                    </div>
                </a>
            </div>
        </div>
    </section>

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
                                <p class="text-sm font-black text-brand-teal">${{ number_format($tour->precio_base_usd) }} MXN</p>
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
    </script>
@endsection
