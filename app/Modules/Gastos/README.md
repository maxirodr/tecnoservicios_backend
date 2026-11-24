# Módulo Gastos

**Propósito:** Facturas y gastos repartidos por renglón (obra + rubro), gastos sin comprobante, combustible, stock propio, amortización, retiros con remito, devoluciones y montos por período (horas y fijos); listado, detalle, corrección y anulación. Los renglones son la única fuente del gastado (ADR-007).

**Funciones del documento firmado que cubre:** F-39, F-40, F-41, F-42, F-43, F-44, F-45, F-46, F-47; el dato de F-54 (horas y fijos por período); F-57 (implementa el puerto `GastadoPorProveedor` de Proveedores); el grupo Gastos de F-03 y F-14 en gastos.

**Reglas:** ver `docs/especificacion/gastos.md` (y `rendicion.md` §12.4.1 para lo que garantiza a Rendición).

## Contratos

- **Expone** `Contracts/GastadoDeObras` (lo usa Rendición) y los eventos `Domain/Events/{GastoImputado, GastoCorregido, GastoAnulado}` (después del commit).
- **Implementa** `Proveedores\Application\Ports\GastadoPorProveedor`, `Shared\Busqueda\FuenteBusqueda` (`gastos`) y `Shared\Adjuntos\AutorizaAdjuntos` (`comprobante_gasto`, `remito`).
- **Usa** `Obras\Contracts\ObrasConsulta`, `Proveedores\Contracts\ProveedoresConsulta`, `Rendicion\Contracts\{MaterialesDeObra, CatalogoDeRubros}` e `Identidad\Contracts\Permisos`.

## Capas

- `Http/` — controllers, form requests, resources y el middleware `PuedeCargarGastos` (la celda de la carga según `origen`).
- `Application/` — `Acceso/` (matriz y alcance), `Carga/` (reglas y alta), `Correccion/` (corregir y anular), `Lecturas/` (listado, detalle, historial, W-46, opciones).
- `Domain/` — modelos `Gasto` y `GastoRenglon`, `Origen`, `TipoDocumento`, `TotalDeRenglon`, `NumeroComprobante`, etiquetas y eventos.
- `Infrastructure/` — `GastadoDeObrasEloquent`, `GastadoPorProveedorEloquent`, `GastosEnBuscador` y los filtros de W-33 en `Consultas/`.
- `routes.php` — rutas del módulo (prefijo `/api`, middleware `api`), las registra `GastosServiceProvider`.
