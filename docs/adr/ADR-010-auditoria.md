# ADR-010 · Registro de quién hizo qué

- **Estado:** Aceptada (Juani, 2026-11-12) · **Fecha:** 2026-11-12 · **Rol:** CTO

## Decisión
- Tabla `actividades`: `sujeto_type`, `sujeto_id`, `user_id`, `accion` (creado, editado, tomado,
  bloqueado, retomado, comentado, reasignado, completado, reabierto, estado_cambiado, anulado…),
  `cambios` JSON (`{campo: {antes, despues}}`), `created_at`. Solo inserción.
- Se escribe desde los casos de uso, no con observers genéricos de Eloquent: cada acción queda
  con su nombre de negocio y dentro de la misma transacción.
- Alimenta el recorrido del pedido (F-34), el historial de la obra (F-20, W-23) y el del gasto
  (F-46, W-34). Cubre F-14.
- Los comentarios del pedido tienen su propia tabla (son contenido, no auditoría), pero también
  dejan una actividad `comentado`.
