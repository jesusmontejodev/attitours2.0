@extends('layouts.app')

<!-- 
 * @file qr.blade.php
 * @description Módulo de escaneo de códigos QR de asistencia para validar boarding de pasajeros. Adaptado a tema claro premium con colores corporativos e identidad de la marca.
 * @date 2026-06-29
 * @author Antigravity
 -->

@section('title', 'Asistencia por QR - Attitour')

@section('content')
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-10 animate-fade-in">
        
        <!-- ENCABEZADO -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-200 pb-6 mb-8">
            <div>
                <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-1.5 text-[10px] font-black uppercase tracking-wider text-slate-500 hover:text-brand-teal transition-colors mb-3 cursor-pointer">
                    &larr; Volver al Panel
                </a>
                <h1 class="text-2xl sm:text-3xl font-black text-slate-800">
                    Asistencia por Código QR
                </h1>
                <p class="text-xs text-slate-500 mt-1 font-semibold">
                    @if(Auth::user()->isAdmin())
                        Consola de control global para boarding de pasajeros.
                    @else
                        Operadora Local: <span class="text-brand-teal font-bold">{{ Auth::user()->proveedor->nombre_empresa }}</span>
                    @endif
                </p>
            </div>
            
            <button id="qr-toggle-btn" onclick="toggleQrScanner()"
                class="h-10 px-5 inline-flex items-center justify-center rounded-xl bg-gradient-to-r from-brand-teal to-brand-teal-hover text-xs font-bold uppercase tracking-wider text-white shadow-md shadow-brand-teal/10 hover:opacity-95 transition-all cursor-pointer">
                Iniciar Cámara
            </button>
        </div>

        {{-- ========= BANNER DE NOTIFICACIONES ========= --}}
        @if(session('success'))
        <div id="flash-success" class="mb-6 flex items-center gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-5 py-3.5 text-sm text-emerald-700 font-bold shadow-sm" role="alert">
            <svg class="h-5 w-5 shrink-0 text-emerald-605" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <span class="font-bold">{{ session('success') }}</span>
            <button onclick="document.getElementById('flash-success').remove()" class="ml-auto text-emerald-600 hover:text-emerald-800 cursor-pointer">✕</button>
        </div>
        @endif
        @if(session('error'))
        <div id="flash-error" class="mb-6 flex items-center gap-3 rounded-xl border border-rose-200 bg-rose-50 px-5 py-3.5 text-sm text-rose-700 font-bold shadow-sm" role="alert">
            <svg class="h-5 w-5 shrink-0 text-rose-605" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <span class="font-bold">{{ session('error') }}</span>
            <button onclick="document.getElementById('flash-error').remove()" class="ml-auto text-rose-600 hover:text-rose-800 cursor-pointer">✕</button>
        </div>
        @endif

        <!-- CONTENIDO MÓDULO -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
            
            <!-- COLUMNA IZQUIERDA: CÁMARA & CONTROL (lg:col-span-7) -->
            <div class="lg:col-span-7 flex flex-col gap-6">
                <div class="p-6 rounded-3xl border border-slate-200 bg-white shadow-md">
                    <h2 class="text-xs font-bold uppercase tracking-widest text-slate-800 border-b border-slate-200 pb-3 mb-5">
                        Lector de Códigos QR
                    </h2>

                    {{-- Estado inicial --}}
                    <div id="qr-idle-state" class="flex flex-col items-center justify-center h-72 rounded-2xl border-2 border-dashed border-slate-200 bg-slate-50/50">
                        <div class="h-16 w-16 rounded-2xl bg-slate-100 flex items-center justify-center mb-4">
                            <svg class="h-8 w-8 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                        </div>
                        <p class="text-xs font-bold text-slate-500">Cámara inactiva</p>
                        <p class="text-[10px] text-slate-400 mt-1">Haz clic en "Iniciar Cámara" para escanear.</p>
                    </div>

                    {{-- Contenedor de la cámara (html5-qrcode) --}}
                    <div id="qr-reader" class="hidden w-full rounded-2xl overflow-hidden border border-slate-200" style="min-height:300px;"></div>

                    {{-- Input manual como fallback --}}
                    <div id="qr-manual-input" class="hidden mt-6 pt-5 border-t border-slate-200">
                        <p class="text-[9px] font-black uppercase tracking-widest text-slate-500 mb-2">Ingresar Código Manualmente</p>
                        <div class="flex gap-2">
                            <input type="text" id="qr-manual-text" placeholder="Pega o escribe el token del QR aquí..."
                                class="flex-1 h-10 rounded-xl border border-slate-200 bg-slate-50 px-3 text-xs text-slate-800 placeholder-slate-400 focus:border-brand-teal focus:bg-white focus:outline-none transition-colors">
                            <button onclick="processQrContent(document.getElementById('qr-manual-text').value)"
                                class="px-5 h-10 rounded-xl bg-brand-teal/10 border border-brand-teal/20 text-xs font-bold text-brand-teal hover:bg-brand-teal/20 cursor-pointer transition-all">
                                Validar
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- COLUMNA DERECHA: RESULTADO & REGISTROS (lg:col-span-5) -->
            <div class="lg:col-span-5 flex flex-col gap-6">
                <!-- Panel de Resultado -->
                <div class="p-6 rounded-3xl border border-slate-200 bg-white shadow-md">
                    <h2 class="text-xs font-bold uppercase tracking-widest text-slate-800 border-b border-slate-200 pb-3 mb-5">
                        Resultado de Validación
                    </h2>

                    {{-- Estado vacío --}}
                    <div id="qr-result-empty" class="flex flex-col items-center justify-center h-48 text-slate-400 text-center">
                        <svg class="h-10 w-10 mb-2 opacity-35" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/>
                        </svg>
                        <p class="text-xs font-semibold">Esperando escaneo...</p>
                        <p class="text-[10px] text-slate-400 mt-1 max-w-[200px]">Los datos del ticket aparecerán en este panel una vez se detecte el código.</p>
                    </div>

                    {{-- Loading --}}
                    <div id="qr-result-loading" class="hidden flex flex-col items-center justify-center h-48">
                        <div class="h-10 w-10 rounded-full border-2 border-brand-teal/30 border-t-brand-teal animate-spin mb-3"></div>
                        <p class="text-xs text-slate-500 font-semibold">Verificando en base de datos...</p>
                    </div>

                    {{-- Resultado confirmado ✅ --}}
                    <div id="qr-result-ok" class="hidden">
                        <div class="flex items-center gap-3 mb-4 p-4 rounded-2xl bg-emerald-50 border border-emerald-250">
                            <div class="h-10 w-10 rounded-xl bg-emerald-100 flex items-center justify-center flex-shrink-0">
                                <svg class="h-6 w-6 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                </svg>
                            </div>
                            <div>
                                <p id="qr-ok-title" class="text-sm font-bold text-emerald-700">Boarding Permitido</p>
                                <p id="qr-ok-subtitle" class="text-[10px] text-emerald-600/70"></p>
                            </div>
                        </div>
                        <div class="space-y-3 font-semibold" id="qr-client-info"></div>
                        <button onclick="resetScanner()" class="mt-5 w-full h-10 rounded-xl border border-slate-200 bg-slate-50 text-xs font-bold text-slate-650 hover:bg-slate-100 hover:text-slate-800 cursor-pointer shadow-xs transition-colors">
                            Listo para Escanear Siguiente
                        </button>
                    </div>

                    {{-- Resultado error ❌ --}}
                    <div id="qr-result-error" class="hidden">
                        <div class="flex items-center gap-3 mb-4 p-4 rounded-2xl bg-rose-50 border border-rose-250">
                            <div class="h-10 w-10 rounded-xl bg-rose-100 flex items-center justify-center flex-shrink-0">
                                <svg class="h-6 w-6 text-rose-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-bold text-rose-700">Boarding Rechazado</p>
                                <p id="qr-error-msg" class="text-[10px] text-rose-600/70"></p>
                            </div>
                        </div>
                        <button onclick="resetScanner()" class="w-full h-10 rounded-xl border border-slate-200 bg-slate-50 text-xs font-bold text-slate-650 hover:bg-slate-100 hover:text-slate-800 cursor-pointer shadow-xs transition-colors">
                            Reintentar Escaneo
                        </button>
                    </div>
                </div>

                <!-- Historial de hoy -->
                <div class="p-6 rounded-3xl border border-slate-200 bg-white shadow-md overflow-hidden">
                    <h2 class="text-xs font-bold uppercase tracking-widest text-slate-800 border-b border-slate-200 pb-3 mb-4">
                        Historial de Escaneos de Hoy
                    </h2>

                    @if($escaneosHoy->isEmpty())
                        <p class="text-xs text-slate-400 text-center py-6" id="empty-history-label">Ningún escaneo registrado hoy</p>
                        <div class="space-y-2 max-h-[250px] overflow-y-auto pr-1" id="history-container"></div>
                    @else
                        <p class="text-xs text-slate-400 text-center py-6 hidden" id="empty-history-label">Ningún escaneo registrado hoy</p>
                        <div class="space-y-2 max-h-[250px] overflow-y-auto pr-1" id="history-container">
                            @foreach($escaneosHoy as $escaneo)
                                @php $esOk = in_array($escaneo->resultado, ['confirmed', 'already_confirmed']); @endphp
                                <div class="flex items-center justify-between px-4 py-3 rounded-xl border {{ $esOk ? 'border-emerald-100 bg-emerald-50/50' : 'border-rose-100 bg-rose-50/50' }} text-xs font-semibold animate-fade-in">
                                    <div>
                                        <span class="font-bold text-slate-800 block">{{ $escaneo->reserva->nombre_cliente ?? 'QR no reconocido' }}</span>
                                        <span class="text-slate-500 text-[10px]">{{ $escaneo->reserva->ticket_codigo ?? $escaneo->mensaje }} &bull; {{ $escaneo->usuario->name ?? '—' }}</span>
                                    </div>
                                    <span class="text-[10px] {{ $esOk ? 'text-emerald-700' : 'text-rose-700' }} font-bold font-mono shrink-0 pl-2">
                                        {{ $esOk ? '✓' : '✕' }} {{ $escaneo->created_at->format('H:i') }}
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

        </div>
    </div>

    {{-- CDN: html5-qrcode para acceso a la cámara --}}
    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
    <script>
        // ═══════════════════════════════════════════════════════════════════════════
        // ESCÁNER QR — SISTEMA DE CONFIRMACIÓN DE ASISTENCIA
        // ═══════════════════════════════════════════════════════════════════════════

        let html5QrScanner = null;
        let qrScannerActive = false;
        let qrLastScanned = '';

        // Auto dismiss flash messages
        ['flash-success','flash-error'].forEach(id => {
            const el = document.getElementById(id);
            if (el) setTimeout(() => el.style.opacity = '0', 3500) || setTimeout(() => el.remove(), 4000);
        });

        /**
         * Activa o desactiva el escáner de cámara.
         */
        function toggleQrScanner() {
            if (qrScannerActive) {
                stopQrScanner();
            } else {
                startQrScanner();
            }
        }

        /**
         * Inicia el escáner usando la cámara trasera.
         */
        function startQrScanner() {
            const idleState  = document.getElementById('qr-idle-state');
            const readerDiv  = document.getElementById('qr-reader');
            const manualDiv  = document.getElementById('qr-manual-input');
            const toggleBtn  = document.getElementById('qr-toggle-btn');

            readerDiv.innerHTML = '';
            idleState.classList.add('hidden');
            readerDiv.classList.remove('hidden');
            manualDiv.classList.remove('hidden');

            toggleBtn.textContent = 'Detener Cámara';
            toggleBtn.classList.remove('from-brand-teal', 'to-brand-teal-hover');
            toggleBtn.classList.add('from-rose-500', 'to-rose-600');

            html5QrScanner = new Html5Qrcode('qr-reader');
            const config = {
                fps: 10,
                qrbox: { width: 220, height: 220 },
                aspectRatio: 1.2,
            };

            html5QrScanner.start(
                { facingMode: 'environment' }, // Cámara trasera
                config,
                (decodedText) => {
                    if (decodedText === qrLastScanned) return;
                    qrLastScanned = decodedText;
                    processQrContent(decodedText);
                },
                (errorMsg) => { /* Silenciar errores de lectura vacíos */ }
            ).catch((err) => {
                console.error('Error cámara:', err);
                // Si falla, revertir e indicar error
                readerDiv.classList.add('hidden');
                idleState.classList.remove('hidden');
                idleState.innerHTML = `
                    <div class="h-16 w-16 rounded-2xl bg-amber-50 border border-amber-200 flex items-center justify-center mb-3 mx-auto">
                        <span class="text-2xl">⚠️</span>
                    </div>
                    <p class="text-xs font-bold text-amber-600 text-center">No se pudo acceder a la cámara</p>
                    <p class="text-[10px] text-slate-500 mt-1 text-center max-w-[220px]">Permite el acceso a la cámara o ingresa el código del QR manualmente en el campo inferior.</p>
                `;
                manualDiv.classList.remove('hidden');
            });

            qrScannerActive = true;
        }

        /**
         * Detiene el escáner.
         */
        function stopQrScanner() {
            if (html5QrScanner && html5QrScanner.isScanning) {
                html5QrScanner.stop().catch(() => {});
            }

            const idleState = document.getElementById('qr-idle-state');
            const readerDiv = document.getElementById('qr-reader');
            const manualDiv = document.getElementById('qr-manual-input');
            const toggleBtn = document.getElementById('qr-toggle-btn');

            idleState.innerHTML = `
                <div class="h-16 w-16 rounded-2xl bg-slate-100 flex items-center justify-center mb-3">
                    <svg class="h-8 w-8 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </div>
                <p class="text-xs font-bold text-slate-500">Cámara inactiva</p>
                <p class="text-[10px] text-slate-400 mt-1">Haz clic en "Iniciar Cámara" para escanear.</p>
            `;
            readerDiv.innerHTML = '';
            idleState.classList.remove('hidden');
            readerDiv.classList.add('hidden');
            manualDiv.classList.add('hidden');

            toggleBtn.textContent = 'Iniciar Cámara';
            toggleBtn.classList.add('from-brand-teal', 'to-brand-teal-hover');
            toggleBtn.classList.remove('from-rose-500', 'to-rose-600');

            qrScannerActive = false;
            qrLastScanned = '';
        }

        /**
         * Envía el token al controlador mediante AJAX POST.
         */
        function processQrContent(content) {
            if (!content || !content.trim()) return;

            if (html5QrScanner && html5QrScanner.isScanning) {
                html5QrScanner.pause();
            }

            showQrPanel('loading');

            fetch('{{ route("dashboard.qr.scan") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ qr_content: content.trim() }),
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    showQrResult(data);
                    // Agregar entrada al historial local dinámicamente si no estaba en la lista de hoy
                    if (data.status === 'confirmed') {
                        prependHistoryRow(data.reserva);
                    }
                } else {
                    showQrError(data.message || 'El código QR ingresado no es válido o ha expirado.');
                }
            })
            .catch(err => {
                console.error(err);
                showQrError('Error de red. Asegúrate de tener conexión a internet.');
            });
        }

        /**
         * Añade una fila al historial de hoy de forma dinámica.
         */
        function prependHistoryRow(res) {
            const container = document.getElementById('history-container');
            const emptyLabel = document.getElementById('empty-history-label');
            if (emptyLabel) emptyLabel.classList.add('hidden');

            const timeNow = new Date().toLocaleTimeString('es-MX', { hour: '2-digit', minute: '2-digit' });
            
            const row = document.createElement('div');
            row.className = 'flex items-center justify-between px-4 py-3 rounded-xl border border-emerald-100 bg-emerald-50/50 text-xs font-semibold animate-fade-in';
            row.innerHTML = `
                <div>
                    <span class="font-bold text-slate-800 block">${res.nombre_cliente}</span>
                    <span class="text-slate-500 text-[10px]">${res.ticket_codigo}</span>
                </div>
                <span class="text-[10px] text-emerald-700 font-bold font-mono">
                    ✓ ${timeNow}
                </span>
            `;

            if (container) {
                container.prepend(row);
            }
        }

        /**
         * Muestra los detalles de boarding exitoso.
         */
        function showQrResult(data) {
            showQrPanel('ok');
            const okTitle    = document.getElementById('qr-ok-title');
            const okSubtitle = document.getElementById('qr-ok-subtitle');
            const clientInfo = document.getElementById('qr-client-info');

            if (data.status === 'already_confirmed') {
                okTitle.textContent = 'Asistencia ya Registrada';
                okTitle.className = 'text-sm font-bold text-brand-orange';
                okSubtitle.textContent = 'Confirmada anteriormente: ' + (data.confirmed_at || '');
            } else {
                okTitle.textContent = '¡Boarding Permitido!';
                okTitle.className = 'text-sm font-bold text-emerald-700';
                okSubtitle.textContent = 'Registrado ahora mismo.';
            }

            const r = data.reserva;
            if (r) {
                let toursHtml = (r.tours || []).map(t => `
                    <div class="text-[10px] text-slate-500 pl-3 border-l-2 border-brand-teal">
                        <span class="font-bold text-slate-700">${t.nombre}</span><br>
                        📅 ${t.fecha} · 🕒 ${t.horario} · 👥 ${t.personas} Pax
                    </div>
                `).join('');

                clientInfo.innerHTML = `
                    <div class="space-y-2.5 p-3 rounded-xl bg-slate-50 border border-slate-200">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="text-xs font-bold text-slate-850">${r.nombre_cliente}</p>
                                <p class="text-[10px] text-slate-500">${r.correo}</p>
                                <p class="text-[10px] text-slate-500">${r.telefono || ''}</p>
                            </div>
                            <span class="text-[9px] font-bold px-2 py-1 rounded-md bg-white text-brand-teal border border-slate-200">${r.ticket_codigo}</span>
                        </div>
                        <div class="space-y-2 pt-2 border-t border-slate-200">
                            ${toursHtml}
                        </div>
                    </div>
                `;
            }
        }

        /**
         * Muestra panel de error.
         */
        function showQrError(msg) {
            showQrPanel('error');
            document.getElementById('qr-error-msg').textContent = msg;
        }

        /**
         * Alterna visibilidad de los paneles de resultado.
         */
        function showQrPanel(panel) {
            ['empty','loading','ok','error'].forEach(p => {
                const el = document.getElementById('qr-result-' + p);
                if (el) el.classList.toggle('hidden', p !== panel);
            });
        }

        /**
         * Resetea el escáner para continuar con el siguiente código.
         */
        function resetScanner() {
            showQrPanel('empty');
            qrLastScanned = '';
            document.getElementById('qr-manual-text').value = '';
            if (html5QrScanner && qrScannerActive) {
                try { html5QrScanner.resume(); } catch(e) {}
            }
        }

        // --- PRECARGAR TOKEN SI VIENE DE REDIRECCIÓN DIRECTA ---
        @if(session('qr_to_verify'))
            document.addEventListener('DOMContentLoaded', () => {
                const token = '{{ session("qr_to_verify") }}';
                processQrContent('ATTITOURS:' + token);
            });
        @endif
    </script>
@endsection
