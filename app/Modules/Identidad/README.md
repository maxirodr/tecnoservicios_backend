# Módulo Identidad

**Propósito:** Ingreso (Sanctum SPA), usuarios, sectores, jerarquía (jefe_id), matriz de permisos sector × función, perfil.

**Funciones del documento firmado que cubre:** F-01, F-02, F-10, F-58, F-59, F-60, F-61, F-68

**Reglas:** ver `docs/especificacion/identidad.md`.

## Capas

- `Http/` — controllers, form requests, resources. Única capa que conoce HTTP.
- `Application/` — casos de uso (Actions), queries y DTOs. Orquesta Domain e Infrastructure.
- `Domain/` — entidades, value objects, estados, reglas y eventos. Sin `Illuminate\Http`, sin Facades, sin nada de `Http/`.
- `Infrastructure/` — Eloquent, repositorios, integraciones. Privada del módulo: ningún otro módulo la importa.
- `routes.php` — rutas del módulo (prefijo `/api`, middleware `api`), las registra `IdentidadServiceProvider`.

Otros módulos se comunican con este por eventos de dominio o por su interfaz pública `Contracts/` (cuando exista).
