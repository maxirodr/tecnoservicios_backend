# Reporte · Prueba de usuario Semana 1 · Ronda 5 (última ronda completa) · Tecnoservicios (NS-2026-025)

**Fecha:** 2026-11-19 · **Equipo:** QA NexoSmart (QA Lead + roles A, B, CEL, REG; rol 4 backend lo hizo el Lead; QC final) · **Detalle:** [`verificacion.md`](verificacion.md) (los 8 PU, la nota del §8 y las decisiones aprobadas) · [`hallazgos.md`](hallazgos.md) (lo nuevo, PU-501 y PU-502) · borradores de cada rol en [`borradores/`](borradores/).

## 1. Resumen ejecutivo

**Veredicto: APROBADA.** La Semana 1 queda aprobada (detalle en el §9). No hace falta `re-verificacion.md`.

Los 7 hallazgos abiertos de la ronda 4 y PU-041 quedaron resueltos: **✅ 8 · ⚠️ 0 · ❌ 0.**
- **PU-302 (alta):** "Cargado" sale con el día de Argentina, por API y en pantalla, en cualquier huso. Las fechas de calendario no se corren.
- **PU-305 (media):** con ingreso real, en Editar obra y en Editar proveedor, al volver dice "Mientras no estabas, alguien cambió esto…" con lo que guardó el otro; sin cambio de otro, recupera lo escrito. Nada se pisa.
- **PU-306 (media):** en la Tabla de permisos, si el guardado falla, la pantalla sube al error y le pone el foco (20 de 20 corridas). Con esto **O-1.1 pasa a ✅**.
- **PU-307 y PU-041:** en el celular, la tarjeta entera de Usuarios abre "Editar usuario", con un solo foco; ningún control que cuente queda < 44 px.
- **PU-308, PU-309 y PU-310 (bajas):** resueltos.

**La nota del §8 de la ronda 4** ("Destrabamos el ingreso de …") anda en la mayoría de los casos, pero a veces el aviso no aparece: **PU-501, baja**. Además apareció **PU-502, baja** (accesibilidad, no regresión). Los dos son pendientes menores para la Semana 2.

**Ninguna regresión.** Backend sano toda la ronda: health 200, 0 `failed_jobs`, canal `errors` vacío.

## 2. Entorno

| Qué | Valor |
|---|---|
| Máquina | con Laragon (`C:\NexoSmart\Projects\…`) |
| Backend | `staging` @ **`6516ba7`** (arreglo de PU-302: `a8deacf`, merge `f98fdd1`). Desde las 12:02 servido desde el worktree `tecnoservicios_backend_qa5` (rama `qa/prueba-usuario-semana-1-ronda-5`) en **`f36c23a`** = `6516ba7` + docs (prompt actualizado y regla nueva del §7), **sin cambios de código** |
| Frontend | `staging` @ **`d2a4806`** (arreglos de PU-305 a PU-310 y de la nota §8), desde el worktree `tecnoservicios_frontend_qa5` (`npm ci`) |
| Base | `tecnoservicios_prueba_usuario`: `migrate` ("Nothing to migrate", 25 migraciones) y `migrate:fresh --seed`, con la base verificada antes con tinker |
| Config | `config:clear` después del pull; `app.zona_empresa` = `America/Argentina/Buenos_Aires` (tinker) |
| Variables | `APP_DEBUG=false`, `MAIL_MAILER=log`, `QUEUE_CONNECTION=redis`, prefijos de Redis propios, `DB_CONNECT_TIMEOUT=1`, `IDENTIDAD_CODIGO_EN_PANTALLA=false`, `ERRORS_TOKEN` nuevo (fuera del repo) |
| Procesos | API :8340 · front :5316 · workers `default` y `mail` iguales a `config/supervisor.php` · `schedule:work` |
| Navegador | Playwright / Chromium · PC 1366×768 · Pixel 7 · iPhone SE 375×667 (forzado) · `--lang=es-AR` · husos Argentina, UTC y Madrid |
| Códigos de ingreso | Lead 7 · A 6 · REG 4 → **17 de 20**, en ~2 h. Ningún 429 ni código quemado. B y CEL: 0 |
| Prefijos de datos | `QA5A`, `QA5B`, `QA5R` (`QA5C` sin usar) |
| Horas | locales (UTC−3); los logs del back en UTC |

### Incidentes del entorno
- **Otra sesión movió los repos principales a `main` (Semana 2).** A las 11:48, una sesión de desarrollo en el mismo repo del backend pasó de la rama de QA a `staging`, commiteó `f36c23a` (incluyó la actualización del prompt hecha por el Lead y la regla nueva del §7) y dejó el repo en `main`, que después avanzó con código de la Semana 2 (`dc57bae`).
  - La API levantada desde el repo principal sirvió ese código entre ~11:48 y 12:02. **Nadie probó en ese lapso** (los borradores empiezan a las 12:05) y no se corrió ninguna migración (25, 0 pendientes).
  - Desde 12:02 la prueba corre en **worktrees fijos en `staging`**. Las sesiones siguieron válidas (misma `.env`).
- **`ERRORS_TOKEN` con `\r` final** (generado con `openssl` en Windows): la primera consulta a `/api/errors` dio 401. Se corrigió y se relanzó solo la API al final, sin agentes probando. No es hallazgo.
- **Memoria:** A midió ~4,1 GB libres y REG ~2 GB, por debajo de los 6 GB del §3. No hubo cortes de procesos ni lentitud (health 0,1–1,1 s).
- **Sesiones de la ronda 4 vivas en Redis** que, tras el reseteo, apuntan a los uid 10 y 11 nuevos. No se usaron. Ver §8.

## 3. Verificación de los 8 PU

| Estado | Cantidad | PU |
|---|---|---|
| ✅ | 8 | PU-302, PU-305, PU-306, PU-307, PU-308, PU-309, PU-310, PU-041 |
| ⚠️ | 0 | — |
| ❌ | 0 | — |
| Nota §8 (fila aparte) | ⚠️ | por PU-501 (baja) |

- Ningún estado cambia respecto de los borradores.
- **Decisiones aprobadas:** O-1.1 ✅ (era ⚠️ en la ronda 4), formato de fecha ✅, sesión vencida ✅. O-1.2, O-3, O-4 y nombre de los links: ✅ en la ronda 4, no tocados por los arreglos.

## 4. Hallazgos nuevos

**Por severidad:** bloqueante 0 · alta 0 · media 0 · **baja 2** · **total 2**.

| Tipo | Baja |
|---|---|
| mensaje | 1 (PU-501) |
| accesibilidad | 1 (PU-502) |

| PU | Borrador | Severidad | Título | Regresión de |
|---|---|---|---|---|
| PU-501 | B-01 (+ REG) | baja | "Destrabar ingreso": a veces no aparece "Destrabamos el ingreso de …" y el diálogo queda sin ningún aviso | no; arreglo incompleto de la nota §8 de la ronda 4 |
| PU-502 | CEL-01 | baja | En los diálogos, Tab se detiene en cada opción de un grupo en vez de una sola vez | no; existe desde `d6da13e` |

**Regresiones:** ninguna.

**Severidades:** QC sostiene las dos en baja (justificación en `hallazgos.md`, "Consolidación"). PU-501: la acción sale bien y la pantalla lo refleja (el aviso de bloqueo y la marca "Ingreso trabado" desaparecen); solo falta a veces la confirmación. No es el caso de PU-111 / PU-306 (media), donde un fallo quedaba invisible. PU-502: el teclado funciona entero, es una parada de más; los análogos PU-136 y PU-212 quedaron en baja.

**Observaciones (no son hallazgo):** "Ya está cargado: …" una vez sin reproducir; sesiones viejas en Redis tras el reseteo; B-02 sigue como límite conocido. Ver §8.

## 5. Cobertura F-XX (solo funciones tocadas)

✅ sin hallazgos abiertos · ⚠️ con hallazgos abiertos (todos bajos en esta ronda) · ❌ con bloqueante o un PU que sigue.

| F-XX | Estado | PU / nota |
|---|---|---|
| F-01 Ingreso con mail + código / sesión vencida | ✅ | PU-305 ✅; ingreso normal y "Salir" + otra persona ✅ |
| F-03 Buscador general | ✅ | PU-309 ✅ |
| F-10 Mi perfil | ✅ | REG: avisos, doble clic, irse con cambios |
| F-14 Historial | ✅ | Formato y hora de Argentina (B) |
| F-16 Alta de obra | ✅ | Recuperado y descartado tras sesión vencida (REG); año del N.º (B, REG) |
| F-17 Ficha de la obra | ✅ | Fechas sin corrimiento |
| F-18 Presupuestos | ✅ | PU-302 ✅ |
| F-19 Documentos | ✅ | Fecha del documento sin corrimiento, en AR y UTC |
| F-20 Editar la obra | ✅ | PU-305 ✅, O-1.1 ✅ |
| F-21 Cambiar estado | ⚠️ | PU-502 (baja) en el diálogo; el cambio de estado funciona (REG) |
| F-23 Fechas previstas | ✅ | 31/12 y 01/01 en alta y edición, AR y UTC |
| F-56 Alta y edición de proveedor | ✅ | PU-305 ✅ (edición con conflicto; alta recuperada, descartada y guardada) |
| F-58 Usuarios | ⚠️ | PU-307 ✅, PU-310 ✅; PU-501 (baja) y PU-502 (baja) |
| F-60 Tabla de permisos | ✅ | PU-306 ✅ |
| F-68 Plazos de la prioridad | ✅ | PU-308 ✅ |
| Sesión vencida / borradores | ✅ | PU-305 ✅ |
| Guardado y salida (O-1.1) | ✅ | Tabla de permisos completa + Editar obra y proveedor |
| Fechas: formato y hora | ✅ | PU-302 ✅, PU-310 ✅; B-02 límite conocido |
| Celular | ✅ | PU-041 ✅, PU-307 ✅ |
| Accesibilidad (teclado y foco) | ⚠️ | PU-502 (baja); foco de PU-306 y PU-307 ✅ |
| Health, cola, mails, scheduler | ✅ | Ver §6 |

## 6. Backend (rol 4, lo hizo el Lead, sin cortes)

| Qué | Resultado |
|---|---|
| `/api/health` | 200 con los 5 servicios en `true` (0,1–1,1 s) toda la ronda |
| `failed_jobs` | 0 |
| Canal `errors` | Sin `errors-2026-11-19.log` en el worktree; `GET /api/errors` (con token) → "No logs for date 2026-11-19". Ni 4xx ni 5xx |
| Workers | Sin errores; 17 mails de código, uno por pedido |
| Scheduler | 16:00 UTC `adjuntos:purgar-huerfanos` DONE (5 s) e `idempotencia:purgar` DONE (1 s) |
| Cortes | No se repitieron por diseño: PU-020, 202, 203 y 138 quedaron ✅ en la ronda 4, y el backend solo cambió `PresupuestoResource` y `app.zona_empresa` |

## 7. Lo que no se pudo probar y por qué

- **PU-305, segunda repetición con ingreso real** de los 4 casos (A): el tope de 6 ingresos alcanzó para 1 vez cada uno. Los 4 dieron lo esperado a la primera, y REG cubrió las otras pantallas con 4 ingresos más.
- **Más de un ingreso normal observado al detalle** (REG): los 4 ingresos se usaron en el normal y en las 3 vueltas de sesión vencida.
- **Borrador descartado con conflicto en Nueva obra y alta de proveedor** (A): no aplica, no tienen versión previa. El recuperado y el descartado los cubrió REG.
- **Año del N.º en el borde de fin de año** (31/12 de 21:00 a 24:00 de Argentina) (B): no se puede forzar la hora del servidor sin tocar el entorno. Se probó el tope que depende de ese año y se leyó `ArmaPresupuestos.php:67` (`Carbon::now(app.zona_empresa)`). Además, el back exige el año en el N.º ("2-551" → "Escribí solo el número, por ejemplo 2-131-2026."), así que no hay un año que se complete solo para observar.
- **"Cargado" de un presupuesto adicional** (B): no se forzó; sale del mismo `PresupuestoResource`.
- **Ingreso trabado real y PU-501 con un bloqueo real** (B, CEL, REG): para no quemar códigos, el bloqueo se simuló con `page.route`; el `DELETE` sí fue real. La carrera de PU-501 depende de que la ficha vuelva sin `bloqueo_ingreso`, que es lo que devuelve el back tras un destrabe real.
- **Lector de pantalla real y toque real en iOS Safari** (CEL): no hay en el entorno. Se usó `ariaSnapshot` y Chromium con viewport y UA de iPhone SE.
- **O-1.2, O-3, O-4 y nombre de los links:** no re-probados; ✅ en la ronda 4 y ningún arreglo los toca.
- **Cortes de Redis, MySQL y worker de mail:** no se repitieron (ver §6).

## 8. Notas menores (vistas y no reportadas como hallazgo)

- **"Ya está cargado: QA5R Proveedor Diego D9400. Abrí su ficha."** (REG) en vez de "Dimos de alta a …", 1 vez, tras un `POST /api/proveedores` 201. En 4 intentos más (`REG-ok-r5-prov-rapido-*.png`) salió siempre "Dimos de alta a …". Probablemente es el aviso de parecidos que llega después del alta. Si alguien lo vuelve a ver, va como hallazgo.
- **Sesiones viejas en Redis tras el reseteo** (REG): las de la ronda 4 de los uid 10 y 11 siguen vivas (TTL ~88 días) y, después de `migrate:fresh --seed`, entrarían como `qa-lead-r5reg/r5reg2`. Es del reseteo de la base de prueba, no de la app. **Para el entorno:** al resetear, borrar las claves de sesión del prefijo de la prueba.
- **409 de la Tabla de permisos** (A): "Alguien cambió esto mientras lo tenías abierto. Recargá y volvé a intentar." En las pantallas de edición dice "Actualizá y revisá.". Es claro y coherente; no es hallazgo (§1).
- **Primer "Editar usuario" en frío en Pixel 7** (CEL): al segundo, el título todavía decía "Editar usuario" con "Cargando el formulario…"; en las demás aperturas ya estaba "Editar usuario · Dora".
- **B-02:** el campo de fecha nativo sigue el idioma del navegador. Límite conocido.

## 9. Veredicto (§7, regla de la ronda 5)

**APROBADA. La Semana 1 queda aprobada.**

| Condición | Cumple | Por qué |
|---|---|---|
| 1. Ningún PU ❌, ni ⚠️ por un hallazgo bloqueante, alto o medio | **Sí** | Los 8 PU están ✅. La única fila ⚠️ es la nota del §8, y solo por PU-501 (baja), que cuenta como aprobado. |
| 2. Las decisiones aprobadas funcionan como están descritas | **Sí** | O-1.1 ✅ (completa en la Tabla de permisos), formato de fecha ✅, sesión vencida ✅. O-1.2, O-3, O-4 y nombre de los links ✅ en la ronda 4, sin cambios. |
| 3. Sin hallazgos nuevos bloqueantes, altos ni medios | **Sí** | Solo 2 bajos: PU-501 y PU-502. |

**No corresponde `re-verificacion.md`:** no hay nada bloqueante, alto ni medio.

**Pendientes menores** (pasan al backlog de la Semana 2; desarrollo los arregla sin otra ronda):
- **PU-501** (baja, mensaje): "Destrabar ingreso" a veces no muestra "Destrabamos el ingreso de …" (3 de 19 + 1 de REG). Causa probable: el `onSuccess` del `mutate` en `BloqueoIngreso.tsx`, que se desmonta antes.
- **PU-502** (baja, accesibilidad): Tab para en cada opción de los grupos Activo/Inactivo y Confirmada/En ejecución dentro de los diálogos (trampa de foco de `Dialog.tsx`).
- **Observación B-02:** campo de fecha nativo según el idioma del navegador (límite conocido; se reabre solo si Juani pide un selector propio).
- **Para mirar:** "Ya está cargado: …" tras un alta (1 vez, sin reproducir).
- **Para el entorno de QA:** limpiar las sesiones de Redis del prefijo de la prueba al resetear la base.

## 10. Firmas

| Rol | Firma |
|---|---|
| Verificador A | PU-305 ✅ con ingreso real en Editar obra y Editar proveedor (conflicto y control, sin pisar datos, sin borradores que reaparezcan), PU-306 ✅ en 20 combinaciones (500/422/409 × 4 caminos, vista y foco medidos), O-1.1 ✅ completa en la Tabla de permisos más control en obra y proveedor, "Salir" r5a → r5b. 0 hallazgos. 6 ingresos. · `borradores/a.md` |
| Verificador B | PU-302 ✅ (01:30 → 19/11 y 02:59 → 18/11, por API y pantalla, AR y UTC; restaurado), regresiones de fechas de calendario y año del N.º ✅, PU-308 ✅, PU-309 ✅ (5 personas), PU-310 ✅ (3 husos, 2 bordes), nota §8 ⚠️ → B-01 (PU-501, baja), formato de fecha ✅. 0 ingresos. · `borradores/b.md` |
| QA celular y accesibilidad | PU-307 ✅ (Pixel 7, iPhone SE y PC; 24 de 24 toques; un foco por tarjeta; PC sigue tabla) y PU-041 ✅ (21 pantallas y diálogos, sin scroll lateral). 1 hallazgo: CEL-01 → PU-502 (baja). 0 ingresos, 0 datos. · `borradores/cel.md` |
| Cazador de regresiones | Caché al ingresar (ingreso normal; sesión vencida en Nueva obra, `/proveedores/nuevo` y "+ Nuevo proveedor" con recuperado, descartado y guardado; listas frescas), Usuarios en PC, fechas del back en AR y UTC, Plazos, buscador, Mi perfil, permisos, estado y doble clic. 0 hallazgos nuevos (vio PU-501, no duplicado); 2 observaciones. 4 ingresos. · `borradores/reg.md` |
| QA Lead / QA backend (rol 4) | Levantó el entorno en `C:\` (Laragon), `migrate` y `migrate:fresh --seed` verificados con tinker, `config:clear`, 7 ingresos y reparto de sesiones; atendió el incidente de la otra sesión pasando a worktrees fijos en `staging` antes de lanzar los roles; backend sin cortes: health, cola, `errors`, workers y scheduler sanos. · `borradores/lead.md` |
| QC final | Sin navegador, código ni git: borradores, capturas (187), salidas del scratchpad y rondas anteriores. Revisé que cada estado y hallazgo tenga evidencia (todas las capturas citadas existen; `logs/` vacía, salidas en el scratchpad; falta la consola de `09-destrabar-rep.mjs`), que lo "esperado" del §1 no aparezca (B-02 queda límite conocido), dedupliqué B-01 con la nota de REG (→ PU-501), normalicé F-XX (B-01: F-60 → F-58), sostuve las dos severidades en baja (PU-501 vs PU-111/PU-306/PU-308; PU-502 vs PU-136/PU-212) y dejé como observaciones "Ya está cargado" y las sesiones viejas en Redis. Sin datos sensibles. **Resultado: ✅ 8 · ⚠️ 0 · ❌ 0 · 2 bajos · APROBADA.** · 2026-11-19 |
