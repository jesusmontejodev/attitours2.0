# Diseño de la solución

> Actualizado tras recibir el documento técnico real de la API a integrar primero: **Unique Reservaciones** (`docsIA/Unique WS Reservaciones DU.pdf`). El diseño de abajo ya refleja su contrato concreto (login por token, catálogo en dos niveles, creación de reserva real vía `POST /create`). El detalle endpoint-por-endpoint está en [05-api-unique-referencia.md](05-api-unique-referencia.md); aquí solo se referencia lo que cambia el diseño interno de Attitour. El diseño se mantiene lo bastante genérico en la capa de base de datos como para soportar una segunda API de otro proveedor en el futuro sin rediseñar.

## 1. Flujo funcional (paso a paso)

```
┌──────────────────────────────────────────────────────────────────────────┐
│ 1. Admin abre pestaña "APIs" en /dashboard                               │
│ 2. Admin crea una Conexión API (nombre, URL, credencial, modo=SANDBOX)   │
│ 3. Admin pulsa "Actualizar Catálogo" sobre esa conexión                  │
│    → Login (obtiene token) → recorre locaciones → trae actividades de   │
│      cada una → guarda cada tour en `tours_importados` (pendiente).     │
│      NO toca la tabla `tours` real.                                      │
│ 4. Admin revisa la bandeja "Tours Importados / Pendientes"               │
│    → Descartar   → estado = descartado                                   │
│    → Completar y Publicar → abre el MISMO modal de Tour ya existente,    │
│      precargado con lo que trajo la API. Los campos que faltan (según    │
│      la validación actual de storeTour) quedan vacíos y son obligatorios.│
│ 5. Al guardar → se crea el Tour real (misma lógica de storeTour) con     │
│    origen = api_externa. La fila de `tours_importados` pasa a publicado. │
│ 6. El tour ya es un tour normal: aparece en el catálogo público, se le   │
│    habilitan fechas (`tour_fechas`) igual que a cualquier otro tour.     │
│ 7. Un cliente reserva y paga → StripeCheckoutService::confirmarPago()    │
│    → si el tour reservado tiene origen = api_externa, se encola un Job  │
│      que llama POST /create en Unique (crea la reserva real de su lado) │
│      y guarda el folio de confirmación que Unique devuelve, con         │
│      reintentos y auditoría si la llamada falla.                        │
└──────────────────────────────────────────────────────────────────────────┘
```

Puntos importantes que confirman el alcance pedido:

- Un tour importado **nunca** aparece en `/tours` (catálogo público) hasta que pasa por "Completar y Publicar" — se logra simplemente porque nunca existe como fila de `tours` hasta ese momento; vive en `tours_importados` mientras tanto.
- La disponibilidad (fechas/cupos) la sigue controlando Attitour localmente (`tour_fechas`), tal como ya funciona hoy. Unique no empuja su calendario hacia Attitour — solo aporta el catálogo inicial. Lo que Attitour le manda de vuelta al reservar **no es un simple aviso**, es una llamada que **crea la reserva real dentro de Unique** (`POST /create`), que responde con un folio de confirmación (`confirma`) — ver [05-api-unique-referencia.md](05-api-unique-referencia.md).

## 2. Modelo de datos nuevo (aditivo, no rompe nada existente)

### `api_conexiones`
Una fila por cada API externa configurada por el Admin. Los campos de credencial reflejan el esquema real de Unique (login por usuario/contraseña/empresa, no una API key estática) pero se nombran de forma neutral para poder alojar un segundo proveedor con otro esquema de auth más adelante sin migración destructiva.

| Campo | Tipo | Notas |
|---|---|---|
| `id` | bigint PK | |
| `nombre` | string | Nombre visible en el panel, ej. "Unique Reservaciones — Sandbox" |
| `proveedor_id` | FK nullable → `proveedores.id` | Cada conexión API se vincula a **un único** Proveedor "contenedor" — todos los tours importados por esa conexión se agrupan bajo ese mismo Proveedor (decisión confirmada el 2026-08-06). Reutiliza el aislamiento por proveedor que ya existe (`DashboardController` valida `tour->proveedor_id` contra el usuario). Nada impide que dos conexiones distintas apunten al mismo Proveedor si se desea, pero cada conexión individual solo tiene uno. |
| `base_url` | string | Ej. `https://apiv1.uniqcloud.com/rsrv` |
| `usuario` | string, **cast `encrypted`** | Usuario del login (`user` en la API de Unique) |
| `password` | string, **cast `encrypted`** | Nunca se muestra completa después de guardada |
| `uniqid_empresa` | string | Identificador de empresa que exige el login de Unique |
| `modo` | enum `sandbox` \| `produccion` | default `sandbox`. Se envía literal como `SANDBOX`/`LIVE` en el login. |
| `auth_token` | string nullable, cast `encrypted` | Token de sesión cacheado, se renueva automáticamente cuando expira o cuando una llamada responde con error de autenticación |
| `auth_token_expira_at` | timestamp nullable | Estimado; si no hay expiración documentada, se refresca de forma perezosa ante el primer 401 |
| `headers_extra` | json nullable | Reservado para un segundo proveedor con otro esquema de auth |
| `activa` | boolean default true | |
| `ultimo_sync_at` | timestamp nullable | |
| `ultimo_sync_status` | string nullable | `ok` / `error: mensaje` |

### `tours_importados` (la bandeja de "no implementados")

| Campo | Tipo | Notas |
|---|---|---|
| `id` | bigint PK | |
| `api_conexion_id` | FK → `api_conexiones` | |
| `locacion_externa_id` | string | El `Id` de la locación en Unique (ej. `PDC`) — obligatorio porque toda llamada posterior (precios, disponibilidad, crear reserva) lo requiere junto con el servicio |
| `external_id` | string | Compuesto `locacion:Id_servicio` (ej. `PDC:PI`) — no se usa el campo `unq` de Unique como único identificador porque el PDF no garantiza que siempre venga |
| `payload_raw` | json | Respuesta cruda completa de `/services` (y precio del día si se consultó), se conserva siempre |
| `titulo_preview` | string nullable | De `Titulo` |
| `descripcion_corta_preview` / `descripcion_larga_preview` | text nullable | Unique sí las entrega en `/services`; se precargan en el formulario, quedando pendiente solo la traducción a los otros idiomas (Attitour requiere `es`/`en`/`zh`) |
| `imagen_preview` | string nullable | De `imagen` |
| `precio_preview` | decimal nullable | De `/prices`, referencial (depende de fecha) |
| `estado` | enum `pendiente` \| `descartado` \| `publicado` | default `pendiente` |
| `tour_id` | FK nullable → `tours` | Se llena al publicar |
| `fecha_importado` | timestamp | |
| `fecha_actualizado_catalogo` | timestamp | Última vez que apareció en un sync (para detectar tours que Unique ya retiró de su catálogo) |

Único por `(api_conexion_id, external_id)` — un mismo tour de Unique no se duplica en syncs repetidos, se actualiza el preview y `fecha_actualizado_catalogo`.

### Extensión de `tours` (columnas nuevas, todas nullable/con default — no afecta tours existentes)

| Campo | Tipo | Notas |
|---|---|---|
| `origen` | string, default `interno` | `interno` \| `api_externa` |
| `api_conexion_id` | FK nullable → `api_conexiones` | |
| `api_tour_id_externo` | string nullable | El `Id` del servicio en Unique |
| `api_locacion_id` | string nullable | El `Id` de la locación en Unique — necesario para poder llamar `POST /create` al reservar |
| `precio_api_referencia_usd` | decimal nullable | El precio tal como lo reporta la API en el último sync (`/prices` de Unique) — **solo informativo**, nunca es lo que se le cobra al cliente |
| `precio_api_actualizado_at` | timestamp nullable | Cuándo se refrescó por última vez `precio_api_referencia_usd` |
| `api_metadata` | json nullable | Resto del `payload_raw` que no mapea a ningún campo propio (ej. `Turno`/`Horario` crudo, `teaser`, `Descripcion_movil`) |

**Dos precios, dos propósitos**: `precio_base_usd` (columna que ya existe) sigue siendo el único precio que se le cobra al cliente y el único que usa todo el flujo de carrito/checkout/comisiones — no cambia su comportamiento. `precio_api_referencia_usd` es nueva y es puramente informativa: le permite al Admin ver, al lado del precio de venta, cuánto reporta la API de origen (para calcular margen o detectar que el proveedor cambió su precio). Al hacer "Completar y Publicar", `precio_base_usd` se **prellena** con el valor que trajo la API pero queda totalmente editable — el Admin puede dejarlo igual o ajustarlo (agregar margen, redondear, etc.) antes de guardar.

### `tour_cambios_precio_api` (detección de cambios de precio, con aprobación manual)

Decisión confirmada el 2026-08-06: cuando un sync posterior detecta que el precio de la API cambió para un tour **ya publicado**, el sistema **no sobrescribe nada solo** — genera un cambio pendiente que el Admin debe aprobar o rechazar explícitamente.

| Campo | Tipo | Notas |
|---|---|---|
| `id` | bigint PK | |
| `tour_id` | FK → `tours` | |
| `api_conexion_id` | FK → `api_conexiones` | |
| `precio_referencia_anterior` | decimal | El `precio_api_referencia_usd` vigente antes de este cambio |
| `precio_referencia_nuevo` | decimal | El precio recién detectado en el sync |
| `precio_venta_actual` | decimal | Snapshot de `precio_base_usd` al momento de detectar — solo para dar contexto en la pantalla de aprobación (cuánto se está cobrando hoy vs. el nuevo costo de referencia) |
| `detectado_at` | timestamp | |
| `estado` | enum `pendiente` \| `aprobado` \| `rechazado` | default `pendiente` |
| `resuelto_por_user_id` | FK nullable → `users` | Quién aprobó/rechazó |
| `resuelto_at` | timestamp nullable | |

Solo puede existir **una fila `pendiente` a la vez por `tour_id`** — si el sync vuelve a detectar diferencia mientras ya hay una pendiente, se actualiza esa misma fila en vez de duplicar.

**Flujo:**
1. `TourCatalogSyncService` amplía su alcance: además de traer tours nuevos a `tours_importados`, en cada "Actualizar Catálogo" también refresca el precio (`/prices` de Unique) de los tours **ya publicados** de esa conexión (`tours` con `origen = api_externa`).
2. Si el precio nuevo difiere de `precio_api_referencia_usd`, se crea (o actualiza) la fila `pendiente` en `tour_cambios_precio_api`. `precio_api_referencia_usd` en `tours` **no se toca todavía**.
3. El Admin ve un contador/badge en el botón de la pestaña "APIs" (ej. "APIs 🔴2") y una sub-sección **"Cambios de Precio Pendientes"** con tarjetas: tour, precio de venta actual, precio de referencia anterior, precio nuevo detectado, y dos botones — **Aprobar** / **Rechazar**.
4. **Aprobar** → actualiza `tours.precio_api_referencia_usd` al nuevo valor y marca la fila `aprobado`. **No** modifica `precio_base_usd` (el precio de venta) automáticamente — esa decisión de ajustar el precio al cliente la sigue tomando el Admin manualmente desde la edición normal del Tour, para no recalcular el margen sin que él lo revise explícitamente.
5. **Rechazar** → marca `rechazado`, no cambia nada en `tours`. Un sync posterior puede volver a proponer el cambio si la diferencia persiste.

### `tour_api_notificaciones` (auditoría de reservas creadas en la API externa)

| Campo | Tipo | Notas |
|---|---|---|
| `id` | bigint PK | |
| `tour_id` | FK → `tours` | |
| `reserva_id` | FK → `reservas` | |
| `reserva_tour_id` | FK → `reserva_tours` | |
| `api_conexion_id` | FK → `api_conexiones` | |
| `payload_enviado` | json | El cuerpo exacto enviado a `POST /create` (fecha, cliente, pax, locacion, servicio, horario, idioma, detalles, transportacion, comentarios) |
| `respuesta_http_status` | int nullable | |
| `respuesta_body` | text nullable | |
| `unique_reserva_id` | string nullable | El `id` que Unique asigna a su reserva |
| `unique_confirma` | string nullable | El folio/voucher de confirmación (`confirma`) que Unique devuelve — es lo que permite luego consultar la reserva vía `GET /get/{cupon}` |
| `estado` | enum `pendiente` \| `enviado` \| `fallido` | |
| `intentos` | int default 0 | |
| `proximo_intento_at` | timestamp nullable | Para reintentos con backoff |

Esta tabla es equivalente en propósito a `webhook_entregas` de `iadocs/propuesta-api-webhook-calendario.docx` (sección 7), aplicada a la dirección de flujo específica de esta función. `unique_confirma` conviene reflejarlo también en `reserva_tours` (columna nueva `folio_proveedor_externo`, nullable) para que el folio de Unique sea visible directamente en el detalle de la reserva sin tener que ir a la tabla de auditoría.

## 3. Rutas, controladores y servicios

### Rutas nuevas
Dentro del grupo ya existente `Route::middleware(['auth'])->prefix('dashboard')` en `routes/web.php`, protegidas con el mismo chequeo `isAdmin()` que usa el resto de `DashboardController`:

```php
Route::prefix('api-conexiones')->group(function () {
    Route::post('/', [ApiConexionController::class, 'store'])->name('dashboard.api.store');
    Route::post('/{id}/update', [ApiConexionController::class, 'update'])->name('dashboard.api.update');
    Route::delete('/{id}', [ApiConexionController::class, 'destroy'])->name('dashboard.api.destroy');
    Route::post('/{id}/sync', [ApiConexionController::class, 'sync'])->name('dashboard.api.sync');

    Route::post('/importados/{id}/descartar', [ApiConexionController::class, 'descartarImportado'])->name('dashboard.api.importados.discard');
    Route::get('/importados/{id}', [ApiConexionController::class, 'showImportado'])->name('dashboard.api.importados.show');
    Route::post('/importados/{id}/publicar', [ApiConexionController::class, 'publicarImportado'])->name('dashboard.api.importados.publish');

    Route::post('/cambios-precio/{id}/aprobar', [ApiConexionController::class, 'aprobarCambioPrecio'])->name('dashboard.api.cambios-precio.approve');
    Route::post('/cambios-precio/{id}/rechazar', [ApiConexionController::class, 'rechazarCambioPrecio'])->name('dashboard.api.cambios-precio.reject');
});
```

### `app/Http/Controllers/ApiConexionController.php` (nuevo)
CRUD de conexiones + acción de sync + gestión de la bandeja de importados + aprobar/rechazar cambios de precio. Sigue el mismo estilo que `DashboardController` (validación inline con `$request->validate()`, chequeo manual de rol).

### `app/Services/UniqueApiClient.php` (nuevo)
Cliente delgado que envuelve el contrato de Unique documentado en [05-api-unique-referencia.md](05-api-unique-referencia.md): `login()` (cachea `auth_token` en la conexión, reintenta el login si una llamada responde 401), `locations()`, `services(locacion)`, `prices(fecha, locacion, servicio?)`, `soldOut(...)`, `create(...)`, `getByCupon(...)`. Todas las llamadas usan el facade `Http` con el `auth_token` en el header `Authorization`, igual que el resto del proyecto usa `Http::` en vez de un cliente Guzzle manual.

### `app/Services/TourCatalogSyncService.php` (nuevo)
Orquesta el sync completo usando `UniqueApiClient`: `locations()` → por cada locación `services($locacionId)` → `prices()` del día → upsert en `tours_importados` por `(api_conexion_id, external_id)` para lo que todavía no está publicado. Adicionalmente, para los tours **ya publicados** de esa conexión, compara el precio recién consultado contra `tours.precio_api_referencia_usd` y, si difiere, crea/actualiza la fila `pendiente` en `tour_cambios_precio_api` (ver modelo de datos arriba) — sin tocar `tours` directamente. Guarda `ultimo_sync_at` y `ultimo_sync_status` en la conexión al terminar (éxito o error), sin dejar excepciones sin capturar hacia el controlador.

### `app/Services/TourApiNotificationService.php` (nuevo)
Arma el payload exacto de `POST /create` (fecha, cliente, pax, `locacion`/`servicio` tomados de `Tour->api_locacion_id`/`api_tour_id_externo`, horario, idioma, `detalles`, `transportacion`, comentarios) a partir de un `ReservaTour`, llama a `UniqueApiClient::create()`, y guarda el resultado (incluyendo `confirma`/`id`) en `tour_api_notificaciones` y en `reserva_tours.folio_proveedor_externo`. Se invoca **desde un Job**, no directamente desde `StripeCheckoutService`, para poder reintentar sin bloquear la confirmación del pago.

### `app/Jobs/NotificarReservaApiExternaJob.php` (nuevo)
`implements ShouldQueue`, con `$tries` y `backoff()` para reintentos automáticos (la cola `database` ya está configurada, no se necesita infraestructura nueva). Se despacha desde `StripeCheckoutService::confirmarPago()`, justo después de `notificarWebhookConfirmacion()`, iterando los `ReservaTour` de la reserva y filtrando los que su `Tour->origen === 'api_externa'`.

## 4. Interfaz del panel de gestión

Nueva pestaña **"APIs"** en `resources/views/dashboard/index.blade.php`, agregada al mismo bloque de botones de pestaña que ya existe (Métricas / Proveedores / Tours / Usuarios), visible solo si `Auth::user()->isAdmin()`.

Contenido de la pestaña:

1. **Tarjetas de conexiones configuradas**: nombre, badge de modo (`SANDBOX` en verde/azul, `PRODUCCIÓN` bloqueado por ahora — ver documento de seguridad), fecha del último sync, botones **"Actualizar Catálogo"**, **Editar**, **Eliminar**.
2. **Modal de alta/edición de conexión**: nombre, URL base, usuario, contraseña, `uniqid_empresa`, modo (Sandbox/Producción), proveedor asociado.
3. **Bandeja "Tours Importados / Pendientes de Revisión"**: grid de tarjetas (imagen, título, precio, ubicación previos) con botones **Descartar** y **Completar y Publicar**.
3b. **Sub-sección "Cambios de Precio Pendientes"**: solo visible cuando hay al menos una fila `pendiente` en `tour_cambios_precio_api`. El botón de la pestaña "APIs" muestra un badge con el conteo (ej. "APIs 🔴2"). Cada tarjeta muestra tour, precio de venta actual, precio de referencia anterior y precio nuevo detectado, con botones **Aprobar** / **Rechazar**.
4. **Completar y Publicar** reutiliza el modal de Tour ya existente (`openCreateTourModal()` / `openEditTourModal()`), con una nueva variante `openCreateTourModalFromImport(id)` que precarga los campos disponibles y deja vacíos (y obligatorios, por la validación ya existente de `storeTour`) los que la API no trajo. El campo de precio se muestra editable (prellenado con el precio de la API) junto a una etiqueta de solo lectura "Precio API: $X USD" para que el Admin vea la referencia mientras decide el precio final de venta.
5. En la tarjeta de un Tour ya publicado con `origen = api_externa` (tanto en la lista de Tours como en su edición), se muestra el precio de venta (`precio_base_usd`, editable como siempre) junto con el precio de referencia de la API (`precio_api_referencia_usd`, solo lectura) — para que el margen sea visible de un vistazo.

No se introduce ningún framework nuevo (Livewire, Alpine, etc.) — se sigue el patrón de Blade + JS plano ya usado en todo el panel, por consistencia con el resto del código.
