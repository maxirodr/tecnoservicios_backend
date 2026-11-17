# Borrador UXM · Ronda 2 — celular, accesibilidad y loaders

Rol: QA celular, accesibilidad y loaders. Fecha: 2026-11-17. Sesiones: Hernán (obras), Carla (presupuesto por rubro con edición, proveedores), Diego (usuarios, sectores, permisos, plazos). Datos propios: obras 2-801-2026 (Pixel 7, `/obras/19`) y 2-802-2026 (iPhone SE, `/obras/20`), proveedores "QA-UXM2 Ferretería pixel7 / iphonese / Foco guardando …". Celular: Pixel 7 (412×839 útil) e iPhone SE forzado a 375×667. PC: 1366×768. Red lenta con `h.abrir({slow})` (2,5 s y 15 s); errores 500 simulados en el navegador (`route.fulfill`, no llegan al backend).
Scripts y salidas: `scratchpad\r2\uxm\` (`cel-obras.cjs`, `cel-otros.cjs`, `cel-dialogos.cjs`, `sectores-se.cjs`, `teclado.cjs`, `foco-guardando.cjs`, `loaders.cjs`, `error-vista.cjs`; salidas `out-*.txt`).
Logs: `errors-2026-11-17.log` no existe al terminar (ni con el 409 real de la prueba de conflicto); `laravel-2026-11-17.log` sin ERROR/CRITICAL. Consola del navegador limpia salvo el 409/500 buscados.

## 1. PU del rol (vista UXM, complementa a VA/VB)

| PU | título corto | estado | pantallas revisadas | evidencia | nota |
|---|---|---|---|---|---|
| PU-017 | Listas con resultados viejos sin señal | ✅ | `/obras` (pestaña, buscador), `/buscar` · 2,5 s y 15 s | `UXM-carga-pestana-2500-*.png`, `UXM-carga-buscar-obras-2500-*.png`, `UXM-carga-buscador-2500-*.png`, `out-loaders-*.txt` | Durante todo el pedido (también 15 s): `role=status` "Buscando obras…" / "Buscando…", la lista anterior atenuada con `aria-busy`, el resto de la pantalla igual (sin spinner de página). Al llegar, datos correctos; layout-shift 0,034 / 0,007 / 0,019. |
| PU-018 | Botón sin "Guardando…" | ⚠️ | Documento, Nuevo proveedor, Editar obra (PC, teclado) | `UXM-teclado-guardando.png`, `out-teclado3.txt` | Se ve el spinner (bien), pero el nombre accesible sigue "Guardar" / "Guardar proveedor" y el foco se cae del botón → ver **UXM-02**. |
| PU-022 | Tablas que no entran en el celular | ⚠️ | Usuarios, Presupuesto por rubro, Permisos ✅; **Sectores** ❌ | `UXM-cel-*-usuarios.png`, `UXM-cel-*-rubro-carla.png`, `UXM-05-sectores-*.png` | Usuarios y Presupuesto por rubro en tarjetas; montos 317–354×44, `inputmode=decimal`, 16 px. Sectores no estaba en el PU, pero tiene el mismo problema → **UXM-04**. |
| PU-037 | Editar obra salta al cargar | ✅ | W-23 con 2,5 s y 15 s | `UXM-carga-editar-2500-*.png`, `UXM-carga-editar-15000-*.png` | "Preparando el formulario…" y después "Cargando el historial…"; layout-shift durante la carga 0,0000 (las dos corridas); total de la página 0,0234. |
| PU-040 | Pestañas cortadas en el celular | ✅ | `/obras`, submenú de Usuarios, ficha | `UXM-cel-*-obras.png` | "Terminadas (1)" queda a la derecha con sombra de "hay más" y se desliza; al tocarla queda entera a la vista. |
| PU-041 | Controles de 40 px | ⚠️ | 24 pantallas y diálogos × 2 celulares | `out-cel-*.txt` | Todo lo nuevo mide 44 px; quedan las listas de alcance de Permisos (25 px), el logo (23 px) y "Saltar al contenido" (39 px) → **UXM-03**. |
| PU-042 | Pestañas sin flechas | ✅ | `/obras` (PC) | `out-teclado.txt` (bloque `tabpanel`) | Inicio/Fin/flechas mueven y eligen; una sola parada de Tab. (Detalle: las pestañas no tienen `aria-controls`; hay un `role=tabpanel`. No lo cuento como hallazgo.) |

## 2. Cobertura del celular por pantalla

Columnas: sin scroll horizontal de página · controles ≥ 44 px · campos ≥ 16 px · pestañas/tablas anchas con aviso.
"Logo/Saltar" = el logo "TECNOSERVICIOS" (141×23) y "Saltar al contenido" (156×39, solo con teclado), que aparecen en todas las pantallas (UXM-03); no se repiten abajo.

| Pantalla | Persona | Pixel 7 (412) | iPhone SE (375) | Nota |
|---|---|---|---|---|
| Obras `/obras` (tarjetas, pestañas) | Hernán | ✅ | ✅ | Tarjetas (sin encabezado de tabla); "Terminadas" se desliza con sombra. |
| Obras · cambio de pestaña | Hernán | ✅ | ✅ | |
| Nueva obra `/obras/nueva` (alta completa) | Hernán | ✅ | ✅ | Obra creada en los dos; `type` correctos (tel, email, url con `inputmode=url`, date). |
| Ficha `/obras/{id}` | Hernán | ✅ | ✅ | Una sola pestaña "Documentos y contactos" (209×44); editar/quitar 44 px. |
| Diálogo "+ agregar certificado o documento" (con archivo) | Hernán | ✅ | ✅ | En SE mide 653 px y el fondo se desliza: "Guardar" se alcanza (pie a la vista tras deslizar). |
| Diálogo "Cambiar estado" + cambio real | Hernán | ✅ | ✅ | Los `radio` miden 13×13 pero el toque es sobre el rótulo segmentado (≥ 44 px). "La obra pasó a En ejecución." |
| Diálogo "+ agregar presupuesto adicional" | Hernán | — | ✅ | Medido solo en SE: 840 px; el fondo se desliza hasta "Guardar". |
| Editar obra `/obras/{id}/editar` | Hernán | ✅ | ✅ | Ver UXM-01 (aviso de error fuera de la vista). |
| Presupuesto por rubro (solo ver) | Hernán | ✅ | ✅ | Tarjetas por rubro. |
| Presupuesto por rubro (cargar montos) | Carla | ✅ | ✅ | Campos 354/317×44, 16 px, `inputmode=decimal`; "Guardar" 85×44. No hace falta deslizar (sin aviso, correcto). |
| Proveedores listado | Carla | ✅ | ✅ | |
| Alta desde `/proveedores/nuevo` | Carla | ✅ | ✅ | Abre el diálogo; 673 px en SE con fondo deslizable; guardó y fue a la ficha. |
| Proveedores búsqueda ("qa-uxm2 ferreteria") | Carla | — | ✅ | Encuentra los dos. En Pixel 7 el paso no corrió (error de mi script al buscar el campo). |
| Ficha de proveedor | Carla | — | ✅ | Pixel 7: idem anterior. |
| Buscador general (lupa 44×44 → `/buscar`) | Hernán | ✅ | ✅ | |
| Mi perfil | Hernán | ✅ | ✅ | |
| Más | Hernán | ✅ | ✅ | |
| Usuarios (tarjetas) + submenú | Diego | ✅ | ✅ | Casillero "Ver inactivos" 18×18 con rótulo 44 px (ok). Submenú deslizable con sombra. |
| Ficha de usuario | Diego | — | — | No llegó: mi script no encontró el link de Laura en las tarjetas (no se investigó). |
| Diálogo nuevo usuario | Diego | — | ✅ | Medido solo en SE (el más chico): 708 px, fondo deslizable. |
| Sectores | Diego | ⚠️ | ❌ | **UXM-04**: tabla de 398 px, "sacar" cortado, sin aviso. |
| Diálogo agregar sector | Diego | — | ✅ | Medido solo en SE: 303 px. |
| Tabla de permisos | Diego | ⚠️ | ⚠️ | Deslizable con aviso (1.820 px); listas de nivel 113×44 y 16 px; **listas de alcance 105×25** (UXM-03). |
| Plazos de la prioridad | Diego | ✅ | ✅ | |

## 3. Hallazgos nuevos

### UXM-01 · Al guardar, el aviso de error o de conflicto aparece arriba, fuera de la vista: el usuario no ve nada
- **Severidad:** media
- **Tipo:** mensaje
- **F-XX / pantalla:** F-20 · W-23 Editar obra `/obras/{id}/editar` (409 de conflicto y 500) · F-16 · W-06 Nueva obra `/obras/nueva` (500) · PC 1366×768 e iPhone SE
- **Persona:** Hernán (Jefatura de Proyectos)
- **Pasos:** 1. Abrir Editar obra de 2-802-2026 en dos pestañas. 2. En la segunda, cambiar "Planta (si aplica)" y guardar. 3. En la primera, cambiar la planta, bajar hasta "Guardar cambios" (al pie, como siempre) y tocarlo. 4. Repetir con un error del servidor (500 simulado en el navegador) en Editar obra y en Nueva obra, en PC y en iPhone SE.
- **Esperado:** el aviso se ve donde está mirando el usuario (la pantalla sube hasta el aviso o el foco va a él), como ya pasa con los errores de campo, que llevan el foco al primer campo con error.
- **Obtenido:** el botón deja de girar y vuelve a "Guardar cambios"; en pantalla no cambia nada más. El aviso "Alguien cambió esto mientras lo tenías abierto. Actualizá y revisá. Al actualizar vas a ver lo que está guardado ahora y se pierde lo que cambiaste acá. Actualizar" queda 610 px por encima de lo visible (PC); "El sistema tuvo un problema. Volvé a intentar en un momento." queda a −550 px (Editar, PC), −278 px (Nueva, PC), −1.823 px y −1.664 px (iPhone SE). Parece que se guardó o que no pasó nada. Con teclado: el foco queda en `body`, "Actualizar" está a 29 Shift+Tab y, después de tocarlo, el foco vuelve a `body`. Reproducido 3 veces el 409 y 2 veces el 500.
- **Evidencia:** `capturas/UXM-04-conflicto-sin-foco.png` · `capturas/UXM-04-editar-500-pc.png` · `capturas/UXM-04-nueva-500-pc.png` · `capturas/UXM-04-editar-500-iphonese.png` · `capturas/UXM-04-nueva-500-iphonese.png` · `capturas/UXM-04-error-fuera-de-vista-2500.png` · request `PATCH /api/obras/20 → 409` · `uxm/out-teclado4.txt`, `uxm/error-vista.cjs`
- **Causa probable (opcional):** `features/obras/components/formulario/ObraFormulario.tsx:119` muestra `AvisoConflicto` (y el aviso general) al principio del formulario sin `scrollIntoView` ni mover el foco. El mismo patrón está en `identidad/components/UsuarioFormulario.tsx:125-134` (no medido).
- **Regresión de:** PU-002 / 669cad2 (el aviso de conflicto nuevo nace fuera de la vista; el del 500 ya era así)

### UXM-02 · Mientras guarda, el foco se cae del botón y sale del diálogo; el lector de pantalla no dice "Guardando…"
- **Severidad:** baja
- **Tipo:** accesibilidad
- **F-XX / pantalla:** F-19 diálogo "Agregar certificado o documento" (ficha `/obras/20`) · F-56 diálogo "Nuevo proveedor" (`/proveedores/nuevo`) · F-20 Editar obra · todos los botones con spinner
- **Persona:** Hernán, Carla (PC, solo teclado)
- **Pasos:** 1. Con la red lenta (4 s en el guardado), completar el diálogo. 2. Con Tab llegar a "Guardar" y apretar Enter. 3. Mientras guarda, apretar Tab dos veces.
- **Esperado:** el foco sigue en el botón (o dentro del diálogo) y se anuncia "Guardando…"; el foco no sale del diálogo modal.
- **Obtenido:** al apretar Enter el botón queda `disabled` y el foco pasa a `body`; el nombre del botón sigue "Guardar" / "Guardar proveedor" (el spinner es `aria-hidden` y solo hay `aria-busy`). Con Tab durante el guardado el foco sale del diálogo a "Saltar al contenido" (la trampa de foco no lo retiene). En Nuevo proveedor, al terminar, el foco quedó en "Saltar al contenido" de la ficha; en el documento, al cerrar volvió bien a "+ agregar certificado o documento". Reproducido en 3 formularios.
- **Evidencia:** `capturas/UXM-teclado-guardando.png` · `uxm/out-teclado3.txt` (bloque `documento-guardando`) · `uxm/foco-guardando.cjs` (salida: "guardando: BODY · botón: "Guardar proveedor" disabled=true · Tab guardando → A "Saltar al contenido" [FUERA del diálogo]")
- **Causa probable (opcional):** `shared/ui/Button.tsx` usa `disabled` cuando `cargando` (el navegador saca el foco de un botón deshabilitado); `Dialog.tsx` solo atrapa Tab cuando el foco está en el primero/último control. `aria-disabled` + texto oculto "Guardando…" lo resolvería.
- **Regresión de:** PU-018 / bfc95ac (arreglo visual sin equivalente accesible)

### UXM-03 · Quedan controles de menos de 44 px en el celular: listas "todas / sus obras" de Permisos, logo y "Saltar al contenido"
- **Severidad:** baja
- **Tipo:** celular
- **F-XX / pantalla:** F-60 Tabla de permisos `/usuarios/permisos` · encabezado de todas las pantallas
- **Persona:** Diego (Gerencia de Administración), Hernán
- **Pasos:** 1. Abrir la Tabla de permisos en Pixel 7 y en iPhone SE. 2. Medir el alto de las listas de alcance ("todas" / "sus obras") debajo de "Ver"/"Editar". 3. Medir el logo "TECNOSERVICIOS" y "Saltar al contenido" en cualquier pantalla.
- **Esperado:** 44 px de alto como mínimo (PU-041).
- **Obtenido:** las listas de alcance miden 105×25 px (todas: "Alcance de Pedidos · Dirección", "Alcance de Obras · Compras", …; 16 px de letra, bien), al lado de las listas de nivel que sí miden 113×44. El logo "TECNOSERVICIOS" mide 141×23 y "Saltar al contenido" 156×39. Igual en los dos celulares (1 corrida en cada uno; VA midió los mismos 25 px por separado).
- **Evidencia:** `capturas/UXM-cel-pixel7-permisos.png` · `capturas/UXM-cel-iphonese-permisos.png` · `uxm/out-cel-diego-p7.txt`, `out-cel-diego-se.txt` (`selectsChicos`)
- **Causa probable (opcional):** `.celda__select--a` (Tabla de permisos) no toma `--alto-control` del celular; el logo es un link en línea sin `min-height`.
- **Regresión de:** PU-041 / bfc95ac (arreglo incompleto)

### UXM-04 · Sectores en el celular: la tabla no pasa a tarjetas y "sacar" queda cortado sin aviso
- **Severidad:** baja
- **Tipo:** celular
- **F-XX / pantalla:** F-59 Sectores `/usuarios/sectores`
- **Persona:** Diego (Gerencia de Administración)
- **Pasos:** 1. Abrir Usuarios → Sectores en iPhone SE (375 px) y en Pixel 7 (412 px).
- **Esperado:** como Usuarios y Presupuesto por rubro (PU-022), tarjetas o todo a la vista; si se desliza, el aviso "Deslizá para ver más".
- **Obtenido:** la tabla mide 398 px dentro de un recuadro de 341 px (SE) / 378 px (Pixel 7). En el iPhone SE se lee "renombrar ·" y del botón "sacar" se ve solo la "s" (L337–R403, el recuadro termina en 359); en el Pixel 7 se ve entero por poco pero la columna "Acciones" también se pasa del recuadro. Sin aviso ni sombra. La página no se desplaza a lo ancho. 2 corridas por celular.
- **Evidencia:** `capturas/UXM-05-sectores-iphonese.png` · `capturas/UXM-05-sectores-pixel7.png` · `capturas/UXM-cel-iphonese-sectores.png` · `uxm/sectores-se.cjs` ("iphonese … "sacar" L337 R403 66x44 visible-entero=false · th=[Sector,Usuarios,Acciones FUERA] · texto deslizá=false")
- **Causa probable (opcional):** `SectoresPantalla.tsx` usa `tabla-marco` sin `tabla-marco--tarjetas` ni `--deslizable`.
- **Regresión de:** PU-022 / 8777360 (no cubierto: Sectores no estaba en la lista del PU)

## 4. Lo que está bien (accesibilidad PC y loaders)

- **Aviso de cambios sin guardar** ("Tenés cambios sin guardar. ¿Salir igual?"): `aria-modal`, título como nombre, foco en "Seguir editando", Tab y Shift+Tab atrapados entre sus dos botones, Escape cierra y devuelve el foco al link que lo disparó ("Obras"), foco visible en todo.
- **Errores de formulario:** Nueva obra vacía → foco al "N.º de presupuesto", 11 campos con `aria-invalid` y `aria-describedby` al texto del error, 11 `role=alert`. Igual en el diálogo de documento (foco a "Fecha"). La oferta técnica es un `fieldset` con `legend` y `alert` adentro.
- **Diálogos** (documento, proveedor, estado): foco inicial adentro, Escape cierra, el foco vuelve a quien lo abrió.
- **"Saltar al contenido"**: primer Tab, visible, Enter lleva al contenido ("+ Nueva obra").
- **"Buscando…"** con `role=status` (se anuncia) y la zona atenuada con `aria-busy`.
- **Loaders a 2,5 s y 15 s:** solo en el componente (lista, formulario, historial); al abrir una ficha o Editar, el encabezado y la barra quedan y el `main` dice "Cargando la obra…" / "Preparando el formulario…"; nada siguió girando después de la respuesta; sin resultados viejos engañosos.

## No probado

- **"Destrabar ingreso"**: ningún usuario estaba trabado cuando miré Usuarios (`trabados: null`), y trabar uno es la prueba de bloqueo del Lead (PU-045). Falta: teclado, foco y anuncio del botón y del aviso de éxito.
- **Teclado en el aviso de cambios sin guardar con "Atrás" del navegador**: mi prueba volvió a otra entrada de la misma ruta y el aviso no aplica; lo cubre REG con "atrás" interno.
- **Lector de pantalla real** (NVDA/VoiceOver): solo árbol de accesibilidad y atributos.
- **Teclado virtual del celular** (cuánto tapa de los diálogos largos): Playwright no lo emula.
- **Conflicto 409 en el celular**: medido solo el 500 en el iPhone SE; el aviso de conflicto sale en el mismo lugar del formulario.
