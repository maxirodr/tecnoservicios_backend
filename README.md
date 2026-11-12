# Tecnoservicios — backend

API del sistema de gestión de Tecnoservicios Bahía (proyecto NS-2026-025, Etapa 1): obras, pedidos,
gastos, rendición en vivo, proveedores, avisos y usuarios con permisos por sector.

- Laravel (API-only) · PHP 8.3 · MySQL 8 · Redis · Sanctum SPA (cookie) · Resend · Supervisord
- Monolito modular: `app/Modules/<Modulo>/{Http,Application,Domain,Infrastructure}` + `app/Shared/`
- Respuesta JSON única: `{ "data": ..., "message": string|null, "errors": object|null }`

## Arranque rápido

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan serve
curl -s http://localhost:8000/api/health
```

Bases: `tecnoservicios` (dev) y `tecnoservicios_testing` (tests). Crearlas una vez:

```sql
CREATE DATABASE IF NOT EXISTS tecnoservicios CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE IF NOT EXISTS tecnoservicios_testing CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

## Calidad

```bash
composer test      # suite completa contra tecnoservicios_testing
composer lint      # Pint
composer analyse   # Larastan nivel 8
```

CI (GitHub Actions) corre lo mismo contra MySQL 8 y Redis en cada push y PR.

## Más

- Cómo trabajar en el repo, reglas de oro y deploy: [`CLAUDE.md`](CLAUDE.md)
- Checklist de PR: [`CONTRIBUTING.md`](CONTRIBUTING.md)
- Documentación de producto (arquitectura, ADR, especificación, QA): [`docs/`](docs/)
