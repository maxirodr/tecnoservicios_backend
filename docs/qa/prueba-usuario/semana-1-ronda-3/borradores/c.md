# Borrador C · Verificador C: proveedores, textos y datos · Ronda 3

- **Fecha:** 2026-11-18 · back `66160b1` · front `1914ea8`
- **Sesiones usadas:** Carla (Compras), Diego (Gerencia Adm.), Hernán (Jefatura de Proyectos), Dora (Dirección), Sofía y Laura (Ingeniería de proyectos). No toqué "Cerrar sesión" ni Redis. Para la sesión vencida saqué la cookie de sesión **solo en mi contexto del navegador**. La sesión del servidor siguió viva: después `GET /api/me` → 200 con Carla y con Diego.
- **Ingresos reales gastados:** 0.
- **Datos creados (prefijo QA3-C):**
  - proveedores: "QA3-C Peña Hnos", "QA3-C Pena Hnos", "QA3-C Corralón Ñandú S.R.L." (30-71900101-3), "QA3-C Corralon Nandu", "QA3-C Muñoz e Hijos" (30-71900202-8), "QA3-C Munoz e Hijos", "QA3-C Logística Añelo S.R.L." y "QA3-C Logistica Anelo";
  - obras: 2-901-2026 "QA3-C Montaje línea Ñandú" (id 9, Confirmada) y 2-902-2026 "QA3-C Mantenimiento planta Añelo" (id 10, Terminada), con un certificado cada una;
  - sector "QA3-C Taller Sur", con el usuario "QA3-C Usuario Taller" (qa3-c-taller@empresa-ejemplo.com) y los permisos cambiados.
- **Scripts:** `C:\Users\juanc\AppData\Local\Temp\claude\C--Users-juanc\3fd8de0f-88e6-40e4-a658-bb42248ba145\scratchpad\qa3\C\` (`p1`…`p24`, helper `comun.mjs`).
- **Nota de entorno:** el servidor respondió lento (hasta 3–4 s en `/parecidos` y en la ficha, por las pruebas en paralelo). Con esperas de 1,5 s el aviso de parecidos todavía no aparecía. Con 4 s aparece siempre, así que no es un hallazgo.

## Verificación

| PU | título corto | estado | pantallas revisadas | evidencia | nota |
|---|---|---|---|---|---|
| PU-110 | Aviso de repetido trata la ñ como n | ✅ | W-38 Nuevo proveedor (Carla), 2 pares | `PU-110-r3-1…8.png` · `p1`, `p2`, `p3`, `p4` | "QA3-C Pena Hnos" → "Parecidos ya cargados: QA3-C Peña Hnos · … · Si es otro proveedor, podés guardar igual." → "Dimos de alta a QA3-C Pena Hnos.". Igual con Munoz/Muñoz, Corralon Nandu/Corralón Ñandú y Logistica Anelo/Logística Añelo S.R.L. (201). "QA3-C Peña Hnos" exacto → "Ya está cargado: QA3-C Peña Hnos. Abrí su ficha." apunta al proveedor correcto (no a Pena) y el guardado da 422. |
| PU-113 | Montos con un decimal | ✅ | W-13 PC y celular 375×667, historial | `PU-113-r3-1.png`, `-1t`, `-1c`, `-1h` | Cajas tras F5: "10,50" y "1.500.000,50". Total "1.502.011,25". Celular: "10,50 1.500.000,50". Historial: "MATERIALES: (vacío) → 10,50", "SUBCONTRATOS: (vacío) → 1.500.000,50" y "LOGISTICA: 2.000 → 2.000,25". |
| PU-116 | CUIT y mail mal: un error por vuelta | ✅ | W-38 Nuevo proveedor | `PU-116-r3-1.png` | En la primera vuelta y sin ningún POST salen los dos: "El CUIT no es válido. Revisá los números." y "Escribí un mail válido, por ejemplo nombre@empresa.com.". CUIT corto: "El CUIT tiene 11 números, por ejemplo 30-00000000-7." |
| PU-117 | Fechas lejanas sin aviso | ✅ | W-06 Nueva obra (Diego, Hernán), W-23 Editar, W-20 certificado, W-21 adicional | `PU-117-r3-1.png`, `-1e`, `-2e`, `PU-028-r3-1.png` | "Revisá la fecha: es de 1900." · "…es de 2199." · "…es de 2099.". No bloquea: guarda (201/200). Bordes: 1999 y 2037 avisan; 2000 y 2036 no. Pendiente de validación de Juani (ver Observaciones). |
| PU-118 | Cliente de 101–150 letras → 422 | ✅ | W-06 campo Cliente | sin captura (red) · `p6` | Con 129 caracteres: `GET /api/obras/clientes?buscar=…` → 200, sin errores en la consola. |
| PU-119 | Repetido: hasta 3 avisos iguales | ✅ | W-38 Nuevo y Editar (nombre, minúsculas, CUIT con y sin guiones) | `PU-119-r3-1…7.png` | Queda un solo aviso, en rojo, bajo el campo que choca. Nombre: "Ya está cargado: QA3-C Peña Hnos. Abrí su ficha." (POST → 422). CUIT: "Ya está cargado: QA3-C Corralón Ñandú S.R.L. Abrí su ficha." bajo **CUIT**. Editar: igual (PATCH → 422). "Guardar proveedor" sigue habilitado (decisión a validar). |
| PU-122 | REV00 rechazado con "Escribí la revisión como REV01." | ✅ | W-06 alta, W-23 editar, W-21 adicional | `PU-122-r3-1.png`, `PU-122-r3-2.png` | REV00, REV0 y rev00 → "Las revisiones empiezan en REV01." en las tres pantallas, sin pedido a la API. REV100 → "Escribí la revisión como REV01.". |
| PU-124 | Sacar sector con usuarios | ✅ | W-40 Sectores (Diego y Dora) | `PU-124-r3-1.png`, `-1b`, `-2`, `-2b` | "Antes de sacar QA3-C Taller Sur, pasá a su usuario a otro sector." (en singular) y "Antes de sacar Compras, pasá a sus 2 usuarios a otro sector.". Solo queda "Entendido": ya no está el botón "Sacar sector" y no sale ningún DELETE. |
| PU-125 | Obra Terminada: "pasar a Terminada" | ✅ | W-07 ficha 2-902-2026 (Hernán) | `PU-125-r3-1.png`, `-1d` | En la obra Terminada el botón dice "Cambiar estado". El diálogo deja "Confirmada" desactivada, preselecciona "En ejecución" y muestra "Pasar a En ejecución". Los avisos: "La obra pasó a En ejecución." y "La obra pasó a Terminada." |
| PU-126 | "Rige desde el próximo pedido…" | ✅ | W-15 Tabla de permisos | `PU-126-r3-1.png` | "Guardamos la tabla de permisos. Cada persona lo ve al pasar a otra pantalla o al recargar la página." (`PUT /api/permisos` → 200). |
| PU-128 | N.º de presupuesto repetido: dos avisos | ✅ | W-06 Nueva obra, W-21 adicional (Diego y Hernán) | `PU-128-r3-1a`, `-1b`, `-2a`, `-2b.png` | Un solo aviso, bajo N.º y con link: "Ese N.º de presupuesto ya está en QA3-C Mantenimiento planta Añelo." (link a `/obras/10`), igual en el alta y en el adicional, sin comillas simples (422). |
| PU-130 | Ejemplo "1234,56" sin punto de miles | ✅ | W-13 | `PU-130-r3-1.png` | "Usá coma para los decimales, por ejemplo 1.234,56." junto a "Escribí el monto con números, por ejemplo 1.500.000,50.". |
| PU-131 | Mail mal escrito dicho de 3 maneras | ✅ | W-06 contactos, W-38 proveedor, Usuarios (alta) | `PU-131-r3-1.png`, `PU-131-r3-3.png`, `PU-116-r3-1.png` | Las tres pantallas dicen "Escribí un mail válido, por ejemplo nombre@empresa.com.". |
| PU-132 | Sin acceso: "Proveedores: tu sector…" | ✅ | `/proveedores`, `/proveedores/2`, `/usuarios`, `/usuarios/sectores`, `/usuarios/permisos`, `/usuarios/plazos`, `/obras/nueva`, `/obras/9/presupuesto-por-rubro` con Sofía, Laura, Carla, Hernán y Dora | `PU-132-r3-*.png` | Se lee como oración: "Tu sector no tiene permiso para ver Proveedores. Si lo necesitás, pedíselo a gerencia.", "…ver los Plazos de la prioridad.", "…ver la Tabla de permisos.", "…ver Sectores.". Carla y Dora en `/obras/nueva`: "Tu sector puede ver las obras pero no darlas de alta.". Ingeniería en el presupuesto por rubro de una obra ajena: "Ves el presupuesto por rubro solo de las obras donde sos responsable.". Lo que cada sector ve coincide con la tabla `permisos`. |
| PU-133 | Sesión vencida y "esperá" dichos de varias maneras | ⚠️ | código y pantalla (sesión vencida en el listado de proveedores) | `C-01-1…3.png` | Lo que se arregló: en el código, 419, 429, 503 e Idempotent ya dicen "Probá de nuevo en …" y "Tu sesión venció. Volvé a ingresar.". Al guardar con la sesión vencida se llega a la pantalla de ingreso con "Tu sesión venció. Volvé a ingresar.". Lo que sigue: el 401 del backend dice otra cosa, y como su mensaje tiene prioridad sobre el del front, en el listado se ve "No pudimos cargar esta información · Tenés que ingresar para seguir. · Reintentar" (**C-01**). |
| PU-137 | Buscar por CUIT: ni ayudas ni resultado lo dicen | ✅ | `/buscar` y `/proveedores` (Diego, Carla) | `PU-137-r3-1.png`, `PU-137-r3-2.png` | Ayuda: "Por N.º de presupuesto, cliente, planta, nombre del trabajo o proveedor (nombre o CUIT).". Resultado: "QA3-C Corralón Ñandú S.R.L. · 30-71900101-3". Con 4 o más dígitos encuentra, con o sin guiones ("7190", "0101", "719001", "30-71900101-3"). Vacío del listado: "No encontramos proveedores · Probá con otra parte del nombre o del CUIT.". El placeholder del listado sigue diciendo solo "Buscar proveedor…" (detalle menor, no lo reporto). |
| PU-013 | Repetido: "podés guardar igual" y después no deja | ✅ | W-38 Nuevo y Editar | ver PU-110 y PU-119 | Lo que quedaba (PU-110 y PU-119) está resuelto. "Podés guardar igual" solo aparece con parecidos, y esos se guardan. |
| PU-028 | Certificado 31/12/2099 | ✅ | W-20 certificado (obras 9 y 10) | `PU-028-r3-1.png`, `-1b`, `-2`, `-2b` | Aviso "Revisá la fecha: es de 2099." antes de guardar, no bloquea (201). La tabla muestra "31-dic-2099". Queda sujeto a que Juani valide el aviso (ver Observaciones). |
| PU-038 | 422 de parecidos con nombre > 150 | ✅ | W-38 Editar proveedor; W-06 Cliente | `PU-038-r3-1.png` · ver PU-118 | Con 166 caracteres no sale ningún pedido a `/parecidos` y se ve "El nombre puede tener hasta 150 caracteres.". Las sugerencias de Cliente dan 200. |
| PU-043 | La ñ cuenta como n | ✅ | W-38, `/proveedores`, `/buscar` | `PU-043-r3-1.png`, `PU-043-r3-2.png` | Los parecidos se pueden guardar (esperado §1). "nandu" y "ÑANDÚ" encuentran "QA3-C Corralón Ñandú S.R.L." y "QA3-C Corralon Nandu", en el listado y en el buscador general. |

**Conteo:** ✅ 19 · ⚠️ 1 (PU-133) · ❌ 0.

## Hallazgos nuevos

### C-01 · Con la sesión vencida, el listado dice "Tenés que ingresar para seguir." en lugar de "Tu sesión venció. Volvé a ingresar."
- **Severidad:** baja
- **Tipo:** mensaje
- **F-XX / pantalla:** F-55 · W-14 Proveedores · `/proveedores` (cualquier lista que falle con 401)
- **Persona:** Carla (Compras)
- **Pasos:** 1. Abrir `/proveedores`. 2. Que venza la sesión (simulado sacando la cookie de sesión solo en el navegador de la prueba). 3. Escribir algo en "Buscar proveedor".
- **Esperado:** el mismo texto que el resto de la app para la sesión vencida, "Tu sesión venció. Volvé a ingresar.", con la salida a ingresar (PU-133).
- **Obtenido:** en el lugar de la lista aparece "No pudimos cargar esta información" · "Tenés que ingresar para seguir." · "Reintentar". Al tocar "Reintentar" recién lleva a `/ingresar`, donde dice "Tu sesión venció. Volvé a ingresar.". Para la misma situación hay dos textos distintos, y "Reintentar" no anticipa que te va a llevar a ingresar. Reproducido 2 veces con Carla. Con Diego, un pedido a `/api/me` se adelantó y lo llevó directo a ingresar.
- **Evidencia:** `capturas/C-01-1.png` · `capturas/C-01-2.png` · `capturas/C-01-3.png` (el camino de guardar sí termina en "Tu sesión venció…") · `GET /api/proveedores?page=1&buscar=zzqa3` → 401 · scripts `C/p20-sesion.mjs`, `C/p21-sesion-get.mjs`
- **Causa probable (opcional):** `tecnoservicios_backend/app/Shared/Http/ApiExceptionRenderer.php:28` `401 => 'Tenés que ingresar para seguir.'`. El front (`src/shared/api/ApiError.ts:22`) le da prioridad al mensaje del backend, así que su "Tu sesión venció. Volvé a ingresar." para 401 no se usa.
- **Regresión de:** PU-133 (quedó sin unificar el 401 del backend)

### C-02 · El historial de la obra muestra los links solo con el dominio ("agregado (drive.example)")
- **Severidad:** baja
- **Tipo:** mensaje
- **F-XX / pantalla:** F-14 Historial de la obra · `/obras/{id}/editar` (o "Historial de cambios")
- **Persona:** Diego (Gerencia Adm.), Hernán (Jefatura de Proyectos)
- **Pasos:** 1. Nueva obra con la oferta como link `https://drive.example/QA3-C/oferta tecnica ñandú.pdf`, sin "Nombre para mostrar". 2. En la ficha, "+ agregar certificado o documento" con el link `https://drive.example/QA3-C/certificado 1.pdf`. 3. Abrir el historial.
- **Esperado:** el mismo nombre que muestran la ficha y la tabla después de PU-112: "oferta tecnica ñandú.pdf" y "certificado 1.pdf".
- **Obtenido:** la ficha muestra "oferta tecnica ñandú.pdf" y "certificado 1.pdf", pero el historial dice "Oferta técnica: agregado (drive.example)" y "QA3-C Certificado N.º 1 · Archivo o link: agregado (drive.example)". Con links reales del Drive todas las líneas van a decir "drive.google.com". Visto en las obras 2-901-2026 (Diego) y 2-902-2026 (Hernán).
- **Evidencia:** `capturas/C-02-1.png` · `capturas/C-02-2.png` · `capturas/C-04-1.png` (la ficha con el nombre bueno) · script `C/p23-hist-cap.mjs`
- **Causa probable (opcional):** `app/Shared/Adjuntos/Application/RegistrarLinkAction.php:32-35` guarda como nombre el host, y `AdjuntosService.php:77` lo registra así en la actividad. El arreglo de PU-112 (front `e2605e5`/`3d48f82`) solo cambió cómo lo muestra el front.
- **Regresión de:** PU-112 (caso no cubierto: el historial)

### C-03 · El historial pone comillas simples al certificado ("Agregó el certificado 'QA3-C Certificado N.º 1'")
- **Severidad:** baja
- **Tipo:** mensaje
- **F-XX / pantalla:** F-14 Historial de la obra · `/obras/{id}/editar`
- **Persona:** Diego, Hernán
- **Pasos:** 1. "+ agregar certificado o documento" en una obra → Guardar. 2. Abrir el historial.
- **Esperado:** el mismo criterio que se fijó con PU-128 (sin comillas simples, que no se usan en ningún otro texto), como en la línea del adicional: "Agregó el presupuesto adicional 2-…".
- **Obtenido:** "Agregó el certificado 'QA3-C Certificado N.º 1'" y "Agregó el certificado 'QA3-C Certificado N.º 2'". En el mismo historial, "Quitó …" y "Agregó el presupuesto adicional …" van sin comillas.
- **Evidencia:** `capturas/C-03-1.png` · `capturas/C-03-2.png`
- **Causa probable (opcional):** `app/Modules/Obras/Application/Historial/DescripcionActividad.php:57` (`" '".self::texto(...)."'"`).

### C-04 · La misma ficha muestra las fechas con dos formatos ("01/01/1900" y "31-dic-2099")
- **Severidad:** baja
- **Tipo:** mensaje
- **F-XX / pantalla:** F-21 · W-07 Ficha de la obra · `/obras/{id}` y F-14 Historial
- **Persona:** Diego, Hernán
- **Pasos:** 1. Obra con fechas previstas y un certificado con fecha. 2. Mirar la ficha y el historial.
- **Esperado:** una sola forma de escribir una fecha en todas las pantallas (por ejemplo "31/12/2099" o "31-dic-2099").
- **Obtenido:** en la ficha, "Fechas previstas 01/01/1900 → 31/12/2199", y en la tabla de certificados de la misma ficha, "31-dic-2099" (y "18-nov" en "Cargado"). En el historial también se mezclan: "18-nov 13:52" en "Cuándo" y "Fin previsto: 31/12/2199 → 30/06/2099" en "Qué cambió". Visto en las obras 9 y 10.
- **Evidencia:** `capturas/C-04-1.png` (ficha) · `capturas/C-04-2.png` (historial)

## Observaciones de UX (decisiones a validar por Juani)

1. **Aviso de fecha lejana "Revisá la fecha: es de {año}."** (PU-028/117). Funciona en el alta, la edición, el certificado y el adicional. No bloquea, y los bordes 2000 y año actual + 10 (2036) son correctos. Me convence, con un detalle: el aviso solo está mientras se carga. Cuando la obra ya se guardó con 1900→2199, la ficha muestra "Fechas previstas 01/01/1900 → 31/12/2199" sin ninguna marca, y el aviso recién reaparece al abrir "Editar". Evidencia: `PU-117-r3-1.png`, `C-04-1.png`.
2. **"Las revisiones empiezan en REV01."** (PU-122). Es claro y es el mismo en las tres pantallas. En cambio, "REV100" da "Escribí la revisión como REV01.", que es el mismo texto confuso que tenía PU-122 en su momento: la persona escribió en ese formato y no entiende qué cambiar. Para ese caso, algo como "La revisión va de REV01 a REV99." Evidencia: `PU-122-r3-1.png` · `C/p22-bordes.mjs`.
3. **"Guardar proveedor" no se deshabilita ante un repetido y el aviso pasa a rojo.** Se ve bien: un solo aviso rojo con el link, y el 422 no agrega nada más. No veo problema. Evidencia: `PU-119-r3-1.png`.

## Lo que no pude probar

- PU-125: lo pasé una sola vez de punta a punta (obra 10). No repetí en la obra 9 para no dejarla Terminada, porque es la que uso para el resto.
- PU-118: sin captura, porque no hay nada visible que mostrar. La verificación es por la red (200).
- PU-133: la espera de 429 y el "Todavía estamos guardando lo anterior" de Idempotent los revisé solo en el código, no en pantalla.
- La sesión vencida es simulada (cookie quitada en mi contexto), no un vencimiento real por tiempo. El vencimiento real le toca al rol B.

Firma: Verificador C. Verifiqué 20 PU (16 de la ronda 2 y 4 ⚠️ de la ronda 1) con Carla, Diego, Hernán, Dora, Sofía y Laura, en proveedores, obras (alta, edición, adicional, certificado, presupuesto por rubro, estado e historial), sectores, permisos, buscador y pantallas sin acceso. 0 ingresos reales.
