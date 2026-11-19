# Prompt — Prueba de usuario Semana 1 · Ronda 4 y siguientes (re-verificación hasta aprobar)

> Pegá esto como primer mensaje de una sesión nueva de Claude Code abierta en la carpeta del proyecto,
> o decile: "Leé y ejecutá `tecnoservicios_backend\tasks\prompt-prueba-usuario-semana-1-ronda-4.md`".
>
> **Rutas:** son relativas a la carpeta del proyecto (`…\NexoSmart\Projects\tecnoservicios_project\`). En una
> máquina es `C:\NexoSmart\Projects\…` y en la otra `B:\ProgramFiles\NexoSmart\Projects\…`. Usá la de la
> máquina donde corrés.
>
> **Este prompt se reusa en cada ronda hasta que la Semana 1 quede aprobada** (ver §7). Para la ronda
> siguiente, el QA Lead actualiza §0 y §2 con lo que quedó abierto y cambia el número de ronda en las rutas.

---

## 0. Misión

Sos el **equipo de QA profesional de NexoSmart** del proyecto NS-2026-025 (Tecnoservicios Bahía SA).

**Ronda actual: 5.** La ronda 4 (`docs\qa\prueba-usuario\semana-1-ronda-4\`) quedó **NO APROBADA**: de los 20 PU,
✅ 19 · ⚠️ 1 (PU-041) · ❌ 0; O-1.1 ⚠️; 10 hallazgos nuevos PU-301…310 (PU-301, 303 y 304 ya arreglados en la
ronda). Desarrollo arregló los 7 abiertos (PU-302, 305…310) y la nota del §8 ("Destrabamos el ingreso de …").
Qué cambió y cómo verificar cada arreglo: `tasks\prueba-usuario-semana-1-ronda-5-novedades.md`. Todo está en
**`staging`**: backend `6516ba7` (arreglo de PU-302: `a8deacf`), front `d2a4806`. Sin migraciones nuevas;
después del pull, `php artisan config:clear` (clave nueva `app.zona_empresa`).

**Esta ronda decide si la Semana 1 queda aprobada.** Es corta y enfocada. Tiene cuatro objetivos, en este orden:

1. **Verificar los 7 hallazgos abiertos de la ronda 4** con los pasos de la tabla de las novedades:
   - **PU-302** (alta): "Cargado" con el día de Argentina;
   - **PU-305** (media): sesión vencida mientras otro cambia lo mismo → "Mientras no estabas, alguien cambió esto…", **con ingreso real**, en Editar obra y en Editar proveedor;
   - **PU-306** (media): Tabla de permisos, error a la vista y con foco si el guardado falla;
   - **PU-307**, **PU-308**, **PU-309** y **PU-310** (bajas), y la nota del §8 ("Destrabamos el ingreso de …").
2. **Verificar lo que quedó ⚠️ en la ronda 4:** **PU-041** (controles ≥ 44 px, ahora con PU-307) y la decisión
   **O-1.1** (con PU-306). Los otros 19 PU de la ronda 4 están ✅: se miran solo si quedan en el camino.
3. **Verificar las decisiones de UX aprobadas** (§1) en lo que tocaron los arreglos.
4. **Cazar regresiones** de los arreglos de la ronda 4:
   - **Caché al ingresar** (PU-305): ingreso normal, sesión vencida con vuelta a la pantalla de origen, borrador
     recuperado **y** descartado (Nueva obra, Editar obra, alta y edición de proveedor), "Salir" y entrar con otra persona.
   - **Fechas del back** (PU-302): las fechas sin hora (fechas previstas, fecha del documento) no deben cambiar; el
     año que se completa en el N.º usa la zona de la empresa.
   - **Tarjeta de Usuarios** (PU-307): en el celular toda la tarjeta abre "Editar usuario"; en PC sigue la tabla y
     el nombre es lo único que abre el diálogo.
   - **Tabla de permisos, Plazos, buscador e ingreso trabado** (PU-306, 308, 309, 310).

Numeración de hallazgos de esta ronda: desde **PU-501**. Carpeta `semana-1-ronda-5`, rama `qa/prueba-usuario-semana-1-ronda-5`.

No repitas la pasada completa de la Semana 1. Lo que ya estaba ✅ se mira solo si queda en el camino de una prueba.

**No arreglás código.** Levantás el entorno, probás, capturás evidencia y escribís reportes, que va a leer la
sesión de desarrollo.

El criterio no cambia: **una persona de la empresa, sin saber nada técnico, entiende siempre qué pasó y qué
hacer.** Nunca puede aparecer nada de esto:
- SQL, "Server Error", 500 ni textos en inglés;
- claves crudas, `undefined`, `null`, `[object Object]` ni NaN;
- códigos internos (W-XX, PU-XX, F-XX);
- pantalla en blanco.

Los loaders van **solo en el componente que recarga**.

## 1. Fuentes y lo "esperado"

- **Repos:** `tecnoservicios_backend` y `tecnoservicios_frontend`, **en la rama `staging`** de los dos
  (`git checkout staging && git pull`). **Ramas:** `staging` = Semana 1 completa (lo que se prueba y lo que
  usa el cliente); `main` = Semana 2 en desarrollo. **Esta prueba es sobre `staging`, nunca sobre `main`.**
  En el reporte anotá la rama y el commit de cada repo.
- **Ronda 3, lo más importante:** `tecnoservicios_backend\docs\qa\prueba-usuario\semana-1-ronda-3\`:
  - `hallazgos.md`: cada PU-201…212 con sus pasos, lo esperado y lo obtenido;
  - `verificacion.md`: los 9 ⚠️, con el texto exacto;
  - `observaciones.md`, `reporte.md`, `capturas\` y `logs\`.
- **Prompts anteriores:** `tasks\prompt-prueba-usuario-semana-1.md` (ronda 1: §3 entorno y checklist) y
  `tasks\prompt-prueba-usuario-semana-1-ronda-3.md`. **Sigue valiendo todo lo que dicen, salvo lo que cambia acá.**
- **Qué cambió:**
  - `git log --oneline --since=2026-11-18` en los dos repos. Los commits mencionan su PU entre paréntesis
    (`fix(...): … (PU-2XX)`, `(O-1)`, `(O-3)`, `(O-4)`).
  - `tecnoservicios_backend\tasks\todo.md`, sección "Ronda 3 de la prueba de usuario".
- **Especificaciones:**
  - `docs\especificacion\obras.md`: §10.3 tiene el contrato nuevo de montos con `updated_at`; también RN-OBR-14 y RN-OBR-27.
  - `identidad.md`, `proveedores.md`, `docs\dudas.md` y `docs\adr\`.
- **Lecciones:** `tasks\lessons.md` de los dos repos.

### Lo "esperado" (no lo reportes como hallazgo)

Siguen valiendo las decisiones de las rondas anteriores:
- **PU-043:** ñ ≠ n.
- **PU-044:** se busca también por CUIT.
- **PU-045:** el ingreso trabado tiene pista y se puede destrabar.
- **PU-002:** dos guardados en el mismo segundo no se detectan. Es un límite aceptado.

Además, aprobadas por Juani el 2026-11-18:
- **Fechas:** en toda la app, `dd/mm/aaaa` (por ejemplo "31/12/2099") y, con hora, `dd/mm/aaaa HH:MM`. Un formato distinto en cualquier pantalla **es un hallazgo**.
- **O-1.1:** si la persona intenta irse mientras se guarda, ve el aviso de que se está guardando.
  - Si el guardado **termina bien**, la app la lleva sola a donde había elegido ir: el link, la barra o atrás.
  - Si **falla** (422, 409 o error), se queda en la pantalla con el error a la vista.
- **O-1.2:** mientras un diálogo guarda, Escape, "Cancelar" y el clic afuera muestran dentro del diálogo
  "Estamos guardando. Esperá unos segundos.", y el diálogo no se cierra.
- **O-3:** el aviso de fecha lejana ("Revisá la fecha", con fecha anterior a 2000 o posterior al año actual
  + 10) se ve al cargar y **también en la ficha**, en las fechas previstas, los certificados y los documentos ya guardados.
- **O-4:** "REV100" (o más) → "La revisión va de REV01 a REV99.", igual en el front y en el backend. REV00 → "Las revisiones empiezan en REV01.".
- **Sesión vencida:** todo 401 dice "Tu sesión venció. Volvé a ingresar.".
- **Nombre de los links:** se nombran por la última parte del link (decodificada) o por el "Nombre para mostrar".
  - Los links ya guardados se renombraron con una migración de datos.
  - **Límite aceptado:** la línea del historial de un link **que ya se quitó** puede seguir mostrando el dominio, porque no queda de dónde sacar el nombre.
- **Si los textos de la app y este prompt no coinciden:** antes de reportarlo, comparalo con el commit y
  la especificación. Si el texto es claro y coherente, no es hallazgo.

## 2. El equipo y la asignación de roles

La **sesión principal es el QA Lead**. Lanza a los demás roles como subagentes, uno por rol, cada uno con
este prompt, su lista y las sesiones guardadas (storageState) que le reparte el Lead.

**Como máximo 3 subagentes con navegador a la vez.**

| # | Rol | Qué verifica | Navegador |
|---|---|---|---|
| 0 | **QA Lead** (sesión principal) | Entorno, ingreso de cada persona y reparto de sesiones, **único que resetea la base**, orden de las tandas, consolidación, matriz PU → estado, veredicto (§7). | sí (solo para ingresar) |
| 1 | **Verificador A · guardado, salida y conflicto de sesión** | **PU-305** (pasos de REG-03 de la ronda 4 con **ingreso real**: sesión vencida mientras otra persona cambia la misma obra o proveedor → al volver, "Mientras no estabas, alguien cambió esto…" con lo que guardó el otro; en **Editar obra** y en **Editar proveedor**; ≥ 4 códigos reservados), **PU-306** (Tabla de permisos: forzar 500 y 422 sin irse, con Atrás, con la barra y con "Sectores": la pantalla sube al error y le pone el foco), **O-1.1** (completa, con la Tabla de permisos) y **O-1.2** si queda en el camino. | sí |
| 2 | **Verificador B · fechas, textos y buscador** | **PU-302** ("Cargado" con el día de Argentina: `created_at` = `2026-11-20 01:30:00` UTC → "19/11/2026" y `2026-11-19 02:59:00` → "18/11/2026", restaurando al final; fechas sin hora y año del N.º sin cambios), **PU-308** (Plazos: "No había cambios…" / "Guardamos…" se van al editar), **PU-309** (pista del buscador según lo que la persona puede encontrar: Laura o Sofía vs. Diego), **PU-310** ("Ingreso trabado hasta las HH:MM" en hora de Argentina con el navegador en UTC y en Madrid), nota del §8 ("Destrabamos el ingreso de …" aunque la ficha se recargue), y el formato de fecha. | sí |
| 3 | **QA celular y accesibilidad** | **PU-307** (Usuarios en Pixel 7 e iPhone SE 375×667: tocar mail, sector y "Activo" de las tarjetas de "Dora" y "Carla" abre "Editar usuario"; un solo foco por tarjeta con teclado; en PC sigue la tabla) y **PU-041** (controles ≥ 44 px en todas las pantallas, con las mismas reglas de la ronda 4). | sí |
| 4 | **QA backend, cola y health** | Sin cortes en esta ronda (PU-020, 202, 203 y 138 quedaron ✅ en la ronda 4 y el backend solo cambió `PresupuestoResource` y la clave `app.zona_empresa`). Health, `failed_jobs`, canal `errors` sin 4xx, workers y scheduler; `config:clear` aplicado. | no |
| 5 | **Cazador de regresiones** | Caché al ingresar (ingreso normal; sesión vencida con vuelta a la pantalla de origen; borrador recuperado **y** descartado en Nueva obra, Editar obra, alta y edición de proveedor; "Salir" y entrar con otra persona), fechas del back (previstas, documento, N.º), tarjeta y tabla de Usuarios, Tabla de permisos, Plazos, buscador e ingreso trabado/destrabar. Con ingreso real solo con su usuario `qa-lead-…` y el tope que le da el Lead. | sí |
| 6 | **QC final** | Que cada estado tenga evidencia (captura o log), deduplicar, que lo "esperado" de §1 no aparezca como hallazgo, severidades coherentes con las rondas anteriores, **veredicto de aprobación (§7)** y firma. | no |

**Orden de las tandas:**
- **Tanda 1:** roles 1 y 2 en paralelo, más el rol 4 (lo hace el Lead, sin navegador).
- **Tanda 2:** roles 3 y 5.
- **Al final:** QC (rol 6) y la consolidación del Lead.
- **Ingresos (20 por IP por hora):** 7 del Lead para las sesiones; ≥ 4 para PU-305 (rol 1); el resto para el rol 5.

## 3. Entorno

Igual que en el §3 del prompt de la ronda 1:
- base propia `tecnoservicios_prueba_usuario`;
- API en :8340 y front en :5316;
- `APP_DEBUG=false`, `MAIL_MAILER=log` y `QUEUE_CONNECTION=redis`;
- prefijos de Redis propios.

A eso se suma lo siguiente:

- **MySQL y Redis arriba antes de empezar.** Verificá `select 1` y `PING`.
  - **Máquina con Laragon:** si no responden, pedile al usuario que los levante desde Laragon.
  - **Máquina sin Laragon (`B:\…`):** MySQL es el MariaDB 10.4 de XAMPP (`C:\xampp\mysql\bin\mysqld
    --defaults-file=mysql\bin\my.ini --standalone` desde `C:\xampp`) y Redis es un ejecutable portable. Si no
    están arriba, pedile al usuario que los levante. Después de un corte, verificá que vuelvan **los dos**
    antes de seguir.
  - **Memoria:** Claude Code puede cortar procesos en segundo plano si falta memoria. Antes de arrancar,
    revisá la memoria libre (más de 6 GB) y, si se corta algo, avisá y retomá.
- **`git pull` en los dos repos y `php artisan migrate` sobre la base de la prueba** antes de resetear. Hay dos
  migraciones de datos nuevas (`2026_11_18_*`).
  - Después, **`migrate:fresh --seed` SOLO sobre `tecnoservicios_prueba_usuario`**, verificando antes con tinker que la config apunta ahí.
  - Nunca dropees bases.
  - Nunca toques `tecnoservicios`, `tecnoservicios_testing*` ni `tecnoservicios_integracion`.
  - Para probar que la migración de nombres de links corrige datos viejos (PU-207): cargá links **antes** de
    migrar, desde un checkout anterior o en la base de una ronda previa si todavía la tenés. Si no se puede,
    dejalo en "no probado" con el motivo.
- **`DB_CONNECT_TIMEOUT=1`** (el valor nuevo de `.env.example`). Revisá que el backend de la prueba lo tenga.
- **Workers iguales a `config/supervisor.php`:**
  - `queue:work redis --queue=default --sleep=3 --tries=3 --backoff=10`
  - `queue:work redis --queue=mail --sleep=1 --rest=1 --tries=5 --backoff=30`
  - `schedule:work`
- **Límite de 20 pedidos de código por IP por hora, y bloqueo progresivo por mail:**
  - El Lead ingresa una vez con cada persona y reparte las sesiones.
  - La sesión vencida y el bloqueo se prueban con usuarios propios (`qa-lead-…@empresa-ejemplo.com`).
  - Planificá el presupuesto de ingresos. En la ronda 3 se usaron 18 de 20 y faltó para la vuelta con ingreso real.
- **`IDENTIDAD_CODIGO_EN_PANTALLA=false`:** el código se lee del mail logueado.
- **`ERRORS_TOKEN`:** configurá uno para la prueba, por ejemplo `openssl rand -hex 32` en el env del backend de
  la prueba. Así podés consultar `/api/errors` (en la ronda 3 no se pudo).
- **No reescribas a mano sesiones en Redis.** En la ronda 3 eso causó 500 falsos (BK-01). Para vencer una
  sesión, borrá la clave **sin pedidos en vuelo**, o esperá a que venza.
- **Al terminar,** pará **solo** los procesos que levantaste. No toques los puertos 8000, 5173, 5310, 8320 ni 5312.

## 4. Reportes

Carpeta nueva: `tecnoservicios_backend\docs\qa\prueba-usuario\semana-1-ronda-5\`

1. **`verificacion.md`:** tabla con **los PU de esta ronda** (ronda 5: los 7 hallazgos abiertos de la ronda 4 — PU-302, 305…310 — y PU-041, que quedó ⚠️), más una sección
   **"Decisiones aprobadas"** con O-1.1, O-1.2, O-3, O-4 y el formato de fecha.
   - Columnas: `PU-XXX | título corto | estado (✅ / ⚠️ / ❌) | pantallas revisadas | rol | evidencia | nota`.
   - Un ⚠️ o ❌ lleva el texto exacto de lo que se vio y el PU nuevo que lo detalla.
2. **`hallazgos.md`:** **solo lo nuevo**, con el mismo formato exacto de las rondas anteriores.
   - Numeración desde **PU-501** (en la ronda N, desde PU-N01).
   - Agregá `- **Regresión de:** PU-2XX / commit` o `- **Regresión de:** no; arreglo incompleto de …`.
3. **`reporte.md`:**
   - resumen ejecutivo;
   - entorno (máquina y commits `git rev-parse --short HEAD` de los dos repos);
   - conteo por estado y hallazgos nuevos por severidad y tipo;
   - tabla F-XX → ✅ / ⚠️ / ❌ de las funciones tocadas;
   - lo que no se pudo probar y por qué;
   - **veredicto (§7)**;
   - firma de cada rol.
4. **`capturas\`** (`PU-XXX-r4-n.png`, `PU-3XX-n.png`) y **`logs\`** (solo las líneas de cada hallazgo).

Severidades y reglas, iguales a las rondas anteriores:
- texto exacto entre comillas;
- un problema por hallazgo;
- datos ficticios;
- nada de secretos, tokens, cookies ni códigos de ingreso en los reportes.

## 5. Git

- Rama `qa/prueba-usuario-semana-1-ronda-5` en el **backend**, creada desde `staging` actualizado (los
  reportes son de la Semana 1).
- Commits convencionales en inglés (`docs(qa): …`), terminados en `Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>`.
- **No toques git config**: la identidad y la autenticación ya están configuradas.
- Pusheá al cerrar cada tanda y al final.
- No mergees a `staging` ni a `main`: lo hace desarrollo.
- No commitees `.env`, `.claude\bug-report.json` ni archivos subidos de prueba.

## 6. Al terminar

Pará tus procesos, hacé el push final y respondé con:
- **el veredicto (§7)**;
- PU ✅ / ⚠️ / ❌ (cuántos y cuáles no quedaron);
- hallazgos nuevos por severidad;
- regresiones, una línea cada una;
- qué no se pudo probar;
- la ruta de `reporte.md`.

## 7. Veredicto: cuándo la Semana 1 queda aprobada

> **Desde la ronda 5 (decisión de Juani, 2026-11-19): la ronda 5 es la última ronda completa.** La Semana 2 se
> desarrolla en paralelo en `main`; QA prueba `staging`.

**APROBADA** si se cumplen las tres condiciones:
1. Ningún PU verificado queda ❌, ni ⚠️ por un hallazgo bloqueante, alto o medio. Un PU ⚠️ **solo por hallazgos
   bajos** cuenta como aprobado: el reporte lo anota con su hallazgo.
2. Las decisiones aprobadas del §1 funcionan como están descritas (salvo detalles bajos, que se anotan igual).
3. No hay hallazgos nuevos de severidad **bloqueante, alta ni media**.

Los hallazgos nuevos **bajos** no impiden la aprobación. Se listan en el reporte como "pendientes menores" y
pasan al backlog de la Semana 2: desarrollo los arregla después, sin otra ronda.

**Si en la ronda 5 aparece algo bloqueante, alto o medio:** no se hace otra ronda completa. Desarrollo lo
arregla en `staging` y se corre una **re-verificación acotada**: solo esos hallazgos, con sus pasos exactos, más
una pasada corta de regresión alrededor de lo que tocó el arreglo. Va en la misma carpeta de la ronda 5, en
`re-verificacion.md`. Si sale bien, la Semana 1 queda **APROBADA**.

**Hasta la ronda 4 regía esto** (se conserva por la trazabilidad). **NO APROBADA** en cualquier otro caso. Entonces:
1. El reporte dice qué falta exactamente para aprobar.
2. Desarrollo arregla.
   Los arreglos de la Semana 1 se hacen sobre `staging` y después se pasan a `main`.
3. Se corre **otra ronda con este mismo prompt**, otra vez sobre `staging`. El QA Lead actualiza §0 y §2 con:
   - los PU que quedaron ⚠️/❌;
   - los hallazgos nuevos que bloquean;
   - el número de ronda en las rutas (carpeta `semana-1-ronda-N`, rama `qa/prueba-usuario-semana-1-ronda-N` y PU desde N01).

Se repite hasta **APROBADA**.
