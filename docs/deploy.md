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
