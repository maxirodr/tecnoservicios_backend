# Reporte · Prueba de usuario Semana 1 · Ronda 3 (re-test corto de los arreglos de la ronda 2) · Tecnoservicios (NS-2026-025)

**Fecha:** 2026-11-18 · **Equipo:** QA NexoSmart (QA Lead + 6 roles + QC final) · **Detalle:** [`verificacion.md`](verificacion.md) (los 52 PU) · [`hallazgos.md`](hallazgos.md) (lo nuevo, PU-201…PU-212; PU-211 sin usar) · [`observaciones.md`](observaciones.md) (decisiones de UX a validar por Juani) · borradores de cada rol en [`borradores/`](borradores/).

## 1. Resumen ejecutivo

Los arreglos de la ronda 2 funcionan. De los 52 PU verificados, **43 quedaron resueltos, 9 a medias y ninguno sigue igual**.

Funcionan:
- Los conflictos de edición en obra, presupuesto principal, proveedor, usuario y Mi perfil.
- La salida mientras se guarda, los diálogos que preguntan antes de cerrar y los borradores por persona.
- La sesión vencida al subir un archivo.
- Los textos de proveedores, montos, fechas, revisiones, permisos y sectores.
- El celular (tarjetas, 44 px) y el foco en los botones de guardar.
- El health rápido con MySQL caído (1,2 s, antes 9 s).

Quedan **11 hallazgos nuevos: 1 bloqueante · 1 alta · 2 media · 7 baja**. Ninguno es regresión: 5 son arreglos que quedaron sin terminar (PU-202, 203, 206, 207 y 212).

**Lo más grave**
1. **PU-201 · bloqueante.** En el **presupuesto por rubro**, una segunda pestaña o persona pisa sin aviso los montos que guardó la primera, y el historial le atribuye el borrado a la segunda. Es el problema de PU-002 en una pantalla que no tenía control de versión.
2. **PU-202 · alta.** Con **MySQL caído**, cualquier pedido con sesión tarda 38 s y responde 500 "El sistema tuvo un problema." en vez de 503, sin dejar nada en el canal `errors`. Una pestaña nueva queda en "Cargando el sistema…" sin salida. Es un arreglo incompleto de PU-138.
3. **PU-203 · media.** Después de un corte de Redis, la pantalla que ya estaba abierta no se recupera sola: sigue diciendo "El sistema no está disponible…" aunque ya volvió. Es el otro resto de PU-138.
4. **PU-204 · media.** El presupuesto por rubro y Plazos de la prioridad se van **sin preguntar** con cambios sin guardar, y se pierde lo escrito.

## 2. Entorno

| Qué | Valor |
|---|---|
| Máquina | sin Laragon (`B:\ProgramFiles\NexoSmart\projects\…`) |
| Backend | `main` @ `66160b1` (reportes en la rama `qa/prueba-usuario-semana-1-ronda-3`) |
| Frontend | `main` @ `1914ea8` |
| Base | **MariaDB 10.4.32 de XAMPP**, no MySQL 8.4 como en la ronda 2. La lección del repo dice que el orden de las claves JSON difiere. `tecnoservicios_prueba_usuario` se creó con `CREATE DATABASE IF NOT EXISTS`, y se hizo `migrate:fresh --seed` después de verificar con tinker que la config apuntaba a ella. |
| Redis | **5.0.14.1 portable** (tporadowski), bajado a `C:\redis` porque la máquina no tenía Redis. Prefijos `tecnoservicios_prueba_` / `tecnoservicios_prueba_cache_`. |
| PHP | 8.4.11 CLI. Se agregaron `upload_max_filesize=25M`, `post_max_size=30M` y `memory_limit=512M` solo a los procesos de la prueba (`PHP_INI_SCAN_DIR`), porque el PHP de la máquina tiene 8M. |
| Procesos | API `artisan serve` :8340 · front Vite :5316 · `queue:work redis --queue=default …` y `--queue=mail --sleep=1 --rest=1 …` · `schedule:work` (iguales a `config/supervisor.php`) |
| Variables | Las del §3 del prompt de la ronda 1. Además, las de `.env.example` (`DB_CONNECT_TIMEOUT=2`, `REDIS_TIMEOUT=1`, `REDIS_READ_TIMEOUT=3`, `HEALTH_*`, `ADJUNTOS_*`) e `IDENTIDAD_CODIGO_EN_PANTALLA=false`. |
| Navegador | Playwright / Chromium headless · PC 1366×768 · Pixel 7 · iPhone SE 375×667 (forzado) · ≤ 3 navegadores a la vez |
| Personas | Diego, Dora (la dio de alta Diego), Hernán, Laura, Sofía, Pablo y Carla, más los usuarios `qa-lead-b1`, `qa-lead-b2`, `qa-lead-reg` y `qa-lead-cel` |
| Códigos de ingreso pedidos | Lead 7 · Verificador B 7 · Cazador de regresiones 3 · QA backend 1 → **18 de 20** |
| Horas | locales (UTC−3). Los logs del back están en UTC. |

**Incidentes del entorno**
- **MySQL y Redis estaban apagados al empezar.** El usuario pidió que los levantara el Lead: MariaDB con el comando del prompt, y Redis bajado y arrancado.
- **Redis se reinició a las 10:35:20 y ningún rol lo reconoció como propio.** Las sesiones sobrevivieron, porque el `dump.rdb` las conservaba.
- **BK-01: 6 respuestas 500 en `/api/me` por `unserialize()` (10:54–10:55).** Las causó el script del Verificador B, que restauró a mano su propia sesión de `qa-lead-b1` en Redis con un salto de línea de más. Es un artefacto de la prueba, no un bug. B borró la clave, corrigió el script y no volvió a escribir valores. Extracto: `logs/BK-01.log`.
- **En la parte 2 (cortes) hubo tres problemas de operación del QA backend:**
  - Un `Stop-Process` al PID de un worker ya muerto mató al `redis-server` nuevo, que había reusado ese PID (12:03:47). Se relanzó desde `dump.rdb` y las 7 sesiones siguieron válidas.
  - Un `mysqld` lanzado sin consola se colgó durante 6 min. Por eso el primer corte de MySQL duró 9 min.
  - Los workers murieron en cada corte de Redis (en producción los relanza Supervisord).
  - Se relanzaron todos. Al final (12:21), el health daba 200 con todo en `true` y las 7 sesiones respondían 200.
- **`/api/errors` no se pudo consultar**, porque `ERRORS_TOKEN` no está configurado (401). Se hizo un solo intento, para no sumar al bloqueo por IP. El canal `errors` se verificó por el archivo.
- **El caso "varias pestañas con la sesión vencida" del Verificador B no fue concluyente.** El Cazador de regresiones lo repitió con 2 pestañas, sin demora y mirando 25 s, y no se reproduce: las dos van a Ingreso con "Tu sesión venció. Volvé a ingresar." y guardan su borrador. La causa probable de la primera vez es una trampa del entorno: si se borra la sesión con pedidos en vuelo, Laravel la vuelve a escribir y el CSRF queda cruzado.
- Un `vendor/bin/phpunit` corrió en paralelo sobre `tecnoservicios_testing` (con array/sync), sin tocar la base de la prueba.

## 3. Verificación de los 52 PU

**✅ 43 · ⚠️ 9 · ❌ 0**

| Estado | PU |
|---|---|
| ❌ | — |
| ⚠️ | **PU-002** (rubro pisa → PU-201) · PU-006 (rubro y Plazos sin aviso → PU-204) · **PU-020** (la pantalla abierta no se recupera → PU-203) · PU-041 (PU-210) · PU-112 (historial por dominio → PU-207; link codificado al pasar el mouse → PU-205) · PU-129 (PU-205) · PU-133 (401 del backend → PU-206) · PU-136 (botones de adjuntos → PU-212) · **PU-138** (PU-202, PU-203) |
| ✅ | PU-101, 102, 103, 104, 105, 106, 107, 108, 109, 110, 111, 113, 114, 115, 116, 117, 118, 119, 120, 121, 122, 123, 124, 125, 126, 127, 128, 130, 131, 132, 134, 135, 137, 139 · PU-003, 004, 013, 018, 022, 028, 029, 038, 043 |

- Los 52 PU aparecen en algún borrador. Ninguno quedó sin verificar.
- Hay 4 estados que bajan de ✅ a ⚠️ respecto de su borrador (PU-002, PU-006, PU-112 y PU-129), por el criterio de la ronda 2: el mismo problema sigue en otra pantalla, según otro rol. Con los estados de los borradores, el conteo sería ✅ 47 · ⚠️ 5. Ver `verificacion.md`.
- **Controles del QA backend (fuera de los 52):**
  - PU-021 ✅: con el worker de mail parado, `"cola_mail": false` a los ~2 min, y al relanzarlo sale un solo mail.
  - PU-005 ✅: 12 pedidos sin sesión → 401 "Tenés que ingresar para seguir.", sin entradas en el canal `errors`.
- PU-028 y PU-117 quedan ✅ sujetos a que Juani valide el aviso de fecha lejana (`observaciones.md` O-3).

## 4. Hallazgos nuevos

**Por severidad:** bloqueante 1 · alta 1 · media 2 · baja 7 · **total 11** (0 regresiones y 5 arreglos incompletos)

| Tipo | Bloq. | Alta | Media | Baja | Total |
|---|---|---|---|---|---|
| funcional | 1 | — | 1 | — | 2 |
| error-servidor | — | 1 | — | — | 1 |
| loader | — | — | 1 | — | 1 |
| mensaje | — | — | — | 5 | 5 |
| celular | — | — | — | 1 | 1 |
| accesibilidad | — | — | — | 1 | 1 |

| PU | Severidad | Título |
|---|---|---|
| PU-201 | bloqueante | Presupuesto por rubro: la segunda pestaña o persona pisa sin aviso los montos de la primera |
| PU-202 | alta | Con MySQL caído, los pedidos con sesión tardan 38 s y dan 500 en vez de 503, sin registro |
| PU-203 | media | Tras un corte de Redis, la pantalla abierta no se recupera sola |
| PU-204 | media | Presupuesto por rubro y Plazos se van sin preguntar con cambios sin guardar |
| PU-205 | baja | Al pasar el mouse por un link con ñ o espacios se ve "%C3%B1" y "%20" |
| PU-206 | baja | Con la sesión vencida, el listado dice "Tenés que ingresar para seguir." |
| PU-207 | baja | El historial nombra los links por el dominio ("agregado (drive.example)") |
| PU-208 | baja | El historial pone comillas simples al certificado |
| PU-209 | baja | La misma ficha muestra las fechas con dos formatos |
| PU-210 | baja | Links de texto de 19–21 px en el celular: el número de la tarjeta de Obras (lo único que la abre), "‹ Volver a la obra", "‹ Proveedores", mail y teléfono de contactos |
| PU-212 | baja | Con teclado, "Pegar link" y "Agregar link" dejan el foco fuera del diálogo |

**Regresiones:** ninguna. La consolidación marcaba PU-206, 207 y 212 como regresiones; QC revisó el código (solo lectura) y ninguno rompió algo que antes funcionaba: son casos que el arreglo no cubrió.

**Arreglos incompletos (no son regresión):**
- PU-202 ← PU-138 (back `5c556c3`): con MySQL caído, el reporte del error vuelve a ir a la base y termina en 500 a los 38 s.
- PU-203 ← PU-138 (front `d468ada`): el reintento automático es solo de la sesión, no de la pantalla.
- PU-206 ← PU-133 (back `f5b7500`): unificó 419 y 429, pero el 401 sigue en "Tenés que ingresar para seguir." (ya estaba antes) y le gana al texto del front.
- PU-207 ← PU-112 (front `e2605e5`/`3d48f82`): el historial sigue nombrando los links por el dominio, que el back guarda como nombre desde antes.
- PU-212 ← PU-136 (front `e062ff0`): el foco quedó arreglado en los botones de guardar; `AdjuntosField` no se tocó y sus botones ya lo perdían.

## 5. Cobertura F-XX (solo funciones tocadas)

✅ sin hallazgos abiertos · ⚠️ con hallazgos alta/media/baja o un PU ⚠️ · ❌ con bloqueante o un PU que sigue.

| F-XX | Estado | PU abiertos |
|---|---|---|
| F-01 Ingreso con mail + código / sesión vencida | ⚠️ | PU-133 ⚠️, PU-206 |
| F-02 Menú según el sector | ✅ | — |
| F-03 Buscador general | ✅ | — |
| F-10 Mi perfil | ✅ | — |
| F-14 Registro de quién hizo qué (historial) | ⚠️ | PU-207, PU-208, PU-209 |
| F-15 Listado de obras por estado | ⚠️ | PU-210 |
| F-16 Alta de obra | ⚠️ | PU-205, PU-212 |
| F-17 Ficha de la obra | ⚠️ | PU-209 |
| F-18 Presupuestos (principal y adicionales) | ⚠️ | PU-112 ⚠️, PU-205 |
| F-19 Documentos y contactos | ⚠️ | PU-136 ⚠️, PU-205, PU-210, PU-212 |
| F-20 Editar la obra | ⚠️ | PU-129 ⚠️, PU-207 |
| F-21 Cambiar estado | ✅ | — |
| F-22 Obras terminadas | ✅ | — |
| F-23 Fechas previstas | ⚠️ | PU-209 |
| F-48 Presupuesto por rubro | ❌ | PU-201 (bloqueante), PU-204, PU-210 |
| F-55 Proveedores con buscador | ⚠️ | PU-206 |
| F-56 Alta y edición de proveedor | ⚠️ | PU-210 |
| F-58 Usuarios | ✅ | — |
| F-59 Sectores | ✅ | — |
| F-60 Tabla de permisos | ✅ | — |
| F-68 Plazos de la prioridad | ⚠️ | PU-204 |
| Adjuntos (ADR-014) | ⚠️ | PU-112 ⚠️, PU-129 ⚠️, PU-205, PU-207, PU-212 |
| Sesión vencida / borradores | ⚠️ | PU-133 ⚠️, PU-206 |
| Conflictos (dos personas / pestañas) | ❌ | PU-002 ⚠️, PU-201 (bloqueante) |
| Cambios sin guardar | ⚠️ | PU-006 ⚠️, PU-204 |
| 403 / 404 | ✅ | — |
| Celular | ⚠️ | PU-041 ⚠️, PU-210 |
| Accesibilidad | ⚠️ | PU-136 ⚠️, PU-212 |
| Loaders | ⚠️ | PU-203 |
| Mails y cola | ✅ | — |
| Scheduler | ✅ | `adjuntos:purgar-huerfanos` e `idempotencia:purgar` sin error, a mano y desde `schedule:work` (11:00:01); `schedule:work` sobrevivió a todos los cortes |
| Health y errores de servidor | ⚠️ | PU-020 ⚠️, PU-138 ⚠️, PU-202 (alta), PU-203 |

**Cortes forzados** (QA backend, parte 2, 11:56–12:21, sin otros roles probando)

| Corte | `/api/health` | Lo que ve el usuario | Al volver |
|---|---|---|---|
| Redis (2 corridas válidas; una tercera se descartó) | 503 con `"redis": false, "cache": false, "cola_mail": false`, `"database": true` en **1,15–2,30 s** (3,15 y 3,71 s con dos medidores y dos pestañas contra `artisan serve`) ✅ | Pestaña abierta: la barra queda y el error va solo en el contenido, "No pudimos cargar esta información · El sistema no está disponible en este momento. Probá de nuevo en unos minutos. · Reintentar" ✅. Pestaña nueva: el mismo texto, más "Apenas vuelva, esta pantalla se recupera sola." `/api/me` → 503, nunca 500. `pedir-codigo` → 503 en 1,2 s. | Health 200 en la primera medición. La pestaña nueva vuelve sola antes de 8 s ✅. La abierta sigue con el error a los +70 s (PU-203). Las sesiones sobreviven. Los workers murieron y se relanzaron. |
| Worker de mail | 503 `"cola_mail": false` a los ~2 min (0,2 s) ✅ | "Si el mail está registrado, te llegó un código." | Un solo mail; health 200 |
| MySQL (Redis arriba, 2 cortes) | 503 `"database": false` en **1,23–1,25 s** (antes 9,2–9,4 s) ✅. Con pestañas pidiendo `/api/me`, se encola detrás: 17,8 s y timeouts de 30 s. | `/api/me` y `/api/proveedores` → **500** "El sistema tuvo un problema. Volvé a intentar en un momento." a los 38,6 s, sin entrada en el canal `errors`. Pestaña nueva en "Cargando el sistema…" sin salida (PU-202). | Las dos pestañas se recuperan solas; health 200 en 0,31 s |

**Canal `errors` y cola durante el día:**
- Ningún 4xx en el canal `errors`.
- Las únicas entradas son las 6 de BK-01 (artefacto), 38 de Predis durante los cortes de Redis (todas respondidas con 503) y una `FatalError` "Maximum execution time of 30 seconds exceeded" durante el corte de MySQL.
- `failed_jobs` en 0 y sin mails duplicados (18 mails).
- Línea de base cada ~10 min (10:41–11:35): health siempre 200 (0,29–1,80 s).

## 6. Lo que no se pudo probar y por qué

**Conflictos y guardado (Verificador A)**
- **Descripción del presupuesto principal en conflicto:** W-23 no tiene ese campo.
- **Adjunto del documento de aprobación en conflicto:** no se chocó por separado. Viaja en el mismo `PATCH /api/obras` que el N.º de OC, que sí se chocó.
- **Dos guardados en el mismo segundo:** es un límite aceptado (PU-002).
- **500 real del backend:** se simuló en el navegador. En la tanda 1 no se cortaba nada.

**Sesión y borradores (Verificador B)**
- **"Agregar link" con la sesión vencida en una sola pestaña:** el ingreso extra se usó para "Subir archivo". La redirección coincidió con otro 401 y no se pudo separar qué pedido la provocó.
- **Vuelta con ingreso real después del vencimiento:** `qa-lead-b1` dio 429 y se terminó el presupuesto de ingresos. La lectura del borrador se probó copiando el borrador real de b2 a la sesión de Diego.
- **"Salir" borra los borradores de la pestaña:** no quedó probado, porque la pestaña no tenía ninguno.
- **b2 en Proveedores:** Ingeniería no tiene acceso, así que no sirvió como "otra persona" para el borrador de proveedor.

**Textos y datos (Verificador C)**
- **PU-125:** se pasó una sola vez de punta a punta.
- **PU-118:** no tiene captura, porque no hay nada visible. La verificación es por la red.
- **PU-133:** la espera de 429 y "Todavía estamos guardando lo anterior" se revisaron solo en el código.
- **Sesión vencida de C:** fue simulada (cookie quitada en su navegador). El vencimiento real lo probaron B y REG.

**Celular y accesibilidad (QA celular)**
- **Lector de pantalla real (NVDA, TalkBack, VoiceOver) y teclado virtual:** se verificó solo por el árbol de accesibilidad. "Guardando…" se anuncia por el cambio de nombre del botón enfocado, no por una región `aria-live`.
- **"Subir archivo" con teclado y "Cancelar" de "Pegar link":** probablemente tienen el mismo patrón de PU-212.
- **Mi perfil:** solo se midió.
- **Celular real (Safari iOS, Chrome Android):** todo fue con emulación de Chromium.

**Regresiones (Cazador de regresiones)**
- **Primer intento de sesión vencida, no válido:** el `DEL` se hizo con pedidos en vuelo y la sesión resucitó. El intento final sí venció de verdad, pero la vuelta no tuvo ingreso real, porque ya no quedaban.
- **Cerrar la pestaña y Ctrl+R por teclado:** en headless se ve el `beforeunload`, pero no el texto del navegador. Ctrl+R se cubrió con `reload()`.

**Backend y cortes (QA backend)**
- **Corte simultáneo de Redis + MySQL:** no se hizo, para no sumar otro corte de MySQL.
- **Purga con datos de más de 24 h:** no había datos tan viejos. Solo se probó que los comandos corren sin error.
- **`/api/errors`:** no se pudo consultar, porque falta `ERRORS_TOKEN`.

**Notas menores que los roles vieron y no reportaron como hallazgo:**
- en Sectores del celular, el separador "·" entre "renombrar" y "sacar" queda más arriba de la línea;
- los nombres de las tarjetas de Usuarios miden 44 de alto pero 33–40 de ancho;
- el placeholder del listado de proveedores sigue diciendo "Buscar proveedor…", sin mencionar el CUIT.

## 7. Firmas

| Rol | Firma |
|---|---|
| QA Lead | Levantó el entorno en la máquina sin Laragon: MariaDB de XAMPP y Redis portable bajado a `C:\redis`, base verificada con tinker y reseteada, PHP con límites de subida solo para la prueba, y workers iguales a producción. Ingresó con cada persona (7 códigos) y repartió las sesiones. Coordinó las tandas y los cortes y escribió los cuatro reportes. · 2026-11-18 |
| Verificador A | 16 PU (101, 102, 103, 104, 107, 108, 111, 114, 115, 121, 123, 127, 002, 003, 006, 029), con dos pestañas y dos personas y con red lenta donde la ronda 2 la usó. Cubrió obra, presupuesto principal, proveedor, usuario y Mi perfil vs Usuarios. 0 ingresos reales. · `borradores/a.md` |
| Verificador B | PU-105, 106, 109, 112, 120, 129 y PU-004, con sesión vencida de verdad (DEL en Redis e ingresos reales de b1 y b2, 7 códigos). 1 hallazgo (PU-205). El caso de varias pestañas quedó no concluyente. · `borradores/b.md` |
| Verificador C | 20 PU (110, 113, 116, 117, 118, 119, 122, 124, 125, 126, 128, 130, 131, 132, 133, 137, 013, 028, 038, 043) con Carla, Diego, Hernán, Dora, Sofía y Laura. Cubrió proveedores, obras, sectores, permisos, buscador y pantallas sin acceso. 4 hallazgos (PU-206…209). 0 ingresos reales. · `borradores/c.md` |
| QA celular y accesibilidad | 6 PU (134, 135, 136, 018, 022, 041) en Pixel 7, iPhone SE y PC con teclado. Midió 20 pantallas y 11 diálogos, y probó la trampa de Tab en 10 diálogos. 3 hallazgos en el borrador (CEL-01…03 → PU-210 y PU-212; QC fusionó CEL-01 y CEL-02). 0 ingresos reales. · `borradores/cel.md` |
| QA backend, cola y health | PU-020, 138 y 139, más los controles PU-021 y PU-005. Hizo la línea de base (6 corridas), revisó el scheduler y cortó Redis ×2, el worker de mail ×1 y MySQL ×2. Revisó el canal `errors`, los workers y las sesiones. 2 hallazgos (PU-202, PU-203). BK-01 quedó como incidente. 1 código pedido. · `borradores/bk.md` |
| Cazador de regresiones | Probó el aviso de salida (6 vías × 6 pantallas), los borradores recuperados y descartados, el 409 en obra, presupuesto principal y perfil, los diálogos guardando y el doble clic (8 pantallas), las pestañas y "Buscando…". Hizo el alta completa de obra y de proveedor con adjuntos, el cambio de estado, el presupuesto por rubro, los permisos (5 sectores × 17 URLs) y la sesión vencida con 2 pestañas (no se reproduce). 3 hallazgos (PU-201, PU-204, PU-207). 3 ingresos reales. · `borradores/reg.md` |
| QC final | Sin navegador ni código, solo los reportes y lectura de git. **Revisé:** cada referencia de evidencia de los 4 reportes contra `capturas/` (242) y `logs/` (6) con un script; las 4 bajadas de estado contra los borradores y los hallazgos de la ronda 2; lo "esperado" y lo "a validar" del §1; formato y severidades contra la regla de la ronda 1 y los análogos de la ronda 2 (PU-002/101 bloqueante, PU-006/107 media, PU-041/136 baja); la línea "Regresión de" con `git log`/`git show` de `f5b7500`, `e062ff0`, `3d48f82` y `RegistrarLinkAction`; y datos sensibles en toda la carpeta (códigos de 6 dígitos, `eyJ`, `laravel_session`, `XSRF`, cookies, tokens y mails). **Corregí:** (1) PU-211 fusionado en PU-210 (mismo problema: links en línea de 19–21 px, resto de PU-041) → 11 hallazgos, baja 7, celular 1; (2) PU-206, 207 y 212 pasan de "Regresión de" a "arreglo incompleto": ninguno rompió algo que funcionaba → 0 regresiones, 5 arreglos incompletos; (3) evidencia: PU-118 y el control PU-005 quedan marcados **sin evidencia** en la carpeta (solo red y scripts del scratchpad), PU-013 cita sus capturas; (4) el mail del contacto de la factory (`@cliente-ejemplo.com`) se sacó de `hallazgos.md` (sigue en `borradores/cel.md`, que no se toca). **Sostuve** las 4 bajadas a ⚠️ (PU-002, 006, 112, 129): ✅ 43 · ⚠️ 9 · ❌ 0. Sin referencias rotas en los 4 reportes (las de `<ROL>-NN` de la tabla de renombres son los nombres viejos, a propósito). Sin códigos, tokens, cookies ni mails reales; "nombre@empresa.com" es el texto exacto de la app. · 2026-11-18 |
