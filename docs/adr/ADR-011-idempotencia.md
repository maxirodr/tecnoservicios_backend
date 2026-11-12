# ADR-011 · Idempotencia en el backend

- **Estado:** Aceptada (Juani, 2026-11-12) · **Fecha:** 2026-11-12 · **Rol:** CTO

## Decisión
- Los POST que crean o hacen transiciones (pedido, gasto, comentario, tomar, completar,
  bloquear, reabrir, reasignar) aceptan `Idempotency-Key` (UUID). El front genera uno por envío
  de formulario y lo reutiliza en los reintentos.
- Middleware `Idempotent`: guarda `(user_id, key, ruta)` → respuesta en la tabla
  `idempotency_keys` (24 h). Misma clave → misma respuesta, sin re-ejecutar. Clave reusada con
  otro payload → 422.
- Restricciones únicas donde el dominio las tiene: aviso con `dedupe_key` único (recordatorio =
  pedido + fecha de entrega); N.º de presupuesto + revisión único.
- Las transiciones son idempotentes por sí mismas: completar un pedido ya completado devuelve
  409 con el estado actual, sin un segundo aviso.
