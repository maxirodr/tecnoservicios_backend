# Shared

Piezas transversales que usan todos los módulos. No contiene reglas de negocio de un módulo.

- `Http/ApiResponse.php` — envelope único `{ data, message, errors }` (+ `meta`/`links` en listados paginados).
- `Http/RespondsWithEnvelope.php` — trait para controllers (`ok`, `created`, `fail`).
- `Http/ApiExceptionRenderer.php` — toda excepción → envelope (401, 403, 404, 405, 419, 422, 429, 500 sin detalles en prod).
- `Http/Middleware/RemoveStackHeaders.php` — quita `X-Powered-By` y `Server`.
- `Providers/ModuleServiceProvider.php` — base de los providers de módulo (registra `routes.php` del módulo bajo `/api`).
- `Console/SupervisorSyncCommand.php` — `php artisan supervisor:sync`.
- `Domain/` — value objects comunes (`Importe`, `Cantidad`, `NumeroPresupuesto`) y auditoría (`actividades`, F-14) cuando se implementen.

Funciones transversales del documento firmado que viven acá o se apoyan en esto: F-03 (buscador general), F-13 (uso desde el celular: mismo API), F-14 (registro de quién hizo qué).
