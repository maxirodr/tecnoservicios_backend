# Borrador ENT — QA de entradas y UX writer · Ronda 2 (2026-11-17)

Personas: Diego (Gerencia de Administración) y Sofía (Ingeniería de proyectos), con las sesiones guardadas. No pedí ningún código de ingreso.
Scripts: `C:\Users\GREEN\AppData\Local\Temp\claude\4fe530b1c11d8e8d\f4efcdda-aa94-4434-aafd-2e4822d4d74b\scratchpad\r2\ent\`.
Datos propios: obras 2-701-2026 (id 17) y 2-702-2026 (id 18), proveedor "QA-ENT2 Proveedor Ñandú 😀 <b>SRL</b>" (id 20), sector "QA-ENT2 Sector Ñandú 😀", usuario `qa-ent2-1@empresa-ejemplo.com`.

## 1. Entradas inválidas: lo que anda (sin hallazgo)

| Formulario | Lo probado | Resultado |
|---|---|---|
| Alta de obra W-06 | vacío, solo espacios, 310 caracteres en los 12 campos de texto y un link de 600, emojis/ñ/HTML, mails `jose@`, `maria peña@cliente .com`, `jose@@cliente.com`, `maria@cliente`, fin antes que inicio, carpeta `javascript:`, `hola`, sin https, N.º `abc`, `2-70-26`, `2 701 2026 😀`, `<b>…</b>`, revisión `REV999999`/`revisión uno`, doble clic | Cada error al lado de su campo, en español, y lo escrito queda (cliente con 310 caracteres sigue ahí). HTML y emojis se guardan y se muestran como texto. Doble clic: 1 solo `POST`. `https://drive.example/carpeta-ñandú-año` y la oferta `https://drive.example/oferta-ñandú` ahora pasan. |
| Editar obra W-23 | sin cambios, cliente vacío, nombre de 301, revisión `REV 01 bis`, mail `a@b`, fechas al revés, `javascript:`; salir por link del menú, "Cancelar", F5 | "No había cambios para guardar." sin `PATCH`. Errores al lado de cada campo. Salir/Cancelar: "Tenés cambios sin guardar. ¿Salir igual?" · "Si salís, se pierde lo que cambiaste." · "Seguir editando" / "Salir". F5: diálogo del navegador. |
| Conflicto (2 pestañas) | A guarda la planta, B guarda otra planta | B ve "Alguien cambió esto mientras lo tenías abierto. Actualizá y revisá." + "Al actualizar vas a ver lo que está guardado ahora y se pierde lo que cambiaste acá." + "Actualizar" (`ENT2-7-conflicto.png`). Con "Actualizar" carga lo de A. |
| Sesión vencida (401 simulado al guardar) | Editar obra con un cambio | Ingreso muestra "Tu sesión venció. Volvé a ingresar."; al volver: "Recuperamos lo que estabas escribiendo." con lo escrito (`ENT2-8`, `ENT2-9`). |
| Adicional W-21 | vacío, espacios, `abc`, `2-701-26`, `2-70123-2026`, `2-799-2099`, revisión con espacios y 30 letras | "Completá el N.º…", "Escribí solo el número…", "Escribí el año con 4 dígitos…", "Eso parecen dos presupuestos…", "El año tiene que estar entre 2000 y 2027.", "Escribí la revisión como REV01." Ver ENT-02 para el N.º repetido. |
| Certificado/documento W-20 | vacío, espacios, 300 caracteres, 0 B, .exe, .exe renombrado a .pdf, .html, .svg, 21 MB, 20 MB, nombre con acentos y "–", links `hola`, `javascript:`, sin https, con ñ, con espacio, `ftp://`, `http://`, nombre del link de 300, link escrito sin "Agregar link", doble clic | "El archivo está vacío. Elegí otro.", "Ese tipo de archivo no se puede subir. Se aceptan PDF, imágenes (JPG, PNG, WEBP, HEIC), Excel, Word, CSV y TXT.", "Este archivo no es un PDF aunque termine en .pdf. Elegí otro.", "El archivo pesa 21 MB y el máximo es 20 MB.", "Eso no parece un link. Copialo desde la barra del navegador.", "El link tiene que empezar con http:// o https://.", "El nombre puede tener hasta 200 caracteres." (bajo su campo), "Tocá «Agregar link» para sumar el link que pegaste.". 20 MB y el de acentos entran. 1 solo `POST`. Fechas 1899/2199: ver PU-028 (⚠️ de VA). |
| Cambio de estado | doble clic en "Pasar a En ejecución"; API con `terminada`, `cualquiera`, `{}` | 1 solo `POST`; "La obra pasó a En ejecución.". API: "De Confirmada se pasa primero a En ejecución." |
| Presupuesto por rubro W-13 | `-100`, `0`, `1.234,56`, `1234.56`, `1e15`, `1000000000000000`, `12,345`, `abc`, `  2.500  `, `1.5`, `<script>`, `0,001`; doble clic | Error al lado de cada celda (`ENT2-19-montos-raros.png`): "El monto no puede ser negativo.", "Usá coma para los decimales, por ejemplo 1234,56.", "Escribí el monto con números, por ejemplo 1.500.000,50.", "El monto es demasiado grande.", "El monto puede tener hasta 2 decimales."; arriba "Revisá los montos marcados.". Con válidos: 1 solo `PUT`, "Guardamos el presupuesto por rubro.". |
| Proveedor W-38 (`/proveedores/nuevo`) | vacío, espacios, 300, CUIT `123`, letras, dígito verificador mal, con espacios, con puntos, 19 dígitos; mails `ventas@`, con espacio, sin dominio, 220 caracteres; teléfono de 60; doble clic | "Completá el nombre del proveedor.", "El CUIT tiene 11 números, por ejemplo 30-00000000-7.", "El CUIT no es válido. Revisá los números.", "Escribí un mail válido." (ver ENT-07), "El mail puede tener hasta 190 caracteres.", "El teléfono puede tener hasta 50 caracteres.". 1 solo alta: "Dimos de alta a QA-ENT2 Proveedor Ñandú 😀 <b>SRL</b>.". |
| Sector | vacío, espacios, 300, "  COMPRAS ", "compras", doble clic | "Completá el nombre del sector.", "El nombre puede tener hasta 80 caracteres.", "Ya hay un sector con ese nombre."; 1 solo alta: "Agregamos QA-ENT2 Sector Ñandú 😀. Ya aparece como una fila más en la tabla de permisos." |
| Usuario (alta y edición) | vacío, espacios, 300, 5 mails mal, mail de Diego con mayúsculas y espacios, `CARLA@…` en edición, teléfono de 60, doble clic | "Completá el nombre.", "Escribí un mail válido, por ejemplo nombre@empresa.com.", "Ese mail ya es de otro usuario (Diego).", "(Carla)."; 1 solo alta: "Dimos de alta a QA-ENT2 Ñoño Pérez 😀. Ya puede entrar con su mail.". |
| Plazos de la prioridad | solo lo miré (sin guardar; 0 `PUT`) | "Urgente hasta 1 día", "Prioritario hasta 7 días", "Normal más de 7 días" (`ENT2-13-plazos.png`). |
| Mi perfil (Sofía) | sin cambios, espacios, 300, teléfono de 60, salir con cambios | "No había cambios para guardar." (ver ENT-01), "Completá el nombre.", "El nombre puede tener hasta 120 caracteres.", "El teléfono puede tener hasta 50 caracteres."; salir con cambios pregunta. Lo dejé igual que estaba (teléfono vacío). |
| Sin acceso (Sofía) | `/proveedores`, `/proveedores/nuevo`, `/proveedores/abc`, `/usuarios`, `/usuarios/plazos`, `/obras/17/presupuesto-por-rubro`, `/obras/99999` | "No tenés acceso a esta sección" · "Proveedores: tu sector no tiene permiso para ver esto. Si lo necesitás, pedíselo a gerencia." (ver ENT-08) · "No sos responsable de esta obra" · "Ves el presupuesto por rubro solo de las obras donde sos responsable." · "Obra no encontrada" · "Puede que el link esté mal o que no tengas acceso a esa obra.". |
| Listas | búsqueda de obras con la red demorada 2,5 s | "Buscando obras…" solo sobre la tabla (`ENT2-26-buscando.png`). |

**Canal `errors`:** después de cada bloque, `storage\logs\errors-2026-11-17.log` **no existe** (ni con los ~25 422, el 409 del conflicto y los 401 simulados). `laravel-2026-11-17.log` no tiene ninguna línea ERROR ni CRITICAL.

## 2. UX writer: textos nuevos de los arreglos

| Texto | Dónde | Visto en pantalla | ¿Se entiende? / nota |
|---|---|---|---|
| "Tenés cambios sin guardar. ¿Salir igual?" · "Si salís, se pierde lo que cambiaste." · "Seguir editando" / "Salir" | Editar obra, Nueva obra, Mi perfil | Sí | Claro. Sugerencia: el botón destacado es "Salir"; el seguro ("Seguir editando") debería ser el principal. No lo cargo como hallazgo. |
| "Recuperamos lo que estabas escribiendo." | Formularios después de una sesión vencida | Sí | Claro. Mejor: "Recuperamos lo que estabas escribiendo. Revisalo y guardá." (hoy no dice que todavía no está guardado). |
| "Alguien cambió esto mientras lo tenías abierto. Actualizá y revisá." + "Al actualizar vas a ver lo que está guardado ahora y se pierde lo que cambiaste acá." | Editar obra | Sí | Claro y honesto. Ver REG-02 (no es mío). |
| "No había cambios para guardar." | Editar obra, proveedor, Mi perfil | Sí | Claro; en Mi perfil queda puesto de más (ENT-01). El presupuesto por rubro todavía dice "No hay cambios para guardar." (`EditorMontos.tsx:79`): tiempo verbal distinto para lo mismo. |
| "Tu sesión venció. Volvé a ingresar." | Ingreso | Sí | Claro. Hay otro texto para lo mismo (ENT-09). |
| "Buscando…", "Buscando obras…", "Buscando proveedores…", "Buscando usuarios…", "Buscando el N.º sugerido…" | Listas, buscador, adicional | "Buscando obras…" | Claro y coherente. |
| "No tenés acceso a esta sección" · "{Sección}: tu sector no tiene permiso para ver esto. Si lo necesitás, pedíselo a gerencia." · "Solo podés ver esta sección" · "Tu sector puede ver {módulo} pero no {acción}." · "No sos responsable de esta obra" | Pantallas sin permiso | Sí (salvo "solo ver", que no tengo con Diego/Sofía en una pantalla) | Se entienden. El "Proveedores: tu sector…" suena a etiqueta técnica, y en Plazos dice "Usuarios:" (ENT-08). |
| "Usá coma para los decimales, por ejemplo 1234,56." | Presupuesto por rubro | Sí | Claro, pero el ejemplo no tiene el punto de miles que usa el resto de la pantalla (ENT-06). |
| "Escribí la revisión como REV01." / "Completá la revisión, por ejemplo REV01." | Alta, edición, adicional | Sí | Claro. |
| "Ya pediste varios códigos. Probá de nuevo en N minutos." (singular "1 minuto.") | Ingreso (solo en el código: `IdentidadServiceProvider.php:124`) | No (no pedí códigos) | Claro y con cifra. Convive con "Demasiados intentos. Probá de nuevo en un minuto." (ENT-09). |
| "¿No te llegó? Si probaste varias veces, esperá una hora o pedile a gerencia que lo revise." | Paso del código (`PasoCodigo.tsx:114`) | No (no pedí códigos) | Claro. Sale siempre, desde el primer código: está bien porque arranca con "¿No te llegó?". |
| "· Ingreso trabado hasta las HH:MM" | Usuarios | No: en este momento no había ningún usuario trabado | Claro. |
| "{Nombre} no puede entrar hasta las HH:MM: se equivocó varias veces con el código. Si ya lo revisaste con la persona, destrabalo y que pida un código nuevo." · "Destrabar ingreso" · "Destrabamos el ingreso de {Nombre}." | Ficha del usuario (`BloqueoIngreso.tsx`) | No (lo mismo) | Claro; "destrabalo" coincide con el botón. |
| "El sistema no está disponible en este momento. Probá de nuevo en unos minutos." (503) y `/api/health` | API | `/api/health` 200 `{"status":"ok",…,"redis":true,"cola_mail":true}` | El JSON del health no lo ve el usuario. El 503 es claro. |
| "Tocá «Agregar link» para sumar el link que pegaste." | Pegar link | Sí | Claro. Usa «», el resto usa "" o '' (ver ENT-02). |

Tuteo/voseo: todo en voseo ("Completá", "Escribí", "Probá", "Tocá"). Ningún texto en inglés, ni clave cruda, ni código interno en pantalla. Puntos finales: bien en todos los avisos que vi.

## 3. Hallazgos nuevos

### ENT-01 · Mi perfil: "No había cambios para guardar." queda puesto mientras escribís y aparecen errores
- **Severidad:** baja
- **Tipo:** mensaje
- **F-XX / pantalla:** F-10 · Mi perfil · `/perfil`
- **Persona:** Sofía (Ingeniería de proyectos)
- **Pasos:** 1. Mi perfil → "Guardar" sin tocar nada. 2. Borrar el Nombre. 3. "Guardar".
- **Esperado:** el aviso se va apenas se cambia algo (como hacen los avisos de proveedor desde PU-036); queda solo "Completá el nombre.".
- **Obtenido:** arriba sigue "No había cambios para guardar." y abajo del campo "Completá el nombre.": la pantalla dice que no hay cambios mientras marca uno. Igual con nombre de 300 ("El nombre puede tener hasta 120 caracteres.") y teléfono de 60. Reproducido 2 veces. En Editar obra, proveedor y usuario no pasa (salen de la pantalla o cierran el diálogo).
- **Evidencia:** `capturas/ENT2-15-perfil-sin-cambios-queda.png` · `ent/perfil2.cjs`
- **Causa probable (opcional):** `PerfilPantalla.tsx`: el `mensaje` de "sin cambios" no se limpia al editar un campo.
- **Regresión de:** PU-029 · `c184727` (el aviso nuevo de "sin cambios")

### ENT-02 · N.º de presupuesto repetido: dos avisos que dicen lo mismo con palabras distintas (y en el adicional, sin link)
- **Severidad:** baja
- **Tipo:** mensaje
- **F-XX / pantalla:** F-16 · W-06 Nueva obra `/obras/nueva`; F-18 · W-21 Agregar presupuesto adicional (ficha de la obra)
- **Persona:** Diego (Gerencia de Administración)
- **Pasos:** 1. Nueva obra con el N.º `2-701-2026`, que ya es de otra obra, y todo lo demás completo → "Guardar obra". 2. En la ficha de otra obra, "+ agregar presupuesto adicional" con N.º `2-701-2026` → "Guardar y cargar montos por rubro →".
- **Esperado:** un solo aviso, el mismo en las dos pantallas y con el link a la obra: "Ese N.º de presupuesto ya está en «QA-ENT2 Montaje…». Abrí la obra." (bajo el campo N.º).
- **Obtenido:** en Nueva obra, arriba: "Ese N.º de presupuesto ya está en QA-ENT2 Montaje 🏗️ ñandú ÁÉÍÓÚ <script>alert(1)</script>." (con link) y bajo el campo: "El presupuesto 2-701-2026 ya está en la obra 'QA-ENT2 Montaje 🏗️ ñandú ÁÉÍÓÚ <script>alert(1)</script>'.". En el adicional sale solo el segundo, sin link, y con comillas simples, que no se usan en ningún otro texto (el resto usa "…" o «…»). Reproducido 2 veces en cada pantalla (`2-701-2026`, `2-702-2026`; en el adicional también `2-701-2026 rev99`).
- **Evidencia:** `capturas/ENT2-28-obra-repetida-alta.png`, `capturas/ENT2-30-obra-repetida-alta-2.png`, `capturas/ENT2-22-adicional-errores.png` · `POST /api/obras` → 422 · `POST /api/obras/17/presupuestos` → 422
- **Causa probable (opcional):** `app/Modules/Obras/Application/Presupuestos/NumeroUnico.php:35` arma el texto con `'…'`; `ObraFormulario.tsx:126-134` agrega su propio aviso y no esconde el del campo; `AdicionalDialog.tsx` no tiene el aviso con link.

### ENT-03 · Los links se muestran solo con el dominio: varios links del Drive se ven todos iguales
- **Severidad:** media
- **Tipo:** mensaje
- **F-XX / pantalla:** F-19 · W-20 Certificados y otros documentos (tabla y "editar"); W-20 Presupuestos (oferta técnica); W-06/W-21 lista de adjuntos al cargar (mismo componente)
- **Persona:** Diego (Gerencia de Administración)
- **Pasos:** 1. "+ agregar certificado o documento" → pegar `https://drive.example/oferta-ñandú` y `http://drive.example/sin-s` sin "Nombre para mostrar" → Guardar. 2. Mirar la fila en "Certificados y otros documentos" y abrir "editar".
- **Esperado:** cada link se reconoce sin abrirlo: el nombre del archivo o la última parte del link ("oferta-ñandú", "sin-s") o, si no, "Link 1", "Link 2". Además, el link completo al pasar el mouse.
- **Obtenido:** las dos filas dicen igual: "drive.example (link, se abre en otra pestaña)" · "drive.example (link, se abre en otra pestaña)", sin `title`. Con links reales todos van a decir "drive.google.com", y para quitar uno hay que adivinar cuál es. "Nombre para mostrar" es opcional y nadie avisa que conviene completarlo. Visto 2 veces (ficha y diálogo "editar").
- **Evidencia:** `capturas/ENT2-31-links-solo-dominio.png`, `capturas/ENT2-32-links-solo-dominio-editar.png` · `ent/links-dominio.cjs`

### ENT-04 · La carpeta de Drive con ñ o espacios se ve como "%C3%B1" y "%20" al editar la obra
- **Severidad:** baja
- **Tipo:** mensaje
- **F-XX / pantalla:** F-20 · W-23 Editar obra · `/obras/{id}/editar` (campo "Carpeta de Drive de la obra")
- **Persona:** Diego (Gerencia de Administración)
- **Pasos:** 1. Editar obra → Carpeta de Drive `https://drive.example/Obras 2026/carpeta-ñandú-año` → "Guardar cambios". 2. Volver a "Editar".
- **Esperado:** el campo muestra lo que la persona escribió: `https://drive.example/Obras 2026/carpeta-ñandú-año`.
- **Obtenido:** `https://drive.example/Obras%202026/carpeta-%C3%B1and%C3%BA-a%C3%B1o`. Parece que el sistema lo rompió, aunque el link funciona. Visto en las obras 2-701-2026 (`…carpeta-%C3%B1and%C3%BA-a%C3%B1o`) y 2-702-2026.
- **Evidencia:** `capturas/ENT2-27-carpeta-codificada.png` · `ent/carpeta-y-repetida.cjs`
- **Causa probable (opcional):** el backend guarda la URL codificada (arreglo de PU-010) y el formulario la muestra tal cual, sin `decodeURI`.
- **Regresión de:** PU-010 · `167e14d`

### ENT-05 · Fechas previstas de la obra: acepta del 01/01/1900 al 31/12/2199 sin avisar nada
- **Severidad:** baja
- **Tipo:** validación
- **F-XX / pantalla:** F-16 · W-06 Nueva obra; F-20 · W-23 Editar obra (Inicio previsto / Fin previsto)
- **Persona:** Diego (Gerencia de Administración)
- **Pasos:** 1. Nueva obra completa, Inicio previsto 01/01/1900, Fin previsto 31/12/2199 → "Guardar obra".
- **Esperado:** el mismo criterio que el N.º de presupuesto ("El año tiene que estar entre 2000 y 2027."): avisar una fecha tan lejana, p. ej. "Revisá la fecha: es de 1900." (un año mal tipeado es lo más común).
- **Obtenido:** se guarda y la ficha muestra "Fechas previstas 01/01/1900 → 31/12/2199". Al editar la obra después, se vuelve a guardar sin aviso. Es el mismo caso que PU-028 (certificados, ⚠️ de VA), en otra pantalla.
- **Evidencia:** `capturas/ENT2-4-obra-fechas-lejanas.png` · `ent/obra-alta2.cjs`

### ENT-06 · El ejemplo de "Usá coma para los decimales" no usa el punto de miles que usa toda la pantalla
- **Severidad:** baja
- **Tipo:** mensaje
- **F-XX / pantalla:** F-48 · W-13 Presupuesto por rubro
- **Persona:** Diego (Gerencia de Administración)
- **Pasos:** escribir `1234.56` en un rubro y `abc` en otro → "Guardar".
- **Esperado:** el mismo ejemplo en los dos avisos: "Usá coma para los decimales, por ejemplo 1.234,56."
- **Obtenido:** en la misma pantalla, una celda dice "Usá coma para los decimales, por ejemplo 1234,56." y la de al lado "Escribí el monto con números, por ejemplo 1.500.000,50.". La tabla muestra los montos como "1.234,56". Visto en una corrida; VA vio el mismo texto en PU-015 (`PU-015-r2-1.png`).
- **Evidencia:** `capturas/ENT2-19-montos-raros.png` · `src/shared/dominio/importes.ts:31`

### ENT-07 · El mail mal escrito se avisa de tres maneras distintas
- **Severidad:** baja
- **Tipo:** mensaje
- **F-XX / pantalla:** W-06/W-23 contactos de la obra; W-38 proveedor; Usuarios (alta y edición)
- **Persona:** Diego (Gerencia de Administración)
- **Pasos:** escribir `ventas@` en el mail de cada formulario → Guardar.
- **Esperado:** un solo texto: "Escribí un mail válido, por ejemplo nombre@empresa.com."
- **Obtenido:** obra: "Escribí un mail válido, por ejemplo compras@cliente.com." · proveedor: "Escribí un mail válido." (sin ejemplo) · usuario: "Escribí un mail válido, por ejemplo nombre@empresa.com.". Reproducido 2 veces en cada uno.
- **Evidencia:** `ent/obra-alta.cjs`, `ent/proveedor.cjs`, `ent/usuario.cjs`, `ent/varios.cjs`

### ENT-08 · Sin acceso: "Proveedores: tu sector no tiene permiso…" suena a etiqueta, y en Plazos dice "Usuarios:"
- **Severidad:** baja
- **Tipo:** mensaje
- **F-XX / pantalla:** pantallas sin permiso (`NoAccessState`): `/proveedores`, `/usuarios`, `/usuarios/plazos`
- **Persona:** Sofía (Ingeniería de proyectos)
- **Pasos:** con Sofía, abrir `/proveedores` y `/usuarios/plazos` por URL.
- **Esperado:** "Tu sector no tiene permiso para ver Proveedores. Si lo necesitás, pedíselo a gerencia." y, en Plazos, "…para ver los Plazos de la prioridad." (el permiso es otro: `plazos_prioridad`).
- **Obtenido:** "No tenés acceso a esta sección" · "Proveedores: tu sector no tiene permiso para ver esto. Si lo necesitás, pedíselo a gerencia." y, en `/usuarios/plazos`, "Usuarios: tu sector no tiene permiso para ver esto.…". Visto 2 veces.
- **Evidencia:** `capturas/ENT2-23-sofia-0.png`, `capturas/ENT2-23-sofia-3.png` · `src/shared/ui/States.tsx:135`
- **Regresión de:** PU-014 · `18abdbc` (texto nuevo)

### ENT-09 · Sesión y "esperá" dichos de varias maneras
- **Severidad:** baja
- **Tipo:** mensaje
- **F-XX / pantalla:** transversal (mensajes por defecto del front y del backend)
- **Persona:** —
- **Pasos:** revisión de textos (`src/shared/api/ApiError.ts`, `app/Shared/Http/ApiExceptionRenderer.php`, `IdentidadServiceProvider.php`, `Idempotent.php`).
- **Esperado:** una sola forma para cada idea: "Tu sesión venció. Volvé a ingresar." para 401 y 419; "Probá de nuevo en …" para todas las esperas.
- **Obtenido:** sesión: "Tu sesión venció. Volvé a ingresar." (401) y "La sesión expiró. Volvé a ingresar." (419). Esperas: "Hiciste demasiados intentos. Esperá un momento y volvé a intentar." (front, 429) · "Fueron demasiadas acciones seguidas. Esperá unos segundos y volvé a intentar." (backend, 429) · "Demasiados intentos. Probá de nuevo en un minuto." · "Ya pediste varios códigos. Probá de nuevo en N minutos." · "El sistema no está disponible en este momento. Probá de nuevo en unos minutos." · "Esta operación todavía se está procesando. Probá de nuevo en unos segundos." ("operación" es la única palabra técnica que encontré; mejor: "Todavía estamos guardando lo anterior. Probá de nuevo en unos segundos."). Solo los vi en el código (en pantalla, únicamente "Tu sesión venció. Volvé a ingresar.").
- **Evidencia:** las rutas de arriba.

### ENT-10 · Un cliente de 101 a 150 letras hace fallar en silencio las sugerencias (422 en la red)
- **Severidad:** baja
- **Tipo:** validación
- **F-XX / pantalla:** F-16 · W-06 Nueva obra / W-23 Editar obra (campo "Cliente *")
- **Persona:** Diego (Gerencia de Administración)
- **Pasos:** escribir en Cliente un nombre de más de 100 caracteres (el campo acepta hasta 150).
- **Esperado:** las sugerencias no se piden pasado el largo de búsqueda (como quedó arreglado en proveedores por PU-038), o el backend acepta hasta 150.
- **Obtenido:** `GET /api/obras/clientes?buscar=…` → 422 `"Es demasiado largo (máximo 100 caracteres)."` en la consola; la persona no ve nada. No llega al canal `errors`. Reproducido 2 veces (310 caracteres desde la pantalla y 151 por API).
- **Evidencia:** `ent/obra-alta.cjs` (red: `GET /api/obras/clientes?buscar=ÁÁÁ… → 422`), `ent/explorar.cjs`

## No probado
- **"¿No te llegó?…", "Ya pediste varios códigos. Probá de nuevo en N minutos.", el bloqueo:** solo los leí en el código. No pedí códigos (regla del límite por IP; el bloqueo y el límite son del Lead).
- **"Ingreso trabado hasta las HH:MM", su ficha y "Destrabar ingreso":** cuando miré Usuarios no había ningún usuario trabado; no toqué los usuarios del Lead.
- **Health caído (503, `"redis": false`, `"cola_mail": false`):** lo prueba el Lead; solo vi el 200.
- **Sesión vencida de verdad:** la simulé con un 401 por `context.route`; no esperé a que venciera.
- **"Solo podés ver esta sección" (sin permiso de editar):** con Diego y Sofía no hay ninguna pantalla en ese caso; no toqué permisos.
- **Plazos:** solo los miré, sin guardar (pedido del brief).
- **Celular:** no repetí las entradas en Pixel 7 ni en iPhone SE (es del rol de celular).
