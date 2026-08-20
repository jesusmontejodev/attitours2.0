# Resumen ejecutivo — Consumo de APIs externas de Tours (modo Sandbox)

## Qué se va a construir

Una nueva sección **"APIs"** dentro del panel de gestión (`/dashboard`), visible solo para el rol **Admin**, que permite:

1. **Configurar conexiones** a APIs estáticas de terceros que exponen catálogos de tours.
2. **Actualizar catálogo**: traer (vía `GET`) el listado de tours de esa API y guardarlos como **tours importados / no implementados** — sin que aparezcan todavía en el catálogo público (`/tours`).
3. **Revisar y decidir**: el Admin ve una bandeja de tours importados y elige cuáles descartar y cuáles completar.
4. **Completar y publicar**: el Admin llena los datos que la API externa no trae (descripción larga, modalidad, itinerario, incluye/no incluye, etc.) reutilizando el mismo formulario de Tour que ya existe hoy. Al guardar, el tour pasa a ser un Tour real y aparece en la plataforma como cualquier otro.
5. **Notificar disponibilidad al reservar**: cuando un cliente reserva un tour que vino de una API externa y el pago se confirma, el sistema avisa automáticamente (vía `POST`) a la API de origen que hay un nuevo registro de disponibilidad, para que esa API pueda agendar el viaje de su lado.

Todo esto arranca **en modo SANDBOX**: el sistema no permitirá marcar una conexión como "producción" hasta que se decida explícitamente lo contrario (ver [03-seguridad-sandbox-y-plan.md](03-seguridad-sandbox-y-plan.md)).

## Cómo leer esta carpeta

| Archivo | Contenido |
|---|---|
| [01-arquitectura-actual.md](01-arquitectura-actual.md) | Cómo funciona **hoy** Attitour (stack, modelo de datos, panel de gestión, flujo de reserva) — la base real sobre la que se construye todo lo demás. |
| [02-diseno-de-la-solucion.md](02-diseno-de-la-solucion.md) | El diseño funcional y técnico de la nueva función: flujo paso a paso, tablas nuevas, rutas, controladores y servicios. |
| [03-seguridad-sandbox-y-plan.md](03-seguridad-sandbox-y-plan.md) | Reglas de seguridad, cómo se garantiza el modo Sandbox, y el plan de implementación por fases. |
| [04-preguntas-abiertas.md](04-preguntas-abiertas.md) | Decisiones que necesitamos del usuario/negocio antes de programar. **Léelo antes de empezar a codear.** |
| [05-api-unique-referencia.md](05-api-unique-referencia.md) | Referencia técnica de la API real a integrar primero — **Unique Reservaciones** — y cómo cada uno de sus endpoints mapea al diseño de los documentos anteriores. |
| [06-impacto-checkout-publico.md](06-impacto-checkout-publico.md) | Cómo las decisiones del negocio (pax por categoría, hotel/pickup, idioma explícito) amplían el alcance al carrito y checkout públicos, no solo al panel admin. |

## Relación con el documento existente

En `iadocs/propuesta-api-webhook-calendario.docx` ya existe una propuesta previa, pero describe el **flujo inverso**: proveedores externos empujando (push) su disponibilidad *hacia* Attitour mediante una API que Attitour expone. Lo que se documenta aquí es distinto y complementario: Attitour **consume (pull)** el catálogo de un tercero para importar sus tours, y luego **notifica hacia afuera** cuando se reserva. Ambos flujos pueden convivir a futuro, pero no son la misma pieza de trabajo — no se reutiliza código de esa propuesta porque nunca se implementó.
