# Módulo Avisos

**Propósito:** Avisos dentro del sistema (campana, lista completa) y por mail (asignación y los que cada uno active), recordatorio de 1 día antes por scheduler. Tabla `avisos` propia (con `dedupe_key` único) + mail por la cola `mail` (Resend, 1 mail/s).

**Funciones del documento firmado que cubre:** F-08, F-09, F-10 (Mis avisos), F-37, F-64

**Reglas:** ver `docs/especificacion/avisos.md`.

## Cómo funciona

- **Eventos → avisos.** Un listener por evento de `Pedidos\Domain\Events` (`Application/Listeners`, cola `default`, 3 intentos). Quién recibe lo decide `Domain\DestinatariosPorEvento` (clase pura); el texto, `Domain\RedactorDeAvisos` con la `FotoDelPedido` del evento. `Application\RegistrarAvisos` crea cada aviso con `createOrFirst` por `dedupe_key` y encola el mail solo si el aviso se acaba de crear.
- **Mail.** `Infrastructure\Mail\AvisoPedidoMail` (extiende `QueuedMailable`) viaja con el id del aviso; al enviarse vuelve a mirar que la persona siga activa y con el mail activado.
- **Recordatorio.** `php artisan avisos:recordatorios`, cada hora desde `routes/console.php` (schedule:work en Supervisord). Sale desde las 8:00 de Argentina; los pedidos los da `Pedidos\Contracts\PedidosParaRecordar`.
- **API.** `GET /api/avisos/no-leidos/contador`, `GET /api/avisos`, `POST /api/avisos/{id}/leido`, `POST /api/avisos/leer-todos`, `GET|PUT /api/me/avisos-por-mail`.
- **Contratos que expone.** `AvisosDelPedido` (recorrido del pedido) y `DestinatariosDeAviso` (el "Se le avisó a…" de W-43).

## Capas

- `Http/` — controllers, form requests, resources. Única capa que conoce HTTP.
- `Application/` — casos de uso (Actions), queries, listeners y DTOs. Orquesta Domain e Infrastructure.
- `Domain/` — modelos, catálogo de tipos, reglas de destinatarios y textos. Sin `Illuminate\Http`, sin Facades, sin nada de `Http/`.
- `Infrastructure/` — mail, comando y la consulta del recorrido. Privada del módulo: ningún otro módulo la importa.
- `routes.php` — rutas del módulo (prefijo `/api`, middleware `api`), las registra `AvisosServiceProvider`.
