# Hallazgos · Prueba de usuario Semana 2 · Ronda 4 (corta) · 2026-11-25

Front `21dfe1a`, backend `3c69efc`. Numeración desde PU-901. Severidad visual del §4 del prompt de la ronda: **media** = ilegible, tapado, no tocable en el celular o información solo por color; **baja** = desalineado o espaciado.

| ID | Severidad | Título | Regresión |
|---|---|---|---|
| PU-901 | baja | En la ficha de la obra, en PC, la página baja sola unos segundos después de entrar y corta el título | PU-803 (`07966f5`) |
| PU-902 | baja | Tareas de mi gente y Obras · Terminadas: la pestaña activa vuelve a quedar cortada cuando llegan los contadores | arreglo incompleto de PU-803 |
| PU-903 | baja | Inicio en el celular: al tocar una pestaña que no cargó todavía, la página sube arriba de todo | probablemente no |

### PU-901 · En la ficha de la obra, en PC, la página baja sola unos segundos después de entrar y corta el título
- **Severidad:** baja
- **Tipo:** consistencia-con-documento
- **F-XX / pantalla:** F-21 · ficha de obra `/obras/:id` · PC 1366×768 (ventana de 1366×768 y de 1366×657, que es lo que
  queda de alto en Chrome con barras en una pantalla de 1366×768)
- **Persona:** Diego (Gerencia de Administración)
- **Pasos:**
  1. Diego entra a Obras › Confirmadas.
  2. Toca la obra 6 "9-983-2026 · QAS2R3-B Reemplazo integral…", o abre `/obras/5` directo.
  3. No toca nada y mira la parte de arriba durante unos segundos.
- **Esperado:** la ficha queda arriba, con el título a la vista, hasta que el usuario baje. Según el §3 de las
  novedades, en PC las pestañas no se mueven solas. El arreglo de PU-803 tenía que llevar la pestaña activa a la vista
  solo dentro de la tira deslizable.
- **Obtenido:**
  - Cuando termina de cargar, la página hace scroll vertical sola hasta que el borde de abajo de la tira (Pedidos /
    Documentos y contactos) toca el borde de abajo de la ventana:
    - con 1366×768: `scrollY` 0→15 en la obra 5 y 0→25 en la obra 6;
    - con 1366×657: 0→126 en la obra 5, 0→136 en la obra 6 y 0→28 en la obra 1. El título queda cortado arriba (`h1` en
      −27 px).
  - Pasa entre 2,2 y 6,6 s después de entrar, cuando llegan los datos: el usuario ya está leyendo.
  - Pasa igual con `prefers-reduced-motion` (salta sin animación).
  - Con 1366×1000, la tira entra entera y no se mueve (`scrollY` 0).
  - En Inicio, Pedidos, Obras y Usuarios no pasa: su tira está arriba, a la vista.
  - Reproducido 2 veces con carga directa (`06-ficha-salto` rep. 1 y 2) y 1 vez entrando desde el listado
    (`07-ficha-spa` rep. 2, obra 6). En la rep. 1 de `07`, la obra 5 todavía no había terminado de cargar a los 3,5 s:
    el back estaba lento.
- **Evidencia:** `capturas/A-01-2-spa-657-despues.png` (después del salto: el título de la obra cortado arriba, tira abajo) ·
  `A-01-2-ficha-obra-arriba-657.png` (la misma ficha llevada arriba a mano: así debería quedar) ·
  `A-01-1-ficha-obra-al-entrar-768.png`, `A-01-2-ficha-obra-al-entrar-{768,657}.png` · `logs/A-01.log`
- **Causa probable (opcional):** `src/shared/ui/useActivaALaVista.ts`: `activa.scrollIntoView({ block: 'nearest', inline: 'center' })`.
  El comentario dice que `'nearest'` "no mueve la página si la tira ya se ve". Pero si la tira está fuera de la
  ventana o se ve a medias, la mueve, y en la ficha de la obra la tira está debajo de la cabecera y de Presupuestos.
  Hace falta desplazar solo la tira, por ejemplo con `tira.scrollTo({ left: … })` en vez de `scrollIntoView`, o
  hacerlo solo cuando desborda.
- **Regresión de:** PU-803 (commit del arreglo de las tiras deslizables, `useActivaALaVista`, entre `0cbdc4d` y `21dfe1a`)
- **Origen:** A-01 (`borradores/a.md`, PC). **Causa verificada por el Lead:** `src/shared/ui/useActivaALaVista.ts` (commit `07966f5`, PU-803) llama a `scrollIntoView({ block: 'nearest', inline: 'center' })` sobre la pestaña activa en cada tira, también en PC y aunque la tira no se deslice; si la tira queda por debajo del borde de la pantalla (la ficha de la obra tiene una cabecera alta), el `block: 'nearest'` mueve la página en vertical. Se repite cuando termina de cargar la fuente (`document.fonts.ready`), lo que explica que pase segundos después de entrar.

### PU-902 · Tareas de mi gente y Obras · Terminadas: la pestaña activa vuelve a quedar cortada cuando llegan los contadores
- **Severidad:** baja
- **Tipo:** celular
- **F-XX / pantalla:** Inicio · Tareas de mi gente (`/?pestana=mi-gente`) y Obras · Terminadas (`/obras?estado=terminada`)
- **Persona:** Hernán (Jefatura de Proyectos)
- **Pasos:**
  1. En el celular (Pixel 7 o SE), entrar a `/?pestana=mi-gente`, o tocar "Tareas de mi gente" desde la tira de Pedidos.
  2. Esperar a que carguen los números de las pestañas.
  3. Mirar la tira de pestañas.
  4. Repetir con `/obras?estado=terminada`.
- **Esperado:** PU-803: "la activa queda a la vista al entrar y al cambiar".
- **Obtenido:** en el primer momento la activa sí queda a la vista: a los 1–2 s, "Tareas de mi gente" [230..396] en [16..396] y "Terminadas" [262..376]. Cuando llegan "(6)", "(4)" y "(0)", las pestañas se ensanchan, la tira no se vuelve a acomodar y la activa queda cortada a la derecha:
  - "Tareas de mi gente" [302..468] en [16..396] (Pixel) y [265..431] en [16..359] (SE); se lee "Tareas de r…" / "Tareas de mi g…";
  - "Terminadas (0)" [310..448] (Pixel) y [293..431] (SE); se lee "Termina…".
  - `scrollLeft` queda igual (188 de 260 posibles; 0 de 52).
  - Igual con y sin `reducedMotion`, en las 2 vueltas y los 2 equipos. Las otras 4 pantallas de B-03 (sin contadores) andan bien.
- **Evidencia:** `capturas/B-01-1.png` (mi gente, Pixel) · `B-01-2.png` (mi gente, SE) · `B-01-3.png` (Terminadas, Pixel) · `B-01-4.png` (Terminadas, SE) · `PU-803-r4-mi-gente-{celular,se}.png` · `logs/B-01.log`
- **Causa probable (opcional):** `shared/ui/useActivaALaVista.ts` solo vuelve a llevar la activa a la vista si cambia el elemento activo o la cantidad de pestañas (`ultima.current.activa === activa && pestanas === ...` → `return`). Los contadores cambian el ancho sin cambiar ninguna de las dos cosas. Podría escuchar el tamaño (`ResizeObserver`) o incluir el `scrollWidth` en la comparación.
- **Regresión de:** PU-803 (arreglo incompleto: es el mismo síntoma de B-03 en 2 de sus 6 pantallas)
- **Origen:** B-01 (`borradores/b.md`, celular). **Nota del Lead:** el hook solo vuelve a acomodar si cambia la pestaña activa o la cantidad de pestañas (`useActivaALaVista.ts`), no si cambia el ancho de las pestañas cuando llegan los contadores "(N)".

### PU-903 · Inicio en el celular: al tocar una pestaña que no cargó todavía, la página sube arriba de todo
- **Severidad:** baja
- **Tipo:** celular
- **F-XX / pantalla:** Inicio · tira "Mis tareas / Pedidos que pedí / Tareas que sigo / Tareas de mi gente" (`/`); una vez también Obras (`/obras?estado=…`) con `reduce`
- **Persona:** Hernán
- **Pasos:**
  1. En el celular, entrar a `/?pestana=mi-gente`.
  2. Bajar la página un poco, dejando la tira a la vista bajo la barra (45 px en Pixel, 71 px en SE).
  3. Tocar "Tareas que sigo" (primera vez en la sesión).
- **Esperado:** §3 de las novedades: "en el celular, al tocar una pestaña la página no salta".
- **Obtenido:**
  - A los 20 ms `scrollY` pasa de 45 a 0 (Pixel) o de 71 a 0 (SE). El contenido pasa a "Cargando lo que seguís…" y el documento se achica al alto de la pantalla (h2495 → h839; SE h2591 → h667). Cuando carga (h1309 / h1380), la página queda arriba y la tira bajó de y=100 a y=145 / 171.
  - Volviendo a una pestaña ya cargada no salta: y45 → y45, y71 → y71.
  - En 2 vueltas × 2 equipos, igual con y sin `reduce`. En `pestanas.txt` también con "Pedidos que pedí".
  - En Obras pasó una vez (v2, `reduce`: y36 → 0 con h839, mientras cargaba la lista).
  - El arreglo de PU-803 no es la causa: la única llamada al tocar es `scrollIntoView({block:'nearest', inline:'center'})`.
- **Evidencia:** `capturas/B-02-1.png` (Pixel) · `capturas/B-02-2.png` (SE; la página ya arriba después del toque) · `logs/B-02.log` (línea de tiempo `scrollY` / alto)
- **Causa probable (opcional):** al cambiar de pestaña, la lista se reemplaza por el estado "Cargando…" en lugar de mantener el contenido anterior mientras llega el nuevo, y el navegador recorta el `scrollY` al nuevo alto.
- **Regresión de:** no determinado. No es del commit de PU-803 (ver las llamadas), y sin git no puedo comparar con la ronda 3.
- **Origen:** B-02 (`borradores/b.md`, celular). **Regresión, según el Lead:** probablemente no. Los arreglos de esta ronda no tocaron la carga del Inicio (`git diff 0cbdc4d 21dfe1a`: solo `Pestanas.tsx` con el hook, que en el toque llama a `scrollIntoView` con `block: 'nearest'` sobre una tira que ya está a la vista). El salto viene de que el documento se achica al alto de la pantalla mientras dice "Cargando…".
