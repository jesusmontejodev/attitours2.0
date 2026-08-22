@extends('layouts.app')

<!--
 * @file mensajes.blade.php
 * @description Panel admin de mensajería: lista de hilos cliente-proveedor a la izquierda,
 *              conversación seleccionada a la derecha. El admin responde en nombre del
 *              proveedor y ve su contacto real (nunca expuesto al cliente).
 * @date 2026-08-21
 * @author Antigravity
-->

@section('title', 'Mensajes - Attitour')

@section('content')
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-10 animate-fade-in">

        <!-- ENCABEZADO -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-200 pb-6 mb-8">
            <div>
                <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-1.5 text-[10px] font-black uppercase tracking-wider text-slate-500 hover:text-brand-teal transition-colors mb-3 cursor-pointer">
                    &larr; Volver al Panel
                </a>
                <h1 class="text-2xl sm:text-3xl font-black text-slate-800">
                    Mensajes
                </h1>
                <p class="text-xs text-slate-500 mt-1 font-semibold">
                    Chat con los clientes. Respondes en nombre del proveedor — su contacto real nunca se muestra al cliente.
                </p>
            </div>
        </div>

        @if(session('success'))
        <div class="mb-6 flex items-center gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-5 py-3.5 text-sm text-emerald-700 font-bold shadow-sm">
            {{ session('success') }}
        </div>
        @endif

        <!-- CONTENIDO MÓDULO -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">

            <!-- COLUMNA IZQUIERDA: LISTA DE HILOS -->
            <div class="lg:col-span-4 flex flex-col gap-4">
                <div class="p-4 rounded-3xl border border-slate-200 bg-white shadow-md">
                    <h2 class="text-xs font-bold uppercase tracking-widest text-slate-800 border-b border-slate-200 pb-3 mb-3">
                        Conversaciones
                    </h2>
                    <div id="hilos-list" class="flex flex-col gap-2 max-h-[60vh] overflow-y-auto">
                        @forelse($hilos as $hilo)
                            <button type="button"
                                onclick="abrirHilo({{ $hilo->reserva->id }}, this)"
                                data-reserva-id="{{ $hilo->reserva->id }}"
                                class="hilo-btn text-left px-4 py-3 rounded-xl border border-slate-200 hover:border-brand-teal/40 hover:bg-slate-50 transition-all cursor-pointer">
                                <div class="flex items-center justify-between gap-2">
                                    <span class="text-xs font-bold text-slate-800 truncate">{{ $hilo->reserva->nombre_cliente }}</span>
                                    @if($hilo->no_leidos > 0)
                                        <span class="shrink-0 px-1.5 py-0.5 rounded-full bg-rose-500 text-white text-[9px] font-black">{{ $hilo->no_leidos }}</span>
                                    @endif
                                </div>
                                <span class="text-[10px] text-slate-400 font-semibold">{{ $hilo->reserva->ticket_codigo }}</span>
                                @if($hilo->ultimo_msg)
                                    <p class="text-[10px] text-slate-500 mt-1 truncate">{{ $hilo->ultimo_msg->cuerpo }}</p>
                                @endif
                            </button>
                        @empty
                            <p class="text-xs text-slate-400 font-semibold px-2 py-6 text-center">Aún no hay mensajes de clientes.</p>
                        @endforelse
                    </div>
                </div>
            </div>

            <!-- COLUMNA DERECHA: CONVERSACIÓN -->
            <div class="lg:col-span-8 flex flex-col gap-6">
                <div class="p-6 rounded-3xl border border-slate-200 bg-white shadow-md">
                    <div id="hilo-empty" class="flex flex-col items-center justify-center h-72 text-slate-400 text-center">
                        <p class="text-xs font-semibold">Selecciona una conversación de la izquierda.</p>
                    </div>

                    <div id="hilo-detail" class="hidden">
                        <h2 id="hilo-titulo" class="text-xs font-bold uppercase tracking-widest text-slate-800 border-b border-slate-200 pb-3 mb-4"></h2>

                        {{-- Contacto real del proveedor: SOLO visible aquí, admin-only --}}
                        <div id="hilo-proveedores" class="flex flex-col gap-2 mb-4"></div>

                        <div id="hilo-mensajes" class="flex flex-col gap-3 max-h-[40vh] overflow-y-auto mb-5 pr-1"></div>

                        <div class="border-t border-slate-200 pt-4 flex flex-col gap-2">
                            <label class="text-[9px] font-black uppercase tracking-widest text-slate-500">
                                Contacto real usado para reenviar (auditoría interna, no se muestra al cliente)
                            </label>
                            <input type="text" id="input-contacto-destino" placeholder="Teléfono o correo real del proveedor..."
                                class="h-10 rounded-xl border border-slate-200 bg-slate-50 px-3 text-xs text-slate-800 placeholder-slate-400 focus:border-brand-teal focus:bg-white focus:outline-none transition-colors">

                            <label class="text-[9px] font-black uppercase tracking-widest text-slate-500 mt-2">
                                Respuesta (visible para el cliente)
                            </label>
                            <textarea id="input-cuerpo" rows="3" placeholder="Escribe la respuesta del proveedor..."
                                class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-800 placeholder-slate-400 focus:border-brand-teal focus:bg-white focus:outline-none transition-colors resize-none"></textarea>

                            <button onclick="enviarRespuesta()"
                                class="self-end mt-2 h-10 px-6 rounded-xl bg-gradient-to-r from-brand-teal to-brand-teal-hover text-xs font-bold uppercase tracking-wider text-white shadow-md shadow-brand-teal/10 hover:opacity-95 transition-all cursor-pointer">
                                Enviar Respuesta
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        let hiloActivoId = null;

        function abrirHilo(reservaId, btn) {
            hiloActivoId = reservaId;

            document.querySelectorAll('.hilo-btn').forEach(b => b.classList.remove('border-brand-teal', 'bg-slate-50'));
            btn.classList.add('border-brand-teal', 'bg-slate-50');
            const badge = btn.querySelector('span.bg-rose-500');
            if (badge) badge.remove();

            fetch(`/dashboard/mensajes/${reservaId}`, {
                headers: { 'Accept': 'application/json' },
            })
            .then(res => res.json())
            .then(data => {
                if (!data.success) return;

                document.getElementById('hilo-empty').classList.add('hidden');
                document.getElementById('hilo-detail').classList.remove('hidden');
                document.getElementById('hilo-titulo').textContent = data.reserva.nombre_cliente + ' — ' + data.reserva.ticket_codigo;

                const proveedoresEl = document.getElementById('hilo-proveedores');
                proveedoresEl.innerHTML = data.proveedores.map(p => `
                    <div class="px-3 py-2 rounded-lg bg-amber-50 border border-amber-200 text-[10px] font-semibold text-amber-800">
                        <span class="font-black">${p.tour_nombre}</span> — ${p.proveedor_nombre}<br>
                        Contacto real: ${p.representante_telefono || '—'} ${p.correo ? '· ' + p.correo : ''}
                    </div>
                `).join('');

                if (data.proveedores.length > 0 && !document.getElementById('input-contacto-destino').value) {
                    document.getElementById('input-contacto-destino').value = data.proveedores[0].representante_telefono || '';
                }

                const mensajesEl = document.getElementById('hilo-mensajes');
                mensajesEl.innerHTML = data.mensajes.map(m => {
                    const esCliente = m.remitente_tipo === 'cliente';
                    return `
                        <div class="flex ${esCliente ? 'justify-start' : 'justify-end'}">
                            <div class="max-w-[75%] px-3 py-2 rounded-xl text-xs font-semibold ${esCliente ? 'bg-slate-100 text-slate-800' : 'bg-brand-teal/10 text-brand-teal'}">
                                <p>${m.cuerpo}</p>
                                <span class="block text-[9px] mt-1 opacity-60">${m.created_at}</span>
                            </div>
                        </div>
                    `;
                }).join('');
                mensajesEl.scrollTop = mensajesEl.scrollHeight;
            });
        }

        function enviarRespuesta() {
            const cuerpo = document.getElementById('input-cuerpo').value.trim();
            if (!hiloActivoId || !cuerpo) return;

            fetch(`/dashboard/mensajes/${hiloActivoId}/responder`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({
                    cuerpo: cuerpo,
                    contacto_destino_usado: document.getElementById('input-contacto-destino').value.trim() || null,
                }),
            })
            .then(res => res.json())
            .then(data => {
                if (!data.success) return;
                document.getElementById('input-cuerpo').value = '';
                abrirHilo(hiloActivoId, document.querySelector(`.hilo-btn[data-reserva-id="${hiloActivoId}"]`));
            });
        }
    </script>
@endsection
