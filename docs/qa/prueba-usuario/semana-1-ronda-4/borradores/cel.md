# Borrador CEL · QA celular y accesibilidad (rol 3) · ronda 4 retomada

- **Fecha:** 2026-11-19, 09:15–10:30 (hora local). `staging`: back `56e5d8f`, front `380ddaf`. Base reseteada hoy.
- **Dispositivos:**
  - Pixel 7: `devices['Pixel 7']`, 412×915 (viewport útil 412×839).
  - iPhone SE forzado a 375×667: táctil, `deviceScaleFactor` 2, UA de iPhone SE.
  - PC 1366×768, solo teclado.
  - Chromium con `--lang=es-AR`.
- **Personas / sesiones:** Diego (`diego.json`, Gerencia de Administración), Hernán (`hernan.json`, Jefatura de Proyectos) y Carla (`carla.json`, Compras).
- **Ingresos reales gastados:** 0.
- **Datos creados (prefijo QA4C):**
  - obras "QA4C Obra foco 1" (id 1, 2-4117-2026; contactos Ana y Beto con mail y teléfono) y "QA4C Obra foco 2" (id 3, 2-4127-2026; el nombre quedó "QA4C Obra foco 2 ed2" por PU-136);
  - documentos "QA4C Documento teclado 1…4", "QA4C Documento celular se/p7 1" y "QA4C Documento sonda se/p7 …" en la obra 1;
  - proveedores "QA4C Ferretería SE 1" (id 3), "QA4C Ferretería P7 1" (id 4), "QA4C Ferretería SE 2" (id 5) y "QA4C Corralón teclado 1/2" (ids 6 y 7).
  - Nada de usuarios del seeder tocado.
- **Scripts:** `C:\Users\GREEN\AppData\Local\Temp\claude\4fe530b1c11d8e8d\f4efcdda-aa94-4434-aafd-2e4822d4d74b\scratchpad\r4\CEL\`. Son los de ayer con rutas `C:` y helper actualizados, más los nuevos:
  - `cel.mjs`: barrido de controles < 44 px, desborde, fechas y tarjetas;
  - `foco-obra.mjs`, `foco-doc.mjs`, `foco-doc-archivo.mjs` y `adj-lib.mjs`: PU-212;
  - `foco-guardar.mjs` y `foco-lib.mjs`: PU-136;
  - `cel-guardando.mjs`: O-1.2;
  - `sonda-doc.mjs` y `sonda-dlg.mjs`: geometría del diálogo mientras guarda;
  - `inv.mjs` y `tarjeta-foco.mjs`: tarjetas de Obras y Usuarios;
  - `dup.mjs`: links de "Parecidos ya cargados".
  - Las salidas son `out-*.txt` en la misma carpeta.
- **Demora de red:** `page.route('**/api/**')` en pedidos que no son GET: 2,5 s (PU-212 lento), 3 s (PU-136), 4 s (archivo), 5–6 s (O-1.2 y sondas).
- **Consola:** limpia en todas las corridas. Solo hubo dos 422 esperados en `POST /api/proveedores` en `sonda-dlg`, al repetir a propósito el nombre de un proveedor ya creado.
- **Interrupción:** entre 09:49 y 10:06 la API respondió 503 porque MySQL dejó de responder y se cayó solo (incidente de entorno, no fue un corte de la prueba; ver reporte). No corrí nada en ese lapso, y las mediciones de ese momento se descartaron y se repitieron.
- **Nota de método:** ayer `out-foco-doc-archivo-1.txt` quedó vacío por una carrera de Playwright, no por la app. `waitForEvent('filechooser')` se armaba después del Enter. Hoy pasó lo mismo 2 veces ("abre el selector: false" o timeout). Con el escucha de `filechooser` puesto al abrir la página, el selector se abrió en 12 de 12 Enter.

## Tabla de verificación

| PU | título corto | estado | pantallas revisadas | evidencia | nota |
|---|---|---|---|---|---|
| PU-210 | Tarjeta de Obras tocable entera con un solo foco; "‹ Volver a la obra", "‹ Proveedores", mail y teléfono ≥ 44 px | ✅ | Obras: "En ejecución" y "Confirmadas", con Diego y Hernán. Presupuesto por rubro (Diego y Carla). Ficha de proveedor (Diego y Carla). Ficha de obra con contactos (Diego y Hernán). Pixel 7, iPhone SE y PC con teclado. | `PU-210-r4b-se/p7-obras.png`, `-ficha.png`, `-contactos.png`, `-rubro.png`, `-prov.png`, `PU-210-r4b-pc/p7/se-foco-tarjeta-1.png` · `out-cel-p7-1.txt`, `out-cel-se-1.txt`, `out-inv-*.txt`, `out-tarjeta-foco-*.txt` | **Tarjetas:** cada una tiene 1 solo enfocable, el link del número. Su `::after` (`position:absolute; inset:0`) cubre toda la tarjeta. En las 4 esquinas y el centro de las 5 tarjetas, `elementFromPoint` da el link de esa obra. El toque real en el nombre del trabajo y en la esquina inferior derecha abre la obra correcta (`/obras/4`, `/obras/1`). Con Tab se pasa de tarjeta en tarjeta, un foco por tarjeta; en el celular el contorno se dibuja sobre la tarjeta entera, y en PC sobre el link. Enter abre la obra.<br>**Por qué el medidor sigue contando el número como chico:** el link en sí mide "87×21" / "96×21", pero lo que se toca es la tarjeta, de 343×148 a 380×165. Ayer, los "3 y 7 controles chicos" eran exactamente estos números de tarjeta: 3 en "En ejecución" y 7 en "Confirmadas". **No es hallazgo.**<br>**Links sueltos:** "‹ Volver a la obra" 104×44 y "‹ Proveedores" 97×44. En los contactos de la obra, "291 400-0041" 97×44, "beto.foco@cliente-ejemplo.com" 226×44 y el mail largo 309×46 (SE) / 346×46 (P7).<br>**Detalle de medición:** el toque en la parte de abajo de una tarjeta que queda detrás de la barra inferior fija cae en la barra ("Obras" / "Más"). Es lo esperable con una barra fija; al deslizar, la tarjeta queda libre. |
| PU-212 | Foco con teclado en "Pegar link", "Agregar link", "Cancelar", "Subir archivo" y "quitar" | ✅ | Diálogo "Agregar certificado o documento" (obra 1): 4 corridas, 2 con red normal y 2 con 2,5 s, más 3 corridas solo de archivo con 4 s. Nueva obra, grupos "Presupuesto (oferta técnica)" y "OC / OT / contrato / mail de aprobación": 3 corridas, una con 2,5 s. Hernán, PC, solo teclado. | `PU-212-r4b-doc-1/3.png`, `PU-212-r4b-doc-2/4-lento.png`, `PU-212-r4b-doc-archivo-1/2/3.png`, `PU-212-r4b-obra-1/3.png`, `PU-212-r4b-obra-2-lento.png` · `out-foco-doc-*.txt`, `out-foco-doc-archivo-*.txt`, `out-foco-obra-*.txt` | En ningún paso el foco queda en `body` ni sale del campo o del diálogo ("RESUMEN: … ninguno").<br>**"Pegar link":** el foco va al campo Link, y lo escrito queda ("https://drive.example/QA4C/escrito-sin-tocar.pdf").<br>**"Cancelar" del link:** el foco vuelve a "Pegar link".<br>**"Agregar link":** mientras agrega, el foco se queda en el botón "Agregando el link…" (`aria-busy`). Al terminar, pasa al link agregado ("doc teclado ñ 1.pdf (link, se abre en otra pestaña)"), y el siguiente Tab va a su "Quitar …".<br>**"quitar" del link:** el foco pasa a "Subir archivo".<br>**"Subir archivo" en el diálogo:** Enter abre el selector. Mientras sube y al terminar, el foco se queda en "Subir archivo"; el "subiendo…" y el "1 KB" se anuncian por la lista viva. "Cancelar QA4C plano ñandú.pdf" mientras sube y "Quitar QA4C plano ñandú.pdf" devuelven el foco a "Subir archivo".<br>**"Subir archivo" en Nueva obra:** el foco pasa a "Cancelar …" mientras sube y al archivo cuando termina. "quitar" lo devuelve a "Subir archivo".<br>Las dos variantes dejan el foco dentro del campo. |
| PU-041 | Controles < 44 px en el celular | ⚠️ | Pixel 7 e iPhone SE, Diego, Carla y Hernán (lista completa abajo). Pantallas: Obras, ficha de obra, Presupuesto por rubro, Nueva obra, Editar obra, Proveedores, ficha de proveedor, Buscador, Mi perfil, Más, Usuarios, ficha (diálogo) de usuario, Sectores, Tabla de permisos y Plazos. Diálogos: agregar y editar documento, presupuesto adicional, cambiar estado, nuevo y editar proveedor, agregar usuario y agregar sector. | `PU-041-r4b-se/p7-usuarios.png`, `PU-041-r4b-se/p7-dlg-doc.png`, `PU-041-r4b-se/p7-prov-parecidos-1.png`, `CEL-01-1/2.png` · `out-cel-p7-1.txt`, `out-cel-se-1.txt`, `out-dup-*.txt` | **Scroll horizontal de página:** no hay en ninguna pantalla (412/412 y 375/375). Solo deslizan por dentro las pestañas de Obras ("Terminadas (0)"), las de Usuarios ("Plazos de la prioridad") y la Tabla de permisos.<br>**Fechas:** ninguna partida ni cortada.<br>**Controles < 44 px que quedan, por pantalla:**<br>• **Obras:** el número de cada tarjeta, "87×21" / "96×21". No cuenta, porque la tarjeta entera es el blanco (ver PU-210).<br>• **Nuevo / Editar proveedor:** "QA4C Ferretería SE 2" 128×19, "QA4C Ferretería P7 1" 129×19 y "QA4C Corralón teclado 1" 239×39. Son los links de la frase "Parecidos ya cargados: … · … · Si es otro proveedor, podés guardar igual." (`div.aviso-parecidos`). Son links dentro de un texto, así que no cuentan.<br>• **Usuarios:** los nombres que abren "Editar usuario": "Carla" 34×44, "Diego" 40×44, "Dora" 33×44, "Laura" 37×44, "Pablo" 38×44 y "Sofía" 34×44. Tienen el alto pero no el ancho, y el resto de la tarjeta (343/380×215) no responde. **CEL-01** (baja).<br>• **Resto:** botones, campos, listas, pestañas, casilleros y pie de los diálogos miden ≥ 44 en todas las demás pantallas y diálogos. |
| PU-136 | Mientras guarda, el foco no se cae y se anuncia "Guardando…" | ✅ | Con teclado en PC y 2,5–3 s de demora: "Guardar" del diálogo de documento (2 corridas), "Guardar cambios" de Editar obra (2), "Guardar proveedor" de Nuevo proveedor (2) y "Agregar link" (4) | `PU-136-r4b-obra-1/2.png`, `PU-136-r4b-prov-1/2.png` · `out-foco-guardar-1/2.txt`, `out-foco-doc-*.txt` | **Mientras guarda:**<br>• el foco sigue en el botón, con `aria-busy` y `aria-disabled=true` (no `disabled`, así que no pierde el foco);<br>• `ariaSnapshot` da `button "Guardando…"`;<br>• `getByRole('button', { name: /Guardando/ })` encuentra 1.<br>**Con Tab durante el guardado:** en los diálogos va y vuelve entre "Cancelar" y "Guardando…" sin salir. En Editar obra, "Guardar cambios" es el último enfocable de la página, así que el Tab sale a `body` y después a "Saltar al contenido": es el orden normal, no una caída (pasa igual sin guardar).<br>**Al terminar:**<br>• diálogo de documento: foco en "+ agregar certificado o documento" y "Agregamos el documento a la obra.";<br>• Nuevo proveedor: foco en "+ Nuevo proveedor" y "Dimos de alta a QA4C Corralón teclado 1.";<br>• Editar obra navega a la ficha, con "Guardamos los cambios de la obra." en la región de estado (el foco queda en `body`, como en toda navegación). |

**Conteo:** ✅ 3 · ⚠️ 1 (PU-041, por CEL-01) · ❌ 0

### Punto 5 · iPhone SE: ¿el diálogo se pasa de la pantalla mientras guarda?

- **Diálogo de documento** (`out-sonda-doc-se-1/2.txt`, `PU-041-r4b-se-doc-guardando-1/2.png`, `…-arriba-1/2.png`): en 2 de 2 corridas el diálogo ocupa 26→659 dentro de un alto de 667, así que **cabe**:
  - título "Agregar certificado o documento" 27→82;
  - aviso "Estamos guardando. Esperá unos segundos." 570→590;
  - "Cancelar" y "Guardar" 602→646.
  - Todo está visible a la vez y no hay nada cortado. En Pixel 7, igual (40→673 de 839).
- **Nuevo proveedor** (es el "−149 px" de ayer; `out-sonda-dlg-se-1/2.txt`, `PU-041-r4b-se-prov-guardando-1/2.png`): el diálogo ya es más alto que la pantalla antes de guardar. Mide 40→796/816 con el aviso "Parecidos ya cargados", y el fondo desliza (`overflow-y:auto`).
  - **Al guardar:** el fondo se desliza solo hasta el pie. El título queda arriba, fuera de la vista (−148→−93), pero el aviso "Estamos guardando. Esperá unos segundos." (570→590) y los botones (602→646) quedan **enteros en pantalla**.
  - **Deslizando hacia arriba:** se vuelve a ver el título, y deslizando hacia abajo, el pie. No hay nada inalcanzable ni recortado en horizontal (375/375).
  - En Pixel 7 todo entra (40→828 de 839).
  - Es un diálogo largo que desliza, no un defecto: **no es hallazgo**.

## Hallazgos nuevos

### CEL-01 · En Usuarios, en el celular, el nombre que abre "Editar usuario" mide 33–40 px de ancho y el resto de la tarjeta no responde
- **Severidad:** baja
- **Tipo:** celular
- **F-XX / pantalla:** Usuarios (listado) · `/usuarios`
- **Persona:** Diego (Gerencia de Administración)
- **Pasos:** 1. En un Pixel 7 o en un iPhone SE (375×667), abrir Usuarios. 2. Medir con `getBoundingClientRect()` el nombre de cada tarjeta, que es lo único que abre "Editar usuario". 3. Tocar otras partes de la tarjeta (centro, derecha, abajo).
- **Esperado:** el control principal de la tarjeta mide ≥ 44×44 (PU-041), o la tarjeta entera abre el usuario, como ya se hizo con Obras (PU-210).
- **Obtenido:** los nombres miden "Carla" 34×44, "Diego" 40×44, "Dora" 33×44, "Laura" 37×44, "Pablo" 38×44 y "Sofía" 34×44. El alto está bien, pero el ancho no llega. Los toques en el resto de la tarjeta (343×215 en SE y 380×215 en Pixel 7) caen en `td` "carla@empresa-ejemplo.com" o "Sí" y no hacen nada. Tocar "Carla" abre "Editar usuario · Carla". Los nombres largos (los `qa-lead-…`) no tienen el problema. Se reprodujo 2 veces en cada celular (`cel.mjs` + `inv.mjs`).
- **Evidencia:** `capturas/CEL-01-1.png` (iPhone SE) · `capturas/CEL-01-2.png` (Pixel 7) · `out-cel-se-1.txt`, `out-cel-p7-1.txt` ("button[button] "Dora" 33×44 · en <th.tabla__dato>") · `out-inv-se-1.txt`, `out-inv-p7-1/2.txt` ("toques en la tarjeta de usuario: botón | td. "carla@empresa-ejemplo.com" | td. "carla@empresa-ejemplo.com" | td. "Sí"")
- **Causa probable (opcional):** `features/identidad/components/UsuariosTabla.tsx:33-38`: `<th scope="row" className="tabla__dato">` con un botón `tabla__enlace` sin `min-width`, y la tarjeta no tiene el link estirado (`::after`) que usa `ObrasTabla`.
- **Regresión de:** no; arreglo incompleto de PU-041 (ya anotado como nota en la verificación de la ronda 3: "Dora" 33×44)

## Decisiones aprobadas

| decisión | estado | dónde se probó | evidencia | nota |
|---|---|---|---|---|
| O-1.2 en el celular ("Cancelar" / tocar afuera mientras guarda) | ✅ | Diálogo de documento (Hernán, obra 1) y Nuevo proveedor (Carla), en Pixel 7 e iPhone SE, con 5 s de demora. También en las sondas, 2 corridas en SE. | `O-1-2-r4b-se-doc-1.png`, `O-1-2-r4b-se-prov-1.png`, `O-1-2-r4b-p7-doc-1.png`, `O-1-2-r4b-p7-prov-1.png` · `out-cel-guardando-se-1.txt`, `out-cel-guardando-p7-1.txt`, `out-sonda-doc-*.txt`, `out-sonda-dlg-*.txt` | **Con "Cancelar" y con el toque afuera:** aparece "Estamos guardando. Esperá unos segundos." y el diálogo no se cierra.<br>**Medidas del aviso:** 278×20, en pantalla y sin recortar (SE: y=570 en documento y y=623 en proveedor; P7: y=584 y y=739).<br>**Botones:** "Cancelar" 88×44 y "Guardar Guardando…" / "Guardar proveedor Guardando…" 107/177×44, visibles.<br>**Al terminar:** se cierra solo con "Agregamos el documento a la obra." / "Dimos de alta a QA4C Ferretería SE 1.". |

## Lo que no pude probar y por qué

- **Lector de pantalla real (NVDA / VoiceOver / TalkBack):** no hay en el entorno. El anuncio de "Guardando…" se verificó por el nombre accesible del botón enfocado (`ariaSnapshot`) y por `aria-busy`, no escuchándolo.
- **Teléfono y mail del proveedor:** en la ficha de proveedor el contacto es texto ("ventas.qa4c.se1@proveedor-ejemplo.com · 291 455-0411"), no un link, así que no hay nada que medir. PU-210 se refería a los contactos de la ficha de obra, que sí son links y miden ≥ 44. No lo levanto como hallazgo: es alcance, no un control chico.
- **Toque real en iOS Safari:** no está disponible. El "iPhone SE" es Chromium con el viewport y el UA forzados, como pide el brief.
- **Tiempos:** de 09:49 a 10:06 la API dio 503 por la caída de MySQL (incidente de entorno). No saqué conclusiones de tiempos ni de loaders en ese lapso.

---
CEL (rol 3): PU-210, PU-212, PU-041 y PU-136 en Pixel 7, iPhone SE 375×667 y PC con teclado, más O-1.2 en el celular y la geometría del diálogo mientras guarda en SE. 1 hallazgo nuevo (CEL-01, baja), 0 ingresos reales.
