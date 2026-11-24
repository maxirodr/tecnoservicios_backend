# Borrador · Rol A · Listas, obra y calendario (PC) · Semana 2, ronda 2 · 2026-11-20

## Encabezado

- **Personas / sesiones:** Diego, Hernán, Dora, Laura, Pablo y Carla, con sus sesiones guardadas (`s2r2\sesiones\*.json`). Sofía no hizo falta. PC 1366×768, `es-AR`, hora de Argentina.
- **Ingresos reales gastados:** 0.
- **Código:** backend `9ca659a`, front `c816c89`. Front `http://localhost:5316`, API `:8340` (health ok durante toda la prueba).
- **Datos creados (prefijo `QAS2R2-A`):**
  - obras 2 `QAS2R2-A Obra uno caldera · 9-961-2026` y 3 `QAS2R2-A Obra dos tanque · 9-962-2026` (Diego). Las dos quedaron **Confirmadas**: no se cambió el estado de ninguna obra;
  - usuario 13 `QAS2R2-A Inactivo` (`qa-a-inactivo@empresa-ejemplo.com`, Ingeniería de proyectos, jefe Hernán). Lo dio de alta Diego y queda **inactivo** a propósito, con los pedidos abiertos 4 y 14;
  - pedidos 1–7 y 9–12 de los datos base. 1/5, 2/6 y 3/7 son duplicados porque el script de alta se corrió dos veces: son pedidos del mismo texto, propios y abiertos;
  - pedidos 19–22, 24, 25 (PU-601), 29–31 (alta normal sin obra) y 36–38 (PU-611);
  - "Tomar" desde la lista tomó los pedidos 3 (Laura) y 10 (Carla), que pasaron a En curso.
- **Restaurado:**
  - la obra de los pedidos 19, 20 y 21 volvió a la 2 después de la prueba de Editar;
  - Laura vuelve a seguir los pedidos 12 y 6 (`POST /seguir` → 200) después de "dejar de seguir".
  - En la base no se tocó nada: solo hubo lecturas.
- **Scripts:** `C:\Users\GREEN\AppData\Local\Temp\claude\4fe530b1c11d8e8d\f4efcdda-aa94-4434-aafd-2e4822d4d74b\scratchpad\s2r2\A\` (`01*-datos`, `02-pu614` … `16-extra`; salidas en `A\out\`; Excel descargados en `s2r2\A-dl\`).

## Tabla de verificación

| PU | Título | Estado | Pantallas | Evidencia | Nota |
|---|---|---|---|---|---|
| PU-601 | "+ Nuevo pedido en esta obra": el campo Obra queda en "Elegí…" | ✅ | `/obras/2` → `/pedidos/nuevo` | `capturas/PU-601-r2-{d1,d2,h1,h2}-5s.png`, `-listo.png`; red lenta `PU-601-r2-l1-5s.png`, `PU-601-r2-l2-5s.png` | **6 de 6** (2 de Diego y 2 de Hernán con red normal; 1 de cada uno con 2,5 s por pedido a la API). Hubo 3 opciones a 0,3 s y 5 cuando llegó la lista. En todas las corridas el campo mostró `value=2` "QAS2R2-A Obra uno caldera · 9-961-2026" a 0,3 / 1,5 / 5 s y, con la red lenta, también a 9 s. Elegir la obra dos y volver a la uno anduvo. "Crear pedido" llevó a `/pedidos/19, 20, 21, 22, 24, 25` con "Obra QAS2R2-A Obra uno caldera" y "Pedido creado. Se le avisó a Carla.", y la base tiene `obra_id = 2` en los 6. Sin errores de consola. |
| PU-602 | Carga por persona sin gente a cargo culpaba al permiso del sector | ✅ | `/pedidos/carga-por-persona` | `capturas/PU-602-r2-{1,2}-{laura,pablo,hernan,dora}.png` | Laura y Pablo (2 veces cada uno) ven "Carga de mi gente" con "Esta sección es para quien tiene gente a cargo.", "Muestra lo que tienen para esta semana las personas que dependen de vos. Lo tuyo está en Mis tareas." e "Ir a Mis tareas", que lleva a `/`. No aparecen el desplegable Sector, la leyenda "no es un puntaje" ni "gerencia", y el front no llama a la API: no hay 403 en la red. Hernán y Dora (2 veces) siguen viendo "Carga de mi gente · semana del 16/11/2026 al 22/11/2026" con el filtro, la tabla y la leyenda. "QAS2R2-A Inactivo (inactivo)" aparece primero. |
| PU-603 (PC) | Tocar la fila no abría el pedido | ✅ | `/` Mis tareas, `/?pestana=que-pedi`, `/?pestana=que-sigo`, `/?pestana=mi-gente`, `/pedidos`, `/pedidos/servicios-generales` | `capturas/PU-603-r2-laura-_*.png`, `PU-603-r2-hernan-_*.png`, `PU-603-r2-{1,2}-foco-*.png`, `PU-603-r2-{1,2}-tomar.png`, `PU-603-r2-1-dejar-de-seguir.png` · `A\out\13-{1,2}.txt`, `14-*.txt` | **80 de 80 clics por corrida, en 2 corridas.** Se tocó cada columna de 2 filas por lista (centro en la 1.ª, esquina inferior derecha en la 2.ª): Prioridad, Tarea, Obra, Solicitante / Propietario / Sector, Pedido, Entrega, Estado y Último movimiento. Cada clic abrió el `href` de esa fila, y el cursor es `pointer` sobre la fila. "Tomar" (Laura en el pedido 3, Carla en el 10) no abre el pedido: la URL sigue en `/` y la fila pasa a "En curso". "dejar de seguir" (Laura, pedidos 12 y 6) tampoco abre: la fila se va y la pestaña pasa a "Tareas que sigo (2)". Ordenar por Entrega (`?dir=desc`) y filtrar Sector = Compras (`?sector=9&dir=desc`, "37 de 56 pedidos con estos filtros") siguen andando; tocar una fila filtrada abre `/pedidos/17`, y Atrás vuelve con el mismo filtro. Tab: **1 parada por fila** (el link) en las 6 listas, más el botón "Tomar" o "dejar de seguir" cuando la fila lo tiene. El foco se marca en la fila entera (`outline: solid 2px`) y Enter abre el pedido (`/pedidos/3`, `/pedidos/42`). Sin errores de consola. |
| PU-611 | "Pedido creado. Se le avisó a …" volvía con F5 o Atrás | ✅ | `/pedidos/nuevo` → `/pedidos/{id}` | `capturas/PU-611-r2-{a1,a2,a3}-{1-creado,2-f5,3-adelante}.png` · `A\out` (consola de `06-pu611.mjs`) | 3 de 3 (Laura 2, Diego 1; pedidos 36, 37 y 38). Al crear, el pedido muestra "Pedido creado. Se le avisó a Carla.". Después no vuelve en ningún paso: ni con F5, ni con Inicio → Atrás, ni con Adelante → Atrás, ni con Atrás hasta el formulario → Adelante al pedido. |
| PU-612 | Faltaba "(inactivo)" en el Excel y en el calendario "Mi gente" | ✅ | `/pedidos?sector=5` → Exportar a Excel · `/calendario?de=mi_gente` (mes, semana actual, semana del 23 al 29-nov) | `capturas/PU-612-r2-{1,2}-tabla.png`, `PU-612-r2-{1,2}-calendario-{hernan,dora}.png` · `A\out\xlsx-{1,2}.json` | En 2 descargas de Hernán (`pedidos-2026-11-20.xlsx`, abiertas y leídas), la columna D "Propietario" dice "QAS2R2-A Inactivo (inactivo)" en las filas 3 y 4 (pedidos I2 e I1). Los activos no llevan la marca: "Laura", "Sofía", "Ingeniería de proyectos (sector)". En el calendario Mi gente, Hernán y Dora, 2 veces cada uno, ven "QAS2R2-A Inactivo (inactivo) · QAS2R2-A I2 Inactivo hoy sin obra" el 20-nov y "QAS2R2-A Inactivo (inactivo) · QAS2R2-A I1 Inactivo semana que viene" el 25-nov. |
| PU-613 | El calendario volvía a "Mes · Mis tareas" | ✅ (ver A-01 y A-02 en la regresión) | `/calendario` | `capturas/PU-613-r2-{1,2}-{1-antes,2-atras}.png` | 2 de 2 con Hernán. Hizo Mi gente → Semana → siguiente → Obra "QAS2R2-A Obra uno caldera" (`/calendario?vista=semana&desde=2026-11-23&de=mi_gente&obra=2`), abrió el pedido 14 y volvió con Atrás. Vuelve igual: "Semana del 23 al 29-nov", [Semana, Mi gente], la misma obra y el mismo pedido; con F5 también. En Mes · Mi gente vuelve igual después de abrir un pedido, y el mes siguiente queda en `?desde=2026-12-01&de=mi_gente`. |
| PU-614 | Sin obras En ejecución no aparecía la fila "Servicios generales" | ✅ | `/obras` (En ejecución) | `capturas/PU-614-r2-{1,2}-{diego,hernan,laura}.png` | Con la base recién reseteada no había ninguna obra En ejecución ("En ejecución (0)", 3 Confirmadas, `contadores.en_ejecucion: 0`), así que no hubo que cambiar el estado de nada. Diego, Hernán y Laura, 2 veces cada uno, ven "No hay obras en esta pestaña" y debajo la fila "Servicios generales · pedidos que no son de una obra" con la cantidad: 8, 9 o 10, según los pedidos que iban creando los otros roles; coincide con `meta.pedidos_sin_obra_abiertos`. Tocar la fila abre `/pedidos/servicios-generales`. Con texto en el buscador (`zzz-no-existe`) la fila no aparece ("No encontramos obras con esa búsqueda", como dice RN-OBR-08), y en Confirmadas tampoco. |

## Regresión

| Qué | Resultado | Evidencia / nota |
|---|---|---|
| Campo Obra en el **alta normal** (`/pedidos/nuevo`) | ✅ 3 de 3: Diego, Hernán y Diego con la red lenta | `PU-601-r2-reg-{r1,r2,r3l}-alta-vacia.png`. Arranca en "Elegí…". Crear con la obra vacía no envía y dice "Elegí la obra o 'Sin obra'.". Elegir la obra dos, buscar "uno" (la elegida se queda), elegir la uno y borrar la búsqueda: queda la uno. Elegir "Sin obra / Servicios generales" y crear guarda el pedido con "Obra Sin obra · Servicios generales" (pedidos 29, 30 y 31). |
| Campo Obra en **Editar** | ✅ 3 de 3: Diego en el pedido 19, Hernán en el 21, Diego en el 20 con la red lenta | `PU-601-r2-reg-{r1,r2,r3l}-editar.png`. `/pedidos/{id}/editar` muestra "QAS2R2-A Obra uno caldera · 9-961-2026" a 0,3 / 1,5 / 5 s; con la red lenta, desde que el formulario aparece. Cambiar a la obra dos y "Guardar" deja "Obra QAS2R2-A Obra dos tanque" en el detalle. Al volver a Editar aparece la dos, y volver a la uno también guarda bien. |
| Listas: botones de la fila, filtros, orden, una parada de Tab | ✅ | Ver PU-603 arriba. Los filtros de `/pedidos` (casilla, fechas) tienen foco visible (`outline solid 2px`). |
| Calendario con la dirección cambiada a mano | ⚠️ **A-01**, **A-02** | `A\out\09-1.txt`. Caen en algo razonable, sin errores: `vista=rara`, `vista=SEMANA`, `desde=abc`, `desde=99999-01-01` y `de=todos` muestran el mes actual · Mis tareas; `obra=-1` y `obra=abc` muestran "todas". `desde=2026-02-31` corre al 3-mar, "Semana del 2 al 8-mar". `desde=2026-13-01` muestra "Enero 2027". `obra=99999` muestra "La obra elegida" con "No hay pedidos en este mes." y "Probá con "Obra: todas" para ver el resto.". `obra=sin_obra`, `desde` repetido y `desde` que no es lunes también andan. Laura y Pablo con `de=mi_gente` ven Mis tareas, sin el botón Mi gente. **En semana con un día o un mes imposible, la pantalla se rompe (A-01).** Con años fuera de rango el título no tiene sentido (A-02). |
| Fila "Servicios generales" con búsqueda y en otras pestañas | ✅ | Ver PU-614. |
| Errores de la API / consola en las pantallas probadas | ✅ | Ningún 4xx ni 5xx en lo verificado, salvo el 422 de A-02. Tampoco hubo errores de consola, salvo el de A-01. No hay `errors-2026-11-20.log`. |

## Hallazgos nuevos

### A-01 · Calendario en semana con un día imposible en la dirección: se va la barra y dice "Revisá tu conexión"
- **Severidad:** baja
- **Tipo:** mensaje
- **F-XX / pantalla:** F-11 · W-03 Calendario · `/calendario?vista=semana&desde=…`
- **Persona:** Hernán (Jefatura de Proyectos), Laura (Ingeniería de proyectos) y Dora (Dirección)
- **Pasos:** 1. Cambiar a mano la dirección del calendario en vista semana con un día o un mes que no existe: `/calendario?vista=semana&desde=2026-11-99` (también pasa con `2026-00-10`, `2026-99-99` y `9999-99-99`). 2. Tocar "Reintentar". 3. Atrás.
- **Esperado:** como en el resto de los valores inválidos de la dirección (y como en vista Mes con la misma fecha, que cae en noviembre 2026), caer en algo razonable, por ejemplo la semana de hoy, con la barra visible. Si no se puede, un mensaje que diga qué pasó. Lo pide el criterio del §0: "nunca … pantalla en blanco" y "entiende siempre qué pasó y qué hacer". Lo pide también la regresión de la ronda 2: "calendario con la dirección cambiada a mano (valores inválidos)".
- **Obtenido:** la pantalla entera se reemplaza, sin la barra (no queda Inicio, Pedidos ni Calendario), por "No pudimos cargar esta información" / "Revisá tu conexión y volvé a intentar. Si sigue pasando, avisá a sistemas." / "Reintentar". "Reintentar" muestra lo mismo; solo sale con Atrás del navegador. La conexión está bien: no hubo ningún pedido a la API. Consola: "React Router caught the following error during render RangeError: Invalid time value". Reproducido con Hernán (`9999-99-99`, `2026-99-99`, `2026-11-99`, `2026-00-10`), Laura y Dora (`2026-11-99`, con Reintentar y Atrás). En vista Mes, las mismas fechas no rompen. Con `2026-11-31` (día 31 de un mes de 30) tampoco: corre a la semana del 30-nov.
- **Evidencia:** `capturas/A-01-1.png` (Hernán) · `capturas/A-01-2.png` (Laura) · `capturas/A-01-3.png` (Dora) · `logs/A-01.log` · `A\out\09-1.txt` (scratchpad)
- **Causa probable (opcional):**
  - `src/features/pedidos/components/calendario/calendarioUrl.ts:20,31`: la regex `FECHA` solo mira la forma `AAAA-MM-DD`, no que la fecha exista.
  - `lunesDe` → `sumarDias` → `aDia` (`fechasCalendario.ts:41`) hace `new Date('2026-11-99T00:00:00Z').toISOString()`, que tira `RangeError` durante el render.
  - En vista Mes no rompe porque `primeroDelMes` usa `Date.UTC`, que normaliza la fecha.
  - El `errorElement` de la ruta muestra el texto genérico de conexión.
- **Regresión de:** no (aparece con el arreglo de PU-613, que lleva la fecha a la dirección)

### A-02 · Calendario con años fuera de rango en la dirección: títulos sin sentido y "Escribí la fecha como AAAA-MM-DD."
- **Severidad:** baja
- **Tipo:** mensaje
- **F-XX / pantalla:** F-11 · W-03 Calendario · `/calendario?desde=…`
- **Persona:** Laura (Ingeniería de proyectos), Hernán y Dora
- **Pasos:** 1. `/calendario?desde=9999-99-99`. 2. `/calendario?desde=0000-00-00`. 3. `/calendario?vista=semana&desde=9999-12-31`.
- **Esperado:** que una fecha absurda en la dirección caiga en el mes o la semana de hoy, como `desde=abc` o `desde=99999-01-01`.
- **Obtenido:**
  1. El título dice "Julio 10", con "No hay pedidos en este mes.".
  2. El título dice "Diciembre 1899".
  3. El título dice "Semana del 27-dic al 1-" y abajo "No pudimos cargar esta información" / "Escribí la fecha como AAAA-MM-DD." / "Reintentar": le pide a la persona que escriba una fecha, pero ella no escribió ninguna. `GET /api/pedidos/calendario?desde=9999-12-27&hasta=%2B010000-01&de=mias` → 422.

  La barra y los controles siguen y "‹" / "Mes" sacan de ahí: no se pierde nada. Reproducido 2 veces: el caso 3 con Laura y Dora, y los casos 1 y 2 con Hernán y Laura.
- **Evidencia:** `capturas/A-02-1.png` ("Julio 10") · `capturas/A-02-2.png` ("Diciembre 1899") · `capturas/A-02-3.png` (422) · `logs/A-02.log`
- **Causa probable (opcional):** la misma validación de A-01 (`calendarioUrl.ts`). El año 0000 y los años de 5 cifras que salen de normalizar con `Date.UTC` pasan a `tituloMes` y a la consulta tal cual (`hasta=+010000-01`).
- **Regresión de:** no (aparece con el arreglo de PU-613)

> A-01 y A-02 tienen la misma causa (la fecha de la dirección no se valida), pero a la persona le pasan cosas distintas: en A-01 pierde la pantalla con un mensaje que culpa a la conexión, y en A-02 ve títulos raros con la pantalla andando. Si QC prefiere uno solo, se fusionan en A-01.

## §6.1 del backlog (lo que toca este rol)

| Punto "sí o sí" | Resultado |
|---|---|
| Cargar un pedido con obra o "Servicios generales" (HU-026…) | ✅ Funciona desde la obra, en el alta normal y con "Sin obra", también con la red lenta (PU-601). |
| Mirar la planilla de la obra y la completa, filtrar (HU-042, HU-054) | ✅ "+ Nuevo pedido en esta obra" con la obra puesta. En Todos los pedidos, filtrar, ordenar y abrir con la fila entera anda, y Atrás conserva los filtros. El Excel sale con "(inactivo)". |
| Corregir una fecha o un link: Editar (HU-031), en lo que toca a la Obra | ✅ Editar conserva la obra y el cambio de obra se guarda. |
| Ver lo mío en una sola lista (HU-038, HU-039) | ✅ Mis tareas, Pedidos que pedí y Tareas que sigo abren el pedido desde toda la fila; "Tomar" y "dejar de seguir" funcionan sin abrir. |

## No probado

- **PU-601 y PU-603 en el celular:** son del rol C.
- **La fila de Servicios generales con obras En ejecución:** como no había ninguna, no hubo que pasar obras de estado. El caso con obra ya estaba ✅ en la ronda 1 (`F-15-listado-en-ejecucion-servicios-generales-pc.png`).
- **PU-612 con el navegador en UTC:** no lo pide la ronda.
- **"Tareas de mi gente":** los clics se probaron solo en la primera tabla (la del primer grupo de personas). Las otras usan el mismo componente.

---
Rol A (Listas, obra y calendario · PC) · Claude Opus 5.5 · 2026-11-20 · 7 de 7 PU verificados ✅ · 2 hallazgos nuevos bajos (A-01, A-02) · 0 ingresos reales.
