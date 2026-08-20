# Arquitectura actual de Attitour (base sobre la que se construye)

Este documento describe lo que **ya existe** en el código, verificado directamente contra el repositorio (no contra los documentos aspiracionales de `iadocs/`, que en algunos puntos ya divergieron de la implementación real).

## Stack

- **Backend**: Laravel 13, PHP.
- **Panel de gestión**: Blade + JavaScript plano (vanilla) + Tailwind CSS 4. **No hay Livewire, Filament, Inertia ni Alpine.js.** Toda la interactividad del panel (pestañas, modales, calendario) se maneja con funciones JS escritas a mano dentro del propio Blade.
- **Vista única del panel**: `resources/views/dashboard/index.blade.php` (~3300 líneas). Es un layout tipo SPA-por-pestañas: un `switchTab('metrics'|'proveedores'|'tours'|'usuarios')` en JS oculta/muestra `<div id="tab-content-...">`, y los botones de pestaña están en la parte superior del archivo.
- **Controlador único del panel**: `app/Http/Controllers/DashboardController.php` (~1170 líneas) — concentra login de admin/proveedor, métricas, CRUD de proveedores, CRUD de tours, disponibilidad (`tour_fechas`), gestión de usuarios y QR de asistencia.
- **Colas**: `QUEUE_CONNECTION=database` ya configurado, con tabla `jobs` migrada. Se puede usar sin infraestructura adicional.
- **Llamadas HTTP salientes**: se usan con el facade `Http` de Laravel (Guzzle por debajo), no hay un cliente HTTP genérico propio.

## Modelo de datos relevante

### `tours` (tabla `tours`, PK string tipo `tour_cancun`)
Modelo: `app/Models/Tour.php`. Campos `fillable`:
`id, proveedor_id, titulo, descripcion_corta, descripcion_larga, ubicacion, punto_encuentro, pais, precio_base_usd, tipo_modalidad, tarifas_privadas, anticipo_porcentaje, duracion, imagen_destacada, galeria, galeria_experiencias, cupo_maximo, tags, horarios, itinerario, incluye, no_incluye`.

- `titulo`, `descripcion_corta`, `descripcion_larga` son **JSON localizado** (`{"es": "...", "en": "...", "zh": "..."}`).
- `galeria` y `galeria_experiencias` son arrays JSON de URLs (no hay tabla de galería separada).
- Relaciones: `belongsTo(Proveedor)`, `hasMany(TourFecha)`, `hasMany(ReservaTour)`.
- **Todos estos campos son obligatorios hoy** para crear un tour vía `DashboardController::storeTour()` (ver validación abajo) — esto es exactamente lo que hace que un tour "no esté completo" cuando viene de una API externa.

### `proveedores`
Modelo: `app/Models/Proveedor.php`: `nombre_empresa, descripcion, rfc, correo, representante_nombre, representante_telefono, comision_porcentaje, foto_url`. Un Tour siempre pertenece a un Proveedor (`proveedor_id` NOT NULL).

### `tour_fechas` (disponibilidad)
Modelo: `app/Models/TourFecha.php`: `tour_id, fecha, horario, es_privado, cupo_maximo, cupo_reservado`, único por `(tour_id, fecha, horario)`. Esto lo administra Attitour internamente (Admin/Proveedor), no cambia con esta nueva función — los tours importados usan el mismo mecanismo de disponibilidad que cualquier otro tour una vez publicados.

### `reservas` / `reserva_tours` (reserva y su detalle)
`app/Models/Reserva.php` (cabecera: cliente, montos, `estado`, `ticket_codigo`, campos de Stripe) y `app/Models/ReservaTour.php` (línea de detalle: `tour_id`, `fecha_seleccionada`, `horario`, `cantidad_personas`, `es_privado`).

## Validación actual al crear/editar un Tour

`DashboardController::storeTour()` (línea ~300) exige como obligatorios: `titulo, descripcion_corta, descripcion_larga, precio_base_usd, duracion, ubicacion, pais, proveedor_id, tipo_modalidad`. Genera el `id` con `Str::slug()` del título (`tour_` + slug). Esta es la misma validación que la nueva función de "Completar y Publicar" debe reutilizar — no se crea un formulario nuevo, se reutiliza el existente.

## Flujo de reserva y el punto de enganche para notificaciones salientes

1. `CheckoutController::placeOrder()` — valida datos del cliente, bloquea filas de `tour_fechas` (`lockForUpdate`), crea `Reserva` en estado `Pendiente` + sus `ReservaTour`, calcula comisión/anticipo, crea la sesión de Stripe Checkout vía `StripeCheckoutService::crearCheckoutSession()`.
2. Stripe confirma el pago (webhook `StripeWebhookController` o fallback en `CheckoutController::success()`), lo que llama a `StripeCheckoutService::confirmarPago()`.
3. `confirmarPago()` marca la reserva como `Pagada`, crea/vincula el `User`, envía el correo de confirmación y — **este es el patrón que ya existe y que replicamos** — llama a `notificarWebhookConfirmacion()`, que hace un `Http::timeout(5)->post($webhookUrl, [...])` hacia una URL configurada en `config('services.n8n.confirmacion_webhook_url')`, envuelto en try/catch que solo registra el error en el log si falla (no revierte el pago).

Este es exactamente el punto donde se debe enganchar la notificación hacia la API externa cuando el tour reservado tiene `origen = api_externa` (ver [02-diseno-de-la-solucion.md](02-diseno-de-la-solucion.md)), aunque con una diferencia importante: la notificación al n8n actual es "dispara y olvida" (si falla, solo se loguea); para la notificación a la API del proveedor externo sí queremos **reintentos y auditoría**, porque de eso depende que el proveedor real agende el viaje.

## Convención de configuración de servicios externos

`config/services.php` sigue el patrón `'servicio' => ['key' => env('SERVICIO_KEY')]` (ver `stripe`, `n8n`). Sin embargo, como la nueva función admite **múltiples conexiones a distintas APIs de terceros** (no una sola credencial fija), las credenciales de cada conexión no viven en `.env`/`config/services.php`, sino en una tabla propia (ver siguiente documento) — `.env` solo aporta una bandera global de sistema para forzar el modo Sandbox.

## Regla de negocio documentada relevante

`iadocs/infodb.txt` (línea 16) menciona: *"No salga el nombre de los proveedores / API De calendario"* — la marca del proveedor no debe exponerse al cliente final. Esto es coherente con el diseño de esta función: el tour importado se "adopta" como un tour propio de Attitour de cara al público, independientemente de dónde vino.
