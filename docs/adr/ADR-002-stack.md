# ADR-002 · Stack

- **Estado:** Aceptada (Juani, 2026-11-12) · **Fecha:** 2026-11-12 · **Rol:** CTO

## Decisión
Stack estándar de NexoSmart, sin desvíos:

| Pieza | Elección | Por qué |
|---|---|---|
| API | Laravel (última estable), PHP 8.3+, `strict_types` | Estándar de la casa; Sanctum, colas, notificaciones y scheduler de fábrica |
| Base | MySQL 8 | Estándar; integridad referencial y DECIMAL exacto para importes |
| Cache, colas, rate limit, sesión | Redis | Estándar; una sola pieza para las cuatro cosas |
| Mail | Resend, siempre por cola, 1 mail/s | Estándar; el mail del sistema es casi solo la asignación de pedidos (F-09) |
| Procesos | Supervisord (worker + scheduler) vía `php artisan supervisor:sync` | Nunca crontab |
| Front | React + Vite + TypeScript strict, SPA | App interna, sin SSR (D-012: web responsive, sin apps nativas) |
| Datos en el front | TanStack Query | Cache, reintentos, polling e invalidación (ADR-005) |
| Formularios | react-hook-form + zod | Validación de forma; las reglas las decide el backend |
| Tests | PHPUnit · Larastan 8 · Pint · Vitest + Testing Library · Playwright | |

## Fuera
Websockets (ADR-005), librerías de UI pesadas, servicios pagos nuevos, SSR.
