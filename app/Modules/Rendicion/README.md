# Módulo Rendicion

**Propósito:** Rendición en vivo por obra: presupuesto por rubro, gastado y restante (query agregada sobre gasto_renglones, sin tabla proyectada), rubros pasados, detalle por rubro y proveedor, rendiciones en curso, control de materiales por cantidad.

**Funciones del documento firmado que cubre:** F-48, F-49, F-50, F-51, F-52, F-53, F-54, F-66

**Reglas:** ver `docs/especificacion/rendicion.md`.

## Capas

- `Http/` — controllers, form requests, resources. Única capa que conoce HTTP.
- `Application/` — casos de uso (Actions), queries y DTOs. Orquesta Domain e Infrastructure.
- `Domain/` — entidades, value objects, estados, reglas y eventos. Sin `Illuminate\Http`, sin Facades, sin nada de `Http/`.
- `Infrastructure/` — Eloquent, repositorios, integraciones. Privada del módulo: ningún otro módulo la importa.
- `routes.php` — rutas del módulo (prefijo `/api`, middleware `api`), las registra `RendicionServiceProvider`.

Otros módulos se comunican con este por eventos de dominio o por su interfaz pública `Contracts/` (cuando exista).
