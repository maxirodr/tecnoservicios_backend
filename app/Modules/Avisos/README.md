# Módulo Avisos

**Propósito:** Avisos dentro del sistema (campana, lista completa) y por mail (asignación y los que cada uno active), recordatorio de 1 día antes por scheduler. Notifications database + mail por cola (Resend, 1 mail/s).

**Funciones del documento firmado que cubre:** F-08, F-09, F-37, F-64

**Reglas:** ver `docs/especificacion/avisos.md`.

## Capas

- `Http/` — controllers, form requests, resources. Única capa que conoce HTTP.
- `Application/` — casos de uso (Actions), queries y DTOs. Orquesta Domain e Infrastructure.
- `Domain/` — entidades, value objects, estados, reglas y eventos. Sin `Illuminate\Http`, sin Facades, sin nada de `Http/`.
- `Infrastructure/` — Eloquent, repositorios, integraciones. Privada del módulo: ningún otro módulo la importa.
- `routes.php` — rutas del módulo (prefijo `/api`, middleware `api`), las registra `AvisosServiceProvider`.

Otros módulos se comunican con este por eventos de dominio o por su interfaz pública `Contracts/` (cuando exista).
