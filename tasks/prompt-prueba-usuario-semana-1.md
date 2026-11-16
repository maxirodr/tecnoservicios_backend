# Prompt — Prueba de usuario completa de la Semana 1 (Tecnoservicios, NS-2026-025)

> Pegá este archivo entero como primer mensaje de una sesión nueva de Claude Code
> (o decile: "Leé y ejecutá `C:\NexoSmart\Projects\tecnoservicios_project\tecnoservicios_backend\tasks\prompt-prueba-usuario-semana-1.md`").

---

## 0. Misión

Sos el **equipo de QA profesional de NexoSmart** para el proyecto NS-2026-025 (sistema interno de
Tecnoservicios Bahía SA, empresa constructora de ~50 personas). La Semana 1 está terminada y en `main`.
Tu trabajo es **usar el sistema como lo usaría el cliente**, persona por persona y pantalla por pantalla,
con todo andando de verdad (backend, front, cola de mails, scheduler, Redis), y dejar **reportes
escritos** que otra sesión (la de desarrollo) va a leer para arreglar todo.

**No arreglás código.** No editás nada en `app/`, `src/`, `tests/`, migraciones ni config. Solo
levantás el entorno, probás, capturás evidencia y escribís reportes. Si algo te impide seguir
(el sistema no arranca), lo documentás como hallazgo bloqueante, buscás la forma de rodearlo sin
tocar código (otra persona, otro camino) y seguís.

El criterio que importa: **una persona de la empresa, sin saber nada técnico, tiene que entender
siempre qué pasó y qué hacer.** Nunca debe ver un error de SQL, un "Server Error", un 500, un texto
en inglés, una clave cruda tipo `validation.required`, `undefined`, `null`, `[object Object]`,
NaN, ni una pantalla en blanco.

## 1. Fuentes de verdad (leelas antes de probar)

Rutas absolutas:
- Backend: `C:\NexoSmart\Projects\tecnoservicios_project\tecnoservicios_backend`
- Frontend: `C:\NexoSmart\Projects\tecnoservicios_project\tecnoservicios_frontend`
- Documento firmado por el cliente (HTML): `C:\NexoSmart\Projects\tecnoservicios_project\tecnoservicios\docs\entregas\2026-10-19-documento-sdr\documento-sdr.html`
  (capítulos 5, 6, 10, 11 y las maquetas W-XX de esos capítulos)
- Especificaciones: `tecnoservicios_backend\docs\especificacion\identidad.md`, `obras.md`, `proveedores.md`
- Decisiones: `tecnoservicios_backend\docs\adr\` (sobre todo ADR-014 archivo o link, ADR-015 ingreso con código por mail), `docs\dudas.md` (resueltas)
- QA previo: `docs\qa\matriz-trazabilidad.md`, `docs\qa\plan-de-pruebas.md`, `docs\qc\entrega-1.md` (bugs B-01…B-05 ya arreglados: re-verificalos)
- Notas al cliente: `docs\entregas\semana-1.md`
- Lecciones del proyecto: `tasks\lessons.md` (leelas: hay trampas de entorno conocidas)

**Funcionalidades de la Semana 1 a cubrir (todas):**
F-01 Ingreso con mail + código · F-02 Menú según el sector · F-03 Buscador general (obras y proveedores) ·
F-10 Mi perfil · F-14 Registro de quién hizo qué (obras y proveedores) · F-15 Listado de obras por estado ·
F-16 Alta de obra · F-17 Ficha de la obra con pestañas · F-18 Presupuestos (principal y adicionales) ·
F-19 Documentos y contactos (archivo **o** link; carpeta Drive solo link) · F-20 Editar la obra ·
F-21 Cambiar estado de la obra · F-22 Obras terminadas consultables · F-23 Fechas previstas ·
F-48 Presupuesto por rubro (carga) · F-55 Proveedores con buscador · F-56 Alta y edición de proveedor ·
F-58 Usuarios: alta, edición y baja · F-59 Sectores · F-60 Tabla de permisos · F-61 Jerarquía ("depende de") ·
F-68 Plazos de la prioridad · más lo transversal: adjuntos, cerrar sesión, sesión vencida, 403/404,
celular, mails y cola.

Lo que es de semanas siguientes (pedidos, gastos, rendición, avisos de pedidos) **no** es hallazgo si
falta; sí es hallazgo si aparece a medio hacer, roto o con un error.

## 2. El equipo (roles que asumís; usá subagentes en paralelo donde se pueda)

| Rol | Qué hace |
|---|---|
| **QA Lead** (vos, en la sesión principal) | Levanta y cuida el entorno, reparte el trabajo, es el único que resetea la base, consolida y deduplica hallazgos, escribe el resumen final. |
| **Tester funcional por persona** (1 subagente por persona) | Recorre el sistema como esa persona real: Dirección, Gerencia de Administración, Jefatura de Proyectos, Ingeniería de proyectos, Compras. Prueba lo que su sector puede y verifica que lo que no puede no aparezca ni funcione. |
| **QA de entradas y errores** | Ataca cada formulario: vacío, espacios, muy largo, emojis, acentos/ñ, mayúsculas, CUIT inválido o repetido, mail mal formado o repetido, fechas al revés, números negativos/0/decimales con coma y punto, montos enormes, archivos de 0 B, 20 MB, 21 MB, tipos no permitidos, links `javascript:` y sin `https`, doble clic en Guardar, guardar sin cambios, F5 a mitad de un formulario, volver atrás. |
| **UX writer / revisor de claridad** | Lee cada mensaje, toast, estado vacío, confirmación y error con ojos del cliente: ¿está en español rioplatense claro, sin jerga, dice qué pasó y qué hacer? Marca todo texto técnico, en inglés, ambiguo o que culpa al usuario. |
| **QA de carga y loaders** | Verifica la regla: **el loader va solo en el componente que se está recargando** (botón que guarda, tabla que filtra, sección que trae datos). Nada de spinner de página completa al refrescar, al hacer polling o al guardar una parte; nada de pantalla que parpadea, salta (layout shift) o se vacía mientras recarga; nada de loaders que quedan girando para siempre ni acciones sin ninguna señal de que están pasando. Usá red lenta (DevTools/Playwright: "Slow 3G" o `route` con demora) para verlos. |
| **QA de backend, cola y workers** | Vigila logs y procesos: `storage\logs\*.log`, canal `errors`, `/api/errors`, salida del `queue:work` y del `schedule:work`. Cada 5xx, excepción, job fallido (`failed_jobs`), mail que no sale o sale dos veces, o tarea programada que falla es un hallazgo, **aunque la pantalla lo haya disimulado**. Revisa también la pestaña Network: toda respuesta 5xx o 4xx inesperada se reporta. |
| **QA celular y accesibilidad** | Repite los caminos críticos en viewport Pixel 7 (412×915) e iPhone SE (375×667): nada se corta, no hay scroll horizontal, los botones se tocan, los formularios se pueden completar con el teclado del celu. Navegación con teclado y foco visible en PC. |
| **QC final** | Revisa el reporte consolidado: cada hallazgo es reproducible, tiene evidencia, severidad justificada y no está duplicado. Firma en el reporte. |

Regla de paralelo: hay **un solo entorno**. Los subagentes pueden navegar en paralelo, pero cada uno
crea sus propios datos con un prefijo propio (ej. obras "QA-DIR-…", proveedores "QA-COMP-…") y
**nadie resetea la base salvo el QA Lead**. Si un subagente necesita un estado limpio, se lo pide al Lead.

## 3. Entorno (levantalo vos, con puertos y base propios)

No uses la base de desarrollo (`tecnoservicios`) ni las de tests (`tecnoservicios_testing*`,
`tecnoservicios_integracion`). **Nunca dropees bases.** Usá una base propia:

1. MySQL 8.4 (Laragon) y Redis tienen que estar arriba. Verificá `select 1` y `redis-cli PING`.
   Si MySQL no responde, **no intentes arrancarlo por consola**: pedile al usuario que abra Laragon.
2. Creá (si no existe) la base `tecnoservicios_prueba_usuario` con `CREATE DATABASE IF NOT EXISTS`.
3. `php artisan config:clear` en el backend (si la config está cacheada no toma las variables de abajo).
4. Variables del backend para esta prueba (pisan el `.env`; pasalas como variables de entorno a
   **cada** proceso de artisan que levantes):
   ```
   APP_ENV=local
   APP_DEBUG=false            # así se ve lo que vería el cliente en producción
   APP_URL=http://localhost:8340
   FRONTEND_URL=http://localhost:5316
   DB_DATABASE=tecnoservicios_prueba_usuario
   SANCTUM_STATEFUL_DOMAINS=localhost:5316
   CORS_ALLOWED_ORIGINS=http://localhost:5316
   SESSION_DOMAIN=localhost
   MAIL_MAILER=log
   QUEUE_CONNECTION=redis      # cola REAL, no sync: queremos probar el worker
   REDIS_PREFIX=tecnoservicios_prueba_
   CACHE_PREFIX=tecnoservicios_prueba_cache_
   ```
5. Verificá con `php artisan tinker --execute "echo config('database.connections.mysql.database');"`
   (con esas variables) que apunta a `tecnoservicios_prueba_usuario` **antes** de migrar.
   Recién ahí: `php artisan migrate:fresh --seed` (solo sobre esa base).
6. Procesos (cada uno en background, con las variables de arriba):
   - API: `php artisan serve --host=127.0.0.1 --port=8340 --no-reload`
   - Worker: `php artisan queue:work --tries=3` (producción usa Supervisord; acá lo corremos a mano)
   - Scheduler: `php artisan schedule:work`
   - Front: en el frontend, `VITE_API_URL=http://localhost:8340 npm run dev -- --port 5316 --strictPort`
7. Smoke: `GET http://127.0.0.1:8340/api/health` → 200; abrir `http://localhost:5316` → pantalla de ingreso.
8. **Ingreso:** se entra con el mail y un código de 6 dígitos que llega por mail. Con `MAIL_MAILER=log`
   el mail queda en `tecnoservicios_backend\storage\logs\laravel.log` (buscá el último mail a esa
   dirección después de pedir el código). Usuarios del seeder (ficticios):
   `diego@empresa-ejemplo.com` (Gerencia de Administración), `hernan@…` (Jefatura de Proyectos),
   `laura@…`, `sofia@…`, `pablo@…` (Ingeniería de proyectos), `carla@…` (Compras).
   Para Dirección, creá el usuario desde Usuarios con Diego (es parte de la prueba).
   Antes de empezar, anotá el tamaño de `laravel.log` para separar lo de esta prueba.

**Cómo probar:** con Playwright (ya instalado en el frontend) manejando un navegador real, con
capturas de pantalla de cada paso relevante; o con el navegador que tengas disponible. En cada
pantalla mirá además la consola del navegador (errores JS, warnings de React) y la red (status de
cada request). Al terminar cada bloque, revisá los logs del backend, el worker y `failed_jobs`.

## 4. Qué probar (checklist mínimo; agregá todo lo que se te ocurra)

Para **cada funcionalidad** de la lista del §1:
- **Camino feliz** como la persona que la usa, contra lo que dice el documento firmado y la maqueta.
- **Permisos:** la persona que no tiene acceso no ve el menú, y si entra por URL directa ve un mensaje
  claro (no un 403 crudo ni pantalla en blanco). Probá con "Ver" y con "Editar". Probá IDs ajenos/inexistentes en la URL (404 entendible).
- **Entradas inválidas** (ver rol QA de entradas): cada error tiene que aparecer **al lado del campo**,
  en español claro, sin borrar lo que el usuario ya escribió.
- **Loaders** (ver rol QA de carga): solo donde recarga, nunca de página completa por una acción parcial.
- **Estados vacíos:** listas sin datos dicen algo útil, no una tabla vacía muda.
- **Concurrencia/repetición:** doble clic en Guardar no crea dos registros; dos pestañas editando lo mismo.
- **Persistencia:** F5 después de guardar muestra lo guardado; el registro de cambios (F-14) dice quién, qué y cuándo, en orden.
- **Celular:** los caminos críticos en Pixel 7.

**Ingreso y sesión (F-01):** mail que no existe, mail de usuario dado de baja, código equivocado,
código vencido, código ya usado, pedir código muchas veces seguidas (bloqueo progresivo: el mensaje
tiene que decir cuánto esperar), pegar el código con espacios, cerrar sesión, sesión vencida a mitad
de un formulario (¿qué ve?, ¿pierde lo escrito?), dos dispositivos.

**Mails y cola:** cada mail que manda el sistema en la Semana 1 (como mínimo el del código; buscá en
`app\` los Mailables/Notifications para ver si hay otros, por ejemplo alta de usuario). Verificá:
llega una sola vez, por la cola (aparece en el worker), en español, con el nombre del sistema, sin
datos de más, con links que apuntan al front correcto. **Pará el worker**, pedí un código y verificá
qué ve el usuario (no puede quedar colgado sin explicación); volvé a levantarlo y verificá que el mail
sale. Revisá el ritmo de envío de 1 por segundo si hay varios mails seguidos.

**Scheduler:** que `schedule:work` corra sin errores las tareas `adjuntos:purgar-huerfanos` e
`idempotencia:purgar` (podés forzarlas con `php artisan schedule:run` o el comando directo).

**Adjuntos (ADR-014):** subir archivo, poner link, cambiar uno por otro, borrar, archivo de 20 MB
(tiene que entrar) y de 21 MB (mensaje claro), tipo no permitido, nombre con acentos y espacios,
descargar/abrir lo subido. La carpeta de Drive acepta solo link.

**Errores de servidor forzados (sin tocar código):** con la app abierta, **pará MySQL** un momento
(pedíselo al usuario si no podés) o **pará Redis**, o **cortá la API**, y hacé una acción: el usuario
tiene que ver un mensaje entendible ("No pudimos guardar, probá de nuevo en unos minutos" o similar),
nunca SQL ni stack trace ni "Server Error". Volvé a levantar todo y verificá que la app se recupera
sin tener que recargar a mano. Revisá que `/api/errors` y el canal `errors` hayan registrado el problema.

## 5. Reportes (esto es lo que lee la sesión de desarrollo)

Carpeta: `C:\NexoSmart\Projects\tecnoservicios_project\tecnoservicios_backend\docs\qa\prueba-usuario\semana-1\`

1. **`hallazgos.md`** — un bloque por hallazgo, **con este formato exacto** (la otra sesión lo parsea):

   ```markdown
   ### PU-001 · <título corto en una línea>
   - **Severidad:** bloqueante | alta | media | baja
   - **Tipo:** funcional | permisos | validación | mensaje | loader | error-servidor | cola-mail | scheduler | celular | accesibilidad | consistencia-con-documento
   - **F-XX / pantalla:** F-16 · W-06 Alta de obra · `/obras/nueva`
   - **Persona:** Diego (Gerencia de Administración)
   - **Pasos:** 1. … 2. … 3. …
   - **Esperado:** … (citar documento firmado / spec / RN si aplica)
   - **Obtenido:** … (texto exacto que vio el usuario, entre comillas)
   - **Evidencia:** `capturas/PU-001-1.png` · request `POST /api/obras` → 500 · extracto de log (sin datos personales)
   - **Causa probable (opcional):** archivo:línea si la viste, sin arreglarla
   ```
   Severidad: **bloqueante** = impide usar una funcionalidad o pierde datos; **alta** = error técnico
   visible al usuario (SQL, 500, inglés, pantalla en blanco), permiso roto, dato incorrecto;
   **media** = mensaje confuso, loader indebido, celular roto en un camino secundario; **baja** = detalle visual o de texto.

2. **`reporte.md`** — resumen ejecutivo: entorno usado (commits de back y front con `git rev-parse --short HEAD`),
   personas probadas, tabla **F-XX → ✅ / ⚠️ / ❌ + hallazgos**, conteo por severidad y tipo,
   lista de lo que **no** se pudo probar y por qué, y la firma de cada rol del §2.
3. **`capturas\`** — PNG nombradas `PU-XXX-n.png` (y las de camino feliz como `F-XX-<pantalla>-pc|celular.png`).
4. **`logs\`** — extractos relevantes de `laravel.log`, worker y scheduler (solo las líneas de cada hallazgo).

Reglas de los reportes:
- Un hallazgo = un problema. Si el mismo error aparece en 5 pantallas, un hallazgo con las 5 listadas.
- Texto exacto del usuario entre comillas; nada de "da error".
- Todo dato es ficticio (empresa-ejemplo.com, obras "QA-…"). Ningún dato real, secreto, token ni
  código de ingreso en los reportes ni en commits.
- Re-verificá B-01…B-05 de `docs\qc\entrega-1.md` y decí si siguen arreglados.

## 6. Git

- Rama `qa/prueba-usuario-semana-1` en el **backend** (la carpeta de reportes vive ahí).
- Commits convencionales en inglés (`docs(qa): …`), terminando con
  `Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>`.
- Commiteá y **pusheá** a medida que avanzás (al menos al cerrar cada bloque grande) y al final.
  No mergees a `main`: lo mergea la sesión de desarrollo.
- No commitees `.env`, `.claude\bug-report.json`, ni archivos subidos de prueba de `storage\`.

## 7. Al terminar

1. Pará todos los procesos que levantaste (API 8340, worker, scheduler, front 5316). No toques otros
   servers que ya estuvieran corriendo.
2. Push final de la rama.
3. Respondé con: cantidad de hallazgos por severidad, los 5 más graves en una línea cada uno, qué no
   se pudo probar, y la ruta de `reporte.md`.
