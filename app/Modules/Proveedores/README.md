# Módulo Proveedores

**Propósito:** base única de proveedores para elegirlos al cargar facturas: listado con buscador, ficha,
alta y edición con aviso de parecidos, y "Gastado por obra" (contrato; los datos llegan con Gastos).
Sin saldos, cuenta corriente ni pagos (siguen en Tango, D-040). Sin adjuntos ni links propios (§4.2).

**Funciones del documento firmado:** F-55, F-56, F-57 (contrato), F-42 (parte: endpoint de opciones y
alta rápida), F-44 (parte: proveedor genérico), F-03 (parte: grupo Proveedores del buscador general).
Historias HU-020…HU-022. Reglas completas: `docs/especificacion/proveedores.md` (RN-PRV-01…17).

## Qué publica para otros módulos

| Contrato | Para qué |
|---|---|
| `Contracts\ProveedoresConsulta` | `existe`, `resumen` (nombre, CUIT formateado, clave del rubro habitual), `generico()`, `esGenerico()`. Lo usa Gastos (semana 3). |
| `Application\Ports\GastadoPorProveedor` | Puerto de F-57 que **implementa Gastos** (semana 3) y bindea en su provider; hasta entonces `Infrastructure\GastadoPorProveedorNulo` (vacío). Recibe `AlcanceObras` (`todas` / `propiasDe(userId)`). |
| Grupo `proveedores` del buscador | `Infrastructure\ProveedoresEnBuscador` registrado en `App\Shared\Busqueda\FuentesBusqueda`. |

## Endpoints (`routes.php`, prefijo `/api`, con sesión)

| Ruta | Permiso | F-XX |
|---|---|---|
| `GET proveedores?buscar=&page=` | `proveedores` ver | F-55 |
| `GET proveedores/{id}` | `proveedores` ver | F-55 |
| `GET proveedores/opciones?buscar=` (máx. 20) | `proveedores` ≥ ver **o** `gastos` = editar (FormRequest) | F-42 |
| `GET proveedores/parecidos?nombre=&cuit=&excluir_id=` (máx. 5) | `proveedores` ver | F-56 |
| `POST proveedores` (acepta `Idempotency-Key`) | `proveedores` editar | F-56, F-42 |
| `PATCH proveedores/{id}` | `proveedores` editar | F-56 |
| `GET proveedores/{id}/gastado-por-obra` | `proveedores` ver **y** `gastos` ver | F-57 |

No hay `DELETE` (RN-PRV-11). El buscador general (`GET /api/buscar?q=`) vive en `app/Shared/Busqueda`.

## Reglas clave implementadas

- Tres formas del nombre (`Domain\NombreProveedor`), derivadas al guardar: `nombre_busqueda` (sin
  mayúsculas, tildes ni puntuación: buscador y orden alfabético), `nombre_clave` (además sin forma
  societaria al final, **única**: "Hormigonera Sur S.R.L." = "HORMIGONERA SUR") y `nombre_normalizado`
  (además sin artículos ni "y": base de parecidos).
- CUIT tolerante (`Domain\Cuit`): con o sin guiones o espacios, 11 dígitos con dígito verificador; se
  guarda en dígitos, se muestra `30-00000001-5`, único si está.
- Duplicado de nombre o CUIT → 422 `errors.nombre` / `errors.cuit` + `errors.existente: {id, nombre}`
  (también si dos altas se cruzan: lo garantiza el índice único).
- Parecidos (`Domain\SimilitudDeNombres`): igual, contenido (≥ 4 letras), mitad de las palabras o ≥ 80 %
  letra por letra; el mismo CUIT va primero (`motivo: "cuit"`). No bloquean.
- Genérico "Gastos sin comprobante" creado por migración, uno solo (índice único sobre columna
  generada), no se edita (422) y la ficha lo marca con `puede.editar = false`.
- Actividad (`RegistroActividad`): `creado` y `editado` con antes → después; guardar sin cambios no
  registra. `created_by` / `updated_by` en la tabla.
- Rubro habitual de la lista cerrada `rubros` (13, EPP incluido), por clave en la API.

## Capas

- `Http/` — controllers delgados, FormRequests, Resources.
- `Application/` — casos de uso (`CrearProveedorAction`, `EditarProveedorAction`), queries con filtros
  componibles (`ProveedoresQuery` + `Infrastructure\Consultas\PorNombre`, `Alfabetico`), parecidos, puerto F-57.
- `Domain/` — modelo `Proveedor`, normalización del nombre, CUIT, similitud, `ProveedorDuplicado`.
- `Infrastructure/` — `ProveedoresConsultaEloquent`, `GastadoPorProveedorNulo`, `ProveedoresEnBuscador`.

Tests: `tests/Feature/Proveedores/`, `tests/Unit/Proveedores/`, `tests/Feature/Shared/Busqueda/`.
