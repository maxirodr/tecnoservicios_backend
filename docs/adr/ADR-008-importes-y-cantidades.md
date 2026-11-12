# ADR-008 · Importes y cantidades

- **Estado:** Propuesta · **Fecha:** 2026-11-12 · **Rol:** CTO + DBA

## Decisión
- Importes en pesos, **netos sin IVA** (C9): `DECIMAL(15,2)`. Nunca float.
- Cantidades: `DECIMAL(14,3)` (m², kg, litros). Negativas solo en devoluciones.
- Moneda extranjera: se carga convertida a pesos; la cotización va como texto en el detalle del
  renglón (F-41). No hay campo de moneda.
- `total = round(cantidad × precio_unitario, 2)` lo calcula el backend; el front lo muestra como
  vista previa.
- Value objects en `app/Shared/Domain`: `Importe` (bcmath, escala 2), `Cantidad` (escala 3),
  `NumeroPresupuesto` (normaliza "2-105-2026"), `RangoFechas`.
- En la API los importes viajan como **string decimal** ("1234.50") para no perder precisión en
  JS; el front formatea con `Intl.NumberFormat` en `es-AR`.
