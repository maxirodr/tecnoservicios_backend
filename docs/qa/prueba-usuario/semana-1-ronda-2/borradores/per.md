# Borrador PER · Tester funcional por persona · Ronda 2 Semana 1 (2026-11-17)

Pasada completa de la Semana 1 como cada persona, con las sesiones guardadas del Lead (sin pedir códigos).
Datos propios: sector "QA-PER Taller" (renombrado "QA-PER Taller Norte"), usuarios `qa-per-1` (QA-PER Ulises Taller) y
`qa-per-2` (QA-PER Valeria Taller), obras `2-601-2026` (OC como link, responsable Hernán, llevada hasta Terminada) y
`2-602-2026` (OC como archivo, responsable Laura), proveedores "QA-PER Herrajes del Sur S.A." (CUIT 30-71600601-4) y
"QA-PER Bulonera Atlántica". Scripts: `scratchpad\r2\per\*.cjs`. Capturas: `capturas\PER-*.png`.

Canal `errors`: después de 7 respuestas 422 (usuario con mail repetido, círculo de jefatura, proveedor repetido, CUIT
repetido, CUIT inválido, sacar sector con usuarios) **no se creó** `errors-2026-11-17.log` (bien: no registra 4xx).
Consola del navegador limpia en todas las pantallas, salvo el "Failed to load resource … 422" de esas validaciones.

## 1. PU vistos de paso (no es la verificación formal: la hacen VA/VB)

| PU | título corto | estado | pantallas revisadas | evidencia | nota |
|---|---|---|---|---|---|
| PU-014 | "Tu sector no tiene permiso para ver esto" cuando sí puede ver | ✅ | Carla `/obras/nueva`; Ingeniería `/proveedores`, `/usuarios/*`; Sofía/Pablo rubro ajeno | `PER-44-carla-editar-obra.png`, `PER-34-rubro-16-*.png` | Carla: "Solo podés ver esta sección / Tu sector puede ver las obras pero no darlas de alta. Si lo necesitás, pedíselo a gerencia." Pablo: "No sos responsable de esta obra / Ves el presupuesto por rubro solo de las obras donde sos responsable." |
| PU-015 | Montos con punto decimal | ✅ | W-13 como Diego | script `32-rubro-carga.cjs` | "Usá coma para los decimales, por ejemplo 1234,56." |
| PU-022 | Usuarios en el celular | ✅ | Usuarios en Pixel 7 e iPhone SE 375 | `PER-09-usuarios-tarjetas-*.png`, `PER-11-dlg-usuario-se-abajo.png` | Tarjetas, sin desplazamiento horizontal (412/412, 375/375); editar desde la tarjeta, botones de 44 px. |
| PU-029 | Guardar sin cambios | ⚠️ | Usuario, Mi perfil (Dora, Diego), proveedor, rubro: ✅ "No había cambios para guardar." / "No hay cambios para guardar." · **Plazos: ❌** | `PER-16-plazos-sin-cambios.png` | Ver PER-02. |
| PU-032 | Fechas del historial | ✅ | Editar obra 2-601-2026 | `PER-28-editar-historial.png` | "Fin previsto: 28/02/2027 → 15/03/2027". |
| PU-044 | Proveedores por CUIT | ✅ | Listado (Carla) y buscador general (Dora, Diego, Hernán, Carla) | `PER-43-prov-buscar-cuit.png` | "6006", "716006", "30-71600601-4" lo encuentran; "060" (3) y "materiales" no. Ver PER-06 sobre cómo se muestra. |

## 2. Hallazgos nuevos

### PER-01 · La revisión "REV00" se rechaza con "Escribí la revisión como REV01."
- **Severidad:** baja
- **Tipo:** mensaje
- **F-XX / pantalla:** F-16 · W-06 Alta de obra · `/obras/nueva` (por código, también Editar y el adicional)
- **Persona:** Hernán (Jefatura de Proyectos)
- **Pasos:** 1. Nueva obra con todo completo. 2. En "Revisión *" escribir `REV00` (o `REV0`). 3. Guardar obra.
- **Esperado:** si la revisión inicial de la empresa puede ser 00 (habitual en ofertas técnicas), aceptarla; si no, decir por qué: p. ej. "Las revisiones empiezan en REV01." RN-OBR-14 lista las formas aceptadas y no dice nada del 0.
- **Obtenido:** debajo del campo, "Escribí la revisión como REV01." La persona escribió exactamente con ese formato y no entiende qué cambiar. No sale ningún pedido a la API (lo frena el front). Reproducido 2 veces (REV00, REV0).
- **Evidencia:** `capturas/PER-23-revision-rev0.png` · script `per/22-obra-alta.cjs`

### PER-02 · Plazos de la prioridad: "Guardar" sin cambiar nada dice "Guardamos los plazos…"
- **Severidad:** baja
- **Tipo:** mensaje
- **F-XX / pantalla:** F-68 · W-50 Plazos de la prioridad · `/usuarios/plazos`
- **Persona:** Diego (Gerencia de Administración)
- **Pasos:** 1. Usuarios → Plazos de la prioridad. 2. Sin tocar nada (1 / 7), tocar "Guardar".
- **Esperado:** "No había cambios para guardar.", como ya hacen Usuario, Mi perfil, Proveedor y Obra tras el arreglo de PU-029 (y sin `PUT`).
- **Obtenido:** "Guardamos los plazos. Valen para los pedidos que se carguen desde ahora." y sale `PUT /api/plazos-prioridad` (200). Reproducido 2 veces. Los valores quedaron igual (1 / 7).
- **Evidencia:** `capturas/PER-16-plazos-sin-cambios.png` · `capturas/PER-13-plazos-diego.png` · script `per/14-plazos-perfil.cjs`, `per/17-plazos-sector.cjs`
- **Regresión de:** — (el arreglo de PU-029 no alcanzó esta pantalla; PU-029 no la listaba)

### PER-03 · Sacar un sector con un usuario: pregunta "¿Sacar…?" y ofrece el botón aunque ya avisa que no se puede
- **Severidad:** baja
- **Tipo:** mensaje
- **F-XX / pantalla:** F-59 · W-40 Sectores · `/usuarios/sectores`
- **Persona:** Diego y Dora
- **Pasos:** 1. Sectores. 2. "sacar" en "QA-PER Taller Norte" (1 usuario activo). 3. Tocar "Sacar sector".
- **Esperado:** si no se puede, decirlo de entrada con el texto de RN-IDE-31 ("Antes de sacar …, pasá a su usuario a otro sector.") y sin un botón que no va a funcionar (o con el botón desactivado); concordancia en singular.
- **Obtenido:** el diálogo muestra a la vez "¿Sacar QA-PER Taller Norte? Deja de aparecer en la lista, en los desplegables y en la tabla de permisos." y "Tiene 1 usuario activo: antes hay que pasarlos a otro sector." (plural con 1), con "Sacar sector" habilitado. Al tocarlo: `DELETE /api/sectores/20` → 422 y se suma arriba "Antes de sacar QA-PER Taller Norte, pasá a su usuario a otro sector."; el diálogo queda con los tres textos. Reproducido 2 veces (Diego, Dora).
- **Evidencia:** `capturas/PER-17-sacar-sector-con-usuario.png` · script `per/17-plazos-sector.cjs`, `per/52-sacar.cjs`

### PER-04 · En una obra Terminada el botón sigue diciendo "Cambiar estado / pasar a Terminada"
- **Severidad:** baja
- **Tipo:** mensaje
- **F-XX / pantalla:** F-21/F-22 · W-07 Ficha de la obra · `/obras/15`
- **Persona:** Hernán (responsable de la obra)
- **Pasos:** 1. Pasar 2-601-2026 a En ejecución y luego a Terminada. 2. Volver a la ficha.
- **Esperado:** en una obra ya terminada, un rótulo que corresponda (p. ej. "Cambiar estado" o "Volver a En ejecución"); RN-OBR-30 nombra el botón "Cambiar estado / pasar a Terminada" pensando en las obras activas.
- **Obtenido:** "Cambiar estado / pasar a Terminada" con la etiqueta "Terminada" al lado. El diálogo sí se arma bien: Confirmada desactivada, preseleccionada "En ejecución", botón "Pasar a En ejecución". Reproducido 2 veces.
- **Evidencia:** `capturas/PER-31-boton-estado-en-terminada.png` · script `per/29-terminadas.cjs`, `per/30-estado2.cjs`

### PER-05 · Proveedor con CUIT y mail mal: primero avisa solo el mail y el CUIT recién en el segundo intento
- **Severidad:** baja
- **Tipo:** validación
- **F-XX / pantalla:** F-56 · W-38 Nuevo proveedor · `/proveedores` → "+ Nuevo proveedor"
- **Persona:** Carla (Compras)
- **Pasos:** 1. Nombre "QA-PER Prueba dos errores", CUIT `30-71600601-5` (dígito verificador mal), mail `ventas@`. 2. "Guardar proveedor". 3. Corregir el mail y volver a guardar.
- **Esperado:** los dos errores juntos en la primera vuelta.
- **Obtenido:** vuelta 1: solo "Escribí un mail válido." (no sale pedido a la API). Vuelta 2: "El CUIT no es válido. Revisá los números." (422 de la API). El front valida el largo del CUIT ("El CUIT tiene 11 números, por ejemplo 30-00000000-7.") pero no el dígito verificador. Reproducido 2 veces.
- **Evidencia:** `capturas/PER-40-prov-cuit-malo.png` (vuelta 1) · `capturas/PER-45-prov-cuit-segunda-vuelta.png` (vuelta 2) · script `per/41-prov-alta.cjs`, `per/43-carla-obras.cjs`

### PER-06 · Buscador general: encuentra proveedores por CUIT pero no lo dice ni muestra el CUIT
- **Severidad:** baja
- **Tipo:** consistencia-con-documento
- **F-XX / pantalla:** F-03 · W-18 Buscador · `/buscar`
- **Persona:** Dora, Diego, Hernán, Carla
- **Pasos:** 1. Lupa. 2. Escribir "6006" (parte del CUIT 30-71600601-4).
- **Esperado:** como en el listado de Proveedores ("MATERIALES · 30-71600601-4"), que el resultado muestre el CUIT con el que coincidió, y que la ayuda lo mencione (decisión PU-044: el buscador general encuentra por CUIT).
- **Obtenido:** ayuda "Por N.º de presupuesto, cliente, planta, nombre del trabajo o proveedor."; resultado "PROVEEDORES (1) / QA-PER Herrajes del Sur S.A. · MATERIALES", sin el CUIT: la persona no ve por qué apareció. Igual con las 4 personas.
- **Evidencia:** `capturas/PER-50-buscador-carla.png` · script `per/50-buscador.cjs`

### PER-07 · "Rige desde el próximo pedido de cada persona al sistema.": "pedido" se confunde con los Pedidos
- **Severidad:** baja
- **Tipo:** mensaje
- **F-XX / pantalla:** F-60 · W-15 Tabla de permisos · `/usuarios/permisos`
- **Persona:** Diego (Gerencia de Administración)
- **Pasos:** 1. Cambiar dos celdas de "QA-PER Taller" (Proveedores → Ver, Presupuesto por rubro → Ver · sus obras). 2. "Guardar cambios".
- **Esperado:** en el sistema "pedido" es un pedido de trabajo (módulo Pedidos). Algo como "Guardamos la tabla de permisos. Cada persona lo ve la próxima vez que use el sistema (o al recargar)."
- **Obtenido:** "Guardamos la tabla de permisos. Rige desde el próximo pedido de cada persona al sistema." Mismo texto en la ronda 1 (borrador ADM, ADM-02); nadie lo marcó.
- **Evidencia:** `capturas/PER-12-permisos-guardado.png` · script `per/13-permisos-cambio.cjs`

Ya reportado por otro rol (no lo duplico): importes con un decimal ("1.500.000,5", "12.650.000,5") en W-13 y en el historial → **VA-02**.

## 3. Cobertura

| F-XX | Estado | Hallazgos | Qué probé |
|---|---|---|---|
| F-01 (lo visible sin códigos) | ✅ | — | Sin sesión `/obras/15` → `/ingresar`; "Te mandamos un código para entrar.", "Si no tenés usuario, pedíselo a gerencia."; mail mal formado → "Escribí un mail válido, por ejemplo nombre@empresa.com.". No pedí códigos. |
| F-02 Menú | ✅ | — | Dora/Diego: Inicio · Obras · Proveedores · Usuarios; Hernán y Carla: Inicio · Obras · Proveedores; Ingeniería: Inicio · Obras (§6.4). Celular "Más": Proveedores · Usuarios · Mi perfil · Salir. URL directa sin permiso: mensaje claro en 6 rutas × 3 ingenieras. |
| F-03 Buscador general | ⚠️ | PER-06 | 6 personas × 6 búsquedas (texto, N.º sin guiones "2601", sin tilde "quimica", CUIT 4 dígitos, proveedor, 1 letra → "Escribí al menos 2 letras o números."). Ingeniería no ve la sección Proveedores; terminada marcada "Terminada". |
| F-10 Mi perfil | ✅ | — | Dora, Diego (guardar sin cambios → "No había cambios para guardar.", nombre vacío → "Completá el nombre."), Laura/Pablo/Carla ("Depende de Hernán (Jefatura de Proyectos)", "Sector y jefatura los cambia gerencia desde Usuarios."). Teléfono es texto libre (spec §4.1). |
| F-14 Historial | ✅ | — | Alta, adicional (revisión "rev 2" → REV02, link, descripción), certificado con archivo + link, documento, edición (OC, revisión, fin, Drive, teléfono de contacto), dos cambios de estado; más nuevo arriba, fechas dd/mm/aaaa. Montos de obra ocultos para Laura y Hernán, visibles para Diego (API). Dora y Carla lo ven por "Historial de cambios". |
| F-15 Listado | ✅ | — | Pestañas con contadores, búsqueda, volver desde la ficha conserva pestaña y búsqueda (`?estado=terminada&buscar=vapor`). |
| F-16 Alta de obra | ⚠️ | PER-01 | Hernán: OC como link (2-601-2026) y como archivo con ñ (2-602-2026), fechas, contactos. |
| F-17 Ficha | ✅ | — | Botones según quién mira (Hernán, Laura, Sofía, Pablo, Carla, Dora, Diego); "Abrir carpeta de Drive de la obra"; iPhone SE sin desplazamiento horizontal. |
| F-18 Presupuestos | ✅ | — | Adicional sugerido "2-601-2026 A1" con solo el N.º (válido por DU-A14), después editado con link y revisión; Hernán ve solo "Guardar". |
| F-19 Documentos y contactos | ✅ | — | Certificado vacío (3 errores claros), certificado con PDF + link, otro documento sin fecha con link sin https; descarga con su nombre. |
| F-20 Editar | ✅ | — | 5 campos a la vez, vuelve a la ficha con "Guardamos los cambios de la obra.". |
| F-21 Cambiar estado | ⚠️ | PER-04 | Hernán (responsable) hasta Terminada; Hernán sin botón en obra ajena; Laura en la suya; Diego: el salto Confirmada→Terminada viene desactivado. |
| F-22 Terminadas | ✅ | — | Pestaña Terminadas, buscable, ficha completa y editable. |
| F-23 Fechas previstas | ✅ | — | Alta 01/12/2026 → 28/02/2027, edición del fin, historial legible. |
| F-48 Presupuesto por rubro | ✅ (ver VA-02) | — | Diego: punto decimal, negativo, carga, sin cambios. Laura (responsable) solo lectura; Sofía/Pablo y Laura en obra ajena: "No sos responsable de esta obra"; Carla y Diego editan; Dora solo lectura. |
| F-55 Proveedores con buscador | ⚠️ | PER-06 | Listado: CUIT 4/6 dígitos, con guiones, sin tilde, rubro no; "No encontramos proveedores". |
| F-56 Alta y edición de proveedor | ⚠️ | PER-05 | Vacío, CUIT corto/inválido, mail inválido, repetido sin tildes ni puntos ("Ya está cargado…" en vivo, "Ya existe…" al guardar), CUIT repetido, alta sin CUIT, edición, sin cambios. |
| F-58 Usuarios | ✅ | — | Dora: alta (vacío, mail repetido con mayúsculas y espacio → "Ese mail ya es de otro usuario (Carla)."), edición de sector y teléfono, baja con aviso de "1 persona a cargo", ver inactivos, reactivar, no desactivarse a sí misma. Diego en Pixel 7 e iPhone SE con tarjetas. |
| F-59 Sectores | ⚠️ | PER-03 | Alta con permisos iniciales RN-IDE-30, renombrar, sacar con usuario. |
| F-60 Tabla de permisos | ⚠️ | PER-07 | Solo la fila QA-PER: "2 cambios sin guardar", guardar, F5, Descartar, botón desactivado sin cambios. |
| F-61 Jerarquía | ✅ | — | Círculo indirecto → "Así se arma un círculo: QA-PER Valeria Taller ya depende de QA-PER Ulises Taller."; el propio usuario no aparece en su desplegable; Mi perfil muestra el jefe. |
| F-68 Plazos | ⚠️ | PER-02 | Dora (Ver) sin campos ni botón; Diego (Editar) mira 1 / 7 y lo deja igual. |

## No probado
- Ingreso con código, límites y bloqueo (F-01 completo): no se llama a `h.ingresar` (brief); lo cubre el Lead.
- Dos pestañas / conflicto 409, aviso de cambios sin guardar, borradores recuperados: los cubre el cazador de regresiones.
- Alcance "solo sus obras" en Obras con un sector propio: solo dejé "Presupuesto por rubro = Ver · sus obras" en QA-PER Taller Norte; no tengo sesión de un usuario de ese sector (haría falta un código).
- Celular de obras más allá del desplazamiento horizontal en iPhone SE (6 pantallas sin desborde): lo cubre UXM.
- Mi perfil con cambios reales: para probar el teléfono, Dora y Diego quedaron unos segundos con teléfono "abc"/"xyz" (texto libre, se guarda); los volví a dejar vacíos como estaban. Nombre no se tocó.
