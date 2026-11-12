# Módulo Importacion

**Propósito:** Carga inicial: pedidos abiertos de la minuta y obras en ejecución con su gastado acumulado por rubro.

**Funciones del documento firmado que cubre:** F-62

**Reglas:** ver `docs/especificacion/importacion.md`.

## Capas

- `Http/` — controllers, form requests, resources. Única capa que conoce HTTP.
- `Application/` — casos de uso (Actions), queries y DTOs. Orquesta Domain e Infrastructure.
- `Domain/` — entidades, value objects, estados, reglas y eventos. Sin `Illuminate\Http`, sin Facades, sin nada de `Http/`.
- `Infrastructure/` — Eloquent, repositorios, integraciones. Privada del módulo: ningún otro módulo la importa.
- `routes.php` — rutas del módulo (prefijo `/api`, middleware `api`), las registra `ImportacionServiceProvider`.

Otros módulos se comunican con este por eventos de dominio o por su interfaz pública `Contracts/` (cuando exista).
