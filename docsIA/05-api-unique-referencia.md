# Referencia técnica — API "Unique Reservaciones"

Resumen de trabajo del documento `Unique WS Reservaciones DU.pdf` (provisto por el negocio), reorganizado para uso de implementación. Ante cualquier duda de detalle, el PDF original es la fuente de verdad.

Base URL: `https://apiv1.uniqcloud.com/rsrv`

## Autenticación (sesión por token, no API key estática)

```
POST /sessions
{ "user": "...", "password": "...", "modo": "SANDBOX" | "LIVE", "uniqid_empresa": "abc123" }
→ { "user": "...", "auth_token": "269b7fd0..." }
```

El `auth_token` se envía en cada llamada posterior en el header `Authorization`. **No hay una vida útil documentada del token** en el PDF — hay que asumir que puede expirar y diseñar el cliente para volver a hacer login si una llamada responde con error de autenticación (ver [04-preguntas-abiertas.md](04-preguntas-abiertas.md), pregunta actualizada).

Esto **cambia el diseño de `api_conexiones`** respecto al borrador inicial: no se guarda un `api_key` estático, sino `usuario`, `password` (ambos cifrados) y `uniqid_empresa`. El `auth_token` es estado en caliente (se obtiene, se cachea con expiración corta, se renueva), no una credencial que el Admin captura a mano.

## Endpoints de catálogo (para "Actualizar Catálogo")

Traer el catálogo completo de Unique **no es una sola llamada** — requiere dos niveles:

1. `GET /locations` → lista de locaciones (`Id`, `Locacion`), ej. `{"Id":"PDC","Locacion":"Playa del Carmen"}`.
2. Por cada locación: `POST /services { "locacion": "PDC" }` → lista de actividades/tours de esa locación:
   ```json
   {
     "Id": "PI",
     "unq": "123abc",
     "Servicio": "Tour Uno",
     "Turno" | "Horario": ["1er Turno" | "9:00"],
     "Titulo": "SNORKEL DISCOVERY",
     "Descripcion_corta": "...",
     "Descripcion_larga": "...",
     "imagen": "https://...",
     "Descripcion_movil": "...",
     "teaser": "...",
     "privado": 0
   }
   ```
   El campo horario puede llamarse `Turno` **o** `Horario` según configuración de la empresa en Unique — el mapeo debe tolerar ambos nombres.
3. Opcionalmente, `POST /prices { "fecha", "locacion", "servicio"? }` → precio del día para una o todas las actividades de la locación. **El precio no viene en `/services`**, es una llamada aparte y depende de una fecha — para el preview en la bandeja de importados se puede consultar con la fecha del día del sync, dejando claro que es un precio de referencia, no el definitivo.

### Mapeo a `tours_importados`

| Campo Unique | Campo interno |
|---|---|
| `locacion` (del request) + `Id` | Identificador compuesto → `external_id` (ver nota abajo) |
| `Titulo` | `titulo_preview` |
| `imagen` | `imagen_preview` |
| `Descripcion_corta` / `Descripcion_larga` | Guardados en `payload_raw`, y sí se pueden precargar directamente en el formulario de Tour (a diferencia de lo asumido en la primera versión de este diseño, **Unique sí entrega descripciones**, aunque solo en un idioma — ver pregunta abierta sobre idiomas) |
| `precio` (de `/prices`) | `precio_preview` |
| `locacion` (Id) | Nuevo campo `locacion_externa_id` — necesario porque **todas** las llamadas posteriores de Unique (precios, disponibilidad, pickup, crear reserva) requieren `locacion` + `servicio` juntos, no solo el id del servicio. |

> **Nota sobre `external_id`**: el campo `unq` aparece en el primer ejemplo del PDF pero no en el segundo — no está garantizado que todas las actividades lo traigan. Por seguridad, el identificador único que se usa internamente debe ser el compuesto `locacion:Id` (ej. `PDC:PI`), no depender de `unq`.

## Endpoint de disponibilidad (consulta, no reserva)

```
POST /sold_out
{ "fecha", "fecha_fin"?, "locacion", "servicio", "horario"?, "idioma"? }
→ [{ "disponibles": 10, "sold_out": false, "consulta": "cache"|"normal", "horario", "idioma"? }]
```

Esto es un **chequeo de cupos del lado de Unique**, independiente del `tour_fechas` propio de Attitour. El diseño ya definido mantiene la disponibilidad interna (`tour_fechas.cupo_maximo`/`cupo_reservado`) como la fuente de verdad para lo que el cliente ve en Attitour — este endpoint se usa opcionalmente al momento de reservar (parámetro `disponibilidad` en `/create`, ver abajo) para que Unique valide su propio cupo antes de confirmar.

## Endpoint de creación de reserva — el punto clave del flujo pedido

```
POST /create
{
  "fecha", "cliente", "disponibilidad": 1|0, "pax", "paxn", "pax_infantes",
  "locacion", "servicio", "horario", "idioma",
  "detalles": { "referencia", "email", "total" },
  "transportacion": { "hotel", "pick_up" },
  "comentarios"
}
→ { "id": 206, "confirma": "WB10215768", "fecha", "cliente", "pax", "locacion", "servicio", "status": "Activa", "detalles", "transportacion", "comentarios" }
```

**Esto es exactamente el "avisar que hay un nuevo registro en la disponibilidad" del pedido original.** No es un simple webhook de notificación — es una llamada que **crea la reserva real en el sistema de Unique**, y Unique responde con un folio de confirmación (`confirma`) que hay que conservar.

Notas del propio PDF:
- `disponibilidad: 1` le pide a Unique que valide cupo al guardar; `0` se usa si ya se consultó `/sold_out` antes, para no duplicar la validación. **Decisión final (confirmada con Unique el 2026-08-19)**: se detectó en pruebas que `disponibilidad: 1` devolvía `{"errors":"SOLD OUT"}` de forma consistente en Sandbox incluso con cupo real confirmado por `/sold_out`; Unique confirmó que **es un bug exclusivo de su ambiente Sandbox y no ocurre en producción**. Por eso el valor ahora depende del modo de la conexión: `1` en producción (Unique valida su propio cupo en vivo — la opción más segura, evita que Attitour y Unique se desincronicen) y `0` en Sandbox (workaround del bug confirmado de ese ambiente). Ver `TourApiNotificationService::construirPayload()`.
- `paxn` = menores, `pax_infantes` = infantes. Attitour hoy solo maneja `cantidad_personas` como un total en `reserva_tours` — **no distingue adultos/menores/infantes**. Es un campo que no existe todavía en el modelo de reserva de Attitour (ver pregunta abierta).
- `transportacion.hotel` / `pick_up` — Attitour no tiene hoy un flujo de captura de hotel/pickup del cliente en el checkout. Si el tour importado de Unique lo requiere, es un dato adicional a pedir en el checkout que hoy no existe (ver pregunta abierta).
- La respuesta trae `confirma` (folio) e `id` (id interno de Unique) — deben guardarse en la tabla de auditoría (`tour_api_notificaciones`) y, para trazabilidad completa, referenciarse también desde `reserva_tours` o `reservas` (ver ajuste al modelo de datos en [02-diseno-de-la-solucion.md](02-diseno-de-la-solucion.md)).

## Otros endpoints disponibles (no bloqueantes para el alcance actual, útiles a futuro)

| Endpoint | Uso |
|---|---|
| `GET /hoteles` | Catálogo de hoteles y lobbies de Unique — necesario solo si se implementa el flujo de `transportacion` en el checkout. |
| `POST /pickup` | Hora exacta de recogida según hotel/horario — mismo caso que arriba. |
| `GET /idiomas` | Catálogo de idiomas soportados por Unique para el tour (usado en `/sold_out` y `/create`). |
| `GET /get/{cupon}` | Consultar una reserva ya creada en Unique — útil para verificación/soporte post-venta. **Importante (confirmado con Unique el 2026-08-19)**: pese al nombre, `{cupon}` debe ser la **`referencia`** que Attitour envió en `/create` (nuestro `Reserva::ticket_codigo`), no el `confirma`/folio que Unique devuelve. Buscar por `confirma` responde `{"errors":"Booking does not exist"}` aunque la reserva sí exista — no es un bug de Unique, es que `referencia` es el identificador que ellos esperan para esta consulta porque es el dato que Attitour controla y garantiza único. |
| `POST /reservations` | Listado de reservas por fecha y locación en Unique — útil para conciliación/auditoría, no para el flujo de reserva en sí. |

## Impacto en el diseño ya escrito

Respecto a [02-diseno-de-la-solucion.md](02-diseno-de-la-solucion.md), esta información concreta obliga a ajustar:

1. `api_conexiones`: reemplazar `api_key` por `usuario`, `password` (cifrados), `uniqid_empresa`, y agregar estado de sesión (`auth_token`, `auth_token_expira_at`) cacheado en runtime.
2. `tours_importados`: agregar `locacion_externa_id` (obligatorio, no solo el id del servicio).
3. `TourCatalogSyncService`: debe iterar `GET /locations` → `POST /services` por cada una → opcionalmente `POST /prices`, en vez de una sola llamada de catálogo genérica.
4. La "notificación de disponibilidad al reservar" pasa a ser específicamente una llamada a `POST /create` con el payload documentado arriba, no un webhook genérico — el diseño del Job y la tabla de auditoría deben guardar `unique_reserva_id` y `unique_confirma` de la respuesta.
5. Nuevos campos pendientes de resolver en el checkout de Attitour si Unique los requiere para un tour dado: pasajeros por categoría (adulto/menor/infante), hotel y hora de pickup, idioma del tour.

Estos ajustes ya están reflejados en la versión actualizada de [02-diseno-de-la-solucion.md](02-diseno-de-la-solucion.md) y [04-preguntas-abiertas.md](04-preguntas-abiertas.md).
