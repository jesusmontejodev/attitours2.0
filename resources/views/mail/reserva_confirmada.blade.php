<!DOCTYPE html>
<!--
 * @file reserva_confirmada.blade.php
 * @description Plantilla HTML del correo de confirmación de reserva. Incluye el ticket
 *              virtual con todos los detalles del tour, el QR único de asistencia y la
 *              sección de credenciales si se le ha creado una cuenta.
 * @date 2026-07-31
 * @author Antigravity
-->
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reserva Confirmada — Atti Tours</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            background-color: #020617;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            color: #e2e8f0;
        }
        .wrapper { max-width: 600px; margin: 0 auto; padding: 32px 16px; }

        /* ── Header ─────────────────────────────── */
        .header {
            text-align: center;
            padding: 32px;
            background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 100%);
            border-radius: 24px 24px 0 0;
            border: 1px solid #1e293b;
            border-bottom: none;
        }
        .logo { font-size: 28px; font-weight: 900; letter-spacing: 3px;
                background: linear-gradient(90deg, #22d3ee, #818cf8); -webkit-background-clip: text;
                -webkit-text-fill-color: transparent; background-clip: text; }
        .header h1 { font-size: 22px; font-weight: 800; color: #fff; margin-top: 12px; }
        .header p  { font-size: 13px; color: #94a3b8; margin-top: 6px; }

        /* ── Ticket ─────────────────────────────── */
        .ticket {
            background: #0f172a;
            border: 1px solid #1e293b;
            border-top: none;
            border-bottom: none;
            padding: 0;
        }
        .ticket-header {
            background: #1e293b;
            padding: 20px 28px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px dashed #0f172a;
        }
        .ticket-label { font-size: 10px; font-weight: 700; text-transform: uppercase;
                        letter-spacing: 2px; color: #64748b; }
        .ticket-code { font-size: 16px; font-weight: 900; color: #22d3ee;
                       letter-spacing: 2px; font-family: monospace; }
        .ticket-body { padding: 24px 28px; }

        /* ── Cliente info ───────────────────────── */
        .client-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 24px; }
        .info-block .label { font-size: 9px; font-weight: 700; text-transform: uppercase;
                             letter-spacing: 2px; color: #64748b; margin-bottom: 4px; }
        .info-block .value { font-size: 13px; font-weight: 700; color: #f1f5f9; }
        .badge-pagada { display: inline-block; background: rgba(16,185,129,0.15); color: #34d399;
                        border: 1px solid rgba(52,211,153,0.3); border-radius: 6px;
                        font-size: 10px; font-weight: 800; padding: 3px 10px; text-transform: uppercase; letter-spacing: 1px; }

        /* ── Tours detalle ──────────────────────── */
        .section-title { font-size: 9px; font-weight: 700; text-transform: uppercase;
                         letter-spacing: 2px; color: #64748b; margin-bottom: 12px; }
        .tour-item {
            background: #1e293b;
            border: 1px solid #334155;
            border-radius: 12px;
            padding: 14px 16px;
            margin-bottom: 10px;
        }
        .tour-name { font-size: 13px; font-weight: 700; color: #f1f5f9; margin-bottom: 6px; }
        .tour-meta { font-size: 11px; color: #94a3b8; line-height: 1.6; }
        .tour-meta span { color: #22d3ee; font-weight: 600; }
        .tour-price { font-size: 14px; font-weight: 900; color: #fff; float: right; margin-top: -20px; }

        /* ── Divisor dashed ─────────────────────── */
        .divider {
            border: none;
            border-top: 2px dashed #1e293b;
            margin: 20px 0;
            position: relative;
        }
        .circle-left  { position: absolute; top: -12px; left:  -28px; width: 22px; height: 22px;
                        background: #020617; border-radius: 50%; border: 1px solid #020617; }
        .circle-right { position: absolute; top: -12px; right: -28px; width: 22px; height: 22px;
                        background: #020617; border-radius: 50%; border: 1px solid #020617; }

        /* ── QR Section ─────────────────────────── */
        .qr-section { text-align: center; padding: 8px 0 24px; }
        .qr-section h2 { font-size: 14px; font-weight: 800; color: #fff; margin-bottom: 4px; }
        .qr-section p  { font-size: 11px; color: #64748b; margin-bottom: 16px; line-height: 1.5; }
        .qr-box {
            display: inline-block;
            padding: 16px;
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 0 40px rgba(34,211,238,0.15);
        }
        .qr-box img { display: block; width: 200px; height: 200px; }
        .qr-note { margin-top: 12px; font-size: 10px; color: #475569; }
        .qr-note strong { color: #94a3b8; }

        /* ── Total ──────────────────────────────── */
        .total-row { display: flex; justify-content: space-between; align-items: center;
                     padding: 16px 0 0; }
        .total-label { font-size: 11px; text-transform: uppercase; letter-spacing: 2px; color: #64748b; font-weight: 700; }
        .total-amount { font-size: 22px; font-weight: 900; color: #22d3ee; }

        /* ── Footer ─────────────────────────────── */
        .footer {
            background: #0f172a;
            border: 1px solid #1e293b;
            border-top: none;
            border-radius: 0 0 24px 24px;
            padding: 24px 28px;
            text-align: center;
        }
        .footer p { font-size: 11px; color: #475569; line-height: 1.7; }
        .footer a { color: #22d3ee; text-decoration: none; }
        .footer .social { margin-top: 16px; }
        .cta-btn {
            display: inline-block; margin: 16px auto 0;
            padding: 12px 28px;
            background: linear-gradient(90deg, #06b6d4, #6366f1);
            color: #fff; font-weight: 800; font-size: 13px;
            border-radius: 10px; text-decoration: none; letter-spacing: 1px;
        }
    </style>
</head>
<body>
<div class="wrapper">

    {{-- ── HEADER ── --}}
    <div class="header">
        <div class="logo">ATTI TOURS</div>
        <h1>¡Reserva Confirmada! 🎉</h1>
        <p>Prepárate para vivir una experiencia inolvidable en el Caribe Mexicano</p>
    </div>

    {{-- ── TICKET ── --}}
    <div class="ticket">

        {{-- Cabecera del ticket --}}
        <div class="ticket-header">
            <div>
                <div class="ticket-label">Código de Reserva</div>
                <div class="ticket-code">{{ $reserva->ticket_codigo }}</div>
            </div>
            @if($reserva->monto_pendiente_destino_usd > 0)
                <span class="badge-pagada" style="background: rgba(249,115,22,0.15); color: #f97316; border: 1px solid rgba(249,115,22,0.3);">✓ Anticipo Pagado</span>
            @else
                <span class="badge-pagada">✓ Pagada</span>
            @endif
        </div>

        {{-- Cuerpo --}}
        <div class="ticket-body">

            {{-- Info del cliente --}}
            <div class="client-grid">
                <div class="info-block">
                    <div class="label">Nombre del Viajero</div>
                    <div class="value">{{ $reserva->nombre_cliente }}</div>
                </div>
                <div class="info-block">
                    <div class="label">Fecha de Reserva</div>
                    <div class="value">{{ $reserva->fecha_reserva->format('d M Y') }}</div>
                </div>
                <div class="info-block">
                    <div class="label">Correo</div>
                    <div class="value" style="font-size:11px;">{{ $reserva->correo_cliente }}</div>
                </div>
                <div class="info-block">
                    <div class="label">Teléfono</div>
                    <div class="value">{{ $reserva->telefono_cliente }}</div>
                </div>
            </div>

            {{-- Detalle de tours --}}
            <div class="section-title">Actividades Incluidas</div>
            @foreach($reserva->detalles as $det)
            <div class="tour-item">
                @php
                    $titulo = $det->tour->titulo ?? 'Tour';
                    if (is_array($titulo)) $titulo = $titulo['es'] ?? reset($titulo);
                @endphp
                <div class="tour-name">{{ $titulo }}</div>
                <div class="tour-price">${{ number_format($det->precio_unitario_usd * $det->cantidad_personas, 0) }} USD</div>
                <div class="tour-meta">
                    📅 Fecha: <span>{{ \Carbon\Carbon::parse($det->fecha_seleccionada)->locale('es')->translatedFormat('d M Y') }}</span><br>
                    @if($det->horario) 🕘 Horario: <span>{{ $det->horario }}</span><br> @endif
                    👥 Personas: <span>{{ $det->cantidad_personas }}</span>
                </div>
            </div>
            @endforeach

            {{-- Total --}}
            @if($reserva->monto_pendiente_destino_usd > 0)
                <div class="total-row" style="padding-bottom: 8px; border-bottom: 1px solid #1e293b; display: flex; justify-content: space-between; align-items: center;">
                    <div class="total-label" style="font-size: 11px;">Monto total del viaje</div>
                    <div class="total-amount" style="font-size: 16px; color: #fff;">${{ number_format($reserva->precio_total_usd, 2) }} <span style="font-size:10px;color:#64748b;">USD</span></div>
                </div>
                <div class="total-row" style="padding-top: 8px; padding-bottom: 8px; border-bottom: 1px solid #1e293b; display: flex; justify-content: space-between; align-items: center;">
                    <div class="total-label" style="font-size: 11px; color: #f97316;">Anticipo pagado online</div>
                    <div class="total-amount" style="font-size: 18px; color: #22d3ee;">${{ number_format($reserva->monto_pagado_online_usd, 2) }} <span style="font-size:10px;color:#64748b;">USD</span></div>
                </div>
                <div class="total-row" style="padding-top: 8px; display: flex; justify-content: space-between; align-items: center;">
                    <div class="total-label" style="font-size: 11px; color: #a5b4fc;">Restante a pagar en destino</div>
                    <div class="total-amount" style="font-size: 16px; color: #818cf8;">${{ number_format($reserva->monto_pendiente_destino_usd, 2) }} <span style="font-size:10px;color:#64748b;">USD</span></div>
                </div>
            @else
                <div class="total-row" style="display: flex; justify-content: space-between; align-items: center; padding: 16px 0 0;">
                    <div class="total-label">Total Pagado</div>
                    <div class="total-amount">${{ number_format($reserva->precio_total_usd, 2) }} <span style="font-size:12px;color:#64748b;font-weight:500;">USD</span></div>
                </div>
            @endif

            @if(isset($tempPassword) && $tempPassword)
            <div style="background: rgba(34,211,238,0.08); border: 1px solid rgba(34,211,238,0.25); border-radius: 16px; padding: 20px; margin-top: 24px; text-align: left;">
                <h3 style="font-size: 13px; font-weight: 800; color: #fff; margin-bottom: 8px;">
                    🔑 ¡Hemos creado tu cuenta de usuario!
                </h3>
                <p style="font-size: 11px; color: #94a3b8; line-height: 1.6; margin-bottom: 12px;">
                    Para que puedas consultar tus tickets virtuales y gestionar esta y futuras reservas de forma rápida, te hemos creado un perfil automáticamente con los siguientes datos de acceso:
                </p>
                <div style="background: #1e293b; border-radius: 10px; padding: 12px 16px; border: 1px solid #334155;">
                    <p style="font-size: 11px; color: #94a3b8; margin: 0 0 6px 0;"><strong>Usuario:</strong> <span style="color: #22d3ee; font-family: monospace;">{{ $reserva->correo_cliente }}</span></p>
                    <p style="font-size: 11px; color: #94a3b8; margin: 0;"><strong>Contraseña Temporal:</strong> <span style="color: #818cf8; font-family: monospace; font-weight: bold; letter-spacing: 1px;">{{ $tempPassword }}</span></p>
                </div>
                <p style="font-size: 9px; color: #64748b; margin-top: 10px; line-height: 1.4;">
                    * Te sugerimos iniciar sesión y cambiar esta contraseña en tu panel de control para mayor seguridad.
                </p>
            </div>
            @endif

            {{-- Divisor decorativo --}}
            <div class="divider">
                <div class="circle-left"></div>
                <div class="circle-right"></div>
            </div>

            {{-- QR de Asistencia --}}
            <div class="qr-section">
                <h2>🔐 Tu Código QR de Asistencia</h2>
                <p>Presenta este QR al proveedor al inicio del tour.<br>
                   El guía lo escaneará para confirmar tu asistencia.</p>
                <div class="qr-box">
                    <img src="{{ $qrUrl }}" alt="QR Atti Tours — {{ $reserva->ticket_codigo }}">
                </div>
                <div class="qr-note">
                    Código: <strong>{{ $reserva->ticket_codigo }}</strong> · Un uso por persona
                </div>
            </div>

        </div>{{-- /ticket-body --}}
    </div>{{-- /ticket --}}

    {{-- ── FOOTER ── --}}
    <div class="footer">
        <a href="{{ url('/') }}" class="cta-btn">Ver mis Reservas →</a>
        <p style="margin-top:20px;">
            ¿Tienes preguntas? Escríbenos a
            <a href="mailto:hola@attitours.com">hola@attitours.com</a><br>
            o visítanos en <a href="{{ url('/') }}">attitours.com</a>
        </p>
        <p style="margin-top:12px; font-size:10px; color:#334155;">
            © {{ date('Y') }} Atti Tours. Todos los derechos reservados.<br>
            Este correo fue enviado a {{ $reserva->correo_cliente }} por haber realizado una reserva.
        </p>
    </div>

</div>
</body>
</html>
