# Hallazgos · Prueba de usuario Semana 2 (Pedidos y avisos) · Ronda 1 · Tecnoservicios (NS-2026-025)

- **Fecha:** 2026-11-20 (máquina con Laragon `C:\`)
- **Entorno:** base `tecnoservicios_prueba_usuario` · front `http://localhost:5316` (`staging` @ `d8f2433`, worktree `tecnoservicios_frontend_qa_s2`) · API `http://localhost:8340` (`staging` @ `7e007dd`, worktree `tecnoservicios_backend_qa_s2`, rama `qa/prueba-usuario-semana-2`)
- **Conteo:** 16 hallazgos · bloqueante 0 · alta 0 · **media 2** · baja 14 · **ninguna regresión**.
- **Próximo número libre:** **PU-617** (la ronda corta numera desde **PU-701**).
- **Orden:** por severidad y, dentro de cada una, por alcance (primero lo que toca un "sí o sí" de §6.1) y por rol (PED, AVI, JEF, OBR, PER, CEL). Al final de cada **Evidencia**, `origen:` dice de qué borrador sale. Las capturas **no se renombraron**: se citan por su nombre real en `capturas/`. Los borradores no se modificaron.

## ID del borrador → PU-6XX

| ID del borrador | Rol | PU nuevo | Severidad final | Capturas y logs (nombre real) |
|---|---|---|---|---|
| OBR-02 (+ lo visto por CEL en W-47, 2 de 2) | OBR, CEL | PU-601 | media (sostenida) | `OBR-02-1.png`, `OBR-02-2.png` |
| JEF-01 | JEF | PU-602 | **media** (borrador: baja) | `JEF-01-1.png`, `JEF-01-2.png` · `logs/JEF-01.log` |
| JEF-02 + CEL-01 | JEF, CEL | PU-603 | baja (sostenida) | `JEF-02-1.png`, `CEL-01-1.png`, `CEL-01-2.png`, `F-13-obra-tarjeta-entera-p7-celular.png` · `JEF\out\11-v1.txt`, `11-v2.txt`, `CEL\out\out-01-{p7-1,p7-2,se-1}.txt` (scratchpad) |
| AVI-03 | AVI | PU-604 | baja (sostenida) | `F-08-campana-sola-pc.png` · `AVI\out-02.txt`, `AVI\out-10.txt` (scratchpad) · **sin log en `logs/`** |
| AVI-02 (+ lo visto por CEL en `/avisos`) | AVI, CEL | PU-605 | baja | `F-08-campana-pc.png`, `F-37-vence-manana-campana-pc.png`, `F-13-avisos-p7-celular.png` |
| AVI-01 | AVI | PU-606 | baja | `F-10-mis-avisos-laura-pc.png`, `F-10-mis-avisos-sofia-pc.png` |
| PED-02 | PED | PU-607 | baja (sostenida) | `PED-02-1.png`, `PED-02-2.png` |
| PED-01 | PED | PU-608 | baja | `PED-01-1.png`, `PED-01-2.png`, `F-34-recorrido-pc.png` |
| PED-03 | PED | PU-609 | baja | `PED-03-1.png`, `PED-03-2.png` · `PED\out\03.txt` (scratchpad) |
| PED-04 | PED | PU-610 | baja | `PED-04-1.png`, `PED-04-2.png`, `PED-04-3.png` |
| PED-05 | PED | PU-611 | baja | `PED-05-1.png` · `PED\out\09b.txt`, `05.txt` (scratchpad) |
| JEF-03 | JEF | PU-612 | baja | `JEF-03-1.png`, `JEF-03-2.png` · `logs/JEF-03.log` |
| JEF-04 | JEF | PU-613 | baja | `JEF-04-1.png`, `JEF-04-2.png` |
| OBR-01 | OBR | PU-614 | baja | `OBR-01-1.png`, `OBR-01-2.png`, `F-15-listado-en-ejecucion-servicios-generales-pc.png` |
| PER-01 | PER | PU-615 | baja | `logs/PER-01.log` · **sin captura** |
| PER-02 | PER | PU-616 | baja (sostenida) | `PER-02-1.png` · `logs/PER-02.log` |

## Consolidación (QC)

- **Deduplicado:**
  - **JEF-02 + CEL-01 → PU-603.** Es la misma causa: todas las listas de pedidos usan la misma celda (`components/inicio/celdas.tsx:20`, el `Link` solo en el texto de la tarea; la usan `TablaMisTareas`, `PedidosQuePedi`, `TareasDeMiGente`, `TareasQueSigo`, `TablaPedidosConSector` y `TablaTodos`), y el "link estirado" (`::after` con `inset: 0`) existe solo para la pestaña Pedidos de la obra (`obra-pedidos.css:59-64`). En PC no se abre la fila; en el celular no se abre la tarjeta. Un solo hallazgo con todas las pantallas.
  - **AVI-02 + lo que vio CEL en `/avisos`** (el texto repite la obra) → **PU-605**. Lo que CEL vio en `/avisos` sobre tocar la obra o el "cuándo" queda dentro de PU-603.
  - **OBR-02 + lo que vio CEL en W-47** (2 de 2 en el celular) → **PU-601**. CEL ya lo marcó como "no lo duplico".
- **Severidades que cambió QC:**
  - **PU-602 (JEF-01): baja → media.** El texto le dice a la persona que es un permiso de su sector y que se lo pida a gerencia, cuando no es una celda de la tabla (RN-PED-35, RN-IDE-13): gerencia no la puede habilitar y la persona va a ir a pedir algo que no existe. Es el mismo caso que **PU-014** de la Semana 1 ("Tu sector no tiene permiso para ver esto" cuando lo que pasa es otra cosa; también entrando por la dirección), que fue **media** por "mensaje confuso".
- **Severidades que QC sostiene (con su razón):**
  - **PU-601 (OBR-02) queda en media.** Un campo obligatorio ("Obra *") dice "Elegí…" mientras el pedido se guarda en la obra: la pantalla contradice lo que se guarda, como PU-111 (media). No es alta: el dato guardado es el correcto (pedido 67 con `obra_id = 2`, confirmado en la base) y no se pierde nada.
  - **PU-604 (AVI-03) queda en baja.** El documento dice "≤ 15 s para lo que hizo otro" de punta a punta (ADR-005, RN-AVI-01, HU-045 c1), no "cada 15 s" (eso es el sondeo, RN-AVI-22). Las dos cosas juntas no cierran: 15 s de sondeo más 0,7–3,4 s de la cola `default`. Pero el aviso **llega siempre**, sin recargar (9 de 9), y se pasó por 0,3 s y 1,4 s. "Un aviso que no llega o llega a quien no corresponde" (alta) no aplica: llega, a quien corresponde, y el criterio n.º 3 se mide contra la fecha de entrega (días), no contra segundos. Es un incumplimiento chico de un número de la spec: baja. Desarrollo decide si baja el intervalo (~10 s) o aclara la spec.
  - **PU-607 (PED-02) queda en baja.** El §0 prohíbe claves crudas. En la Semana 1 no apareció ninguna clave del tipo `validation.required`; los textos con formato técnico a la vista quedaron en **baja**: PU-032 ("Fin previsto: 2027-03-31 → 2027-04-30" en el historial) y PU-132 ("Proveedores: tu sector…", suena a etiqueta). "(completado gente)" es el tipo interno con el guion bajo cambiado por un espacio: son palabras en castellano, se adivina el sentido, está en una línea del recorrido (HU-037, que no es "sí o sí") y no es un error técnico (SQL, 500, inglés). Queda en el borde: si desarrollo no lo arregla como pendiente menor, en la ronda siguiente sube.
  - **PU-603 (JEF-02 + CEL-01) queda en baja.** Es el mismo patrón que **PU-210** (ronda 3, el número de 21 px como único link de la tarjeta de Obras) y **PU-307** (ronda 4, la tarjeta de Usuarios), las dos **baja**. Todo se puede abrir tocando la tarea; no es "celular roto": comentar, tomar y completar desde el celular andan 3 de 3.
  - **PU-616 (PER-02) queda en baja.** El texto promete un aviso que no se manda, pero solo cuando uno completa un pedido que se pidió a sí mismo; nadie recibe un aviso equivocado y nada se pierde. Es como **PU-029** (Semana 1, "Guardamos los cambios" sin haber cambiado nada), **baja**.
  - El resto (PU-605, PU-606, PU-608…PU-615) son detalles de texto, de vista o casos poco comunes, con análogos bajos en la Semana 1 (PU-127/PU-308 para PU-609; PU-123 para textos de éxito).
- **"Regresión de":** ninguno es regresión. PU-602 repite el patrón de PU-014 y PU-603 el de PU-210/PU-307 en pantallas nuevas de la Semana 2 (el arreglo de la Semana 1 no se extendió a ellas); se dice en cada bloque.
- **Lo "esperado" del §1 / `dudas.md` no se reportó como hallazgo.** Revisado contra DU-P01…P24 y DU-Q01…Q03: bloquear sin tomar, quien toma del sector queda propietario, reasignar mantiene el estado, no se borra (405 y "Cargado por error"), prioridad solo de quien lo pidió, entrega anterior → Urgente (PU-610 es solo el texto "-1 días", no la regla), editar no avisa, seguidor sin mail, "vence mañana" y superior nunca por mail, recordatorio desde las 8:00 y lunes → domingo, buscador sin comentarios, Equipos "Fecha" = envío al taller. PU-606 sí es hallazgo **porque** DU-P19 dice "se saca esa casilla".
- **Observaciones de los borradores que QC decidió NO reportar:**
  - **PED obs. 1, el detalle no muestra los seguidores:** W-09 no los dibuja, y RN-PED-26/27 y F-07 no piden verlos en el detalle (Tareas que sigo muestra lo que sigo yo). No es hallazgo; queda como sugerencia de producto en `reporte.md` §9.
  - **PED obs. 2, `pedido_seguidores.created_at` en hora local:** no se ve en ninguna pantalla. Nota técnica para desarrollo.
  - **PED obs. 3, ayuda de "Fecha de pedido" en Editar** ("Hoy, si no la cambiás…"): texto que no aplica al editar. Sin captura ni segunda reproducción: nota menor.
  - **OBR obs. 5, el diálogo "¿Sacar…?" sigue activo después del 422 por un pedido en el sector:** se parece a PU-124 (baja, arreglado para el caso de usuarios activos), pero sin reproducción dedicada y el rol dice que se entiende. Nota menor para mirar junto con PU-124.
  - **OBR obs. 2, "(inactivo)" en masculino:** no hay dato de género; no es hallazgo.
  - **CEL, la fila del aviso todavía "No leído" al volver, 1 de 5:** sin la segunda reproducción que pide el §4. No se reporta.
  - El resto de las observaciones de JEF, OBR y PER coinciden con el documento o son límites del entorno (detalle en `reporte.md` §9).
- **Evidencia:** las 184 capturas y los 6 logs de `capturas/` y `logs/` se listaron; **todas las capturas citadas existen**. Faltan o son parciales:
  - **PU-615 (PER-01):** sin captura; tiene `logs/PER-01.log` con los largos en la base (5000 / 2000) y las dos corridas (pedidos 90/91 y 92/93). Alcanza: el hallazgo es lo que queda guardado.
  - **PU-604 (AVI-03):** sin log en `logs/`; las 9 medidas están en `AVI\out-02.txt` y `AVI\out-10.txt` del scratchpad (revisados por QC: "medida 6 … campana 16414 ms", intervalos 15 170–15 963 ms).
  - **PU-603, PU-609, PU-611:** una captura por pantalla y la segunda reproducción en las salidas del scratchpad (`JEF\out\11-v2.txt`, `PED\out\03.txt`, `PED\out\09b.txt`), revisadas.
  - Ningún hallazgo baja a "sin evidencia".
- **Confirmado en la base (solo lectura):** pedido 67 con `obra_id = 2` (PU-601); pedidos 94 y 96 con 0 avisos (PU-616); tipo `completado_gente` con 17 filas (PU-607); `failed_jobs` = 0.
- Sin datos sensibles: ni códigos de ingreso, ni tokens, ni cookies. Se sacaron los mails de las personas de prueba de los textos citados.

---

### PU-601 · "+ Nuevo pedido en esta obra": el campo Obra queda en "Elegí…" aunque el pedido se guarda en la obra
- **Severidad:** media
- **Tipo:** funcional
- **F-XX / pantalla:** F-17 · W-07 pestaña Pedidos → F-24 · W-08 Nuevo pedido · `/obras/2` → `/pedidos/nuevo` (PC y celular W-47)
- **Persona:** Diego (Gerencia de Administración) y Hernán (Jefatura de Proyectos); en el celular, Diego
- **Pasos:** 1. Abrir la obra 9-950-2026 (pestaña Pedidos). 2. Tocar "+ Nuevo pedido en esta obra". 3. Mirar el campo Obra un par de segundos después de que carga. 4. (Hernán) Completar tarea, sector Compras, Carla, entrega, sin tocar Obra, y "Crear pedido".
- **Esperado:** el formulario "viene con la obra puesta" y se ve así (RN-PED-53 "con la obra elegida", W-07).
- **Obtenido:** a los ~0,3 s el campo muestra "QAS2-OBR Montaje de cañería planta norte · 9-950-2026" y, en cuanto llega la lista de obras, pasa a **"Elegí…"** (a 1,5 s y a 5 s sigue así). El pedido igual se guarda en la obra: K quedó en `/pedidos/67` con "Obra: QAS2-OBR Montaje de cañería planta norte" y la pestaña pasó a "Pedidos (8 abiertos)". La pantalla dice una cosa en un campo obligatorio ("Obra *") y se guarda otra: la persona cree que tiene que elegir la obra, o que "Elegí…" va a cargar el pedido sin obra. 3 de 3 en PC (Diego 2, Hernán 1) y 2 de 2 en el celular.
- **Evidencia:** `capturas/OBR-02-1.png` (Diego, "Elegí…" a los 5 s) · `capturas/OBR-02-2.png` (Hernán, formulario listo para enviar con "Elegí…") · `POST /api/pedidos` → 201 con `obra.id: 2` · base: pedido 67 con `obra_id = 2` · origen: OBR-02 + CEL (F-13 obras)
- **Causa probable (opcional):** `src/features/pedidos/components/formulario/CampoObra.tsx:48`: la obra traída se muestra con la `<option>` de `fijadaFuera`; cuando `obras-opciones` trae esa misma obra, esa opción se saca y se agrega otra con el mismo valor más abajo. El `<select>` no controlado pierde la selección en el DOM (queda en "Elegí…") mientras react-hook-form sigue con `obra = "2"`. Pasa siempre que la obra está entre las primeras 20 de la lista.
- **Regresión de:** no (HU-054)

### PU-602 · Carga por persona sin gente a cargo dice que es un permiso del sector y manda a pedírselo a gerencia
- **Severidad:** media
- **Tipo:** mensaje
- **F-XX / pantalla:** F-12 · W-04 Carga por persona · `/pedidos/carga-por-persona`
- **Persona:** Laura y Pablo (Ingeniería de proyectos, sin gente a cargo)
- **Pasos:** 1. Laura (o Pablo) entra a `/pedidos/carga-por-persona` por la dirección (no tiene el acceso en la barra de Pedidos, que está bien). 2. Mirar el mensaje.
- **Esperado:** que diga por qué no la ve, como la API: "Esta sección es para quien tiene gente a cargo." (RN-PED-35, RN-IDE-13: no es una celda de la tabla de permisos, gerencia no la puede habilitar), sin el filtro ni la leyenda de una pantalla que no se muestra.
- **Obtenido:** "No tenés acceso a esta sección" / "Tu sector no tiene permiso para ver Carga por persona. Si lo necesitás, pedíselo a gerencia.", debajo del título "Carga de mi gente", del desplegable "Sector: todos" y de la leyenda "La barra compara lo que vence esta semana entre las personas: no es un puntaje. Tocando una persona se ven sus tareas abiertas.". Reproducido 2 veces con cada una.
- **Evidencia:** `capturas/JEF-01-1.png` (Laura) · `capturas/JEF-01-2.png` (Pablo) · `logs/JEF-01.log` · `GET /api/pedidos/carga-por-persona` → 403 "Esta sección es para quien tiene gente a cargo." · origen: JEF-01
- **Causa probable (opcional):** `CargaPorPersonaPantalla.tsx:103` usa `ConsultaError` con `seccion`, y para un 403 `shared/ui/States.tsx:135` arma siempre "Tu sector no tiene permiso para ver {seccion}. {PEDIRSELO}" sin mirar el mensaje del back; el filtro y la leyenda se dibujan fuera del estado de error.
- **Regresión de:** no (mismo patrón que PU-014 de la Semana 1, en una pantalla nueva)

### PU-603 · Tocar la fila (PC) o la tarjeta (celular) no abre el pedido: solo el texto de la tarea
- **Severidad:** baja
- **Tipo:** consistencia-con-documento
- **F-XX / pantalla:**
  - PC: F-35 · W-24 Todos los pedidos `/pedidos` · F-36 · W-25 `/pedidos/servicios-generales` · F-06 · W-26 `/?pestana=mi-gente`;
  - celular (Pixel 7 e iPhone SE 375×667): F-13 / F-04 · W-41 Mis tareas `/` · F-05 Pedidos que pedí `/?pestana=que-pedi` · F-06 Tareas de mi gente `/?pestana=mi-gente`;
  - en parte, la lista de Avisos en el celular `/avisos` (tocar la obra o el "cuándo" no abre; el resto de la tarjeta sí).
- **Persona:** Hernán (Jefatura de Proyectos) en PC; Carla (Compras), Laura (Ingeniería de proyectos) y Hernán en el celular
- **Pasos:** 1. En PC, abrir Todos los pedidos y tocar la primera fila sobre el propietario, la entrega o el sector (fuera del texto subrayado de la tarea); igual en Servicios generales y en Tareas de mi gente. 2. En el celular, abrir Inicio → Mis tareas y tocar una tarjeta sobre la prioridad, la obra, el solicitante, la entrega, el estado o una esquina; igual en Pedidos que pedí y en Tareas de mi gente.
- **Esperado:** "tocando una fila se abre el pedido (W-09)" (W-24, RN-PED-42; HU-042 c3). En el celular, Mis tareas "en tarjetas" tocables enteras, ≥ 44 px (W-41, HU-051), como ya hacen la pestaña Pedidos de la obra en el celular (W-47) y Usuarios (arreglo de PU-307).
- **Obtenido:** no pasa nada: la URL sigue en `/pedidos`, `/pedidos/servicios-generales`, `/?pestana=mi-gente` o `/`, y el cursor de la fila es la flecha (`cursor: auto`). Solo abre el link de la tarea, que en el celular mide 21 px de alto en una tarjeta de 380×268 (`a "QAS2-AVI R 5 vence hoy" 177×21`). En la ficha de obra, la misma tarjeta abre `/pedidos/59` desde cualquier punto. En el calendario, tocar el pedido sí lo abre. Reproducido 2 veces en las tres pantallas de PC y en 2 corridas de Pixel 7 + 1 de iPhone SE (2 tarjetas por pantalla, 5 puntos por tarjeta).
- **Evidencia:** `capturas/JEF-02-1.png` (PC, fila con el mouse encima) · `capturas/CEL-01-1.png` (Mis tareas, Pixel 7) · `capturas/CEL-01-2.png` (iPhone SE) · para comparar, `capturas/F-13-obra-tarjeta-entera-p7-celular.png` · `JEF\out\11-v1.txt`, `11-v2.txt` y `CEL\out\out-01-{p7-1,p7-2,se-1}.txt` (scratchpad) · origen: JEF-02 + CEL-01 (fusionados por QC)
- **Causa probable (opcional):** `src/features/pedidos/components/inicio/celdas.tsx:20` pone el `Link` solo en el texto de la tarea, y esa celda la usan `TablaMisTareas`, `PedidosQuePedi`, `TareasDeMiGente`, `TareasQueSigo`, `TablaPedidosConSector` y `TablaTodos`; el `::after` con `inset: 0` que estira el link a la fila existe solo para `.pedidos-de-la-obra` (`components/obra/obra-pedidos.css:59-64`). En Avisos, `avisos.css:212` deja la obra y la fecha fuera del link.
- **Regresión de:** no (mismo patrón que PU-210 y PU-307 de la Semana 1, en pantallas nuevas)

### PU-604 · La campana a veces tarda más de 15 s
- **Severidad:** baja
- **Tipo:** aviso
- **F-XX / pantalla:** F-08 · W-02 campana (PC)
- **Persona:** Carla mirando la campana mientras Laura crea el pedido; en la otra prueba, Laura mira y Carla comenta
- **Pasos:** 1. Abrir Inicio con la persona A y no tocar nada. 2. Con la persona B, crear un pedido para A o comentar uno suyo. 3. Medir cuánto pasa hasta que cambia el contador de la campana de A.
- **Esperado:** "≤ 15 s para lo que hizo otro" (ADR-005), "Un aviso aparece en la campana en ≤ 15 s" (RN-AVI-01), "el contador de la campana sube sin recargar en ≤ 15 s" (HU-045 c1).
- **Obtenido:** 9 medidas: 8,3 · **15,3** · 13,0 · 5,2 · 5,5 · 10,7 · 2,6 · 8,0 · **16,4 s**. Siempre llega sin recargar, pero 2 de 9 pasan los 15 s. El contador se consulta cada 15,15–15,96 s (21 intervalos) y el aviso tarda 0,7–3,4 s en existir (el listener va por la cola `default`, con `--sleep=3`): el peor caso teórico es ~19 s.
- **Evidencia:** `capturas/F-08-campana-sola-pc.png` · `AVI\out-02.txt` (vuelta 2) y `AVI\out-10.txt` ("medida 6: … aviso en la base 2792 ms · campana 16414 ms · sin recargar") en el scratchpad · origen: AVI-03
- **Causa probable (opcional):** `INTERVALO_AVISOS_MS` = 15 s (`src/shared/polling/polling.ts`) más la latencia de la cola `default`. Para cumplir "≤ 15 s" de punta a punta alcanza con un intervalo de ~10 s; o se aclara en la spec que los 15 s son del sondeo (RN-AVI-22) y no de la acción.
- **Regresión de:** no

### PU-605 · Cada aviso repite la obra: en el texto y otra vez abajo
- **Severidad:** baja
- **Tipo:** consistencia-con-documento
- **F-XX / pantalla:** F-08 · W-02 panel "Avisos" de la campana (PC) · F-13 · W-41 lista de avisos en el celular `/avisos`
- **Persona:** Carla (Compras)
- **Pasos:** 1. Laura le crea un pedido a Carla. 2. Carla toca la campana (o, en el celular, abre Avisos).
- **Esperado:** W-02: el texto y abajo "hace 10 min · asignado a vos" ("Diego te asignó Envío de cómputo de materiales · Piso industrial nave 3" / "hace 10 min · asignado a vos"). La obra va una vez.
- **Obtenido:** "Laura te asignó QAS2-AVI 01 campana Carla · Servicios generales" y abajo "**Servicios generales** · hace 1 min". Con obra: "Laura te asignó QAS2-PED Alquiler de andamio… · QAS2-PED Reparación de losa tanque B" y abajo "QAS2-PED Reparación de losa tanque B · recién". Igual con "Vence mañana: … · Servicios generales" / "Servicios generales · hace 16 min" y en la lista del celular. Reproducido en las 2 vueltas de AVI y en las corridas de CEL.
- **Evidencia:** `capturas/F-08-campana-pc.png` · `capturas/F-37-vence-manana-campana-pc.png` · `capturas/F-13-avisos-p7-celular.png` · origen: AVI-02 + CEL (F-13 avisos)
- **Causa probable (opcional):** `src/features/avisos/components/PanelAvisos.tsx` (pie `{nombreObra(aviso)} · {cuandoFue(...)}`) cuando el texto del backend ya trae la obra.
- **Regresión de:** no

### PU-606 · Mis avisos: "Vence mañana una tarea mía" sigue con una casilla (gris y vacía)
- **Severidad:** baja
- **Tipo:** consistencia-con-documento
- **F-XX / pantalla:** F-10 · W-19 Mi perfil, sección "Mis avisos" · `/perfil`
- **Persona:** Laura y Sofía (Ingeniería de proyectos)
- **Pasos:** 1. Entrar a Mi perfil. 2. Mirar la tabla "Mis avisos", columna "Además por mail".
- **Esperado:** DU-P19 (resuelta): "nunca por mail 'vence mañana' (F-37; **se saca esa casilla**)". `avisos.md` §4.3: "la casilla 'vence mañana' de W-19 no va".
- **Obtenido:** la fila "Vence mañana una tarea mía | siempre" tiene una casilla deshabilitada y vacía, sin explicación. Se parece a las de asignación (deshabilitadas, pero tildadas) y no se entiende por qué no se puede tildar. La API la manda en `GET /api/me/avisos-por-mail` con `se_puede_cambiar: false`. Reproducido 2 veces (Laura y Sofía, y otra vez al restaurar).
- **Evidencia:** `capturas/F-10-mis-avisos-laura-pc.png` · `capturas/F-10-mis-avisos-sofia-pc.png` · origen: AVI-01
- **Causa probable (opcional):** `app/Modules/Avisos/Domain/TipoAviso.php:31` (`deMisAvisos()` incluye `VenceManana`) y `src/features/avisos/components/MisAvisosPorMail.tsx:69-78` (dibuja la casilla en todas las filas).
- **Regresión de:** no

### PU-607 · El recorrido muestra el tipo de aviso como clave: "(completado gente)"
- **Severidad:** baja
- **Tipo:** mensaje
- **F-XX / pantalla:** F-34 · W-09 Detalle del pedido · `/pedidos/{id}` (Historial del recorrido)
- **Persona:** Laura (Ingeniería de proyectos)
- **Pasos:** 1. Laura le pide algo a Carla. 2. Carla lo completa. 3. Laura abre el pedido y mira el recorrido. (Igual cuando Laura completa un pedido que pidió Carla: el aviso a Diego.)
- **Esperado:** un texto que una persona entienda, como en la lista de avisos ("Completado (de mi gente)", `avisos/dominio.ts`), por ejemplo "sistema avisó a Hernán, jefe de Laura (completado)". El §0 prohíbe claves crudas.
- **Obtenido:** "Sistema avisó a Hernán (completado gente)" (pedido 3) y "Sistema avisó a Diego (completado gente)" (pedido 10).
- **Evidencia:** `capturas/PED-02-1.png` (pedido 10) · `capturas/PED-02-2.png` (pedido 3) · base: tipo `completado_gente` (17 avisos) · origen: PED-02
- **Causa probable (opcional):** `app/Modules/Pedidos/Application/Recorrido/RecorridoQuery.php:117` arma la línea con `str_replace('_', ' ', $aviso->tipo)`. Con "asignado", "tomado" o "reasignado" se lee bien de casualidad.
- **Regresión de:** no

### PU-608 · El recorrido repite el nombre de quien hizo cada cosa
- **Severidad:** baja
- **Tipo:** consistencia-con-documento
- **F-XX / pantalla:** F-34 · W-09 Detalle del pedido · `/pedidos/{id}` (Historial del recorrido)
- **Persona:** Laura (Ingeniería de proyectos); igual con Carla, Sofía, Pablo y Diego
- **Pasos:** 1. Laura crea un pedido para Carla. 2. Carla lo toma y comenta. 3. Abrir el pedido y mirar "Historial del recorrido".
- **Esperado:** "13-oct 16:20 · Laura creó el pedido", "13-oct 16:20 · sistema avisó a Carla (asignado)" (W-09; RN-PED-31).
- **Obtenido:** "20/11/2026 11:12 · Laura Laura creó el pedido", "20/11/2026 11:14 · Carla Carla lo tomó → En curso", "20/11/2026 11:12 · sistema Sistema avisó a Carla (asignado)". En todos los pedidos mirados (3, 7, 10, 13, 24, 43).
- **Evidencia:** `capturas/PED-01-1.png` (pedido 3) · `capturas/PED-01-2.png` (pedido 7) · `capturas/F-34-recorrido-pc.png` · origen: PED-01
- **Causa probable (opcional):** el front pone el nombre (`Recorrido.tsx:29`, `{item.quien?.nombre ?? 'sistema'}`) y el texto del back ya empieza con el nombre (`RecorridoQuery.php`).
- **Regresión de:** no

### PU-609 · En Bloquear, Completar y Reabrir el "Contá…" en rojo queda después de escribir el texto
- **Severidad:** baja
- **Tipo:** validación
- **F-XX / pantalla:** F-29 · W-30 Bloquear · F-31 · W-29 Completar · F-65 · Reabrir (diálogos en `/pedidos/{id}`)
- **Persona:** Carla (Compras), Sofía y Laura (Ingeniería de proyectos)
- **Pasos:** 1. Abrir Bloquear… (o Completar, o Reabrir (falta algo)). 2. Tocar el botón de confirmar con el campo vacío. 3. Escribir el motivo (o el comentario, o lo que falta).
- **Esperado:** al escribir, el error desaparece, como en los formularios de la Semana 1 (arreglo de PU-127/PU-308).
- **Obtenido:** sigue en rojo "Contá qué lo traba." (Bloquear, Sofía, pedido 43), "Contá cómo se resolvió." (Completar, Carla, pedido 3) y "Contá qué falta." (Reabrir, Laura, pedido 3), con el campo lleno, hasta que se vuelve a tocar el botón. Al confirmar anda bien.
- **Evidencia:** `capturas/PED-03-1.png` (Bloquear) · `capturas/PED-03-2.png` (Reabrir) · `PED\out\03.txt` "diálogo completar lleno" (scratchpad) · origen: PED-03
- **Causa probable (opcional):** los diálogos guardan los errores en un `useState` (`BloquearDialog.tsx:16`, igual en Completar y Reabrir) que solo se rearma al confirmar (`AccionDialog.tsx:46-50`).
- **Regresión de:** no

### PU-610 · Con la entrega anterior a la fecha de pedido se lee "-1 días"
- **Severidad:** baja
- **Tipo:** mensaje
- **F-XX / pantalla:** F-26 · W-08 Nuevo pedido `/pedidos/nuevo` y W-09 Detalle `/pedidos/21`
- **Persona:** Laura (Ingeniería de proyectos)
- **Pasos:** 1. Nuevo pedido. 2. Fecha de pedido 18/11/2026, entrega 17/11/2026. 3. Mirar la ayuda de la prioridad; crear y mirar "Pedido · Entrega" en el detalle.
- **Esperado:** se acepta y queda Urgente (DU-P08, esto anda), con una cuenta que se entienda, por ejemplo "Urgente · la entrega es 1 día antes del pedido".
- **Obtenido:** en el formulario, "Urgente · -1 días entre el pedido y la entrega." (con 15-11 → 12-11: "Urgente · -3 días entre el pedido y la entrega."); en el detalle, "18/11/2026 · 17/11/2026 · vencido (-1 días)".
- **Evidencia:** `capturas/PED-04-1.png` (formulario) · `capturas/PED-04-2.png` (detalle) · `capturas/PED-04-3.png` (-3 días) · origen: PED-04
- **Causa probable (opcional):** `textoDias()` en `src/features/pedidos/dominio.ts:95` no mira el signo.
- **Regresión de:** no

### PU-611 · "Pedido creado. Se le avisó a …" vuelve a aparecer con F5 o Atrás
- **Severidad:** baja
- **Tipo:** mensaje
- **F-XX / pantalla:** F-24 · W-09 Detalle del pedido · `/pedidos/{id}`
- **Persona:** Laura (Ingeniería de proyectos) y Diego (Gerencia de Administración)
- **Pasos:** 1. Crear un pedido (queda en el detalle con "Pedido creado."). 2. Tomarlo ("Lo tomaste: el pedido pasó a En curso."). 3. Ir a Inicio y volver con Atrás, o apretar F5 en el detalle.
- **Esperado:** la confirmación de la creación se ve una sola vez, después de crear.
- **Obtenido:** arriba del pedido, que ya está En curso, vuelve "Pedido creado." (pedidos 40 y 41, Atrás y F5 en cada uno). A Diego, al volver a abrir el pedido 13 ya Completado: "Pedido creado. Se le avisó a Hernán." encima de "Completada".
- **Evidencia:** `capturas/PED-05-1.png` · `PED\out\09b.txt` ("[F5 1] F5: "Pedido creado."", "[F5 2] Atrás desde Inicio: "Pedido creado."") · `PED\out\05.txt` ("P3 Diego tras completar") (scratchpad) · origen: PED-05
- **Causa probable (opcional):** el aviso viaja en `location.state`, que sobrevive al F5 y al Atrás.
- **Regresión de:** no

### PU-612 · El "(inactivo)" del propietario falta en el Excel y en el calendario "Mi gente"
- **Severidad:** baja
- **Tipo:** consistencia-con-documento
- **F-XX / pantalla:** F-35 · W-24 Exportar a Excel (`GET /api/pedidos/exportar`) · F-11 · W-03 Calendario `/calendario` (Mi gente)
- **Persona:** Hernán (Jefatura de Proyectos); el calendario igual con Dora (Dirección)
- **Pasos:** 1. Diego desactiva a `QAS2-JEF Inactivo con pendientes`, que tiene 2 pedidos abiertos. 2. Hernán abre Todos los pedidos filtrado por Ingeniería de proyectos: la tabla dice "QAS2-JEF Inactivo con pendientes (inactivo)". 3. "Exportar a Excel" y abrir el archivo. 4. Calendario → Mi gente → Semana.
- **Esperado:** que en todos lados se lea que esa persona ya no está, como en la tabla, W-25, W-26 y W-04 (RN-PED-60, DU-P09). El Excel lleva "las columnas de la tabla" (RN-PED-45).
- **Obtenido:** Excel, columna Propietario: "QAS2-JEF Inactivo con pendientes" sin la marca (filas 8 y 19 de la primera descarga, 10 y 21 de la segunda). Calendario: "QAS2-JEF Inactivo con pendientes · QAS2-JEF Inactivo 1 vencido" el 19-nov y "… Inactivo 2 la semana que viene" el 26-nov, sin "(inactivo)". Quien reparte el trabajo desde el Excel o el calendario no se entera de que esos pedidos no los mira nadie.
- **Evidencia:** `capturas/JEF-03-1.png` (calendario) · `capturas/JEF-03-2.png` (la tabla con "(inactivo)") · `logs/JEF-03.log` (Excel ↔ pantalla, dos descargas) · origen: JEF-03
- **Causa probable (opcional):** `PedidosAExcel.php:68` escribe `$p->propietario->nombre` sin mirar `activo`; `PedidoEnCalendario.tsx:8` usa `p.propietario?.nombre` sin la marca.
- **Regresión de:** no

### PU-613 · El calendario vuelve a "Mes · Mis tareas" al volver de un pedido
- **Severidad:** baja
- **Tipo:** funcional
- **F-XX / pantalla:** F-11 · W-03 Calendario · `/calendario`
- **Persona:** Hernán (Jefatura de Proyectos)
- **Pasos:** 1. Calendario → "Mi gente" → "Semana" → "›" (semana del 23 al 29-nov). 2. Tocar un pedido (abre `/pedidos/50`). 3. Volver con "Atrás" del navegador.
- **Esperado:** volver a la misma vista (Mi gente, semana del 23 al 29-nov), como vuelven Inicio (`?pestana=`) y Todos los pedidos (filtros, orden y página en la URL).
- **Obtenido:** vuelve a "Noviembre 2026" con "Mes" y "Mis tareas": hay que volver a tocar Mi gente, Semana y ›. Reproducido 2 veces.
- **Evidencia:** `capturas/JEF-04-1.png` (antes de abrir el pedido) · `capturas/JEF-04-2.png` (después de Atrás) · origen: JEF-04
- **Causa probable (opcional):** `CalendarioPantalla.tsx:40-43` guarda vista, semana, Mis tareas / Mi gente y obra en `useState`, no en la URL.
- **Regresión de:** no

### PU-614 · Sin obras En ejecución, la fila "Servicios generales" no aparece
- **Severidad:** baja
- **Tipo:** funcional
- **F-XX / pantalla:** F-15 · W-05 Obras · `/obras` (pestaña En ejecución)
- **Persona:** Diego (Gerencia de Administración) y Hernán (Jefatura de Proyectos)
- **Pasos:** 1. Que ninguna obra esté En ejecución (al empezar la prueba no había ninguna; la 2.ª vez, Hernán pasó la obra 2 a Confirmada). 2. Entrar a Obras (abre en En ejecución).
- **Esperado:** al final de En ejecución, la fila "Servicios generales · pedidos que no son de una obra" con la cantidad de abiertos sin obra, para quien tiene Pedidos ≥ Ver y sin texto en el buscador (RN-OBR-08), sin condición de que haya obras en la pestaña.
- **Obtenido:** solo "No hay obras en esta pestaña", sin la fila. La API sí trae el número: `GET /api/obras?estado=en_ejecucion` → `meta.pedidos_sin_obra_abiertos: 56`. Con una obra En ejecución la fila aparece ("… · 43").
- **Evidencia:** `capturas/OBR-01-1.png` (Diego) · `capturas/OBR-01-2.png` (Hernán) · `capturas/F-15-listado-en-ejecucion-servicios-generales-pc.png` (con obra, aparece) · origen: OBR-01
- **Causa probable (opcional):** `src/features/obras/components/ResultadosObras.tsx:45-55`: con `data.obras.length === 0` devuelve el `EmptyState` antes de pasarle `pedidosSinObra` a `ObrasTabla`.
- **Regresión de:** no (HU-055)

### PU-615 · En los diálogos, un texto más largo que el máximo se guarda cortado sin avisar
- **Severidad:** baja
- **Tipo:** validación
- **F-XX / pantalla:** F-30 · W-09 "Escribir un comentario" · F-29 · W-30 "Motivo" · F-65 "¿Qué falta?" (Completar usa el mismo campo) · `/pedidos/{id}`
- **Persona:** QAS2-PER Editar (Pedidos = Editar)
- **Pasos:** 1. Abrir un pedido propio. 2. Pegar 5001 caracteres en "Escribir un comentario" y tocar "Comentar". 3. "Bloquear…", pegar 2001 caracteres en "Motivo" y "Bloquear". 4. En uno completado que pedí, "Reabrir (falta algo)" con 5001 caracteres.
- **Esperado:** como en W-08 y W-32 ("La tarea puede tener hasta 2000 caracteres.") o como la API (422 "Es demasiado largo (máximo 5000 caracteres)."): un aviso y nada se guarda hasta acortarlo (§0: "entiende siempre qué pasó").
- **Obtenido:** el campo corta en silencio (`maxLength`) y se guarda: comentario de 5000, motivo de 2000, comentario de reapertura de 5000, con mensajes de éxito ("El pedido pasó a Bloqueada.", "Reabrimos el pedido: volvió a En curso."). Reproducido 2 veces (pedidos 90/91 y 92/93).
- **Evidencia:** `logs/PER-01.log` (largos en la base y las dos corridas) · `POST /api/pedidos/90/comentarios` → 201, `/90/bloquear` → 200, `/91/reabrir` → 200 · sin captura · origen: PER-01
- **Causa probable (opcional):** `TextoDialogo.tsx:29` y `Comentarios.tsx:74` (`maxLength={…}`) en lugar del mensaje de `pedidos.formularios.ts`.
- **Regresión de:** no

### PU-616 · Al completar un pedido que me pedí a mí, dice "Le avisamos a" mí mismo, y no se avisa a nadie
- **Severidad:** baja
- **Tipo:** mensaje
- **F-XX / pantalla:** F-31 · W-29 Completar · `/pedidos/{id}`
- **Persona:** QAS2-PER Editar (solicitante y propietario del mismo pedido, RN-PED-01 "también a uno mismo")
- **Pasos:** 1. Crear un pedido para uno mismo. 2. Abrirlo y tocar "Completar". 3. Escribir el comentario y "Completar y avisar".
- **Esperado:** como al crear (RN-PED-08: si quien carga es el propietario no se avisa a nadie y la confirmación es solo "Pedido creado."): botón "Completar", sin "le avisamos a…" y "Pedido completado." al terminar. El actor nunca recibe lo suyo (`avisos.md`).
- **Obtenido:** la ventana dice "Al confirmar, el pedido pasa a Completada, le avisamos a QAS2-PER Editar con este comentario y queda en el historial…", el botón es "Completar y avisar" y al terminar "Pedido completado. Le avisamos a QAS2-PER Editar.". En la base no hay ningún aviso de esos pedidos (correcto): el texto promete algo que no pasa. Reproducido 2 veces (pedidos 94 y 96).
- **Evidencia:** `capturas/PER-02-1.png` · `logs/PER-02.log` · base: 0 avisos de los pedidos 94 y 96 · origen: PER-02
- **Causa probable (opcional):** `CompletarDialog.tsx:35` y `:54` usan el nombre del solicitante sin mirar si es quien completa.
- **Regresión de:** no
