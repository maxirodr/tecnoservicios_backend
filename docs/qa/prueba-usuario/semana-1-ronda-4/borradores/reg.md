# Borrador REG · Cazador de regresiones (rol 5) · Ronda 4 retomada

- **Fecha:** 2026-11-19, 09:30–10:40 (hora de Argentina; servidor en UTC +3 h). Rama `staging`: back `56e5d8f` (rama `qa/prueba-usuario-semana-1-ronda-4`, mismo código), front **`380ddaf`** (B-01, A-01, A-02).
- **Corte ajeno:** MySQL se cayó solo entre ~09:50 y 10:06 (aviso del Lead). Lo corrido en ese rato (permisos de Hernán: `GET /api/me` → 503) se descartó y se repitió con `/api/health` en 200.
- **Personas / sesiones:** Diego, Dora, Hernán, Laura, Sofía, Carla (`sesiones\*.json`, solo lectura de sus sesiones: ninguna clave del seeder se tocó en Redis). `qa-lead-r4reg` (uid 10) y `qa-lead-r4reg2` (uid 11) para la sesión vencida: `DEL` solo de **su** clave (el script verifica el uid antes de borrar), sin pedidos en vuelo, `EXISTS` → 0.
- **Ingresos reales gastados: 5 de 5** (r4reg ×3, r4reg2 ×2). Además 1 pedido de código de r4reg rechazado con 429 por el límite de 3 por mail en 10 min (no se emitió código, ninguno quemado). Un ingreso de r4reg se perdió por un error de mi script (formulario con contactos vacíos: no salió el pedido).
- **Datos creados (prefijo QA4R):** obras 4 (4-401-2026 "QA4R Obra completa Añelo", responsable Hernán; adicional 4-401-2026 A1 REV02; documento "QA4R Certificado Ñ 1") y 5 (4-402-2026 "QA4R Obra de Laura Ñandú", responsable Laura); proveedor 2 "QA4R Ferretería Ñandú 232 SRL" (CUIT 30-71923210-4). La obra 5 quedó con cliente/planta cambiados por las pruebas de sesión. Sofía: teléfono puesto y devuelto a vacío (`NULL`). Plazos: 2/10 y devueltos a 1/7.
- **Scripts:** `C:\Users\GREEN\AppData\Local\Temp\claude\4fe530b1c11d8e8d\f4efcdda-aa94-4434-aafd-2e4822d4d74b\scratchpad\r4\REG\` (`01`…`17`, salidas `out-*.txt`, `ingresos.txt`).

## Tabla de lo recorrido

| área | estado | pantallas / personas | evidencia | nota |
|---|---|---|---|---|
| Alta de obra con adjuntos | ✅ | W-06 Nueva obra (Diego) | `REG-ok-r4b-alta-obra-antes.png`, `REG-ok-r4b-alta-obra-ficha.png` | Oferta: link con ñ/espacios → quitar → archivo "oferta año 2026.pdf"; OC: archivo → quitar → link "oc ñandú 01.pdf". Doble clic en "Guardar obra" → un solo `POST /api/obras` 201, "Obra creada. Ya se le pueden cargar pedidos.". Fechas "01/12/2026 → 31/03/2027". Sin errores de consola. |
| Edición de obra con adjuntos | ✅ | W-23 Editar obra (Diego) | `REG-ok-r4b-editar-obra-antes.png` | Link `https://drive.example/carpeta/Plano%20nave%20a%C3%B1o.pdf` se ve "Plano nave año.pdf". Doble clic → un solo `PATCH /api/obras/4` 200, "Guardamos los cambios de la obra.". |
| Historial (formato y hora de Argentina, B-01) | ✅ | Historial de cambios obra 4, navegador en Argentina, **UTC** y **Asia/Tokyo** | `REG-ok-r4b-historial-obra.png`, `REG-ok-r4b-fechas-tokyo.png` | `dd/mm/aaaa HH:MM` y la misma hora en los tres husos: cambio guardado a las 13:23 UTC → "19/11/2026 10:23". Textos sin comillas: "Oferta técnica: agregado (Plano nave año.pdf)". |
| Adicional | ✅ | W-21 (Diego) | `REG-ok-r4b-adicional-rubro.png` | Link → quitar → archivo; OC: archivo → quitar → link "mail aprobación ñ.eml". Doble clic → un solo `POST /api/obras/4/presupuestos` 201 y va al presupuesto por rubro. |
| Documento (certificado) | ✅ | W-20 (Diego) | `REG-ok-r4b-documento.png` | 2 archivos + 1 link, quitar uno; doble clic → un solo `POST /api/obras/4/documentos`, "Agregamos el documento a la obra.", fecha "15/11/2026". REV01–REV99 lo verificó B (O-4); acá solo REV02 en el adicional, sin problema. |
| Presupuesto por rubro | ✅ | Diego + Carla | `REG-ok-r4b-rubro-modo-dialogo.png`, `-rubro-guardado.png`, `-rubro-409.png`, `-rubro-salida.png` | Carga "1.234.567,89", total por fila y de obra; "Solo el total" con diálogo "Cargar solo el total…"; doble clic → un `PUT` por presupuesto. Conflicto: Carla guarda LOGISTICA, Diego guarda → 409 "Alguien cambió esto mientras lo tenías abierto. Actualizá y revisá." + "Actualizar" → trae 77.000. Salir tras "Actualizar" no pregunta; con cambios pregunta "Tenés cambios sin guardar. ¿Salir igual?". |
| Cambio de estado | ✅ | Diego (E), Hernán (responsable obra 4), Laura (responsable obra 5), Carla/Dora/Laura en obra ajena | `REG-ok-r4b-estado-terminada.png`, `-estado-terminada-ficha.png`, `-estado-reabrir.png`, `-estado-responsable-hernan.png`, `-estado-responsable-laura.png` | Confirmada → En ejecución → Terminada → En ejecución, de a un paso (Confirmada deshabilitada desde Terminada). Doble clic → un solo `POST …/estado`. Volver de Terminada limpia `terminada_at`/`terminada_por`. Pestañas "En ejecución (0) · Confirmadas (4) · Terminadas (1)". Sin botón para quien no es responsable ni tiene Editar. |
| Alta y edición de proveedor | ✅ | W-38 (Diego) | `REG-ok-r4b-alta-prov.png`, `REG-ok-r4b-editar-prov.png` | Doble clic → un solo `POST`/`PATCH`; "Dimos de alta a …", "Guardamos los cambios de … SRL.". El proveedor no tiene adjuntos (no hay campo). |
| Editar proveedor + Atrás / Escape mientras guarda (A-02) | ✅ | Diego, red lenta 4 s, 2 veces + Escape | `REG-ok-r4b-prov-atras-guardando.png`, `REG-ok-r4b-prov-escape-guardando.png` | Atrás: "Estamos guardando. Cuando termine te llevamos." → termina, cierra y queda en `/proveedores` con "Guardamos los cambios de QA4R Ferretería Ñandú 232 SRL.". Escape: "Estamos guardando. Esperá unos segundos." y no cierra. Un solo `PATCH`. |
| Plazos de la prioridad | ⚠️ | Diego (E), Dora (V), resto sin acceso | `REG-ok-r4b-plazos-validacion.png`, `REG-ok-r4b-plazos-guardado.png`, `REG-01-1/2.png` | Validaciones ("Prioritario tiene que ser mayor que Urgente.", "No puede ser negativo.", "Completá un número entero de días."), pregunta al irse con cambios, doble clic → un `PUT`, "Guardamos los plazos. Valen para los pedidos que se carguen desde ahora.", después de guardar no pregunta. Pero "No había cambios para guardar." queda puesto mientras se edita → **REG-01**. |
| Mi perfil | ✅ | Sofía (guardar y restaurar), Laura (sin cambios + nombre vacío), los 5 sectores entran | `REG-ok-r4b-perfil-guardado.png` | Pregunta al irse con cambios; doble clic → un `PATCH /api/me`, "Guardamos tus datos."; "No había cambios para guardar." se va al editar (PU-127 sigue bien acá). Teléfono de Sofía devuelto a vacío. |
| Doble clic en cada "Guardar…" principal | ✅ | Todas las de esta tabla | salidas `out-*.txt` | Un solo pedido en: obra alta/edición, adicional, documento, estado, rubro, proveedor alta/edición, Plazos, Mi perfil, recuperado tras sesión. No probado en "Guardar cambios" de la Tabla de permisos (ver no probado). |
| Buscador general | ⚠️ | Diego, Laura, Sofía | `REG-ok-r4b-buscador-nandu.png`, `REG-ok-r4b-buscador-cuit.png`, `REG-02-1/2.png` | "nandu" y "Ñandú" encuentran las 2 obras y el proveedor; "añelo" por planta; "4-401" y "401" por N.º; CUIT "3071", "30-7192", "719232" encuentran; "307" (3 dígitos) no; "zzzzqq" → "Sin resultados.". Laura/Sofía no ven el grupo Proveedores, pero la pista lo promete → **REG-02**. |
| Permisos por sector | ✅ | Dora, Diego, Hernán, Laura, Carla × 16 rutas | `out-10a.txt`, `out-10b.txt`, `out-10c.txt`, `REG-ok-r4b-permisos-dora-_obras_nueva.png`, `REG-ok-r4b-permisos-dora-_proveedores_nuevo.png` | Coincide con `permisos` del seeder y con identidad.md §6.3/§6.4 (detalle abajo). Lo que no puede no aparece o dice "Solo podés ver esta sección / Tu sector puede ver las obras pero no darlas de alta. Si lo necesitás, pedíselo a gerencia.", "No tenés acceso a esta sección / Tu sector no tiene permiso para ver Proveedores…", "No sos responsable de esta obra / Ves el presupuesto por rubro solo de las obras donde sos responsable.". Ningún 403 crudo, ningún 4xx/consola. |
| Sesión vencida → ingreso real → borrador recuperado (Nueva obra) | ✅ | r4reg, ingreso real | `REG-ok-r4b-sesion-A-401.png`, `REG-ok-r4b-sesion-A-recuperado.png` | `DEL` de su clave → "Guardar obra" → 419 → 401 → `/ingresar` con "Tu sesión venció. Volvé a ingresar.". Ingreso real → vuelve a `/obras/nueva` con "Recuperamos lo que estabas escribiendo." y nombre, cliente, N.º y el link "Plano nave año.pdf". Al salir sin guardar y volver a Nueva obra: vacío, sin aviso (no reaparece). |
| Sesión vencida → borrador **descartado** (otro cambió el registro) | ❌ | r4reg2 en Editar obra 5 con Diego cambiando; repetido 2 veces simulando el 401 | `REG-03-1.png`, `REG-03-2.png`, `REG-03-3.png`, `REG-03-*-guardar.png`, `logs/REG-03.log` | Esperado "Mientras no estabas, alguien cambió esto. Lo que habías escrito no se pudo recuperar."; se ve "Recuperamos lo que estabas escribiendo." con los datos viejos → **REG-03**. En Editar proveedor no se llegó a probar (429 del límite por mail). |
| Mensaje de límite de códigos | ✅ | r4reg | `REG-ok-r4b-ingreso-429.png` | "Ya pediste varios códigos. Probá de nuevo en 8 minutos." (sin 429 a la vista). |
| Textos / consola / red | ✅ | todas | salidas | Sin `undefined`, `null`, NaN, inglés ni códigos internos. Consola: solo "Failed to load resource" de los 401/409/419/429 provocados. Ningún 5xx fuera del corte de MySQL. Sin loader de página completa por acciones parciales. |

### Permisos: lo que vio cada sector (con el seeder)
| sector · persona | menú | Obras | Estado | Presupuesto por rubro | Proveedores | Usuarios · Sectores · Permisos · Plazos |
|---|---|---|---|---|---|---|
| Dirección · Dora | Inicio · Obras · Proveedores · Usuarios | ve; sin "+ Nueva obra"/Editar; `/obras/nueva` → "Solo podés ver…"; `/editar` → "Historial de la obra" | sin botón (V) | ve, sin campos (V·T) | ve; `/proveedores/nuevo` → "Solo podés ver…" | edita Usuarios, Sectores y Tabla (E); Plazos solo lectura (V) |
| Gerencia Adm. · Diego | ídem | todo | cambia | edita (17 campos, incluye "Monto del presupuesto") | todo | todo |
| Jefatura · Hernán | Inicio · Obras · Proveedores | alta y edición | solo su obra (4) | ve, sin campos | ve; alta → "Solo podés ver…" | "No tenés acceso a esta sección" en las 4 |
| Ingeniería · Laura | Inicio · Obras | alta y edición | solo su obra (5) | solo su obra; obra 4 → "No sos responsable de esta obra" (sin botón en la ficha) | "No tenés acceso…" en lista, alta y ficha | "No tenés acceso…" en las 4 |
| Compras · Carla | Inicio · Obras · Proveedores | ve; `/obras/nueva` → "Solo podés ver…" | sin botón | edita (15 campos: sin "Monto del presupuesto", monto = sin acceso) | todo | "No tenés acceso…" en las 4 |

## Hallazgos nuevos

### REG-01 · Plazos: "No había cambios para guardar." sigue a la vista mientras se edita y aparecen errores
- **Severidad:** baja
- **Tipo:** mensaje
- **F-XX / pantalla:** F-68 · W-50 Plazos de la prioridad · `/usuarios/plazos`
- **Persona:** Diego (Gerencia de Administración)
- **Pasos:** 1. Usuarios → Plazos de la prioridad. 2. "Guardar" sin tocar nada. 3. Escribir 7 en "Urgente hasta". 4. "Guardar".
- **Esperado:** el aviso se va apenas se cambia algo (como en Mi perfil desde PU-127); queda solo "Prioritario tiene que ser mayor que Urgente.".
- **Obtenido:** arriba sigue "No había cambios para guardar." después de escribir y junto al error "Prioritario tiene que ser mayor que Urgente.": la pantalla dice que no hay cambios mientras marca uno. Reproducido 2 veces. En Mi perfil (Laura) el aviso sí se va.
- **Evidencia:** `capturas/REG-01-1.png` · `capturas/REG-01-2.png` · script `15-sincambios.mjs` (sin `PUT`)
- **Causa probable (opcional):** `PlazosPrioridadPantalla.tsx` no limpia el mensaje de "sin cambios" al editar (el arreglo de PU-127 quedó solo en `PerfilPantalla.tsx`).
- **Regresión de:** no; arreglo incompleto de PU-127 (la pantalla de Plazos sumó el aviso con PU-123)

### REG-02 · Buscador: la pista ofrece buscar proveedores a quien no puede verlos
- **Severidad:** baja
- **Tipo:** mensaje
- **F-XX / pantalla:** F-03 · W-18 Buscador general · `/buscar`
- **Persona:** Laura y Sofía (Ingeniería de proyectos, Proveedores = sin acceso)
- **Pasos:** 1. Ingresar como Laura. 2. Tocar "Buscar". 3. Escribir "3071" (CUIT de un proveedor existente).
- **Esperado:** la pista habla solo de lo que la persona puede encontrar ("Por N.º de presupuesto, cliente, planta o nombre del trabajo."), o el buscador explica que proveedores no están en su búsqueda.
- **Obtenido:** "Por N.º de presupuesto, cliente, planta, nombre del trabajo o proveedor (nombre o CUIT)." y debajo solo "OBRAS (0) · Sin resultados.": parece que el proveedor no existe. Reproducido 2 veces (Laura y Sofía). El grupo Proveedores se oculta bien.
- **Evidencia:** `capturas/REG-02-1.png` · `capturas/REG-02-2.png`
- **Causa probable (opcional):** `src/features/buscador/BuscadorPantalla.tsx:93` (`hint` fijo, sin mirar `permisos.proveedores`), desde `75bc80e`.
- **Regresión de:** no; arreglo incompleto de PU-044 (la pista con CUIT de `75bc80e`)

### REG-03 · Sesión vencida: si otro cambió la obra, al volver dice "Recuperamos…" y muestra los datos viejos
- **Severidad:** media
- **Tipo:** funcional
- **F-XX / pantalla:** F-20 · W-23 Editar obra · `/obras/{id}/editar`
- **Persona:** qa-lead-r4reg2 (Ingeniería de proyectos), con Diego cambiando la obra; repetido con Diego y Hernán
- **Pasos:** 1. Editar obra 4-402-2026 y cambiar la planta a "Planta Añelo r4reg2". 2. Vence la sesión (`DEL` de su clave). 3. Mientras tanto Diego cambia la planta de la misma obra a "Planta Añelo Diego" y guarda (200). 4. "Guardar cambios" → "Tu sesión venció. Volvé a ingresar.". 5. Ingresar con el código del mail.
- **Esperado:** vuelve a Editar obra con "Mientras no estabas, alguien cambió esto. Lo que habías escrito no se pudo recuperar." y los datos actuales (PU-109).
- **Obtenido:** "Recuperamos lo que estabas escribiendo." con "Planta Añelo r4reg2" sobre la versión vieja; los datos de Diego no se ven. Al guardar: 409 "Alguien cambió esto mientras lo tenías abierto. Actualizá y revisá." (no se pisa nada, pero el aviso de recuperado y el conflicto se contradicen). Ingreso real 1 vez; reproducido 2 veces más simulando solo el 401 en el navegador de Diego (Hernán cambia el cliente; al volver, el cliente en pantalla es el anterior y "Guardar cambios" → 409).
- **Evidencia:** `capturas/REG-03-1.png` (ingreso real) · `capturas/REG-03-2.png`, `REG-03-3.png` · `REG-03-2-guardar.png`, `REG-03-3-guardar.png` · `logs/REG-03.log` · `PATCH /api/obras/5` → 419, 401; tras volver `PATCH /api/obras/5` → 409
- **Causa probable (opcional):** al ingresar solo se guarda la sesión (`useIngresar` → `useGuardarSesion`); la ficha queda en la caché de React Query con el `updated_at` de antes y `EditarObraPantalla.tsx:93` monta el formulario con esa `version`, así `useBorrador` la compara con la vieja y no con la actual.
- **Regresión de:** no; arreglo incompleto de PU-109

## Decisiones aprobadas que me tocaron
| decisión | estado | dónde se probó | evidencia | nota |
|---|---|---|---|---|
| Fechas `dd/mm/aaaa` y `dd/mm/aaaa HH:MM` (y B-01) | ✅ | ficha, historial, documentos, "Cargado"; navegador en Argentina, UTC y Tokio | `REG-ok-r4b-historial-obra.png`, `REG-ok-r4b-fechas-tokyo.png` | Misma hora de Argentina en los tres husos. |
| O-1.1 (irse mientras guarda) | ✅ | Editar proveedor + Atrás (A-02) | `REG-ok-r4b-prov-atras-guardando.png` | Lleva a `/proveedores` al terminar. A-01 (Tabla de permisos) no lo probé: es del Verificador A. |
| O-1.2 (diálogo guardando) | ✅ | Editar proveedor + Escape | `REG-ok-r4b-prov-escape-guardando.png` | |
| Sesión vencida "Tu sesión venció. Volvé a ingresar." | ✅ | Nueva obra, Editar proveedor, Editar obra (401 real) | `REG-ok-r4b-sesion-A-401.png`, `-B-401.png`, `-D-401.png` | |
| Nombre de los links | ✅ | ficha, edición, adicional, documento, historial | capturas de arriba | "Plano nave año.pdf" desde `…/Plano%20nave%20a%C3%B1o.pdf`. |

## Lo que no pude probar y por qué
- **Borrador recuperado en el alta de proveedor y descartado en Editar proveedor por ingreso real:** el segundo ingreso de r4reg chocó con el límite de 3 códigos por mail en 10 min (429, "Ya pediste varios códigos. Probá de nuevo en 8 minutos.") y el tope de 5 ingresos quedó agotado con r4reg2 (Editar obra). La parte previa sí se vio: `PATCH /api/proveedores/2` → 401, "Tu sesión venció…" y borrador `proveedor:2` guardado (`REG-ok-r4b-sesion-B-401.png`). Por el código, REG-03 debería pasar igual en Editar proveedor (misma caché).
- **Doble clic y salida en "Guardar cambios" de la Tabla de permisos (A-01):** cambia la configuración compartida que estaba usando el Verificador A; quedó para él.
- **Permisos de Sofía y Pablo** por separado: mismo sector que Laura (Ingeniería); Sofía solo en el buscador y Mi perfil.

**Firma:** REG · ronda 4 retomada · cubrí alta/edición de obra y proveedor con adjuntos, adicional, documento, rubro con conflicto, estado, Plazos, Mi perfil, doble clic, buscador, permisos de los 5 sectores, B-01 y A-02 alrededor, y sesión vencida con ingreso real (recuperado ✅, descartado ❌ REG-03) · 5 ingresos reales.
