# Reporte · Prueba de usuario Semana 1 · Ronda 2 (re-test de los arreglos) · Tecnoservicios (NS-2026-025)

**Fecha:** 2026-11-17 · **Equipo:** QA NexoSmart (QA Lead + 6 testers + QC final) · **Detalle:** [`verificacion.md`](verificacion.md) (los 45 PU de la ronda 1) · [`hallazgos.md`](hallazgos.md) (lo nuevo, PU-101…PU-139) · cobertura, no probado, observaciones y QC: [`borradores/consolidacion.md`](borradores/consolidacion.md).

## 1. Resumen ejecutivo

Los arreglos de la ronda 1 funcionan en general. De los 45 PU, **32 quedaron resueltos, 12 a medias y 1 sigue** (PU-002).

Funcionan:
- El link que se perdía al guardar, el 500 sin sesión, el canal `errors` sin 4xx y el health rápido con Redis caído y con el worker de mail parado.
- El bloqueo de ingreso con "Destrabar ingreso", la ñ al guardar y el CUIT en el buscador.
- Los loaders, las pestañas con teclado y las tarjetas en el celular.

Pero los arreglos de formularios (cambios sin guardar, borradores, conflictos) abrieron casos nuevos, y hay **39 hallazgos nuevos: 1 bloqueante · 1 alta · 11 media · 26 baja, de los cuales 23 son regresiones** de un arreglo.

**Lo más grave**
1. **PU-101 · bloqueante · regresión de PU-002.** En Editar obra, la Revisión y los datos del presupuesto principal no tienen control de versión: una segunda persona los pisa sin aviso y ve "Guardamos los cambios de la obra.".
2. **PU-102 · alta · regresión de PU-002.** Si hay conflicto, la pantalla dice "se pierde lo que cambiaste acá", pero la Revisión ya quedó guardada: la edición queda guardada a medias.
3. **PU-104 · media.** Si te vas de la pantalla mientras guarda (Atrás o un link de la barra), el cambio se guarda igual. Es el caso de PU-003 por otra puerta.
4. **PU-105 · media.** El borrador recuperado de una persona le aparece a otra que entra en la misma pestaña.
5. **PU-111 · media.** El aviso de conflicto o de error aparece arriba, fuera de la vista: parece que no pasó nada.
6. **PU-138 · media.** Si se cae Redis o MySQL, la app entera queda tapada por "No pudimos cargar esta información", sin barra, y no se recupera sola cuando vuelve: hay que tocar "Reintentar".

## 2. Entorno

| Qué | Valor |
|---|---|
| Backend | `main` @ `c27e382` (reportes en la rama `qa/prueba-usuario-semana-1-ronda-2`) |
| Frontend | `main` @ `f930579` |
| Base | MySQL 8.4.3, `tecnoservicios_prueba_usuario` (`migrate:fresh --seed` solo sobre ella, verificado antes con tinker) |
| Redis | 5.0.14, prefijos `tecnoservicios_prueba_` / `tecnoservicios_prueba_cache_` (limpiados al empezar) |
| Procesos | API `artisan serve` :8340 · front Vite :5316 · `queue:work redis --queue=default …` y `--queue=mail --sleep=1 --rest=1 …` (iguales a `config/supervisor.php`) · `schedule:work` |
| Variables | las del prompt de la ronda 1 + las nuevas de `.env.example` con sus valores (`DB_CONNECT_TIMEOUT=2`, `REDIS_TIMEOUT=1`, `REDIS_READ_TIMEOUT=3`, `HEALTH_QUEUE_MAX_AGE=120`, `HEALTH_REQUESTS_PER_MINUTE=60`, `HEALTH_LIMIT_STORE=file`) |
| Navegador | Playwright / Chromium · PC 1366×768 · Pixel 7 · iPhone SE 375×667 (forzado) · ≤ 3 navegadores a la vez (dos tandas de agentes) |
| Logs | `laravel-2026-11-17.log` y `errors-2026-11-17.log` empezaron vacíos |

**Incidentes y notas del entorno**
- El `node_modules` del front estaba vacío. Se reinstaló con `npm ci` (lockfile, sin cambios de código).
- Para no romperla, se esperó a que terminara una suite de otro proyecto (cuonti) antes de cortar Redis y MySQL.
- Al cortar MySQL desde Laragon se cortó también Redis, y Laragon no volvió a levantar Redis. Lo levantó el Lead desde su directorio de trabajo (`C:\Users\GREEN`) para que cargara su snapshot.
- En el primer corte de Redis, el Lead lo relanzó un momento desde otro directorio y levantó un snapshot viejo. Se corrigió en menos de un minuto y quedaron restauradas las 103 claves (sesiones de todos los proyectos).
- Los workers murieron en cada corte de Redis; el scheduler sobrevivió. Se relanzaron a mano (en producción lo hace Supervisord).
- PER y ENT cambiaron el teléfono de Dora, Diego y Sofía (usuarios del seeder) durante pruebas de Mi perfil y lo dejaron vacío, como estaba.

## 3. Verificación de los 45 PU de la ronda 1

**✅ 32 · ⚠️ 12 · ❌ 1**

| Estado | PU |
|---|---|
| ❌ | **PU-002** (conflictos: la obra avisa, pero el presupuesto principal y Mi perfil siguen pisando → PU-101, PU-102, PU-108, PU-111) |
| ⚠️ | PU-003 (PU-103, PU-104) · PU-004 (PU-105, PU-106, PU-109) · PU-006 (PU-104, PU-107, PU-114) · PU-013 (PU-110, PU-119) · PU-018 (PU-136) · **PU-020** (health ✅ 503 en 1,5 s; la app no se recupera sola → PU-138) · PU-022 (Sectores en el celular → PU-135) · PU-028 (sin aviso por fecha lejana → PU-117) · PU-029 (Plazos, Mi perfil, solo espacios → PU-123, PU-127, PU-121) · PU-038 (lo mismo en Cliente de la obra → PU-118) · PU-041 (PU-134) · PU-043 (el aviso del front todavía trata la ñ como n → PU-110) |
| ✅ | PU-001, 005, 007, 008, 009, 010, 011, 012, 014, 015, 016, 017, 019, 021, 023, 024, 025, 026, 027, 030, 031, 032, 033, 034, 035, 036, 037, 039, 040, 042, 044, 045 |

Los resultados de las pruebas que hizo el Lead:
- **PU-016 ✅.** El cuarto pedido de código responde "Ya pediste varios códigos. Probá de nuevo en 8 minutos.".
- **PU-021 ✅.** Con el worker de mail parado, a los 2 min `/api/health` responde 503 `"cola_mail": false` en 0,7–0,9 s. Al relanzar el worker, el mail sale una sola vez y el health vuelve a 200.
- **PU-045 ✅.**
  - Con 3 códigos quemados, el paso del código muestra "¿No te llegó? Si probaste varias veces, esperá una hora o pedile a gerencia que lo revise.".
  - En Usuarios aparece "Sí · Ingreso trabado hasta las 13:05".
  - En la ficha hay un aviso y el botón "Destrabar ingreso", que deja `bloqueo_ingreso_quitado` en el historial. Después la persona entra.
- **PU-005 / PU-039 ✅.**
  - `/api/obras/1`, `/api/adjuntos/1` y `/api/proveedores/1` sin sesión responden 401 "Tenés que ingresar para seguir.".
  - `errors-2026-11-17.log` no se creó en todo el día pese a los 401/404/409/419/422/429 que provocaron todos los roles.
  - Solo lo crearon los cortes de Redis: 4 entradas, todas 5xx.
- **PU-020 ⚠️.** Ver arriba y PU-138.

## 4. Hallazgos nuevos

**Por severidad:** bloqueante 1 · alta 1 · media 11 · baja 26 · **total 39** (23 regresiones)

| Tipo | Bloq. | Alta | Media | Baja | Total |
|---|---|---|---|---|---|
| funcional | 1 | 1 | 6 | 3 | 11 |
| mensaje | — | — | 4 | 15 | 19 |
| validación | — | — | — | 3 | 3 |
| error-servidor | — | — | 1 | 1 | 2 |
| celular | — | — | — | 2 | 2 |
| accesibilidad | — | — | — | 1 | 1 |
| consistencia-con-documento | — | — | — | 1 | 1 |

**Regresiones**, una línea cada una:
- PU-101 ← PU-002 (`fbffdca`, `669cad2`): la Revisión del presupuesto principal se pisa sin aviso.
- PU-102 ← PU-002: con conflicto dice "se pierde", pero la Revisión ya se guardó.
- PU-104 ← PU-003/PU-006 (`ce05073`, `669cad2`): irse mientras guarda lo guarda igual; Editar obra te devuelve a la ficha.
- PU-105 ← PU-004 (`ce05073`): el borrador de una persona le aparece a otra en la misma pestaña.
- PU-106 ← PU-004: la sesión vencida al subir un archivo o link queda sin salida y sin borrador.
- PU-107 ← PU-006 (`f930579`): Ctrl+clic en los links de repetido apaga el aviso o cierra el alta.
- PU-108 ← PU-002 (`c184727`): Mi perfil pisa lo que cambió gerencia.
- PU-109 ← PU-004: el borrador se descarta sin aviso si la obra cambió.
- PU-110 ← PU-043 (`8b3490f`): el aviso del front sigue tratando la ñ como n.
- PU-111 ← PU-002 (`669cad2`): el aviso de conflicto o error queda fuera de la vista.
- PU-114 ← PU-006: "Cancelar" o Escape en los diálogos tiran lo escrito sin preguntar.
- PU-115 ← PU-001 (`ebac793`): cancelar un link en curso no lo cancela.
- PU-118 ← PU-038: 422 silencioso en las sugerencias de Cliente.
- PU-119 ← PU-013/PU-035 (`fe6c562`): hasta tres avisos para el mismo proveedor repetido.
- PU-120 ← PU-001: con un link en curso, el aviso dice "subir los archivos".
- PU-121 ← PU-029: agregar solo espacios dice "Guardamos los cambios".
- PU-123 ← PU-029: Plazos sin cambios dice "Guardamos los plazos…".
- PU-127 ← PU-029 (`c184727`): "No había cambios" queda puesto junto a los errores en Mi perfil.
- PU-129 ← PU-010 (`167e14d`): la carpeta de Drive se ve con `%C3%B1` y `%20`.
- PU-132 ← PU-014 (`18abdbc`): "Proveedores: tu sector…" suena a etiqueta, y en Plazos dice "Usuarios:".
- PU-134 ← PU-041 (`bfc95ac`): quedan controles de menos de 44 px.
- PU-135 ← PU-022 (`8777360`): Sectores no entra en el celular.
- PU-136 ← PU-018 (`bfc95ac`): el foco se cae del botón mientras guarda y no se anuncia "Guardando…".

## 5. Cobertura F-XX

✅ sin hallazgos abiertos · ⚠️ con hallazgos alta/media/baja · ❌ con bloqueante o un PU que sigue.

| F-XX | Estado | PU abiertos |
|---|---|---|
| F-01 Ingreso con mail + código | ⚠️ | PU-004 ⚠️, PU-105, PU-106, PU-109, PU-133 |
| F-02 Menú según el sector | ⚠️ | PU-132 |
| F-03 Buscador general | ⚠️ | PU-137 |
| F-10 Mi perfil | ⚠️ | PU-108, PU-127 |
| F-14 Registro de quién hizo qué | ⚠️ | PU-113 |
| F-15 Listado de obras por estado | ✅ | — |
| F-16 Alta de obra | ⚠️ | PU-106, PU-107, PU-111, PU-115, PU-117, PU-118, PU-120, PU-122, PU-128, PU-131 |
| F-17 Ficha de la obra | ✅ | — |
| F-18 Presupuestos | ⚠️ | PU-112, PU-120, PU-128 |
| F-19 Documentos y contactos | ⚠️ | PU-003 ⚠️, PU-103, PU-104, PU-112, PU-114, PU-117, PU-120, PU-136 |
| F-20 Editar la obra | ❌ | PU-002 ❌, PU-101, PU-102, PU-104, PU-105, PU-109, PU-111, PU-117, PU-118, PU-121, PU-129 |
| F-21 Cambiar estado | ⚠️ | PU-104, PU-125 |
| F-22 Obras terminadas | ⚠️ | PU-125 |
| F-23 Fechas previstas | ⚠️ | PU-117 |
| F-48 Presupuesto por rubro | ⚠️ | PU-113, PU-130 |
| F-55 Proveedores con buscador | ⚠️ | PU-137 |
| F-56 Alta y edición de proveedor | ⚠️ | PU-013 ⚠️, PU-043 ⚠️, PU-104, PU-105, PU-107, PU-110, PU-114, PU-116, PU-119, PU-131, PU-136 |
| F-58 Usuarios | ⚠️ | PU-108, PU-114, PU-131 |
| F-59 Sectores | ⚠️ | PU-124, PU-135 |
| F-60 Tabla de permisos | ⚠️ | PU-126, PU-134 |
| F-61 Jerarquía | ✅ | — |
| F-68 Plazos de la prioridad | ⚠️ | PU-123, PU-132 |
| Adjuntos (ADR-014) | ⚠️ | PU-103, PU-106, PU-112, PU-115, PU-120, PU-129 |
| Sesión vencida / borradores | ⚠️ | PU-004 ⚠️, PU-105, PU-106, PU-109, PU-133 |
| Conflictos (dos personas / pestañas) | ❌ | PU-002 ❌, PU-101, PU-102, PU-108, PU-111 |
| Cambios sin guardar | ⚠️ | PU-006 ⚠️, PU-104, PU-107, PU-114 |
| 403 / 404 | ⚠️ | PU-132 |
| Celular | ⚠️ | PU-022 ⚠️, PU-041 ⚠️, PU-111, PU-134, PU-135 |
| Accesibilidad | ⚠️ | PU-018 ⚠️, PU-111, PU-136 |
| Loaders | ✅ | — |
| Mails y cola | ✅ | — |
| Scheduler | ✅ | `adjuntos:purgar-huerfanos` e `idempotencia:purgar` sin error; `schedule:work` sobrevivió a los cortes |
| Health y errores de servidor | ⚠️ | PU-020 ⚠️, PU-138, PU-139 |

**Cortes forzados**

| Corte | `/api/health` | Lo que ve el usuario | Al volver |
|---|---|---|---|
| Redis | 503 en **1,46–1,87 s** con `"redis": false` (antes 51 s) ✅ | "No pudimos cargar esta información. El sistema tuvo un problema. Volvé a intentar en un momento. · Reintentar", pantalla completa sin barra (PU-138) | health 200 en 0,24 s; las sesiones siguen; la pantalla no se recupera sola, sí con "Reintentar" |
| Worker de mail | 503 `"cola_mail": false` a los 2 min ✅ | "Si el mail está registrado, te llegó un código." + "¿No te llegó?…" | el mail sale una vez; health 200 |
| MySQL (+ Redis, por Laragon) | 503 en **9,2–9,4 s** (PU-139) | el mismo mensaje de pantalla completa a los ~11 s | health 200; la sesión guardada sigue |

## 6. Bugs del QC anterior (`docs/qc/entrega-1.md`)

| Bug | Estado ronda 2 |
|---|---|
| B-01 Ficha de la obra en el celular | ✅ Pixel 7: sin scroll horizontal, los 9 "editar/quitar" dentro de la pantalla (`capturas/B-01-r2-ficha-pixel7.png`) |
| B-02 Códigos internos "(W-xx)" | ✅ ninguno en Mi perfil, Usuarios, Sectores, Avisos, Pedidos, ficha y edición de obra |
| B-03 Secciones no entregadas | ✅ sin campana; `/avisos` y `/pedidos`: "Esta sección todavía no está disponible · Va a estar disponible en una próxima entrega." (`B-03-r2-avisos-pc.png`) |
| B-04 Corte en 8 MB | ✅ en este entorno (ENT: 20 MB entra, 21 MB con mensaje claro); sigue sin verificarse el 413 con un PHP de 8 MB |
| B-05 Importes del historial | ✅ "Monto del presupuesto: (vacío) → 20.000.000" (`B-05-r2-historial-montos.png`) |

## 7. Lo que no se pudo probar y por qué

- **MySQL caído solo.** Laragon paró MySQL y Redis juntos.
- **Dos guardados en el mismo segundo.** Es un límite aceptado (PU-002).
- **PU-004 con ingreso real en otros formularios.** Solo había 2 ingresos autorizados por rol. Usuario, Mi perfil, permisos, documentos y adicionales no tienen borrador por diseño.
- **Cambio de persona en la misma pestaña con ingreso real (PU-105).** Se simuló cargando la sesión guardada de la otra persona.
- **Lector de pantalla real y teclado virtual del celular.** No se probaron.
- **Algunas celdas de celular.** Faltaron el conflicto 409, la ficha de usuario y la búsqueda de proveedores en Pixel 7.
- **Teclado en "Destrabar ingreso".** Durante el turno del QA de celular no había usuarios trabados.
- **413 con un PHP de 8 MB (B-04).**

El detalle está en `borradores/consolidacion.md` §(b).

## 8. Firmas

| Rol | Firma |
|---|---|
| QA Lead | Levantó el entorno (reset de la base, `npm ci` del front, workers iguales a producción), repartió las sesiones (7 personas) en dos tandas de agentes y coordinó los cortes con el usuario. Verificó PU-005, 016, 020, 021, 039 y 045, el health con Redis, worker y MySQL caídos, el scheduler y B-01…B-05. Sumó PU-138 y PU-139 y escribió este reporte. · 2026-11-17 |
| Verificador de arreglos A (VA) | PU-001…PU-022 con los pasos y las demoras de la ronda 1 · `borradores/va.md` |
| Verificador de arreglos B (VB) | PU-023…PU-045 (incl. "solo sus obras" con QA-VB Vera, ñ y CUIT) · `borradores/vb.md` |
| Cazador de regresiones (REG) | Cambios sin guardar, borradores, 409, sesión vencida real, salidas mientras guarda, links en curso · `borradores/reg.md` |
| Tester funcional por persona (PER) | Pasada completa de la Semana 1 con Dora, Diego, Hernán, Laura, Sofía, Pablo y Carla · `borradores/per.md` |
| QA de entradas y UX writer (ENT) | Todos los formularios con datos inválidos, adjuntos y links, y todos los textos nuevos · `borradores/ent.md` |
| QA celular, accesibilidad y loaders (UXM) | 24 pantallas en Pixel 7 e iPhone SE, teclado y foco, loaders con 2,5 y 15 s · `borradores/uxm.md` |
| QA de backend, cola y health | Lo cubrió el Lead: logs, `failed_jobs`, canal `errors`, health, workers y scheduler |
| QC final | Consolidó los 6 borradores, dedujo el estado de cada PU, fusionó duplicados, releyó en el código las causas de PU-101, 102, 103 y 111 (se sostienen) y revisó que no haya datos sensibles · `borradores/consolidacion.md` §(f) · 2026-11-17 |
