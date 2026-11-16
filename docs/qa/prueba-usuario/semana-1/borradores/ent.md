# Borrador ENT · QA de entradas y errores + UX writer

> Rol ENT · 2026-11-16 · sesiones guardadas de Diego (Gerencia de Administración) y Sofía (Ingeniería).
> Datos creados con prefijo "QA-ENT" (obras 2-801-2026 en adelante). Scripts fuera de los repos.
> Primera tanda hasta las 14:49 UTC (corte de MySQL); segunda tanda 16:03–16:25 UTC con el entorno restablecido.
> Datos QA-ENT creados: obras 2-801-2026 (id 5, con adicional 9-001-2026 A1 y un certificado) y 2-802-2026 (id 8); proveedor "QA-ENT Proveedor Ñandú 😀 <b>SRL</b>" (id 5); sector "QA-ENT Sector Ñandú 😀" (id 18); usuario qa-ent-1@empresa-ejemplo.com (id 11). Plazos de la prioridad: quedaron en 1 / 7, como estaban.

### ENT-01 · Un link que todavía se está agregando se pierde al guardar la obra, sin aviso
- **Severidad:** alta
- **Tipo:** funcional
- **F-XX / pantalla:** F-16 · W-06 Alta de obra · `/obras/nueva` (mismo componente en W-21 adicional y W-20 documentos)
- **Persona:** Diego (Gerencia de Administración)
- **Pasos:** 1. Nueva obra, completar todo y subir la oferta técnica. 2. En "OC / OT / contrato / mail de aprobación" tocar "Pegar link", pegar `https://drive.example/oc-2-802-2026` y tocar "Agregar link". 3. Mientras "Agregar link" está gris (cargando), tocar "Guardar obra".
- **Esperado:** "Guardar obra" queda deshabilitado mientras se agrega el link (como ya pasa mientras sube un archivo: "Esperá a que terminen de subir los archivos para guardar.") o el link se guarda con la obra.
- **Obtenido:** la obra se crea y el link de la OC no queda en ningún lado: en la ficha "OC / OT / contrato / mail de aprobación" dice "—". El pedido `POST /api/obras` sale con `"aprobacion":{"tipo":"oc","numero":"4500","adjunto":null}` y el link queda huérfano en `adjuntos` (sin obra; se purga a las 24 h). Nadie le avisa al usuario. Reproducido 2 veces (obras 2-801-2026 y 2-802-2026).
- **Evidencia:** `capturas/ENT-5-alta-con-oc-link-antes-de-guardar.png` (botón "Agregar link" cargando y "Guardar obra" habilitado) · `capturas/ENT-ficha-5.png` · tabla `adjuntos` ids 19 y siguientes con `sujeto_id` NULL.
- **Causa probable (opcional):** `src/shared/adjuntos/AdjuntosField.tsx`: `subiendo` solo cuenta los archivos pendientes (`pendientes`), no el `PegarLink` en curso (`enviando` vive dentro de `PegarLink.tsx` y no llega a `onSubiendoChange`).

### ENT-02 · Un link con acentos o eñe se rechaza diciendo que "tiene que empezar con https://"
- **Severidad:** media
- **Tipo:** mensaje
- **F-XX / pantalla:** F-16, F-18, F-19 · W-06, W-20, W-21 · "Pegar link" en cualquier adjunto
- **Persona:** Diego (Gerencia de Administración)
- **Pasos:** 1. Nueva obra → Presupuesto (oferta técnica) → "Pegar link". 2. Pegar `https://drive.example/oferta-ñandú`. 3. "Agregar link".
- **Esperado:** se acepta (es un link http(s) válido; los de OneDrive/SharePoint con nombres de carpeta en castellano traen tildes) o, si no, un mensaje que diga el motivo real.
- **Obtenido:** "El link tiene que empezar con http:// o https://." — y el link empieza con https://. Lo mismo con `https://exa mple.com` (espacio). La versión con `%C3%A9` sí entra. Reproducido en la pantalla y por la API (`POST /api/adjuntos` → 422).
- **Evidencia:** `POST /api/adjuntos {url:"https://drive.example/oferta-ñandú"}` → 422 `errors.url = ["El link tiene que empezar con http:// o https://."]`.
- **Causa probable (opcional):** `app/Shared/Adjuntos/Http/Rules/LinkHttp.php` usa `FILTER_VALIDATE_URL`, que rechaza caracteres no ASCII, y para todo usa el mismo mensaje. El front (`normalizarLink`) podría mandar `url.href` (ya codificado).

### ENT-03 · Un archivo vacío (0 bytes) dice "tipo de archivo no se acepta" y figura como "1 KB"
- **Severidad:** media
- **Tipo:** mensaje
- **F-XX / pantalla:** F-16/F-19 · cualquier "Subir archivo"
- **Persona:** Diego
- **Pasos:** 1. Nueva obra → Presupuesto (oferta técnica) → "Subir archivo". 2. Elegir un `vacio.pdf` de 0 bytes (pasa cuando una descarga quedó cortada o un escaneo falló).
- **Esperado:** "El archivo está vacío (0 KB). Elegí otro." y el tamaño "0 KB".
- **Obtenido:** "vacio.pdf · 1 KB" y "Ese tipo de archivo no se acepta. Subí un PDF, una imagen, un Excel, un Word, un CSV o un TXT." La persona subió un PDF y se le dice que el PDF no se acepta. Reproducido 2 veces.
- **Evidencia:** `capturas/ENT-adj-vacio-pdf.png` · `POST /api/adjuntos` → 422.
- **Causa probable (opcional):** front `formatearTamano` usa `Math.max(1, …)`; backend `TipoPermitidoPorContenido` no distingue el archivo vacío.

### ENT-04 · El mismo error de tipo de archivo se dice de dos maneras distintas
- **Severidad:** baja
- **Tipo:** mensaje
- **F-XX / pantalla:** cualquier "Subir archivo"
- **Persona:** Diego
- **Pasos:** subir `programa.exe` y después `programa-renombrado.pdf` (el mismo .exe con otra extensión).
- **Esperado:** un solo texto, el que diga mejor qué hacer.
- **Obtenido:** .exe (lo frena el front): "Ese tipo de archivo no se puede subir. Se aceptan PDF, imágenes (JPG, PNG, WEBP, HEIC), Excel, Word, CSV y TXT." · .exe renombrado a .pdf (lo frena el backend, bien detectado por contenido): "Ese tipo de archivo no se acepta. Subí un PDF, una imagen, un Excel, un Word, un CSV o un TXT." Para el renombrado conviene además "Este archivo no es un PDF aunque termine en .pdf. Elegí otro."
- **Evidencia:** `capturas/ENT-adj-programa-renombrado-pdf.png`.

### ENT-05 · Cualquier palabra suelta se acepta como link ("hola" → https://hola)
- **Severidad:** baja
- **Tipo:** validación
- **F-XX / pantalla:** "Pegar link" en W-06/W-20/W-21 y carpeta de Drive
- **Persona:** Diego
- **Pasos:** "Pegar link" → escribir `hola` → "Agregar link".
- **Esperado:** "Eso no parece un link. Copialo desde la barra del navegador." (un dominio sin punto no es un link del Drive).
- **Obtenido:** se agrega como link "hola" que apunta a `https://hola`.
- **Evidencia:** `POST /api/adjuntos {url:"https://hola"}` → 201.

### ENT-06 · Si pegás el link y no tocás "Agregar link", al guardar te dice que no pegaste nada
- **Severidad:** media
- **Tipo:** mensaje
- **F-XX / pantalla:** F-16 · W-06 `/obras/nueva` (mismo `PegarLink` en W-20 y W-21)
- **Persona:** Diego
- **Pasos:** 1. Nueva obra con todo completo. 2. Oferta técnica → "Pegar link" → pegar `https://drive.example/oferta-sin-agregar`. 3. Sin tocar "Agregar link", tocar "Guardar obra".
- **Esperado:** el link se agrega solo al guardar, o el aviso dice qué falta: "Tocá «Agregar link» para sumar el link que pegaste."
- **Obtenido:** "Subí la oferta técnica o pegá su link." — la persona acaba de pegarlo; el link sigue escrito en la caja y no entiende qué hizo mal. Es el paso que más se va a saltear ("Agregar link" no parece obligatorio). En el campo opcional de la OC el mismo descuido haría que el link se pierda sin aviso (ver ENT-01; deducido del código, no reproducido aparte).
- **Evidencia:** `capturas/ENT-6-link-sin-agregar.png`.
- **Causa probable (opcional):** `PegarLink.tsx` guarda la URL en estado local; el formulario no sabe que hay un link escrito sin agregar.

### ENT-07 · Dos pestañas editando la misma obra: la segunda pisa lo que guardó la primera, sin avisar
- **Severidad:** alta
- **Tipo:** funcional
- **F-XX / pantalla:** F-20 · W-23 Editar obra · `/obras/{id}/editar`
- **Persona:** Diego (lo mismo pasa con dos personas editando la misma obra)
- **Pasos:** 1. Abrir "Editar" de la obra en la pestaña A y en la pestaña B. 2. En A cambiar el Cliente a "QA-ENT Cliente de A" y "Guardar cambios". 3. En B cambiar solo la Planta y "Guardar cambios".
- **Esperado:** B recibe el aviso que el sistema ya tiene escrito ("Alguien cambió esto mientras lo tenías abierto. Actualizá y revisá.") o, al menos, solo se guarda lo que B cambió.
- **Obtenido:** B dice "Guardamos los cambios de la obra." y el Cliente vuelve al valor viejo: el cambio de A se perdió sin que nadie se entere (en la base, tras A: `QA-ENT Cliente de A`; tras B: `QA-ENT Cliente`). Reproducido 2 veces (obras 2-801-2026 y 2-802-2026).
- **Evidencia:** `capturas/ENT-9-dos-pestanas.png` · `PATCH /api/obras/{id}` manda el formulario entero (nombre, cliente, planta, responsable, fechas, carpeta, contactos) aunque se haya tocado un solo dato.
- **Causa probable (opcional):** el front manda todos los campos en el PATCH y el backend no controla versión (`updated_at`), así que nunca hay 409.

### ENT-08 · F5, "atrás" o "Cancelar" a mitad de un formulario borran todo lo escrito sin preguntar
- **Severidad:** media
- **Tipo:** funcional
- **F-XX / pantalla:** F-16 `/obras/nueva` (15 campos y 2 adjuntos), F-20 `/obras/{id}/editar`, Mi perfil
- **Persona:** Diego
- **Pasos:** 1. Nueva obra, escribir cliente, nombre del trabajo, etc. 2. Apretar F5 (o "atrás" del navegador, o "Cancelar").
- **Esperado:** "Tenés datos sin guardar. ¿Salir igual?" antes de perderlos (o recuperar el borrador al volver).
- **Obtenido:** la página se recarga vacía (N.º = ""); "atrás" vuelve a Obras y al ir "adelante" el formulario está vacío; "Cancelar" sale sin preguntar. No aparece ningún aviso del navegador. Reproducido 2 veces.
- **Evidencia:** scripts `ent/obra-extra.cjs`, `ent/atras.cjs` (sin diálogo `beforeunload`).

### ENT-09 · "Cancelar" en Nueva obra abierta desde un link o pestaña nueva saca al usuario del sistema
- **Severidad:** media
- **Tipo:** funcional
- **F-XX / pantalla:** F-16 · W-06 `/obras/nueva`
- **Persona:** Diego
- **Pasos:** 1. Abrir `/obras/nueva` en una pestaña nueva (favorito o link que le pasaron). 2. Tocar "Cancelar".
- **Esperado:** vuelve a Obras (como "Cancelar" de Editar, que vuelve a la ficha).
- **Obtenido:** la pestaña queda en blanco (`about:blank`): salió del sistema. Reproducido 2 veces.
- **Evidencia:** `capturas/ENT-7-cancelar-pestana-nueva.png`.
- **Causa probable (opcional):** `src/features/obras/pantallas/NuevaObraPantalla.tsx:43` `onCancelar={() => void navigate(-1)}`.

### ENT-10 · "Guardar" sin haber cambiado nada dice "Guardamos los cambios"
- **Severidad:** baja
- **Tipo:** mensaje
- **F-XX / pantalla:** W-23 Editar obra, Editar proveedor, Editar usuario, Mi perfil
- **Persona:** Diego, Sofía
- **Pasos:** abrir el formulario de edición y tocar Guardar sin tocar nada.
- **Esperado:** "No había cambios para guardar." (el front ya tiene un texto así: "No hay cambios para guardar.", lo usa el presupuesto por rubro).
- **Obtenido:** "Guardamos los cambios de la obra." · "Guardamos los cambios de QA-ENT Proveedor Ñandú 😀 <b>SRL</b>." · "Guardamos los cambios de QA-ENT Ñoño Pérez 😀." · "Guardamos tus datos." (y hace el PATCH igual).
- **Evidencia:** `capturas/ENT-8-guardar-sin-cambios.png`.

### ENT-11 · Proveedor repetido: dice "podés guardar igual" y después no deja guardar
- **Severidad:** media
- **Tipo:** mensaje
- **F-XX / pantalla:** F-56 · W-38 Nuevo / Editar proveedor
- **Persona:** Diego
- **Pasos:** 1. "+ Nuevo proveedor" con el CUIT de "QA-PRV Corralón Ñandú S.R.L." (o editar uno y ponerle el nombre "qa-prv corralon nandu s.r.l."). 2. Leer el aviso de parecidos. 3. "Guardar proveedor".
- **Esperado:** con el mismo CUIT o el mismo nombre, el aviso dice desde el principio "Ya está cargado: QA-PRV Corralón Ñandú S.R.L. Abrí su ficha.", sin invitar a guardar.
- **Obtenido:** "Parecidos ya cargados: QA-PRV Corralón Ñandú S.R.L. (mismo CUIT) · Si es otro proveedor, podés guardar igual." y al guardar: "Ese CUIT ya es de QA-PRV Corralón Ñandú S.R.L." / "Ya existe QA-PRV Corralón Ñandú S.R.L.". Además el link dice "Ver la ficha de QA-PRV Corralón Ñandú S.R.L.." (doble punto cuando el nombre termina en punto). Reproducido 2 veces (por CUIT y por nombre).
- **Evidencia:** `capturas/ENT-17-proveedor-30712345698.png`, `capturas/ENT-18-proveedor-nombre-repetido.png`.

### ENT-12 · El error del "Nombre para mostrar" de un link aparece debajo del campo "Link"
- **Severidad:** baja
- **Tipo:** validación
- **F-XX / pantalla:** F-19 · W-20 Agregar certificado o documento (y todo "Pegar link")
- **Persona:** Diego
- **Pasos:** "Pegar link" → link válido → "Nombre para mostrar" con 300 caracteres → "Agregar link".
- **Esperado:** "El nombre puede tener hasta 200 caracteres." debajo de "Nombre para mostrar", y frenado en el front como el resto de los largos.
- **Obtenido:** el mensaje sale debajo de "Link" (que está bien) y recién después de ir al servidor (`POST /api/adjuntos` → 422).
- **Evidencia:** `capturas/ENT-11-link-nombre-largo.png`.
- **Causa probable (opcional):** `PegarLink.tsx`: `e.fieldError('url') ?? e.fieldError('nombre')` va al único `error` del Field "Link".

### ENT-13 · Montos con punto decimal: "Escribí el monto con números" cuando ya son números
- **Severidad:** baja
- **Tipo:** mensaje
- **F-XX / pantalla:** F-48 · W-13 Presupuesto por rubro
- **Persona:** Diego
- **Pasos:** escribir `1234.56` o `1.5` en un rubro (lo que sale al copiar de un sistema o planilla con punto decimal) → Guardar.
- **Esperado:** "Usá coma para los decimales, por ejemplo 1234,56."
- **Obtenido:** "Escribí el monto con números, por ejemplo 1.500.000,50." El resto anda bien: `-100` → "El monto no puede ser negativo.", `0` entra, `1.234,56` y "  2.500  " entran, `12,345` y `0,001` → "El monto puede tener hasta 2 decimales.", `1e15` → "Escribí el monto con números…", 16 dígitos → "El monto es demasiado grande.", `9.999.999.999.999,99` entra y el total da `10.000.000.003.746,89`. Doble clic en Guardar: un solo `PUT`.
- **Evidencia:** `capturas/ENT-13-montos-raros.png`, `capturas/ENT-14-montos-guardados.png`.

### ENT-14 · Un certificado con fecha 31/12/2099 se acepta y se muestra "31-dic", sin año
- **Severidad:** baja
- **Tipo:** validación
- **F-XX / pantalla:** F-19 · W-20 Certificados y otros documentos
- **Persona:** Diego
- **Pasos:** "+ agregar certificado o documento" → Certificado, fecha 31/12/2099 → Guardar.
- **Esperado:** avisar una fecha tan lejana ("¿Seguro? La fecha es de 2099.") y mostrar el año en la tabla cuando no es el actual ("31-dic-2099"): los certificados de una obra larga cruzan de año.
- **Obtenido:** se guarda y la tabla muestra "31-dic".
- **Evidencia:** `capturas/ENT-UX-sofia-_obras_5.png` (fila "QA-ENT Certificado N.º 1"), `ent/documentos.cjs`.

## Re-verificación de bugs de la entrega 1

| Bug | Estado | Cómo |
|---|---|---|
| B-02 códigos internos "(W-39)" | ✅ arreglado, visto en pantalla: Mi perfil "Sector y jefatura los cambia gerencia desde Usuarios."; Agregar/Editar usuario "Lo que puede ver y editar sale de la tabla de permisos de su sector…"; Sectores "Cada sector nuevo aparece como una fila más en la tabla de permisos."; Pedidos/Avisos "Va a estar disponible en una próxima entrega.". Ningún "(W-…)" en las pantallas recorridas. | `capturas/ENT-23-mi-perfil-sofia.png`, `ENT-19-sectores.png`, `ENT-20-agregar-usuario.png` |
| B-03 secciones no entregadas | ✅ en PC: barra de Diego Inicio · Obras · Proveedores · Usuarios; de Sofía Inicio · Obras; sin campana. `/avisos` y `/pedidos` a mano: "Esta sección todavía no está disponible / Va a estar disponible en una próxima entrega." (aceptable). Celular: rol celular. | `capturas/ENT-UX-diego-_avisos.png` |
| B-04 corte en 8 MB | ✅ en este entorno: 20 MB entra (≈14 s); 21 MB lo frena el front ("El archivo pesa 21 MB y el máximo es 20 MB.") y el backend si se manda directo (`POST /api/adjuntos` → 422, mismo texto). PHP con 2G de límite: no se probó el 413 de un PHP más chico. | `capturas/ENT-adj-20mb.png`, `ENT-adj-veintiun-mb-pdf.png` |
| B-05 importes del historial | ✅ "SUBCONTRATOS: (vacío) → 1.234,56", "HORAS LIQUIDADAS: (vacío) → 9.999.999.999.999,99". | `capturas/ENT-24-historial-obra.png` |

## UX writer · recorrido de pantallas
Como Diego: Inicio/Obras (3 pestañas), Nueva obra, ficha, Editar + Historial, Presupuesto por rubro, Cambiar estado, Agregar adicional, Agregar/editar documento, Proveedores (lista, ficha, alta, edición, inexistente), Usuarios, Agregar/Editar usuario, Sectores, Tabla de permisos, Plazos, Buscar (`qa-ent` y 1 letra), `/obras/99999`, `/obras/abc`, `/proveedores/99999`, `/pedidos`, `/avisos`, `/no-existe`. Como Sofía: Obras, ficha, Editar, Nueva obra, Mi perfil y, sin acceso, Proveedores, Usuarios, Plazos y Presupuesto por rubro.
- Ninguna pantalla mostró `undefined`, `null`, NaN, `[object Object]`, `validation.*`, inglés, SQL ni 500. 404: "Obra no encontrada · Puede que el link esté mal o que no tengas acceso a esa obra." y "Proveedor no encontrado · Elegí otro de la lista."; sin permiso: "No tenés acceso a esta sección · Proveedores: tu sector no tiene permiso para ver esto. Si lo necesitás, pedíselo a gerencia." Claros.
- Los textos con problema son hallazgos: ENT-02, 03, 04, 06, 10, 11, 13. Detalles menores sin hallazgo propio: N.º de adicional "2-80123-2026" → "Eso parecen dos presupuestos: cargá uno como principal y el otro como adicional." (para un error de tipeo, mejor "Revisá el número: tiene que ser como 2-131-2026."); el N.º repetido pone el nombre de la obra entre comillas simples ('…'), que el resto del sistema no usa.
- La API tiene textos genéricos para campos que la pantalla no deja vacíos ("Completá este campo.", "El valor elegido en estado no es válido.", "No existe o no está disponible."). Hoy no los ve nadie; anotados por si cambia el front.

## Lo que anduvo bien (para no re-probar)
- **Alta de obra:** vacío y solo espacios marcan cada obligatorio al lado del campo, en castellano y sin borrar lo escrito. 310 caracteres dan el máximo de cada campo. Fechas al revés: "La fecha de fin no puede ser anterior a la de inicio.". Mails mal escritos: "Escribí un mail válido, por ejemplo compras@cliente.com.". Carpeta `javascript:` rechazada; sin `https://` se completa. Emojis, ñ y `<script>` se guardan y se muestran como texto. " 2 801 2026 " / "rev 1" se normalizan. Doble y triple clic: una sola obra.
- **Edición:** vaciar cliente o nombre → error en el campo.
- **Adicional:** N.º vacío, "abc", año de 2 dígitos, año 2099 ("El año tiene que estar entre 2000 y 2027."), N.º repetido (lleva a la obra que lo tiene); sugiere "2-801-2026 A1".
- **Documentos:** vacío, nombre de 300, certificado sin fecha; varios archivos a la vez (el .exe se marca y los demás siguen); quitar todos → "Subí al menos un archivo o pegá un link."; cambiar archivos por un link y guardar anda; doble clic: un solo POST; **el archivo bajado conserva el nombre "Oferta técnica Año 2026 – ñandú.pdf"**.
- **Adjuntos:** .exe, .html y .svg rechazados; .exe renombrado a .pdf lo detecta el backend; links `javascript:`, `data:`, `file:`, `ftp:` rechazados; más de 1000 caracteres limitado.
- **Cambio de estado:** doble clic → un solo pedido; por la API, Confirmada → Terminada: "De Confirmada se pasa primero a En ejecución.".
- **Proveedor:** vacío, espacios, 150+, CUIT con 3 dígitos o letras ("El CUIT tiene 11 números, por ejemplo 30-00000000-7."), dígito verificador malo ("El CUIT no es válido. Revisá los números."), con espacios o puntos, mail `ventas@`, teléfono de 60; alta con doble clic → uno solo; el mail en mayúsculas se guarda en minúsculas.
- **Usuario QA-ENT:** vacío, espacios, largos, mails mal escritos, `  DIEGO@Empresa-Ejemplo.com ` → "Ese mail ya es de otro usuario (Diego)."; doble clic → uno solo; no se ofrece a sí mismo en "Depende de"; por la API, depender de sí mismo → "No puede depender de sí mismo.".
- **Sector QA-ENT:** vacío, espacios, 300 ("…hasta 80 caracteres."), "  COMPRAS " y "compras" → "Ya hay un sector con ese nombre."; doble clic → uno solo.
- **Plazos** (sin guardar): vacío "Completá un número entero de días.", -1 "No puede ser negativo.", 1.5 "Tiene que ser un número entero de días.", 0/0, 7/1 y 5/5 "Prioritario tiene que ser mayor que Urgente.", 366 "Puede ser hasta 365 días.". Quedaron en 1 / 7. (Una corrida anterior volvió a guardar 1 / 7, los mismos valores.)
- **Mi perfil (Sofía, sin cambios reales):** nombre vacío, 300 caracteres, teléfono de 60 → error en el campo y lo escrito se mantiene.
- **Logs:** ninguna excepción nueva causada por estas pruebas (solo WARNING de 422). En `errors-2026-11-16.log` hay 3 `Route [login] not defined` (500) a las 16:15 y 16:22 en `GET /api/adjuntos/{id}` y `GET /api/obras/1` sin sesión: no son de este rol (pedido directo sin `Accept: application/json`); para el rol backend.

## Cobertura

| F-XX | Estado | Hallazgos | Qué probé |
|---|---|---|---|
| F-10 Mi perfil | ⚠️ | ENT-08, ENT-10 | vacío, largo, teléfono largo, guardar sin cambios, F5 |
| F-16 Alta de obra | ⚠️ | ENT-01, 06, 08, 09 | vacío, espacios, 310 caracteres, emojis/ñ/HTML, mails mal, fechas al revés, carpeta `javascript:` y sin https, doble clic, F5, atrás, Cancelar |
| F-17 Ficha | ✅ | — | datos raros guardados se ven como texto; 404 con id inexistente y no numérico |
| F-18 Presupuestos | ✅ | — | N.º vacío, mal escrito, repetido, año fuera de rango |
| F-19 Documentos y adjuntos | ⚠️ | ENT-01…05, 12, 14 | 0 B, 20 MB, 21 MB (front y API), .exe, .exe→.pdf, .html, .svg, acentos y espacios, varios a la vez, 18 variantes de link, quitar, archivo por link, descarga con nombre |
| F-20 Editar obra | ❌ | ENT-07, 08, 10 | sin cambios, vaciar campos, dos pestañas |
| F-21 Cambiar estado | ✅ | — | doble clic, salto de estado y estado inválido por API |
| F-23 Fechas previstas | ✅ | — | al revés, mismo día |
| F-48 Presupuesto por rubro | ⚠️ | ENT-13 | negativo, 0, coma, punto, 1e15, 16 dígitos, 3 decimales, texto, `<script>`, máximo, doble clic |
| F-56 Proveedor | ⚠️ | ENT-10, 11 | vacío, espacios, largo, CUIT corto/letras/DV malo/repetido, mail, teléfono, nombre repetido, sin cambios, doble clic |
| F-58 Usuarios | ⚠️ | ENT-10 | vacío, espacios, largos, mails mal y repetido con mayúsculas, jefe = sí mismo, sector inexistente, doble clic |
| F-59 Sectores | ✅ | — | vacío, espacios, largo, repetido con mayúsculas y espacios, emojis, doble clic |
| F-68 Plazos | ✅ | — | vacío, negativo, decimal, 0/0, invertidos, iguales, más de 365 (sin dejar cambios) |
| Transversal: textos (UX) | ⚠️ | ENT-02, 03, 04, 06, 10, 11, 13 | ~20 pantallas como Diego y 9 como Sofía; B-02/B-03/B-04/B-05 re-verificados |

## No probado
- Renombrar o sacar el sector QA-ENT y dar de baja al usuario QA-ENT: lo cubre el rol de administración.
- Dos pestañas en adicional, documentos, proveedor y usuario: probablemente tengan el mismo problema de ENT-07; solo lo reproduje en la obra.
- El link escrito sin "Agregar link" en el campo opcional de la OC: en ENT-06 está deducido del código y de ENT-01, sin reproducirlo aparte.
- Celular y teclado del celu: rol celular.
- El 413 del backend con un PHP de 8 MB: este entorno tiene 2G y no toqué `php.ini`.
- Sesión vencida a mitad de un formulario: es del Lead (no se puede forzar sin cerrar sesiones compartidas).
