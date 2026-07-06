@extends('layouts.app')

<!-- 
 * @file success.blade.php
 * @description Vista Blade para la confirmación exitosa de reservas. Muestra el ticket virtual e interacciones simuladas de envío. Adaptado a tema claro con colores corporativos e identidad de la marca.
 * @date 2026-06-29
 * @author Antigravity
 -->

@section('title', '¡Reserva Confirmada! - Atti Tours')

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
                    <span class="text-xs font-black tracking-wider text-brand-teal">ATTI TOURS</span>
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
                                <span class="text-xs font-bold text-slate-650 shrink-0">${{ number_format($detalle->precio_unitario_usd * $detalle->cantidad_personas) }} MXN</span>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Total pagado -->
                <div class="flex items-center justify-between border-t border-slate-200 pt-4 font-semibold">
                    <span class="text-xs font-bold text-slate-500">Importe Pagado (MXN):</span>
                    <span class="text-sm font-black text-brand-teal">${{ number_format($reserva->precio_total_usd, 2) }} MXN</span>
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
