# ADR-007 · Rendición calculada desde una única fuente: los renglones

- **Estado:** Aceptada (Juani, 2026-11-12) · **Fecha:** 2026-11-12 · **Rol:** CTO + DBA

## Contexto
RN-07: saldo por rubro = presupuestado − imputado, recalculado en cada carga. Criterio de
aceptación 2: "una factura se carga una sola vez y queda imputada en todas las obras". Nada se
puede contar dos veces: stock contra consumo, combustible por carga contra factura, retiros por
remito contra factura (definiciones C10, C13 y C14).

Volumen real: unas 5 a 15 obras activas; la rendición más grande tiene ≈165 gastos. Un
`SUM … GROUP BY rubro` indexado tarda milisegundos.

## Decisión
- **Una sola fuente del gastado: `gasto_renglones`.** Todo lo que resta en una rendición es un
  renglón con `obra_id` y `rubro_id`: facturas, sin comprobante, combustible por carga, consumo
  de stock, amortización, retiros por remito, devoluciones (negativas), horas y fijos por
  período y el gastado inicial de la carga (F-62). No hay otra tabla que sume.
- **Sin tabla proyectada.** La rendición es un query object (`RendicionDeObraQuery`) que agrega
  lo presupuestado (por rubro, sumando todos los presupuestos de la obra) y lo gastado (por
  rubro, sin gastos anulados), aplica EPP→INSUMOS en el resumen y marca rubros pasados. Se
  cachea por obra en Redis; `GastoImputado`, `GastoCorregido` y `GastoAnulado` invalidan la clave.
- **Sin doble conteo por construcción:**
  - Compra a stock = renglón **sin obra**; consumo = renglón sin documento **con obra**.
  - Combustible y Tecnobahía se imputan por carga o remito; su factura no se carga (queda en Tango).
  - Un renglón tiene una sola obra: si va a dos, se divide (C11).
  - Anular no es borrar: el renglón anulado deja de sumar y queda en el historial.
- El control por cantidad (F-66) lee los mismos renglones (`material_id` opcional): lo retirado
  de un material es la suma de sus renglones. Una carga, dos vistas.

## Consecuencias
- Si el volumen creciera 100 veces, se agrega una tabla de saldos alimentada por los mismos
  eventos, sin cambiar la API.
- Las consultas de rendición llevan test de "no se cuenta dos veces" y EXPLAIN documentado en
  `docs/modelo-datos.md`.
