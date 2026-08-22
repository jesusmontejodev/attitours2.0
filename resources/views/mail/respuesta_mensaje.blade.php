<!DOCTYPE html>
<!--
 * @file respuesta_mensaje.blade.php
 * @description Plantilla HTML del correo que avisa al cliente de una respuesta nueva en el chat
 *              de su reserva, mismo estilo claro/corporativo que mail.verificar_email.
 * @date 2026-08-21
 * @author Antigravity
-->
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Respuesta nueva — Attitour</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            background-color: #f1f5f9;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            color: #334155;
        }
        .wrapper { max-width: 560px; margin: 0 auto; padding: 32px 16px; }
        .card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 20px;
            overflow: hidden;
        }
        .header { text-align: center; padding: 36px 32px 8px; }
        .logo { font-size: 26px; font-weight: 900; letter-spacing: 3px; color: #007a63; }
        .icon-badge {
            display: inline-flex; align-items: center; justify-content: center;
            width: 56px; height: 56px; border-radius: 50%;
            background: #f0fdfa; border: 1px solid #99f6e4;
            font-size: 26px; margin: 20px 0 4px;
        }
        .body { padding: 8px 36px 8px; text-align: center; }
        .body h1 { font-size: 19px; font-weight: 800; color: #0f172a; margin-bottom: 10px; }
        .body p { font-size: 13px; color: #64748b; line-height: 1.6; margin-bottom: 8px; }
        .mensaje-box {
            margin: 18px 0 4px; padding: 16px 18px;
            background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px;
            font-size: 13px; color: #334155; text-align: left; line-height: 1.6;
        }
        .cta-btn {
            display: inline-block; margin: 20px 0 6px;
            padding: 13px 32px;
            background: #007a63;
            color: #fff !important; font-weight: 800; font-size: 13px;
            border-radius: 10px; text-decoration: none; letter-spacing: 0.5px;
        }
        .footer { padding: 24px 32px 32px; text-align: center; }
        .footer p { font-size: 10px; color: #94a3b8; line-height: 1.7; }
        .footer a { color: #007a63; text-decoration: none; }
    </style>
</head>
<body>
<div class="wrapper">
    <div class="card">
        <div class="header">
            <div class="logo">ATTITOUR</div>
            <div class="icon-badge">💬</div>
        </div>
        <div class="body">
            <h1>Tienes una respuesta nueva</h1>
            <p>Sobre tu reserva <strong>{{ $reserva->ticket_codigo }}</strong>, el operador de tu tour te respondió:</p>

            <div class="mensaje-box">{{ $mensaje->cuerpo }}</div>

            <a href="{{ route('cliente.dashboard') }}" class="cta-btn">Ver conversación completa →</a>

            <p style="margin-top: 18px; font-size: 11px;">Responde directamente desde "Mi Cuenta" en la plataforma.</p>
        </div>
        <div class="footer">
            <p>
                ¿Tienes preguntas? Escríbenos a <a href="mailto:hola@attitours.com">hola@attitours.com</a><br>
                © {{ date('Y') }} Attitour. Todos los derechos reservados.
            </p>
        </div>
    </div>
</div>
</body>
</html>
