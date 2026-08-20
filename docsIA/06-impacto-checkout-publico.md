# Impacto en el checkout público (decisiones ya tomadas con el negocio)

Estas decisiones se tomaron explícitamente el 2026-08-06, resolviendo las preguntas #3, #4 y #6 de la versión anterior de [04-preguntas-abiertas.md](04-preguntas-abiertas.md):

1. **Pax por categoría**: se agrega el desglose adultos/menores/infantes al checkout (no se aproxima con menores=0/infantes=0).
2. **Transportación**: hotel + hora de pickup **sí son necesarios**, se agregan al checkout.
3. **Idioma**: se agrega un **selector explícito** en el checkout (no se infiere del locale de sesión).
4. **Proveedor interno**: un solo `Proveedor` de Attitour agrupa todos los tours de la conexión Unique (no uno por locación).

Estas decisiones amplían el alcance: ya no es solo el panel de gestión, también se modifica el flujo público de compra (`CartController`, `CheckoutController`, vistas de carrito/checkout). Este documento describe ese impacto.

## Regla general

Estos campos adicionales (pax por categoría, idioma, hotel, pickup) **solo aplican cuando el tour en el carrito tiene `origen = api_externa`** (hoy, en la práctica, cualquier tour importado de Unique). Los tours internos (`origen = interno`, la mayoría del catálogo) no cambian su flujo de checkout actual — sin esta condición, se complicaría innecesariamente la compra de todo el catálogo existente.

## Cambios de modelo de datos

Nuevas columnas nullable en `reserva_tours` (aditivas, no afectan reservas existentes):

| Campo | Tipo | Notas |
|---|---|---|
| `cantidad_adultos` | int nullable | Reemplaza el uso exclusivo de `cantidad_personas` cuando el tour requiere desglose |
| `cantidad_menores` | int nullable | Mapea a `paxn` en `POST /create` de Unique |
| `cantidad_infantes` | int nullable | Mapea a `pax_infantes` |
| `idioma_seleccionado` | string nullable | Código de idioma de Unique (ej. `ESP`), elegido explícitamente por el cliente |
| `hotel_nombre` | string nullable | Elegido del catálogo `GET /hoteles` de Unique |
| `hotel_lobby` | string nullable | Opcional, si el hotel tiene varios lobbies |
| `pickup_horario` | string nullable | Resuelto vía `POST /pickup` de Unique al momento de elegir el hotel |
| `folio_proveedor_externo` | string nullable | El `confirma` que Unique devuelve al crear la reserva (ya mencionado en [02-diseno-de-la-solucion.md](02-diseno-de-la-solucion.md)) |

`cantidad_personas` (columna ya existente) se sigue usando como total general para los cálculos de precio/comisión que ya existen hoy (`cantidad_adultos + cantidad_menores + cantidad_infantes` cuando aplica el desglose).

## Flujo de carrito/checkout actualizado (solo para tours `api_externa`)

1. Al agregar el tour al carrito, en vez de un único contador de personas se piden **adultos / menores / infantes** por separado.
2. Se agrega un **selector de idioma**, poblado desde `GET /idiomas` de Unique.
3. Se agrega un **selector de hotel**, poblado desde `GET /hoteles` de Unique; al elegir hotel (y horario del tour), se consulta `POST /pickup` para mostrar la hora estimada de recogida antes de confirmar la compra.
4. `CartController::add()` / `update()` guardan estos campos adicionales en el item del carrito en sesión, junto a lo que ya se guarda hoy (`tour_id`, `fecha`, `cantidad`, `horario`, etc.).
5. `CheckoutController::placeOrder()` los persiste en las columnas nuevas de `reserva_tours` al crear la reserva.
6. Al confirmarse el pago, `TourApiNotificationService` (ver [02-diseno-de-la-solucion.md](02-diseno-de-la-solucion.md)) arma el payload real de `POST /create` con estos datos ya capturados — ya no hace falta inventar valores por defecto para `paxn`, `pax_infantes`, `transportacion` o `idioma`.

## Nuevas rutas "proxy" (públicas, no del panel admin)

Para no exponer las credenciales de Unique al navegador, el frontend nunca llama a Unique directamente — llama a Attitour, y Attitour llama a Unique del lado del servidor:

```
GET  /cart/tour-api/idiomas?tour_id=...   → UniqueApiClient::idiomas()  (vía la conexión del tour)
GET  /cart/tour-api/hoteles?tour_id=...   → UniqueApiClient::hoteles()
POST /cart/tour-api/pickup                → UniqueApiClient::pickup(locacion, servicio, hotel, horario, lobby?)
```

Las tres resuelven la conexión a partir de `Tour->api_conexion_id` y devuelven vacío/404 si el tour consultado no es `origen = api_externa` — así un tour interno nunca dispara una llamada a Unique por error.

## Nota de alcance

Este es el cambio de **mayor superficie de UI** de todo el proyecto: toca páginas públicas de cara al cliente (carrito, checkout), no solo el panel de administración. Antes de programarlo a fondo conviene validar con el negocio un mockup del nuevo paso de checkout (dónde aparece el desglose de pax, el selector de idioma y el de hotel dentro del flujo actual de compra), para no romper la experiencia de compra de los tours internos que no lo necesitan.

## Ubicación en el plan de fases

Ver [03-seguridad-sandbox-y-plan.md](03-seguridad-sandbox-y-plan.md) — este trabajo se inserta como una fase propia entre "Completar y Publicar" y "Notificación saliente al reservar", porque la notificación depende de que estos datos ya existan en la reserva antes de poder armar el `POST /create` completo.
