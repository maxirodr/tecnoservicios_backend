# Borrador VB · Verificador de arreglos B (PU-023…PU-045) · Ronda 2

Entorno: front :5316 · API :8340 · base `tecnoservicios_prueba_usuario` · 2026-11-17.
Datos propios: sector "QA-VB Propias" (id 12, Obras = Editar · solo sus obras), usuario "QA-VB Vera" (id 11,
`qa-vb-1@empresa-ejemplo.com`, 1 ingreso con `h.ingresar` autorizado), obras 2-401-2026 "QA-VB Obra propia"
(id 7, responsable QA-VB Vera) y 2-402-2026 "QA-VB Obra ajena" (id 8, responsable Laura), proveedores
"QA-VB Corralón Ñandú S.R.L." (30-70400401-6, MATERIALES), "QA-VB Prueba CUIT" (30-70400402-4),
"QA-VB Corralon Nandu", "QA-VB Peña Hnos", "QA-VB Pena Hnos", "QA-VB Muñoz e Hijos", "QA-VB Munoz e Hijos",
"QA-VB Ferretería Cía.". El sector QA-VB quedó como se creó. Scripts: `scratchpad\r2\vb\`.

## 1. Verificación

| PU | Título corto | Estado | Pantallas revisadas | Evidencia | Nota |
|---|---|---|---|---|---|
| PU-023 | Filtro "Responsable" con "solo sus obras" | ✅ | `/obras` las 3 pestañas como QA-VB Vera; `/obras` como Diego (control) | `PU-023-r2-1..3.png` (Vera), `PU-023-r2-4.png` (Diego) | Con "solo sus obras" el desplegable "Responsable" ya no aparece en ninguna pestaña; Vera ve solo "2-401-2026 · QA-VB Obra propia". Diego sigue viendo "Todos, Carla, Diego, …". |
| PU-024 | Links mal escritos de proveedor | ✅ | `/proveedores/abc`, `/0`, `/-1`, `/1.5`, `/999999`, `/nuevo` | `PU-024-r2-1..6.png` | Los 5 links malos muestran "Proveedor no encontrado · Elegí otro de la lista."; `/proveedores/nuevo` abre el diálogo "Nuevo proveedor". |
| PU-025 | "Atrás" después de guardar un proveedor | ✅ | `/proveedores/5` → 2 guardados → `/proveedores/6` → Atrás ×3 | salida `12-prv-pantalla.cjs` | Atrás: `/proveedores/5` → `/proveedores` → `/obras`. Sin entradas repetidas. |
| PU-026 | Palabra suelta aceptada como link | ✅ | W-20 documento (ficha), W-06 alta (oferta y OC), W-21 adicional, "Carpeta de Drive" en W-23 | `PU-026-r2-1..4.png` | "hola", "drive", "localhost", "https://hola" → "Eso no parece un link. Copialo desde la barra del navegador." sin ir al servidor. "hola." va al servidor y vuelve con el mismo texto (422). |
| PU-027 | Error del nombre del link bajo "Link" | ✅ | W-20, W-06 oferta, W-21 adicional | `PU-027-r2-1.png` | "El nombre puede tener hasta 200 caracteres." sale bajo "Nombre para mostrar" y no hay `POST /api/adjuntos`. |
| PU-028 | Certificado 31/12/2099 | ⚠️ | W-20 ficha de la obra 2-401-2026 | `PU-028-r2-1.png` (sin aviso), `PU-028-r2-2.png` (tabla) | La tabla ya muestra el año: "31-dic-2099". Pero no hay ningún aviso por la fecha lejana: se guardó sin preguntar (el esperado de la ronda 1 pedía "¿Seguro? La fecha es de 2099."). Si ese aviso se descartó a propósito, pasa a ✅. |
| PU-029 | "Guardar" sin cambios | ✅ | W-23 editar obra (Hernán), editar proveedor (Carla), editar usuario QA-VB Vera (Diego), Mi perfil (Diego) | `PU-029-r2-1..4.png` | En los 4: "No había cambios para guardar." y ningún PATCH. |
| PU-030 | Mismo error de tipo de archivo dicho de dos maneras | ✅ | W-20 documento, W-06 oferta; además API directa | `PU-030-r2-1..3.png` | .exe (front) y .exe/.html (backend) dicen lo mismo: "Ese tipo de archivo no se puede subir. Se aceptan PDF, imágenes (JPG, PNG, WEBP, HEIC), Excel, Word, CSV y TXT."; el renombrado: "Este archivo no es un PDF aunque termine en .pdf. Elegí otro." (y ".docx", ".jpg", ".txt" en sus versiones). |
| PU-031 | Renombrar sector con el mismo nombre | ✅ | `/usuarios/sectores` · "renombrar" en "QA-VB Propias" | `PU-031-r2-1.png` | Mismo nombre y "  QA-VB Propias  " con espacios: la ventana se cierra sin mensaje y sin `PATCH`. |
| PU-032 | Fechas del historial en formato técnico | ✅ | W-23 historial de 2-401-2026 (Hernán) | `PU-032-r2-1.png` | "Fin previsto: 31/03/2027 → 30/04/2027". |
| PU-033 | Revisión larga sin explicación | ✅ | W-23 (Hernán) | `PU-033-r2-1.png` | "versión dos", "rev dos" y "REV1234567890" → "Escribí la revisión como REV01." |
| PU-034 | Doble punto con nombre terminado en punto | ✅ | Alta y repetido (W-38), edición, API | `PU-034-r2-1..3.png` | "Dimos de alta a QA-VB Corralón Ñandú S.R.L.", "Ver la ficha de QA-VB Corralón Ñandú S.R.L.", "Guardamos los cambios de QA-VB Corralón Ñandú S.R.L.", API `message: "Guardamos QA-VB Ferretería Cía."`: un solo punto en todos. |
| PU-035 | "Ver la ficha de…" colgado al corregir el nombre | ✅ | W-38 Nuevo proveedor | `PU-035-r2-1.png`, `PU-035-r2-2.png` | Al cambiar a "QA-VB Distinto Total" se van el error y el link. Con choque por CUIT, "Ese CUIT ya es de … · Ver la ficha de …" sale bajo **CUIT** y se va al corregir el CUIT. Ver VB-02 (el choque se avisa dos veces). |
| PU-036 | Aviso verde queda al cambiar de proveedor | ✅ | `/proveedores/5` → `/proveedores/6` | `PU-036-r2-1.png` | Al elegir "QA-VB Prueba CUIT" el aviso "Guardamos los cambios de …" ya no está. |
| PU-037 | Editar obra salta al cargar | ✅ | W-23 con 1,5 s de demora en cada request (2 veces) | `PU-037-r2-1.png`, `PU-037-r2-2.png` | "Preparando el formulario…" y después "Cargando el historial…"; `layout-shift` 0,0000 en las dos corridas (antes 0,156). |
| PU-038 | 422 del aviso de parecidos con nombre > 150 | ✅ | W-38 Editar proveedor | `PU-038-r2-1.png` | Nombre de 151 caracteres y CUIT de 40 dígitos: 0 pedidos a `/api/proveedores/parecidos`; "El nombre puede tener hasta 150 caracteres." / "El CUIT tiene 11 números, por ejemplo 30-00000000-7." |
| PU-039 | Canal `errors` con validaciones normales | ✅ (parte del archivo) · `/api/errors`: Lead | `storage/logs/` | listado de `storage/logs` | Generé varios 422 (`POST /api/proveedores` repetidos, `POST /api/adjuntos` renombrado y "hola."), un 404 (`GET /api/proveedores/999999`): `errors-2026-11-17.log` **no existe** (0 líneas) y `laravel-2026-11-17.log` no tiene ERROR/WARNING. La consulta de `/api/errors` queda para el Lead. |
| PU-040 | Pestañas cortadas en el celular | ✅ | `/obras` (Hernán) y `/usuarios` (Diego) en Pixel 7 e iPhone SE | `PU-040-r2-1..4.png` | Las pestañas se deslizan (`overflow-x: auto`) y hay sombra a la derecha que avisa que hay más ("Terminada…", "Tabla de permisos…"). El buscador de Obras tiene el rótulo completo "Buscar por N.º de presupuesto, cliente o trabajo" y la ayuda corta "N.º, cliente o trabajo…" entra entera. |
| PU-041 | Controles de 40 px en el celular | ✅ | `/obras`, ficha 2-401-2026, `/usuarios` en Pixel 7 e iPhone SE | `PU-041-r2-1..4.png` | Botones, campos, pestañas y "editar"/"quitar" miden 44 px (separados 12 px en horizontal, ahora con 44 de alto). "Carpeta de Drive (se abre en otra pestaña)" 118×44. Quedan por debajo solo el logo "TECNOSERVICIOS" del encabezado (141×23) y "Saltar al contenido" (39 px, solo visible con teclado); el casillero "Ver inactivos" mide 18×18 pero su rótulo, que también lo marca, mide 111×44. |
| PU-042 | Pestañas sin flechas del teclado | ✅ | `/obras` y ficha de obra (PC, Hernán) | salida `30-cel-teclado.cjs` | → / ← / Inicio / Fin cambian de pestaña (con vuelta al principio); solo la activa tiene `tabindex=0`; Tab sale al buscador. En la ficha, la única pestaña "Documentos y contactos" se comporta igual. |
| PU-043 | La ñ cuenta como n | ⚠️ | W-38 Nuevo proveedor (Carla), `/proveedores` buscar, `/buscar` | `PU-043-r2-1..8.png` | El guardado ya sigue la decisión: "QA-VB Corralon Nandu", "QA-VB Pena Hnos" y "QA-VB Munoz e Hijos" se dan de alta aunque existan "Corralón Ñandú S.R.L.", "Peña Hnos" y "Muñoz e Hijos". "nandu" encuentra "QA-VB Corralón Ñandú S.R.L." en el listado y en el buscador general. **Pero** el aviso previo al guardar dice "Ya está cargado: QA-VB Peña Hnos. Abrí su ficha." en vez de "Parecidos ya cargados: … Si es otro proveedor, podés guardar igual." → VB-01. |
| PU-044 | Buscar proveedor por CUIT | ✅ | `/proveedores` (buscador del listado) y `/buscar` | `PU-044-r2-1..6.png` | "30-70400401-6", "30704004016", "0401", "70400401", "30-7040" encuentran (listado y buscador general); "040" (3 dígitos) y "materiales" no: "No encontramos proveedores · Probá con otra parte del nombre." / "PROVEEDORES (0) · Sin resultados.", como decidió Juani. Detalle menor: las ayudas no mencionan el CUIT ("Probá con otra parte del nombre.", "Por N.º de presupuesto, cliente, planta, nombre del trabajo o proveedor."). |
| PU-045 | Bloqueo del ingreso | Lead (pendiente del Lead) | `/usuarios` (Diego) | salida `25-identidad.cjs`, `40-final.cjs` | El mensaje del ingreso lo prueba el Lead. En Usuarios no había ningún usuario bloqueado (`bloqueo_ingreso: null` en los 11, dos veces): no pude ver "Ingreso trabado hasta las HH:MM" ni "Destrabar ingreso". Pendiente del Lead cuando bloquee a su usuario. |

Conteo: ✅ 20 · ⚠️ 2 (PU-028, PU-043) · ❌ 0 · Lead 1 (PU-045; PU-039 a medias).

## 2. Hallazgos nuevos

### VB-01 · El aviso de proveedor repetido sigue tratando la ñ como n y contradice al guardado
- **Severidad:** media
- **Tipo:** mensaje
- **F-XX / pantalla:** F-56 · W-38 Nuevo proveedor · `/proveedores` (aviso "Ya está cargado" mientras se escribe)
- **Persona:** Carla (Compras)
- **Pasos:** 1. Dar de alta "QA-VB Peña Hnos". 2. "+ Nuevo proveedor" y escribir "QA-VB Pena Hnos". 3. Mirar el aviso bajo Nombre y tocar "Guardar proveedor". 4. Repetir con "QA-VB Muñoz e Hijos" / "QA-VB Munoz e Hijos" y con "QA-VB Corralón Ñandú S.R.L." / "QA-VB Corralon Nandu". 5. Escribir otra vez "QA-VB Peña Hnos" (el repetido de verdad) y guardar.
- **Esperado:** decisión de Juani para PU-043 (DU-Q04): la ñ no es n, "Pena" y "Peña" son parecidos → "Parecidos ya cargados: QA-VB Peña Hnos · Si es otro proveedor, podés guardar igual.", y el "Ya está cargado" solo para el mismo nombre de verdad.
- **Obtenido:** en el paso 2 sale "Ya está cargado: QA-VB Peña Hnos. Abrí su ficha." (sin "podés guardar igual"), pero al guardar lo da de alta: "Dimos de alta a QA-VB Pena Hnos.". Igual con "Ya está cargado: QA-VB Muñoz e Hijos. Abrí su ficha." y "Ya está cargado: QA-VB Corralón Ñandú S.R.L. Abrí su ficha.". En el paso 5, con los dos cargados, el aviso apunta al proveedor equivocado: "Ya está cargado: QA-VB Pena Hnos. Abrí su ficha." y, después de guardar, debajo "Ya existe QA-VB Peña Hnos." · "Ver la ficha de QA-VB Peña Hnos.": dos proveedores distintos para el mismo choque. Quien le cree al aviso no carga el proveedor nuevo o abre la ficha de otro. Reproducido 4 veces.
- **Evidencia:** `capturas/PU-043-r2-1.png` (Corralon Nandu) · `capturas/PU-043-r2-3.png` (Pena Hnos) · `capturas/PU-043-r2-5.png` (Munoz e Hijos) · `capturas/VB-02-1-repetido-dos-avisos.png` (Peña apunta a Pena) · `GET /api/proveedores/parecidos` devuelve los candidatos con `motivo: "nombre"` (bien); el backend acepta el `POST` (201).
- **Causa probable (opcional):** `tecnoservicios_frontend/src/features/proveedores/dominio.ts:28` `claveDeNombre()` hace `normalize('NFD')` + quita diacríticos, así que "ñ" → "n"; el backend (`8b3490f`) ya conserva la ñ en la clave. `Parecidos.tsx:39-43` usa esa clave para decidir "Ya está cargado".
- **Regresión de:** PU-043 / `8b3490f` (backend cambió la clave; el front quedó con la vieja)

### VB-02 · El proveedor repetido se avisa dos veces con textos distintos
- **Severidad:** baja
- **Tipo:** mensaje
- **F-XX / pantalla:** F-56 · W-38 Nuevo proveedor · `/proveedores`
- **Persona:** Carla (Compras)
- **Pasos:** 1. "+ Nuevo proveedor" con un nombre que ya existe ("qa-vb CORRALON ÑANDU srl" o "qa-vb ferreteria cia"). 2. "Guardar proveedor".
- **Esperado:** un solo aviso del choque con su link (el de PU-013/PU-035).
- **Obtenido:** bajo Nombre quedan tres renglones para lo mismo: "Ya existe QA-VB Ferretería Cía." · "Ver la ficha de QA-VB Ferretería Cía." · "Ya está cargado: QA-VB Ferretería Cía. Abrí su ficha.". Con CUIT repetido: "Ya está cargado: QA-VB Corralón Ñandú S.R.L. Abrí su ficha." bajo **Nombre** y "Ese CUIT ya es de QA-VB Corralón Ñandú S.R.L." · "Ver la ficha de …" bajo CUIT. Reproducido 3 veces.
- **Evidencia:** `capturas/PU-034-r2-2.png` · `capturas/PU-035-r2-2.png` · `capturas/VB-02-1-repetido-dos-avisos.png` · `capturas/VB-02-2-repetido-dos-avisos.png` · `POST /api/proveedores` → 422
- **Causa probable (opcional):** `ProveedorDialog.tsx` muestra el error del 422 con su link y `Parecidos.tsx` sigue mostrando su "Ya está cargado" al mismo tiempo.
- **Regresión de:** PU-013 / PU-035 · `fe6c562` (el aviso previo "Ya está cargado" se suma al error del servidor)

## No probado
- **PU-045:** el mensaje del ingreso bloqueado lo prueba el Lead (regla del brief). En Usuarios no había ningún usuario bloqueado durante mi turno, así que "Ingreso trabado hasta las HH:MM" y "Destrabar ingreso" quedan pendientes del Lead.
- **PU-039, parte `/api/errors`:** la consulta del endpoint la hace el Lead. Yo revisé solo los archivos de `storage/logs`.
- **PU-041:** no medí todas las pantallas una por una. Medí Obras, ficha y Usuarios en los dos celulares. Formularios y diálogos solo se revisaron en PC.
