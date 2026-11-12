# Módulo Gastos

**Propósito:** Facturas y gastos repartidos por renglón (obra + rubro), gastos sin comprobante, combustible, stock propio, amortización, retiros y devoluciones; listado, corrección y anulación.

**Funciones del documento firmado que cubre:** F-39, F-40, F-41, F-42, F-43, F-44, F-45, F-46, F-47

**Reglas:** ver `docs/especificacion/gastos.md`.

## Capas

- `Http/` — controllers, form requests, resources. Única capa que conoce HTTP.
- `Application/` — casos de uso (Actions), queries y DTOs. Orquesta Domain e Infrastructure.
- `Domain/` — entidades, value objects, estados, reglas y eventos. Sin `Illuminate\Http`, sin Facades, sin nada de `Http/`.
- `Infrastructure/` — Eloquent, repositorios, integraciones. Privada del módulo: ningún otro módulo la importa.
- `routes.php` — rutas del módulo (prefijo `/api`, middleware `api`), las registra `GastosServiceProvider`.

Otros módulos se comunican con este por eventos de dominio o por su interfaz pública `Contracts/` (cuando exista).
