# Módulo Obras

**Propósito:** Legajo de la obra: alta, ficha, presupuestos (principal y adicionales), documentos y contactos, estados Confirmada → En ejecución → Terminada.

**Funciones del documento firmado que cubre:** F-15, F-16, F-17, F-18, F-19, F-20, F-21, F-22, F-23

**Reglas:** ver `docs/especificacion/obras.md`.

## Capas

- `Http/` — controllers, form requests, resources. Única capa que conoce HTTP.
- `Application/` — casos de uso (Actions), queries y DTOs. Orquesta Domain e Infrastructure.
- `Domain/` — entidades, value objects, estados, reglas y eventos. Sin `Illuminate\Http`, sin Facades, sin nada de `Http/`.
- `Infrastructure/` — Eloquent, repositorios, integraciones. Privada del módulo: ningún otro módulo la importa.
- `routes.php` — rutas del módulo (prefijo `/api`, middleware `api`), las registra `ObrasServiceProvider`.

Otros módulos se comunican con este por eventos de dominio o por su interfaz pública `Contracts/` (cuando exista).
