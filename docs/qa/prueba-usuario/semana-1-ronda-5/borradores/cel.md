# Borrador CEL · QA celular y accesibilidad (rol 3) · ronda 5

- **Fecha:** 2026-11-19, 12:30–12:55 (hora local). `staging` desde los worktrees `_qa5`: back `6516ba7`, front `d2a4806`.
- **Dispositivos:**
  - Pixel 7: `devices['Pixel 7']`, 412×915;
  - iPhone SE forzado a 375×667 (táctil, `deviceScaleFactor` 2, UA de iPhone SE);
  - PC 1366×768, mouse y teclado.
  - Chromium con `--lang=es-AR`.
- **Personas / sesiones:** Diego (`diego.json`, Gerencia de Administración, la mayor parte), Dora (`dora.json`, Dirección) y Carla (`carla.json`, Compras).
- **Ingresos reales gastados:** 0.
- **Datos creados:** ninguno (prefijo QA5C sin usar).
  - Se midió sobre datos que ya existían, solo abriendo y cancelando: obra 2 "QA5B Obra fechas" con sus documentos, proveedor 2 "QA5A Ferretería Sesión" y los usuarios del seeder.
  - Ninguna corrida hizo un pedido distinto de GET ("escrituras: ninguna" en todas las salidas).
- **Bloqueo de ingreso simulado:** para ver "Destrabar ingreso" sin trabar a nadie, `page.route` agrega `bloqueo_ingreso: { hasta: ahora + 1 h }` solo a Dora en las respuestas GET de `/api/usuarios*`. El botón no se tocó.
- **Scripts:** en `C:\Users\GREEN\AppData\Local\Temp\claude\4fe530b1c11d8e8d\f4efcdda-aa94-4434-aafd-2e4822d4d74b\scratchpad\r5\CEL\`. Son los de la ronda 4, con `lib.mjs` apuntando a `tecnoservicios_frontend_qa5`, a `sesiones` de r5 y a las capturas de la ronda 5. Nuevos:
  - `pu307.mjs`: tarjetas de Usuarios (toques, teclado, nombre accesible y diálogo);
  - `cel5.mjs`: barrido de PU-041 por pantalla;
  - `dlg5.mjs`: controles dentro de cada diálogo, esperando a que cargue el formulario;
  - `radios.mjs`: Tab en grupos de opciones dentro de los diálogos.
  - Las salidas son `out-*.txt` en la misma carpeta.
- **Consola y red:** limpias en las 14 corridas válidas: sin errores JS, sin warnings de React, sin 4xx ni 5xx. Health 200 en 0,13 s; la API no tuvo demoras.

## Tabla de verificación

| PU | título corto | estado | pantallas revisadas | evidencia | nota |
|---|---|---|---|---|---|
| PU-307 | Usuarios en el celular: toda la tarjeta abre "Editar usuario", con un solo foco | ✅ | Usuarios (Diego) en Pixel 7, iPhone SE y PC, 2 corridas por equipo (`out-pu307-{p7,se,pc}-{1,2}.txt`). Usuarios con Dora y Carla en `cel5`. | `PU-307-r5-p7/se-lista.png`, `PU-307-r5-p7/se-toque-dora-activo.png`, `PU-307-r5-p7/se-toque-carla-activo.png`, `PU-307-r5-p7/se/pc-foco-tarjeta.png`, `PU-307-r5-p7/se/pc-dialogo-dora.png`, `PU-307-r5-pc-lista.png` | **Toques en el celular:** tocar el mail, el sector y "Activo" de Dora y Carla abre "Editar usuario · Dora" / "Editar usuario · Carla": 6 de 6 toques, 2 veces, en los dos celulares (24 de 24). `elementFromPoint` en cada punto da `button "Dora"` / `button "Carla"`: el `::after` del nombre (`position:absolute; inset:0`) cubre la tarjeta (343×215 en SE y 380×215 en Pixel 7).<br>**Tocar "Activo" no cambia el estado:** abre el diálogo con "Activo" marcado e "Inactivo" sin marcar, y no sale ningún pedido de escritura.<br>**Con teclado (celular y PC):** cada tarjeta tiene 1 solo enfocable, el nombre, y Tab va de tarjeta en tarjeta: Diego → Dora → Hernán → … En el celular el contorno (`outline 2px rgb(31,95,122)`) se dibuja en la tarjeta, y el botón no tiene contorno propio. Enter en "Dora" y en "Carla" abre su diálogo. Escape lo cierra y el foco vuelve al nombre.<br>**Lector de pantalla:** `ariaSnapshot` da `rowheader "Dora"` → `button "Dora"`, y celdas "Mail dora@…", "Sector Dirección", "Activo Sí · Ingreso trabado hasta las 13:35". Se oye el nombre de la persona como botón, dentro de su fila, sin duplicados.<br>**PC:** sigue la tabla (`thead` visible, filas `table-row`). Un clic en mail, sector o "Activo" no abre nada (12 de 12 en 2 corridas). Solo el nombre abre el diálogo, con Enter. En PC el contorno queda en el botón.<br>**"Destrabar ingreso"** (bloqueo simulado): se ve en el diálogo de Dora, junto a "Dora no puede entrar hasta las 13:35: se equivocó varias veces con el código…". Mide 147×44 en el celular y 147×40 en PC, y es el primer foco del diálogo. Tab recorre Destrabar → Nombre → Mail → Sector → Depende de → Activo → Inactivo → Teléfono → Cancelar → Guardar y vuelve a Destrabar, sin salir del diálogo. En la lista se ve "Sí · Ingreso trabado hasta las 13:35".<br>**Detalle no hallazgo:** en la primera apertura en frío (Pixel 7, corrida 1), al segundo el título todavía decía "Editar usuario" y el formulario estaba cargando. En las demás aperturas ya estaba "Editar usuario · Dora". |
| PU-041 | Controles < 44 px en el celular | ✅ | Pixel 7 e iPhone SE, con Diego, Carla y Dora (tabla abajo). Diálogos medidos por dentro en 2 corridas por celular (`out-dlg5-{p7,se}-{1,2}.txt`). | `PU-041-r5-p7/se-obras.png`, `-ficha.png`, `-proveedores.png`, `-usuarios.png`, `-dlg-doc.png`, `-dlg-adicional.png`, `-dlg-estado.png`, `-dlg-usuario.png` · `out-cel5-p7-1.txt`, `out-cel5-se-1.txt` | No hay scroll horizontal de página en ninguna pantalla (412/412 y 375/375). Solo deslizan por dentro las pestañas de Obras y de Usuarios y la Tabla de permisos.<br>**Lo único < 44 que queda no cuenta, por las reglas de la ronda 4:**<br>• el número de las tarjetas de Obras (87×21), porque la tarjeta entera abre la obra;<br>• los nombres de Usuarios ("Carla" 34×44, "Diego" 40×44, "Dora" 33×44, "Laura" 37×44, "Pablo" 38×44, "Sofía" 34×44), que ahora tienen el mismo caso: la tarjeta entera (343/380×215) es el blanco (PU-307).<br>Fechas: ninguna partida ni cortada. |

**Conteo:** ✅ 2 · ⚠️ 0 · ❌ 0

### PU-041 · medición por pantalla (Pixel 7 / iPhone SE)

| pantalla | persona | controles medidos | < 44 que cuentan | página (ancho) | nota |
|---|---|---|---|---|---|
| Obras (pestaña inicial y "Confirmadas") | Diego | 11 / 14–15 | 0 | 412/412 · 375/375 | Números de tarjeta 87×21 (no cuentan). Toques en las tarjetas: "abre" en todos los puntos visibles. Los que caen debajo de la barra inferior fija van a la barra, como en r4. "Terminadas (0)" queda dentro de las pestañas deslizables. |
| Ficha de obra (2) | Diego / Dora | 24 / 16 | 0 | ok | Contactos: mails 270×44 y 276×44, teléfonos 97×44. |
| Presupuesto por rubro | Diego / Carla | 21 / 20 | 0 | ok | "‹ Volver a la obra" 104×44. |
| Nueva obra | Diego | 31 | 0 | ok | |
| Editar obra (2) | Diego | 29 | 0 | ok | |
| Proveedores (lista) | Diego / Carla | 11 | 0 | ok | |
| Ficha de proveedor (2) | Diego / Carla | 8 | 0 | ok | |
| Nuevo proveedor (diálogo) | Diego / Carla | 7 en el diálogo | 0 | ok | |
| Editar proveedor (diálogo) | Diego | 7 en el diálogo | 0 | ok | |
| Usuarios | Diego / Dora | 24 | 0 | ok | Nombres 33–40×44 con la tarjeta entera como blanco (PU-307). Con Dora: 1 enfocable por tarjeta. |
| Agregar usuario (diálogo) | Diego | 7 en el diálogo | 0 | ok | El formulario aparece a los 1,1–2,2 s ("Cargando el formulario…"). |
| Editar usuario (diálogo, Carla y Dora) | Diego | 9 (10 con "Destrabar ingreso") | 0 | ok | Las opciones Activo/Inactivo miden 13×13, pero el toque es sobre el rótulo segmentado: 72×44 y 80×44. |
| Sectores y "Agregar sector" | Diego | 32 / 3 en el diálogo | 0 | ok | |
| Tabla de permisos | Diego | 197 | 0 | ok | Desliza por dentro (1820/378 y 1820/341), como se espera. |
| Plazos de la prioridad | Diego | 12 | 0 | ok | |
| Mi perfil | Diego / Carla | 8 | 0 | ok | |
| Más | Diego | 9 | 0 | ok | |
| Agregar documento (diálogo) | Diego | 8 en el diálogo | 0 | ok | |
| Editar documento (diálogo) | Diego | 10 en el diálogo | 0 | ok | |
| Presupuesto adicional (diálogo) | Diego | 13 en el diálogo | 0 | ok | El diálogo mide 999–1020 de alto y desliza por dentro. |
| Cambiar estado (diálogo) | Diego | 5 en el diálogo | 0 | ok | Opciones de 13×13 con el rótulo segmentado ≥ 44. |

### Regresiones de la tarjeta (punto 3)

- **Dos focos por tarjeta:** no hay. En las 12 tarjetas, con Diego y con Dora, hay 1 enfocable por tarjeta (`enfocables=1`), y Tab da un paso por tarjeta.
- **Nombre accesible:** es el de la persona, `button "Dora"`, dentro de `rowheader "Dora"`. No tiene `aria-label` que lo pise y no se repite.
- **Tocar "Activo":** abre el diálogo y no cambia nada. Las opciones quedan como estaban ("Activo" marcado) y no sale ningún PUT/PATCH en ninguna corrida.

## Hallazgos nuevos

### CEL-01 · En los diálogos, Tab se detiene en cada opción de un grupo (Activo / Inactivo, Confirmada / En ejecución) en vez de una sola vez
- **Severidad:** baja
- **Tipo:** accesibilidad
- **F-XX / pantalla:** F-58 · Usuarios, diálogo "Editar usuario" · `/usuarios`. También el diálogo "Cambiar estado" de la ficha de obra (`/obras/2`).
- **Persona:** Diego (Gerencia de Administración)
- **Pasos:** 1. Usuarios, foco en "Carla", Enter. 2. Recorrer el diálogo con Tab. 3. En la ficha de una obra, "Cambiar estado" con Enter y Tab.
- **Esperado:** cada grupo de opciones (`name="activo"`, `name="estado"`) es **una** sola parada de Tab, en la opción marcada, y se cambia con las flechas. Es como funciona un grupo de opciones nativo y lo que espera quien usa teclado o lector.
- **Obtenido:**
  - **Editar usuario:** Tab pasa por "Activo" (checked=true) **y** por "Inactivo" (checked=false): "→ paradas de Tab en radios (una vuelta): 2".
  - **Cambiar estado:** pasa por "Confirmada" (sin marcar) y por "En ejecución" (marcada). El primer foco del diálogo cae en la opción **no** elegida.
  - Las flechas sí funcionan (ArrowRight lleva a "Inactivo" y la marca). Pararse en "Inactivo" con Tab no la marca, así que no se pierde nada, pero es una parada de más que el lector anuncia como "Inactivo, botón de opción, no seleccionado".
  - Reproducido 2 veces (`out-radios-1.txt`, `out-radios-2.txt`). En la PC se ve igual que en el celular con teclado.
- **Evidencia:** `capturas/CEL-01-1.png` (foco en "Inactivo" sin marcar, Editar usuario · Carla) · `capturas/CEL-01-2.png` (Cambiar estado) · `CEL\out-radios-1.txt`, `out-radios-2.txt` (scratchpad del rol)
- **Causa probable (opcional):** `src/shared/ui/Dialog.tsx:76-87`. La trampa de foco hace `preventDefault()` de todo Tab y salta al siguiente de `enfocables()` (`:38-46`), que incluye cada `input[type=radio]` del grupo. El navegador no puede aplicar su regla de "una parada por grupo".
- **Regresión de:** no; existe desde `d6da13e` (la trampa de foco original del diálogo). No es un arreglo incompleto de ningún PU.

## Decisiones aprobadas

No me tocaba ninguna en esta ronda (O-1.1 lo prueba otro rol). No vi nada de O-1.2 fuera de lugar en los diálogos medidos: no se guardó nada.

## Lo que no pude probar y por qué

- **Lector de pantalla real (NVDA / VoiceOver / TalkBack):** no hay en el entorno. El nombre accesible se verificó con `ariaSnapshot` y el rol de cada foco.
- **Toque real en iOS Safari:** no está disponible. El "iPhone SE" es Chromium con el viewport y el UA forzados, como pide el brief.
- **"Destrabar ingreso" de punta a punta:** no se tocó. El bloqueo de Dora es simulado con `page.route` para no trabar a nadie ni gastar ingresos. Solo verifiqué que se ve, que se alcanza con Tab y que mide 147×44. El resultado "Destrabamos el ingreso de …" (nota §8) lo prueba VER.
- **Datos QA5C:** no hizo falta crear ninguno. Los diálogos de documento, adicional y estado se midieron sobre la obra QA5B (2) sin guardar, y el proveedor sobre QA5A (2).

---
CEL (rol 3): PU-307 (Pixel 7, iPhone SE 375×667 y PC; toques en mail, sector y "Activo" de Dora y Carla, 2 veces; teclado, nombre accesible y "Destrabar ingreso") y PU-041 (21 pantallas y diálogos, 2 celulares, sin scroll lateral). 1 hallazgo nuevo (CEL-01, baja, accesibilidad), 0 ingresos reales, 0 datos creados.
