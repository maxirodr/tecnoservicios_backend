# Módulo Rendicion

**Propósito:** Rendición en vivo por obra: presupuesto por rubro, gastado y restante (query agregada sobre gasto_renglones, sin tabla proyectada), rubros pasados, detalle por rubro y proveedor, rendiciones en curso, control de materiales por cantidad.

**Funciones del documento firmado que cubre:** F-48, F-49, F-50, F-51, F-52, F-53, F-54, F-66 (y la parte de rendición de F-13, F-15 y F-17)

**Reglas:** ver `docs/especificacion/rendicion.md`.

## Endpoints (rendicion.md §12)

| Ruta | Pantalla | Permiso |
|---|---|---|
| `GET /api/obras/{id}/rendicion` | W-11, W-44 | `rendicion` ≥ ver con alcance (403 / 404) |
| `GET /api/obras/{id}/rendicion/rubros/{clave}?orden=&dir=` | W-36 | ídem |
| `GET /api/obras/{id}/rendicion/por-proveedor?rubro=&desde=&hasta=` | W-37 | ídem |
| `GET /api/rendiciones` | W-12 | `rendicion` ≥ ver (alcance propias: solo sus obras) |
| `GET /api/obras/{id}/control-cantidad` | W-48 | `control_cantidad` ≥ ver con alcance |
| `POST /api/obras/{id}/materiales` · `PATCH` · `DELETE /api/materiales/{id}` | W-48 | `control_cantidad` = editar con alcance (idempotencia y 409) |
| `GET /api/obras/{id}/materiales/opciones?buscar=` | W-10, W-46 | `control_cantidad` ≥ ver, `gastos` = editar o `rendicion` = editar |

## Qué publica y qué usa

| Pieza | Para qué |
|---|---|
| `Contracts\CatalogoDeRubros` (`CatalogoDeRubrosEloquent`) | La lista cerrada de rubros para Gastos, Obras y Proveedores. |
| `Contracts\MaterialesDeObra` (`MaterialesDeObraEloquent`) | Opciones, pertenencia y alta de lo "fuera de presupuesto" para Gastos. |
| Puertos de Obras `AvanceGastoDeObra` (`AvanceGastoDeObraEloquent`) y `MaterialesDePresupuesto` | "% gastado" de W-05 / W-07 y RN-OBR-24. Registrados con `bind`. |
| Usa `Gastos\Contracts\GastadoDeObras` y `Obras\Contracts\ObrasConsulta` | Lo gastado (única fuente, ADR-007) y lo presupuestado. |
| Cache `rendicion:obra:{id}:g{n}` (`Application\Rendicion\GastadoEnCache`) | Solo lo gastado por rubro, con la generación de la obra (invalidar la sube: una lectura vieja no pisa la invalidación); lo invalidan `GastoImputado`, `GastoCorregido`, `GastoAnulado` y `PresupuestoMontosCambiados` (`InvalidarRendicion`). |
| Evento `MaterialCambiado` y `Relation::morphMap`: `material` | Cambios de la lista en `actividades` (F-14). |

## Capas

- `Http/` — controllers, form requests, resources. Única capa que conoce HTTP.
- `Application/` — casos de uso (Actions), queries y DTOs. Orquesta Domain e Infrastructure.
- `Domain/` — la cuenta de la rendición (`Calculo/`), renglones (etiquetas, orden, por proveedor), materiales; sin `Illuminate\Http` ni Facades.
- `Infrastructure/` — Eloquent e implementaciones de los contratos. Privada del módulo: ningún otro módulo la importa.
- `routes.php` — rutas del módulo (prefijo `/api`, middleware `api`), las registra `RendicionServiceProvider`.
