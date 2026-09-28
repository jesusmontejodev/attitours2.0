@extends('layouts.app')

<!--
 * @file privacidad.blade.php
 * @description Aviso de Privacidad integral de Attitour conforme a la legislación mexicana de
 *              protección de datos personales en posesión de particulares. Los datos del
 *              responsable se toman de config/legal.php (.env).
 * @date 2026-09-28
-->

@section('title', __('privacy') . ' - Attitour')

@section('content')
@php $legal = config('legal'); @endphp
<div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8 py-12">
    <h1 class="text-2xl sm:text-3xl font-black text-slate-800">Aviso de Privacidad</h1>
    <p class="text-xs text-slate-500 font-semibold mt-2">Última actualización: {{ $legal['fecha_actualizacion'] }}</p>

    <div class="legal-doc mt-8 flex flex-col gap-6 text-sm text-slate-600 leading-relaxed">

        <section>
            <h2>1. Identidad y domicilio del responsable</h2>
            <p><strong>{{ $legal['razon_social'] }}</strong> ("Attitour"), con domicilio en {{ $legal['domicilio'] }}, es responsable del tratamiento de los datos personales que nos proporcionas a través de este sitio web y aplicación, conforme a la Ley Federal de Protección de Datos Personales en Posesión de los Particulares y demás normativa aplicable.</p>
            <p>Para cualquier asunto relacionado con tus datos personales puedes escribirnos a <a href="mailto:{{ $legal['email_privacidad'] }}">{{ $legal['email_privacidad'] }}</a>.</p>
        </section>

        <section>
            <h2>2. Datos personales que recabamos</h2>
            <p>Dependiendo de cómo uses la plataforma, podemos recabar:</p>
            <ul>
                <li><strong>Identificación y contacto:</strong> nombre, correo electrónico, número de teléfono (incluida la clave de país) y país de residencia.</li>
                <li><strong>Datos de tu cuenta:</strong> contraseña (almacenada cifrada, nunca en texto plano) y, si decides subirla, tu foto de perfil.</li>
                <li><strong>Datos de la reserva:</strong> tours reservados, fechas, horarios, número de viajeros por rango de edad (adultos, menores e infantes), modalidad (privado o compartido), idioma preferido, hotel y lobby de recogida, y montos pagados o pendientes.</li>
                <li><strong>Comunicaciones:</strong> los mensajes que intercambias con nosotros o con el proveedor del tour a través del chat interno.</li>
                <li><strong>Datos técnicos:</strong> dirección IP, tipo de navegador y datos de sesión necesarios para el funcionamiento y la seguridad del sitio, así como registros de validación de tu código QR de asistencia.</li>
            </ul>
            <p><strong>No recabamos datos de tarjetas bancarias.</strong> Los pagos se procesan directamente en la plataforma de Stripe; Attitour solo recibe la confirmación del pago y un identificador de la transacción.</p>
            <p>No solicitamos datos personales sensibles. Respecto de menores de edad, únicamente registramos cuántos viajan en la reserva; la reserva debe hacerla un adulto responsable.</p>
        </section>

        <section>
            <h2>3. Finalidades del tratamiento</h2>
            <p><strong>Finalidades primarias</strong> (necesarias para darte el servicio):</p>
            <ul>
                <li>Crear y administrar tu cuenta de usuario.</li>
                <li>Procesar tus reservas y pagos, y emitir tu ticket y código QR de asistencia.</li>
                <li>Compartir con el proveedor que opera el tour los datos necesarios para prestarlo (ver sección 4).</li>
                <li>Enviarte confirmaciones, avisos y cambios sobre tu reserva por correo electrónico y/o WhatsApp.</li>
                <li>Atender tus mensajes, dudas, aclaraciones, cancelaciones y reembolsos.</li>
                <li>Validar tu asistencia el día del tour y prevenir fraudes o usos indebidos de la plataforma.</li>
                <li>Cumplir obligaciones legales, fiscales y contables.</li>
            </ul>
            <p><strong>Finalidades secundarias</strong> (no son necesarias para el servicio):</p>
            <ul>
                <li>Enviarte promociones, novedades y recomendaciones de tours.</li>
                <li>Realizar encuestas de calidad y estadísticas internas para mejorar nuestros servicios.</li>
            </ul>
            <p>Si no deseas que tus datos se usen para las finalidades secundarias, puedes indicarlo en cualquier momento escribiendo a <a href="mailto:{{ $legal['email_privacidad'] }}">{{ $legal['email_privacidad'] }}</a>. Tu negativa no será motivo para negarte el servicio.</p>
        </section>

        <section>
            <h2>4. Transferencias y encargados</h2>
            <p>Para prestar el servicio compartimos datos personales con:</p>
            <ul>
                <li><strong>Proveedores de tours:</strong> el operador de cada tour que reservas recibe tu nombre, correo, teléfono y los datos de la reserva (fecha, horario, viajeros, hotel de recogida) para prestar el servicio y comunicarse contigo. Esta transferencia es necesaria para cumplir el contrato que celebras con nosotros, por lo que no requiere tu consentimiento adicional.</li>
                <li><strong>Encargados que nos prestan servicios</strong>, que tratan los datos solo por nuestra cuenta y bajo nuestras instrucciones:
                    <ul>
                        <li>Stripe (procesamiento de pagos).</li>
                        <li>Meta / WhatsApp Business (envío de confirmaciones por WhatsApp).</li>
                        <li>Servicios de correo electrónico, alojamiento y automatización de notificaciones.</li>
                        <li>Sistemas de reservas de operadores externos, para los tours que se confirman directamente en su plataforma.</li>
                        <li>QuickChart (generación de la imagen del código QR, que contiene únicamente un código de verificación, no tus datos de contacto).</li>
                        <li>Google Translate (traducción automática de la página al idioma que elijas).</li>
                    </ul>
                </li>
                <li><strong>Autoridades competentes</strong>, cuando exista un requerimiento legal.</li>
            </ul>
            <p>Algunos de estos proveedores pueden estar ubicados fuera de México; en esos casos se transfieren los datos con las mismas finalidades descritas en este aviso.</p>
        </section>

        <section>
            <h2>5. Derechos ARCO y revocación del consentimiento</h2>
            <p>Tienes derecho a <strong>Acceder</strong> a tus datos, <strong>Rectificarlos</strong> si son inexactos, <strong>Cancelarlos</strong> cuando consideres que no se requieren para las finalidades señaladas y <strong>Oponerte</strong> a su tratamiento para fines específicos (derechos ARCO). También puedes revocar tu consentimiento o limitar el uso de tus datos.</p>
            <p>Para ejercerlos envía una solicitud a <a href="mailto:{{ $legal['email_privacidad'] }}">{{ $legal['email_privacidad'] }}</a> indicando:</p>
            <ul>
                <li>Tu nombre y un medio para comunicarte la respuesta.</li>
                <li>Un documento que acredite tu identidad (o la de tu representante legal).</li>
                <li>La descripción clara de los datos y del derecho que deseas ejercer.</li>
                <li>Cualquier dato que facilite localizar tu información (por ejemplo, tu código de reserva).</li>
            </ul>
            <p>Te responderemos en un plazo máximo de 20 días hábiles y, de ser procedente, la haremos efectiva dentro de los 15 días hábiles siguientes.</p>
            <p>Además, desde tu panel "Mi cuenta" puedes actualizar tus datos y eliminar tu cuenta en cualquier momento. Conservaremos únicamente la información de reservas que debamos mantener por obligaciones legales o fiscales.</p>
        </section>

        <section>
            <h2>6. Conservación y seguridad</h2>
            <p>Conservamos tus datos mientras tengas una cuenta activa o durante el tiempo necesario para cumplir las finalidades de este aviso y las obligaciones legales aplicables. Aplicamos medidas de seguridad administrativas, técnicas y físicas para proteger tus datos contra daño, pérdida, alteración o acceso no autorizado.</p>
        </section>

        <section>
            <h2>7. Cookies y tecnologías similares</h2>
            <p>Usamos cookies estrictamente necesarias para mantener tu sesión, tu carrito de compras, el idioma seleccionado y la protección contra ataques (CSRF), así como almacenamiento local del navegador para recordar que ya viste el aviso de privacidad. El traductor de Google puede usar cookies propias para aplicar el idioma elegido. No usamos cookies de publicidad. Puedes deshabilitar las cookies desde tu navegador, pero algunas funciones (como iniciar sesión o reservar) podrían dejar de funcionar.</p>
        </section>

        <section>
            <h2>8. Cambios al aviso de privacidad</h2>
            <p>Podemos modificar este aviso por cambios legales, en nuestros servicios o en nuestras prácticas. Publicaremos la versión vigente en esta página, con su fecha de actualización.</p>
        </section>

        <section>
            <h2>9. Autoridad</h2>
            <p>Si consideras que tu derecho a la protección de datos personales ha sido vulnerado, puedes acudir ante la autoridad competente en materia de protección de datos personales en México.</p>
        </section>

        <p class="text-xs text-slate-400">Este documento se redacta en español; las traducciones automáticas a otros idiomas son solo de referencia y, en caso de discrepancia, prevalece la versión en español.</p>
    </div>
</div>

@include('legal._estilos')
@endsection
