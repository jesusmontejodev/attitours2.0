@extends('layouts.app')

<!--
 * @file terminos.blade.php
 * @description Términos y Condiciones de uso de la plataforma y de contratación de tours en
 *              Attitour. La política de cancelación coincide con lo anunciado en el home
 *              (cancelación sin cargo hasta 24 horas antes). Los datos del responsable se toman
 *              de config/legal.php (.env).
 * @date 2026-09-28
-->

@section('title', __('terms') . ' - Attitour')

@section('content')
@php $legal = config('legal'); @endphp
<div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8 py-12">
    <h1 class="text-2xl sm:text-3xl font-black text-slate-800">Términos y Condiciones</h1>
    <p class="text-xs text-slate-500 font-semibold mt-2">Última actualización: {{ $legal['fecha_actualizacion'] }}</p>

    <div class="legal-doc mt-8 flex flex-col gap-6 text-sm text-slate-600 leading-relaxed">

        <section>
            <h2>1. Quiénes somos y aceptación</h2>
            <p>Este sitio web y aplicación es operado por <strong>{{ $legal['razon_social'] }}</strong> ("Attitour"), con domicilio en {{ $legal['domicilio'] }} y correo de contacto <a href="mailto:{{ $legal['email'] }}">{{ $legal['email'] }}</a>.</p>
            <p>Al crear una cuenta o realizar una reserva aceptas estos Términos y Condiciones y el <a href="{{ route('legal.privacidad') }}">Aviso de Privacidad</a>. Si no estás de acuerdo, no utilices la plataforma.</p>
        </section>

        <section>
            <h2>2. Naturaleza del servicio</h2>
            <p>Attitour es una plataforma que comercializa tours y actividades turísticas. <strong>Los tours son operados por proveedores independientes</strong> ("Proveedores"), cuyo nombre se indica en cada reserva y en tu correo de confirmación. Attitour gestiona la venta, el cobro y la comunicación, y el Proveedor es responsable de la prestación del tour: transporte, guías, equipo, seguridad y cumplimiento de los permisos y seguros que exige la ley.</p>
        </section>

        <section>
            <h2>3. Cuentas de usuario</h2>
            <ul>
                <li>Debes ser mayor de edad para crear una cuenta o reservar.</li>
                <li>La información que proporciones debe ser verdadera y estar actualizada. Eres responsable de mantener la confidencialidad de tu contraseña.</li>
                <li>Si reservas sin iniciar sesión, podemos crearte una cuenta automáticamente con el correo de la reserva y enviarte una contraseña temporal para que consultes tus tickets. Te recomendamos cambiarla.</li>
                <li>Puedes eliminar tu cuenta en cualquier momento desde "Mi cuenta".</li>
            </ul>
        </section>

        <section>
            <h2>4. Reservas, precios y pagos</h2>
            <ul>
                <li>Los precios se muestran y se cobran en <strong>dólares estadounidenses (USD)</strong> e incluyen los impuestos aplicables, salvo que se indique lo contrario. Las conversiones a otras monedas son solo de referencia; tu banco puede aplicar su propio tipo de cambio y comisiones.</li>
                <li>El pago se procesa de forma segura a través de Stripe. La reserva queda confirmada solo cuando el pago es aprobado y recibes el correo de confirmación con tu código de reserva y código QR.</li>
                <li>En algunos tours (por ejemplo, privados) se cobra en línea un anticipo y el <strong>saldo restante se paga en destino</strong> el día del tour. El monto pendiente se indica en tu confirmación.</li>
                <li>Los lugares se apartan temporalmente mientras completas el pago; si no lo completas en el tiempo indicado, la reserva se cancela automáticamente y los lugares se liberan.</li>
                <li>Pueden existir cargos no incluidos en el precio (por ejemplo, impuestos de muelle, brazaletes de parques naturales o propinas) cuando así se indique en la descripción del tour.</li>
            </ul>
        </section>

        <section>
            <h2>5. Cancelaciones, cambios y reembolsos</h2>
            <ul>
                <li><strong>Cancelación por el cliente:</strong> puedes cancelar o modificar tu reserva sin cargo hasta <strong>24 horas antes</strong> de la hora de inicio del tour, escribiéndonos a través del chat de tu reserva o a <a href="mailto:{{ $legal['email'] }}">{{ $legal['email'] }}</a>. Las cancelaciones con menos de 24 horas de anticipación y las inasistencias (no show) no son reembolsables.</li>
                <li><strong>Cambios</strong> de fecha u horario están sujetos a la disponibilidad del Proveedor.</li>
                <li><strong>Cancelación por el Proveedor o por Attitour:</strong> si el tour se cancela por clima, condiciones de seguridad, disposiciones de autoridades (por ejemplo, cierre de puertos o áreas naturales), falta de cupo mínimo u otra causa ajena a ti, te ofreceremos una nueva fecha o el reembolso total de lo pagado.</li>
                <li>Los reembolsos se realizan al mismo medio de pago utilizado, y el tiempo en que se reflejen depende de tu banco.</li>
            </ul>
        </section>

        <section>
            <h2>6. El día del tour</h2>
            <ul>
                <li>Presenta tu código QR (impreso o en tu teléfono) al Proveedor al inicio del tour. Cada código es único; no lo compartas.</li>
                <li>Preséntate en el punto y horario de encuentro o de recogida indicados. Si no te presentas a tiempo, el tour puede salir sin ti y se considerará inasistencia.</li>
                <li>Los menores de edad deben ir acompañados por un adulto responsable.</li>
                <li>Debes seguir las indicaciones de seguridad de los guías. El Proveedor puede negar el servicio a quien ponga en riesgo su seguridad o la de otros (por ejemplo, bajo efectos del alcohol), sin derecho a reembolso.</li>
                <li>Algunas actividades tienen restricciones por salud, edad, peso o condición física indicadas en su descripción. Es tu responsabilidad verificar que puedes participar.</li>
            </ul>
        </section>

        <section>
            <h2>7. Comunicación con el Proveedor</h2>
            <p>El chat de tu reserva te permite comunicarte con Attitour y con el Proveedor del tour. Úsalo únicamente para temas relacionados con tu reserva, con respeto y sin compartir contenido ilícito u ofensivo. Podemos revisar las conversaciones para darte soporte y prevenir fraudes.</p>
        </section>

        <section>
            <h2>8. Responsabilidad</h2>
            <p>Attitour responde por la correcta gestión de tu reserva y de tu pago. La prestación del tour es responsabilidad del Proveedor que lo opera. En la medida en que lo permita la ley, Attitour no es responsable por daños derivados de causas de fuerza mayor, caso fortuito, actos de terceros o del propio cliente. Nada en estos términos limita los derechos que te otorga la Ley Federal de Protección al Consumidor.</p>
        </section>

        <section>
            <h2>9. Proveedores</h2>
            <p>Los Proveedores que ofrecen sus tours en Attitour se obligan a prestar el servicio tal como se describe, mantener sus permisos y seguros vigentes, tratar los datos de los clientes solo para prestar el servicio y respetar estos términos. Las condiciones comerciales (como la comisión) se rigen por el acuerdo firmado con cada Proveedor.</p>
        </section>

        <section>
            <h2>10. Propiedad intelectual</h2>
            <p>La marca Attitour, el diseño del sitio y sus contenidos están protegidos. No está permitido copiarlos o usarlos con fines comerciales sin autorización. Las fotografías y descripciones de los tours pueden pertenecer a sus Proveedores.</p>
        </section>

        <section>
            <h2>11. Cambios a estos términos</h2>
            <p>Podemos actualizar estos términos; la versión vigente estará siempre publicada en esta página. Las reservas ya confirmadas se rigen por los términos vigentes al momento de realizarlas.</p>
        </section>

        <section>
            <h2>12. Ley aplicable y quejas</h2>
            <p>Estos términos se rigen por las leyes de los Estados Unidos Mexicanos. Para cualquier queja escríbenos primero a <a href="mailto:{{ $legal['email'] }}">{{ $legal['email'] }}</a> y buscaremos resolverla. Como consumidor, también puedes acudir a la Procuraduría Federal del Consumidor (PROFECO). Para cualquier controversia, las partes se someten a los tribunales competentes del domicilio de Attitour, sin perjuicio de los derechos que la ley otorgue al consumidor.</p>
        </section>

        <p class="text-xs text-slate-400">Este documento se redacta en español; las traducciones automáticas a otros idiomas son solo de referencia y, en caso de discrepancia, prevalece la versión en español.</p>
    </div>
</div>

@include('legal._estilos')
@endsection
