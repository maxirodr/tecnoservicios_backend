# ADR-003 · Monolito modular con capas limpias

- **Estado:** Aceptada (Juani, 2026-11-12) · **Fecha:** 2026-11-12 · **Rol:** CTO

## Contexto
≈13 usuarios, un solo equipo, 4 semanas: microservicios no se justifican. Pero el documento
pide que las etapas siguientes (indicadores, cotizaciones, herramientas, Tango) "se sumen sin
rehacer nada", y el código tiene que poder extenderlo otro agente sin contexto.

## Decisión
`app/Modules/<Modulo>/` con módulos alineados a los capítulos del documento: **Identidad**
(cap. 11, F-01, F-02, F-10), **Obras** (cap. 6), **Pedidos** (cap. 7, incluye equipos en
reparación), **Gastos** (cap. 8), **Rendicion** (cap. 9), **Proveedores** (cap. 10),
**Avisos** (F-08, F-09, F-37, F-64), **Importacion** (F-62). Más `app/Shared/` para lo
transversal (envelope JSON, value objects comunes, auditoría, idempotencia).

Capas por módulo, dependencias solo hacia adentro:

```
Http  ──►  Application  ──►  Domain  ◄──  Infrastructure
(controller, FormRequest,   (un caso de uso   (modelos, VOs,     (queries complejas,
 Resource, Policy)           por clase, DTOs,  estados, reglas,   mail, lectores xlsx)
                             transacción)      eventos)
```

- Un módulo usa a otro **solo** por su `Contracts/` (interfaz pública) o escuchando sus
  eventos de dominio. Nunca escribe tablas ajenas. Leer por FK con Eloquent (`belongsTo`) está
  permitido; escribir, no.
- Cada módulo registra sus rutas desde su ServiceProvider: sumar un módulo no toca archivos
  centrales.
- Los modelos Eloquent viven en `Domain/Models`: concesión pragmática (Eloquent es Active
  Record). Las reglas puras (transiciones, prioridad, cálculos) van en clases sin framework,
  testeables en unit.
- Un test de arquitectura falla si `Domain` importa `Http` o Facades, o si un módulo importa la
  `Infrastructure` de otro.

## Consecuencias
- Indicadores (etapa siguiente) será un módulo nuevo que lee los contratos de Rendicion.
- Herramientas absorberá Equipos en reparación moviendo el submódulo, sin migrar datos.

## Enmienda 2026-11-13: Application y Http usan la Infrastructure de su propio módulo

- **Decide:** Juani · **Origen:** TL-02 de `docs/seguridad.md`. El diagrama de arriba (`Application → Domain ←
  Infrastructure`) no describía el código ni lo que el test permitía: los casos de uso usan modelos Eloquent y
  query objects del propio módulo. Se corrige el ADR, no el código.
- **Permitido:** `Application` y `Http` **pueden** usar la `Infrastructure` **de su propio módulo** (modelos
  Eloquent, query objects y sus filtros, servicios técnicos como `CodigosDeIngreso` o `MailsDeCuenta`).
- **Sigue prohibido:**
  - `Domain` → `Infrastructure` (ni la propia), `Http` (de cualquier módulo), `Illuminate\Http` y Facades.
  - Cualquier capa de un módulo → la `Infrastructure` de **otro** módulo (se usa su `Contracts/` o sus eventos).
  - Un puerto (`Application/Ports`) sigue siendo la forma de depender de algo que implementa **otro** módulo
    (p. ej. `PedidosDeObra`, que implementa Pedidos).
- **Ubicación única de los query objects:** el punto de partida (`ObrasQuery`, `ProveedoresQuery`) y sus
  filtros componibles (`PorResponsable`, `PorNombre`…) viven en `<Modulo>/Infrastructure/Consultas/`. Los casos
  de uso de lectura (`ListarObrasQuery`, `ListarProveedoresQuery`) quedan en `Application` y los componen.
  Proveedores tenía los suyos en `Application/Filtros` y `Application/ProveedoresQuery`: se movieron a
  `Infrastructure/Consultas` (cambio mecánico de namespace).
- **Lo verifica** `tests/Architecture/ModuleBoundariesTest`: `test_application_y_http_pueden_usar_la_infrastructure_de_su_propio_modulo`,
  `test_ningun_modulo_usa_la_infrastructure_de_otro_modulo`, `test_domain_no_usa_infrastructure_ni_la_propia` y
  `test_los_query_objects_y_filtros_viven_en_infrastructure_consultas`, además del barrido de todo `app/Modules`.

```
Http ──► Application ──► Domain
  │           │            ▲
  └─────┬─────┘            │
        ▼                  │
  Infrastructure (propia) ─┘        Otro módulo: solo Contracts/ o eventos
```
