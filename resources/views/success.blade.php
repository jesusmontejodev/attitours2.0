@extends('layouts.app')

<!-- 
 * @file success.blade.php
 * @description Vista Blade para la confirmación exitosa de reservas. Muestra el ticket virtual, la información del usuario creado o asociado, y botones de notificaciones.
 * @date 2026-07-31
 * @author Antigravity
 -->

@section('title', '¡Reserva Confirmada! - Attitour')

@section('content')
    <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8 py-10 flex flex-col items-center">
        
        <!-- ICONO DE CHECK ANIMADO -->
        <div class="h-20 w-20 rounded-full border border-emerald-200 bg-emerald-50 flex items-center justify-center text-emerald-600 mb-6 shadow-md animate-float">
            <svg class="h-10 w-10 animate-pulse" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
            </svg>
        </div>

        <h1 class="text-2xl sm:text-3xl font-black text-slate-805 text-center">
            {{ __('successTitle') }}
        </h1>
        <p class="text-xs text-slate-500 text-center mt-2 max-w-md leading-relaxed font-semibold">
            {{ __('successSub') }}
        </p>

        <!-- TICKET VIRTUAL PREMIUM (ESTILO TICKET DE ABORDO) -->
        <div class="w-full max-w-md mt-10 rounded-3xl overflow-hidden border border-slate-200 shadow-xl bg-white relative">
            
            <!-- Parte Superior del Ticket -->
            <div class="p-6 bg-slate-50 border-b-2 border-dashed border-slate-200 relative">
                <!-- Círculos de corte de ticket a los lados (coinciden con bg-slate-50 del body) -->
                <div class="absolute -bottom-3 -left-3 h-6 w-6 rounded-full bg-slate-50 border border-slate-50"></div>
                <div class="absolute -bottom-3 -right-3 h-6 w-6 rounded-full bg-slate-50 border border-slate-50"></div>

                <div class="flex items-center justify-between">
                    <span class="text-xs font-black tracking-wider text-brand-teal">ATTITOUR</span>
                    <span class="px-2.5 py-1 rounded-md bg-white text-[10px] font-bold uppercase text-slate-800 border border-slate-200 shadow-xs">
                        {{ $reserva->ticket_codigo }}
                    </span>
                </div>
                
                <div class="mt-6 flex justify-between gap-4 font-semibold">
                    <div>
                        <p class="text-[8px] font-bold text-slate-450 uppercase tracking-widest">{{ __('clientLabel') }}</p>
                        <p class="text-xs font-bold text-slate-800 mt-1">{{ $reserva->nombre_cliente }}</p>
                    </div>
                    <div class="text-right">
                        <p class="text-[8px] font-bold text-slate-450 uppercase tracking-widest">{{ __('dateLabel') }}</p>
                        <p class="text-xs font-bold text-slate-800 mt-1">{{ $reserva->fecha_reserva->format('d M Y, H:i') }}</p>
                    </div>
                </div>
            </div>

            <!-- Cuerpo Central del Ticket -->
            <div class="p-6 flex flex-col gap-5">
                
                <!-- Tours Reservados -->
                <div>
                    <p class="text-[8px] font-bold text-slate-450 uppercase tracking-widest mb-3">Detalle de Actividades</p>
                    <div class="flex flex-col gap-4">
                        @foreach($reserva->detalles as $detalle)
                            <div class="flex items-start justify-between gap-4 p-3 rounded-xl border border-slate-200 bg-slate-50 font-semibold">
                                <div>
                                    <h4 class="text-xs font-bold text-slate-800">{{ $detalle->tour->nombre }}</h4>
                                    <p class="text-[9px] text-slate-500 mt-1">
                                        Fecha: <span class="text-brand-teal font-bold">{{ $detalle->fecha_seleccionada->format('d M, Y') }}</span>
                                    </p>
                                    <p class="text-[9px] text-slate-500 mt-0.5">
                                        Pasajeros: {{ $detalle->cantidad_personas }} {{ $detalle->cantidad_personas > 1 ? __('people') : __('person') }}
                                    </p>
                                </div>
                                <span class="text-xs font-bold text-slate-650 shrink-0">${{ number_format($detalle->precio_unitario_usd * $detalle->cantidad_personas) }} USD</span>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Total pagado -->
                <div class="flex items-center justify-between border-t border-slate-200 pt-4 font-semibold">
                    <span class="text-xs font-bold text-slate-500">Importe Pagado (USD):</span>
                    <span class="text-right">
                        <span class="text-sm font-black text-brand-teal">${{ number_format($reserva->precio_total_usd, 2) }} USD</span>
                        <x-currency-note :usd="$reserva->precio_total_usd" />
                    </span>
                </div>

                <!-- Código de Barras / QR Simulador -->
                <div class="mt-4 flex flex-col items-center gap-2">
                    {{-- QR Real de Asistencia --}}
                    <div class="flex flex-col items-center gap-2 pt-1">
                        @if($reserva->qr_token)
                        <div class="p-3 bg-white rounded-2xl shadow-md border border-slate-200">
                            <img src="{{ $reserva->getQrImageUrl(160) }}"
                                 alt="QR de Asistencia — {{ $reserva->ticket_codigo }}"
                                 class="w-40 h-40 block"
                                 loading="lazy">
                        </div>
                        <span class="text-[9px] font-bold text-brand-teal/80 tracking-widest uppercase mt-1">QR de Asistencia</span>
                        <p class="text-[9px] text-slate-500 text-center max-w-[180px] font-semibold">Muestra este QR al guía al inicio del tour</p>
                        @else
                        <div class="w-40 h-40 rounded-2xl bg-slate-50 border border-slate-200 flex items-center justify-center">
                            <span class="text-slate-400 text-xs">Generando QR...</span>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- BLOQUE INFORMATIVO DE USUARIO / MI CUENTA -->
        <div class="w-full max-w-md mt-6 p-6 rounded-3xl border {{ $userCreated ? 'border-cyan-200 bg-cyan-50/40' : ($userAssociated ? 'border-amber-200 bg-amber-50/40' : 'border-slate-200 bg-slate-50/50') }} shadow-sm font-semibold">
            @if($userCreated)
                <div class="flex items-start gap-3.5 text-left">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-cyan-500 text-white shadow-md text-lg">🔑</span>
                    <div class="min-w-0 flex-grow">
                        <h3 class="text-sm font-bold text-slate-800">¡Tu cuenta ha sido creada exitosamente!</h3>
                        <p class="text-xs text-slate-650 mt-1 font-semibold leading-relaxed">
                            Para tu comodidad, te hemos iniciado sesión automáticamente. Ya puedes entrar a tu panel en cualquier momento para ver tus reservas, boletos con códigos QR o cambiar tus datos.
                        </p>
                        <div class="mt-3.5 p-3.5 rounded-2xl bg-white border border-cyan-100 shadow-xs flex flex-col gap-1.5 font-mono text-[11px] text-slate-700">
                            <p><strong>Usuario (Correo):</strong> <span class="text-brand-teal font-bold select-all">{{ $reserva->correo_cliente }}</span></p>
                            <p><strong>Contraseña temporal:</strong> <span class="text-indigo-600 font-bold select-all">{{ $tempPassword }}</span></p>
                        </div>
                        <p class="text-[10px] text-slate-450 mt-2 font-medium italic">
                            * Te sugerimos cambiar tu contraseña temporal en tu perfil para mayor seguridad.
                        </p>
                        <a href="{{ route('cliente.dashboard') }}" class="mt-4 w-full h-10 inline-flex items-center justify-center rounded-xl bg-cyan-600 hover:bg-cyan-700 text-xs font-bold text-white transition-all shadow-md shadow-cyan-600/10 cursor-pointer">
                            Ir a Mi Cuenta y Ver Reservas →
                        </a>
                    </div>
                </div>
            @elseif($userAssociated)
                <div class="flex items-start gap-3.5 text-left">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-amber-500 text-white shadow-md text-lg">💼</span>
                    <div class="min-w-0 flex-grow">
                        <h3 class="text-sm font-bold text-slate-800">Vinculado a tu cuenta existente</h3>
                        <p class="text-xs text-slate-650 mt-1 font-semibold leading-relaxed">
                            Hemos detectado que ya tienes una cuenta registrada con el correo <strong class="text-slate-800">{{ $reserva->correo_cliente }}</strong>. Esta reserva ha sido guardada en tu historial.
                        </p>
                        <p class="text-xs text-slate-500 mt-2 font-semibold">
                            Inicia sesión para gestionar esta reserva y descargar tus tickets QR.
                        </p>
                        <a href="{{ route('login') }}" class="mt-4 w-full h-10 inline-flex items-center justify-center rounded-xl bg-amber-600 hover:bg-amber-700 text-xs font-bold text-white transition-all shadow-md shadow-amber-600/10 cursor-pointer">
                            Iniciar Sesión en Mi Cuenta →
                        </a>
                    </div>
                </div>
            @else
                <div class="flex items-start gap-3.5 text-left">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-brand-teal text-white shadow-md text-lg">⛵</span>
                    <div class="min-w-0 flex-grow">
                        <h3 class="text-sm font-bold text-slate-800">Reserva guardada en tu panel</h3>
                        <p class="text-xs text-slate-650 mt-1 font-semibold leading-relaxed">
                            Tu reserva ya está guardada de forma segura en tu historial de cliente. Puedes verla desde tu panel en cualquier momento.
                        </p>
                        <a href="{{ route('cliente.dashboard') }}" class="mt-4 w-full h-10 inline-flex items-center justify-center rounded-xl bg-brand-teal hover:opacity-95 text-xs font-bold text-white transition-all shadow-md shadow-brand-teal/15 cursor-pointer">
                            Ir a Mi Panel de Reservas →
                        </a>
                    </div>
                </div>
            @endif
        </div>

        <!-- ACCIONES NOTIFICACIONES -->
        <div class="mt-8 flex flex-col sm:flex-row items-center gap-4 w-full max-w-md">
            
            <!-- Botón WhatsApp -->
            <button id="whatsapp-btn" onclick="sendNotification('whatsapp')" class="w-full sm:w-1/2 h-10 inline-flex items-center justify-center gap-2 rounded-xl bg-slate-50 hover:bg-slate-100 border border-slate-200 text-xs font-bold text-emerald-600 shadow-xs transition-all cursor-pointer">
                <span>💬</span>
                {{ __('sendWhatsapp') }}
            </button>

            <!-- Botón Email -->
            <button id="email-btn" onclick="sendNotification('email')" class="w-full sm:w-1/2 h-10 inline-flex items-center justify-center gap-2 rounded-xl bg-slate-50 hover:bg-slate-100 border border-slate-200 text-xs font-bold text-brand-teal shadow-xs transition-all cursor-pointer">
                <span id="email-icon">✉️</span>
                <span id="email-text">{{ __('sendEmail') }}</span>
            </button>
        </div>

        <!-- Botón Volver -->
        <a href="{{ route('home') }}" class="mt-10 inline-flex h-10 items-center justify-center px-6 rounded-xl bg-slate-50 hover:bg-slate-100 border border-slate-200 text-xs font-bold text-slate-650 hover:text-slate-850 transition-colors shadow-xs">
            {{ __('backToHome') }}
        </a>
    </div>

    <!-- SCRIPTS DE NOTIFICACIÓN AJAX -->
    <script>
        function sendNotification(channel) {
            const btn = document.getElementById(`${channel}-btn`);
            const token = document.querySelector('input[name="_token"]').value;

            if (channel === 'email') {
                const textEl = document.getElementById('email-text');
                const iconEl = document.getElementById('email-icon');
                
                // Efecto de carga
                textEl.textContent = 'Enviando...';
                iconEl.innerHTML = '<span class="inline-block animate-spin h-3.5 w-3.5 border-2 border-brand-teal border-t-transparent rounded-full"></span>';
                btn.disabled = true;

                fetch("{{ route('checkout.notify') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': token
                    },
                    body: JSON.stringify({
                        channel: 'email',
                        reserva_id: {{ $reserva->id }}
                    })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        textEl.textContent = '¡Correo Enviado!';
                        iconEl.textContent = '✔️';
                        btn.classList.remove('text-brand-teal');
                        btn.classList.add('text-emerald-600');
                    }
                })
                .catch(err => {
                    console.error(err);
                    textEl.textContent = 'Reintentar';
                    iconEl.textContent = '✉️';
                    btn.disabled = false;
                });
            } else if (channel === 'whatsapp') {
                btn.disabled = true;
                const originalContent = btn.innerHTML;
                btn.textContent = 'Redirigiendo...';

                fetch("{{ route('checkout.notify') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': token
                    },
                    body: JSON.stringify({
                        channel: 'whatsapp',
                        reserva_id: {{ $reserva->id }}
                    })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success && data.redirect_url) {
                        // Abrir redirección
                        window.open(data.redirect_url, '_blank');
                        btn.innerHTML = originalContent;
                        btn.disabled = false;
                    }
                })
                .catch(err => {
                    console.error(err);
                    btn.innerHTML = originalContent;
                    btn.disabled = false;
                });
            }
        }
    </script>
@endsection
