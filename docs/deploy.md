# Deploy

## Staging · `https://clientes.nexosmart.com.ar/tecnoservicios/`

Front y API en el **mismo dominio y la misma subruta**: no hace falta CORS y la cookie de sesión queda atada a `/tecnoservicios`, sin cruzarse con otras apps de `clientes.nexosmart.com.ar`.

| URL | Sirve |
|---|---|
| `/tecnoservicios/api/*`, `/tecnoservicios/sanctum/*` | Laravel (`tecnoservicios_backend/public/index.php`) |
| `/tecnoservicios/*` (todo lo demás) | `tecnoservicios_frontend/dist` con vuelta a `index.html` |

Rama: `staging` en los dos repos. Actualizar = `git pull` + los pasos de abajo.

### Requisitos del servidor
PHP 8.3+ (`pdo_mysql`, `redis` o predis, `mbstring`, `intl`, `fileinfo`, `gd`), Composer 2, Node 22 (solo para compilar el front), MySQL 8.x, Redis con **AOF** (`appendonly yes`, `appendfsync everysec`), Supervisord, nginx. `upload_max_filesize` y `post_max_size` ≥ **21M** (adjuntos de hasta 20 MB, B-04) y `client_max_body_size 21m` en nginx.

### Backend `.env` (sobre `.env.example`)
```
APP_ENV=staging
APP_DEBUG=false
APP_URL=https://clientes.nexosmart.com.ar/tecnoservicios
FRONTEND_URL=https://clientes.nexosmart.com.ar/tecnoservicios

SESSION_DOMAIN=clientes.nexosmart.com.ar
SESSION_PATH=/tecnoservicios
SESSION_SECURE_COOKIE=true
SANCTUM_STATEFUL_DOMAINS=clientes.nexosmart.com.ar
CORS_ALLOWED_ORIGINS=https://clientes.nexosmart.com.ar

DB_DATABASE=tecnoservicios_staging   # base propia; usuario con permisos solo sobre ella
REDIS_PREFIX=tecnoservicios_staging_
CACHE_PREFIX=tecnoservicios_staging_cache_
QUEUE_CONNECTION=redis
MAIL_MAILER=resend                   # + su clave; remitente verificado
ERRORS_TOKEN=                        # openssl rand -hex 32, distinto de producción
HEALTH_DB_TIMEOUT=1

SUPERVISOR_CONF_DIR=/etc/supervisor/conf.d
SUPERVISOR_USER=www-data
SUPERVISOR_PROGRAM_PREFIX=tecnoservicios_staging
```
Si hay otras apps Laravel en el mismo Redis, el prefijo propio es obligatorio.

### Front `.env.production.local` (antes de compilar)
```
VITE_BASE_PATH=/tecnoservicios
VITE_API_URL=https://clientes.nexosmart.com.ar/tecnoservicios
VITE_MODULOS_ENTREGADOS=
```

### Pasos (primera vez y cada actualización)
```bash
# backend
cd tecnoservicios_backend && git checkout staging && git pull
composer install --no-dev --optimize-autoloader
php artisan key:generate          # solo la primera vez
php artisan migrate --force
php artisan db:seed --force       # solo la primera vez (datos ficticios / catálogos)
php artisan storage:link 2>/dev/null || true
php artisan config:cache && php artisan route:cache && php artisan view:cache
php artisan supervisor:sync       # genera y recarga los workers (default, mail) y el scheduler
php artisan queue:restart         # en cada actualización: los workers toman el código nuevo

# front
cd ../tecnoservicios_frontend && git checkout staging && git pull
npm ci && npm run build           # deja dist/ con base /tecnoservicios/
```
`storage/` y `bootstrap/cache/` escribibles por `www-data`.

### nginx (dentro del `server` de `clientes.nexosmart.com.ar`)
```nginx
client_max_body_size 21m;

# API y CSRF de Sanctum → Laravel
location ~ ^/tecnoservicios/(api|sanctum)/ {
    include fastcgi_params;
    fastcgi_pass unix:/run/php/php8.3-fpm.sock;
    fastcgi_param SCRIPT_FILENAME /ruta/tecnoservicios_backend/public/index.php;
    fastcgi_param SCRIPT_NAME     /tecnoservicios/index.php;   # Laravel toma /tecnoservicios como base
    fastcgi_param REQUEST_URI     $request_uri;
    fastcgi_read_timeout 60s;
}

# Front (SPA): archivos de dist/ y, si no existe, index.html
location /tecnoservicios/ {
    alias /ruta/tecnoservicios_frontend/dist/;
    try_files $uri $uri/ /tecnoservicios/index.html;
}
location = /tecnoservicios { return 301 /tecnoservicios/; }
```

### Verificar
- `curl -s https://clientes.nexosmart.com.ar/tecnoservicios/api/health` → 200 `{"status":"ok",…}`.
- `curl -s https://clientes.nexosmart.com.ar/tecnoservicios/api/me` → 401 "Tu sesión venció. Volvé a ingresar.".
- Abrir `/tecnoservicios/obras` directo y recargar: carga la app (no 404).
- Ingresar con mail + código (llega el mail: worker `mail` vivo), recargar y seguir adentro.
- Checklist de `docs/seguridad.md` (§ primer deploy) y `.claude/bug-report.json` con la URL de staging y el `ERRORS_TOKEN`.

## Producción · arranque de la semana 4 (carga inicial, `importacion.md` §12, HU-100 y HU-101)

Producción arranca con la **base limpia** (resuelta DU-I01): lo cargado en las pruebas de las semanas 1 a 3 no pasa.
Mismos pasos que staging, con estas diferencias y en este orden (D = día de la apertura, §12.2).

### D−5 · Producción lista (HU-100)
- [ ] Backend con la versión de la semana 4: `composer install --no-dev --optimize-autoloader`, `php artisan migrate --force`
      (crea `importacion_corridas`, `importacion_filas` y la marca `actividades.carga_inicial`). **Sin `db:seed`**: los
      seeders son datos ficticios; catálogos, permisos y el proveedor genérico llegan por migración.
- [ ] `.env` de producción: `APP_ENV=production`, `APP_DEBUG=false`, `IMPORTACION_DIR` = carpeta **privada** fuera del
      repositorio y de `public` (por defecto `storage/app/private/importacion/`), `chmod 700` y dueño el usuario del
      servidor (RN-IMP-12).
- [ ] Front con las **cuatro entregas encendidas**: `VITE_MODULOS_ENTREGADOS=` vacío (= las semanas 1 a 3:
      pedidos, obras, calendario, gastos, rendicion, proveedores, usuarios, avisos) antes de `npm run build`; la barra
      muestra Tareas, Obras, Proveedores, Gastos y Rendiciones según la tabla de permisos. La semana 4 no suma pantallas.
- [ ] Primer usuario: `php artisan identidad:crear-administrador {mail} {nombre}` (ADR-015). Entra pidiendo el código.
- [ ] `curl -s https://<api>/api/health` → 200.
- [ ] Gerencia carga en W-39 a cada persona con su sector y su jefatura (~13 personas, 11 sectores) y repite en W-15 lo
      que hayan cambiado de la tabla de permisos en las pruebas. Cada mail de la hoja Personas tiene usuario activo.
- [ ] No se importan proveedores (RN-IMP-46): se dan de alta con su primera factura.

### D−4…D−1 · Plantillas, ensayos y aprobación
- [ ] `php artisan importacion:plantilla obras` y `… tareas` → `plantilla-obras.xlsx` / `plantilla-tareas.xlsx` en la
      carpeta. NexoSmart las completa desde la minuta y las rendiciones **en su equipo, sin versionar**; viajan solo por la
      carpeta de Drive del proyecto. Nunca al repositorio, a un issue ni a un test.
- [ ] Ensayos (no guardan nada): `php artisan importacion:obras obras.xlsx`, después `importacion:tareas tareas.xlsx`
      (las tareas necesitan las obras ya confirmadas). Cada corrida deja `informe-…-ensayo-AAAAMMDD-HHMM.xlsx` y dice su
      huella corta. Se repite hasta que el informe no tenga faltantes o administración acepte dejarlos afuera.
- [ ] Administración aprueba por mail "Apruebo el informe {huella corta}" del **último** ensayo de cada lote.

### D−1 · Carga real (HU-101)
- [ ] **Respaldo de la base antes de cada `--confirmar`** (RN-IMP-44): `mysqldump --single-transaction tecnoservicios > respaldo-antes-obras-AAAAMMDD-HHMM.sql`.
- [ ] `php artisan importacion:obras obras.xlsx --confirmar --aprobo={mail de quien aprobó}` (mismo archivo, misma
      huella: si se tocó, pide otro ensayo).
- [ ] Respaldo de nuevo → `php artisan importacion:tareas tareas.xlsx --confirmar --aprobo={mail}`.
- [ ] `php artisan importacion:corridas`: las dos cargas `confirmada` · `terminada` (si dice `con_errores` o
      `interrumpida`, se vuelve a correr el mismo archivo: entra solo lo que faltaba, nada se duplica).
- [ ] Una carga mal hecha **antes de la apertura** se deshace restaurando el respaldo y confirmando de nuevo; **después**
      no se restaura nada: se anula la carga inicial en W-34 y se vuelve a importar (RN-IMP-27).

### D−1 · Conteos de QC contra el origen (§12.3), antes de pasar el link
- [ ] **Tareas:** filas abiertas de la minuta = tareas importadas + filas con faltante (hoja Faltantes, con su motivo) +
      filas que administración decidió dejar afuera. Ninguna diferencia sin explicar.
- [ ] **Obras:** las obras En ejecución de su lista están todas, o con su faltante en el informe.
- [ ] **Gastado:** por obra y rubro, W-11 = su hoja "General" al corte, **diferencia 0** (hoja Control por rubro; EPP
      se ve dentro de INSUMOS).
- [ ] **Retirado:** en W-48, "Resta llevar" de cada material = su planilla de retiro al corte.
- [ ] **Personas:** cada propietario entra y ve sus tareas en Mis tareas, en PC y en el celular.
- [ ] **Sin avisos:** la campana no tiene avisos de asignación de la carga y no salió ningún mail.

### D · Apertura y después
- [ ] Se pasa el link al equipo; la minuta y las rendiciones en Excel quedan de consulta. Lo posterior al corte se carga
      en el sistema (RN-IMP-26).
- [ ] **A los 30 días de la apertura** (anotar la fecha: ____/____/______) NexoSmart borra la carpeta de la importación
      (archivos e informes); en la base quedan solo las corridas con su huella y sus conteos (RN-IMP-12).
