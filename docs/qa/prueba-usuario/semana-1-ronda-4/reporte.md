# Reporte · Prueba de usuario Semana 1 · Ronda 4 (re-verificación para aprobar) · Tecnoservicios (NS-2026-025)

**Fecha:** 2026-11-18 (tanda 1, interrumpida) y 2026-11-19 (retomada) · **Equipo:** QA NexoSmart (QA Lead + roles A, B, BK, CEL, REG, VER + QC final) · **Detalle:** [`verificacion.md`](verificacion.md) (los 20 PU y las decisiones aprobadas) · [`hallazgos.md`](hallazgos.md) (lo nuevo, PU-301…PU-310) · borradores de cada rol en [`borradores/`](borradores/).

## 1. Resumen ejecutivo

**Veredicto: NO APROBADA** (detalle en el §7).

Los arreglos de la ronda 3 funcionan. De los 20 PU verificados, **19 quedaron resueltos, 1 a medias y ninguno sigue igual**:

**Funcionan:**
- Los conflictos en el presupuesto por rubro (PU-201) y en el resto de las pantallas.
- Preguntar antes de irse con cambios sin guardar (PU-204, PU-006).
- Con MySQL parado de verdad: 503 en 1–3 s, en lugar del 500 de 38 s (PU-202).
- La pantalla abierta se recupera sola después de un corte (PU-203).
- El texto del 401, en todas las pantallas.
- Los nombres de los links, incluidos los cargados antes de la migración.
- El historial sin comillas y el formato `dd/mm/aaaa` en todas las pantallas.
- Las tarjetas de Obras se tocan enteras.
- El foco con teclado en los adjuntos.
- O-1.2, O-3 y O-4 funcionan como se aprobaron.

**Durante la ronda, desarrollo arregló tres hallazgos del 18/11** (front `380ddaf`). Se re-verificaron el 19/11:
- PU-303: Tabla de permisos al irse mientras guarda.
- PU-304: editar proveedor con Atrás.
- PU-301: la hora del historial, que ahora sale en hora de Argentina en cualquier huso.

**Siguen abiertos 7 hallazgos nuevos: alta 1 · media 2 · baja 4.** Ninguno es regresión: son casos que los arreglos no cubrieron.

**Lo que frena la aprobación**
1. **PU-302 · alta.** "Cargado" del presupuesto muestra **el día siguiente** si se cargó entre las 21:00 y las 24:00 de Argentina. El backend manda la fecha ya cortada en UTC. Es el resto de B-01 que el arreglo del front no podía cubrir.
2. **PU-305 · media.** Si la sesión vence y otra persona cambia la obra, al volver se ve "Recuperamos lo que estabas escribiendo." sobre los datos viejos, y al guardar da conflicto. Tendría que avisar "Mientras no estabas, alguien cambió esto…" (PU-109).
3. **PU-306 · media.** En la Tabla de permisos, si el guardado falla, el error queda arriba, fuera de la vista: parece que no pasó nada. Por eso **O-1.1** queda ⚠️.
4. **PU-041 ⚠️.** En Usuarios, en el celular, el nombre que abre "Editar usuario" mide 33–40 px de ancho (**PU-307**, baja). El hallazgo es bajo, pero deja el PU a medias, y el §7 pide los 20 PU en ✅.

## 2. Entorno

| Qué | 18/11 (tanda 1) | 19/11 (retomada) |
|---|---|---|
| Máquina | sin Laragon (`B:\ProgramFiles\NexoSmart\projects\…`) | con Laragon (`C:\NexoSmart\Projects\…`) |
| Backend | `staging` @ `74beea5` (reportes en `qa/prueba-usuario-semana-1-ronda-4`) | `staging` @ **`56e5d8f`**. La rama de QA `qa/prueba-usuario-semana-1-ronda-4` tiene `origin/staging` mergeado: mismo código, más docs. **Sin cambios de código respecto del 18/11.** |
| Frontend | `staging` @ `15b544f` | `staging` @ **`380ddaf`**: merge de `efaf4b1` (B-01), `428eb27` (A-01) y `9d0f84e` (A-02) |
| `main` | — | `main` = `staging` en los dos repos |
| Base | MariaDB 10.4 de XAMPP | MySQL 8.4.3 de Laragon. `migrate` (las dos `2026_11_18_*`: 127 ms y 1,3 ms) y `migrate:fresh --seed` sobre `tecnoservicios_prueba_usuario`, con la base verificada antes con tinker |
| Redis | 5.0.14.1 portable | 5.0.14.1 de Laragon |
| PHP | el de la máquina | 8.3.30 |
| Variables | §3 del prompt: `DB_CONNECT_TIMEOUT=1`, `IDENTIDAD_CODIGO_EN_PANTALLA=false`, `APP_DEBUG=false`, `MAIL_MAILER=log`, cola Redis y `ERRORS_TOKEN` propio (fuera del repo) | ídem, con un `ERRORS_TOKEN` **nuevo** que vive solo en el entorno de los procesos |
| Procesos | API `artisan serve` :8340 · front Vite :5316 · `queue:work redis --queue=default …` y `--queue=mail --sleep=1 --rest=1 …` · `schedule:work` (iguales a `config/supervisor.php`) | ídem |
| Navegador | Playwright / Chromium headless · PC 1366×768 · Pixel 7 · iPhone SE 375×667 (forzado) | ídem, con `--lang=es-AR`; husos `America/Argentina/Buenos_Aires`, `UTC`, `Europe/Madrid` y `Asia/Tokyo` donde hizo falta |
| Personas | Diego, Dora, Hernán, Laura, Sofía, Pablo y Carla; `qa-lead-r4a` y `qa-lead-r4b` | las mismas 7, más `qa-lead-r4a`, `r4b`, `r4reg`, `r4reg2` y `r4bk` (Diego dio de alta a Dora y a los `qa-lead-…`) |
| Códigos de ingreso | Verificador B 4; los del Lead no constan en los borradores | Lead 7 · REG 5 · PU-021 1 → **13 de 20** |
| Prefijos de datos | `QA4-A`, `QA4-B` | `QA4V`, `QA4C`, `QA4R` |
| Horas | locales (UTC−3); los logs del back están en UTC | ídem |

### Incidentes del entorno

**18/11**
- **La ronda se interrumpió a las ~15:20, a mitad de la tanda 2, a pedido de Juani.**
  - CEL y REG se cortaron sin borrador firmado.
  - Se retomó el 19/11 en la otra máquina, con la base reseteada, y CEL y REG se rehicieron completos.
- **Memoria:** un `clangd` de otro proyecto llegó a 62 GB y dejó la API en 4–15 s por pedido. Juani autorizó cerrarlo. Después, la memoria libre quedó entre 3,1 y 9,5 GB, y ningún rol sacó conclusiones de tiempos en esa franja.

**19/11**
- **MySQL dejó de responder y se cayó solo entre las 12:45 y las 13:06 UTC (09:45–10:06 local).** No fue un corte de la prueba.
  - Hubo 68 entradas `SQLSTATE[HY000] [2002]` en el canal `errors` y, a las 13:00, las dos tareas del scheduler fallaron por la base caída.
  - Memoria libre: ~3,6 GB.
  - Juani lo relanzó desde Laragon (recuperación de InnoDB). Los datos quedaron intactos y la API, los workers y el scheduler siguieron vivos.
  - Mientras tanto, la API respondió 503 en ~1,2 s, nunca 500.
  - **Ningún rol usó lo visto en esa franja:** VER repitió A-02, CEL descartó 09:49–10:06 y REG repitió los permisos de Hernán.
- **Los cortes del Lead fueron de 13:37 a 14:05 UTC, sin nadie más probando.**
  - Los `queue:work` murieron en cada caída de Redis (`Error while writing bytes to the server`), como en las rondas anteriores; en producción los reinicia Supervisord. Se relanzaron con la línea de `config/supervisor.php`.
  - `schedule:work` sobrevivió a todo.
- **Laragon bajó Redis al levantar MySQL** (MySQL arriba a las 13:43:27; Redis caído hasta las 14:00:08 UTC, cuando el Lead lo relanzó con el `dump.rdb`, con las 99 claves y las sesiones intactas). Ya había pasado en la ronda 2.
  - Por eso la recuperación de PU-203 "con MySQL" fue en rigor tras MySQL **y** Redis.
  - **Nota QC:** el canal `errors` ya registra errores de Redis desde las 13:42:17 UTC, un minuto antes de lo que dice el borrador del Lead.
  - Las mediciones de PU-202 (13:40:49–13:41:07 UTC) quedan antes de esa hora, así que fueron con MySQL solo.
- **Canal `errors` del 19/11** (`logs/errores-canal-2026-11-19.log`): 503 entradas en total, todas explicadas por el incidente o por los cortes, y **ningún 4xx**. `failed_jobs` quedó en 0.

  | Origen | Entradas |
  |---|---|
  | Incidente de MySQL | 66 + 2 |
  | Scheduler (13:00) | 2 |
  | Corte de Redis | 28 |
  | Corte de MySQL | 37 `SQLSTATE` |
  | Redis caído por Laragon (13:42–14:00) | ~368 |

  El extracto está recortado a ~150 caracteres por línea, así que el `user_id` que cita el Lead no se ve en él.
- **`POST /api/auth/pedir-codigo` por curl, sin la cookie CSRF, da 419** "Tu sesión venció. Volvé a ingresar.". Solo pasa fuera de la app, porque la pantalla pide la cookie antes. No es hallazgo.

## 3. Verificación de los 20 PU

**✅ 19 · ⚠️ 1 · ❌ 0**

| Estado | PU |
|---|---|
| ❌ | — |
| ⚠️ | **PU-041**: en Usuarios, el nombre que abre "Editar usuario" mide 33–40 px de ancho en el celular (→ PU-307) |
| ✅ | PU-201, 202, 203, 204, 205, 206, 207, 208, 209, 210, 212 · PU-002, 006, 020, 112, 129, 133, 136, 138 |

- Los 20 PU aparecen en algún borrador. Ninguno quedó sin verificar.
- **Cambio de estado respecto del borrador:** PU-209 pasa de ⚠️ (B, 18/11) a ✅.
  - El problema del PU era el **formato**, que hoy está bien en todas las pantallas.
  - La **hora y el día** son otro problema, con sus propios hallazgos (PU-301 arreglado, PU-302 y PU-310 abiertos). Ver `verificacion.md`.
- **Relación con la ronda 3.** Los 9 ⚠️ de la ronda 3 dependían de PU-201, 202, 203, 204, 205, 206, 207, 210 y 212. Todos esos quedaron ✅, y con ellos 8 de los 9. PU-041 sigue ⚠️ por lo que quedaba de la nota de la ronda 3 ("Dora" 33×44).
- **Controles fuera de los 20:**
  - PU-021 ✅: con el worker de mail parado, `"cola_mail": false` a los 121 s; al relanzarlo, sale un solo mail.
  - Ingreso trabado y "Destrabar ingreso" ✅, con un bloqueo real el 18/11.
  - Migraciones `2026_11_18_*` ✅, sin error y dos veces seguidas. Sin log en la carpeta.

## 4. Hallazgos nuevos

**Por severidad:** alta 2 · media 4 · baja 4 · **total 10**.
- **Arreglados durante la ronda (3):** PU-301 en el historial, PU-303 y PU-304.
- **Abiertos (7):** alta 1 · media 2 · baja 4.
- **0 regresiones:** los 10 son arreglos incompletos.

| Tipo | Alta | Media | Baja | Total | De ellos abiertos |
|---|---|---|---|---|---|
| funcional | 2 (301 ✅, 302) | 3 (303 ✅, 304 ✅, 305) | — | 5 | 2 (302 alta, 305 media) |
| mensaje | — | 1 (306) | 2 (308, 309) | 3 | 3 |
| celular | — | — | 1 (307) | 1 | 1 |
| consistencia-con-documento | — | — | 1 (310) | 1 | 1 |

| PU | Borrador | Severidad | Estado | Título |
|---|---|---|---|---|
| PU-301 | B-01 | alta | ✅ arreglado en el historial (`380ddaf`) | El historial mostraba la hora en UTC: un cambio de las 14:08 decía "18/11/2026 17:08" |
| PU-302 | LEAD-01 | alta | abierto | "Cargado" del presupuesto muestra el día siguiente si se cargó entre las 21:00 y las 24:00 de Argentina |
| PU-303 | A-01 | media | ✅ arreglado (`380ddaf`) | Tabla de permisos: salir mientras se guarda preguntaba "Tenés cambios sin guardar" y la pregunta quedaba abierta |
| PU-304 | A-02 | media | ✅ arreglado (`380ddaf`) | Editar proveedor: con Atrás mientras guarda, iba a la lista con el diálogo abierto |
| PU-305 | REG-03 | media | abierto | Sesión vencida: si otro cambió la obra, al volver dice "Recuperamos…" sobre los datos viejos, y al guardar da conflicto |
| PU-306 | VER-02 | media | abierto | Tabla de permisos: si el guardado falla, el error queda fuera de la vista |
| PU-307 | CEL-01 | baja | abierto | En Usuarios, en el celular, el nombre que abre "Editar usuario" mide 33–40 px de ancho |
| PU-308 | REG-01 | baja | abierto | Plazos: "No había cambios para guardar." sigue a la vista mientras se edita |
| PU-309 | REG-02 | baja | abierto | Buscador: la pista ofrece buscar proveedores a quien no puede verlos |
| PU-310 | VER-01 | baja | abierto | "Ingreso trabado hasta las HH:MM" sigue el huso del navegador |

**Regresiones:** ninguna. En Plazos, Mi perfil y Editar obra, O-1.1 se re-verificó después de `380ddaf` y no cambió.

**Arreglos incompletos (no son regresión):**
- PU-301 ← PU-209: el formato quedó bien, pero la hora ya salía en UTC antes.
- PU-302 y PU-310 ← PU-301 (front `efaf4b1`):
  - "Cargado" llega cortado en UTC desde el back (`PresupuestoResource.php:49`).
  - `horaDelBloqueo.ts` no usa `timeZone`.
- PU-303 y PU-304 ← O-1 (front `63fd81b`): la Tabla de permisos y la edición de proveedor no quedaron cubiertas.
- PU-305 ← PU-109: la vuelta con ingreso real en la misma pestaña usa la ficha que quedó en la caché.
- PU-306 ← PU-111: la Tabla de permisos no lleva la vista al error.
- PU-307 ← PU-041: era la nota de la ronda 3.
- PU-308 ← PU-127: no cubrió Plazos.
- PU-309 ← PU-044: la pista con CUIT de `75bc80e`.

**Observación (no es hallazgo):** **B-02**. El campo de fecha nativo muestra mm/dd/aaaa si el navegador está en inglés; en español se ve dd/mm/aaaa (`B-02-r4b-1.png`). Queda como límite conocido salvo que Juani pida un selector de fecha propio.

## 5. Cobertura F-XX (solo funciones tocadas)

✅ sin hallazgos abiertos · ⚠️ con hallazgos abiertos (alta, media o baja) o un PU ⚠️ · ❌ con bloqueante o un PU que sigue.

| F-XX | Estado | PU abiertos |
|---|---|---|
| F-01 Ingreso con mail + código / sesión vencida | ⚠️ | PU-305 (media) |
| F-02 Menú según el sector | ✅ | — |
| F-03 Buscador general | ⚠️ | PU-309 (baja) |
| F-10 Mi perfil | ✅ | — |
| F-14 Registro de quién hizo qué (historial) | ✅ | — (PU-301 arreglado; PU-207 y 208 ✅) |
| F-15 Listado de obras por estado | ✅ | — (PU-210 ✅) |
| F-16 Alta de obra | ✅ | — (PU-205 y 212 ✅; B-02 es observación) |
| F-17 Ficha de la obra | ✅ | — (O-3 ✅) |
| F-18 Presupuestos (principal y adicionales) | ⚠️ | PU-302 (alta) |
| F-19 Documentos y contactos | ✅ | — (PU-136 y 212 ✅) |
| F-20 Editar la obra | ⚠️ | PU-305 (media) |
| F-21 Cambiar estado | ✅ | — |
| F-23 Fechas previstas | ✅ | — |
| F-48 Presupuesto por rubro | ✅ | — (PU-201 y 204 ✅) |
| F-55 Proveedores con buscador | ✅ | — |
| F-56 Alta y edición de proveedor | ✅ | — (PU-304 arreglado) |
| F-58 Usuarios | ⚠️ | PU-041 ⚠️, PU-307 (baja), PU-310 (baja) |
| F-59 Sectores | ✅ | — |
| F-60 Tabla de permisos | ⚠️ | PU-306 (media); PU-303 arreglado |
| F-68 Plazos de la prioridad | ⚠️ | PU-308 (baja) |
| Adjuntos (ADR-014) y nombre de los links | ✅ | — (PU-112, 129, 205, 207 ✅) |
| Sesión vencida / borradores | ⚠️ | PU-305 (media) |
| Conflictos (dos personas / pestañas) | ✅ | — (PU-002, 201 ✅) |
| Cambios sin guardar | ✅ | — (PU-006, 204 ✅) |
| Guardado y salida (O-1.1 / O-1.2) | ⚠️ | PU-306 (media) |
| Fechas: formato y hora | ⚠️ | PU-302 (alta), PU-310 (baja) |
| 403 / 404 (permisos de los 5 sectores) | ✅ | — |
| Celular | ⚠️ | PU-041 ⚠️, PU-307 |
| Accesibilidad (teclado y foco) | ✅ | — |
| Loaders y recuperación | ✅ | — (PU-203 ✅) |
| Mails y cola | ✅ | — (PU-021 ✅) |
| Scheduler | ✅ | El 18/11 corrió `adjuntos:purgar-huerfanos` e `idempotencia:purgar` sin error. El 19/11, a las 13:00, fallaron solo por el incidente de MySQL. `schedule:work` sobrevivió a todos los cortes. |
| Health y errores de servidor | ✅ | — (PU-020, 138, 202 ✅) |

**Cortes forzados** (los hizo el Lead como parte 2 de BK, de 13:37 a 14:05 UTC del 19/11, sin otros roles probando)

| Corte | `/api/health` | Lo que ve el usuario | Al volver |
|---|---|---|---|
| Redis (`SHUTDOWN SAVE`) | 503 con `"redis": false, "cache": false, "cola_mail": false`, `"database": true` en **1,09–3,54 s** (8 de 9 ≤ 2,4 s) | Con sesión, la API da 503 "El sistema no está disponible…" en 1,14–3,35 s. La pestaña A conserva la barra. La nueva (B) muestra "No pudimos cargar esta información · … Apenas vuelva, esta pantalla se recupera sola." y "Reintentar" | Health 200 en la primera medición. A y B con datos a los **+8 s** sin tocar. Las sesiones sobreviven. Los workers murieron y se relanzaron. |
| MySQL (`mysqladmin shutdown`) | 503 con `"database": false` en **1,08–4,48 s** (24 de 30 ≤ 3,1 s) | Con sesión, **503** con `Retry-After=30` en 1,11–3,37 s, nunca 500. Sin sesión, 401 en 0,8 s. 37 entradas `SQLSTATE` en `errors`. B, con el mismo aviso | A a los +8 s y B a los +15 s, sin tocar (con Redis también caído por Laragon) |
| Worker de mail | 503 con `"cola_mail": false` a los 121 s | "Te mandamos un código a … · Si el mail está registrado, te llegó un código." | Un solo mail, `failed_jobs` 0 y health 200 a los 5 s de relanzar |

## 6. Lo que no se pudo probar y por qué

**Conflictos y guardado (A, 18/11)**
- **Dos guardados en el mismo segundo:** es un límite aceptado. Siempre hubo ≥ 1,6 s entre guardados.
- **Mi perfil por link:** la pantalla no tiene un link propio para salir. Se probó con la barra, Atrás y F5.
- **Conflicto entre dos personas en "Monto del presupuesto":** Carla no ve ese campo y Hernán no puede guardar. Se probó con dos pestañas de Diego.
- **409 real en Nueva obra:** no aplica, porque el alta no tiene versión.

**Textos, datos y fechas (B, 18/11)**
- **Pedir un código estando trabado:** no hubo presupuesto de ingresos.
- **"Destrabamos el ingreso de …":** el aviso no se llegó a ver (1 sola vez; ver §8).
- **Lista con la sesión borrada de verdad (`DEL`):** quedó tapada por el 401 de otra pestaña. Las listas se probaron con la cookie quitada, y el texto del 401 del back en 4 rutas.

**Re-verificación (VER, 19/11)**
- **Ingreso trabado:** el bloqueo se puso con `page.route` sobre la respuesta real. No se quemaron códigos.
- **Borde de día:** con `cuando` cambiado sobre la respuesta real, porque no se puede guardar a las 22:30.
- **Los 409 de A-01 y A-02:** forzados. El choque real lo probó A el 18/11.

**Celular y accesibilidad (CEL, 19/11)**
- **Lector de pantalla real (NVDA, VoiceOver, TalkBack):** no hay en el entorno. Se verificó por `ariaSnapshot` y `aria-busy`.
- **Safari de iOS real:** no hay. El iPhone SE es Chromium con el viewport y el UA forzados.
- **Mail y teléfono del proveedor:** en la ficha de proveedor son texto, no links, así que no hay control que medir.

**Regresiones (REG, 19/11)**
- **Borrador recuperado en el alta de proveedor, y descartado en Editar proveedor, con ingreso real:** chocó con el límite de 3 códigos por mail en 10 min (429) y con el tope de 5 ingresos del rol. Por el código, PU-305 debería pasar igual en Editar proveedor.
- **Doble clic en "Guardar cambios" de la Tabla de permisos:** no se probó. La salida mientras guarda la cubrió VER.
- **Permisos de Sofía y Pablo por separado:** son del mismo sector que Laura.

**Backend y cortes (BK, 18/11, y Lead, 19/11)**
- **Recuperación de PU-203 tras un corte de MySQL solo:** no quedó aislada, porque Laragon bajó también Redis.
- **Salidas de la parte 1 de BK** (`linea-base.log`, `migraciones.log`, `sched.out`): quedaron en el scratchpad de la máquina `B:\`, **sin evidencia en la carpeta**. El 401 sin sesión sí está (`borradores/scripts/BK/sin-sesion.txt`).
- **`logs/A-01.log`, `logs/A-02.log` y `logs/B-01.log`:** los citan los borradores del 18/11, pero no están en `logs/`. Los tres hallazgos tienen capturas y re-verificación.

## 7. Veredicto

**NO APROBADA.** Estas son las tres condiciones del §7:

| Condición | Cumple | Qué falta |
|---|---|---|
| 1. Los 20 PU en ✅ | **No** | **PU-041** ⚠️ → arreglar **PU-307**: que el nombre de Usuarios mida ≥ 44×44 o que la tarjeta entera abra el usuario, como en Obras. El hallazgo es bajo, pero es lo que queda de PU-041 (mismo problema en otra pantalla, con el criterio de las rondas 2 y 3), y la condición 1 lo pide. |
| 2. Las decisiones aprobadas funcionan como están descritas | **No** | **O-1.1** ⚠️ → **PU-306**: en la Tabla de permisos, si el guardado falla, el error tiene que quedar a la vista. O-1.2, O-3, O-4, el formato de fecha, el 401 y el nombre de los links funcionan. |
| 3. Sin hallazgos nuevos bloqueantes, altos ni medios | **No** | **PU-302** (alta) · **PU-305** (media) · **PU-306** (media) |

**Lista exacta para aprobar:** PU-302, PU-305, PU-306 y PU-307 arreglados en `staging`, y en la próxima ronda PU-041 ✅ y O-1.1 ✅.

**Pendientes menores** (bajos, no frenan; desarrollo los arregla sin otra ronda):
- **PU-308:** Plazos, "No había cambios para guardar." mientras se edita.
- **PU-309:** la pista del buscador promete proveedores.
- **PU-310:** "Ingreso trabado hasta las…" según el huso del navegador.
  - Conviene arreglarlo junto con PU-302: los dos son el resto de B-01.
- **Observación B-02:** el campo de fecha nativo sigue el idioma del navegador. Es un límite conocido.

**Próxima ronda (ronda 5, con el mismo prompt, sobre `staging`)**
- **Qué verificar:**
  - PU-041;
  - O-1.1 en la Tabla de permisos (falla con Atrás, con la barra y sin irse);
  - PU-302: "Cargado" entre las 21:00 y las 24:00, y el resto de las fechas que el back manda sin hora;
  - PU-305: vuelta con ingreso real, en Editar obra y en Editar proveedor;
  - PU-306 y PU-307.
- **Además:** cazar regresiones alrededor de esos arreglos, y confirmar los menores si ya están.
- **Datos de la ronda:** carpeta `semana-1-ronda-5`, rama `qa/prueba-usuario-semana-1-ronda-5` y hallazgos desde PU-501.
- **Presupuesto de ingresos:** reservar ≥ 4 códigos para PU-305.

## 8. Notas menores (vistas y no reportadas como hallazgo)

- **"Destrabar ingreso" (B, 18/11, 1 sola vez):** el aviso "Ingreso trabado…" desaparece, pero no se llega a ver "Destrabamos el ingreso de QA Lead R4 B." (`PU-209-r4-destrabado.png`). Probablemente el aviso de éxito se desmonta al recargar el usuario. No se pudo repetir sin otro bloqueo; conviene mirarlo en la ronda 5.
- **`down()` de la migración de links (BK):** a un link que se llamaba "drive.example" le devuelve "www.drive.example". Solo afecta un rollback y no cambia lo que ve la persona.
- **Barra inferior fija en el celular (CEL):** el toque en la parte de una tarjeta que queda detrás de la barra cae en la barra. Al deslizar, la tarjeta queda libre. Es lo esperable con una barra fija.
- **Nuevo proveedor en iPhone SE (CEL):** el diálogo es más alto que la pantalla y desliza. Mientras guarda, el aviso y los botones quedan enteros a la vista.
- **Nueva obra con un 409 forzado (A):** la pantalla se queda sin aviso. No corresponde a un caso real, porque el alta no tiene versión.

## 9. Firmas

| Rol | Firma |
|---|---|
| Verificador A (18/11) | PU-201, PU-204, PU-002 y PU-006 ✅, con dos pestañas y dos personas (Diego, Carla, Hernán y Pablo). Probó O-1.1 en 6 pantallas y 7 diálogos (ok, 422, 409 y 500), y O-1.2 en 14 diálogos. 2 hallazgos medios (A-01 → PU-303 y A-02 → PU-304, los dos arreglados en la ronda). 0 ingresos reales. · `borradores/a.md` |
| Verificador B (18/11) | PU-205, 206, 207 (incluida la evidencia anterior a la migración), 208, 209, 112, 129 y 133, más O-3, O-4 y el formato de fecha, con Diego, Hernán, Dora, Carla y `r4a`. Ingreso trabado real con `r4b`. 2 hallazgos: B-01 → PU-301 y B-02, que queda como observación. 4 códigos. · `borradores/b.md` |
| QA backend · parte 1 (18/11) | Línea de base cada ~10 min, 401 sin sesión en 4 rutas, migraciones `2026_11_18_*` (idempotencia en una base propia, creada y borrada), scheduler y plan de cortes. Sin cortes ni ingresos, y sin hallazgos. · `borradores/bk.md` |
| QA backend · parte 2, cortes (19/11, la hizo el Lead) | PU-020, PU-202, PU-203 y PU-138 ✅, con el control PU-021 ✅. Cortó Redis, MySQL y el worker de mail, con el health cada ~3 s, la API con sesión y las pestañas A y B. Relanzó workers y Redis. 1 código. · `borradores/lead-retomada.md` |
| QA celular y accesibilidad (19/11) | PU-210, PU-212 y PU-136 ✅, y PU-041 ⚠️, en Pixel 7, iPhone SE 375×667 y PC con teclado. Aclaró los "3 y 7 controles chicos" (son las tarjetas de Obras) y el diálogo en SE mientras guarda. Probó O-1.2 en el celular. 1 hallazgo (CEL-01 → PU-307). 0 ingresos reales. · `borradores/cel.md` |
| Cazador de regresiones (19/11) | Alta y edición de obra y de proveedor con adjuntos, adicional, documento, rubro con conflicto, cambio de estado, Plazos, Mi perfil, doble clic, buscador, permisos de los 5 sectores, B-01 y A-02 alrededor, y sesión vencida con ingreso real (recuperado ✅, descartado ❌). 3 hallazgos (REG-03 → PU-305, REG-01 → PU-308, REG-02 → PU-309). 5 ingresos reales. · `borradores/reg.md` |
| Re-verificación (19/11) | B-01 en 3 husos y en el borde de día (historial ✅; ingreso trabado ❌), A-01 ✅ (11 corridas), A-02 ✅ (6 corridas) y B-02 como observación. Regresión de O-1.1 en Plazos, Mi perfil y Editar obra: sin cambios. 2 hallazgos (VER-01 → PU-310, VER-02 → PU-306). 0 ingresos reales. · `borradores/ver.md` |
| QA Lead | 18/11: levantó el entorno en `B:\`, ingresó con las 7 personas, armó la evidencia de PU-207 con datos anteriores a la migración y dejó la agenda para retomar. 19/11: levantó el entorno en `C:\` (Laragon), hizo `migrate` y `migrate:fresh --seed` verificados con tinker, ingresó con las 7 personas (7 códigos), coordinó las tandas, atendió el incidente de MySQL, hizo los cortes y verificó LEAD-01 → PU-302. · `borradores/lead-retomada.md`, `estado-parcial.md` |
| QC final (19/11) | Sin navegador, sin código y sin git: solo los borradores, la evidencia y las rondas anteriores.<br>**Revisé:**<br>• las referencias de evidencia de los 7 borradores contra `capturas/` (503) y `logs/` (5), con un script;<br>• lo "esperado" del §1;<br>• las severidades contra el BRIEF y los análogos de las rondas 2 y 3 (PU-111 media, PU-109 media, PU-127, 137, 041 y 210 baja);<br>• el canal `errors` del 19/11 por franja horaria;<br>• datos sensibles en la carpeta (códigos de 6 dígitos, `eyJ`, `laravel_session`, `XSRF`, `Bearer` y tokens): no hay ninguno.<br>**Corregí:**<br>1. VER-02 → PU-306 sube de baja a **media** (mismo problema que PU-111).<br>2. VER-01 → PU-310 baja de media a **baja** (en el uso real, con las PC en Argentina, la hora sale bien; mismo criterio que B-02; no cambia el veredicto).<br>3. PU-209 pasa de ⚠️ a ✅ (el formato está resuelto; la hora tiene sus hallazgos, así que no se cuenta dos veces).<br>4. O-1.1 pasa de ✅ (VER) a **⚠️** por PU-306 (la decisión pide "el error a la vista").<br>5. B-02 queda como observación, sin PU.<br>6. "Regresión de" de PU-306: de A-01 a PU-111, porque pasa también sin irse.<br>7. F-XX normalizados (F-18, F-56 y F-58).<br>8. Evidencia: `logs/A-01.log`, `A-02.log` y `B-01.log` faltan, y las salidas de la parte 1 de BK quedaron fuera de la carpeta; se marcan "sin evidencia en la carpeta". Todas las capturas citadas existen.<br>9. Se sacaron mails de personas de prueba de `hallazgos.md`.<br>**Sostuve:** PU-302 alta, PU-305 media, PU-307, 308 y 309 baja, y PU-041 ⚠️. El incidente de MySQL y la bajada de Redis por Laragon no generaron hallazgos.<br>**Resultado:** ✅ 19 · ⚠️ 1 · ❌ 0 · **NO APROBADA**. · 2026-11-19 |
