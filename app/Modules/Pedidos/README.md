# Módulo Pedidos

**Propósito:** La minuta: pedidos con propietario, prioridad automática, toma/bloqueo/comentarios/completado/reasignación/reapertura, seguidores, mis tareas, tareas de mi gente, calendario, carga por persona y equipos en reparación.

**Funciones del documento firmado que cubre:** F-04, F-05, F-06, F-07, F-11, F-12, F-24, F-25, F-26, F-27, F-28, F-29, F-30, F-31, F-32, F-33, F-34, F-35, F-36, F-38, F-65, F-67

**Reglas:** ver `docs/especificacion/pedidos.md`.

## Capas

- `Http/` — controllers, form requests, resources. Única capa que conoce HTTP.
- `Application/` — casos de uso (Actions), queries y DTOs. Orquesta Domain e Infrastructure.
- `Domain/` — entidades, value objects, estados, reglas y eventos. Sin `Illuminate\Http`, sin Facades, sin nada de `Http/`.
- `Infrastructure/` — Eloquent, repositorios, integraciones. Privada del módulo: ningún otro módulo la importa.
- `routes.php` — rutas del módulo (prefijo `/api`, middleware `api`), las registra `PedidosServiceProvider`.

Otros módulos se comunican con este por eventos de dominio o por su interfaz pública `Contracts/` (cuando exista).
