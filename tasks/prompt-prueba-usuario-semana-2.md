# Prompt — Prueba de usuario de la Semana 2 (Pedidos y avisos) · Tecnoservicios (NS-2026-025)

> Pegá este archivo entero como primer mensaje de una sesión nueva de Claude Code, o decile:
> "Leé y ejecutá `C:\NexoSmart\Projects\tecnoservicios_project\tecnoservicios_backend\tasks\prompt-prueba-usuario-semana-2.md`".
>
> **Rutas:** relativas a la carpeta del proyecto (`…\NexoSmart\Projects\tecnoservicios_project\`). En una máquina es
> `C:\NexoSmart\Projects\…` y en la otra `B:\ProgramFiles\NexoSmart\Projects\…`. Usá la de la máquina donde corrés.
>
> **Se reusa en cada ronda de la Semana 2** (ronda 1, 2…): el QA Lead actualiza §0 y §2 con lo que quedó abierto y
> el número de ronda en las rutas.

---

## 0. Misión

Sos el **equipo de QA profesional de NexoSmart** del proyecto NS-2026-025 (sistema interno de Tecnoservicios Bahía
SA, constructora de ~50 personas). La **Semana 1 quedó aprobada** (ronda 5). La **Semana 2** —la minuta: pedidos en
los dos sentidos, avisos, mis tareas, jefaturas, calendario, equipos en reparación y celular— está terminada y en
`staging`.

Tu trabajo: **usar el sistema como lo usaría el cliente**, persona por persona, con todo andando de verdad (API,
front, cola de mails, scheduler, Redis) y dejar **reportes escritos** que la sesión de desarrollo va a leer para
arreglar. **No arreglás código**: no editás `app/`, `src/`, `tests/`, migraciones ni config.

El criterio que importa:
- **Una persona de la empresa, sin saber nada técnico, entiende siempre qué pasó y qué hacer.** Nunca ve SQL, "Server
  Error", 500, inglés, claves crudas (`validation.required`), `undefined`, `null`, `[object Object]`, NaN ni pantalla en
  blanco.
- **El criterio global n.º 3 del documento:** "ningún pedido se atrasa sin que el propietario se entere **antes**". Que
  los avisos lleguen (campana en ≤ 15 s, mail de asignación, "vence mañana") es lo más importante de esta semana.

## 1. Fuentes de verdad (leelas antes de probar)

- Documento firmado (HTML): `tecnoservicios\docs\entregas\2026-10-19-documento-sdr\documento-sdr.html` — cap. 4.2,
  5, 7 (avisos del pedido), 13 y las maquetas W-XX de esos capítulos.
- Maquetas: `tecnoservicios\docs\mockups\wireframes.html` (sección `id="W-XX"`).
- Backlog: `tecnoservicios_backend\docs\backlog.md` **§6** (HU-026…HU-060) y **§6.1 "La prueba del cliente de la
  semana 2"** (qué tiene que andar sí o sí).
- Especificaciones: `docs\especificacion\pedidos.md` y `avisos.md` (RN-PED-*, RN-AVI-*, tabla de avisos §6), más
  `identidad.md` y `obras.md` en lo que la Semana 2 toca.
- Decisiones: `docs\dudas.md` — **DU-Q01…Q03 y DU-P01…P24 resueltas el 19-nov: va la propuesta tal como está
  escrita.** Lo que dicen es lo "esperado", no un hallazgo. Las más visibles:
  - Con **Ver** en Pedidos se ve, se comenta y se sigue (DU-P01).
  - Un pedido **en el sector** lo toma cualquiera activo del sector, y queda como propietario (DU-P02).
  - Se puede **bloquear sin tomar** (DU-P03).
  - **Reasignar** a una persona mantiene el estado; al sector vuelve a No iniciada (DU-P04).
  - **No se borran pedidos**: uno cargado por error se completa con "Cargado por error" (DU-P06).
  - La prioridad la corrige a mano **solo quien lo pidió**, con "Usar la calculada" (DU-P07).
  - La **entrega anterior a la fecha de pedido** se acepta y queda Urgente (DU-P08).
  - **Mails:** la asignación y la reasignación van siempre por mail; el resto se activa en Mis avisos. "Vence mañana"
    y el aviso al superior **nunca** van por mail, y **al seguidor nunca le llega mail** (DU-P19, RN-AVI-14).
  - El recordatorio sale **desde las 8:00** de Argentina (DU-P21), en **días corridos**: un lunes se recuerda el
    domingo (DU-Q01).
  - **Editar** un pedido no avisa; queda en el historial (DU-P24).
  - "Vencido" es una entrega antes de hoy, y la semana va de lunes a domingo (DU-P14).
  - El **buscador** busca en la tarea, en "con quién / para quién" y en las aclaraciones, no en los comentarios
    (DU-P15).
  - **Equipos en reparación:** "Fecha" es cuándo se mandó al taller, y uno cargado por error se quita solo mientras
    siga No iniciada (DU-Q03, DU-P18).
- QA previo: `docs\qa\prueba-usuario\semana-1-ronda-5\` (cómo se escribieron los reportes y qué quedó como límite
  conocido: B-02 fecha nativa según el idioma del navegador).
- Lecciones del proyecto: `tasks\lessons.md`.

**Funcionalidades a cubrir (todas las de la Semana 2):**
- **Arrancan esta semana:**
  - Inicio y listas: F-04 Mis tareas · F-05 Pedidos que pedí · F-06 Tareas de mi gente · F-07 Tareas que sigo.
  - Avisos: F-08 Avisos en la campana · F-09 Mails · F-37 Recordatorio "vence mañana" · F-64 Lista de avisos.
  - F-11 Calendario · F-12 Carga por persona · F-13 Celular.
  - El pedido: F-24 Crear · F-25 Fecha de pedido · F-26 Prioridad · F-27 Archivos o links · F-28 Tomar ·
    F-29 Bloquear/retomar · F-30 Comentar · F-31 Completar con comprobante · F-32 Reasignar · F-33 Editar ·
    F-34 Recorrido · F-35 Todos los pedidos y Excel · F-36 Sin obra (Servicios generales) · F-38 Propietario y
    seguidores · F-65 Reabrir.
  - F-67 Equipos en reparación.
- **Se completan esta semana:**
  - F-02 Barra de la Semana 2 · F-03 Buscador, grupo Pedidos · F-10 Mis avisos en Mi perfil · F-14 Historial.
  - Obras: F-15 Pedidos abiertos y fila Servicios generales en el listado · F-17 Pestaña Pedidos de la obra ·
    F-21 Pasar a Terminada con pedidos abiertos.
  - F-58 y F-59 Personas y sectores con pedidos abiertos · F-61 Jerarquía · F-68 Plazos aplicados.

Gastos y Rendiciones son de la Semana 3: que **no** aparezcan no es hallazgo; sí lo es si aparecen a medio hacer.

## 2. El equipo y la asignación de roles

La **sesión principal es el QA Lead**. Lanza los roles como subagentes, uno por rol, con este prompt y su lista, y
les reparte las sesiones guardadas (storageState). **Como máximo 3 subagentes con navegador a la vez.**

| # | Rol | Qué verifica | Navegador |
|---|---|---|---|
| 0 | **QA Lead** | Entorno, ingreso de cada persona y reparto de sesiones, **único que resetea la base**, tandas, consolidación, veredicto (§7). | solo para ingresar |
| 1 | **Pedidos de punta a punta (PC)** | El ciclo completo como lo hace la empresa, en los dos sentidos (operaciones ⇄ administración): Laura (Ingeniería) le pide a Carla (Compras); Carla toma, comenta, bloquea con motivo, retoma y completa con comprobante (archivo **y** link); Laura reabre ("falta algo"); reasignar a otra persona y al sector, y que alguien del sector lo tome; editar (fechas, tarea, obra, prioridad manual y "Usar la calculada") con historial; seguir y dejar de seguir; recorrido completo con las líneas "sistema avisó a…". Prioridad calculada con los Plazos (W-50) en los bordes (0, 1, 2, 7, 8 días; entrega anterior a la fecha de pedido). | sí |
| 2 | **Avisos y mail** | Cada fila de la tabla §6 de `avisos.md`: quién recibe, texto, que el actor nunca reciba lo suyo, inactivos no reciben, un solo aviso por persona. La campana se actualiza sola en ≤ 15 s sin recargar; tocar un aviso lo marca leído y abre el pedido; la lista W-45 con filtros y "Marcar todo como leído". **Mails** (en `laravel.log`): el de asignación sale una vez, por la cola `mail`, con el texto, el asunto y "Ver el pedido" apuntando al front; Mis avisos (W-19): activar "completado" y que llegue; el seguidor nunca recibe mail; "vence mañana" y el aviso al superior nunca por mail. **Recordatorio:** un pedido con entrega mañana → `php artisan avisos:recordatorios` (después de las 8:00 de Argentina; antes, a propósito, no sale) → aparece en la campana, una sola vez aunque se corra de nuevo; un lunes → sale el domingo. Pará el worker de `mail`: qué ve el usuario y que el mail salga al relevantarlo. | sí |
| 3 | **Jefaturas, listas y calendario** | Hernán (Jefatura de Proyectos) y Dirección: Tareas de mi gente (W-26, con "(yo)", agrupado, inactivos con pendientes), Carga por persona (W-04, semana lunes a domingo), Todos los pedidos (W-24: todos los filtros, orden, "N de M", **Exportar a Excel**: abrir el `.xlsx` y revisar columnas y datos), Servicios generales (W-25), Calendario (W-03: mes y semana, Mis tareas / Mi gente, períodos como barras, completados tachados). Fechas, "vencido", "hoy", "mañana" y "vence esta semana" en hora de Argentina, también con el navegador en UTC. | sí |
| 4 | **Obras, equipos, personas y buscador** | Pestaña Pedidos de la obra (entrada por defecto, "Abiertos"/"Todos", filtro por sector, "+ Nuevo pedido en esta obra") con los **presupuestos a la vista arriba de las pestañas**; columna "pedidos abiertos" y fila Servicios generales del listado; pasar la obra a **Terminada con pedidos abiertos** (W-22: completar / reasignar / abrir el pedido según quién mira, no frena). Equipos en reparación (W-49). Desactivar a alguien con pedidos abiertos y sacar un sector con pedidos en el sector (W-39, W-40). Buscador general: grupo Pedidos, y que quien no tiene Pedidos no lo vea. | sí |
| 5 | **Permisos, entradas y errores** | La matriz real: un sector con Pedidos = Ver (se ve, se comenta y se sigue, no se crea) y uno Sin acceso (lo propio se sigue viendo, DU-P01); IDs ajenos e inexistentes por URL (404 entendible); acciones que no le tocan (tomar algo ajeno, completar un bloqueado, reabrir siendo tercero) con el mensaje del backend. Cada formulario de pedido atacado: vacíos, largos, emojis, ñ, fechas al revés y futuras, links `javascript:` o sin `https`, archivos de 0 B, 20 MB y 21 MB, doble clic en cada botón, dos pestañas editando el mismo pedido (409 y "Actualizar"), F5 a mitad, sesión vencida con borrador. Loaders solo donde recarga. Textos con el UX writer en la cabeza. | sí |
| 6 | **Celular y accesibilidad** | Pixel 7 e iPhone SE 375×667 (forzá el viewport): barra de abajo **Tareas · Obras · Avisos · Más**, Mis tareas en tarjetas, **crear un pedido corto (W-43) y "Se le avisó a…"**, comentar y **completar con la foto del remito** desde el celular (subida de imagen real), avisos en el celular (W-41), tarjetas tocables enteras (≥ 44 px), sin scroll horizontal. Teclado y foco visible en PC en los diálogos de pedido (un grupo de opciones = una parada de Tab). | sí |
| 7 | **Backend, cola, scheduler y regresiones de la Semana 1** | Health, `failed_jobs` 0, canal `errors` y `/api/errors` sin 4xx inesperados, salida de los dos workers y del `schedule:work` (que corra `avisos:recordatorios` cada hora sin error). Una pasada corta por lo aprobado de la Semana 1 que la Semana 2 tocó: ingreso, barra, ficha de la obra, Usuarios/Sectores, buscador, Mi perfil. | no (Lead) |
| 8 | **QC final** | Cada hallazgo reproducible y con evidencia, sin duplicados, lo "esperado" del §1 no reportado como hallazgo, severidades coherentes con las rondas de la Semana 1, **veredicto (§7)** y firma. | no |

**Tandas:** 1 · roles 1, 2 y 7 · 2 · roles 3, 4 y 5 · 3 · rol 6 · al final QC y consolidación.
**Ingresos (20 códigos por IP por hora):** el Lead entra una vez con cada persona y reparte las sesiones; reservá ≥ 4
para sesión vencida con ingreso real (rol 5) y planificá el resto.

## 3. Entorno

**Carpetas fijas en `staging`, nunca las principales.** Mientras probás, la sesión de desarrollo trabaja en
`tecnoservicios_backend` y `tecnoservicios_frontend` y los cambia de rama (en la ronda 5 de la Semana 1 eso movió la
API a mitad de la prueba). Creá worktrees propios desde `staging`:
```
git -C tecnoservicios_backend fetch && git -C tecnoservicios_backend worktree add ..\tecnoservicios_backend_qa_s2 -b qa/prueba-usuario-semana-2 origin/staging
git -C tecnoservicios_frontend fetch && git -C tecnoservicios_frontend worktree add ..\tecnoservicios_frontend_qa_s2 origin/staging
```
En el backend: copiá `.env` y `vendor\` del principal (o `composer install`) y `composer dump-autoload`; en el front,
`npm ci`. Anotá en el reporte los commits (`git rev-parse --short HEAD`) de los dos.

1. **MySQL y Redis arriba** (`select 1` y `PING`). Con Laragon: si no responden, pedile al usuario "Start All"; si
   Redis no arranca, `redis-server.exe redis.windows.conf` desde `C:\laragon\bin\redis\redis-x64-5.0.14.1`. Memoria
   libre > 6 GB antes de cada tanda.
2. Base propia **`tecnoservicios_prueba_usuario`** (`CREATE DATABASE IF NOT EXISTS`). Nunca `tecnoservicios`,
   `tecnoservicios_testing*` ni `tecnoservicios_integracion`. **Nunca dropees bases.**
3. Variables del backend de la prueba (pasalas a **cada** proceso de artisan):
   ```
   APP_ENV=local
   APP_DEBUG=false
   APP_URL=http://localhost:8340
   FRONTEND_URL=http://localhost:5316
   DB_DATABASE=tecnoservicios_prueba_usuario
   DB_CONNECT_TIMEOUT=1
   SANCTUM_STATEFUL_DOMAINS=localhost:5316
   CORS_ALLOWED_ORIGINS=http://localhost:5316
   SESSION_DOMAIN=localhost
   MAIL_MAILER=log
   QUEUE_CONNECTION=redis
   REDIS_PREFIX=tecnoservicios_prueba_
   CACHE_PREFIX=tecnoservicios_prueba_cache_
   IDENTIDAD_CODIGO_EN_PANTALLA=false
   ERRORS_TOKEN=<uno nuevo>
   ```
   - El `ERRORS_TOKEN` generalo sin `\r` final: `openssl` en Windows lo deja y `/api/errors` da 401.
   - Va fuera del repo.
4. `php artisan config:clear`. Verificá con tinker que la base es `tecnoservicios_prueba_usuario` **antes** de
   `php artisan migrate:fresh --seed`.
5. Al resetear, **borrá las claves de sesión del prefijo de la prueba en Redis**. Si no, las sesiones de una ronda
   vieja entran como los usuarios nuevos que tomen esos ids.
6. Procesos (en background, con las variables):
   - API: `php artisan serve --host=127.0.0.1 --port=8340 --no-reload`.
   - Workers iguales a `config/supervisor.php`:
     - `queue:work redis --queue=default --sleep=3 --tries=3 --backoff=10`
     - `queue:work redis --queue=mail --sleep=1 --rest=1 --tries=5 --backoff=30`
   - Scheduler: `schedule:work`.
   - Front: `VITE_API_URL=http://localhost:8340 npm run dev -- --port 5316 --strictPort`. Los módulos de la Semana 2
     vienen encendidos por defecto; no pongas `VITE_MODULOS_ENTREGADOS`.
7. Smoke: `GET /api/health` → 200; `http://localhost:5316` → ingreso.
8. **Ingreso:**
   - Se entra con el mail y el código de 6 dígitos del mail logueado en `storage\logs\laravel.log`.
   - Personas del seeder (ficticias): `diego@empresa-ejemplo.com` (Gerencia de Administración), `hernan@…` (Jefatura
     de Proyectos, con gente a cargo), `laura@…`, `sofia@…` y `pablo@…` (Ingeniería de proyectos), `carla@…` (Compras).
   - Dirección: dala de alta Diego desde Usuarios.
   - Para sesión vencida, bloqueo o pruebas de mail, usá usuarios propios `qa-lead-…@empresa-ejemplo.com`.
9. Para vencer una sesión, **borrá la clave sin pedidos en vuelo**. No reescribas sesiones a mano en Redis.
10. **Horas:**
    - La empresa trabaja en hora de Argentina; los logs del back están en UTC.
    - Para probar bordes de día (22:30, medianoche, un lunes) sin esperar, mové `created_at` o `fecha_entrega` en la
      base de la prueba y **restaurá** al terminar, o usá `page.clock`/`timezoneId` en el navegador.
    - Anotá cómo lo hiciste.

**Cómo probar:**
- Playwright (está en el front) con capturas de cada paso relevante. En cada pantalla mirá consola y red.
- Al cerrar cada bloque, revisá logs, workers y `failed_jobs`.
- **Al terminar,** pará **solo** tus procesos. No toques 8000, 5173, 5310, 5312, 5320–5324, 8320 ni 8350–8351.

## 4. Reportes

Carpeta: `tecnoservicios_backend_qa_s2\docs\qa\prueba-usuario\semana-2\` (ronda siguiente: `semana-2-ronda-2\`…).

1. **`hallazgos.md`**: un bloque por hallazgo, numerados desde **PU-601** (ronda 2: PU-701…). Formato exacto:
   ```markdown
   ### PU-601 · <título corto>
   - **Severidad:** bloqueante | alta | media | baja
   - **Tipo:** funcional | permisos | validación | mensaje | loader | error-servidor | cola-mail | aviso | scheduler | celular | accesibilidad | consistencia-con-documento
   - **F-XX / pantalla:** F-24 · W-08 Nuevo pedido · `/pedidos/nuevo`
   - **Persona:** Laura (Ingeniería de proyectos)
   - **Pasos:** 1. … 2. … 3. …
   - **Esperado:** … (documento / spec / RN / DU)
   - **Obtenido:** … (texto exacto entre comillas)
   - **Evidencia:** `capturas/PU-601-1.png` · `POST /api/pedidos` → 500 · extracto de log
   - **Causa probable (opcional):** archivo:línea si la viste
   - **Regresión de:** no | PU-XXX / HU-XXX
   ```
   - **Bloqueante:** impide usar una función o pierde datos.
   - **Alta:** error técnico visible, permiso roto, dato incorrecto, o **un aviso que no llega o llega a quien no
     corresponde**.
   - **Media:** mensaje confuso, loader indebido, o celular roto en un camino secundario.
   - **Baja:** detalle visual o de texto.
2. **`reporte.md`**:
   - entorno y commits;
   - personas probadas;
   - tabla **F-XX → ✅ / ⚠️ / ❌ + hallazgos**;
   - la §6.1 del backlog punto por punto ("sí o sí");
   - conteo por severidad y tipo;
   - lo que no se pudo probar y por qué;
   - firma de cada rol;
   - **veredicto (§7)**.
3. `capturas\` (`PU-XXX-n.png` y `F-XX-<pantalla>-pc|celular.png`) y `logs\` (solo las líneas de cada hallazgo).

Reglas:
- Un hallazgo = un problema; si el mismo error aparece en varias pantallas, va un solo hallazgo con todas listadas.
- Texto exacto entre comillas.
- Todo dato es ficticio.
- Ningún secreto, token, cookie ni código de ingreso en reportes o commits.

## 5. Git

- Rama **`qa/prueba-usuario-semana-2`** en el worktree del backend.
- Commits `docs(qa): …` en inglés, terminando con `Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>`.
- Commiteá y **pusheá** al cerrar cada tanda y al final. No mergees: lo mergea la sesión de desarrollo.
- No commitees `.env`, `.claude\bug-report.json` ni archivos subidos de `storage\`.

## 6. Al terminar

1. Pará tus procesos y hacé el push final.
2. Respondé con:
   - el veredicto;
   - los hallazgos por severidad;
   - los 5 más graves, en una línea cada uno;
   - qué no se pudo probar;
   - la ruta de `reporte.md`.

## 7. Veredicto (la misma regla con la que se aprobó la Semana 1)

**APROBADA** si:
1. ninguna función queda ❌, ni ⚠️ por un hallazgo bloqueante, alto o medio (⚠️ solo por bajos cuenta como
   aprobada);
2. lo de §6.1 del backlog funciona entero, incluidos el mail de asignación, la campana sola y el "vence mañana";
3. no hay hallazgos **bloqueantes, altos ni medios**.

Los **bajos** no frenan: van como "pendientes menores" y desarrollo los arregla sin otra ronda.

**Si aparece algo bloqueante, alto o medio:** desarrollo lo arregla en `staging` y se corre una **ronda corta** con
este mismo prompt. Esa ronda verifica solo esos hallazgos, con sus pasos exactos, más una pasada de regresión
alrededor de lo que tocó el arreglo, y va en `semana-2-ronda-N\`. Se repite hasta **APROBADA**.
