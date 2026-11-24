# Borrador B · Avisos, recorrido y diálogos (PC) · Semana 2 · ronda 2 · 2026-11-20

- **Personas / sesiones:** Laura, Carla, Sofía, Diego (alta de la obra) por storageState de `scratchpad\s2r2\sesiones\`; Hernán y QA Lead S2 BK solo como destinatarios (mirados en la base). PC 1366×768, `es-AR`, `America/Argentina/Buenos_Aires`.
- **Ingresos reales gastados:** 0.
- **Datos creados (prefijo `QAS2R2-B`):** obra 1 "QAS2R2-B Cambio de válvulas planta norte" (9-822-2026, Diego); 22 pedidos `QAS2R2-B …` (ids 8, 13, 15–18, 23, 26–28, 32–35, 39–45 aprox.); 56 avisos de esos pedidos.
- **Cambios compartidos:** Laura · Mis avisos "Completan un pedido que pedí" tildada 14:21 y **restaurada** 14:24 (fila `preferencias_aviso` id 1 queda con `por_mail = 0`, igual al valor por defecto). Nada movido en la base.
- **Scripts:** `C:\Users\GREEN\AppData\Local\Temp\claude\4fe530b1c11d8e8d\f4efcdda-aa94-4434-aafd-2e4822d4d74b\scratchpad\s2r2\B\` (salidas en `out\`).
- Código: backend `9ca659a`, front `c816c89`. Rol A corría en paralelo (prefijo `QAS2R2-A`, también con Laura y Carla): las medidas de la campana se cruzaron con la base para descartar avisos ajenos.

## 1. Verificación de los 9 PU

| PU | título | estado | pantallas | evidencia | nota |
|---|---|---|---|---|---|
| PU-604 | La campana a veces tarda más de 15 s | ✅ | W-02 campana (Inicio) de Carla y de Laura | `capturas/PU-604-r2-1.png` · `out/01.txt` | 9 corridas, sin recargar: **4,0 · 8,8 · 8,3 · 2,1 · 7,5** s (Laura crea → Carla mira) y **7,1 · 7,6 · 1,7 · 6,1** s (Carla comenta → Laura mira). Máx 8,8 s, promedio 5,9 s. El contador se pide cada 10,16–10,93 s (26 intervalos). En la corrida 1 el contador saltó de 2 a 5 porque A creó 2 pedidos para Carla en el mismo segundo; el aviso de B (id 13) estaba entre ellos, la medida vale. |
| PU-605 | Cada aviso repite la obra | ✅ | W-02 panel (Carla, Laura) · `/avisos` en PC | `PU-605-r2-1.png` (panel Carla) · `-2` (panel Laura) · `-3`/`-4` (tabla) | Panel: "Laura te asignó QAS2R2-B 04 campana Carla con obra · QAS2R2-B Cambio de válvulas planta norte" / "hace 1 min" (sin obra repetida); sin obra: "… · Servicios generales" / "hace 1 min". Si el texto no trae la obra (comentado), el pie la pone una vez: "QAS2R2-B Cambio de válvulas planta norte · recién". La tabla de PC conserva las columnas "Leído · Aviso · Obra · Cuándo". 2 vueltas (Carla y Laura). |
| PU-606 | Mis avisos: "Vence mañana" con casilla | ✅ | W-19 Mi perfil · Mis avisos (Laura y Sofía) | `PU-606-r2-1.png` (Laura) · `-2` (Sofía) · `out/03-*.txt` | "Vence mañana una tarea mía \| siempre \| Solo en el sistema", sin casilla; API `solo_en_el_sistema: true`. Resto: asignación y reasignación tildadas y fijas, las demás destildadas. Laura tildó "Completan un pedido que pedí" → "Listo: "Completan un pedido que pedí" te llega también por mail." y llegó el mail "Carla completó tu pedido: QAS2R2-B R1/R2 recorrido con historia" (2 veces); restaurado → "… te llega solo dentro del sistema.". |
| PU-607 | Recorrido con el tipo como clave | ✅ | W-09 detalle · Historial del recorrido (pedidos 18 y 23) | `PU-607-r2-1.png` · `-2` · `out/04-1.txt`, `04-2.txt` | "Sistema avisó a Hernán (completado, a su jefe)", "Sistema avisó a Laura, Sofía (comentario nuevo)", "(tomado)", "(bloqueado)", "(completado)", "Sistema avisó a Carla (reabierto)", "Sistema avisó a QA Lead S2 BK (reasignado)". Ningún `_` ni clave cruda. 2 pedidos con historia completa (creado, tomado, comentado, bloqueado, retomado, completado, reabierto, reasignado). |
| PU-608 | Recorrido repite el nombre | ✅ | ídem | ídem | "20/11/2026 14:23 · Laura creó el pedido", "… · Carla lo tomó → En curso", "… · Sistema avisó a Carla (asignado)": "fecha · texto", sin "Laura Laura" ni "sistema Sistema". 2 vueltas. |
| PU-609 | "Contá…" queda después de escribir | ✅ | W-30 Bloquear · W-29 Completar · Reabrir | `PU-609-r2-1.png` (Bloquear) · `-2` (Completar) · `-3` (Reabrir) | Las 3 ventanas, 2 vueltas: confirmar vacío → "Contá qué lo traba." / "Contá cómo se resolvió." / "Contá qué falta."; al escribir una letra se va; al borrar todo no aparece solo, y al volver a confirmar vuelve. |
| PU-610 | "-1 días" con la entrega anterior | ✅ | W-08 Nuevo pedido · W-09 detalle · Mis tareas · Pedidos que pedí · Calendario | `PU-610-r2-form-1a/1b/3.png` · `PU-610-r2-detalle-1a/1b/3.png` · `out/05.txt` | 18→17/11 y 20→19/11: "Urgente · la entrega es 1 día antes del pedido." y en el detalle "18/11/2026 · 17/11/2026 · vencido (1 día antes del pedido)". 15→12/11: "Urgente · la entrega es 3 días antes del pedido." / "(3 días antes del pedido)". Ningún "-N días" en detalle ni listas. |
| PU-615 | Texto más largo que el máximo se corta sin avisar | ✅ | W-09 comentario · W-30 Motivo · W-29 Completar · Reabrir "¿Qué falta?" · W-31 Reasignar comentario | `PU-615-r2-1…5.png` · `out/07-1.txt`, `07-2.txt`, `08-1.txt`, `08-2.txt` | 2 vueltas: comentario 5001 → "El comentario puede tener hasta 5000 caracteres."; motivo 2001 → "El motivo puede tener hasta 2000 caracteres."; Completar, Reabrir y Reasignar 5001 → "El comentario puede tener hasta 5000 caracteres."; **0 envíos** en todos. Máximo exacto guardado entero (base: comentario 5000, motivo 2000, cierre 5000, reapertura 5000, reasignación 5000). 2001 en comentario se guarda (el máximo es 5000, `pedidos.md` §4.1). Vacíos y solo espacios: "Escribí el comentario." / "Contá…", 0 envíos; en Reasignar (opcional) solo espacios se manda sin comentario. Red lenta 2,5 s + doble clic: crear, comentar, bloquear, completar y reasignar → 1 envío, 1 registro, 1 aviso, 1 mail; el botón muestra "Enviando…" / "Bloqueando…". Ver **B-01** (aparte). |
| PU-616 | "Le avisamos a" mí mismo | ✅ | W-29 Completar (pedidos 26 y 27, Laura → Laura) | `PU-616-r2-1.png` · `-2` · `out/06.txt` | Botón "Completar", texto "Al confirmar, el pedido pasa a Completada y queda en el historial quién lo completó y cuándo." (sin "le avisamos a…"), resultado "Pedido completado.". Control (Carla completa un pedido de Laura, 2 veces): "Completar y avisar", "…le avisamos a Laura con este comentario…", "Pedido completado. Le avisamos a Laura.". Ver nota de RN-AVI-12 abajo. |

Consola y red: sin errores JS, sin warnings de React y sin 4xx/5xx en ninguna pantalla de las corridas (`problemas []` en todos los `out/*.txt`).

## 2. Regresión de avisos (tabla §6 de `avisos.md`, una pasada)

| Evento | Esperado | Obtenido | |
|---|---|---|---|
| Crear (a persona) | propietario, sistema + mail | Carla: "Laura te asignó QAS2R2-B R1 recorrido con historia · QAS2R2-B Cambio de válvulas planta norte"; mail "Te asignaron un pedido: …" **una vez** por pedido (14 pedidos a Carla, 14 mails, también con doble clic y red lenta) | ✅ |
| Tomar | solicitante + seguidores | Laura "Carla tomó tu pedido … → pasó a En curso", Sofía "… (que seguís) → En curso"; sin mail (no lo activaron) | ✅ |
| Comentar (propietario) | solicitante + seguidores | Laura "Carla comentó en tu pedido …: '…'", Sofía "Carla comentó en …" | ✅ |
| Bloquear | solicitante + seguidores | Laura "Carla bloqueó tu pedido …: '…'", Sofía "… (que seguís): '…'" | ✅ |
| Completar | solicitante, superior (solo sistema), seguidores | Laura `completado` (+ mail con la casilla tildada, ninguno después de restaurarla), Hernán `completado_gente` "Carla completó …, que pidió Laura: '…'" sin mail, Sofía `completado` sin mail | ✅ |
| Reabrir | propietario | Carla "Laura reabrió … (falta algo): '…'" | ✅ |
| Reasignar a persona | nuevo propietario, sistema + mail | QA Lead S2 BK "Laura te reasignó … · obra: '…'" + mail "Te reasignaron un pedido: …" una vez | ✅ |
| Actor | nunca recibe lo suyo | 0 avisos con `user_id = actor_id` en los 56 avisos de `QAS2R2-B` | ✅ |
| Un aviso por persona | sin duplicados | 0 `dedupe_key` repetidas; seguidor nunca recibió mail | ✅ |

`failed_jobs` = 0; `GET /api/errors` sin registros del día; `errors-2026-11-20.log` no existe.

## 3. Hallazgos nuevos

### B-01 · Con la red lenta, lo que se escribe mientras se manda un comentario se borra solo
- **Severidad:** baja
- **Tipo:** funcional
- **F-XX / pantalla:** F-30 · W-09 Detalle del pedido, "Escribir un comentario" · `/pedidos/{id}`
- **Persona:** Laura (Ingeniería de proyectos)
- **Pasos:** 1. Con la red lenta (2,5 s por pedido a la API), abrir un pedido propio. 2. Escribir un comentario y tocar "Comentar" (el botón pasa a "Enviando…"). 3. Mientras dice "Enviando…", escribir el comentario siguiente en la misma caja (la caja sigue habilitada). 4. Esperar a que termine el envío.
- **Esperado:** lo que la persona escribió después no se pierde (o la caja no deja escribir mientras manda). §0: "entiende siempre qué pasó".
- **Obtenido:** el primer comentario se guarda bien; unos 3 s después del 201, la caja queda vacía y se pierde el segundo texto ("QAS2R2-B debug segundo escrito durante el envío", 47 caracteres) sin ningún mensaje. Si se toca "Comentar" otra vez sale "Escribí el comentario.". Reproducido 4 veces (08b vueltas 1 y 2, 08 vueltas 1 y 2); en la base solo queda el primero.
- **Evidencia:** `capturas/B-01-1.png` · `capturas/B-01-2.png` · `logs/B-01.log` · `POST /api/pedidos/41/comentarios` → 201 a los 22,4 s; caja vacía a los 25,4 s
- **Causa probable (opcional):** `src/features/pedidos/components/detalle/Comentarios.tsx` — `onSuccess: () => { reiniciar(); setTexto(''); }` vacía la caja con lo que haya en ese momento, y el `textarea` no se deshabilita mientras `comentar.isPending`.
- **Regresión de:** no (el `setTexto('')` en `onSuccess` ya estaba; se ve ahora probando PU-615 con red lenta)

## 4. §6.1 del backlog (lo que me toca)
- Campana en ≤ 15 s sin recargar: **funciona** (máx 8,8 s en 9 corridas).
- Mail de asignación una sola vez, por la cola `mail`: **funciona** (también con doble clic y red lenta).
- Avisos a quien corresponde, el actor nunca, seguidores sin mail, "vence mañana" y superior nunca por mail: **funciona**.
- Recorrido con "sistema avisó a…" legible: **funciona**.

## 5. Observaciones (no son hallazgos)
- **Orden del recorrido cuando se actúa muy rápido:** en el pedido 18 (vuelta 1, el script hizo tomar → comentar → bloquear con 3 s entre cada uno) la línea "Sistema avisó a Laura, Sofía (tomado)" quedó después de "Carla comentó…", porque el aviso se crea 1–3 s después (cola `default`). Es su hora real; con ritmo de persona (vuelta 2, pedido 23) salió en orden.
- **PU-616 con jefe:** Laura → Laura completado le avisa a Hernán "Laura completó QAS2R2-B entrega antes 1a …, que pidió Laura: '…'" (`completado_gente`, solo sistema). Es lo que dicen RN-AVI-12 y DU-13 (superior del solicitante); solo el "que pidió Laura" suena repetido. Lo dejo para que el Lead decida si va.

## 6. No probado
- Celular (`/avisos` del celular para PU-605): es del rol C.
- Mails "tomado / bloqueado / comentado / reabierto" con la casilla tildada: solo se activó "completado", como pide el reparto.
- Reasignar al sector y recordatorio "vence mañana": fuera del alcance de esta ronda corta (sin cambios en ese código); los avisos al sector de A (`QAS2R2-A L2`, `D4`) se ven bien en la base.

— Rol B (Avisos, recorrido y diálogos, PC) · 2026-11-20 14:15–14:40 (Argentina) · 0 ingresos reales.
