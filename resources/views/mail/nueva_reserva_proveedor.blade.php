<!DOCTYPE html>
<!--
 * @file nueva_reserva_proveedor.blade.php
 * @description Plantilla HTML del correo que avisa al proveedor de una reserva pagada de sus tours.
 *              Mismo estilo claro/corporativo que mail.reserva_confirmada.
 * @date 2026-09-28
-->
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nueva Reserva — Attitour</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            background-color: #f1f5f9;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            color: #334155;
        }
        .wrapper { max-width: 600px; margin: 0 auto; padding: 32px 16px; }
        .header {
            text-align: center; padding: 36px 32px 28px; background: #ffffff;
            border-radius: 20px 20px 0 0; border: 1px solid #e2e8f0; border-bottom: none;
        }
        .logo { font-size: 26px; font-weight: 900; letter-spacing: 3px; color: #007a63; }
        .header h1 { font-size: 21px; font-weight: 800; color: #0f172a; margin-top: 14px; }
        .header p  { font-size: 13px; color: #64748b; margin-top: 6px; }
        .body { background: #ffffff; border: 1px solid #e2e8f0; border-top: none; border-bottom: none; padding: 24px 28px; }
        .ticket-label { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 2px; color: #94a3b8; }
        .ticket-code { font-size: 16px; font-weight: 900; color: #007a63; letter-spacing: 2px; font-family: monospace; margin-bottom: 20px; }
        .section-title { font-size: 9px; font-weight: 700; text-transform: uppercase; letter-spacing: 2px; color: #94a3b8; margin-bottom: 12px; }
        .client-box { background: #f0fdfa; border: 1px solid #99f6e4; border-radius: 12px; padding: 14px 16px; margin-bottom: 24px; font-size: 12px; line-height: 1.8; }
        .client-box strong { color: #0f172a; }
        .client-box a { color: #007a63; text-decoration: none; }
        .tour-item { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 14px 16px; margin-bottom: 10px; }
        .tour-name { font-size: 13px; font-weight: 700; color: #0f172a; margin-bottom: 6px; }
        .tour-meta { font-size: 11px; color: #64748b; line-height: 1.7; }
        .tour-meta span { color: #007a63; font-weight: 600; }
        .note { font-size: 11px; color: #64748b; line-height: 1.6; margin-top: 16px; }
        .footer {
            background: #f8fafc; border: 1px solid #e2e8f0; border-top: none;
            border-radius: 0 0 20px 20px; padding: 24px 28px; text-align: center;
        }
        .footer p { font-size: 11px; color: #94a3b8; line-height: 1.7; }
        .footer a { color: #007a63; text-decoration: none; }
    </style>
</head>
<body>
<div class="wrapper">

    <div class="header">
        <div class="logo">ATTITOUR</div>
        <h1>¡Tienes una nueva reserva! 🧭</h1>
        <p>Hola {{ $proveedor->representante_nombre ?: $proveedor->nombre_empresa }}, un cliente acaba de pagar uno de tus tours.</p>
    </div>

    <div class="body">
        <div class="ticket-label">Código de Reserva</div>
        <div class="ticket-code">{{ $reserva->ticket_codigo }}</div>

        <div class="section-title">Datos del Cliente</div>
        <div class="client-box">
            <strong>Nombre:</strong> {{ $reserva->nombre_cliente }}<br>
            <strong>Correo:</strong> <a href="mailto:{{ $reserva->correo_cliente }}">{{ $reserva->correo_cliente }}</a><br>
            <strong>Teléfono:</strong> {{ $reserva->telefono_cliente ?: '—' }}
        </div>

        <div class="section-title">Tours Reservados</div>
        @foreach($detalles as $det)
            <div class="tour-item">
                @php
                    $titulo = $det->tour->titulo ?? 'Tour';
                    if (is_array($titulo)) $titulo = $titulo['es'] ?? reset($titulo);
                @endphp
                <div class="tour-name">{{ $titulo }}</div>
                <div class="tour-meta">
                    📅 Fecha: <span>{{ \Carbon\Carbon::parse($det->fecha_seleccionada)->locale('es')->translatedFormat('d M Y') }}</span><br>
                    @if($det->horario) 🕘 Horario: <span>{{ $det->horario }}</span><br> @endif
                    👥 Personas: <span>{{ $det->cantidad_personas }}</span>
                    @if($det->cantidad_adultos || $det->cantidad_menores || $det->cantidad_infantes)
                        ({{ $det->cantidad_adultos ?? 0 }} adultos, {{ $det->cantidad_menores ?? 0 }} menores, {{ $det->cantidad_infantes ?? 0 }} infantes)
                    @endif
                    <br>
                    🔒 Modalidad: <span>{{ $det->es_privado ? 'Privado' : 'Compartido' }}</span><br>
                    @if($det->idioma_seleccionado) 🗣️ Idioma: <span>{{ $det->idioma_seleccionado }}</span><br> @endif
                    @if($det->hotel_nombre)
                        🏨 Hotel: <span>{{ $det->hotel_nombre }}</span>@if($det->hotel_lobby) — Lobby: <span>{{ $det->hotel_lobby }}</span>@endif<br>
                    @endif
                    @if($det->pickup_horario) 🚐 Pickup: <span>{{ $det->pickup_horario }}</span><br> @endif
                    @if($det->folio_proveedor_externo) 🧾 Folio: <span>{{ $det->folio_proveedor_externo }}</span> @endif
                </div>
            </div>
        @endforeach

        @if($reserva->monto_pendiente_destino_usd > 0)
            <p class="note">💵 Esta reserva tiene un saldo pendiente que el cliente pagará en destino.</p>
        @endif
        <p class="note">El cliente presentará su código QR al inicio del tour. Escanéalo desde tu panel para confirmar su asistencia.</p>
    </div>

    <div class="footer">
        <p>
            Consulta el detalle completo en tu <a href="{{ url('/dashboard') }}">panel de proveedor</a>
            y responde los mensajes del cliente desde <a href="{{ route('dashboard.mensajes.index') }}">Mensajes</a>.<br>
            © {{ date('Y') }} Attitour. Este correo fue enviado a {{ $proveedor->correo }}.
        </p>
    </div>

</div>
</body>
</html>
