# Borrador ENT · QA de entradas y errores + UX writer

> Rol ENT · 2026-11-16 · sesiones guardadas de Diego (Gerencia de Administración) y Sofía (Ingeniería).
> Datos creados con prefijo "QA-ENT" (obras 2-801-2026 en adelante). Scripts fuera de los repos.
> (en curso — MySQL estuvo caído durante la prueba, ver "No probado")

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

## Re-verificación de bugs de la entrega 1

| Bug | Estado | Cómo |
|---|---|---|
| B-02 códigos internos "(W-39)" | ✅ en lo recorrido (Nueva obra, ficha) y en el código: los textos de Mi perfil, Usuarios y Sectores ya no tienen códigos; el `grep` de `(W-|F-|HU-…)` en `src/` solo encuentra comentarios. **Falta mirarlo en pantalla** en Mi perfil, Usuarios y Sectores (MySQL caído). | `capturas/ENT-5-…png` |
| B-03 secciones no entregadas | ✅ en PC: la barra de Diego es Inicio · Obras · Proveedores · Usuarios, sin campana. Las rutas a mano (`/avisos`, `/pedidos`) muestran "Esta sección todavía no está disponible / Va a estar disponible en una próxima entrega." (aceptable). Celular no revisado (lo hace el rol celular). | `capturas/ENT-5-…png` |
| B-04 corte en 8 MB | ✅ arreglado en este entorno: un PDF de 20 MB entra (≈14 s con el server de a un pedido); uno de 21 MB lo frena el front con "El archivo pesa 21 MB y el máximo es 20 MB." PHP de este entorno: `upload_max_filesize`/`post_max_size` = 2G. No probé el 413 del backend con un PHP más chico. | `capturas/ENT-adj-20mb.png`, `capturas/ENT-adj-veintiun-mb-pdf.png` |

## Lo que anduvo bien (para no re-probar)
- Alta de obra vacía o solo con espacios: cada campo obligatorio marca su error al lado, en castellano, sin borrar lo escrito ("Completá el N.º de presupuesto, por ejemplo 2-131-2026.", "Elegí el responsable de obra.", etc.). `capturas/ENT-1-obra-vacia.png`.
- 310 caracteres en cada campo: "El nombre del trabajo puede tener hasta 200 caracteres.", "El link puede tener hasta 500 caracteres.", etc. `capturas/ENT-2-obra-largo.png`.
- Fechas al revés: "La fecha de fin no puede ser anterior a la de inicio."; mismo día sí entra. Mails `jose@`, `maria peña@cliente .com`, `jose@@cliente.com`: "Escribí un mail válido, por ejemplo compras@cliente.com." `capturas/ENT-3-…png`.
- Carpeta de Drive `javascript:alert(1)` → "El link tiene que empezar con http:// o https://."; sin `https://` se completa solo.
- Links `javascript:`, `JaVaScRiPt:`, con espacio adelante, `data:`, `file:`, `ftp:` → rechazados; más de 1000 caracteres → "El link puede tener hasta 1000 caracteres."
- Archivos: .exe, .html y .svg rechazados; .exe renombrado a .pdf lo detecta el backend por contenido; nombre con acentos, espacios y raya ("Oferta técnica Año 2026 – ñandú.pdf") se sube y se ve bien en la ficha.
- Emojis, tildes, ñ y `<script>` en nombre del trabajo, cliente y planta: se guardan tal cual y se muestran como texto (no se ejecuta nada). N.º " 2 801 2026 " y "rev 1" se normalizan a "2-801-2026" y "REV01". Mail en mayúsculas se guarda en minúsculas.
- Doble clic (y triple) en "Guardar obra": un solo `POST /api/obras`, una sola obra.
- Logs: ninguna excepción nueva por mis pruebas hasta las 14:49 UTC (solo WARNING de validación 422, esperables). Las ~560 líneas ERROR posteriores son de la caída de MySQL.

## Cobertura

| F-XX | Estado | Hallazgos | Qué probé |
|---|---|---|---|
| F-16 Alta de obra | ⚠️ | ENT-01 | vacío, espacios, 310 caracteres, emojis/ñ/HTML, mails mal, fechas al revés, carpeta `javascript:` y sin https, doble clic |
| F-19 Adjuntos (ADR-014) | ⚠️ | ENT-01…05 | 0 B, 20 MB, 21 MB, .exe, .exe→.pdf, .html, .svg, acentos y espacios; 18 variantes de link |
| F-17 Ficha | ✅ | — | cómo se ven los datos raros guardados |
| F-18, F-20, F-21, F-23, F-48, F-56, F-58, F-59, F-68, F-10 | sin probar | — | MySQL caído (ver abajo) |

## No probado
MySQL se apagó a las 14:49 UTC ("Received SHUTDOWN from user root" en `C:\laragon\data\mysql-8.4\Green.err`, parece la prueba de "errores forzados" del Lead) y a las 15:16 UTC (27 min después) seguía sin levantar; la API de salud no responde. Sin base no hay sesión, así que quedó sin probar:
- Edición de obra (guardar sin cambios, dos pestañas), presupuesto adicional, documentos/certificados (fecha, 10 adjuntos, cambiar archivo por link, quitar, descargar y ver el nombre del archivo bajado), presupuesto por rubro (negativos, 0, "1.234,56", "1234.56", 1e15), cambio de estado.
- Link escrito sin tocar "Agregar link" y después "Guardar" (script listo: `ent/obra-extra.cjs`), F5 y volver atrás a mitad del formulario, dónde queda el foco cuando el único error es la oferta.
- Proveedor (CUIT inválido/repetido, mail repetido), usuario QA-ENT (mail mal/repetido), sector "QA-ENT", plazos (solo mirar), Mi perfil.
- Recorrido UX de todas las pantallas en vivo (B-02 en Mi perfil/Usuarios/Sectores).
