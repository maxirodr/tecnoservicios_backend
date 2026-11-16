# Borrador UXM — QA de carga y loaders + QA celular y accesibilidad

> Rol UXM. Datos creados: obras 2-901-2026 "QA-UXM Tanque de prueba", 2-902-2026 "QA-UXM Galpón norte",
> 2-903-2026 "QA-UXM Cañería sur", 2-911-2026 "QA-UXM Alta celular pixel7", 2-912-2026 "QA-UXM Alta celular
> iphonese"; proveedores "QA-UXM Corralón lento NNNN", "QA-UXM Ferretería pixel7", "QA-UXM Ferretería iphonese";
> sector "QA-UXM Sector carga" (con un permiso cambiado en Obras, solo en ese sector).
> Loaders juzgados con demora artificial (`slow: 2500`, `8000` y `15000` ms por pedido a la API), no con la
> lentitud de base del entorno. El corte de MySQL/Redis de 14:49–15:55 UTC no se reporta acá; lo que falló
> durante el corte se repitió después.
> Celular: Pixel 7 (412 px) e iPhone SE de 375×667. **Ojo con el helper:** `devices['iPhone SE']` de Playwright
> es el modelo de 320×568. Yo lo forcé a 375×667 en mis scripts (`uxm/lib.cjs`). Conviene corregir el helper
> común si otros roles usaron `iphonese`.

## Hallazgos

### UXM-01 · Al cambiar de pestaña, buscar o filtrar, la lista muestra resultados viejos sin ninguna señal de que está cargando
- **Severidad:** media
- **Tipo:** loader
- **F-XX / pantalla:** F-15 · W-05 Obras `/obras` (pestañas, buscador, filtro Responsable) · F-55 · W-14 Proveedores `/proveedores` (Buscar proveedor) · F-03 · W-18 Buscador general `/buscar` · F-58 · W-15 Usuarios `/usuarios` (Buscar por nombre o mail)
- **Persona:** Hernán (Jefatura de Proyectos), Carla (Compras), Diego (Gerencia de Administración)
- **Pasos:** 1. Con la red lenta (2,5 s por pedido), entrar a Obras en "En ejecución". 2. Tocar la pestaña "Confirmadas". 3. Escribir "Galpón" en el buscador. 4. Elegir "Laura" en Responsable. 5. Hacer lo mismo en Proveedores ("zzz…"), en el buscador general (de "QA-UXM" a "Galpón") y en Usuarios ("pablo").
- **Esperado:** la regla del proyecto: el loader va en el componente que recarga. Mientras llega la respuesta, la tabla tiene que mostrar que está cargando (spinner o tabla atenuada con "Buscando…"). No puede mostrar resultados que no corresponden a lo que el usuario eligió.
- **Obtenido:** durante todo el pedido (3–4 s en la prueba) **no aparece ninguna señal** y queda la lista anterior debajo de lo nuevo que eligió el usuario. Ejemplos, reproducidos 2 veces:
  - La pestaña "Confirmadas (6)" queda marcada, pero la tabla sigue mostrando las obras **en ejecución** (2-703-2026, 2-901-2026). Se puede hacer clic en una de esas filas creyendo que es una obra confirmada.
  - Si la pestaña anterior estaba vacía, con "Galpón" escrito se lee **"No encontramos obras con esa búsqueda"** y "Probá con otra parte del N.º, del cliente o del trabajo…" aunque la obra 2-902-2026 "QA-UXM Galpón norte" existe. A los 2,5 s aparece. El usuario cree que la obra no está.
  - Con "Galpón" escrito se siguen viendo las 6 obras confirmadas, y con Responsable = Laura, las obras de Hernán y de Diego.
  - Pasa lo mismo en Proveedores, en el buscador general (el grupo "OBRAS (4)" de la búsqueda anterior sigue debajo de "Galpón") y en Usuarios.
- **Evidencia:** `capturas/UXM-1-pestana-confirmadas-muestra-en-ejecucion-pc.png` · `capturas/UXM-1-buscar-galpon-muestra-otras-pc.png` · `capturas/UXM-1-responsable-laura-muestra-otras-pc.png` · `capturas/UXM-carga-obras-pestana-confirmadas-1800ms.png` (Confirmadas (4) + "No hay obras en esta pestaña") · `capturas/UXM-carga-obras-buscar-1800ms.png` ("No encontramos obras con esa búsqueda" con "Galpón") · `capturas/UXM-carga-buscador-nueva-busqueda-1800ms.png` · `capturas/UXM-carga-prov-buscar-1800ms.png` · `capturas/UXM-carga-usuarios-buscar-1800ms.png`
- **Causa probable (opcional):** `placeholderData: keepPreviousData` sin mirar `isFetching`/`isPlaceholderData` en ninguna pantalla (`features/obras/hooks/useObras.ts:26`, `features/proveedores/hooks/useProveedores.ts:19`, `features/buscador/useBuscador.ts:12`, `features/identidad/hooks/useUsuarios.ts:19`). Además, el título del estado vacío en `ObrasPantalla.tsx` se arma con el `buscar` de la URL (lo nuevo) sobre datos viejos.

### UXM-02 · Mientras guarda, el botón solo se pone gris: no dice "Guardando…" ni muestra un spinner
- **Severidad:** media
- **Tipo:** loader
- **F-XX / pantalla:** todos los guardados: F-19 Agregar certificado o documento (Guardar) · F-21 Cambiar estado ("Pasar a En ejecución") · F-16/F-20 Nueva obra / Editar obra ("Guardar obra", "Guardar cambios") · F-48 Presupuesto por rubro (Guardar) · F-56 Nuevo proveedor ("Guardar proveedor") · F-10 Mi perfil (Guardar) · F-59 Sectores (Guardar) · F-60 Tabla de permisos ("Guardar cambios")
- **Persona:** Hernán, Carla, Diego
- **Pasos:** 1. Con la red lenta, abrir "+ agregar certificado o documento" en una obra, completar y tocar "Guardar". 2. Mirar el botón durante el guardado. 3. Repetir en las otras pantallas de la lista.
- **Esperado:** el botón que guarda muestra que está trabajando ("Guardando…" o spinner adentro), distinto de un botón deshabilitado porque falta completar algo.
- **Obtenido:** el botón pasa a gris claro, con el mismo aspecto que un botón deshabilitado. El texto no cambia ("Guardar") y no aparece ningún spinner. El resto de la pantalla no cambia. Con 15 s de demora, durante 15 s no hay nada que diga que se está guardando. El usuario no sabe si guardó, si falta un dato o si se colgó. Lo único que hay es `aria-busy`, que la pantalla no muestra. Lo bueno: el doble clic no manda dos veces.
- **Evidencia:** `capturas/UXM-carga-documento-guardar-1800ms.png` · `capturas/UXM-4-guardando-solo-boton-gris-pc.png` · `capturas/UXM-carga-estado-confirmar-1800ms.png` · `capturas/UXM-carga-editar-guardar-1800ms.png` · `capturas/UXM-carga-prov-guardar-1800ms.png` · `capturas/UXM-carga-perfil-guardar-1800ms.png` · `capturas/UXM-carga-permisos-guardar-1800ms.png` · `capturas/UXM-carga-rubro-guardar-1800ms.png` · `capturas/UXM-carga-lento15-guardar-6000ms.png`
- **Causa probable (opcional):** `shared/ui/Button.tsx` con `cargando` solo pone `disabled` + `aria-busy`. `Button.css` para `[aria-busy='true']` solo cambia el cursor.

### UXM-03 · "+ agregar presupuesto adicional" no responde durante segundos
- **Severidad:** media
- **Tipo:** loader
- **F-XX / pantalla:** F-18 · W-21 Adicional, desde la ficha `/obras/:id` (botón de la cabecera y botón bajo la tabla Presupuestos)
- **Persona:** Hernán (Jefatura de Proyectos)
- **Pasos:** 1. Con la red lenta, abrir la ficha de una obra. 2. Tocar "+ agregar presupuesto adicional". 3. Esperar 3 s.
- **Esperado:** el diálogo se abre enseguida (con un spinner en el campo N.º mientras busca el número sugerido) o el botón muestra que está cargando.
- **Obtenido:** durante todo el pedido del N.º sugerido **no pasa nada visible**: ni diálogo, ni spinner, ni cambio en el botón. Fueron 2,5 s y 8 s en la prueba (reproducido 2 veces). El diálogo aparece de golpe cuando llega la respuesta. Lo natural es tocar de nuevo.
- **Evidencia:** `capturas/UXM-3-adicional-sin-senal-pc.png` · `capturas/UXM-carga-ficha-adicional-abrir-1800ms.png`
- **Causa probable (opcional):** `features/obras/pantallas/ObraPantalla.tsx`: `dialogo.presupuesto === null && sugerido.isPending ? null : <AdicionalDialog …>` muestra `null` mientras carga.

### UXM-04 · "Cancelar" durante el guardado cierra el diálogo, pero el cambio se guarda igual y sin aviso
- **Severidad:** alta
- **Tipo:** funcional
- **F-XX / pantalla:** F-21 · W-22 Cambiar estado · F-19 Agregar certificado o documento (y todo diálogo con "Cancelar" habilitado mientras guarda)
- **Persona:** Hernán (Jefatura de Proyectos)
- **Pasos:** 1. Con la red lenta (8 s), en la obra 2-903-2026 (Confirmada) tocar "Cambiar estado / pasar a Terminada" y luego "Pasar a En ejecución". 2. Como no pasa nada visible (UXM-02), tocar "Cancelar". 3. Esperar sin recargar.
- **Esperado:** si Cancelar está habilitado, cancela de verdad. Si el pedido ya salió y no se puede cancelar, Cancelar se deshabilita o la pantalla avisa "Ya se guardó". El usuario nunca se tiene que enterar después de algo que creyó cancelado.
- **Obtenido:** el diálogo se cierra y la cabecera sigue diciendo "Confirmada". Unos segundos después, sin ningún mensaje, la obra pasa a "En ejecución". Con el documento pasó lo mismo: "QA-UXM Doc cancelado a mitad" quedó guardado y aparece en la lista (15 s de demora). Con "Pasar a Terminada" significaría archivar una obra que el usuario creyó haber cancelado. Reproducido 2 veces (documento con 15 s y estado con 8 s).
- **Evidencia:** `capturas/UXM-4-guardando-solo-boton-gris-pc.png` (Cancelar habilitado mientras guarda) · `capturas/UXM-4-cancelado-pero-cambio-estado-pc.png` (obra en "En ejecución" sin aviso) · `capturas/UXM-4-cancelar-durante-guardar-pc.png`
- **Causa probable (opcional):** en `CambiarEstadoDialog.tsx` y `DocumentoDialog.tsx`, "Cancelar" (`onCerrar`) no se deshabilita con `isPending`. Como las callbacks de `mutate` no corren con el diálogo desmontado, el aviso "La obra pasó a …" / "Agregamos el documento…" no aparece.

### UXM-05 · Editar obra: la pantalla salta cuando termina de cargar el formulario
- **Severidad:** baja
- **Tipo:** loader
- **F-XX / pantalla:** F-20 · W-23 Editar obra `/obras/:id/editar`
- **Persona:** Hernán (Jefatura de Proyectos)
- **Pasos:** 1. Con la red lenta, tocar "Editar" en la ficha. 2. Mirar la pantalla mientras carga.
- **Esperado:** sin saltos: el lugar del formulario reservado (esqueleto con su alto) o el historial cargado después del formulario.
- **Obtenido:** primero se ve "Preparando el formulario…" (una tarjeta chica) y justo debajo "HISTORIAL DE CAMBIOS" con su lista. Cuando llega el formulario (unos 1.600 px), el historial se va para abajo de golpe. Desplazamiento medido (`layout-shift`): **0,156** y **0,111** en dos corridas (por encima de 0,1 se considera malo). Si el usuario ya estaba leyendo o tocando el historial, lo pierde.
- **Evidencia:** `capturas/UXM-carga-editar-entrada-400ms.png` · `capturas/UXM-carga-editar-entrada-4000ms.png` · medición `SECTION.bloque-ficha` 0,1557 / 0,1114

### UXM-06 · En el celular, las tablas de Usuarios, Presupuesto por rubro y Permisos no entran y no avisan que se pueden deslizar
- **Severidad:** media
- **Tipo:** celular
- **F-XX / pantalla:** F-58 · W-15 Usuarios `/usuarios` · F-48 · W-13 Presupuesto por rubro `/obras/:id/presupuesto-por-rubro` · F-60 Tabla de permisos `/usuarios/permisos`
- **Persona:** Diego (Gerencia de Administración), Hernán
- **Pasos:** 1. Abrir esas pantallas en el celular (Pixel 7 412 px e iPhone SE 375 px).
- **Esperado:** como en Obras y en la ficha (que pasan a tarjetas), los datos se leen sin deslizar a ciegas. Los montos se ven completos para poder cargarlos.
- **Obtenido:** la página no se desplaza a lo ancho, pero cada tabla queda dentro de un recuadro más ancho que la pantalla, sin ninguna señal de que sigue a la derecha:
  - Usuarios (517 px de tabla en 412): la columna Sector queda cortada ("Compra", "Gerenci Adminis", "Ingenie proyect") y "Activo" queda fuera. Los mails se parten en 2 o 3 renglones.
  - Presupuesto por rubro (489 px en 375/412): la columna del presupuesto queda cortada a la mitad ("2-901-2026 REV…", "princ…", el monto cargado se ve "1.500,") y "Total de la obra" queda fuera. No se puede ver lo que uno escribe.
  - Tabla de permisos: 1.729 px de ancho. Además, sus listas tienen letra de 12–14 px: en el iPhone eso hace que la pantalla se agrande sola al tocarlas. Lo mismo pasa con "Cómo se carga" en Presupuesto por rubro (12 px).
- **Evidencia:** `capturas/F-58-usuarios-celular-pixel7.png` · `capturas/UXM-cel-_usuarios-iphonese.png` · `capturas/UXM-cel-_obras_2_presupuesto-por-rubro-iphonese.png` · `capturas/F-48-presupuesto-rubro-celular-pixel7.png` · `capturas/F-48-presupuesto-rubro-celular-iphonese.png` · `capturas/UXM-cel-_usuarios_permisos-iphonese.png` · `capturas/F-60-permisos-celular-pixel7.png`
- **Causa probable (opcional):** las tablas de identidad y montos no usan la variante de tarjetas (`.tabla-marco--tarjetas`, `shared/ui/Tabla.css:52`) que sí usa Obras. Los selects de la tabla de permisos tienen `font-size` < 16 px.

### UXM-07 · En el celular, las pestañas cortan el último nombre y su número
- **Severidad:** baja
- **Tipo:** celular
- **F-XX / pantalla:** F-15 · W-47 Obras `/obras` · F-58/F-59/F-60/F-68 · submenú de Usuarios
- **Persona:** Hernán, Diego
- **Pasos:** 1. Abrir Obras en el celular (412 px o 375 px). 2. Abrir Usuarios.
- **Esperado:** las tres pestañas se leen con su cantidad, o se nota que hay más hacia la derecha.
- **Obtenido:** en Obras se lee "En ejecución (2) · Confirmadas (6) · Terminadas" y el número de Terminadas queda fuera de la pantalla (la pestaña va de 304 a 430 px). En Usuarios, la cuarta pestaña se ve "Plazo". No hay flecha ni sombra que indique que se puede deslizar. El texto de ayuda del buscador de Obras también queda cortado ("…cliente o trabajo" sin terminar).
- **Evidencia:** `capturas/F-15-obras-celular-pixel7.png` · `capturas/F-15-obras-celular-iphonese.png` · `capturas/F-58-usuarios-celular-pixel7.png`

### UXM-08 · Controles de 40 px de alto en el celular, por debajo de los 44 px recomendados
- **Severidad:** baja
- **Tipo:** celular
- **F-XX / pantalla:** todas (botones, campos, pestañas, lupa "Buscar", "editar"/"quitar"); el link "Abrir carpeta de Drive de la obra" mide 23 px de alto
- **Persona:** Hernán, Carla, Diego
- **Pasos:** 1. Medir el alto de los controles en Pixel 7 / iPhone SE.
- **Esperado:** 44 px de alto como mínimo en el celular (objetivo táctil cómodo). En la obra se usa con guantes o con una sola mano.
- **Obtenido:** botones y campos de 40–41 px (`--alto-control: 2.5rem`). En la ficha, "editar" y "quitar" quedan juntos con 12 px de separación y 40 px de alto. El link a la carpeta de Drive mide 23 px.
- **Evidencia:** `capturas/F-17-ficha-obra-celular-pixel7.png` · medición en `uxm/out-cel-pixel7.txt` / `out-cel-se.txt`
- **Causa probable (opcional):** `shared/ui/tokens.css:103` `--alto-control: 2.5rem` (dice "40px: objetivo táctil cómodo") sin aumento para el celular.

### UXM-09 · Las pestañas no se manejan con las flechas del teclado
- **Severidad:** baja
- **Tipo:** accesibilidad
- **F-XX / pantalla:** F-15 Obras (En ejecución / Confirmadas / Terminadas) · F-17 ficha ("Documentos y contactos")
- **Persona:** Hernán (PC)
- **Pasos:** 1. Con Tab, llegar a la pestaña "En ejecución". 2. Apretar → (flecha derecha).
- **Esperado:** si son `role="tab"`, las flechas pasan de una pestaña a otra y solo la pestaña activa recibe el Tab (patrón de pestañas). Si no, que sean botones comunes sin `role="tab"`.
- **Obtenido:** la flecha no hace nada (el foco se queda en "En ejecución") y cada pestaña es una parada de Tab. Un lector de pantalla las anuncia como pestañas pero no se comportan así. Con Tab y Enter igual se pueden usar.
- **Evidencia:** salida `uxm/out-teclado.txt` (bloque `orden-obras`: "ArrowRight en pestañas -> button "En ejecución (3)"")

## Re-verificación de bugs de la entrega 1

- **B-01 (ficha de la obra en el celular): ARREGLADO.** En Pixel 7 y en iPhone SE 375 px, Presupuestos, OC y Certificados pasan a tarjetas. El nombre del archivo se lee entero ("oferta tecnica QA-UXM.pdf · 1 KB") y "editar"/"quitar" quedan dentro de la pantalla (L29–R184). Sin desplazamiento a lo ancho. `capturas/F-17-ficha-obra-celular-pixel7.png`, `capturas/F-17-ficha-obra-celular-iphonese.png`.
- **B-03 (campana / Avisos en el celular): ARREGLADO.** No hay campana en la barra superior (PC ni celular). La barra inferior del celular tiene solo "Obras · Más" (no están "Avisos" ni "Tareas"). "Más" lleva a "Proveedores · Mi perfil · Salir". `capturas/F-15-obras-celular-pixel7.png`, `capturas/F-02-mas-celular-pixel7.png`.

## Lo que está bien (para no repetir la prueba)

- Navegar entre secciones no muestra spinner de página completa. "Cargando el sistema…" aparece solo al abrir la app la primera vez. Cada sección carga en su lugar ("Cargando proveedores…", "Cargando la obra…", "Cargando el proveedor…" / "Cargando lo gastado…", "Cargando la tabla de permisos…"). No vi desplazamientos fuera de Editar obra (UXM-05): todos midieron < 0,03.
- Subir un adjunto muestra la fila del archivo con su spinner y deshabilita Guardar hasta que termina. El doble clic en Guardar no manda dos veces.
- Cambiar una celda de permisos muestra "1 cambio sin guardar" sin recargar la tabla.
- No hay polling en la Semana 1. Al volver a la pestaña del navegador se refresca en segundo plano sin parpadeo.
- Con un pedido muy lento (15 s), las pantallas que cargan muestran su "Cargando…" todo el tiempo y no quedan girando para siempre. El problema son las acciones (UXM-02, UXM-03, UXM-04).
- Celular (412 y 375 px): sin desplazamiento a lo ancho en ingreso, obras, alta de obra, ficha, cambiar estado, documento, editar, proveedores (listado, búsqueda, alta, ficha), buscador, mi perfil, Más, Sectores y Plazos. Los campos tienen los tipos correctos: mail `type=email` + `inputmode=email`, teléfono `type=tel`, CUIT `inputmode=numeric`, montos `inputmode=decimal`, Drive y links `type=url`, fechas `type=date`, plazos `inputmode=numeric`, buscadores `type=search`. Los campos de texto tienen letra de 16 px (no hay zoom en el iPhone), salvo UXM-06.
- Teclado (PC): el primer Tab muestra "Saltar al contenido" y Enter lleva el foco a `main`. El foco se ve en todo, con orden lógico (en el alta sigue las secciones Presupuesto → Cliente → Contactos → Cancelar/Guardar). Los diálogos (documento, cambiar estado, adicional, nuevo proveedor) llevan el foco al primer campo, lo atrapan (14 Tab sin salir) y Escape cierra y devuelve el foco al botón que los abrió. Al guardar vacío, el foco va al primer campo con error, con `aria-invalid`, `aria-describedby` apuntando al error y `role="alert"`. Todos los campos tienen label asociado y no hay botones sin nombre. No encontré textos con contraste insuficiente (< 4,5:1) en Obras, Alta, Ficha y Proveedores.

## Cobertura

| F-XX | Estado | Hallazgos | Qué probé |
|---|---|---|---|
| F-02 Menú según el sector | ✅ | — | Barra PC; celular: barra inferior "Obras · Más", Más; B-03 re-verificado |
| F-03 Buscador general | ⚠️ | UXM-01 | Loader con red lenta (2ª búsqueda); celular 412/375; tipo `search` |
| F-10 Mi perfil | ⚠️ | UXM-02 | Guardar con red lenta; celular 412/375; teclado; `type=tel` |
| F-15 Listado de obras | ⚠️ | UXM-01, UXM-07, UXM-09 | Pestañas, buscar y responsable con red lenta; layout shift; celular; teclado |
| F-16 Alta de obra | ⚠️ | UXM-02 | Alta completa en PC, Pixel 7 e iPhone SE 375 (5 obras); teclado y errores con el formulario vacío |
| F-17 Ficha con pestañas | ✅ | UXM-09 | Carga lenta; celular 412/375 (B-01 arreglado); teclado |
| F-18 Presupuestos adicionales | ⚠️ | UXM-03 | Abrir el adicional con red lenta (2,5 s y 8 s); teclado del diálogo |
| F-19 Documentos y contactos | ⚠️ | UXM-02, UXM-04 | Subir adjunto y guardar con 2,5 s y 15 s; Cancelar a mitad; diálogo en celular; teclado |
| F-20 Editar obra | ⚠️ | UXM-02, UXM-05 | Entrada y guardado con red lenta; CLS; celular |
| F-21 Cambiar estado | ⚠️ | UXM-02, UXM-04 | Confirmar con red lenta; Cancelar a mitad; celular (opciones de 40 px); teclado |
| F-48 Presupuesto por rubro | ⚠️ | UXM-02, UXM-06 | Carga y guardado (Diego) con red lenta; `inputmode=decimal`; celular |
| F-55 Proveedores con buscador | ⚠️ | UXM-01 | Buscar con red lenta; abrir ficha; celular (Carla) |
| F-56 Alta de proveedor | ⚠️ | UXM-02 | Alta con red lenta (PC) y en celular 412/375; teclado del diálogo |
| F-58 Usuarios | ⚠️ | UXM-01, UXM-06, UXM-07 | Entrada y búsqueda con red lenta; celular; diálogo Agregar usuario en celular |
| F-59 Sectores | ⚠️ | UXM-02 | Alta de "QA-UXM Sector carga" con red lenta; celular |
| F-60 Tabla de permisos | ⚠️ | UXM-02, UXM-06 | Cambiar una celda (de mi sector) y guardar con red lenta; celular |
| F-68 Plazos | ✅ | — | Solo celular (sin desbordes) |
| F-01 Ingreso (solo celular y teclado) | ✅ | — | Pantalla de ingreso en 412/375 (`type=email`, `inputmode=email`); teclado; mail inválido con `aria-invalid`. No pedí códigos |

## No probado

- **F-01 completo, cerrar sesión y sesión vencida:** no los probé. El brief lo prohíbe (no pedir códigos ni cerrar sesión con las sesiones compartidas).
- **Celular real (cámara/galería, teclado virtual real, Safari/WebKit):** usé Chromium emulando Pixel 7 e iPhone SE 375×667. El tipo de teclado lo deduje de los atributos `type`/`inputmode`, no lo vi en un teléfono.
- **Lector de pantalla real (NVDA/VoiceOver):** solo revisé atributos (`role`, `aria-*`, labels) y la navegación con teclado.
- **Contraste:** cálculo automático sobre el texto de Obras, Alta, Ficha y Proveedores; no medí estados hover/foco ni la pantalla de Usuarios.
- **F-22, F-23, F-14, F-61:** sin pruebas específicas de carga o celular aparte de las de la ficha y edición.
