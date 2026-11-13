# ADR-015 · Ingreso con código por mail, sin contraseña

- **Estado:** Aceptada · **Fecha:** 2026-11-13 · **Decide:** Juani (cambia F-01 del documento firmado) · **Rol:** CTO + Seguridad
- **Reemplaza en parte:** ADR-009 (el ingreso con contraseña, la recuperación, la primera contraseña y "recordarme").
  Sigue vigente de ADR-009: Sanctum SPA con cookie, sin Google, el inactivo no entra y las cuentas las crea gerencia.

## Contexto
- El documento firmado (F-01, W-16) dice "usuario y contraseña, y recuperación por mail".
- El cliente **no es técnico** y ya **abandonó un software por complejo** (riesgo n.º 1: la adopción).
  Una contraseña más para recordar, el "Olvidé mi contraseña" y el mail de bienvenida con un link
  que vence son justo el tipo de fricción que hace que la gente vuelva a la planilla.
- Mucha gente lo va a usar desde el celular en obra: entrar seguido tiene que ser corto, y una
  vez adentro no tiene que volver a pedirle nada por meses.

## Decisión
- **Se entra con el mail y un código de 6 dígitos que llega por mail.** No hay contraseñas en
  ningún lado (ni en la base, ni en pantallas, ni en mails).
  1. La persona escribe su mail → `POST /api/auth/pedir-codigo`.
  2. Le llega un mail con el código → lo escribe → `POST /api/auth/ingresar` y queda adentro.
- **El código:** 6 dígitos numéricos, vence a los **10 minutos**, sirve **una sola vez**, **5
  intentos fallidos** lo invalidan, **pedir uno nuevo invalida el anterior**. Se guarda solo su
  HMAC-SHA256 con la `APP_KEY` (nunca en claro, ni en logs: el campo `codigo` se excluye del
  registro de errores). Tabla `codigos_ingreso`, una fila por usuario.
- **Sin revelar cuentas:** solo los usuarios **activos** reciben código, pero la respuesta de pedir
  código es **siempre la misma** ("Si el mail está registrado, te llegó un código."). Al ingresar,
  código incorrecto, vencido o usado, usuario inactivo o inexistente dan el mismo error
  ("El código no es válido o venció.").
- **Límites (429):** pedir código, 3 cada 10 minutos por mail y 20 por hora por IP; ingresar,
  10 intentos por minuto por mail y 30 por IP.
- **El mail** es de cuenta (no un "aviso"): por la cola `mail` (`QueuedMailable`), desde noreply,
  texto corto en las palabras del cliente.
- **Sesión:** 90 días **desde el último uso** (`SESSION_LIFETIME=129600`; cada pedido renueva el
  plazo) y `expire_on_close = false`: cerrar el navegador no cierra la sesión. No hay
  "recordarme". "Salir" la cierra. Desactivar a un usuario corta su sesión en el próximo pedido.
- **Alta de usuario:** gerencia lo da de alta y la persona **entra directo con su mail** (no hay
  mail de bienvenida ni reenvío). El primer usuario de producción se crea con
  `php artisan identidad:crear-administrador` y entra igual.
- Se eliminan: olvidé/elegir contraseña, cambiar contraseña en Mi perfil, reenviar mail de
  bienvenida, los brokers de contraseña de `config/auth.php`, `users.password`,
  `password_reset_tokens` y `password_bienvenida_tokens` (migración reversible
  `2026_11_13_400000_ingreso_con_codigo_por_mail`).

## Consecuencias
- **Depende de que el mail llegue rápido.** Si Resend o el worker de la cola `mail` se demoran,
  nadie puede entrar. El worker de mail tiene que estar siempre arriba (Supervisord) y conviene
  vigilar la demora de la cola. Las sesiones largas (90 días) hacen que se pida código pocas veces.
- La seguridad de la cuenta pasa a ser la del **buzón de mail** de cada persona (quien lee el
  mail, entra). Es el mismo nivel que ya tenía "Olvidé mi contraseña".
- Una sesión robada (cookie) dura hasta 90 días sin uso; se corta desactivando al usuario.
- **Cambia F-01 del documento firmado** (y con eso el cambio de contraseña de F-10 y la primera
  contraseña de F-58/DU-03). **Hay que avisarle al cliente** antes de la entrega de la semana 1
  (pendiente de Juani en `tasks/todo.md`).
- El front cambia W-16 (mail → código) y W-19 (sin "Cambiar contraseña"), y W-15 pierde
  "Reenviar mail de bienvenida".
