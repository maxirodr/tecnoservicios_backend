# Borrador CEL · Ronda 3 — celular y accesibilidad

- **Fecha:** 2026-11-18 · front `1914ea8` (main).
- **Sesiones usadas:** `diego.json` (Gerencia Adm.: obras, proveedores, usuarios, sectores, permisos, plazos, perfil), `hernan.json` (Jefatura: alta/edición de obra, documentos), `carla.json` (Compras: proveedores, presupuesto por rubro). Nunca se tocó "Cerrar sesión".
- **Ingresos reales gastados:** 0.
- **Dispositivos:** Pixel 7 (`devices['Pixel 7']`, 412×839 útil) · iPhone SE forzado 375×667 (isMobile, hasTouch) · PC 1366×768 con teclado.
- **Red lenta:** 2,5 s en cada POST/PATCH (`page.route` → `fallback`), los GET sin demora.
- **Datos creados (QA3-CEL):** obras `/obras/13` "QA3-CEL Obra foco 1" (2-961-2026, planta cambiada a "QA3-CEL Planta 1") y `/obras/14` "QA3-CEL Obra foco 3" (2-963-2026); documentos "QA3-CEL Documento teclado 3/4" en la obra 13; proveedores 11, 12, 13, 14 y 15 "QA3-CEL Ferretería Foco 1 / Foco 2 / SE / P7 / SE 2". Datos de QA3-A/B/C solo se miraron (diálogos abiertos y cerrados sin cambios).
- **Scripts y salidas:** `scratchpad\qa3\CEL\` (`cel.mjs` medición por pantalla, `foco-dialogos.mjs` trampa de Tab/Escape/foco de vuelta y guardado, `foco-obra.mjs`, `foco-doc.mjs`, `foco-link.mjs`, `cel-guardar.mjs`, `foco-visible.mjs`; salidas `out-*.txt`).
- **Consola y red:** limpias en todas las corridas (sin errores JS, warnings de React ni 4xx/5xx). `errors-2026-11-18.log`: nada de esta franja.

## Verificación

| PU | título corto | estado | pantallas revisadas | evidencia | nota |
|---|---|---|---|---|---|
| PU-134 | Controles < 44 px: listas de alcance, logo, "Saltar al contenido" | ✅ | Tabla de permisos y encabezado, Pixel 7 e iPhone SE | `PU-134-r3-se-1.png`, `PU-134-r3-p7-1.png` · `out-cel-se.txt`, `out-cel-p7.txt` | Las 57 listas de alcance miden 105×44 (16 px), las de nivel 113/112×44; "Saltar al contenido" 156×44; el logo no aparece entre los controles chicos en ningún celular. Tabla deslizable con "Deslizá para ver más →". |
| PU-135 | Sectores no pasa a tarjetas en el celular | ✅ | `/usuarios/sectores` + diálogos agregar, renombrar y sacar, en los dos celulares (2 corridas por celular) | `PU-135-r3-se-1.png`, `PU-135-r3-se-2.png`, `PU-135-r3-p7-1.png` | Tarjetas (`tabla-marco--tarjetas`), página 375/375 y 412/412 sin scroll lateral; "sacar" entero (L158 R224, 66×44) en los dos. Detalle sin reportar: el separador "·" entre "renombrar" y "sacar" queda suelto, más arriba de la línea de los links. |
| PU-136 | Mientras guarda, el foco se cae y no se dice "Guardando…" | ⚠️ | Nuevo proveedor (2 veces), Agregar documento (2), Editar obra, Nueva obra (2), en PC con teclado; Nuevo proveedor en Pixel 7 e iPhone SE | `PU-136-r3-prov-1.png`, `PU-136-r3-prov-2.png`, `PU-136-r3-doc-3.png`, `PU-136-r3-obra-1.png`, `PU-136-r3-obra-3.png` · `out-foco-*.txt` | En los botones de guardar quedó bien: durante los 2,5 s el foco sigue en el botón, nombre accesible "Guardando…" (`aria-disabled`, sin `disabled`). En un diálogo, Tab y Shift+Tab no lo sacan del botón. Al terminar, el foco vuelve al botón que abrió el diálogo ("+ agregar certificado o documento", "+ Nuevo proveedor"). Falta "Agregar link", otro botón con ruedita: al terminar, el foco se cae a `body` ("foco=BODY (FUERA del diálogo)"). Lo mismo pasa con "Pegar link" (**CEL-03**). |
| PU-018 | Mientras guarda, el botón solo se pone gris | ✅ | Nuevo proveedor tocado en Pixel 7 e iPhone SE; Guardar obra, Guardar cambios, Guardar, Guardar proveedor en PC | `PU-018-r3-se-antes.png`, `PU-018-r3-se-guardando.png`, `PU-018-r3-p7-guardando.png` | El botón no queda gris: pasa de `rgb(15,42,74)` a `rgb(31,61,99)` con opacidad 1, muestra la ruedita y sigue a la vista (top 570 de 667). El lector lee "Guardando…" (árbol de accesibilidad: `button "Guardando…"`). |
| PU-022 | Tablas que no entran en el celular | ✅ | Usuarios, Presupuesto por rubro (Diego y Carla), Tabla de permisos, Sectores, ficha de obra (documentos y contactos), Editar obra, Plazos; Pixel 7 e iPhone SE | `PU-022-r3-se-usuarios.png`, `PU-022-r3-p7-usuarios.png`, `PU-022-r3-se-rubro.png`, `PU-022-r3-p7-rubro.png`, `PU-135-r3-*.png` | Sin scroll lateral de página en ninguna pantalla (`scrollWidth = clientWidth` en todas). Las tablas pasan a tarjetas, salvo Permisos (deslizable con aviso) y Plazos (341 px, entra entera). Los submenús y las pestañas que se pasan del ancho se deslizan con sombra. |
| PU-041 | Controles de 40 px en el celular | ⚠️ | 20 pantallas y 11 diálogos, Pixel 7 e iPhone SE, como Diego, Carla y Hernán | `PU-041-r3-se-*.png`, `PU-041-r3-p7-*.png`, `CEL-01-*.png`, `CEL-02-*.png` · `out-cel-se.txt`, `out-cel-p7.txt` | Botones, campos, listas, pestañas, casilleros (por su rótulo) y la lupa miden ≥ 44. Quedan controles chicos. En las tarjetas de Obras, el link para abrir una obra mide "87×21" (**CEL-01**). Los links sueltos de texto miden 19–21 px de alto: "‹ Volver a la obra" "104×19", "‹ Proveedores" "97×21" y mail/teléfono de los contactos "227×21" / "97×21" (**CEL-02**). Los nombres de las tarjetas de Usuarios tienen 44 de alto pero 33–40 de ancho ("Dora" 33×44): queda como nota. |

### Diálogos: Tab atrapado, Escape y foco de vuelta (PC 1366, solo teclado)

Recorrí cada diálogo con N+3 Tab y N+3 Shift+Tab, donde N es la cantidad de controles. Después apreté Escape, sin cambios hechos.

| Diálogo | Controles | ¿Sale con Tab/Shift+Tab? | Escape | Foco vuelve a |
|---|---|---|---|---|
| Agregar certificado o documento | 7 | nunca | cierra | "+ agregar certificado o documento" ✅ |
| Presupuesto adicional | 11 | nunca | cierra | "+ agregar presupuesto adicional" ✅ |
| Cambiar estado | 4 | nunca | cierra | "Cambiar estado / pasar a Terminada" ✅ |
| Nuevo proveedor (2 corridas) | 7 | nunca | cierra | "+ Nuevo proveedor" ✅ |
| Editar proveedor (2 corridas) | 7–8 | nunca | cierra | "Editar" ✅ |
| Agregar sector / Renombrar / Sacar (confirmación) | 3 / 3 / 2 | nunca | cierra | botón que lo abrió ✅ |
| Agregar usuario / Editar usuario (Laura) | 7 / 9 | nunca | cierra | botón que lo abrió ✅ |

El foco se ve en todos los casos: `outline: solid 2px rgb(31,95,122)`. Los diálogos se exponen como `dialog "<título>"` con `aria-modal`.

### Foco visible en PC (1366×768, Tab)

Recorrí 13 pantallas con hasta 45 Tab en cada una: Obras, la ficha, Editar, Presupuesto por rubro, Nueva obra, Proveedores y su ficha, Usuarios, Sectores, Permisos, Plazos, Mi perfil y el Buscador. Hubo **0 paradas sin indicador visible**, y ninguna parada quedó fuera de la vista. Evidencia: `PU-136-r3-foco-visible-obras.png`, `PU-136-r3-foco-visible-permisos.png` y `out-foco-visible.txt`.

## Hallazgos nuevos

### CEL-01 · En las tarjetas de Obras del celular, lo único que abre la obra es el número: un link de 21 px de alto
- **Severidad:** baja
- **Tipo:** celular
- **F-XX / pantalla:** F-15 · W-05 Obras (listado) · `/obras`
- **Persona:** Diego (Gerencia de Administración), Hernán (Jefatura de Proyectos)
- **Pasos:** 1. Abrir Obras en un Pixel 7 o en un iPhone SE (375×667). 2. Medir con `boundingBox()` lo que se toca para entrar a una obra.
- **Esperado:** el objetivo para entrar a la obra mide 44 px de alto como mínimo (PU-041), o la tarjeta entera abre la obra.
- **Obtenido:** solo el número subrayado ("2-703-2026") es un link y mide "87×21" px. El resto de la tarjeta (nombre, cliente, planta, responsable) no responde al toque. Pasa igual en los dos celulares, en las 3 tarjetas de "En ejecución" y con Diego y con Hernán. Se reprodujo en 2 corridas por celular.
- **Evidencia:** `capturas/CEL-01-1.png` (iPhone SE) · `capturas/CEL-01-2.png` (Pixel 7) · `CEL\out-cel-se.txt`, `out-cel-p7.txt` ("a "2-703-2026 · QA3-A Obra conflictos 3" 87×21")
- **Causa probable (opcional):** `ObrasTabla.tsx:48`: el `<Link className="enlace-fuerte">` es en línea y no tiene `min-height`. La fila `tr` de la tarjeta no es clicable.

### CEL-02 · En el celular quedan links de texto sueltos de 19–21 px de alto: "‹ Volver a la obra", "‹ Proveedores" y el mail y el teléfono de los contactos
- **Severidad:** baja
- **Tipo:** celular
- **F-XX / pantalla:** F-21 Presupuesto por rubro `/obras/{id}/presupuesto-por-rubro` · F-56 Ficha de proveedor `/proveedores/{id}` · F-18 Ficha de obra, contactos `/obras/{id}`
- **Persona:** Diego (Gerencia de Administración), Carla (Compras), Hernán (Jefatura de Proyectos)
- **Pasos:** 1. En un Pixel 7 o en un iPhone SE, abrir Presupuesto por rubro de una obra, la ficha de un proveedor y la ficha de una obra con contactos. 2. Medir los links "‹ Volver a la obra", "‹ Proveedores" y los links de mail y teléfono de los contactos.
- **Esperado:** 44 px de alto como mínimo. Son controles sueltos, no links dentro de una frase. El teléfono es justo lo que se toca en el celular para llamar.
- **Obtenido:** "‹ Volver a la obra" mide "104×19", "‹ Proveedores" "97×21", "ana.perez@cliente-ejemplo.com" "227×21" y "291 455-1234" "97×21". Pasa igual en los dos celulares y se reprodujo en 2 corridas por celular.
- **Evidencia:** `capturas/CEL-02-1.png` (ficha de proveedor, iPhone SE) · `capturas/CEL-02-2.png` (ficha de obra, Pixel 7) · `CEL\out-cel-se.txt`, `out-cel-p7.txt`
- **Causa probable (opcional):** `PresupuestoPorRubroPantalla.tsx:41` y `ProveedoresPantalla.tsx:140`: son `<Link>` en línea sin `min-height`. Los `mailto:` y `tel:` de los contactos tampoco tienen `min-height`.

### CEL-03 · Con teclado, "Pegar link" y "Agregar link" dejan el foco en la nada: en un diálogo, el foco sale del diálogo
- **Severidad:** baja
- **Tipo:** accesibilidad
- **F-XX / pantalla:** F-19 diálogo "Agregar certificado o documento" `/obras/{id}` · F-16 · W-06 Nueva obra `/obras/nueva` (oferta técnica). También deberían estar afectados los demás campos de adjuntos, porque usan el mismo componente.
- **Persona:** Hernán (Jefatura de Proyectos), PC 1366×768, solo teclado
- **Pasos:** 1. En la ficha de la obra 2-961-2026, abrir "+ agregar certificado o documento" con Enter. 2. Llegar con Tab a "Pegar link" y apretar Enter. 3. Escribir el link: se escribe en ningún lado. 4. Ir con Tab al campo "Link", escribir el link, llegar a "Agregar link" y apretar Enter. 5. Esperar a que termine ("Agregando el link…").
- **Esperado:** después de "Pegar link", el foco va al campo "Link". Cuando termina "Agregar link", el foco queda en el diálogo, por ejemplo en el link agregado o en "Pegar link". Es lo mismo que ya se arregló para Guardar (PU-136).
- **Obtenido:** después de Enter en "Pegar link", el foco queda en `body` ("foco=BODY (FUERA del diálogo)"). Lo que se escribe se pierde, y al tocar "Agregar link" aparece "Pegá el link.". Durante "Agregar link" el foco se queda bien en el botón ("Agregando el link…"). Cuando termina, el botón desaparece y el foco vuelve a `body`, fuera del diálogo. El lector de pantalla no anuncia nada, y el siguiente Tab salta al primer control del diálogo. En Nueva obra pasa lo mismo: después de Enter en "Pegar link" y al terminar "Agregar link", el foco queda en `body`. El Tab siguiente va a "+ agregar presupuesto adicional" y se saltea el "quitar" del link recién agregado. Reproducido: diálogo 4 veces (2 con red normal y 2 con 2,5 s), Nueva obra 3 veces.
- **Evidencia:** `capturas/CEL-03-1.png` ("Pegá el link." después de escribir sin foco) · `capturas/CEL-03-3.png`, `CEL-03-4.png` (link agregado, foco en `body`) · `CEL\out-foco-doc-3.txt` ("agregar link +2500 ms: foco=BODY (FUERA del diálogo)") · `out-foco-link-2.txt`, `out-foco-link-3.txt`
- **Causa probable (opcional):** `shared/adjuntos/AdjuntosField.tsx:166-197`: `setPegandoLink(true)` desmonta el botón "Pegar link" y `PegarLink` no enfoca su campo al montarse. Al terminar (`onAgregado` → `setPegandoLink(false)`), se desmonta `PegarLink` mientras el foco está en su botón. Seguramente "Cancelar" de `PegarLink` hace lo mismo (no lo probé).
- **Regresión de:** PU-136 / front `e062ff0` (arreglo incompleto: cubre los botones de guardar, no los de adjuntos)

## Observaciones de UX (decisiones "a validar por Juani")

Ninguna. Este rol no probó esas decisiones. Al pasar, Escape sin cambios cerró todos los diálogos al instante.

## No probado

- **Lector de pantalla real** (NVDA, TalkBack, VoiceOver) y **teclado virtual**: no se pueden correr acá. Se verificó por el árbol de accesibilidad (`ariaSnapshot`): el botón ocupado se expone como `button "Guardando…" [disabled]`. El anuncio depende de que el lector diga el cambio de nombre del control enfocado, porque no hay una región `aria-live` o `role=status` con "Guardando…". Los avisos de resultado sí están en regiones vivas ("Dimos de alta a…", "Guardamos los cambios de la obra.", "Agregamos el documento a la obra."). Conviene confirmarlo con NVDA y TalkBack reales.
- **Después de guardar en una pantalla** (Nueva obra, Editar obra), la app navega a la ficha y el foco queda en `body`, con el aviso en la región viva. No lo cuento como hallazgo porque es navegación de página. Tab durante el guardado en una pantalla (no en un diálogo) sale del botón: es lo normal fuera de un diálogo.
- **"Subir archivo"** con teclado (abre el selector de archivos del sistema) y "Cancelar" de "Pegar link": no se probaron. Seguramente tienen el mismo patrón de CEL-03.
- **Mi perfil:** solo se midió. No guardé para no cambiar datos de Diego.
- **Celular real** (Safari iOS, Chrome Android): todo se hizo con emulación de Chromium.

Firma CEL: 6 PU (134, 135, 136, 018, 022, 041) en Pixel 7, iPhone SE y PC con teclado; 20 pantallas y 11 diálogos medidos, 10 diálogos con trampa de Tab; 3 hallazgos nuevos (baja).
