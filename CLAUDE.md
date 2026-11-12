# Tecnoservicios — backend (NS-2026-025, Etapa 1)

API Laravel (API-only) del sistema de gestión de Tecnoservicios Bahía: obras, pedidos (minuta), gastos,
rendición en vivo, proveedores, avisos y usuarios/permisos. El front es una SPA React aparte
(`../tecnoservicios_frontend`) que entra con Sanctum SPA (cookie).

La documentación de producto vive en `docs/` (arquitectura, modelo de datos, ADR, especificación por
módulo, QA, dudas, bitácora). **Leé `docs/especificacion/<modulo>.md` antes de tocar un módulo.**

## Reglas de oro (no negociables)

1. **Alcance firmado.** No se agrega, quita ni cambia funcionalidad respecto del documento firmado
   (F-01…F-68). Lo no especificado va a `docs/dudas.md` con fuente + propuesta, no al código.
   Fuera de alcance: Tango, cobranzas, calendario de pagos, sueldos, IA, WhatsApp, acceso de clientes,
   cotizaciones, herramientas, indicadores.
2. **Sin datos reales.** Ninguna cifra de dinero real ni nombres reales de empleados/proveedores en
   código, migraciones, seeders, factories, fixtures ni tests. Datos ficticios siempre. Los `.xlsx`
   del cliente no se commitean (`.gitignore`).
3. **Base de tests separada.** Tests contra MySQL real `tecnoservicios_testing` (lo fuerza `phpunit.xml`),
   nunca contra `tecnoservicios` (dev) ni sqlite. Una suite con 0 assertions o "connection refused" no
   prueba nada: `TestEnvironmentGuardTest` lo detecta.
4. **Nunca dropear bases.** Nada de `DROP DATABASE`, `migrate:fresh`/`db:wipe` contra dev o prod.
   Bases nuevas con `CREATE DATABASE IF NOT EXISTS`.
5. **Idempotencia.** Los POST que crean (pedido, gasto, comentario, transiciones) aceptan
   `Idempotency-Key` (UUID del front) con clave única en DB: el doble clic devuelve el mismo recurso.
   Avisos con `dedupe_key` único.
6. **Envelope.** Toda respuesta es `{ "data", "message", "errors" }` (éxito y error). Usar
   `App\Shared\Http\ApiResponse` / trait `RespondsWithEnvelope`; las excepciones ya se convierten solas
   (`ApiExceptionRenderer`). Listados paginados: `meta`/`links` dentro del envelope.
   Única excepción: el 200 de `GET /api/errors`, que respeta el contrato de bug-report.
7. **Permisos por matriz.** Gate/Policy leen la matriz sector × función (`sin_acceso|ver|editar`,
   alcance `todas|propias`). Nada de roles hardcodeados ni `if ($user->sector === ...)`. El catálogo de
   funciones y la configuración inicial se cargan **por migración** (los seeders no llegan a prod).
8. **Mail** siempre por cola: extender `App\Shared\Mail\QueuedMailable` (cola `mail`, 1 mail/s).
   Nunca `Mail::send()` crudo. Resend en prod; en tests `MAIL_MAILER=array`.
9. **Background** solo por Supervisord (`php artisan supervisor:sync`), nunca crontab.

## Cómo correr

Requisitos: PHP 8.3, Composer, MySQL 8 (Laragon, 127.0.0.1:3306, root sin clave), Redis 127.0.0.1:6379.

```bash
composer install
cp .env.example .env && php artisan key:generate   # completar ERRORS_TOKEN: openssl rand -hex 32
php artisan migrate
php artisan serve                                   # http://localhost:8000
```

Redis usa el cliente `predis` (no hace falta la extensión phpredis en Windows). Sesión, cache y cola
van a Redis.

## Cómo testear

```bash
composer test      # php artisan test (Unit + Feature + Architecture) contra tecnoservicios_testing
composer lint      # pint --test
composer analyse   # larastan nivel 8
composer format    # pint (corrige)
```

Antes de correr la suite, verificar que MySQL responde:
`mysql -uroot -h127.0.0.1 -e "select 1"` (Laragon: `C:\laragon\bin\mysql\mysql-8.4.3-winx64\bin\mysql.exe`).
Los nombres de test dicen la regla (`pedido_vencido_aparece_marcado_en_mis_tareas`), no el método.

## Cómo deployar

1. `git pull` · `composer install --no-dev --optimize-autoloader` · `php artisan migrate --force`
2. `php artisan config:cache route:cache event:cache`
3. `php artisan supervisor:sync` (escribe los .conf en `SUPERVISOR_CONF_DIR` y hace `reread`+`update`)
4. `php artisan queue:restart` — los workers tienen el código viejo en memoria hasta reiniciarse.
5. Verificar: `curl -s https://<api>/api/health` y `curl -s "https://<api>/api/errors?token=…"`.

En producción: `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://…` (HTTPS forzado en
`AppServiceProvider`), `SESSION_SECURE_COOKIE=true`, `SANCTUM_STATEFUL_DOMAINS` y `SESSION_DOMAIN` del
dominio del front, `MAIL_MAILER=resend` + `RESEND_API_KEY`.

## Estructura

```
app/
  Http/Controllers/Api/       Health + ErrorLog (infra bug-report de NexoSmart) y SafeErrorResponse
  Modules/<Modulo>/           Identidad, Obras, Pedidos, Gastos, Rendicion, Proveedores, Avisos, Importacion
    Http/                     controllers, form requests, resources (única capa que conoce HTTP)
    Application/              casos de uso (CreateObraAction), queries, DTOs
    Domain/                   entidades, value objects, estados, eventos (sin Illuminate\Http ni Facades)
    Infrastructure/           Eloquent, repositorios (privada del módulo)
    routes.php                rutas del módulo (prefijo /api), registradas por <Modulo>ServiceProvider
    README.md                 propósito y F-XX que cubre
  Shared/                     envelope, renderer de excepciones, middleware, mail base, supervisor:sync
config/supervisor.php         programas de Supervisord (workers default/mail y scheduler)
tests/Architecture/           límites del monolito modular
tests/Feature/Shared/         envelope, headers, bug-report, supervisor, mail, guard de la base
docs/                         documentación de producto (la escribe el equipo; no es código)
```

Agregar un módulo: carpeta en `app/Modules/<Modulo>` con las 4 capas + `routes.php` + provider que
extiende `App\Shared\Providers\ModuleServiceProvider`, y registrarlo en `bootstrap/providers.php`.
No se toca `routes/api.php`.

## Convenciones

- `declare(strict_types=1);` en todo PHP propio. PHPUnit (no Pest). Pint + Larastan nivel 8 limpios.
- Idioma: lenguaje del cliente en español **sin tildes ni ñ** para entidades y estados (`Obra`,
  `Pedido`, `Renglon`, `Rendicion`; `no_iniciada`, `en_curso`, `bloqueada`, `completada`); inglés
  técnico para el resto (`CreateObraAction`, `ObraController`, `PedidoStatus`, `store/update`).
- Tablas en español snake_case plural con `$table` explícito en el modelo (Eloquent pluraliza mal
  "renglon"/"proveedor"). Rutas `/api/obras`, `/api/pedidos`, `/api/gastos`.
- Importes `DECIMAL(15,2)` en pesos, netos sin IVA; cantidades `DECIMAL(14,3)` (negativas en devoluciones).
- Módulos se hablan por eventos de dominio o por la interfaz pública `Contracts/` del módulo; nunca
  importan `Infrastructure` de otro módulo (lo verifica `tests/Architecture`).
- Auditoría en tabla `actividades` (F-14). Rendición = query agregada sobre `gasto_renglones`, sin tabla
  proyectada.
- Migraciones reversibles (`down()` real). Sin N+1 (`preventLazyLoading` activo fuera de prod).
- Conventional commits en inglés (`feat:`, `fix:`, `refactor:`, `chore:`). Los agentes no commitean.

## Bug-report (NexoSmart)

`GET /api/health` (público) y `GET /api/errors` (token `ERRORS_TOKEN`, Bearer o `?token=`; 10 fallos/h
→ ban 24 h). Los errores de servidor y las validaciones fallidas se registran en el canal `errors`
(`storage/logs/errors-YYYY-MM-DD.log`). `.claude/bug-report.json` (URL de prod + token) está en
`.gitignore`. Contrato: `~/.claude/bug-reporting-autofixing/spec/error-endpoint-spec.md`.
