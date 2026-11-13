# Módulo Obras

**Propósito:** el legajo de la obra: alta, listado por estado con búsqueda, ficha con presupuestos
(principal y adicionales), documentos y contactos, edición con historial, estados Confirmada → En
ejecución → Terminada y el presupuesto por rubro de cada presupuesto (+ monto de la obra).

**Funciones del documento firmado:** F-15, F-16, F-17, F-18, F-19, F-20, F-21, F-22, F-23, F-48 (dato),
F-54 (dato: monto de la obra), F-14 (obras) y el grupo Obras de F-03. Historias HU-012…HU-019.
Reglas: `docs/especificacion/obras.md` (RN-OBR-01…37).

## Endpoints (`routes.php`, prefijo `/api`, todos con sesión)

| Ruta | Permiso (matriz) | F-XX |
|---|---|---|
| `GET obras?estado=&buscar=&responsable_id=&page=` · `GET obras/clientes?buscar=` | `obras` ver (alcance propias filtra) | F-15, F-03, F-22 |
| `POST obras` (idempotent) | `obras` editar (propias: solo se pone a sí mismo) | F-16, F-23 |
| `GET obras/{id}` · `GET obras/{id}/historial` | `obras` ver + obra visible (si no, 404 "Obra no encontrada.") | F-17, F-20 |
| `PATCH obras/{id}` | `obras` editar + obra visible | F-20 |
| `GET obras/{id}/pedidos-abiertos` · `POST obras/{id}/estado` (idempotent) | ver la obra + (responsable **o** `estado_obra` editar) | F-21 |
| `GET obras/{id}/presupuestos/sugerir-numero` · `POST obras/{id}/presupuestos` (idempotent) | `obras` ver · editar | F-18 |
| `PATCH presupuestos/{id}` · `DELETE presupuestos/{id}` | `obras` editar | F-18 |
| `GET obras/{id}/presupuesto-por-rubro` · `PUT presupuestos/{id}/montos` | `presupuesto_rubro` ver · editar (con alcance) | F-48 |
| `PUT presupuestos/{id}/monto-obra` | `monto_obra` editar (con alcance) | F-54 (dato) |
| `POST obras/{id}/documentos` (idempotent) · `PATCH/DELETE documentos/{id}` | `obras` editar | F-19 |

`monto_obra` no viaja en el JSON (ni la clave) a quien no tiene `monto_obra ≥ ver` para esa obra; las
líneas del historial de montos y de monto de la obra se filtran igual (RN-OBR-28, 36).

## Qué publica y qué espera

| Pieza | Para qué |
|---|---|
| `Contracts\ObrasConsulta` (impl. `ObrasConsultaEloquent`) | `existe`, `resumen`, `esResponsable`, `obrasDondeEsResponsable` (alcance "solo sus obras" de otros módulos), `presupuestadoPorRubro` (rendición s3). |
| `Identidad\Contracts\ObrasDelResponsable` (impl. `ObrasDelResponsableEloquent`) | Obras activas (no terminadas) de una persona, para el aviso al desactivarla (RN-IDE-24). |
| Eventos `ObraCreada`, `ObraEstadoCambiado`, `PresupuestoMontosCambiados` | Después del commit. |
| Puertos `Application\Ports\PedidosDeObra` (Pedidos, s2) y `AvanceGastoDeObra` (Rendicion, s3) | Con implementación nula (`null` = "—") registrada con `bindIf`: el módulo que llega registra la suya. |
| Contextos de adjuntos `presupuesto`, `aprobacion`, `documento_obra` → `Application\Acceso\ObraAdjuntos` | Bajar = `obras` ver con alcance; quitar = `obras` editar (RN-IDE-47/48). |
| Fuente `obras` del buscador general (`Infrastructure\ObrasEnBuscador`) | N.º de cualquier presupuesto, cliente, planta o trabajo, todos los estados, alcance propias. |
| `Relation::morphMap`: `obra`, `presupuesto`, `obra_documento` | `sujeto_type` estable en `actividades` y `adjuntos`. |

El catálogo `rubros` es de Rendicion (migración `2026_11_13_300001`, modelo `Rendicion\Domain\Models\Rubro`);
Obras es dueño de `presupuesto_montos` (`2026_11_14_200001`, corre después del catálogo).

## Capas

- `Http/` — controllers delgados, FormRequests (normalizan N.º y revisión con los value objects), Resources.
- `Application/` — un caso de uso o query por clase: `Obras/`, `Presupuestos/`, `Documentos/`, `Estado/`,
  `Montos/`, `Historial/` (texto "Qué cambió"), `Acceso/` (`AccesoObra` = matriz + alcance + responsable,
  `BuscarObra` = 404 fuera de alcance / 403 sin editar), `Ports/`.
- `Domain/` — modelos, enums (`ObraEstado` con su tabla de transiciones), `Estados\ObraStateMachine`,
  value objects `NumeroPresupuesto` y `Revision`, eventos.
- `Infrastructure/` — `Consultas\ObrasQuery` + filtros componibles (`PorEstado`, `PorBusqueda`,
  `PorResponsable`), implementaciones de contratos y puertos nulos.

Tests: `tests/Feature/Obras/`, `tests/Unit/Obras/`.
