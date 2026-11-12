# ADR-009 · Autenticación

- **Estado:** Propuesta · **Fecha:** 2026-11-12 · **Rol:** CTO + Seguridad

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
