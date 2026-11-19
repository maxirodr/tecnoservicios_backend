# Hallazgos · Prueba de usuario Semana 1 · Ronda 4 · Tecnoservicios (NS-2026-025)

- **Fecha:** 2026-11-18 (tanda 1, máquina sin Laragon `B:\`) y 2026-11-19 (retomada, máquina con Laragon `C:\`)
- **Entorno:** base `tecnoservicios_prueba_usuario` · front `http://localhost:5316` (`staging` @ `15b544f` el 18/11 → `380ddaf` el 19/11) · API `http://localhost:8340` (`staging` @ `74beea5` el 18/11 → `56e5d8f` el 19/11; mismo código, solo cambian docs)
- **Alcance:** solo lo nuevo de la ronda 4. El estado de los 20 PU verificados (PU-201…210, PU-212 y los 9 ⚠️ de la ronda 3) está en `verificacion.md`.
- **Conteo:** 10 hallazgos · alta 2 · media 4 · baja 4 · **ninguna regresión** (los 10 son "arreglo incompleto").
  - **Arreglados durante la ronda** (front `380ddaf`, re-verificados el 19/11 en `borradores/ver.md`): PU-303 y PU-304 enteros, y PU-301 en la parte del historial.
  - **Abiertos: 7** · alta 1 (PU-302) · media 2 (PU-305, PU-306) · baja 4 (PU-307…310).
- **Próximo número libre:** **PU-311**
- **Orden:** por severidad (bloqueante, alta, media, baja) y, dentro de cada una, por rol (A, B, CEL, REG, VER, Lead). Al final de cada **Evidencia**, `origen:` dice de qué borrador sale. Las capturas **no se renombraron**: se citan por su nombre real en `capturas/`. Los borradores no se modificaron.

## ID del borrador → PU-3XX

| ID del borrador | Rol | PU nuevo | Severidad final | Estado | Capturas y logs (nombre real) |
|---|---|---|---|---|---|
| B-01 | Verificador B (18/11) | PU-301 | alta | ✅ arreglado en el historial (`380ddaf`); lo que queda abierto va en PU-302 y PU-310 | `B-01-1.png`, `B-01-2.png` · re-verificación `B-01-r4b-*.png` · `logs/B-01.log` **no está en la carpeta** |
| LEAD-01 | QA Lead (19/11) | PU-302 | alta | abierto | `LEAD-CARGADO-1.png`, `LEAD-CARGADO-2.png`, `LEAD-CARGADO-control.png` |
| A-01 | Verificador A (18/11) | PU-303 | media | ✅ arreglado (`380ddaf`) | `A-01-1.png`, `A-01-2.png`, `A-01-3.png` · re-verificación `A-01-r4b-*.png` · `logs/A-01.log` **no está en la carpeta** |
| A-02 | Verificador A (18/11) | PU-304 | media | ✅ arreglado (`380ddaf`) | `A-02-1.png`, `A-02-2.png`, `A-02-3.png` · re-verificación `A-02-r4b-*.png` · `logs/A-02.log` **no está en la carpeta** |
| REG-03 | Cazador de regresiones (19/11) | PU-305 | media | abierto | `REG-03-1.png`, `REG-03-2.png`, `REG-03-3.png`, `REG-03-2-guardar.png`, `REG-03-3-guardar.png` · `logs/REG-03.log` |
| VER-02 | Re-verificación (19/11) | PU-306 | **media** (borrador: baja) | abierto | `A-01-r4b-vista-*-permisos-*.png`, `A-01-r4b-7-atras-422-final.png`, `A-01-r4b-10-atras-500-final.png` |
| CEL-01 | QA celular y accesibilidad (19/11) | PU-307 | baja | abierto | `CEL-01-1.png`, `CEL-01-2.png` |
| REG-01 | Cazador de regresiones (19/11) | PU-308 | baja | abierto | `REG-01-1.png`, `REG-01-2.png` |
| REG-02 | Cazador de regresiones (19/11) | PU-309 | baja | abierto | `REG-02-1.png`, `REG-02-2.png` |
| VER-01 | Re-verificación (19/11) | PU-310 | **baja** (borrador: media) | abierto | `B-01-r4b-trabado-*.png`, `B-01-r4b-trabado-dlg-3-*.png` |
| B-02 | Verificador B (18/11) | — (observación, límite conocido) | — | sin cambio de código; ver `reporte.md` §8 | `FECHAS-r4-lang-default.png`, `FECHAS-r4-lang-es.png`, `B-02-r4b-1.png` |

## Consolidación (QC)

- **B-01 se partió en tres.** El arreglo `efaf4b1` (en `380ddaf`) dejó bien el historial en cualquier huso (PU-301 ✅). Quedan dos problemas distintos, cada uno con su hallazgo: "Cargado" del presupuesto, que el backend manda cortado en UTC (**PU-302**), y "Ingreso trabado hasta las…", que sigue el huso del navegador (**PU-310**). Los dos llevan "Regresión de: no; arreglo incompleto de PU-301 (B-01)".
- **A-01 y A-02** quedaron arreglados y re-verificados (11 y 6 corridas: ok, 422, 409 y 500). Conservan su PU para la trazabilidad.
- **B-02 no es hallazgo.** Es una observación, un límite conocido (`tasks/prueba-usuario-semana-1-ronda-4-pendiente.md`): el campo de fecha nativo sigue el idioma del navegador. Con el navegador en español se ve dd/mm/aaaa (`B-02-r4b-1.png`). Se reabre solo si Juani pide un selector propio.
- **Severidades que cambió QC:**
  - **PU-306 (VER-02): baja → media.** Es el mismo problema que PU-111 de la ronda 2 ("el aviso de error aparece arriba, fuera de la vista: parece que no pasó nada"), que fue **media**, ahora en la Tabla de permisos.
  - **PU-310 (VER-01): media → baja.** En el uso real, con las PC de la empresa en hora de Argentina, la hora sale bien. Solo falla si el navegador está en otro huso. El análogo es B-02 (depende de cómo esté configurado el navegador), que quedó en baja. Este cambio no mueve el veredicto: PU-302 y PU-305 ya lo frenan.
- **Severidades que QC sostiene:**
  - **PU-302** queda en alta: es un dato incorrecto para cualquiera que cargue entre las 21:00 y las 24:00 en Argentina, con la misma regla que dejó B-01 en alta.
  - **PU-305** queda en media, como PU-109 de la ronda 2.
  - **PU-307** queda en baja, como PU-041, PU-134 y PU-210.
  - **PU-308** queda en baja, como PU-127.
  - **PU-309** queda en baja, como PU-137.
- **"Regresión de" de PU-306:** el borrador decía "arreglo incompleto de A-01". Pero pasa igual **sin irse** de la pantalla, así que corresponde al arreglo de PU-111, que no cubrió la Tabla de permisos.
- **F-XX normalizados a la numeración de la ronda 3:** F-18 presupuestos (LEAD-01 decía F-14), F-56 edición de proveedor (A-02 decía F-55), F-58 Usuarios (VER-01 decía F-60).
- **Evidencia:** todas las capturas citadas existen en `capturas/` (503 archivos, revisados con un script).
  - Los logs `logs/A-01.log`, `logs/A-02.log` y `logs/B-01.log` que citan los borradores del 18/11 **no están** en `logs/`: quedaron en la máquina `B:\`. Los tres hallazgos tienen capturas y re-verificación, así que no se bajan a "sin evidencia".
- **Lo "esperado" del §1 no aparece como hallazgo.** No se reportaron el mismo segundo de PU-002, la ñ, el CUIT ni el historial de un link ya quitado.
- **El incidente de MySQL (09:45–10:06) no generó hallazgos.** Todo lo medido en ese rato se descartó y se repitió. REG-03 se reprodujo con la base arriba (`PATCH /api/obras/5` → 200 de Diego en el mismo log).
- Se sacaron los mails de las personas de prueba de los textos citados.

### PU-301 · El historial mostraba la hora en UTC: un cambio de las 14:08 decía "18/11/2026 17:08"
- **Severidad:** alta
- **Estado:** ✅ arreglado en front `380ddaf` (`efaf4b1`), re-verificado (`borradores/ver.md`, y `borradores/reg.md` en UTC y Asia/Tokyo). Siguen abiertas dos partes, cada una con su hallazgo: **PU-302** ("Cargado") y **PU-310** (ingreso trabado).
- **Tipo:** funcional
- **F-XX / pantalla:** F-14 Historial · F-20 · W-23 Editar obra (Historial de cambios) · `/obras/{id}/editar`
- **Persona:** Diego (Gerencia de Administración); visto igual con Hernán y Dora
- **Pasos:** 1. Abrir Editar obra de 2-941-2026 a las 14:08 (hora de la PC, Argentina). 2. Cambiar la planta y "Guardar cambios". 3. Volver a "Editar" y mirar la primera fila del Historial de cambios.
- **Esperado:** "18/11/2026 14:08", la hora de Argentina (`obras.md` pide ISO-8601 con zona `America/Argentina/Buenos_Aires`).
- **Obtenido (18/11, front `15b544f`):** "18/11/2026 17:08 · Diego · Planta: Planta Ñandú QA4-B → Planta Ñandú QA4-B v1". La API manda `"cuando":"2026-11-18T17:08:34+00:00"` y todas las filas del historial están 3 h adelantadas, así que entre las 21:00 y las 24:00 "Cuándo" muestra el día siguiente. Reproducido 2 veces.
- **Re-verificación (19/11, front `380ddaf`):**
  - Un cambio de las 09:30:29 de Argentina se ve "19/11/2026 09:30" con el navegador en Argentina, en UTC y en Madrid.
  - Borde de día, en los 3 husos:
    - `2026-11-19T01:30:00+00:00` → "18/11/2026 22:30";
    - `…T02:59:00Z` → "18/11/2026 23:59";
    - `…T03:00:00+00:00` → "19/11/2026 00:00".
  - REG lo repitió en UTC y en Asia/Tokyo: un cambio de las 13:23 UTC se ve "19/11/2026 10:23".
- **Evidencia:**
  - 18/11: `capturas/B-01-1.png` · `capturas/B-01-2.png` (con el reloj de la PC) · `logs/B-01.log` (**no está en la carpeta**) · `GET /api/obras/5/historial` → 200.
  - 19/11: `capturas/B-01-r4b-1-buenos_aires.png`, `B-01-r4b-1-utc.png`, `B-01-r4b-1-madrid.png`, `B-01-r4b-2-buenos_aires.png`, `B-01-r4b-2-utc.png`, `B-01-r4b-2-madrid.png`, `B-01-r4b-borde-1-buenos_aires.png`, `B-01-r4b-borde-1-utc.png`, `B-01-r4b-borde-1-madrid.png`, `B-01-r4b-borde-2-buenos_aires.png`, `B-01-r4b-borde-2-utc.png`, `B-01-r4b-borde-2-madrid.png`, `B-01-r4b-borde-3-buenos_aires.png`, `B-01-r4b-borde-3-utc.png`, `B-01-r4b-borde-3-madrid.png` · `capturas/REG-ok-r4b-historial-obra.png` · `capturas/REG-ok-r4b-fechas-tokyo.png`.
  - origen: B-01 (re-verificado por VER y REG).
- **Causa probable (opcional):** `tecnoservicios_backend/config/app.php:70` `'timezone' => 'UTC'`, y `formatearFechaHora` (`src/shared/dominio/fechas.ts`) tomaba la hora tal como llegaba. El arreglo `efaf4b1` la pasa a hora de Argentina en el front.
- **Regresión de:** no; arreglo incompleto de PU-209 (el formato quedó bien; la hora ya salía en UTC antes)

### PU-302 · "Cargado" del presupuesto muestra el día siguiente si se cargó entre las 21:00 y las 24:00 de Argentina
- **Severidad:** alta
- **Tipo:** funcional
- **F-XX / pantalla:** F-18 Presupuestos · W-07 Ficha de la obra · `/obras/{id}` → tabla de presupuestos, columna "Cargado"
- **Persona:** Diego (Gerencia de Administración)
- **Pasos:**
  1. Cargar un presupuesto a las 22:30 de Argentina del 19/11/2026.
     - Para no esperar a la noche, el Lead puso el `created_at` del presupuesto 2 en `2026-11-20 01:30:00` UTC (19/11 22:30 en Argentina) y después en `2026-11-19 02:59:00` UTC (18/11 23:59).
     - Al terminar restauró el valor original, `2026-11-19 12:29:45`.
  2. Abrir la ficha de la obra y mirar "Cargado".
- **Esperado:** "19/11/2026 · Diego" (y "18/11/2026" en el segundo caso): la fecha de Argentina, como ya muestra el historial (PU-301).
- **Obtenido:** "20/11/2026 · Diego" y "19/11/2026 · Diego", o sea un día después. La API manda `"created_at":"2026-11-20"`: la fecha ya llega cortada en UTC, así que el front no la puede corregir. Con la hora original (12:29 UTC), el control da "19/11/2026", bien. Reproducido 2 veces (dos horarios), por la API y en pantalla.
- **Evidencia:** `capturas/LEAD-CARGADO-1.png` ("20/11/2026") · `capturas/LEAD-CARGADO-2.png` · `capturas/LEAD-CARGADO-control.png` · `GET /api/obras/2` → 200 · origen: LEAD-01
- **Causa probable (opcional):** `app/Modules/Obras/Http/Resources/PresupuestoResource.php:49` `'created_at' => $presupuesto->created_at->toDateString()`, con `config/app.php:70` `'timezone' => 'UTC'`. Ya estaba anotado como probable en la causa de B-01.
- **Regresión de:** no; arreglo incompleto de PU-301 (B-01): el front `380ddaf` pasa a hora de Argentina los instantes con hora, pero esta fecha llega cortada en UTC desde el backend

### PU-303 · Tabla de permisos: salir mientras se guarda preguntaba "Tenés cambios sin guardar" y la pregunta quedaba abierta después de guardar
- **Severidad:** media
- **Estado:** ✅ arreglado en front `380ddaf` (`428eb27`), re-verificado (`borradores/ver.md`, 11 corridas: ok ×5, 422 ×2, 409 ×2, 500 ×2, por la barra, "Sectores" y Atrás)
- **Tipo:** funcional
- **F-XX / pantalla:** F-60 · W-15 Tabla de permisos · `/usuarios/permisos`
- **Persona:** Diego (Gerencia de Administración)
- **Pasos:** 1. Entrar a Usuarios → "Tabla de permisos". 2. Cambiar una celda (fila RRHH, alcance de Pedidos: "todas" → "sus obras"). 3. Con la red lenta (2,5 s en `PUT /api/permisos`), tocar "Guardar cambios". 4. Mientras guarda, tocar "Obras" en la barra (o "Sectores", o Atrás). 5. No tocar nada y esperar a que termine.
- **Esperado:** (O-1.1) "Estamos guardando. Cuando termine te llevamos." y, al terminar bien, ir a la pantalla elegida.
- **Obtenido (18/11, front `15b544f`):**
  - Mientras guarda sale "Tenés cambios sin guardar. ¿Salir igual?" / "Si salís, se pierde lo que cambiaste.". Al terminar (200) aparece "Guardamos la tabla de permisos. Cada persona lo ve al pasar a otra pantalla o al recargar la página." y la pregunta **sigue abierta encima**: la persona queda en `/usuarios/permisos`.
  - Reproducido 3 veces (barra, "Sectores" y Atrás). Plazos, con la misma demora, sí avisaba bien.
- **Re-verificación (19/11, front `380ddaf`):**
  - Mientras guarda, sale "Estamos guardando. Cuando termine te llevamos." / "No cierres esta pestaña hasta que termine." / "Entendido".
  - Si guarda bien, llega sola a `/obras`, `/usuarios/sectores` o `/usuarios`, sin ninguna pregunta abierta.
  - Si falla, se queda en la pantalla con el error. Con Atrás, ese error queda fuera de la vista: ver **PU-306**.
- **Evidencia:**
  - 18/11: `capturas/A-01-1.png` · `capturas/A-01-2.png` · `capturas/A-01-3.png` · `logs/A-01.log` (**no está en la carpeta**) · `PUT /api/permisos` → 200.
  - 19/11: `capturas/A-01-r4b-1-barra-ok-guardando.png`, `A-01-r4b-1-barra-ok-final.png`, `A-01-r4b-2-sectores-ok-*.png`, `A-01-r4b-3-atras-ok-*.png`, `A-01-r4b-4-barra-ok-*.png`, `A-01-r4b-5-atras-ok-*.png`, `A-01-r4b-6-barra-422-*.png`, `A-01-r4b-7-atras-422-*.png`, `A-01-r4b-8-sectores-409-*.png`, `A-01-r4b-9-barra-409-*.png`, `A-01-r4b-10-atras-500-*.png`, `A-01-r4b-11-barra-500-*.png` (`*` = `guardando` / `final`).
  - origen: A-01 (re-verificado por VER).
- **Causa probable (opcional):** `TablaPermisosPantalla.tsx:45` llamaba `useCambiosSinGuardar(cambios.size > 0)` sin `guardar.isPending`, y `enviar` no llamaba a `salida.permitirSalida()` ni a `salida.terminoBien()`.
- **Regresión de:** no; arreglo incompleto de O-1 (front `63fd81b`; pantalla no cubierta)

### PU-304 · Editar proveedor: con Atrás mientras guarda, al terminar iba a la lista con el diálogo de edición todavía abierto
- **Severidad:** media
- **Estado:** ✅ arreglado en front `380ddaf` (`9d0f84e`), re-verificado (`borradores/ver.md`, 6 corridas: ok ×2, 422, 409 y 500 ×2; y `borradores/reg.md`, 2 veces con 4 s más Escape)
- **Tipo:** funcional
- **F-XX / pantalla:** F-56 · W-14 Proveedores (diálogo "Editar proveedor") · `/proveedores/{id}` → `/proveedores`
- **Persona:** Diego (Gerencia de Administración)
- **Pasos:** 1. En Proveedores, abrir la ficha de "QA4-A Ferretería Diálogo 29019" (`/proveedores/2`). 2. Tocar "Editar" y cambiar "Contacto · teléfono". 3. Con la red lenta (2,5 s en `PATCH`), tocar "Guardar proveedor". 4. Mientras guarda, tocar Atrás. 5. Esperar a que termine.
- **Esperado:** (O-1.1) al terminar bien, ir a la lista con el diálogo cerrado y "Guardamos los cambios de …", como en los demás diálogos.
- **Obtenido (18/11, front `15b544f`):**
  - La app iba a `/proveedores` con el diálogo "Editar proveedor · QA4-A Ferretería Diálogo 29019" todavía abierto, con el teléfono nuevo, y sin "Guardamos los cambios de …".
  - El cambio sí se guardaba (`PATCH /api/proveedores/2` → 200).
  - Reproducido 3 veces. El alta de proveedor no tenía el problema.
- **Re-verificación (19/11, front `380ddaf`):**
  - **Si guarda bien:** va a `/proveedores` con **0 diálogos abiertos** y "Guardamos los cambios de QA4V Ferretería Atrás.", y el teléfono nuevo queda en la base.
  - **Si falla:** se queda en `/proveedores/8` con el diálogo abierto y el error adentro.
- **Evidencia:**
  - 18/11: `capturas/A-02-1.png` · `capturas/A-02-2.png` · `capturas/A-02-3.png` · `capturas/O-1-1-r4-prov-atras-ok-2.png` · `logs/A-02.log` (**no está en la carpeta**).
  - 19/11: `capturas/A-02-r4b-1-ok-guardando.png`, `A-02-r4b-1-ok-final.png`, `A-02-r4b-2-ok-guardando.png`, `A-02-r4b-2-ok-final.png`, `A-02-r4b-3-422-*.png`, `A-02-r4b-4-409-*.png`, `A-02-r4b-5-500-*.png`, `A-02-r4b-6-500-*.png` · `capturas/REG-ok-r4b-prov-atras-guardando.png` · `capturas/REG-ok-r4b-prov-escape-guardando.png`.
  - origen: A-02 (re-verificado por VER y REG).
- **Causa probable (opcional):** `ProveedorDialog.tsx:94` hacía `if (salida.permitirSalida()) return;` antes de `onGuardado(guardado)`. Como la lista y la ficha son la misma pantalla (`ProveedoresPantalla.tsx`), el diálogo no se desmontaba.
- **Regresión de:** no; arreglo incompleto de O-1 (front `63fd81b`)

### PU-305 · Sesión vencida: si otro cambió la obra, al volver dice "Recuperamos lo que estabas escribiendo." sobre los datos viejos, y al guardar da conflicto
- **Severidad:** media
- **Tipo:** funcional
- **F-XX / pantalla:** F-20 · W-23 Editar obra · `/obras/{id}/editar`
- **Persona:** `qa-lead-r4reg2` (Ingeniería de proyectos), con Diego cambiando la obra; repetido con Diego y Hernán
- **Pasos:** 1. Abrir Editar obra de 4-402-2026 y cambiar la planta a "Planta Añelo r4reg2". 2. Vence la sesión (`DEL` de su clave, sin pedidos en vuelo). 3. Mientras tanto, Diego cambia la planta de la misma obra a "Planta Añelo Diego" y guarda (200). 4. "Guardar cambios" → "Tu sesión venció. Volvé a ingresar.". 5. Ingresar con el código del mail.
- **Esperado:** (PU-109) vuelve a Editar obra con "Mientras no estabas, alguien cambió esto. Lo que habías escrito no se pudo recuperar." y con los datos actuales.
- **Obtenido:** "Recuperamos lo que estabas escribiendo." con "Planta Añelo r4reg2" sobre la versión vieja; lo que guardó Diego no se ve.
  - Al guardar: 409 "Alguien cambió esto mientras lo tenías abierto. Actualizá y revisá.". No se pisa nada, pero el aviso de recuperado y el de conflicto se contradicen.
  - Reproducido 1 vez con ingreso real y 2 veces más simulando solo el 401 en el navegador de Diego. En esas, Hernán cambió el cliente; al volver, la pantalla mostraba el cliente anterior y "Guardar cambios" daba 409.
- **Evidencia:** `capturas/REG-03-1.png` (ingreso real) · `capturas/REG-03-2.png` · `capturas/REG-03-3.png` · `capturas/REG-03-2-guardar.png` · `capturas/REG-03-3-guardar.png` · `logs/REG-03.log` · `PATCH /api/obras/5` → 419, 401; después de volver, `PATCH /api/obras/5` → 409 · origen: REG-03
- **Causa probable (opcional):** al ingresar solo se guarda la sesión (`useIngresar` → `useGuardarSesion`). La ficha queda en la caché de React Query con el `updated_at` de antes, y `EditarObraPantalla.tsx:93` monta el formulario con esa versión, así que `useBorrador` compara contra la vieja. En la ronda 3, PU-109 se verificó sin ingreso real en la misma pestaña, así que este camino no estaba cubierto.
- **Regresión de:** no; arreglo incompleto de PU-109

### PU-306 · Tabla de permisos: si el guardado falla, el error queda arriba, fuera de la vista, y parece que no pasó nada
- **Severidad:** media
- **Tipo:** mensaje
- **F-XX / pantalla:** F-60 · W-15 Tabla de permisos · `/usuarios/permisos` (PC 1366×768)
- **Persona:** Diego (Gerencia de Administración)
- **Pasos:** 1. Entrar a Usuarios → "Tabla de permisos". 2. Cambiar "Alcance de Pedidos · RRHH". 3. Bajar hasta "Guardar cambios" y tocarlo; el guardado falla (500 forzado a los 2,5 s; también 422). 4. Variante: tocar Atrás mientras guarda.
- **Esperado:** el error a la vista. Es lo que pide O-1.1 ("si falla, se queda en la pantalla con el error a la vista") y lo que ya hacen Editar obra y Nueva obra desde PU-111.
- **Obtenido:**
  - "El sistema tuvo un problema. Volvé a intentar en un momento." queda 302 px **por encima** de lo visible (`scrollY` 473; el aviso en `top = -302`). El foco vuelve a "Guardar cambios", y lo único visible junto al botón es "1 cambio sin guardar".
  - Pasa igual sin irse y con Atrás, 2 veces cada uno.
  - Con "Obras" en la barra o con "Sectores" el error sí queda a la vista, porque la página sube.
- **Evidencia:** `capturas/A-01-r4b-vista-1-permisos-quedarse.png` · `capturas/A-01-r4b-vista-2-permisos-quedarse.png` · `capturas/A-01-r4b-vista-1-permisos-atras.png` · `capturas/A-01-r4b-vista-2-permisos-atras.png` · `capturas/A-01-r4b-10-atras-500-final.png` · `capturas/A-01-r4b-7-atras-422-final.png` · `PUT /api/permisos` → 500 (forzado) · origen: VER-02
- **Causa probable (opcional):** en `TablaPermisosPantalla.tsx`, el `<Aviso tono="error">` va arriba de la tabla y `enviar` no lleva el foco ni el scroll al aviso cuando falla. `428eb27` no tocó esa parte.
- **Regresión de:** no; arreglo incompleto de PU-111 (la Tabla de permisos no quedó cubierta; pasa también sin irse)

### PU-307 · En Usuarios, en el celular, el nombre que abre "Editar usuario" mide 33–40 px de ancho y el resto de la tarjeta no responde
- **Severidad:** baja
- **Tipo:** celular
- **F-XX / pantalla:** F-58 · W-16 Usuarios (listado) · `/usuarios`
- **Persona:** Diego (Gerencia de Administración)
- **Pasos:** 1. En un Pixel 7 o en un iPhone SE (375×667), abrir Usuarios. 2. Medir el nombre de cada tarjeta, que es lo único que abre "Editar usuario". 3. Tocar otras partes de la tarjeta (centro, derecha y abajo).
- **Esperado:** (PU-041) el control principal de la tarjeta mide ≥ 44×44, o la tarjeta entera abre el usuario, como ya pasa en Obras (PU-210).
- **Obtenido:**
  - Los nombres miden "Carla" 34×44, "Diego" 40×44, "Dora" 33×44, "Laura" 37×44, "Pablo" 38×44 y "Sofía" 34×44. El alto está bien, pero el ancho no llega.
  - Los toques en el resto de la tarjeta (343×215 en SE y 380×215 en Pixel 7) caen en las celdas del mail o de "Sí" y no hacen nada.
  - Los nombres largos no tienen el problema. Reproducido 2 veces en cada celular.
- **Evidencia:** `capturas/CEL-01-1.png` (iPhone SE) · `capturas/CEL-01-2.png` (Pixel 7) · `CEL\out-cel-se-1.txt`, `out-cel-p7-1.txt` ("button "Dora" 33×44 · en <th.tabla__dato>") y `out-inv-se-1.txt`, `out-inv-p7-1/2.txt` (scratchpad del rol; los de ayer están en `borradores/scripts/CEL/`) · origen: CEL-01
- **Causa probable (opcional):** `features/identidad/components/UsuariosTabla.tsx:33-38`: `<th scope="row" className="tabla__dato">` con un botón `tabla__enlace` sin `min-width`, y la tarjeta no tiene el link estirado (`::after`) que usa `ObrasTabla`.
- **Regresión de:** no; arreglo incompleto de PU-041 (ya estaba anotado como nota en la verificación de la ronda 3: "Dora" 33×44)

### PU-308 · Plazos: "No había cambios para guardar." sigue a la vista mientras se edita y aparecen errores
- **Severidad:** baja
- **Tipo:** mensaje
- **F-XX / pantalla:** F-68 · W-50 Plazos de la prioridad · `/usuarios/plazos`
- **Persona:** Diego (Gerencia de Administración)
- **Pasos:** 1. Usuarios → Plazos de la prioridad. 2. "Guardar" sin tocar nada. 3. Escribir 7 en "Urgente hasta". 4. "Guardar".
- **Esperado:** el aviso se va apenas se cambia algo, como en Mi perfil desde PU-127, y queda solo "Prioritario tiene que ser mayor que Urgente.".
- **Obtenido:** arriba sigue "No había cambios para guardar." después de escribir, junto al error "Prioritario tiene que ser mayor que Urgente.": la pantalla dice que no hay cambios mientras marca uno. Reproducido 2 veces. En Mi perfil (Laura), el aviso sí se va.
- **Evidencia:** `capturas/REG-01-1.png` · `capturas/REG-01-2.png` · script `REG/15-sincambios.mjs` (sin `PUT`) · origen: REG-01
- **Causa probable (opcional):** `PlazosPrioridadPantalla.tsx` no limpia el aviso de "sin cambios" al editar. El arreglo de PU-127 quedó solo en `PerfilPantalla.tsx`.
- **Regresión de:** no; arreglo incompleto de PU-127 (la pantalla de Plazos sumó el aviso con PU-123)

### PU-309 · Buscador: la pista ofrece buscar proveedores a quien no puede verlos
- **Severidad:** baja
- **Tipo:** mensaje
- **F-XX / pantalla:** F-03 · W-18 Buscador general · `/buscar`
- **Persona:** Laura y Sofía (Ingeniería de proyectos, Proveedores = sin acceso)
- **Pasos:** 1. Ingresar como Laura. 2. Tocar "Buscar". 3. Escribir "3071" (CUIT de un proveedor que existe).
- **Esperado:** la pista habla solo de lo que la persona puede encontrar ("Por N.º de presupuesto, cliente, planta o nombre del trabajo."), o el buscador explica que los proveedores no entran en su búsqueda.
- **Obtenido:** "Por N.º de presupuesto, cliente, planta, nombre del trabajo o proveedor (nombre o CUIT)." y, debajo, solo "OBRAS (0) · Sin resultados.": parece que el proveedor no existe. Reproducido 2 veces (Laura y Sofía). El grupo Proveedores sí se oculta bien.
- **Evidencia:** `capturas/REG-02-1.png` · `capturas/REG-02-2.png` · origen: REG-02
- **Causa probable (opcional):** `src/features/buscador/BuscadorPantalla.tsx:93`: `hint` fijo, sin mirar `permisos.proveedores`, desde `75bc80e`.
- **Regresión de:** no; arreglo incompleto de PU-044 (la pista con CUIT de `75bc80e`)

### PU-310 · "Ingreso trabado hasta las HH:MM" sigue el huso del navegador: con la PC en UTC dice 13:35 en vez de 10:35
- **Severidad:** baja
- **Tipo:** consistencia-con-documento
- **F-XX / pantalla:** F-58 · W-16 Usuarios · `/usuarios` (línea "Ingreso trabado hasta las…") y diálogo "Editar usuario" ("… no puede entrar hasta las …")
- **Persona:** Diego (Gerencia de Administración)
- **Pasos:**
  1. Tener un usuario con el ingreso trabado.
     - Acá, `bloqueo_ingreso.hasta` = `2026-11-19T13:35:29+00:00`, que son las 10:35 de Argentina.
     - Se puso con `page.route` sobre el cuerpo real de `GET /api/usuarios`, sin quemar códigos.
  2. Abrir Usuarios con el navegador en `timezoneId: 'UTC'` y después en `'Europe/Madrid'`.
  3. Mirar la línea del usuario y abrir su diálogo.
- **Esperado:** "· Ingreso trabado hasta las 10:35" en cualquier huso, como el historial después del arreglo de PU-301.
- **Obtenido:**
  - En la línea: Argentina "· Ingreso trabado hasta las 10:35", UTC "· Ingreso trabado hasta las 13:35" y Madrid "· Ingreso trabado hasta las 14:35".
  - En el diálogo, en UTC: "QA Lead R4 B no puede entrar hasta las 13:37: se equivocó varias veces con el código…".
  - Reproducido 3 veces. Con la PC en hora de Argentina se ve bien (el 18/11 lo vio B con un bloqueo real).
- **Evidencia:** `capturas/B-01-r4b-trabado-1-buenos_aires.png`, `B-01-r4b-trabado-1-utc.png`, `B-01-r4b-trabado-1-madrid.png`, `B-01-r4b-trabado-2-buenos_aires.png`, `B-01-r4b-trabado-2-utc.png`, `B-01-r4b-trabado-2-madrid.png`, `B-01-r4b-trabado-3-buenos_aires.png`, `B-01-r4b-trabado-3-utc.png`, `B-01-r4b-trabado-3-madrid.png` · `capturas/B-01-r4b-trabado-dlg-3-utc.png` (y `-buenos_aires`, `-madrid`) · `capturas/PU-209-r4-usuarios.png` (bloqueo real del 18/11, en hora local) · `GET /api/usuarios` → 200 · origen: VER-01
- **Causa probable (opcional):** `src/features/identidad/components/horaDelBloqueo.ts:3-15` usa `toLocaleTimeString`, `toLocaleDateString` y `toDateString` sin `timeZone`. El arreglo `efaf4b1` solo cambió `src/shared/dominio/fechas.ts`.
- **Regresión de:** no; arreglo incompleto de PU-301 (B-01, front `efaf4b1`)
