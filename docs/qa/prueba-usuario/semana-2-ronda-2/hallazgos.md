# Hallazgos · Prueba de usuario Semana 2 (Pedidos y avisos) · Ronda 2 (corta) · Tecnoservicios (NS-2026-025)

- **Fecha:** 2026-11-20 (A, B y parte de C) y 2026-11-24 (resto de C, tras el corte), máquina con Laragon `C:\`
- **Entorno:** base `tecnoservicios_prueba_usuario` · front `http://localhost:5316` (`staging` @ `c816c89`, worktree `tecnoservicios_frontend_qa_s2r2`) · API `http://localhost:8340` (`staging` @ `9ca659a`, arreglos de back en `fb55537`; worktree `tecnoservicios_backend_qa_s2r2`, rama `qa/prueba-usuario-semana-2-ronda-2`)
- **Alcance:** solo lo nuevo de la ronda 2. El estado de los 16 PU de la ronda 1 (PU-601…PU-616, los 16 ✅) está en `verificacion.md`.
- **Conteo:** 3 hallazgos · bloqueante 0 · alta 0 · media 0 · **baja 3**. Ninguno rompe algo que antes anduviera. **PU-701** aparece con el arreglo de PU-613 (`c816c89`): el calendario lee ahora la fecha de la dirección y no la valida.
- **Próximo número libre:** **PU-704**
- **Orden:** por severidad y, dentro de cada una, por rol (A, B, C). Al final de cada **Evidencia**, `origen:` dice de qué borrador sale. Las capturas no se renombraron y los borradores no se modificaron.

## ID del borrador → PU-7XX

| ID del borrador | Rol | PU nuevo | Severidad final | Capturas y logs (nombre real) |
|---|---|---|---|---|
| A-01 + A-02 | A · Listas, obra y calendario (PC) | PU-701 | baja (sostenida) | `A-01-1.png`, `A-01-2.png`, `A-01-3.png`, `A-02-1.png`, `A-02-2.png`, `A-02-3.png` · `logs/A-01.log`, `logs/A-02.log` · `A\out\09-1.txt` (scratchpad) |
| B-01 | B · Avisos, recorrido y diálogos (PC) | PU-702 | baja (sostenida) | `B-01-1.png`, `B-01-2.png` · `logs/B-01.log` · `B\out\08-{1,2}.txt`, `08b-{1,2}.txt` (scratchpad) |
| C-01 | C · Celular y teclado | PU-703 | baja (sostenida) | `C-01-1.png`, `C-01-2.png`, `C-01-3.png`, `C-01-4.png` · `logs/C-01.log` · `C\out\out-06-pc-{1,2}.txt`, `out-04-pc-{2,3}.txt` (scratchpad) |
| B obs. 5.1: orden del recorrido cuando se actúa muy rápido | B | — (observación) | — | `B\out\04-1.txt` |
| B obs. 5.2: "…, que pidió Laura" en el aviso al jefe de un pedido de Laura a Laura | B | — (nota menor) | — | `B\out\06.txt` |

## Consolidación (QC)

- **Deduplicado: A-01 + A-02 → PU-701.** Los dos son el mismo error en la misma pantalla. La fecha que viene en la dirección del calendario no se valida: `calendarioUrl.ts:20,31` solo mira que tenga la forma `AAAA-MM-DD`, y no que el día exista ni que el año tenga sentido. Un solo arreglo, que valide la fecha y si no sirve use la de hoy, resuelve las dos cosas. El §4 pide "un hallazgo = un problema", y A también dejó la fusión a criterio de QC. En el bloque quedan los dos síntomas por separado: la pantalla rota y los títulos raros. El tipo pasa de "mensaje" a **funcional**: la causa es una fecha sin validar que rompe el render, y el texto equivocado es una consecuencia.
- **Severidades (QC no cambió ninguna). La razón de cada una:**
  - **PU-701 queda en baja.** Lo más serio es el síntoma de A-01: la pantalla pierde la barra y culpa a la conexión ("Revisá tu conexión…"). Por eso se pensó en media ("mensaje confuso"). No sube porque **solo pasa si alguien inventa la fecha en la dirección**: el sistema nunca arma un link con una fecha imposible, y un link copiado o guardado tiene fechas válidas. Es el mismo caso que **PU-024** de la Semana 1 (`/proveedores/abc` escrito a mano muestra algo que no corresponde), que quedó **baja**. Tampoco se pierde nada: Atrás sale de ahí. En PU-602 sí se subió a media un mensaje equivocado, pero esa dirección era real (la sección existe y alguien puede tener el link). Si apareciera un camino real hasta una fecha imposible, PU-701 sube a media.
  - **PU-702 queda en baja (en el borde).** Se revisó contra la regla de bloqueante ("pierde datos"):
    - **No se pierde nada guardado.** El comentario enviado queda en la base, y QC lo confirmó: en los pedidos 41 y 42 quedó "QAS2R2-B debug primero" y no hay ningún "segundo". Lo que se borra es un texto **todavía no enviado**, que la persona escribió en los ~3 s que dura el envío con la red lenta.
    - La Semana 1 trató así lo escrito y no guardado. **PU-114** ("Cancelar" y Escape tiran lo escrito sin preguntar) quedó **baja**, y **PU-115** (el envío en vuelo le gana a lo que hace la persona) también.
    - **PU-106** fue **media** por otra razón: la sesión vence en un camino común y la persona queda trabada, sin salida y sin borrador.
    - Para que pase PU-702 tienen que darse dos cosas a la vez: la red lenta (2,5 s por pedido) y escribir un segundo comentario antes de que termine el primero. Es raro, y en el peor caso se pierde un texto corto que la persona tiene a la vista.
    - Queda en el borde por una sola razón: no hay ningún aviso. Si se ve con la red real del celular en obra, sube.
  - **PU-703 queda en baja.** El foco se pierde después de una acción que sale bien, y con Tab se sigue. Es el mismo patrón que **PU-136** (ronda 2: el foco se caía del botón al guardar) y **PU-212** (ronda 3: "Pegar link" deja el foco en la nada), los dos **baja**, y más leve que esos, porque acá no sale de un diálogo. **PU-502** (ronda 5, una parada de Tab de más) también fue baja.
- **"Regresión de":** PU-701 la introduce el arreglo de PU-613 (`c816c89`). No rompe algo que anduviera: antes el calendario ignoraba la dirección. PU-702 viene de antes, porque el `setTexto('')` en `onSuccess` ya estaba. PU-703 también: "Tomar" y "dejar de seguir" ya eran botones aparte en la ronda 1.
- **Lo "esperado" del §1 / `dudas.md` no se reportó como hallazgo.**
  - El aviso al jefe cuando Laura completa un pedido que se pidió a ella misma (RN-AVI-12, DU-13) no es hallazgo.
  - Tampoco lo son el comentario de 2001 caracteres que se guarda (el máximo es 5000, `pedidos.md` §4.1) ni Reasignar con solo espacios, que se manda sin comentario porque el campo es opcional.
  - Laura y Pablo con `de=mi_gente` ven Mis tareas sin el botón Mi gente: es lo esperado.
- **Observaciones que QC decidió NO reportar:**
  - **Orden del recorrido cuando se actúa muy rápido (B obs. 5.1):** "Sistema avisó a … (tomado)" quedó después del comentario porque el aviso se crea en la cola 1–3 s más tarde. Es su hora real, y con ritmo de persona sale en orden. No es hallazgo.
  - **"Laura completó …, que pidió Laura" (B obs. 5.2):** el texto suena repetido solo en un pedido que alguien se pide a sí mismo. Es correcto según RN-AVI-12 y hay 1 sola observación, sin captura propia. Queda como nota menor en `reporte.md` §9.
- **Evidencia:** las 148 capturas y los 4 logs de `capturas/` y `logs/` se listaron. **Todas las capturas que citan los hallazgos existen**, y los 4 logs también. Las salidas del scratchpad citadas (`A\out\09-1.txt`, `B\out\08*.txt`, `C\out\out-04-pc-*.txt`, `out-06-pc-*.txt`) existen y las revisó QC. Ningún hallazgo baja a "sin evidencia".
- **Confirmado en la base (solo lectura):** en los comentarios de los pedidos 40–46 están "primero", "doble clic", los de largo máximo y los de Reasignar, pero ninguno de los textos escritos durante el envío (PU-702).
- Sin datos sensibles: ni códigos de ingreso, ni tokens, ni cookies en los borradores ni acá.

### PU-701 · Calendario con una fecha imposible en la dirección: en semana se rompe la pantalla y dice "Revisá tu conexión"; con años fuera de rango, títulos sin sentido y "Escribí la fecha como AAAA-MM-DD."
- **Severidad:** baja
- **Tipo:** funcional
- **F-XX / pantalla:** F-11 · W-03 Calendario · `/calendario?vista=semana&desde=…` y `/calendario?desde=…`
- **Persona:** Hernán (Jefatura de Proyectos), Laura (Ingeniería de proyectos) y Dora (Dirección)
- **Pasos:**
  1. **Día o mes imposible, vista semana:** cambiar a mano la dirección a `/calendario?vista=semana&desde=2026-11-99`. Pasa lo mismo con `2026-00-10`, `2026-99-99` y `9999-99-99`. Después tocar "Reintentar" y, por último, Atrás.
  2. **Años fuera de rango:** `/calendario?desde=9999-99-99`, después `/calendario?desde=0000-00-00` y después `/calendario?vista=semana&desde=9999-12-31`.
- **Esperado:** que caiga en algo razonable, como pasa con el resto de los valores inválidos de la dirección: `desde=abc` y `desde=99999-01-01` muestran el mes de hoy, y en vista Mes la misma `2026-11-99` cae en noviembre de 2026. Por ejemplo, la semana o el mes de hoy, con la barra visible. Lo pide el §0: "nunca … pantalla en blanco" y "entiende siempre qué pasó y qué hacer". También lo pide la regresión de esta ronda: "calendario con la dirección cambiada a mano (valores inválidos)".
- **Obtenido:**
  1. **Paso 1:** la pantalla entera se reemplaza, **sin la barra** (no quedan Inicio, Pedidos ni Calendario), por "No pudimos cargar esta información" / "Revisá tu conexión y volvé a intentar. Si sigue pasando, avisá a sistemas." / "Reintentar".
     - "Reintentar" muestra lo mismo: solo se sale con Atrás.
     - La conexión está bien: no hubo ningún pedido a la API.
     - En la consola: "React Router caught the following error during render RangeError: Invalid time value".
     - Con `2026-11-31` no pasa: corre a la semana del 30-nov.
     - Reproducido con Hernán (4 fechas), Laura y Dora.
  2. **Paso 2:** los títulos no tienen sentido: "Julio 10" (con "No hay pedidos en este mes.") y "Diciembre 1899". En semana, el título es "Semana del 27-dic al 1-" y debajo dice "No pudimos cargar esta información" / "Escribí la fecha como AAAA-MM-DD." / "Reintentar".
     - Le pide a la persona que escriba una fecha, pero ella no escribió ninguna.
     - `GET /api/pedidos/calendario?desde=9999-12-27&hasta=%2B010000-01&de=mias` → 422.
     - La barra y los controles siguen, y "‹" o "Mes" sacan de ahí.
     - Reproducido 2 veces: el caso de semana con Laura y Dora, y los otros dos con Hernán y Laura.
- **Evidencia:** `capturas/A-01-1.png` (Hernán), `capturas/A-01-2.png` (Laura) y `capturas/A-01-3.png` (Dora): pantalla sin barra · `capturas/A-02-1.png` ("Julio 10") · `capturas/A-02-2.png` ("Diciembre 1899") · `capturas/A-02-3.png` (422) · `logs/A-01.log` · `logs/A-02.log` · `A\out\09-1.txt` (scratchpad) · origen: A-01 + A-02 (`borradores/a.md`)
- **Causa probable (opcional):**
  - `src/features/pedidos/components/calendario/calendarioUrl.ts:20,31`: la regex `FECHA` (`/^\d{4}-\d{2}-\d{2}$/`) valida la forma y no que la fecha exista.
  - En semana, `lunesDe` → `sumarDias` → `aDia` (`fechasCalendario.ts`) hace `new Date('2026-11-99T00:00:00Z').toISOString()`, que tira `RangeError` durante el render.
  - En Mes, `primeroDelMes` normaliza con `Date.UTC`, y el año 0000 o uno de 5 cifras pasa tal cual a `tituloMes` y a la consulta (`hasta=+010000-01`).
  - Además, el `errorElement` de la ruta muestra el texto de conexión para **cualquier** error de render: conviene que diga otra cosa cuando no es de la red.
- **Regresión de:** PU-613 / `c816c89`. No rompe nada que anduviera: lo introduce el arreglo, que lleva la fecha a la dirección.

### PU-702 · Con la red lenta, lo que se escribe en el comentario mientras se envía el anterior se borra solo, sin aviso
- **Severidad:** baja
- **Tipo:** funcional
- **F-XX / pantalla:** F-30 · W-09 Detalle del pedido, "Escribir un comentario" · `/pedidos/{id}`
- **Persona:** Laura (Ingeniería de proyectos)
- **Pasos:** 1. Con la red lenta (2,5 s por pedido a la API), abrir un pedido propio. 2. Escribir un comentario y tocar "Comentar": el botón pasa a "Enviando…". 3. Mientras dice "Enviando…", escribir el comentario siguiente en la misma caja, que sigue habilitada. 4. Esperar a que termine el envío.
- **Esperado:** que no se pierda lo que la persona escribió después, o que la caja no deje escribir mientras se envía. §0: "entiende siempre qué pasó".
- **Obtenido:** el primer comentario se guarda bien. Unos 3 s después del 201, la caja queda vacía y el segundo texto se pierde sin ningún mensaje ("QAS2R2-B debug segundo escrito durante el envío", 47 caracteres). Si se toca "Comentar" otra vez, sale "Escribí el comentario.". Reproducido 4 veces; en la base queda solo el primero.
- **Evidencia:** `capturas/B-01-1.png` · `capturas/B-01-2.png` · `logs/B-01.log` (caja con 47 caracteres hasta los 24,4 s y vacía a los 25,4 s) · `POST /api/pedidos/41/comentarios` → 201 a los 22,4 s · `B\out\08-{1,2}.txt`, `08b-{1,2}.txt` (scratchpad) · base: pedidos 41 y 42 con solo "QAS2R2-B debug primero" · origen: B-01 (`borradores/b.md`)
- **Causa probable (opcional):** `src/features/pedidos/components/detalle/Comentarios.tsx:32-34`: `onSuccess` llama a `setTexto('')` y vacía la caja con lo que haya en ese momento, no con lo que se envió. Además, el `textarea` no se deshabilita mientras `comentar.isPending`.
- **Regresión de:** no. El `setTexto('')` en `onSuccess` ya estaba; se vio ahora, al probar PU-615 con la red lenta.

### PU-703 · Con teclado, después de "Tomar" o "dejar de seguir" en la lista, el foco se pierde (queda en la página)
- **Severidad:** baja
- **Tipo:** accesibilidad
- **F-XX / pantalla:** F-04 · Inicio · Mis tareas `/` ("Tomar", F-28) · F-07 · Tareas que sigo `/?pestana=que-sigo` ("dejar de seguir", F-38) · PC 1366×768
- **Persona:** Carla (Compras) y Hernán (Jefatura de Proyectos), solo con teclado
- **Pasos:** 1. Carla, en Mis tareas, con un pedido del sector sin persona ("QAS2R2-C F foco-1 tomar"): Tab hasta "Tomar · QAS2R2-C F foco-1 tomar" y Enter. 2. Mirar dónde queda el foco y apretar Tab. 3. Hernán, en Tareas que sigo: Tab hasta "dejar de seguir · QAS2R2-C C2 Laura a Carla sin obra" y Enter. 4. Mirar dónde queda el foco y apretar Tab.
- **Esperado:** que después de la acción el foco quede en un lugar con sentido: en la misma fila, que ahora dice "En curso", o en la fila siguiente si la fila se va. Que se anuncie qué pasó y que el foco siga visible. Es el mismo criterio de PU-136 y PU-212 de la Semana 1: el foco no se pierde cuando el botón desaparece.
- **Obtenido:** la acción anda. El pedido pasa a `en_curso` o la fila se va, y el pedido no se abre. Pero el foco queda en `body`: no hay anillo en ningún lado y no se anuncia nada. Con Tab, después de "Tomar" el foco salta a otra fila más abajo ("QAS2R2-C 601 se-2 hernan desde la obra", con la página en `scrollY=1039`). Después de "dejar de seguir", el Tab siguiente vuelve a `body`. Reproducido: "Tomar" 4 veces y "dejar de seguir" 5 veces.
- **Evidencia:** `capturas/C-01-1.png` y `capturas/C-01-3.png` (Mis tareas después de "Tomar", sin foco visible) · `capturas/C-01-2.png` y `capturas/C-01-4.png` (Tareas que sigo después de "dejar de seguir") · `logs/C-01.log` · `C\out\out-06-pc-{1,2}.txt`, `out-04-pc-{2,3}.txt` (scratchpad) · origen: C-01 (`borradores/c.md`)
- **Causa probable (opcional):** `src/features/pedidos/components/inicio/AccionFila.tsx:17-31,34-73`. `BotonFila` usa `Button` con `cargando`, y cuando termina, el botón se desmonta: "Tomar" porque el pedido ya no es del sector, y con "dejar de seguir" se va la fila entera. Nada mueve el foco y no hay un `aria-live` con el resultado.
- **Regresión de:** no. Es el mismo patrón que PU-136 y PU-212 de la Semana 1, en una pantalla nueva.
