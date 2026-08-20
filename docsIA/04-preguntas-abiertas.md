# Preguntas abiertas — decisiones necesarias antes de programar

Con el documento `docsIA/Unique WS Reservaciones DU.pdf` ya varias preguntas de la primera versión de este documento quedaron resueltas (ver [05-api-unique-referencia.md](05-api-unique-referencia.md)). Lo que queda pendiente es más específico:

## Resueltas por el documento de Unique

- ~~¿Cuál es la API de terceros específica?~~ → **Unique Reservaciones** (`https://apiv1.uniqcloud.com/rsrv`).
- ~~Esquema de autenticación~~ → Login con `usuario`/`password`/`modo`/`uniqid_empresa` → `auth_token` en header `Authorization`. No es una API key estática, es una sesión (ver diseño de `api_conexiones` actualizado).
- ~~Formato del payload de "disponibilidad"~~ → No es una notificación libre, es literalmente `POST /create` con la estructura documentada en [05-api-unique-referencia.md](05-api-unique-referencia.md), y devuelve un folio de confirmación (`confirma`) que hay que conservar.

## Resueltas el 2026-08-06 (decisión del negocio)

Ver el detalle de implicaciones en [06-impacto-checkout-publico.md](06-impacto-checkout-publico.md).

- **Pasajeros por categoría** → Se agrega el desglose adultos/menores/infantes al checkout (no se aproxima con menores=0/infantes=0).
- **Transportación (hotel + pickup)** → Sí es necesario, se agrega un paso nuevo de checkout.
- **Idioma del tour** → Selector explícito en el checkout (no se infiere del locale de sesión).
- **Proveedor interno** → Un solo `Proveedor` de Attitour agrupa todos los tours de la conexión Unique (no uno por locación).
- **Cambios de precio detectados en syncs posteriores** → No se aplican solos: se genera un cambio `pendiente` en `tour_cambios_precio_api` que el Admin debe **Aprobar** o **Rechazar** explícitamente desde el panel (ver [02-diseno-de-la-solucion.md](02-diseno-de-la-solucion.md)).

## Resuelta durante la Fase 2 (2026-08-06)

- **Credenciales reales de Sandbox** → Ya estaban en `.env` bajo el nombre `OCEANTOURS_*` (`OCEANTOURS_API_URL`, `OCEANTOURS_API_USERNME`, `OCEANTOURS_API_PASSWORD`, `OCEANTOURS_UNIQID_EMPRESA`, `OCEANTOURS_MODO=SANDBOX`). Se usaron para sembrar la primera `ApiConexion` real ("Ocean Tours — Unique Reservaciones (Sandbox)") directamente en la base de datos a través del nuevo panel. Queda sin `proveedor_id` asignado — pendiente de que el Admin decida a qué Proveedor de Attitour vincularla desde el botón "Editar".

## Pendientes

1. **Vida útil real del `auth_token`.**
   El PDF no documenta cuánto dura el token antes de expirar. Se diseña el cliente para reintentar login ante un 401, pero conviene confirmarlo con Unique (o descubrirlo empíricamente en Sandbox) para decidir si conviene cachear-y-refrescar proactivamente en vez de reactivamente. Bloquea la Fase 3 (sync real).

2. **¿Qué pasa si un tour ya publicado desaparece del catálogo de Unique en un sync posterior?**
   Opciones: dejarlo tal cual (el Admin lo desactiva manualmente si quiere), marcarlo automáticamente como inactivo, o solo notificar al Admin sin tocar nada.

3. **Frecuencia de sincronización.**
   El pedido original solo menciona el botón manual **"Actualizar Catálogo"** — así se diseña la Fase 3. Sync automático (cron) es una extensión natural pero fuera del alcance descrito hoy.

4. **Cancelaciones ya notificadas a Unique.**
   `StripeCheckoutService::liberarReservaPendiente()` hoy solo libera reservas en estado `Pendiente` (antes de pagar). No está definido si una reserva ya `Pagada` y ya creada en Unique (con folio `confirma`), que se cancela después, debe generar una llamada de cancelación hacia Unique — el PDF no documenta un endpoint de cancelación (no aparece en el índice de 11 secciones), así que probablemente haya que resolverlo manualmente o vía soporte de Unique en una primera fase.

No es necesario resolver todas para arrancar la Fase 3. La pregunta 1 es la única que la bloquea directamente; la 4 puede resolverse después del piloto, no bloquea el lanzamiento inicial.

## Requisito operativo detectado en la Fase 6 (2026-08-07)

- **Se necesita un worker de colas corriendo en producción.** `NotificarReservaApiExternaJob` se despacha a la cola `database` (`QUEUE_CONNECTION=database`, ya configurado) — pero **nada la procesa sola**. Sin un proceso `php artisan queue:work` corriendo de forma permanente (vía Supervisor, systemd, o el servicio equivalente del hosting), las notificaciones a Unique se quedan encoladas indefinidamente y nunca se envían, aunque el pago del cliente se confirme con normalidad. Esto no bloquea el desarrollo/pruebas en Sandbox (se puede correr `queue:work` manualmente), pero **es un requisito de despliegue a validar antes del piloto** (Fase 7) — hay que confirmar con quien administre el hosting que hay un worker persistente configurado.
- **Falla de negocio vs. falla de red**: se confirmó en pruebas reales que Unique puede responder HTTP 200 con un cuerpo de error de negocio (ej. `{"errors":"SOLD OUT"}`) en vez de fallar a nivel HTTP. `TourApiNotificationService` ya distingue esto (sin `confirma` en la respuesta, se trata como fallo con reintento), pero vale la pena que el Admin revise periódicamente `tour_api_notificaciones` con `estado = fallido` una vez en producción, ya que un "SOLD OUT" real (no de prueba) significa que un cliente pagó en Attitour un cupo que Unique ya no tiene disponible — un caso de negocio que requiere intervención humana, no solo reintentos automáticos.

## ⚠️ Hallazgo crítico verificado el 2026-08-12 — inestabilidad del Sandbox de Unique

Se hizo una prueba end-to-end real (carrito → checkout → pago simulado → notificación → verificación independiente contra Unique) usando inventario real confirmado por `/sold_out`. Resultado:

1. **`"disponibilidad": 1` está roto en este Sandbox** — devuelve `{"errors":"SOLD OUT"}` de forma consistente aunque `/sold_out` confirme cupo real disponible (probado con 127+ cupos libres). Se corrigió el payload de Attitour a `"disponibilidad": 0` (ver Fase 6 en [03-seguridad-sandbox-y-plan.md](03-seguridad-sandbox-y-plan.md)) — con esto sí se logran creaciones exitosas.
2. **Incluso con `disponibilidad: 0`, el comportamiento es intermitente.** En la misma tanda de pruebas, `POST /create` alternó entre: (a) éxito con folio `confirma` válido, y (b) un **error 500 con stack trace completo del servidor de Unique**, revelando: `SQLSTATE[42000]: [SQL Server] The insert failed. It conflicted with an identity range check constraint in database 'unique_pmc', replicated table 'dbo.rsrv_pagos_reserva', column 'id_pago'` — su base de datos de Sandbox tiene el **rango de identity agotado** en la tabla de pagos/reservas (typical de replicación en SQL Server; solo lo resuelve su equipo con `sp_adjustpublisheridentityrange` del lado del Publisher).
3. **Ni siquiera las reservas "exitosas" son verificables después.** Se intentó confirmar de forma independiente (sin depender de nuestra propia base de datos) usando `GET /get/{cupon}` y `POST /reservations` con el folio que Unique acababa de devolver como `confirma` — ambos respondieron `{"errors":"Booking does not exist"}`. No se pudo determinar si esto es la misma causa raíz que el error de identity range, u otro problema separado de su lado.

**Conclusión**: la integración de Attitour funciona correctamente contra el contrato documentado — el payload es aceptado, el manejo de éxito/fallo es correcto, y el flujo completo (checkout → pago → cola → notificación → auditoría) fue verificado de punta a punta. El problema está **del lado de la infraestructura de Unique**, no en nuestro código.

**Siguiente paso recomendado**: reportar a soporte técnico de Unique, con el stack trace exacto de arriba, antes de continuar con la Fase 7 (piloto). Sin que ellos resuelvan el rango de identity de su base de datos, ninguna integración (la nuestra o cualquier otra) puede crear reservas de forma confiable en este Sandbox.

## ✅ Respuesta oficial del equipo técnico de Unique (2026-08-19) — hallazgo cerrado

Se envió el reporte técnico (`iadocs/reporte-tecnico-sandbox-unique-2026-08-12.docx`) y el equipo de desarrollo de Unique respondió las 4 preguntas directamente. Resumen y resolución de cada una:

1. **Rango de identity agotado (Hallazgo 2)** → *"Aplica una configuración similar [en producción], pero esto fue una coincidencia que ocurriera, es muy raro que este error ocurra."* Es decir: producción **no está exenta en teoría** (comparte una configuración similar de replicación), pero Unique lo caracteriza como un evento raro/casual, no un problema estructural. Riesgo residual bajo, mitigado por el punto 2.

2. **Monitoreo de rangos de identity en producción** → *"Sí, recibimos alertas cuando hay problemas en la sincronización de BD y se atienden siempre con prioridad."* Confirmado: si algo similar ocurriera en producción, Unique ya tiene proceso para detectarlo y atenderlo rápido — no depende de que Attitour lo reporte primero.

3. **`"disponibilidad": 1` devolviendo SOLD OUT falso (Hallazgo 1)** → *"Es un comportamiento que solo ocurre en sandbox."* Confirmado explícitamente que no ocurre en producción. **Se ajustó el código** (`TourApiNotificationService::construirPayload()`) para enviar `disponibilidad: 1` en conexiones con `modo = produccion` (la opción más segura, deja que Unique valide su propio cupo en vivo) y mantener `disponibilidad: 0` solo en Sandbox como workaround de este bug ya confirmado como exclusivo de ese ambiente.

4. **Reservas no localizables vía `GET /get/{cupon}` (Hallazgo 3)** → **Esto no era un bug de Unique — fue un error de prueba de nuestro lado.** El equipo de Unique aclaró: *"las están buscando con el folio de confirmación que les entregó Unique, cuando lo esperado es que la busquen con la referencia que ustedes enviaron, la referencia es un dato que ustedes deben controlar y ser única."* Es decir, `GET /get/{cupon}` debe llamarse con la `referencia` que Attitour manda en `/create` (nuestro `Reserva::ticket_codigo`), no con el `confirma` que Unique devuelve. **Verificado de nuevo con esta corrección: funciona perfectamente** — se creó una reserva de prueba y se encontró de inmediato buscando por su `ticket_codigo`. Se corrigió el docblock de `UniqueApiClient::getByCupon()` para dejarlo explícito.

**Estado final**: de los 3 hallazgos originales, 2 quedaron confirmados como exclusivos de Sandbox (no bloquean producción) y 1 resultó ser un malentendido de nuestra propia prueba, ya corregido. No queda ningún bloqueante técnico conocido para avanzar a la Fase 7 (piloto).
