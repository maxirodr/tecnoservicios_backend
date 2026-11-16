# Hallazgos · Prueba de usuario Semana 1 · Tecnoservicios (NS-2026-025)

- **Fecha:** 2026-11-16
- **Entorno:** base `tecnoservicios_prueba_usuario` · front `http://localhost:5316` · API `http://localhost:8340`
- **Conteo:** 45 hallazgos · bloqueante 2 · alta 3 · media 18 · baja 22
- **Orden:** por severidad (bloqueante, alta, media, baja) y, dentro de cada una, por tipo. Al final de cada **Evidencia**, `origen:` lista los borradores de `borradores\` que se consolidaron en el hallazgo.

### PU-001 · Un link que todavía se está agregando se pierde al guardar, sin aviso
- **Severidad:** bloqueante
- **Tipo:** funcional
- **F-XX / pantalla:** F-16 · W-06 Alta de obra · `/obras/nueva` (campo "OC / OT / contrato / mail de aprobación") · F-19 · W-20 Agregar certificado o documento · `/obras/{id}` · por código, lo mismo en W-21 Adicional y W-23 Editar obra (todos usan el campo "Archivo o link")
- **Persona:** Diego (Gerencia de Administración), Laura (Ingeniería de proyectos)
- **Pasos:** 1. Nueva obra con todo completo y la oferta técnica subida (o, en la ficha, "+ agregar certificado o documento" con fecha, nombre y un PDF). 2. En el campo de archivo o link tocar "Pegar link", pegar `https://drive.example/oc-2-802-2026` y tocar "Agregar link". 3. Mientras "Agregar link" está gris (cargando; con el servidor lento tarda unos segundos), tocar "Guardar obra" / "Guardar".
- **Esperado:** el botón de guardar queda deshabilitado mientras se agrega el link, como ya pasa con los archivos ("Esperá a que terminen de subir los archivos para guardar."), o el link se guarda junto con la obra o el documento (ADR-014: cada documento tiene sus archivos o links).
- **Obtenido:** se guarda sin el link y sin ningún aviso. En el alta, la obra se crea y la ficha muestra "—" en "Archivo o link" de la OC (`POST /api/obras` sale con `"aprobacion":{"tipo":"oc","numero":"4500","adjunto":null}`). En el documento aparece "Agregamos el documento a la obra." y queda solo el PDF (`POST /api/obras/9/documentos` con `"adjuntos":[42]` sale a las 16:14:09; la respuesta del link llega 16:14:17). El link queda huérfano en `adjuntos` (`sujeto_id` NULL; se purga a las 24 h). Pasó también sin forzar nada, con el servidor lento (el "Certificado N.º 1" se guardó sin el link "Certificado firmado (Drive)"). Reproducido 4 veces (obras 2-801-2026, 2-802-2026 y 2-704-2026).
- **Evidencia:** `capturas/PU-001-1.png` (alta: "Agregar link" cargando y "Guardar obra" habilitado) · `capturas/PU-001-2.png` (ficha 2-801-2026: OC sin archivo ni link) · `capturas/PU-001-3.png` (documento: "Guardar" habilitado con el link en curso) · `capturas/PU-001-4.png` (documento guardado sin el link) · tabla `adjuntos` ids 19 y siguientes con `sujeto_id` NULL · script `obr/s7d-carrera.cjs` (demora de 8 s en `POST /api/adjuntos` para reproducirlo siempre) · origen: ENT-01, OBR-01
- **Causa probable (opcional):** `src/shared/adjuntos/AdjuntosField.tsx`: `subiendo = pendientes.some(...)` cuenta solo archivos; el `enviando` de `PegarLink.tsx` no llega a `onSubiendoChange`, así que el formulario no se entera del link en curso.

### PU-002 · Dos pestañas o dos personas editando la misma obra: la segunda pisa sin aviso lo que guardó la primera
- **Severidad:** bloqueante
- **Tipo:** funcional
- **F-XX / pantalla:** F-20 · W-23 Editar obra · `/obras/{id}/editar`
- **Persona:** Hernán (Jefatura de Proyectos) y Laura (Ingeniería de proyectos); Diego con dos pestañas
- **Pasos:** 1. Hernán y Laura abren "Editar" de la obra 2-704-2026 al mismo tiempo (o una persona en dos pestañas). 2. Hernán cambia el teléfono del solicitante a "291 555-0001" y toca "Guardar cambios". 3. Laura, sin recargar, cambia solo la planta a "QA-OBR Muelle 4" y toca "Guardar cambios".
- **Esperado:** quedan los dos cambios, o Laura recibe el aviso que el sistema ya tiene escrito ("Alguien cambió esto mientras lo tenías abierto. Actualizá y revisá."). Plan de pruebas §4: "dos pestañas editando lo mismo".
- **Obtenido:** Laura ve "Guardamos los cambios de la obra." y el teléfono vuelve a "291 000-0000" sin que nadie lo pida. El historial se lo atribuye a Laura: "Contacto solicitante: teléfono: 291 555-0001 → 291 000-0000". Con dos pestañas de Diego, el Cliente "QA-ENT Cliente de A" volvió a "QA-ENT Cliente" (verificado en la base). `PATCH /api/obras/{id}` → 200 las dos veces. Reproducido 4 veces (obras 2-701-2026, 2-704-2026, 2-801-2026 y 2-802-2026).
- **Evidencia:** `capturas/PU-002-1.png` (obra 2-704-2026 tras el guardado de Laura: planta nueva, teléfono de Hernán perdido) · scripts `obr/s8.cjs`, `obr/s5-editar.cjs` · `PATCH /api/obras/{id}` manda el formulario entero (nombre, cliente, planta, responsable, fechas, carpeta, contactos) aunque se toque un solo dato · origen: ENT-07, OBR-02
- **Causa probable (opcional):** el front manda todos los campos (`datosObraPayload`) y el backend no controla versión (`updated_at`), así que nunca hay 409. Opciones: mandar solo los campos tocados y/o devolver 409 si la obra cambió desde que se abrió.

### PU-003 · "Cancelar" durante el guardado cierra el diálogo, pero el cambio se guarda igual y sin aviso
- **Severidad:** alta
- **Tipo:** funcional
- **F-XX / pantalla:** F-21 · W-22 Cambiar estado · F-19 · W-20 Agregar certificado o documento · `/obras/{id}` (todo diálogo con "Cancelar" habilitado mientras guarda)
- **Persona:** Hernán (Jefatura de Proyectos)
- **Pasos:** 1. Con la red lenta (8 s), en la obra 2-903-2026 (Confirmada) tocar "Cambiar estado / pasar a Terminada" y luego "Pasar a En ejecución". 2. Como no pasa nada visible (ver PU-018), tocar "Cancelar". 3. Esperar sin recargar.
- **Esperado:** si "Cancelar" está habilitado, cancela de verdad; si el pedido ya salió, "Cancelar" se deshabilita o la pantalla avisa "Ya se guardó". El usuario nunca se tiene que enterar después de algo que creyó cancelado.
- **Obtenido:** el diálogo se cierra y la cabecera sigue diciendo "Confirmada"; unos segundos después, sin ningún mensaje, la obra pasa a "En ejecución". Con el documento pasó lo mismo: "QA-UXM Doc cancelado a mitad" quedó guardado y aparece en la lista (15 s de demora). Con "Pasar a Terminada" significaría archivar una obra que el usuario creyó haber cancelado. Reproducido 2 veces.
- **Evidencia:** `capturas/PU-003-1.png` ("Cancelar" habilitado mientras guarda) · `capturas/PU-003-2.png` (obra en "En ejecución" sin aviso) · `capturas/PU-003-3.png` · origen: UXM-04
- **Causa probable (opcional):** en `CambiarEstadoDialog.tsx` y `DocumentoDialog.tsx`, "Cancelar" (`onCerrar`) no se deshabilita con `isPending`; con el diálogo desmontado, las callbacks de `mutate` no corren y el aviso "La obra pasó a …" / "Agregamos el documento…" no aparece.

### PU-004 · Si la sesión vence a mitad de un formulario, se pierde lo escrito y no se dice por qué
- **Severidad:** alta
- **Tipo:** funcional
- **F-XX / pantalla:** F-01 (sesión vencida) · W-38 Alta de proveedor · `/proveedores` → "+ Nuevo proveedor" (aplica a todo formulario)
- **Persona:** QA-LEAD Uno (Compras)
- **Pasos:** 1. Entrar y abrir "+ Nuevo proveedor". 2. Escribir el nombre "QA-LEAD Proveedor sesión vencida". 3. Simular el vencimiento de la sesión (borrar las cookies del navegador, equivalente a que la sesión venza en el servidor o se pierda por un reinicio de Redis, ver PU-020). 4. Tocar "Guardar proveedor".
- **Esperado:** un aviso claro ("Tu sesión venció. Volvé a ingresar.", texto que ya existe en `src/shared/api/ApiError.ts`) y, al volver a entrar, el formulario con lo escrito (o al menos avisar que hay que volver a cargarlo). El plan de la prueba lo pide: "¿qué ve?, ¿pierde lo escrito?".
- **Obtenido:** lleva directo a la pantalla de ingreso, vacía, sin ningún mensaje: "Mail · Te mandamos un código para entrar. · Enviar código". Lo escrito se pierde. Red: `POST /api/proveedores → 401`, `GET /api/me → 401`.
- **Evidencia:** `capturas/PU-004-1.png` (formulario antes) · `capturas/PU-004-2.png` (ingreso vacío, sin mensaje) · origen: LEAD-01
- **Causa probable (opcional):** `src/app/queryClient.ts` (401 global → invalida la sesión y el guard redirige) no muestra el mensaje de `STATUS_NO_AUTENTICADO` ni guarda el borrador. La sesión dura 90 días, pero un reinicio de Redis la corta para toda la empresa a la vez.

### PU-005 · Abrir una dirección de la API sin sesión da 500 "El sistema tuvo un problema" en vez de pedir ingresar
- **Severidad:** alta
- **Tipo:** error-servidor
- **F-XX / pantalla:** transversal · F-19 descarga de adjuntos `GET /api/adjuntos/{id}` (pasa con cualquier `/api/...`, p. ej. `/api/obras/1`)
- **Persona:** cualquiera (sin sesión: link de descarga copiado, compartido o abierto con la sesión vencida)
- **Pasos:** 1. Sin sesión, abrir en el navegador `http://localhost:8340/api/adjuntos/2` (o `/api/adjuntos/999999`, `/api/obras/1`), sin el encabezado JSON que manda el sistema.
- **Esperado:** 401 "Tenés que ingresar para seguir." (lo que responde el mismo pedido con `Accept: application/json`) o una pantalla que lleve a ingresar.
- **Obtenido:** 500 con el cuerpo `{"message":"El sistema tuvo un problema. Volvé a intentar en un momento."}` en el navegador, y una entrada en el canal `errors` por cada intento: "Route [login] not defined." (`RouteNotFoundException`, acción `AdjuntoController@show`). Reproducido 3 veces con curl y 3 entradas en el log (16:15 y 16:22 UTC).
- **Evidencia:** sin captura · `storage/logs/errors-2026-11-16.log` 16:15:04 · `curl http://localhost:8340/api/obras/1` → 500 · con `-H "Accept: application/json"` → 401 · origen: OBR-03, LEAD-05 (visto también por ENT en `errors-*.log`)
- **Causa probable (opcional):** el middleware `auth` redirige a `route('login')` cuando el pedido no es JSON y esa ruta no existe. Falta forzar JSON en `/api/*` (o definir `redirectGuestsTo`).

### PU-006 · F5, "atrás", "Cancelar" o cambiar de pestaña con cambios sin guardar los borra sin preguntar
- **Severidad:** media
- **Tipo:** funcional
- **F-XX / pantalla:** F-16 · W-06 `/obras/nueva` (15 campos y 2 adjuntos) · F-20 · W-23 `/obras/{id}/editar` · F-10 Mi perfil · F-60 · W-15 Tabla de permisos `/usuarios/permisos`
- **Persona:** Diego (Gerencia de Administración)
- **Pasos:** 1. Nueva obra: escribir cliente, nombre del trabajo, etc. (o en Tabla de permisos cambiar 4 celdas de "QA-ADM Pruebas": la barra dice "4 cambios sin guardar"). 2. Apretar F5, o "atrás" del navegador, o "Cancelar", o tocar la pestaña "Sectores". 3. Volver.
- **Esperado:** "Tenés datos sin guardar. ¿Salir igual?" antes de perderlos (o recuperar el borrador al volver). La tabla de permisos tiene 12 columnas × 14 filas: armar varios cambios y perderlos de un clic es fácil.
- **Obtenido:** se va sin preguntar y no aparece el aviso del navegador. Nueva obra se recarga vacía (N.º = ""); "atrás" vuelve a Obras y al ir "adelante" el formulario está vacío. En la tabla de permisos, al volver la fila está como antes: los "4 cambios sin guardar" se perdieron. Reproducido 4 veces (2 en Nueva obra, 2 en permisos: pestaña y F5).
- **Evidencia:** `capturas/PU-006-1.png` (tabla con "4 cambios sin guardar" antes de salir) · scripts `ent/obra-extra.cjs`, `ent/atras.cjs`, `adm/07-permisos.cjs`, `adm/12-ana-impacto.cjs` ("F5 con cambio sin guardar → diálogo del navegador: null") · origen: ENT-08, ADM-03
- **Causa probable (opcional):** los formularios y `TablaPermisosPantalla.tsx` guardan lo escrito en estado local sin `useBlocker`/`beforeunload`.

### PU-007 · "Cancelar" en Nueva obra abierta en una pestaña nueva saca al usuario del sistema
- **Severidad:** media
- **Tipo:** funcional
- **F-XX / pantalla:** F-16 · W-06 Alta de obra · `/obras/nueva`
- **Persona:** Diego (Gerencia de Administración)
- **Pasos:** 1. Abrir `/obras/nueva` en una pestaña nueva (favorito o link que le pasaron). 2. Tocar "Cancelar".
- **Esperado:** vuelve a Obras (como "Cancelar" de Editar, que vuelve a la ficha).
- **Obtenido:** la pestaña queda en blanco ("about:blank"): salió del sistema. Reproducido 2 veces.
- **Evidencia:** `capturas/PU-007-1.png` · origen: ENT-09
- **Causa probable (opcional):** `src/features/obras/pantallas/NuevaObraPantalla.tsx:43` `onCancelar={() => void navigate(-1)}`.

### PU-008 · Doble clic sin protección en "Sacar sector" y en "Guardar cambios" de la tabla de permisos
- **Severidad:** media
- **Tipo:** funcional
- **F-XX / pantalla:** F-59 · W-40 Sectores · `/usuarios/sectores` (ventana "Sacar · …") · F-60 · W-15 Tabla de permisos · `/usuarios/permisos`
- **Persona:** Diego (Gerencia de Administración)
- **Pasos:** 1. Usuarios → Sectores → "sacar" en un sector sin usuarios (ej. "QA-ADM Sacar Doble") → doble clic en "Sacar sector". 2. Usuarios → Tabla de permisos → cambiar una celda (ej. "Gastos y facturas · QA-ADM Doble" a Ver) → doble clic en "Guardar cambios".
- **Esperado:** un solo envío; la ventana se cierra con "Sacamos QA-ADM Sacar Doble." (como con un clic). Plan de pruebas §4: doble clic no crea ni rompe nada.
- **Obtenido:** en Sectores salen dos `DELETE /api/sectores/{id}` (200 y 404): el sector se saca, pero la ventana queda abierta con el error en rojo "No encontramos lo que buscabas." sobre "¿Sacar QA-ADM Sacar Doble?…"; el usuario cree que falló. En la tabla de permisos salen dos `PUT /api/permisos` iguales (ambos 200); hoy no duplica nada (verificado en `actividades`) y muestra "Guardamos la tabla de permisos. Rige desde el próximo pedido de cada persona al sistema.", pero es el mismo agujero en un botón que guarda permisos. Reproducido 5 veces (2 en Sectores, 3 en permisos). Con un clic anda bien.
- **Evidencia:** `capturas/PU-008-1.png` (sector sacado con un clic) · `capturas/PU-008-2.png` (doble clic: "No encontramos lo que buscabas.") · `capturas/PU-008-3.png` (permisos tras doble clic) · `DELETE /api/sectores/13 → 200` + `→ 404`; `DELETE /api/sectores/16 → 404` · `PUT /api/permisos` ×2 a los 0,1 s · origen: ADM-01, ADM-02
- **Causa probable (opcional):** `src/features/identidad/components/SacarSectorDialog.tsx` (`sacar.mutate` en `onClick`, el `cargando` llega un render tarde) y `src/features/identidad/pantallas/TablaPermisosPantalla.tsx` (`onClick={enviar}` sin guarda ni clave de idempotencia). Los altas no lo tienen porque son `<form>` con clave de idempotencia.

### PU-009 · Buscar solo signos ("%%", "--", "__", "..") trae todos los proveedores
- **Severidad:** media
- **Tipo:** funcional
- **F-XX / pantalla:** F-55 · W-14 Proveedores · `/proveedores` · F-03 · W-18 Buscador general · `/buscar`
- **Persona:** Carla (Compras); igual con Hernán y Diego
- **Pasos:** 1. En `/buscar`, escribir `%%` (o `--`, `__`, `''`, `..`, o dos emojis). 2. Mirar los grupos. 3. En `/proveedores`, escribir `%%` o `_` en "Buscar proveedor…".
- **Esperado:** "Sin resultados" en los dos grupos, igual que hace Obras con la misma búsqueda.
- **Obtenido:** en el buscador general, "OBRAS (0) · Sin resultados." pero "PROVEEDORES (3)" con todos los proveedores ("Gastos sin comprobante", "QA-PRV Corralón Ñandú S.R.L.", "QA-PRV Prueba CUIT"). En el listado, `%%` y `_` muestran la lista completa como si no se hubiera buscado nada. Con la carga inicial (cientos de proveedores, semana 4) la persona ve una lista enorme que no tiene que ver con lo que buscó. No expone datos a quien no tiene permiso. Reproducido 2 veces.
- **Evidencia:** `capturas/PU-009-1.png` · `capturas/PU-009-2.png` · `GET /api/buscar?q=%25%25` → 200 con `proveedores.cantidad: 3` y 0 obras · origen: PRV-06
- **Causa probable (opcional):** `app/Modules/Proveedores/Infrastructure/Consultas/PorNombre.php`: si `NombreProveedor::busqueda()` deja el texto vacío, devuelve la consulta sin filtro.

### PU-010 · Un link con acentos o eñe se rechaza diciendo que "tiene que empezar con https://"
- **Severidad:** media
- **Tipo:** mensaje
- **F-XX / pantalla:** F-16, F-18, F-19 · W-06, W-20, W-21 · "Pegar link" en cualquier adjunto
- **Persona:** Diego (Gerencia de Administración)
- **Pasos:** 1. Nueva obra → Presupuesto (oferta técnica) → "Pegar link". 2. Pegar `https://drive.example/oferta-ñandú`. 3. "Agregar link".
- **Esperado:** se acepta (es un link https válido; los de OneDrive/SharePoint con carpetas en castellano traen tildes) o, si no, un mensaje con el motivo real.
- **Obtenido:** "El link tiene que empezar con http:// o https://." — y el link empieza con https://. Lo mismo con `https://exa mple.com` (espacio). La versión con `%C3%A9` sí entra. Reproducido en pantalla y por la API.
- **Evidencia:** sin captura · `POST /api/adjuntos {url:"https://drive.example/oferta-ñandú"}` → 422 `errors.url = ["El link tiene que empezar con http:// o https://."]` · origen: ENT-02
- **Causa probable (opcional):** `app/Shared/Adjuntos/Http/Rules/LinkHttp.php` usa `FILTER_VALIDATE_URL` (rechaza no ASCII) con un único mensaje; el front (`normalizarLink`) podría mandar `url.href` ya codificado.

### PU-011 · Un archivo vacío (0 bytes) dice "Ese tipo de archivo no se acepta" y figura como "1 KB"
- **Severidad:** media
- **Tipo:** mensaje
- **F-XX / pantalla:** F-16 / F-19 · cualquier "Subir archivo"
- **Persona:** Diego (Gerencia de Administración)
- **Pasos:** 1. Nueva obra → Presupuesto (oferta técnica) → "Subir archivo". 2. Elegir un `vacio.pdf` de 0 bytes (descarga cortada o escaneo fallido).
- **Esperado:** "El archivo está vacío (0 KB). Elegí otro." y el tamaño "0 KB".
- **Obtenido:** "vacio.pdf · 1 KB" y "Ese tipo de archivo no se acepta. Subí un PDF, una imagen, un Excel, un Word, un CSV o un TXT." La persona subió un PDF y se le dice que el PDF no se acepta. Reproducido 2 veces.
- **Evidencia:** `capturas/PU-011-1.png` · `POST /api/adjuntos` → 422 · origen: ENT-03
- **Causa probable (opcional):** front `formatearTamano` usa `Math.max(1, …)`; backend `TipoPermitidoPorContenido` no distingue el archivo vacío.

### PU-012 · Si pegás el link y no tocás "Agregar link", al guardar te dice que no pegaste nada
- **Severidad:** media
- **Tipo:** mensaje
- **F-XX / pantalla:** F-16 · W-06 `/obras/nueva` (mismo `PegarLink` en W-20 y W-21)
- **Persona:** Diego (Gerencia de Administración)
- **Pasos:** 1. Nueva obra con todo completo. 2. Oferta técnica → "Pegar link" → pegar `https://drive.example/oferta-sin-agregar`. 3. Sin tocar "Agregar link", tocar "Guardar obra".
- **Esperado:** el link se agrega solo al guardar, o el aviso dice qué falta: "Tocá «Agregar link» para sumar el link que pegaste."
- **Obtenido:** "Subí la oferta técnica o pegá su link." — la persona acaba de pegarlo y el link sigue escrito en la caja. En el campo opcional de la OC el mismo descuido haría que el link se pierda sin aviso (deducido del código y de PU-001, no reproducido aparte).
- **Evidencia:** `capturas/PU-012-1.png` · origen: ENT-06
- **Causa probable (opcional):** `PegarLink.tsx` guarda la URL en estado local; el formulario no sabe que hay un link escrito sin agregar.

### PU-013 · Proveedor repetido: dice "podés guardar igual" y después no deja guardar
- **Severidad:** media
- **Tipo:** mensaje
- **F-XX / pantalla:** F-56 · W-38 Nuevo / Editar proveedor · `/proveedores`
- **Persona:** Diego (Gerencia de Administración)
- **Pasos:** 1. "+ Nuevo proveedor" con el CUIT de "QA-PRV Corralón Ñandú S.R.L." (o editar uno y ponerle el nombre "qa-prv corralon nandu s.r.l."). 2. Leer el aviso de parecidos. 3. "Guardar proveedor".
- **Esperado:** con el mismo CUIT o el mismo nombre, el aviso dice desde el principio "Ya está cargado: QA-PRV Corralón Ñandú S.R.L. Abrí su ficha.", sin invitar a guardar.
- **Obtenido:** "Parecidos ya cargados: QA-PRV Corralón Ñandú S.R.L. (mismo CUIT) · Si es otro proveedor, podés guardar igual." y al guardar: "Ese CUIT ya es de QA-PRV Corralón Ñandú S.R.L." / "Ya existe QA-PRV Corralón Ñandú S.R.L.". Reproducido 2 veces (por CUIT y por nombre).
- **Evidencia:** `capturas/PU-013-1.png` (por CUIT) · `capturas/PU-013-2.png` (por nombre) · origen: ENT-11 (el doble punto del mismo caso va en PU-034)

### PU-014 · "Tu sector no tiene permiso para ver esto" cuando el sector sí puede ver y lo que falta es editar
- **Severidad:** media
- **Tipo:** mensaje
- **F-XX / pantalla:** F-02 / F-16 · W-06 `/obras/nueva` (Carla) · F-48 · W-13 `/obras/{id}/presupuesto-por-rubro` de una obra ajena (Pablo) · toda pantalla con `RequirePermiso nivel="editar"`
- **Persona:** Carla (Compras: Obras = Ver) y Pablo (Ingeniería: Presupuesto por rubro = Ver · solo sus obras)
- **Pasos:** 1. Carla abre `/obras/nueva` escribiendo la dirección (o desde un link compartido). 2. Pablo abre el presupuesto por rubro de una obra de Laura escribiendo la dirección.
- **Esperado:** decir lo que pasa: "Tu sector puede ver las obras pero no darlas de alta. Si lo necesitás, pedíselo a gerencia." (con link a Obras) / "Ves el presupuesto por rubro solo de las obras donde sos responsable."
- **Obtenido:** "No tenés acceso a esta sección · Obras: tu sector no tiene permiso para ver esto. Si lo necesitás, pedíselo a gerencia." y "Presupuesto por rubro: tu sector no tiene permiso para ver esto. …". Carla tiene Obras en el menú y ve el listado; Pablo ve el presupuesto por rubro de sus obras. El bloqueo es correcto (la API da 403); el texto contradice lo que la persona ve.
- **Evidencia:** `capturas/PU-014-1.png` (Carla en `/obras/nueva`) · scripts `obr/s6-listado.cjs`, `obr/s8.cjs` · origen: OBR-05, PRV-10
- **Causa probable (opcional):** `src/shared/ui/States.tsx:90` (`NoAccessState`) dice siempre "permiso para ver esto", sin mirar si lo que falta es Editar o el alcance "solo sus obras".

### PU-015 · Montos con punto decimal: "Escribí el monto con números" cuando ya son números
- **Severidad:** media
- **Tipo:** mensaje
- **F-XX / pantalla:** F-48 · W-13 Presupuesto por rubro · `/obras/{id}/presupuesto-por-rubro`
- **Persona:** Diego (Gerencia de Administración)
- **Pasos:** 1. Escribir `1234.56` o `1.5` en un rubro (lo que sale al copiar de un sistema o planilla con punto decimal). 2. Guardar.
- **Esperado:** "Usá coma para los decimales, por ejemplo 1234,56."
- **Obtenido:** "Escribí el monto con números, por ejemplo 1.500.000,50." El resto de los casos anda bien (negativo, 0, 3 decimales, 16 dígitos, doble clic).
- **Evidencia:** `capturas/PU-015-1.png` · `capturas/PU-015-2.png` · origen: ENT-13

### PU-016 · Ingreso: "Ya pediste varios códigos. Probá de nuevo en unos minutos." no dice cuánto esperar
- **Severidad:** media
- **Tipo:** mensaje
- **F-XX / pantalla:** F-01 · W-16 Ingreso · `/ingresar`
- **Persona:** QA-LEAD Uno (Compras)
- **Pasos:** 1. Pedir el código 3 veces en menos de 10 minutos para el mismo mail. 2. Pedirlo una cuarta vez.
- **Esperado:** que diga cuánto esperar (plan de la prueba: "el mensaje tiene que decir cuánto esperar"), p. ej. "Ya pediste 3 códigos. Probá de nuevo en 7 minutos." El backend conoce el tiempo (`Retry-After` del limitador).
- **Obtenido:** "Ya pediste varios códigos. Probá de nuevo en unos minutos." (`POST /api/auth/pedir-codigo → 429`). Mismo texto para el límite por IP (20 por hora).
- **Evidencia:** `capturas/PU-016-1.png` · origen: LEAD-02
- **Causa probable (opcional):** `IdentidadServiceProvider::DEMASIADOS_CODIGOS` es un texto fijo.

### PU-017 · Al cambiar de pestaña, buscar o filtrar, la lista muestra resultados viejos sin ninguna señal de que está cargando
- **Severidad:** media
- **Tipo:** loader
- **F-XX / pantalla:** F-15 · W-05 Obras `/obras` (pestañas, buscador, filtro Responsable) · F-55 · W-14 Proveedores `/proveedores` · F-03 · W-18 Buscador general `/buscar` · F-58 · W-15 Usuarios `/usuarios`
- **Persona:** Hernán (Jefatura de Proyectos), Carla (Compras), Diego (Gerencia de Administración)
- **Pasos:** 1. Con la red lenta (2,5 s por pedido), entrar a Obras en "En ejecución". 2. Tocar la pestaña "Confirmadas". 3. Escribir "Galpón" en el buscador. 4. Elegir "Laura" en Responsable. 5. Repetir en Proveedores, en el buscador general y en Usuarios ("pablo").
- **Esperado:** el loader va en el componente que recarga (spinner o tabla atenuada con "Buscando…"); nunca resultados que no corresponden a lo elegido.
- **Obtenido:** durante todo el pedido no aparece ninguna señal y queda la lista anterior. "Confirmadas (6)" queda marcada pero la tabla sigue mostrando las obras en ejecución (2-703-2026, 2-901-2026). Si la pestaña anterior estaba vacía, con "Galpón" se lee "No encontramos obras con esa búsqueda" aunque 2-902-2026 "QA-UXM Galpón norte" existe. Con Responsable = Laura se ven obras de Hernán y de Diego. En el buscador general el grupo "OBRAS (4)" de la búsqueda anterior sigue debajo de "Galpón". Reproducido 2 veces.
- **Evidencia:** `capturas/PU-017-1.png` · `capturas/PU-017-2.png` · `capturas/PU-017-3.png` · `capturas/PU-017-4.png` · `capturas/PU-017-5.png` · `capturas/PU-017-6.png` · `capturas/PU-017-7.png` · `capturas/PU-017-8.png` · origen: UXM-01
- **Causa probable (opcional):** `placeholderData: keepPreviousData` sin mirar `isFetching`/`isPlaceholderData` (`features/obras/hooks/useObras.ts:26`, `features/proveedores/hooks/useProveedores.ts:19`, `features/buscador/useBuscador.ts:12`, `features/identidad/hooks/useUsuarios.ts:19`); el título vacío de `ObrasPantalla.tsx` usa el `buscar` nuevo sobre datos viejos.

### PU-018 · Mientras guarda, el botón solo se pone gris: no dice "Guardando…" ni muestra un spinner
- **Severidad:** media
- **Tipo:** loader
- **F-XX / pantalla:** todos los guardados: F-19 documento · F-21 Cambiar estado · F-16/F-20 Nueva/Editar obra · F-48 Presupuesto por rubro · F-56 Nuevo proveedor · F-10 Mi perfil · F-59 Sectores · F-60 Tabla de permisos
- **Persona:** Hernán, Carla, Diego
- **Pasos:** 1. Con la red lenta, abrir "+ agregar certificado o documento", completar y tocar "Guardar". 2. Mirar el botón durante el guardado. 3. Repetir en las otras pantallas.
- **Esperado:** el botón muestra que está trabajando ("Guardando…" o spinner), distinto de un botón deshabilitado por falta de datos.
- **Obtenido:** el botón pasa a gris claro, igual que uno deshabilitado; el texto sigue "Guardar" y no hay spinner. Con 15 s de demora, durante 15 s nada dice que se está guardando (solo `aria-busy`, invisible). El doble clic no manda dos veces (bien).
- **Evidencia:** `capturas/PU-018-1.png` · `capturas/PU-018-2.png` · `capturas/PU-018-3.png` · `capturas/PU-018-4.png` · `capturas/PU-018-5.png` · `capturas/PU-018-6.png` · `capturas/PU-018-7.png` · `capturas/PU-018-8.png` · `capturas/PU-018-9.png` · origen: UXM-02
- **Causa probable (opcional):** `shared/ui/Button.tsx` con `cargando` solo pone `disabled` + `aria-busy`; `Button.css` para `[aria-busy='true']` solo cambia el cursor.

### PU-019 · "+ agregar presupuesto adicional" no responde durante segundos
- **Severidad:** media
- **Tipo:** loader
- **F-XX / pantalla:** F-18 · W-21 Adicional, desde la ficha `/obras/{id}`
- **Persona:** Hernán (Jefatura de Proyectos)
- **Pasos:** 1. Con la red lenta, abrir la ficha de una obra. 2. Tocar "+ agregar presupuesto adicional". 3. Esperar 3 s.
- **Esperado:** el diálogo se abre enseguida (con spinner en el N.º sugerido) o el botón muestra que está cargando.
- **Obtenido:** durante el pedido del N.º sugerido no pasa nada visible (2,5 s y 8 s en la prueba); "+ agregar presupuesto adicional" no cambia y el diálogo aparece de golpe al llegar la respuesta. Lo natural es tocar de nuevo. Reproducido 2 veces.
- **Evidencia:** `capturas/PU-019-1.png` · `capturas/PU-019-2.png` · origen: UXM-03
- **Causa probable (opcional):** `features/obras/pantallas/ObraPantalla.tsx`: `dialogo.presupuesto === null && sugerido.isPending ? null : <AdicionalDialog …>`.

### PU-020 · Con Redis caído, `/api/health` tarda 51 s y responde 500, y al volver se pierden todas las sesiones
- **Severidad:** media
- **Tipo:** error-servidor
- **F-XX / pantalla:** transversal · `GET /api/health` y toda la API
- **Persona:** monitoreo / todos
- **Pasos:** 1. Con la app abierta, cae Redis (corte externo entre 14:49 y 15:53 UTC). 2. Consultar `/api/health` y usar la app. 3. Levantar Redis.
- **Esperado:** `/api/health` responde rápido 503 con `"redis": false`; la app muestra un mensaje entendible y se recupera sola.
- **Obtenido:** `/api/health` → 500 en 51,1 s con "El sistema tuvo un problema. Volvé a intentar en un momento." (una segunda consulta no respondió en 70 s); 1.614 entradas en el canal `errors`. En pantalla se vio "No pudimos cargar esta información" (bien). Al volver Redis, todas las sesiones se perdieron (toda la empresa tiene que volver a pedir código) y el worker y el scheduler habían muerto.
- **Evidencia:** sin captura · `logs/corte-mysql-redis.log` · `logs/worker-scheduler-corte.log` · origen: LEAD-04
- **Causa probable (opcional):** el limitador `throttleApi` (`bootstrap/app.php`) usa el cache en Redis y corre antes que el controlador, así que el health nunca llega a sus `try/catch`; la demora es el timeout de predis. Sesiones: `SESSION_DRIVER=redis`; verificar persistencia (AOF/RDB) en producción.

### PU-021 · Con el worker de mails caído, el código no llega y `/api/health` dice que está todo bien
- **Severidad:** media
- **Tipo:** cola-mail
- **F-XX / pantalla:** F-01 · W-16 Ingreso · `/ingresar` · `GET /api/health`
- **Persona:** QA-LEAD Vence (Compras)
- **Pasos:** 1. Parar `queue:work --queue=mail`. 2. Pedir el código. 3. Esperar 1 minuto. 4. Consultar `/api/health`. 5. Levantar el worker.
- **Esperado:** que la caída del worker se detecte (health "degraded" o aviso en `errors`), porque sin él nadie puede entrar; y que el usuario sepa qué hacer si el código no llega ("¿No te llegó? Revisá spam o avisale a gerencia").
- **Obtenido:** la pantalla dice "Te mandamos un código a qa-lead-3@empresa-ejemplo.com · Si el mail está registrado, te llegó un código." y pasado un minuto solo se habilita "Reenviar código". El pedido queda en la cola (`LLEN queues:mail = 1`) y `/api/health` → 200 `"status":"ok"`. Al levantar el worker el mail sale una sola vez (~2 s), correcto.
- **Evidencia:** `capturas/PU-021-1.png` · `logs/cola-worker-parado.log` · origen: LEAD-03
- **Causa probable (opcional):** `HealthController` no mira la cola (antigüedad del job más viejo o tamaño de `queues:mail`).

### PU-022 · En el celular, las tablas de Usuarios, Presupuesto por rubro y Permisos no entran y no avisan que se pueden deslizar
- **Severidad:** media
- **Tipo:** celular
- **F-XX / pantalla:** F-58 · W-15 Usuarios `/usuarios` · F-48 · W-13 Presupuesto por rubro `/obras/{id}/presupuesto-por-rubro` · F-60 Tabla de permisos `/usuarios/permisos`
- **Persona:** Diego (Gerencia de Administración), Hernán
- **Pasos:** 1. Abrir esas pantallas en el celular (Pixel 7 412 px e iPhone SE 375 px).
- **Esperado:** como Obras y la ficha (que pasan a tarjetas), los datos se leen sin deslizar a ciegas y los montos se ven completos para cargarlos.
- **Obtenido:** la página no se desplaza a lo ancho, pero cada tabla queda en un recuadro más ancho que la pantalla, sin señal de que sigue. Usuarios (517 px): Sector cortado ("Compra", "Gerenci Adminis", "Ingenie proyect") y "Activo" fuera. Presupuesto por rubro (489 px): la columna del presupuesto cortada ("2-901-2026 REV…", "princ…", el monto se ve "1.500,") y "Total de la obra" fuera: no se ve lo que uno escribe. Tabla de permisos: 1.729 px; sus listas (12–14 px de letra) hacen zoom solo en el iPhone, igual que "Cómo se carga" (12 px).
- **Evidencia:** `capturas/PU-022-1.png` · `capturas/PU-022-2.png` · `capturas/PU-022-3.png` · `capturas/PU-022-4.png` · `capturas/PU-022-5.png` · `capturas/PU-022-6.png` · `capturas/PU-022-7.png` · origen: UXM-06
- **Causa probable (opcional):** las tablas de identidad y montos no usan `.tabla-marco--tarjetas` (`shared/ui/Tabla.css:52`); los selects de permisos tienen `font-size` < 16 px.

### PU-023 · Con "solo sus obras", el filtro "Responsable" del listado ofrece a toda la empresa
- **Severidad:** baja
- **Tipo:** funcional
- **F-XX / pantalla:** F-15 · W-05 Listado de obras · `/obras` (filtro "Responsable")
- **Persona:** QA-ADM Ana (sector QA-ADM Pruebas con Obras = Editar · solo sus obras)
- **Pasos:** 1. Con Obras = Editar · solo sus obras, entrar a Obras. 2. Abrir el desplegable "Responsable".
- **Esperado:** con ese alcance la persona solo ve sus obras: ocultar el filtro, o dejar "Todos" y ella (como ya hacen el alta y la edición, que solo ofrecen "QA-ADM Ana Ñúñez 👷").
- **Obtenido:** ofrece 15 personas ("Carla, Diego, Dora, Hernán, Laura, Pablo, …, Sofía"); elegir cualquiera que no sea ella da "No hay obras en esta pestaña", como si esas personas no tuvieran obras. Visto en las 3 pestañas.
- **Evidencia:** `capturas/PU-023-1.png` · salida de `adm/16-alcance-propias.cjs` · origen: ADM-05 (relacionado con la observación OBS-1 de OBR, lista larga de responsables)
- **Causa probable (opcional):** el filtro usa `GET /api/usuarios/opciones` sin mirar el alcance de `obras` de la sesión.

### PU-024 · Direcciones de proveedor mal escritas muestran la lista como si nada
- **Severidad:** baja
- **Tipo:** funcional
- **F-XX / pantalla:** F-55 · W-14 Proveedores · `/proveedores/abc`, `/proveedores/0`, `/proveedores/-1`, `/proveedores/1.5`, `/proveedores/nuevo`
- **Persona:** Carla (Compras)
- **Pasos:** 1. Abrir a mano `/proveedores/abc` (o cualquiera de las otras).
- **Esperado:** el mismo aviso que con un número que no existe ("Proveedor no encontrado · Elegí otro de la lista.", que sale bien con `/proveedores/999999`).
- **Obtenido:** muestra la lista y "Elegí un proveedor de la lista", como si el link fuera bueno. `/proveedores/nuevo` tampoco abre el alta.
- **Evidencia:** `capturas/PU-024-1.png` (`/proveedores/nuevo`) · `capturas/PU-024-2.png` (id inexistente, bien) · origen: PRV-04
- **Causa probable (opcional):** `ProveedoresPantalla.tsx`: `elegido` queda en `null` si el parámetro no es un entero positivo, igual que "sin elegir".

### PU-025 · "Atrás" del navegador después de guardar un proveedor se queda en el mismo proveedor
- **Severidad:** baja
- **Tipo:** funcional
- **F-XX / pantalla:** F-56 · W-14 · `/proveedores/{id}`
- **Persona:** Carla (Compras)
- **Pasos:** 1. Ir a `/proveedores` y abrir "QA-PRV Corralón Ñandú S.R.L.". 2. Editar y guardar dos veces. 3. Abrir otro proveedor. 4. Apretar "Atrás" del navegador.
- **Esperado:** cada "Atrás" vuelve a la pantalla anterior: el proveedor anterior y después la lista.
- **Obtenido:** hacen falta 3 "Atrás" sobre "QA-PRV Corralón Ñandú S.R.L." (`/proveedores/3`, sin cambio en pantalla) antes de volver a `/proveedores`: cada guardado agrega una entrada repetida al historial. La persona aprieta "Atrás" y "no pasa nada".
- **Evidencia:** sin captura · `p10.cjs`: URLs tras 4 "Atrás": `/proveedores/3`, `/proveedores/3`, `/proveedores/3`, `/proveedores` · origen: PRV-09
- **Causa probable (opcional):** `ProveedoresPantalla.tsx` → `guardado()` hace `navigate(rutaProveedor(id))` también al editar; con `{ replace: true }` (o sin navegar) se evita.

### PU-026 · Cualquier palabra suelta se acepta como link ("hola" → https://hola)
- **Severidad:** baja
- **Tipo:** validación
- **F-XX / pantalla:** "Pegar link" en W-06/W-20/W-21 y carpeta de Drive
- **Persona:** Diego (Gerencia de Administración)
- **Pasos:** 1. "Pegar link" → escribir `hola` → "Agregar link".
- **Esperado:** "Eso no parece un link. Copialo desde la barra del navegador." (un dominio sin punto no es un link del Drive).
- **Obtenido:** se agrega como link "hola" que apunta a "https://hola".
- **Evidencia:** sin captura · `POST /api/adjuntos {url:"https://hola"}` → 201 · origen: ENT-05

### PU-027 · El error del "Nombre para mostrar" de un link aparece debajo del campo "Link"
- **Severidad:** baja
- **Tipo:** validación
- **F-XX / pantalla:** F-19 · W-20 Agregar certificado o documento (y todo "Pegar link")
- **Persona:** Diego (Gerencia de Administración)
- **Pasos:** 1. "Pegar link" → link válido → "Nombre para mostrar" con 300 caracteres → "Agregar link".
- **Esperado:** "El nombre puede tener hasta 200 caracteres." debajo de "Nombre para mostrar", frenado en el front como el resto de los largos.
- **Obtenido:** "El nombre puede tener hasta 200 caracteres." sale debajo de "Link" (que está bien) y recién después de ir al servidor (`POST /api/adjuntos` → 422).
- **Evidencia:** `capturas/PU-027-1.png` · origen: ENT-12
- **Causa probable (opcional):** `PegarLink.tsx`: `e.fieldError('url') ?? e.fieldError('nombre')` va al único `error` del Field "Link".

### PU-028 · Un certificado con fecha 31/12/2099 se acepta y se muestra "31-dic", sin año
- **Severidad:** baja
- **Tipo:** validación
- **F-XX / pantalla:** F-19 · W-20 Certificados y otros documentos · `/obras/{id}`
- **Persona:** Diego (Gerencia de Administración)
- **Pasos:** 1. "+ agregar certificado o documento" → Certificado, fecha 31/12/2099 → Guardar.
- **Esperado:** avisar una fecha tan lejana ("¿Seguro? La fecha es de 2099.") y mostrar el año cuando no es el actual ("31-dic-2099"): los certificados de una obra larga cruzan de año.
- **Obtenido:** se guarda y la tabla muestra "31-dic".
- **Evidencia:** `capturas/PU-028-1.png` (fila "QA-ENT Certificado N.º 1") · `ent/documentos.cjs` · origen: ENT-14

### PU-029 · "Guardar" sin haber cambiado nada dice "Guardamos los cambios"
- **Severidad:** baja
- **Tipo:** mensaje
- **F-XX / pantalla:** F-20 · W-23 Editar obra · F-56 Editar proveedor · F-58 Editar usuario · F-10 Mi perfil
- **Persona:** Diego, Sofía, Hernán
- **Pasos:** 1. Abrir el formulario de edición y tocar Guardar sin tocar nada.
- **Esperado:** "No había cambios para guardar." (el front ya tiene "No hay cambios para guardar.", lo usa el presupuesto por rubro).
- **Obtenido:** "Guardamos los cambios de la obra." · "Guardamos los cambios de QA-ENT Proveedor Ñandú 😀 <b>SRL</b>." · "Guardamos los cambios de QA-ENT Ñoño Pérez 😀." · "Guardamos tus datos." (y hace el PATCH igual; no agrega líneas al historial).
- **Evidencia:** `capturas/PU-029-1.png` · origen: ENT-10, OBR (observación OBS-4, promovida a este hallazgo)

### PU-030 · El mismo error de tipo de archivo se dice de dos maneras distintas
- **Severidad:** baja
- **Tipo:** mensaje
- **F-XX / pantalla:** F-16 / F-19 · cualquier "Subir archivo"
- **Persona:** Diego (Gerencia de Administración)
- **Pasos:** 1. Subir `programa.exe`. 2. Subir `programa-renombrado.pdf` (el mismo .exe con otra extensión).
- **Esperado:** un solo texto; para el renombrado, además, "Este archivo no es un PDF aunque termine en .pdf. Elegí otro."
- **Obtenido:** .exe (front): "Ese tipo de archivo no se puede subir. Se aceptan PDF, imágenes (JPG, PNG, WEBP, HEIC), Excel, Word, CSV y TXT." · renombrado (backend, bien detectado): "Ese tipo de archivo no se acepta. Subí un PDF, una imagen, un Excel, un Word, un CSV o un TXT."
- **Evidencia:** `capturas/PU-030-1.png` · origen: ENT-04

### PU-031 · Renombrar un sector sin cambiar el nombre dice "Renombramos QA-ADM Doble a QA-ADM Doble."
- **Severidad:** baja
- **Tipo:** mensaje
- **F-XX / pantalla:** F-59 · W-40 Sectores · `/usuarios/sectores` (ventana "Renombrar · …")
- **Persona:** Diego (Gerencia de Administración)
- **Pasos:** 1. Sectores → "renombrar" en "QA-ADM Doble". 2. Dejar el mismo nombre y tocar "Guardar".
- **Esperado:** cerrar sin mensaje, o "No cambiaste el nombre."
- **Obtenido:** se manda `PATCH /api/sectores/14` (200) y aparece en verde "Renombramos QA-ADM Doble a QA-ADM Doble.".
- **Evidencia:** sin captura · salida de `adm/14-sectores2.cjs` · `PATCH /api/sectores/14 → 200` · origen: ADM-04
- **Causa probable (opcional):** `src/features/identidad/components/SectorDialog.tsx` arma el texto sin comparar con el nombre anterior.

### PU-032 · El historial muestra las fechas previstas en formato técnico
- **Severidad:** baja
- **Tipo:** mensaje
- **F-XX / pantalla:** F-14 / F-23 · W-23 Historial de cambios · `/obras/{id}/editar`
- **Persona:** Hernán (Jefatura de Proyectos)
- **Pasos:** 1. Editar la obra y cambiar "Fin previsto" de 31/03/2027 a 30/04/2027. 2. Mirar el historial.
- **Esperado:** el formato de la ficha ("31/03/2027 → 30/04/2027"), como se arregló con los importes en B-05.
- **Obtenido:** "Fin previsto: 2027-03-31 → 2027-04-30".
- **Evidencia:** `capturas/PU-032-1.png` · `GET /api/obras/1/historial` · origen: OBR-04

### PU-033 · Revisión con texto largo: el mensaje no dice cómo escribirla
- **Severidad:** baja
- **Tipo:** mensaje
- **F-XX / pantalla:** F-20 · W-23 Editar obra · campo "Revisión"
- **Persona:** Hernán (Jefatura de Proyectos)
- **Pasos:** 1. Editar la obra y escribir "versión dos" en Revisión. 2. "Guardar cambios".
- **Esperado:** "Escribí la revisión como REV01." (RN-OBR-14; es lo que sale con "rev dos").
- **Obtenido:** "La revisión puede tener hasta 10 caracteres." La persona acorta y vuelve a fallar. Reproducido 2 veces.
- **Evidencia:** `capturas/PU-033-1.png` · origen: OBR-06

### PU-034 · Doble punto en los mensajes cuando el nombre del proveedor termina en punto
- **Severidad:** baja
- **Tipo:** mensaje
- **F-XX / pantalla:** F-56 · W-38 Alta y edición de proveedor · `/proveedores`
- **Persona:** Carla (Compras), Diego (Gerencia de Administración)
- **Pasos:** 1. "+ Nuevo proveedor" con nombre "QA-PRV Corralón Ñandú S.R.L.". 2. Guardar proveedor. 3. Intentar dar de alta otra vez "qa-prv CORRALON ÑANDU srl".
- **Esperado:** "Dimos de alta a QA-PRV Corralón Ñandú S.R.L." con un solo punto.
- **Obtenido:** "Dimos de alta a QA-PRV Corralón Ñandú S.R.L.." y, en el repetido, "Ver la ficha de QA-PRV Corralón Ñandú S.R.L..". La API responde `message: "Guardamos QA-PRV Corralón Ñandú S.R.L.."`. Pasa con cualquier "S.A.", "S.R.L.", "Cía.". Reproducido 3 veces.
- **Evidencia:** `capturas/PU-034-1.png` · `capturas/PU-034-2.png` · `capturas/PU-034-3.png` · origen: PRV-01, ENT-11 (parte del doble punto)
- **Causa probable (opcional):** `ProveedoresPantalla.tsx`, `ProveedorDialog.tsx` y el `message` del store en el backend agregan el punto sin mirar si el nombre ya termina en punto.

### PU-035 · "Ver la ficha de …" queda colgado después de corregir el nombre del proveedor
- **Severidad:** baja
- **Tipo:** mensaje
- **F-XX / pantalla:** F-56 · W-38 Nuevo proveedor · `/proveedores`
- **Persona:** Carla (Compras)
- **Pasos:** 1. "+ Nuevo proveedor". 2. Nombre igual a uno existente ("qa-prv CORRALON ÑANDU srl") → Guardar. 3. Sale "Ya existe QA-PRV Corralón Ñandú S.R.L." y debajo "Ver la ficha de …". 4. Cambiar el nombre por "QA-PRV Distinto Total".
- **Esperado:** al corregir el nombre se va el error y también el link que lo acompañaba.
- **Obtenido:** el error se va pero "Ver la ficha de QA-PRV Corralón Ñandú S.R.L.." sigue debajo del nombre, suelto; parece que el nombre nuevo también choca. El link sale bajo "Nombre" aunque el choque sea por CUIT. Reproducido 2 veces.
- **Evidencia:** `capturas/PU-035-1.png` · origen: PRV-02
- **Causa probable (opcional):** `ProveedorDialog.tsx`: el estado `existente` solo se limpia al volver a enviar.

### PU-036 · El aviso "Guardamos los cambios de …" queda puesto al elegir otro proveedor
- **Severidad:** baja
- **Tipo:** mensaje
- **F-XX / pantalla:** F-56 · W-14/W-38 · `/proveedores/3` → `/proveedores/2`
- **Persona:** Carla (Compras)
- **Pasos:** 1. Editar "QA-PRV Corralón Ñandú S.R.L." y guardar. 2. En la lista, elegir "QA-PRV Prueba CUIT".
- **Esperado:** el aviso verde se va al cambiar de proveedor (o a los pocos segundos).
- **Obtenido:** sigue "Guardamos los cambios de QA-PRV Corralón Ñandú S.R.L.." mientras se ve la ficha de "QA-PRV Prueba CUIT"; parece que se guardó algo en el proveedor que se está mirando.
- **Evidencia:** `capturas/PU-036-1.png` · origen: PRV-08
- **Causa probable (opcional):** `ProveedoresPantalla.tsx`: `aviso` no se limpia al cambiar `:id` (la ruta `/proveedores/:id?` no vuelve a montar la pantalla).

### PU-037 · Editar obra: la pantalla salta cuando termina de cargar el formulario
- **Severidad:** baja
- **Tipo:** loader
- **F-XX / pantalla:** F-20 · W-23 Editar obra `/obras/{id}/editar`
- **Persona:** Hernán (Jefatura de Proyectos)
- **Pasos:** 1. Con la red lenta, tocar "Editar" en la ficha. 2. Mirar la pantalla mientras carga.
- **Esperado:** sin saltos: lugar del formulario reservado (esqueleto con su alto) o el historial cargado después.
- **Obtenido:** primero "Preparando el formulario…" (tarjeta chica) con "HISTORIAL DE CAMBIOS" debajo; al llegar el formulario (~1.600 px) el historial se va abajo de golpe. `layout-shift` 0,156 y 0,111 (por encima de 0,1 es malo).
- **Evidencia:** `capturas/PU-037-1.png` · `capturas/PU-037-2.png` · medición `SECTION.bloque-ficha` 0,1557 / 0,1114 · origen: UXM-05

### PU-038 · Con un nombre de más de 150 letras, el aviso de parecidos da 422 en la red
- **Severidad:** baja
- **Tipo:** error-servidor
- **F-XX / pantalla:** F-56 · W-38 Editar proveedor · `/proveedores/3`
- **Persona:** Carla (Compras)
- **Pasos:** 1. Editar un proveedor. 2. Pegar en Nombre un texto de 151 caracteres.
- **Esperado:** el formulario avisa el límite y no llama a la API con un dato que ya sabe inválido.
- **Obtenido:** sale bien "El nombre puede tener hasta 150 caracteres.", pero además se pide `GET /api/proveedores/parecidos?nombre=…(151)` y el backend contesta 422, que queda como error en la consola. El usuario no lo ve. Visto 1 vez (repetible por código).
- **Evidencia:** sin captura · `GET /api/proveedores/parecidos?nombre=QA-PRV+LLLL…&cuit=30-71234569-8&excluir_id=3 → 422` · origen: PRV-05
- **Causa probable (opcional):** `Parecidos.tsx` consulta con cualquier largo.

### PU-039 · El canal `errors` y `/api/errors` se llenan de errores de validación normales
- **Severidad:** baja
- **Tipo:** error-servidor
- **F-XX / pantalla:** transversal · `storage/logs/errors-*.log` · `GET /api/errors`
- **Persona:** equipo de desarrollo / `/bug-report`
- **Pasos:** 1. Usar los formularios con datos inválidos. 2. Consultar `/api/errors`.
- **Esperado:** el canal `errors` junta solo errores del sistema (5xx, excepciones, jobs fallidos).
- **Obtenido:** entre las 1.709 entradas del día hay decenas de `WARNING` de validación: "Ya hay un sector con ese nombre." (9), "Completá este campo." (7), "El CUIT no es válido. Revisá los números." (6), "Ese mail ya es de otro usuario (Laura)." (3)… Incluyen el nombre de otra persona en el mensaje.
- **Evidencia:** sin captura · `GET /api/errors` 16:37 UTC · origen: LEAD-06
- **Causa probable (opcional):** el handler registra las `ValidationException` (422) en el canal `errors`.

### PU-040 · En el celular, las pestañas cortan el último nombre y su número
- **Severidad:** baja
- **Tipo:** celular
- **F-XX / pantalla:** F-15 · W-47 Obras `/obras` · F-58/F-59/F-60/F-68 · submenú de Usuarios
- **Persona:** Hernán, Diego
- **Pasos:** 1. Abrir Obras en el celular (412 o 375 px). 2. Abrir Usuarios.
- **Esperado:** las pestañas se leen con su cantidad, o se nota que hay más a la derecha.
- **Obtenido:** en Obras se lee "En ejecución (2) · Confirmadas (6) · Terminadas" y el número de Terminadas queda fuera; en Usuarios la cuarta pestaña se ve "Plazo". Sin flecha ni sombra. La ayuda del buscador de Obras también queda cortada ("…cliente o trabajo" sin terminar).
- **Evidencia:** `capturas/PU-040-1.png` · `capturas/PU-040-2.png` · `capturas/PU-040-3.png` · origen: UXM-07

### PU-041 · Controles de 40 px de alto en el celular, por debajo de los 44 px recomendados
- **Severidad:** baja
- **Tipo:** celular
- **F-XX / pantalla:** todas (botones, campos, pestañas, lupa, "editar"/"quitar"); el link "Abrir carpeta de Drive de la obra" de la ficha
- **Persona:** Hernán, Carla, Diego
- **Pasos:** 1. Medir el alto de los controles en Pixel 7 / iPhone SE.
- **Esperado:** 44 px de alto como mínimo en el celular (en obra se usa con guantes o con una mano).
- **Obtenido:** botones y campos de 40–41 px; en la ficha "editar" y "quitar" quedan a 12 px uno del otro; "Abrir carpeta de Drive de la obra" mide 23 px.
- **Evidencia:** `capturas/PU-041-1.png` · `uxm/out-cel-pixel7.txt`, `out-cel-se.txt` · origen: UXM-08
- **Causa probable (opcional):** `shared/ui/tokens.css:103` `--alto-control: 2.5rem` sin aumento para el celular.

### PU-042 · Las pestañas no se manejan con las flechas del teclado
- **Severidad:** baja
- **Tipo:** accesibilidad
- **F-XX / pantalla:** F-15 Obras (En ejecución / Confirmadas / Terminadas) · F-17 ficha ("Documentos y contactos")
- **Persona:** Hernán (PC)
- **Pasos:** 1. Con Tab, llegar a la pestaña "En ejecución". 2. Apretar → (flecha derecha).
- **Esperado:** con `role="tab"`, las flechas pasan de pestaña y solo la activa recibe Tab; si no, que sean botones comunes sin `role="tab"`.
- **Obtenido:** la flecha no hace nada (el foco sigue en "En ejecución (3)") y cada pestaña es una parada de Tab. Un lector de pantalla las anuncia como pestañas pero no se comportan así. Con Tab y Enter se pueden usar.
- **Evidencia:** sin captura · `uxm/out-teclado.txt` (bloque `orden-obras`) · origen: UXM-09

### PU-043 · La "ñ" cuenta como "n": no deja cargar un proveedor distinto que solo cambia en la ñ
- **Severidad:** baja
- **Tipo:** consistencia-con-documento
- **F-XX / pantalla:** F-56 · W-38 Nuevo proveedor · `/proveedores`
- **Persona:** Carla (Compras)
- **Pasos:** 1. Con "QA-PRV Corralón Ñandú S.R.L." cargado, "+ Nuevo proveedor" con nombre "QA-PRV Corralon Nandu". 2. Guardar.
- **Esperado:** RN-PRV-04 compara "sin tildes"; la ñ no es una tilde ("Peña"/"Pena", "Muñoz"/"Munoz" son distintos): mostrarlo como parecido (aviso que deja guardar). Para decidir con el cliente.
- **Obtenido:** "Ya existe QA-PRV Corralón Ñandú S.R.L." y no deja guardar. Reproducido 2 veces.
- **Evidencia:** sin captura · `POST /api/proveedores` → 422 `errors.nombre: ["Ya existe QA-PRV Corralón Ñandú S.R.L."]` · origen: PRV-03
- **Causa probable (opcional):** `app/Modules/Proveedores/Domain/NombreProveedor.php::busqueda()` usa `Str::ascii()` (ñ → n).

### PU-044 · El buscador de proveedores no encuentra por CUIT ni por rubro, aunque los muestra
- **Severidad:** baja
- **Tipo:** consistencia-con-documento
- **F-XX / pantalla:** F-55 · W-14 Proveedores · `/proveedores` · F-03 · W-18 `/buscar`
- **Persona:** Carla (Compras)
- **Pasos:** 1. En "Buscar proveedor…" escribir `30-71234569-8` o `30712345698` (el CUIT que se ve bajo "QA-PRV Corralón Ñandú S.R.L."). 2. Escribir `materiales`.
- **Esperado:** F-03 y RN-PRV-03 piden buscar por nombre (no es incumplimiento: para decidir). La lista muestra "MATERIALES · 30-71234569-8" y Compras busca por CUIT con la factura en la mano; si queda solo por nombre, que el estado vacío lo diga.
- **Obtenido:** "No encontramos proveedores · Probá con otra parte del nombre." para el CUIT con y sin guiones y para el rubro; en el buscador general, "PROVEEDORES (0) · Sin resultados.". Reproducido 2 veces.
- **Evidencia:** `capturas/PU-044-1.png` · origen: PRV-07
- **Causa probable (opcional):** `PorNombre` filtra solo `nombre_busqueda`.

### PU-045 · Bloqueo del ingreso: la persona bloqueada no se entera, espera un código que nunca llega y gerencia no tiene cómo destrabarla
- **Severidad:** media
- **Tipo:** mensaje
- **F-XX / pantalla:** F-01 · W-16 Ingreso · `/ingresar` · (sin pantalla para gerencia)
- **Persona:** QA-LEAD Bloqueo (Compras)
- **Pasos:** 1. Pedir el código y equivocarse 5 veces (el código se "quema"). 2. Repetir hasta quemar 3 códigos (con pausas para no chocar el límite de 10 intentos por minuto). 3. Pedir otro código. 4. Esperar el mail 30 s. 5. Escribir un código.
- **Esperado:** el plan de la prueba pide que el bloqueo progresivo diga cuánto esperar. ADR-015 (enmienda 2026-11-13) decidió a propósito que "durante el bloqueo nada lo delata" y que "la persona bloqueada espera o pide a gerencia que revise". Al menos una de las dos salidas tiene que existir: un mensaje que oriente sin confirmar que el mail existe (p. ej. "¿No te llegó? Si probaste varias veces, esperá una hora o pedile a gerencia que lo revise") o una forma de que gerencia vea y quite el bloqueo desde Usuarios.
- **Obtenido:** tabla `bloqueos_ingreso` → `bloqueado_hasta = 2026-11-16 18:06:58` (1 hora). Al pedir el código: "Te mandamos un código a qa-lead-2@empresa-ejemplo.com · Si el mail está registrado, te llegó un código." y **no sale ningún mail** (0 en 30 s). Al escribir un código: "El código no es válido o venció." No hay en el front ni en el backend ninguna pantalla o endpoint para ver o quitar el bloqueo (solo `BloqueoIngreso` y `BloqueosDeIngreso`), así que gerencia tampoco puede ayudar. Quedó registrado en el log (`ingreso.bloqueado`, sin el mail).
- **Evidencia:** `capturas/PU-045-1.png` (pide código estando bloqueado) · `capturas/PU-045-2.png` (ingresa estando bloqueado) · origen: LEAD (bloqueo progresivo)
- **Causa probable (opcional):** decisión de ADR-015 (no es un error de código). Hace falta que Juani o el cliente decidan el mensaje y/o la pantalla de desbloqueo. Distinto de PU-016 (el límite por cantidad de pedidos, que sí avisa pero sin decir cuánto).
