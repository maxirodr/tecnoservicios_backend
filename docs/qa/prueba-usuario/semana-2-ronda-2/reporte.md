# Reporte · Prueba de usuario Semana 2 (Pedidos y avisos) · Ronda 2 (corta) · Tecnoservicios (NS-2026-025)

**Fechas:** 2026-11-20 y 2026-11-24 (corte y reanudación en el §2) · **Equipo:** QA NexoSmart (QA Lead + roles A, B y C; el rol 7, backend, lo hizo el Lead; QC final) · **Detalle:** [`verificacion.md`](verificacion.md) (los 16 PU de la ronda 1) y [`hallazgos.md`](hallazgos.md) (lo nuevo, PU-701…703) · borradores en `borradores/` (sin modificar) · capturas en `capturas/` (148) · logs en `logs/` (4)

## 1. Resumen ejecutivo

**Veredicto: APROBADA.** La condición que había dejado el QC (la corrida del scheduler del 24/11 a las 13:00 UTC) se cumplió: sin error, sin duplicados y sin mail (ver §7).

- **Los 16 hallazgos de la ronda 1 quedaron resueltos: ✅ 16 · ⚠️ 0 · ❌ 0.** Entre ellos están los dos medios que frenaron la ronda 1:
  - **PU-601:** el campo Obra muestra la obra de la ficha. Se vio 6 de 6 veces en PC, también con la red lenta, y 4 de 4 en el celular, y se guarda lo que se ve.
  - **PU-602:** Laura y Pablo ven "Esta sección es para quien tiene gente a cargo." con "Ir a Mis tareas", y Hernán y Dora siguen viendo la carga.
- **La regresión alrededor de los arreglos sale limpia en lo importante:**
  - las listas abren el pedido desde la fila o la tarjeta, con 1 parada de Tab por fila, y "Tomar" y "dejar de seguir" no abren el pedido;
  - el campo Obra anda en el alta normal, en el alta desde la obra y en Editar;
  - los diálogos con textos largos y vacíos avisan y no envían;
  - la campana llega en 8,8 s como máximo (9 corridas);
  - el mail de asignación sale una vez (14 de 14);
  - en el celular se crea un pedido y se completa con la foto real del remito (4 de 4).
- **3 hallazgos nuevos, los 3 bajos:**
  - **PU-701:** el calendario, con una fecha imposible escrita a mano en la dirección, se rompe o muestra títulos sin sentido. A-01 y A-02 se unieron en este hallazgo.
  - **PU-702:** con la red lenta, lo que se escribe mientras se envía un comentario se borra.
  - **PU-703:** con teclado, el foco se pierde después de "Tomar" o "dejar de seguir".
- QC no cambió ninguna severidad. La razón de cada una está en el §6 y en `hallazgos.md`.
- **Backend sano los dos días:** health 200, `failed_jobs` 0, canal `errors` vacío y ningún 5xx.

## 2. Entorno y commits

| Qué | Valor |
|---|---|
| Máquina | con Laragon (`C:\NexoSmart\Projects\…`) |
| Backend | `staging` @ **`9ca659a`**. Los arreglos de back (PU-606, PU-607 y PU-612) están en `fb55537`, y no hay migraciones nuevas. Worktree `tecnoservicios_backend_qa_s2r2`, rama `qa/prueba-usuario-semana-2-ronda-2` (desde `origin/staging`). `.env` copiado del principal, `composer install` + `dump-autoload`, `config:clear`. |
| Front | `staging` @ **`c816c89`** (arreglos de PU-601…PU-616), worktree `tecnoservicios_frontend_qa_s2r2`, `npm ci`. |
| Servicios | MySQL 8.4.3 y Redis 5.0.14.1 de Laragon. |
| Base | `tecnoservicios_prueba_usuario`, verificada con tinker antes de `migrate:fresh --seed` (20/11; no se volvió a resetear). Antes de ingresar se borraron en Redis 12 claves de sesión viejas de la prueba. |
| Variables | las del §3 en cada proceso. El token de `/api/errors` tiene 64 caracteres, sin `\r`, y está fuera del repo. |
| Procesos | `serve --no-reload` en :8340 · workers `default` y `mail` iguales a `config/supervisor.php` · `schedule:work` · front `npm run dev -- --port 5316 --strictPort`, sin `VITE_MODULOS_ENTREGADOS`. |
| Prompt | §0 y §2 actualizados para la ronda 2 (commit `a05a989`). |
| **Corte del 20/11** | Juani pidió parar a las **~14:55** (Argentina). A y B habían terminado. C quedó a mitad: las corridas del celular de PU-603 y PU-605 estaban completas, y PU-601 tenía 3 de 4. Se pararon todos los procesos y quedó `estado-parcial.md` (commit `6750fa5`). |
| **Reanudación del 24/11** | A las **09:04** (Argentina) MySQL y Redis estaban apagados. MySQL lo levantó Juani desde Laragon y Redis el Lead. La base conservaba los datos de la ronda (13 usuarios, 71 pedidos), pero **no las sesiones**: `/api/me` daba 401 con las 4 sesiones guardadas, con "Tu sesión venció…", que es lo correcto. Se volvió a ingresar con Diego, Carla, Laura y Hernán. `staging` no se había movido (`git fetch`): mismos commits y mismos worktrees, y los procesos se relanzaron con las mismas variables. C terminó el 24/11 (PU-601 en el iPhone SE, teclado en PC y regresión). |
| Ingresos reales | **11** (7 el 20/11 y 4 el 24/11, todos del Lead), sin 429 ni códigos quemados. A, B y C no usaron ninguno. |
| Datos | Cada rol creó sus datos con un prefijo propio (`QAS2R2-A`, `-B`, `-C`). Se restauró lo compartido: Mis avisos de Laura al valor por defecto (B), seguimientos de Laura y Hernán (A, C) y la obra de los pedidos 19–21 (A). Nadie movió fechas en la base. Al cierre de QC (24/11, 12:25 UTC) la base tiene 81 pedidos y 163 avisos. |

## 3. Personas probadas

| Persona | Sector | Roles que la usaron |
|---|---|---|
| Diego | Gerencia de Administración | A, B (alta de obra), C |
| Dora | Dirección | A |
| Hernán | Jefatura de Proyectos (con gente a cargo) | A, C (B solo como destinatario) |
| Laura | Ingeniería de proyectos | A, B, C |
| Sofía | Ingeniería de proyectos | B |
| Pablo | Ingeniería de proyectos | A |
| Carla | Compras | A, B, C |
| Usuarios propios de QA | `QAS2R2-A Inactivo` (inactivo con pedidos abiertos), `qa-lead-s2a/b/reg/reg2/bk` | A, B (destinatario de reasignación), Lead |

Dispositivos: PC 1366×768 (`es-AR`, `America/Argentina/Buenos_Aires`), Pixel 7 (412 px) e iPhone SE forzado a 375×667.

## 4. Conteo de los 16 PU de la ronda 1

| Estado | Cantidad | PU |
|---|---|---|
| ✅ | **16** | PU-601 … PU-616 |
| ⚠️ | 0 | — |
| ❌ | 0 | — |

Hay detalle por PU (pantallas, rol, evidencia y nota) en `verificacion.md`.

## 5. Cobertura F-XX de lo que tocaron los arreglos

✅ = funciona · ⚠️ = funciona con hallazgos (entre paréntesis, la severidad más alta) · ❌ = no funciona. Las F-XX que no aparecen en la tabla quedaron **✅ en la ronda 1 y no las tocaron los arreglos**: F-03, F-14, F-21, F-25, F-27, F-58, F-59, F-61, F-67 y F-68.

| F-XX | Estado | Roles | PU de la ronda 1 que se cierran | Hallazgos nuevos |
|---|---|---|---|---|
| F-02 Barra de la Semana 2 | ✅ | C | — (regresión del celular) | — |
| F-04 Mis tareas | ⚠️ (baja) | A, C | PU-603 | PU-703 |
| F-05 Pedidos que pedí | ✅ | A, C | PU-603 | — |
| F-06 Tareas de mi gente | ✅ | A, C | PU-603 | — |
| F-07 Tareas que sigo | ⚠️ (baja) | A, C | PU-603 | PU-703 |
| F-08 Avisos en la campana | ✅ | B | PU-604, PU-605 | — |
| F-09 Mails | ✅ | B | — (regresión: asignación 1 vez, seguidor sin mail) | — |
| F-10 Mis avisos en Mi perfil | ✅ | B | PU-606 | — |
| F-11 Calendario | ⚠️ (baja) | A | PU-612, PU-613 | PU-701 |
| F-12 Carga por persona | ✅ | A | **PU-602** | — |
| F-13 Celular | ✅ | C | **PU-601**, PU-603, PU-605 | — |
| F-15 Pedidos abiertos y fila Servicios generales | ✅ | A | PU-614 | — |
| F-17 Pestaña Pedidos de la obra | ✅ | A, C | **PU-601** | — |
| F-24 Crear | ✅ | A, B, C | **PU-601**, PU-611 | — |
| F-26 Prioridad | ✅ | B | PU-610 | — |
| F-28 Tomar | ✅ | A, C | — ("Tomar" desde la lista no abre el pedido) | (el foco con teclado va en PU-703, F-04) |
| F-29 Bloquear / retomar | ✅ | B, C | PU-609, PU-615 | — |
| F-30 Comentar | ⚠️ (baja) | B, C | PU-615 | PU-702 |
| F-31 Completar con comprobante | ✅ | B, C | PU-609, PU-616 | — |
| F-32 Reasignar | ✅ | B | PU-615 (comentario opcional) | — |
| F-33 Editar | ✅ | A | — (regresión del campo Obra) | — |
| F-34 Recorrido | ✅ | B | PU-607, PU-608 | — |
| F-35 Todos los pedidos y Excel | ✅ | A | PU-603, PU-612 | — |
| F-36 Sin obra (Servicios generales) | ✅ | A | PU-603 | — |
| F-37 Recordatorio "vence mañana" | ✅ en la ronda 1, no tocado | Lead | — | — (corrida del 24/11: §7) |
| F-38 Propietario y seguidores | ✅ | A, C | — ("dejar de seguir" no abre el pedido) | (el foco con teclado va en PU-703, F-07) |
| F-64 Lista de avisos | ✅ | B, C | PU-605 | — |
| F-65 Reabrir | ✅ | B | PU-609, PU-615 | — |

**Conteo de la tabla:** 28 F-XX · ✅ 24 (una de ellas, F-37, porque quedó así en la ronda 1) · ⚠️ 4, **todas solo por bajos**: F-04, F-07, F-11 y F-30 · ❌ 0. En la ronda 1 había 4 ⚠️ por un medio (F-12, F-13, F-17 y F-24): las 4 pasan a ✅.

## 6. Hallazgos nuevos

| Severidad | Cantidad | Hallazgos |
|---|---|---|
| Bloqueante | 0 | — |
| Alta | 0 | — |
| Media | 0 | — |
| Baja | **3** | PU-701, PU-702, PU-703 |
| **Total** | **3** | PU-701 aparece con el arreglo de PU-613 (`c816c89`); no rompe nada que anduviera |

| Tipo | Cantidad | Hallazgos |
|---|---|---|
| funcional | 2 | PU-701, PU-702 |
| accesibilidad | 1 | PU-703 |
| permisos · validación · mensaje · loader · error-servidor · cola-mail · aviso · scheduler · celular · consistencia-con-documento | 0 | — |

**Por rol de origen:** A 2 (A-01 + A-02, unidos en PU-701) · B 1 · C 1.

**Severidades: QC sostuvo las tres.**
- **PU-701, baja.** Solo pasa con una fecha inventada a mano en la dirección: la app nunca arma un link así. Es como PU-024 de la Semana 1. Atrás sale de ahí.
- **PU-702, baja, en el borde.** Lo que se pierde es un texto que todavía no se envió, no un dato guardado. Para que pase hacen falta la red lenta y un segundo comentario escrito durante el envío. Es como PU-114 y PU-115 de la Semana 1. PU-106 fue media porque dejaba a la persona trabada en un camino común.
- **PU-703, baja.** Es el patrón de PU-136, PU-212 y PU-502, todos bajos, y más leve que ellos, porque no sale de un diálogo.

## 7. Backend, cola y scheduler (rol 7, lo hizo el Lead)

| Qué | Resultado |
|---|---|
| `/api/health` | 200 con los 5 servicios en `true` el 20/11 y el 24/11. |
| `failed_jobs` | 0 (QC lo confirmó en la base el 24/11 a las 12:25 UTC). |
| Canal `errors` | No se creó ningún `errors-*.log`. `GET /api/errors` → "No logs for date 2026-11-20" y "… 2026-11-24". Ni 4xx ni 5xx. |
| Workers y API | Sin FAIL, Exception ni ERROR en las salidas de los dos días. B vio el mail de asignación 1 vez por pedido por la cola `mail` (14 de 14). |
| Avisos (QC, en la base) | 0 avisos con `user_id = actor_id` en 163. 0 "vence mañana" duplicados por pedido y persona. |
| `avisos:recordatorios` el 20/11 | No se llegó a ver: el scheduler arrancó a las 14:13 (Argentina), la corrida siguiente era a las 15:00 y Juani cortó a las 14:55. |

- Scheduler 24/11 13:00 UTC (10:00 Argentina): `adjuntos:purgar-huerfanos` DONE 2 s, `idempotencia:purgar` DONE 1 s, **`avisos:recordatorios` DONE 2 s**. Creó exactamente los 4 "vence mañana" previstos (pedidos 1 y 5 → Sofía, 10 → Carla, 50 → Laura, todos 13:00:09 UTC; el 14 no, propietario inactivo), **0 duplicados**, **ningún mail** en `laravel-2026-11-24.log` a esa hora, `failed_jobs` 0. ✅ Condición cumplida.

> Para el Lead, de QC: a las 12:25 UTC del 24/11 la base no tenía ningún aviso `vence_manana`. Hay 5 pedidos abiertos con entrega el 25/11:
> - el 1 y el 5, de Sofía;
> - el 10, de Carla;
> - el 50, de Laura;
> - el 14, del usuario 13, que está inactivo.
>
> La corrida de las 13:00 UTC (10:00 en Argentina, después de las 8:00) tendría que crear **4 avisos**: 1, 5, 10 y 50. Para el 14 no corresponde ninguno, porque los inactivos no reciben avisos. Ninguno de los 4 tiene que salir por mail.

## 8. Lo que no se pudo probar y por qué

| Qué | Por qué | Rol |
|---|---|---|
| `avisos:recordatorios` en el scheduler el 20/11 | el corte llegó antes de la corrida de las 15:00; la del 24/11 la anota el Lead (§7) | Lead |
| La fila Servicios generales con obras En ejecución | no había ninguna y no se cambió el estado de obras; el caso con obra quedó ✅ en la ronda 1 (`semana-2/capturas/F-15-listado-en-ejecucion-servicios-generales-pc.png`) | A |
| PU-612 con el navegador en UTC | no lo pide la ronda | A |
| Clics de la fila en el 2.º grupo de Tareas de mi gente en adelante | se probó solo la primera tabla, y las demás usan el mismo componente | A |
| Mails de "tomado", "bloqueado", "comentado" y "reabierto" con la casilla tildada | se activó solo "completado", como pide el reparto | B |
| Reasignar al sector, y el recordatorio "vence mañana" de punta a punta | fuera del alcance de la ronda corta, sin cambios en ese código; los avisos al sector que generó A se ven bien en la base | B |
| "Tomar" y "dejar de seguir" con un lector de pantalla real (NVDA, VoiceOver) | no hay en el entorno; se midieron `document.activeElement` y el anillo de foco | C |
| Avisos con obra **y** "cuándo" de otros tipos en el celular | se usaron avisos de asignación; B cubrió los otros tipos en el panel de PC | C |
| Foto HEIC del iPhone y cámara (`capture`) | Chromium headless no tiene cámara, y el `input` no tiene `capture`; se probó un JPG real de 1,8 MB | C |
| PU-601 en el celular con la red lenta | lo cubrió A en PC (2,5 s) | C |
| Teclado en Todos los pedidos y Servicios generales | lo cubrió A en PC | C |
| PU-601 con la obra fuera de las primeras 20 | la base tiene 4 obras | A, C |

## 9. Notas menores (vistas y no reportadas como hallazgo)

- **"Laura completó …, que pidió Laura"** (B): es el aviso al jefe de un pedido que Laura se pidió a sí misma. Es correcto según RN-AVI-12, aunque suena repetido. Desarrollo puede decir "que se pidió a sí misma" si quiere.
- **Orden del recorrido cuando se actúa muy rápido** (B): el aviso del sistema se crea en la cola 1–3 s más tarde y se muestra con su hora real. A ritmo de persona sale en orden.
- **El `errorElement` del front dice "Revisá tu conexión…" ante cualquier error de render** (QC, a partir de PU-701): no solo cuando falla la red. Se arregla junto con PU-701.

## 10. Veredicto (§7)

**APROBADA.** La corrida del scheduler de las 13:00 UTC del 24/11 salió bien (§7): la condición 2 se cumple entera.

| Condición | Cumple | Por qué |
|---|---|---|
| 1. Ninguna función ❌, ni ⚠️ por un hallazgo bloqueante, alto o medio | **Sí** | 0 ❌. Hay 4 ⚠️ (F-04, F-07, F-11, F-30), **solo por bajos**. Las 4 que estaban ⚠️ por un medio en la ronda 1 (F-12, F-13, F-17, F-24) pasan a ✅. |
| 2. La §6.1 funciona entera, incluidos el mail de asignación, la campana sola y el "vence mañana" | **Sí** | Lo que tocaron los arreglos se volvió a ver y anda (§10.1). El mail de asignación sale 1 vez, y la campana llega en 8,8 s como máximo, sin recargar. El "vence mañana" quedó ✅ en la ronda 1 y los arreglos no tocaron ese código, solo el texto de Mis avisos (PU-606). La corrida del 24/11 queda en el §7. |
| 3. Sin hallazgos bloqueantes, altos ni medios | **Sí** | Los 3 nuevos son bajos. |

### 10.1 §6.1 del backlog, punto por punto

| Lo que hacen en la minuta hoy | Historias | Resultado en esta ronda | Quién lo probó |
|---|---|---|---|
| Cargar un pedido con todas sus columnas (obra o "Servicios generales", …) | HU-026/027/028/043 | **Se volvió a ver: funciona.** Desde la obra, el campo Obra queda puesto (PU-601: PC 6/6, celular 4/4). En el alta normal y con "Sin obra" también anda, con la red lenta incluida. "Pedido creado…" sale una vez (PU-611). | A, C |
| Mirar la planilla de la obra y la completa, filtrar | HU-042, HU-054 | **Se volvió a ver: funciona.** Filtros, orden, fila entera y Atrás con filtros andan, y el Excel sale con "(inactivo)" (PU-612). | A |
| Cambiar el estado: En curso, Bloqueada con motivo, Completada | HU-032/033/034 | **Se volvió a ver: funciona.** Tomar sin abrir el pedido (PU-603); Bloquear y Completar avisan con el texto (PU-609, PU-615) y completan con la foto en el celular. Bajo: PU-703 (foco con teclado). | A, B, C |
| La ida y vuelta en Notas ("falta tal cosa") | HU-029 | **Se volvió a ver: funciona.** Comentar y Reabrir con los textos largos y vacíos (PU-609, PU-615). Bajo: PU-702 (red lenta). | B, C |
| Cambiar el propietario a mano | HU-035 | **A persona se volvió a ver: funciona.** El aviso y el mail salen una vez, y el comentario largo avisa. Al sector: ✅ en la ronda 1, no tocado. | B |
| Corregir una fecha o un link | HU-031 | **Para la obra, en Editar, se volvió a ver: funciona.** El resto quedó ✅ en la ronda 1 y no se tocó. | A |
| Enterarse sin que lo arroben: asignación (también por mail), tomado, completado, "vence mañana" | HU-045/046/047/048 | **Se volvió a ver: funciona.** La campana llega sola en 9 de 9 (máx. 8,8 s, PU-604). La tabla §6 sale 9/9 en una pasada, el mail de asignación 1 vez y el seguidor no recibe mail. "Vence mañana": ✅ en la ronda 1; la corrida del 24/11 va en el §7. | B, Lead |
| Ver lo mío en una sola lista | HU-038, HU-039 | **Se volvió a ver: funciona.** Mis tareas, Pedidos que pedí y Tareas que sigo abren el pedido desde la fila o la tarjeta (PU-603). | A, C |
| Desde el celular | HU-051, HU-052 | **Se volvió a ver: funciona.** La barra, W-43 con "Se le avisó a…", las tarjetas tocables enteras, los avisos (PU-605) y completar con una foto real de 1,8 MB, 4 de 4 en Pixel 7 e iPhone SE. | C |
| Que el entorno mande los mails, corra el recordatorio y muestre Pedidos en la barra | HU-060 | **Los mails y la barra se volvieron a ver: funcionan.** El recordatorio quedó ✅ en la ronda 1 (scheduler a las 15:00 UTC del 20/11 de esa ronda); la corrida del 24/11 va en el §7. | B, C, Lead |

**Pendientes menores** (bajos: desarrollo los arregla sin otra ronda):
- **PU-701:** validar la fecha de la dirección del calendario (que el día exista y el año esté en un rango) y, si no sirve, usar la de hoy. De paso, que el `errorElement` no culpe a la conexión cuando el error no es de la red.
- **PU-702:** no vaciar la caja del comentario con lo que se escribió después del envío, o no dejar escribir mientras se envía.
- **PU-703:** después de "Tomar" o "dejar de seguir", llevar el foco a la fila o a la siguiente y anunciar el resultado.
- **De la ronda 1 no queda nada:** los 14 bajos (PU-603…PU-616) quedaron ✅, incluido PU-607, que estaba "en el borde".

## 11. Firmas

| Rol | Firma |
|---|---|
| 0 · QA Lead | Entorno en worktrees de `staging` (`9ca659a` / `c816c89`), reseteo único de la base (20/11), 11 ingresos reales, corte del 20/11 y reanudación del 24/11 con las mismas variables, rol 7 (backend, cola y scheduler; falta la línea de las 13:00 UTC del 24/11) · `borradores/lead.md`, `estado-parcial.md` |
| A · Listas, obra y calendario (PC) | 2026-11-20 · 0 ingresos reales · 7 de 7 PU ✅ (601, 602, 603 PC, 611, 612, 613, 614) · 2 hallazgos bajos (A-01, A-02 → PU-701) · `borradores/a.md` |
| B · Avisos, recorrido y diálogos (PC) | 2026-11-20 14:15–14:40 AR · 0 ingresos reales · 9 de 9 PU ✅ (604…610, 615, 616) · regresión de avisos 9/9 · 1 hallazgo bajo (B-01 → PU-702) · `borradores/b.md` |
| C · Celular y teclado | 2026-11-20 y 2026-11-24, retomado tras el corte · 0 ingresos reales · PU-601, PU-603 y PU-605 en el celular ✅, más el teclado en PC · regresión del celular 4 de 4 · 1 hallazgo bajo (C-01 → PU-703) · `borradores/c.md` |
| QC final | Evidencia revisada con un script: 148 capturas, 4 logs y las salidas del scratchpad citadas. Lo "esperado" del §1 y de `dudas.md` quedó excluido. A-01 y A-02 se unieron en PU-701 (misma causa, misma pantalla). Las 3 severidades se sostienen en baja, con su análogo de la Semana 1. Consultas de solo lectura a la base: `obra_id` de PU-601, comentarios de PU-702, avisos propios y duplicados, `failed_jobs`, pedidos que vencen el 25/11. **Veredicto: APROBADA, con la condición de la corrida del scheduler del 24/11 (§7).** · 2026-11-24 |
