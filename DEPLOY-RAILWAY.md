# Despliegue en Railway — Atti Tours 2.0

Guía para publicar esta aplicación Laravel 13 en [Railway](https://railway.com).

Railway detecta que es una app Laravel (por el archivo `artisan`) y la construye con
**Railpack**, que instala Composer, instala las dependencias de NPM y ejecuta
`npm run build` (Vite), y la sirve con **FrankenPHP + Caddy** apuntando a `public/`.
No hace falta Dockerfile.

## 1. Crear el proyecto

Desde el dashboard de Railway:

1. **New Project → Deploy from GitHub repo** y elige `attitours2.0`.
2. Railway crea un servicio con la app. Todavía no la despliegues del todo: falta la base de datos y las variables.

O desde la CLI, en la raíz del repo:

```bash
npm i -g @railway/cli
railway login
railway init
railway up
```

## 2. Añadir la base de datos MySQL

En el proyecto: **New → Database → Add MySQL**.

La app usa SQLite por defecto (`.env.example`), y eso **no sirve en Railway**: el disco del
contenedor se borra en cada despliegue y perderías reservas y usuarios. El esquema de
`database/migrations/` está escrito pensando en MySQL (columnas `json`, `->after()`),
así que MySQL es la opción natural. Postgres también funcionaría, pero no hay motivo para
asumir ese riesgo aquí.

## 3. Variables de entorno

En el servicio de la app → **Variables → Raw Editor**, pega esto:

```shell
APP_NAME=Atti Tours
APP_ENV=production
APP_DEBUG=false
APP_KEY=
APP_URL=https://TU-DOMINIO.up.railway.app

APP_LOCALE=es
APP_FALLBACK_LOCALE=es

DB_CONNECTION=mysql
DB_HOST=${{MySQL.MYSQLHOST}}
DB_PORT=${{MySQL.MYSQLPORT}}
DB_DATABASE=${{MySQL.MYSQLDATABASE}}
DB_USERNAME=${{MySQL.MYSQLUSER}}
DB_PASSWORD=${{MySQL.MYSQLPASSWORD}}

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database

LOG_CHANNEL=stderr
LOG_LEVEL=warning

RAILPACK_SKIP_MIGRATIONS=true
RAILWAY_RUN_UID=0
```

Notas sobre algunas de estas variables:

- **`APP_KEY`** — genérala en local y pega el resultado (incluye el prefijo `base64:`):

  ```bash
  php artisan key:generate --show
  ```

  Sin ella Laravel no arranca. No reutilices la de desarrollo y no la subas al repo.

- **`APP_URL`** — actualízala con el dominio real después del paso 5.

- **`${{MySQL.*}}`** son *reference variables*: Railway las resuelve al servicio MySQL,
  así que las credenciales no quedan escritas a mano y siguen funcionando si la base de
  datos se recrea. Si nombraste el servicio distinto a `MySQL`, ajusta el prefijo.

- **`LOG_CHANNEL=stderr`** hace que los logs salgan en el panel de Railway. Con el valor
  por defecto (`stack` → `single`) se escribirían a `storage/logs/laravel.log`, un archivo
  que se borra en cada despliegue.

- **`RAILPACK_SKIP_MIGRATIONS=true`** — ver el paso 4.

- **`RAILWAY_RUN_UID=0`** — ver el paso 6.

`SESSION_DRIVER`, `CACHE_STORE` y `QUEUE_CONNECTION` usan `database`; las tablas
correspondientes ya las crean las migraciones. Los correos de confirmación de reserva se
envían de forma síncrona (`ReservaConfirmada` no implementa `ShouldQueue`), así que **no
necesitas un worker de colas**. Para que salgan de verdad, configura un `MAIL_MAILER` real
(SMTP, Resend, etc.); con el valor por defecto sólo se escriben al log.

## 4. Migraciones

Ya están configuradas en [`railway.json`](railway.json):

```json
"deploy": {
  "preDeployCommand": ["php artisan migrate --force"]
}
```

El `preDeployCommand` corre en un contenedor aparte **antes** de que la versión nueva
reciba tráfico, y una sola vez aunque haya varias réplicas. Si falla, el despliegue se
detiene y la versión anterior sigue sirviendo.

Railpack, por su cuenta, también ejecuta `php artisan migrate --force` al arrancar *cada*
contenedor. Por eso ponemos `RAILPACK_SKIP_MIGRATIONS=true`: dejamos las migraciones sólo
en el `preDeployCommand` y evitamos que dos réplicas migren a la vez.

El healthcheck apunta a `/up`, que ya está declarado en `bootstrap/app.php`.

### Datos iniciales (opcional)

`DatabaseSeeder` **borra todas las tablas** antes de insertar los datos de prueba. No lo
pongas en el despliegue automático. Si quieres poblar la base recién creada, hazlo una vez
y a conciencia:

```bash
railway run php artisan db:seed
```

## 5. Generar el dominio

**Settings → Networking → Generate Domain**. Copia la URL resultante en `APP_URL` y
vuelve a desplegar.

## 6. Volumen para las imágenes subidas

`DashboardController` guarda las fotos de tours y proveedores en el disco `public`
(`storage/app/public`) con `$file->store('tours', 'public')`. El sistema de archivos del
contenedor es efímero: **sin un volumen, cada despliegue borra las imágenes subidas.**

En el servicio de la app: **Settings → Volumes → Add Volume**, con mount path:

```
/app/storage/app/public
```

Railway monta los volúmenes como `root`. Como el contenedor no corre como root, hace falta
`RAILWAY_RUN_UID=0` (ya incluida arriba) para que PHP pueda escribir ahí.

No hace falta crear el symlink `public/storage` a mano: Railpack ejecuta
`php artisan storage:link` al arrancar el contenedor.

Ten en cuenta un par de límites de Railway: sólo se permite **un volumen por servicio**, y
los servicios con volumen pueden tener una breve caída durante el redespliegue.

## Cambios que se hicieron en el repo para esto

- **`railway.json`** (nuevo) — builder Railpack, migraciones en `preDeployCommand`,
  healthcheck en `/up` y política de reinicio.
- **`bootstrap/app.php`** — `trustProxies(at: '*')`. Railway termina el TLS en su edge y
  reenvía por HTTP interno; sin esto Laravel generaría URLs y redirecciones `http://` y el
  navegador bloquearía los assets por mixed content.
- **`composer.json`** — se declara `ext-pdo_mysql` (`composer.lock` se actualizó para que
  el hash siga cuadrando; no cambió ninguna versión de paquete).

## Comprobaciones después del primer despliegue

1. Los logs de build muestran `composer install` y `npm run build` (Vite).
2. La home carga con estilos — si se ven sin CSS, revisa `APP_URL` y el build de Vite.
3. Registro y login funcionan → sesiones en MySQL correctas.
4. Sube una imagen de tour desde el dashboard, redespliega y confirma que sigue ahí → el
   volumen está bien montado.
