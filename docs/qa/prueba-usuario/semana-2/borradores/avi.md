# Borrador AVI · Rol 2 · Avisos y mail · Semana 2 · ronda 1

- **Fecha:** 2026-11-20, de 11:10 a 11:45 (hora de Argentina; 14:10–14:45 UTC). Worktrees `staging`: back `7e007dd`, front `d8f2433`. La API respondió bien todo el rato: health 200, `errors-2026-11-20.log` no existe (sin errores), `failed_jobs` = 0.
- **Personas y sesiones:**
  - Laura, Carla, Sofía, Hernán, Diego, Pablo y Dora: provocar eventos por API (con su `storageState`), como lo haría el front.
  - En el navegador: Carla (campana y "vence mañana"), Laura (Mis avisos y tiempos de la campana), Sofía (Mis avisos y W-45) y Hernán (W-45, 2.ª vuelta).
  - No cerré ni borré ninguna sesión.
- **Ingresos reales gastados:** **0**.
- **Datos creados (prefijo `QAS2-AVI`):**
  - Usuarios (los dio de alta Diego):
    - id 13 "QAS2-AVI Compras Activa" (`qa-avi-compras@…`, Compras), activo;
    - id 14 "QAS2-AVI Compras Inactiva" (`qa-avi-inactiva@…`, Compras), **desactivado**.
    - Así Compras tiene 3 personas activas (Carla, qa-lead-s2bk y la 13) y una inactiva.
  - Pedidos:
    - 1, 2, 4: campana;
    - 5–12 (v1) y 22–29 (v2): tabla §6;
    - 14, 15 (v1) y 38, 39 (v2): reasignar y sector;
    - 30–37: recordatorio;
    - 42, 44, 55: tiempos de la campana.
    - Todos son "Servicios generales": no creé obras, eso es de otro rol.
- **Cosas compartidas que toqué y cómo quedaron:**
  - **Mis avisos** de Laura y Sofía: activé "Completan un pedido que pedí" y **lo volví a destildar** (`preferencias_aviso` queda con `por_mail = 0`, que es el valor por defecto).
  - **"Marcar todo como leído"** de Sofía (14:2x UTC) y de Hernán (14:39 UTC): sus avisos quedaron todos leídos.
  - No moví ninguna fecha en la base.
- **Scripts:** `C:\Users\GREEN\AppData\Local\Temp\claude\4fe530b1c11d8e8d\f4efcdda-aa94-4434-aafd-2e4822d4d74b\scratchpad\s2\AVI\`:
  - `lib.mjs`;
  - `01-usuarios`, `02-campana`, `03-misavisos` (con `restaurar`);
  - `04-eventos <vuelta>`, `05-recordatorio-datos`, `06-reasignar <vuelta>`, `07-lista`;
  - `08-recordatorio-horas.php` (tinker);
  - `09-vence-campana`, `10-campana-tiempos`, `11-mails-log`.
  - Salidas: `out-02.txt`, `out-04-v1/v2.txt`, `out-06-v1/v2.txt`, `out-10.txt`, `run-*.log`.

## Tabla F-XX

| F-XX | Estado | Pantallas | Evidencia | Nota |
|---|---|---|---|---|
| F-08 Avisos en la campana | ⚠️ | W-02 campana y panel (Carla, Laura), W-09 al tocar | `F-08-campana-pc.png`, `F-08-aviso-abre-pedido-pc.png`, `F-08-campana-sola-pc.png` · `out-02.txt`, `out-10.txt` | Se actualiza **sola, sin recargar**: el contador se consulta cada ~15,2 s. Medí 9 veces, entre que pasa la acción y el cambio de la campana: 8,3 · 15,3 · 13,0 · 5,2 · 5,5 · 10,7 · 2,6 · 8,0 · **16,4 s**. **2 de 9 pasan los 15 s** (**AVI-03**). El aviso tarda 0,7–3,4 s en llegar a la base (cola `default`). Al tocar un aviso se abre `/pedidos/1` y la campana baja de "Avisos, 4 sin leer" a "3 sin leer" (`leido_at` puesto). El globito muestra "9+" con 31 no leídos. El panel trae los 5 más recientes con ● y la nota "Por mail llega solo la asignación; el resto, acá adentro…". Repite la obra en cada aviso (**AVI-02**). Consola y red limpias. |
| F-09 Mails | ✅ | `laravel-2026-11-20.log`, `proc\wmail.log` | `logs/AVI-mails.log` (56 cabeceras) | La asignación sale **una vez** por evento y por la cola `mail`: 72 `AvisoPedidoMail … DONE` en `wmail.log` y 0 en `wdef.log`. Sale a los ~1 s del aviso. Desde `noreply@example.com`, con reply-to `administracion@example.com`, en español. **Asunto:** "Te asignaron un pedido: …". **Cuerpo:** texto, Tarea, Obra, Entrega (dd/mm/aaaa), Prioridad, Lo pidió, el botón **"Ver el pedido" → `http://localhost:5316/pedidos/{id}`** y el pie "Este aviso es automático. Respondé desde el sistema, en el pedido. Podés elegir qué avisos te llegan por mail en Mi perfil.". **Reasignación:** "Te reasignaron un pedido: …" a la persona y "Hay un pedido para Compras: …" a cada persona activa del sector. **Doble POST con la misma `Idempotency-Key`:** 1 pedido, 1 aviso, 1 mail. **Nunca por mail** (ni una línea en el log): seguidor (Sofía, aun con "completado" tildado), superior (`completado_gente`), vence mañana, tomado/bloqueado/comentado/reabierto sin activar. No hubo duplicados: los 2 "Carla completó tu pedido: … A" son dos cierres distintos (completar → reabrir → completar). |
| F-10 Mis avisos (Mi perfil) | ⚠️ | W-19 `/perfil` (Laura, Sofía) | `F-10-mis-avisos-laura-pc.png`, `F-10-mis-avisos-sofia-pc.png` | 8 filas, "siempre" en el sistema, asignación y reasignación tildadas y fijas. Tildar "Completan un pedido que pedí" guarda al tocar (`PUT` 200) y muestra "Listo: "Completan un pedido que pedí" te llega también por mail."; el mail llegó en el siguiente cierre. **API:** destildar `asignado` → 422 "La asignación siempre llega por mail."; activar `vence_manana` o `completado_gente` → 422 "Este aviso llega solo dentro del sistema."; un tipo inexistente → 422 "Ese aviso no existe.". La fila "Vence mañana una tarea mía" sigue con una casilla (deshabilitada y vacía) (**AVI-01**). |
| F-37 Recordatorio "vence mañana" | ✅ | W-45 Tipo = Vence mañana (Carla), campana | `F-37-vence-manana-lista-pc.png` (+`-v2`), `F-37-vence-manana-campana-pc.png` | `php artisan avisos:recordatorios` a las 11:24 de Argentina → "Recordatorios creados: 6." Detalle en la tabla de abajo (§6, fila Recordatorio). Corrido 2 veces más → "Recordatorios creados: 0." y la base sigue con 1 por persona y pedido. Ninguna línea de mail. Sofía (seguidora de R1) y Laura (quien lo pidió) no lo reciben. Bordes de hora y de día: ver "Recordatorio: 8:00 y lunes". |
| F-64 Lista de avisos | ✅ | W-45 `/avisos` (Sofía; 2.ª vuelta Hernán) | `F-64-avisos-pc.png`, `F-64-avisos-todo-leido-pc.png` (+`-v2`) | Columnas "Leído · Aviso · Obra · Cuándo", la más nueva arriba, de a 25 ("Página 1 de 2 · 31 avisos"). Pestañas "Todos · No leídos (31) · Leídos". **Tipo:** Todos, Asignado, Reasignado, Tomado, Bloqueado, Comentario nuevo, Completado, Completado (de mi gente), Reabierto, Vence mañana. "No leídos" + "Bloqueado" filtran bien, con la URL `?leidos=no&tipo=bloqueado`. "Leídos" + "Bloqueado" → "No hay avisos con este filtro". Al tocar uno se abre el pedido y la base pasa de 31 a 30 no leídos. "Marcar todo como leído" → 0, la campana queda "Avisos" y la pestaña "No leídos (0)". Consola y red limpias. |

## Tabla §6 de `avisos.md`, fila por fila (2 vueltas: v1 y v2, mismo resultado)

| Fila §6 | Esperado | Obtenido | |
|---|---|---|---|
| `PedidoAsignado` (crear) | Al propietario, en el sistema y por mail; el actor no. | Laura → Carla, con seguidores Sofía, Hernán y la 14: **solo Carla** recibe "Laura te asignó QAS2-AVI v1 A andamio bombas · Servicios generales" + 1 mail "Te asignaron un pedido: …". Laura, los seguidores y la inactiva no reciben nada. Carla se lo carga a sí misma → ningún aviso ni mail. Laura → Hernán: solo Hernán. | ✅ |
| `PedidoReasignado` a persona | Al nuevo propietario, sistema + mail, con el comentario; ni al anterior ni a quien lo pidió. | Carla → Diego: **solo Diego** recibe "Carla te reasignó QAS2-AVI v1 G discos de corte · Servicios generales: 'Diego tiene la cuenta con el proveedor de discos.'" + mail "Te reasignaron un pedido: …". El estado sigue En curso (DU-P04). | ✅ |
| `PedidoReasignado` al sector | A cada persona activa del sector, menos el actor y los inactivos; sistema + mail. | Diego → Compras: Carla, qa-lead-s2bk y la 13 reciben "Diego dejó en Compras (tu sector) QAS2-AVI v1 G discos de corte: lo toma el primero que pueda" + 3 mails "Hay un pedido para Compras: …". **La 14 (inactiva), no.** Vuelve a No iniciada. Con Laura (quien lo pidió) dejándolo en el sector, igual, y Carla tampoco lo recibe cuando es ella la que lo deja (R2). | ✅ |
| `PedidoTomado` → quien lo pidió | "Carla tomó tu pedido X → pasó a En curso". | Igual, a Laura. También cuando Carla lo toma desde el sector. | ✅ |
| `PedidoTomado` → seguidores | "Carla tomó X (que seguís) → En curso". | Igual, a Sofía, a Hernán y a Pablo (que lo siguió después). La inactiva, no. | ✅ |
| `PedidoBloqueado` | A quien lo pidió (tu pedido) y a los seguidores ("(que seguís)"), con el motivo. | "Carla bloqueó tu pedido QAS2-AVI v1 A andamio bombas: 'Falta el talle de botines de 3 personas de la cuadrilla.'" a Laura; a Sofía y Hernán "…bloqueó … (que seguís): '…'". Al bloquear desde el sector sin tomar (H): solo Laura. | ✅ |
| `PedidoComentado` (comenta quien lo pidió) | Al propietario (o al sector) y a los seguidores. | Laura comenta → Carla, Sofía y Hernán reciben "Laura comentó en X: '30 bolsas. Dejé el cómputo actualizado en el Drive.'"; Laura no. **Con el pedido en el sector:** Carla, qa-lead-s2bk y la 13, más los seguidores (DU-P23). | ✅ |
| `PedidoComentado` (comenta el propietario) | A quien lo pidió ("en tu pedido") y a los seguidores. | Carla comenta → Laura recibe "Carla comentó en tu pedido X: 'Mandame la medición de la semana 42.'"; Sofía y Hernán reciben "…comentó en X…". | ✅ |
| `PedidoComentado` (comenta un tercero) | A quien lo pidió, al propietario y a los seguidores, nunca al autor. | Sofía (seguidora) comenta → Laura, Carla y Hernán; Sofía no. Dora (Dirección, sin seguir) → Laura, Carla, Sofía y Hernán. Un comentario de 600 caracteres se ve recortado a 200 con "…". | ✅ |
| `PedidoCompletado` → quien lo pidió | "Carla completó tu pedido X: '…' · comprobante". | "Carla completó tu pedido QAS2-AVI v1 A andamio bombas: 'Entregado en obra, adjunto remito' · comprobante" (con el link de comprobante). Sin comprobante, sin la marca. Laura tenía "completado" por mail activado → 1 mail. | ✅ |
| `PedidoCompletado` → superior de quien lo pidió | `completado_gente`, solo en el sistema; un solo aviso si además sigue el pedido; nada si el superior es quien completa o si quien lo pidió no tiene jefe. | Hernán (jefe de Laura y además seguidor) recibe **uno solo**: "Carla completó X, que pidió Laura: '…'". Sin mail. Pedido de Pablo seguido por Sofía, su jefa: Sofía recibe uno solo, `completado_gente`. Hernán completa uno de Laura: solo Laura. Diego (sin jefe): solo Diego. | ✅ |
| `PedidoCompletado` → seguidores | "…completó X (que seguís): '…'"; sin mail. | Igual, a Sofía y a Pablo. **Sofía tenía "completado" por mail activado y no le llegó mail** (correcto: es seguidora). | ✅ |
| `PedidoReabierto` | Al propietario, con el comentario. | "Laura reabrió QAS2-AVI v1 A andamio bombas (falta algo): 'Faltaron 2 bolsas.'" **solo a Carla**; sin mail (no lo activó). | ✅ |
| Recordatorio (F-37) | Al propietario, o a cada persona activa del sector; solo en el sistema; no a quien lo pidió ni a los seguidores. | R1 (No iniciada) → Carla. R2 (en el sector) → Carla, 12 y 13; la 14 inactiva no. R3 (Bloqueada) → Carla. R4 (Completada), R5 (vence hoy) y R6 (pasado mañana) → nada. Texto: "Vence mañana: QAS2-AVI R 1 mañana No iniciada (sigue Sofía) · Servicios generales". | ✅ |
| Retomar · editar · seguir | No avisan. | Retomar → 0 avisos. Editar entrega y aclaraciones → 0. Pablo empieza a seguir → 0. | ✅ |

**Conteo:**
- F-XX: ✅ 3 (F-09, F-37, F-64) · ⚠️ 2 (F-08 y F-10, por bajos) · ❌ 0.
- Tabla §6: 15/15 ✅.
- Nadie recibió un aviso de lo que hizo él mismo. La inactiva nunca recibió nada (seguidora, sector, recordatorio). Ningún aviso duplicado por persona y evento.

### Recordatorio: 8:00 y lunes (DU-P21, DU-Q01)
El comando toma la hora del reloj (`CarbonImmutable::now()`) y no tiene una opción para forzarla. Lo probé **sin tocar código ni fechas**:
- Llamé a `RecordarEntregasDeMananaAction::handle($ahora)` desde tinker, dentro de `DB::beginTransaction()` / `DB::rollBack()` (`AVI\08-recordatorio-horas.php`).
- No queda nada en la base: después hay 6 `vence_manana`, los mismos de antes. Este aviso no encola mails.

| Hora simulada | Resultado |
|---|---|
| vie 20-nov 07:59:59 AR | `null` → el comando dice "Todavía no son las 8:00 (hora de Argentina): el recordatorio sale desde esa hora." ✅ |
| sáb 21-nov 07:59:59 AR | `null` ✅ |
| sáb 21-nov 08:00:00 AR | 4 recordatorios para las entregas del **22-nov**: R6 y R8 (míos) y los pedidos 18 y 24 (de PED). Se descartaron con el rollback. ✅ |
| **dom 22-nov 08:00 AR** | 1 recordatorio, a Carla, para **R7 (entrega lunes 23-nov)** → días corridos, un lunes se recuerda el domingo (DU-Q01). ✅ |
| dom 22-nov 23:59 AR | el mismo R7 (la clave lo deja salir una sola vez) ✅ |
| sáb 21-nov 02:30 UTC (= vie 20-nov 23:30 AR) | 0: sigue siendo viernes en Argentina, así que busca las entregas del 21, que ya tenían su recordatorio. Con la fecha de UTC habría buscado las del 22. ✅ |

## Hallazgos

### AVI-01 · Mis avisos: "Vence mañana una tarea mía" sigue con una casilla (gris y vacía)
- **Severidad:** baja
- **Tipo:** consistencia-con-documento
- **F-XX / pantalla:** F-10 · W-19 Mi perfil, sección "Mis avisos" · `/perfil`
- **Persona:** Laura (Ingeniería de proyectos) y Sofía (Ingeniería de proyectos)
- **Pasos:** 1. Entrar a Mi perfil. 2. Mirar la tabla "Mis avisos", columna "Además por mail".
- **Esperado:** DU-P19 (resuelta): "nunca por mail 'vence mañana' (F-37; **se saca esa casilla**)". `avisos.md` §4.3: "la casilla 'vence mañana' de W-19 no va".
- **Obtenido:**
  - La fila "Vence mañana una tarea mía | siempre" tiene una casilla deshabilitada y vacía, sin ninguna explicación. Se parece a las de asignación (también deshabilitadas, pero tildadas), y quien la ve no entiende por qué no la puede tildar.
  - La API la manda en `GET /api/me/avisos-por-mail` con `se_puede_cambiar: false`.
  - Reproducido 2 veces (Laura y Sofía, y otra vez al restaurar).
- **Evidencia:** `capturas/F-10-mis-avisos-laura-pc.png`, `capturas/F-10-mis-avisos-sofia-pc.png`
- **Causa probable (opcional):** `app/Modules/Avisos/Domain/TipoAviso.php:31` (`deMisAvisos()` incluye `VenceManana`) y `src/features/avisos/components/MisAvisosPorMail.tsx:69-78` (dibuja la casilla para todas las filas). Alternativa: dejar la fila con "no llega por mail" en lugar de la casilla.
- **Regresión de:** no

### AVI-02 · Panel de la campana: cada aviso repite la obra
- **Severidad:** baja
- **Tipo:** consistencia-con-documento
- **F-XX / pantalla:** F-08 · W-02 panel "Avisos" de la campana
- **Persona:** Carla (Compras)
- **Pasos:** 1. Laura le crea un pedido a Carla. 2. Carla toca la campana.
- **Esperado:** W-02: texto y abajo "hace 10 min · asignado a vos" ("Diego te asignó Envío de cómputo de materiales · Piso industrial nave 3" / "hace 10 min · asignado a vos"). La obra va una vez.
- **Obtenido:**
  - "Laura te asignó QAS2-AVI 01 campana Carla · Servicios generales", y abajo "**Servicios generales** · hace 1 min".
  - Con obra pasa lo mismo: "Laura te asignó QAS2-PED Alquiler de andamio… · QAS2-PED Reparación de losa tanque B" y abajo "QAS2-PED Reparación de losa tanque B · recién".
  - Lo mismo con "Vence mañana: … · Servicios generales" / "Servicios generales · hace 16 min".
  - Reproducido en las 2 vueltas (`02-campana`, `09-vence-campana`).
- **Evidencia:** `capturas/F-08-campana-pc.png`, `capturas/F-37-vence-manana-campana-pc.png`
- **Causa probable (opcional):** `src/features/avisos/components/PanelAvisos.tsx` (pie `{nombreObra(aviso)} · {cuandoFue(...)}`), cuando el texto del backend ya trae la obra.
- **Regresión de:** no

### AVI-03 · La campana a veces tarda más de 15 s
- **Severidad:** baja
- **Tipo:** aviso
- **F-XX / pantalla:** F-08 · W-02 campana (PC)
- **Persona:** Carla mirando la campana; Laura crea el pedido. En la otra prueba, Laura mira y Carla comenta.
- **Pasos:**
  1. Abrir Inicio con la persona A y no tocar nada.
  2. Con la persona B, crear un pedido para A o comentar uno suyo.
  3. Medir cuánto pasa hasta que cambia el contador de la campana de A.
- **Esperado:** "La campana se actualiza sola en ≤ 15 s" (prompt §2; RN-AVI-01 "Un aviso aparece en la campana en ≤ 15 s").
- **Obtenido:**
  - 9 medidas: 8,3 · **15,3** · 13,0 · 5,2 · 5,5 · 10,7 · 2,6 · 8,0 · **16,4 s**. Siempre llega sin recargar, pero 2 de 9 pasan los 15 s.
  - El contador se consulta cada 15,15–15,96 s (21 intervalos medidos). Además, el aviso tarda 0,7–3,4 s en existir: el listener va por la cola `default`, con `--sleep=3`.
  - El peor caso teórico es entonces ~19 s.
  - Reproducido 2 veces (`out-02.txt` vuelta 2 y `out-10.txt` medida 6).
- **Evidencia:** `AVI\out-02.txt`, `AVI\out-10.txt` · `capturas/F-08-campana-sola-pc.png`
- **Causa probable (opcional):** `INTERVALO_AVISOS_MS` = 15 s (`src/shared/polling/polling.ts`), sumado a la latencia de la cola `default`. Para cumplir "≤ 15 s" de punta a punta alcanza con un intervalo de ~10 s, o hay que aceptar que los 15 s son del polling (ADR-005) y no de la acción.
- **Regresión de:** no

## §6.1 del backlog que me toca
- **"Enterarse sin que lo arroben: asignación (también por mail), tomado, completado, 'vence mañana'":** **funciona**. Campana sin recargar (≤ 15 s en 7 de 9; ver AVI-03), mail de asignación una vez por la cola `mail`, tomado y completado a quien lo pidió y a los seguidores, y "vence mañana" en la campana del propietario.
- **"Que el entorno mande los mails, corra el recordatorio…":** **funciona**.
  - Mails: worker `mail`, 72 `AvisoPedidoMail` DONE, 0 fallidos.
  - `avisos:recordatorios`: corrido a mano 3 veces, sin errores.
  - Hasta las 11:45 de Argentina, el `schedule:work` no había llegado a una hora en punto (`sched.log`: "No scheduled commands are ready to run").
  - **Para el Lead:** en la corrida de las 12:00 de Argentina (15:00 UTC) espero "Recordatorios creados: N" **sin** repetir los de los pedidos 30, 31 y 32 (siguen 1 por persona). R6 y R8 (entrega 22-nov) **no** tienen que salir hoy.
- **"Que los mails que no tienen que salir no salgan":** **funciona**. Ningún mail a seguidores, al superior, de vence mañana, ni de tomado/bloqueado/comentado/reabierto sin activar.

## Para el corte del worker de mail (lo hace el Lead)
Qué espero ver:
1. Con el worker `mail` parado, crear un pedido Laura → Carla:
   - el aviso aparece igual en la campana de Carla (va por la cola `default`);
   - el front muestra el pedido creado y "Se le avisó a Carla" sin error;
   - en `laravel-*.log` **no** aparece el mail.
   - En Redis, la cola `tecnoservicios_prueba_queues:mail` crece en 1.
2. Al levantar el worker: sale **un** mail "Te asignaron un pedido: …", **sin duplicar**, y con el link `http://localhost:5316/pedidos/{id}`.
3. Si durante el corte se desactiva al destinatario (por ejemplo, un pedido a mi usuario 13 y luego desactivarlo con Diego), al levantar el worker **no** sale (RN-AVI-20, caso borde 22). Yo no lo probé porque necesita el corte.

## No probado
- **DU-P09, reabrir con el propietario inactivo** (elegir el nuevo propietario al reabrir): hace falta un pedido completado por un usuario que después se desactiva, y mis usuarios no tienen sesión (0 ingresos reales).
- **El mail no sale si desactivan a la persona antes de que se envíe** (caso borde 22): necesita el worker `mail` parado. Queda para el corte del Lead (ver arriba).
- **Los bordes de hora del recordatorio en la corrida real del scheduler:** los probé llamando a la acción con la hora simulada y rollback, no esperando a las 8:00 ni a un domingo. El comando no tiene una opción `--ahora`.
- **Asignación al sector al crear:** el alta exige propietario ("Elegí el propietario."), así que no hay asignación al sector al crear. Probé el texto y el asunto del sector reasignando.
- **Celular (W-41):** es del rol 6.
- **Avisos con obra real:** todos mis pedidos son de Servicios generales. Lo con obra lo vi de rebote en los pedidos `QAS2-PED` de Carla, y el texto lleva la obra.
- **Refresco al volver a enfocar la pestaña:** no lo medí.

— AVI (rol 2, Avisos y mail), 2026-11-20 11:45 AR · 0 ingresos reales · 3 hallazgos bajos.
