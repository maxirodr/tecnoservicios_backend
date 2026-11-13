# ADR-009 · Autenticación

- **Estado:** Aceptada (Juani, 2026-11-12) · **Reemplazada en parte por [ADR-015](ADR-015-ingreso-con-codigo-por-mail.md)** (2026-11-13) · **Fecha:** 2026-11-12 · **Rol:** CTO + Seguridad

> **Nota (13-nov):** ADR-015 reemplaza el ingreso con contraseña, "recordarme", la recuperación,
> el cambio de contraseña y el mail para elegir la primera contraseña: ahora se entra con el mail
> y un código por mail, con sesión de 90 días desde el último uso. Sigue vigente: Sanctum SPA con
> cookie, sin Google, el inactivo no entra y las cuentas las crea gerencia. Lo de abajo queda como historia.

## Decisión
- Sanctum en modo SPA (cookie de sesión + CSRF), front y API bajo el mismo dominio padre.
- Ingreso con **mail de la empresa + contraseña** (W-16), "recordarme en este equipo" y
  recuperación por mail con el broker de Laravel (token de 60 min, mail en cola). Sin Google:
  W-16 deja la elección a NexoSmart y así no dependemos de la cuenta de Google del cliente.
- Throttle: login 5 intentos/min por mail + IP; recuperación 3/h por mail. Las respuestas no
  revelan si el mail existe.
- Un usuario inactivo no entra (W-39: no se borra a nadie). Contraseñas con
  `Password::defaults()` (mínimo 10). El cambio de contraseña en Mi perfil (F-10) pide la actual.
- Las cuentas las crea gerencia (F-58); la persona recibe un mail para elegir su contraseña
  (mismo flujo que la recuperación).
