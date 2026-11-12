# ADR-012 · Avisos

- **Estado:** Aceptada (Juani, 2026-11-12) · **Fecha:** 2026-11-12 · **Rol:** CTO

## Decisión
- Laravel Notifications con canal `database` propio (tabla `avisos`, para tener `tipo`,
  `pedido_id` y `dedupe_key` indexados) y canal `mail`.
- Disparo por eventos de dominio de Pedidos: `PedidoAsignado`, `PedidoReasignado`,
  `PedidoTomado`, `PedidoBloqueado`, `PedidoComentado`, `PedidoCompletado`, `PedidoReabierto`.
  Un listener por evento en el módulo Avisos decide destinatarios según el cap. 7 (propietario,
  solicitante, superior del solicitante, seguidores). Nadie recibe aviso de lo que hizo él mismo.
- Mail: por defecto solo la asignación y la reasignación (F-09); el resto, si la persona lo
  activó en Mi perfil (`preferencias_aviso`). Mailable en cola `mail`, Resend, 1 mail/s
  (rate limiter de Redis), from noreply + reply-to configurable.
- Recordatorio "falta 1 día" (F-37): comando programado cada hora con `schedule:work` en
  Supervisord. Busca pedidos abiertos con entrega mañana y crea el aviso con
  `dedupe_key = recordatorio:{pedido}:{fecha_entrega}`. Solo dentro del sistema.
