# Borrador — QA Lead (ingreso y sesión, cola y mails, scheduler, errores de servidor)

### LEAD-01 · Si la sesión vence a mitad de un formulario, se pierde lo escrito y no se dice por qué
- **Severidad:** alta
- **Tipo:** funcional
- **F-XX / pantalla:** F-01 (sesión vencida) · W-38 Alta de proveedor · `/proveedores` → "+ Nuevo proveedor" (aplica a todo formulario)
- **Persona:** QA-LEAD Uno (Compras)
- **Pasos:** 1. Entrar y abrir "+ Nuevo proveedor". 2. Escribir el nombre "QA-LEAD Proveedor sesión vencida". 3. Simular el vencimiento de la sesión (se borran las cookies del navegador, lo mismo que pasa cuando la sesión vence en el servidor). 4. Tocar "Guardar".
- **Esperado:** un aviso claro ("Tu sesión venció. Volvé a ingresar.", el texto ya existe en `src/shared/api/ApiError.ts`) y, después de volver a entrar, volver al formulario sin perder lo escrito (o al menos avisar que hay que volver a cargarlo). El prompt de la prueba lo pide explícitamente ("¿qué ve?, ¿pierde lo escrito?").
- **Obtenido:** el sistema lleva directo a la pantalla de ingreso, vacía, sin ningún mensaje ("Mail · Te mandamos un código para entrar. · Enviar código"). Lo escrito se pierde. Red: `POST /api/proveedores → 401`, `GET /api/me → 401`.
- **Evidencia:** `capturas/LEAD-F01-sesion-vencida-antes.png`, `capturas/LEAD-F01-sesion-vencida-despues.png`
- **Causa probable (opcional):** `src/app/queryClient.ts` (401 global → invalida la sesión y el guard redirige) no muestra el mensaje de `STATUS_NO_AUTENTICADO` ni guarda el borrador.
- **Nota de severidad:** la sesión dura 90 días (`SESSION_LIFETIME=129600`), así que es poco frecuente; pero también pasa si se reinicia Redis (ver LEAD-04), y ahí le pasa a toda la empresa a la vez.

### LEAD-02 · "Ya pediste varios códigos. Probá de nuevo en unos minutos." no dice cuánto esperar
- **Severidad:** media
- **Tipo:** mensaje
- **F-XX / pantalla:** F-01 · W-16 Ingreso · `/ingresar`
- **Persona:** QA-LEAD Uno (Compras)
- **Pasos:** 1. Pedir el código 3 veces en menos de 10 minutos para el mismo mail. 2. Pedirlo una cuarta vez.
- **Esperado:** el prompt de la prueba pide que el bloqueo diga cuánto esperar (p. ej. "Ya pediste 3 códigos. Probá de nuevo en 7 minutos."). El backend conoce el tiempo (`Retry-After` del limitador).
- **Obtenido:** "Ya pediste varios códigos. Probá de nuevo en unos minutos." (`POST /api/auth/pedir-codigo → 429`). Mismo texto para el límite por IP (20 por hora).
- **Evidencia:** `capturas/LEAD-F01-limite-por-mail.png`
- **Causa probable (opcional):** `IdentidadServiceProvider::DEMASIADOS_CODIGOS` es un texto fijo.

### LEAD-03 · Con el worker de mails caído, el usuario espera un código que no llega y el sistema dice que está todo bien
- **Severidad:** media
- **Tipo:** cola-mail
- **F-XX / pantalla:** F-01 · W-16 Ingreso · `/ingresar` · `GET /api/health`
- **Persona:** QA-LEAD Vence (Compras)
- **Pasos:** 1. Parar el proceso `queue:work --queue=mail`. 2. Pedir el código. 3. Esperar 1 minuto. 4. Consultar `/api/health`. 5. Volver a levantar el worker.
- **Esperado:** que la caída del worker se detecte (health "degraded" o un aviso al canal `errors`), porque sin él nadie puede entrar al sistema. Para el usuario, algo que le diga qué hacer si el código no llega (p. ej. "¿No te llegó? Revisá spam o avisale a gerencia").
- **Obtenido:** la pantalla dice "Te mandamos un código a qa-lead-3@empresa-ejemplo.com · Si el mail está registrado, te llegó un código." y, pasado un minuto, solo se habilita "Reenviar código". El pedido queda en la cola (`LLEN queues:mail = 1`). `/api/health` → 200 `"status":"ok"` con el worker parado. Al levantar el worker, el mail sale una sola vez (~2 s), correcto.
- **Evidencia:** `capturas/LEAD-COLA-worker-parado-63s.png` · `logs/cola-worker-parado.log`
- **Causa probable (opcional):** `HealthController` no mira la cola (antigüedad del job más viejo o tamaño de `queues:mail`).

### LEAD-04 · Con Redis caído, `/api/health` tarda 51 s y responde 500 en vez de "degraded"
- **Severidad:** media
- **Tipo:** error-servidor
- **F-XX / pantalla:** transversal · `GET /api/health` y toda la API
- **Persona:** — (monitoreo) / todos
- **Pasos:** 1. Con la app abierta, se cayó Redis (corte externo de Laragon entre 14:49 y 15:53 UTC, ver `logs/corte-mysql-redis.log`). 2. Consultar `/api/health` y usar la app.
- **Esperado:** `/api/health` responde rápido 503 con `"redis": false` (es lo que hace `HealthController` si llega a ejecutarse). La app muestra un mensaje entendible y se recupera sola cuando vuelve Redis.
- **Obtenido:** `/api/health` → 500 en 51,1 s, cuerpo "El sistema tuvo un problema. Volvé a intentar en un momento." (una segunda consulta no respondió en 70 s). 1.614 entradas en el canal `errors` ("No connection could be made … [tcp://127.0.0.1:6379]", con `action: HealthController@index`). En las pantallas, los testers vieron "No pudimos cargar esta información" (bien). Al volver Redis la API respondió, pero **todas las sesiones se perdieron** (toda la empresa tiene que volver a pedir código) y el worker y el scheduler habían muerto (en producción los reinicia Supervisord).
- **Evidencia:** `logs/corte-mysql-redis.log`, `logs/worker-scheduler-corte.log`
- **Causa probable (opcional):** el limitador `throttleApi` (`bootstrap/app.php`) usa el cache en Redis y corre antes que el controlador, así que el health nunca llega a sus `try/catch`; la demora es el timeout de conexión de predis. Para las sesiones: `SESSION_DRIVER=redis`; verificar persistencia (AOF/RDB) del Redis de producción.

### LEAD-06 · El canal `errors` y `/api/errors` se llenan de errores de validación normales
- **Severidad:** baja
- **Tipo:** error-servidor
- **F-XX / pantalla:** transversal · `storage/logs/errors-*.log` · `GET /api/errors`
- **Persona:** — (equipo de desarrollo / `/bug-report`)
- **Pasos:** 1. Usar los formularios con datos inválidos (lo que hace cualquier usuario). 2. Consultar `/api/errors`.
- **Esperado:** el canal `errors` junta solo errores del sistema (5xx, excepciones, jobs fallidos), para que `/bug-report` priorice lo real.
- **Obtenido:** entre las 1.709 entradas del día hay decenas de `WARNING` de validación del usuario: "Ya hay un sector con ese nombre." (9), "Completá este campo." (7), "El CUIT no es válido. Revisá los números." (6), "Ese mail ya es de otro usuario (Laura)." (3)… Incluyen el nombre de otra persona en el mensaje.
- **Evidencia:** `GET /api/errors` 16:37 UTC (conteo por mensaje en el reporte).
- **Causa probable (opcional):** el handler registra las `ValidationException` (422) en el canal `errors`.

### LEAD-05 · Pedir sin sesión cualquier dirección de la API desde el navegador da 500 "Route [login] not defined"
- Duplicado de **OBR-03** (lo vieron también ENT en `errors-*.log`). Se consolida allí.

## Verificado y correcto
- Mail mal formado: "Escribí un mail válido, por ejemplo nombre@empresa.com." al lado del campo.
- Mail inexistente y usuario dado de baja: misma respuesta que uno existente ("Si el mail está registrado, te llegó un código."), **no sale mail** (verificado en el log 20 s después), y al probar un código: "El código no es válido o venció." (RN-IDE-02, no delata qué mails existen).
- Código equivocado: "El código no es válido o venció." · letras: "Escribí los 6 números del código." · código pegado con espacios (" 123 456 "): entra.
- "Reenviar código" con cuenta regresiva (28 s); "Usar otro mail".
- Código vencido (canjeado 11,6 min después de emitido): 422 "El código no es válido o venció."
- Código ya usado / código correcto después de 5 intentos fallidos (quemado): "El código no es válido o venció."
- Más de 10 intentos por minuto para el mismo mail: "Demasiados intentos. Probá de nuevo en un minuto." (bien: dice cuánto).
- Con la API cortada (proceso de `artisan serve` matado) y un alta de proveedor a medio llenar: al tocar "Guardar proveedor", el diálogo queda abierto con lo escrito y dice "No pudimos conectarnos con el sistema. Revisá tu conexión y volvé a intentar." Al volver la API, sin recargar, "Guardar proveedor" de nuevo → "Dimos de alta a QA-LEAD Proveedor API caída." (un solo registro). Capturas `LEAD-ERR-api-caida-guardar.png`, `LEAD-ERR-api-vuelve-reintento.png`.
- `/api/errors` (con token) responde y registró el corte de Redis (1.614) y los "Route [login] not defined" (3). Una caída de la API misma, obviamente, no queda registrada en el backend.
- Dos dispositivos con el mismo usuario: ambos entran; "Salir" (menú del avatar) en uno no cierra el otro. Después de "Salir", ir a `/obras` lleva a ingresar.
- Mail del código: uno solo por pedido, en español, "Tu código para entrar a Tecnoservicios", con nombre, vigencia ("vence en 10 minutos") y "Si no lo pediste, ignorá este mail."; sin datos de más; **no trae link al sistema** (la spec no lo pide; observación).
- Cola: los dos jobs (`EnviarCodigoJob` y `CodigoIngresoMail`) corren en la cola `mail`; con el worker igual al de producción (`--rest=1`) salen de a uno por segundo.
- Scheduler: `adjuntos:purgar-huerfanos` ("Adjuntos huérfanos purgados: 0") e `idempotencia:purgar` ("Idempotency-Key purgadas: 0") corren sin error; `schedule:list` los muestra cada hora.
- Alta de usuario: no manda mail (RN-IDE-09; verificado por ADM).

## Observaciones de entorno (no son hallazgos del sistema)
- El comando del worker del prompt (`queue:work --tries=3`) solo escucha la cola `default`; los mails van por `mail` (como en `config/supervisor.php`). Se corrigió en el entorno de la prueba. Actualizar el prompt.
- Límite de 20 pedidos de código por IP por hora: en la empresa (~50 personas detrás de una misma IP pública) un lunes con sesiones perdidas (p. ej. tras un reinicio de Redis, LEAD-04) bloquearía el ingreso de la mayoría durante una hora. Revisar el límite o la IP de salida con el cliente.
- El dispositivo "iPhone SE" de Playwright es el de 320×568; el rol UXM forzó 375×667.
