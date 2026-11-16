# Borrador · Tester funcional de Obras (OBR)

Personas: Hernán (Jefatura de Proyectos), Laura (Ingeniería de proyectos); para comparar, Diego
(Gerencia de Administración), Carla (Compras), Pablo y Sofía (Ingeniería). Datos creados (ficticios):

| id | N.º | Trabajo | Responsable | Cómo se creó | Estado final |
|---|---|---|---|---|---|
| 1 | 2-701-2026 | QA-OBR Piso industrial nave 7 | Laura | W-06 por pantalla (Hernán) | Confirmada, REV02, solo el total 25.650.000 |
| 6 | 2-702-2026 | QA-OBR Galpón de Pablo | Pablo | API (mismo payload que W-06) | Terminada |
| 7 | 2-703-2026 | QA-OBR Obra de Hernán | Hernán | API | En ejecución |
| 9 | 2-704-2026 | QA-OBR Obra para terminar | Laura | API | Confirmada, con certificado |

Nota de entorno: la prueba se cortó entre las 14:50 y las 16:05 UTC (Redis caído, sesiones
regeneradas por el Lead). No se reporta acá: lo documenta el Lead. Con MySQL cargado por otra
suite algunos pedidos tardaron varios segundos; eso solo se usa en OBR-01 porque ahí la demora
**pierde un dato**.

---

### OBR-01 · El link pegado se pierde si se guarda mientras se está agregando
- **Severidad:** bloqueante
- **Tipo:** funcional
- **F-XX / pantalla:** F-19 · W-20 "Agregar certificado o documento" · `/obras/9` (por el código, lo mismo en W-06 Alta, W-23 Editar y W-21 Adicional: todos usan el mismo campo "Archivo o link")
- **Persona:** Laura (Ingeniería de proyectos)
- **Pasos:** 1. En la ficha de su obra, "+ agregar certificado o documento". 2. Fecha, nombre "QA-OBR Certificado N.º 2 (carrera)" y subir un PDF (mientras sube, "Guardar" está deshabilitado: bien). 3. "Pegar link", pegar `https://drive.google.com/file/d/qa-obr-cert2-firmado` y tocar "Agregar link". 4. Mientras el link se registra (con el servidor lento tarda unos segundos), tocar "Guardar".
- **Esperado:** "Guardar" deshabilitado hasta que el link termine de agregarse (como con los archivos: "Esperá a que terminen de subir los archivos para guardar."), o que se guarde con el link. ADR-014: cada documento tiene sus archivos o links.
- **Obtenido:** "Guardar" queda habilitado mientras el link está en curso. Se guarda el documento solo con el PDF y aparece "Agregamos el documento a la obra." El link no queda en ningún lado, sin ningún aviso. El link registrado queda como adjunto huérfano. Red: `POST /api/adjuntos` (link) sale 16:14:08, `POST /api/obras/9/documentos` con `"adjuntos":[42]` (solo el PDF) sale 16:14:09 → 201, y la respuesta del link llega 16:14:17. Pasó también **sin forzar nada** en la primera corrida (16:05, servidor lento): el "Certificado N.º 1" se guardó sin el link "Certificado firmado (Drive)" que se había pegado.
- **Evidencia:** `capturas/OBR-13-guardar-habilitado-con-link-en-curso-pc.png`, `capturas/OBR-13-documento-guardado-sin-link-pc.png` · script `obr/s7d-carrera.cjs` (demora de 8 s en `POST /api/adjuntos` para reproducirlo siempre).
- **Causa probable (opcional):** `src/shared/adjuntos/AdjuntosField.tsx`: `subiendo = pendientes.some(...)` cuenta solo los archivos. El `enviando` de `PegarLink.tsx` no llega a `onSubiendoChange`, así que el formulario padre no se entera de que hay un link en curso.

### OBR-02 · Si dos personas editan la misma obra, la segunda borra sin aviso lo que guardó la primera
- **Severidad:** alta
- **Tipo:** funcional
- **F-XX / pantalla:** F-20 · W-23 Editar obra · `/obras/9/editar`
- **Persona:** Hernán (Jefatura de Proyectos) y Laura (Ingeniería de proyectos)
- **Pasos:** 1. Hernán y Laura abren "Editar" de la obra 2-704-2026 al mismo tiempo. 2. Hernán cambia el teléfono del solicitante a "291 555-0001" y guarda. 3. Laura, sin recargar, cambia la planta a "QA-OBR Muelle 4" y guarda.
- **Esperado:** quedan los dos cambios, o Laura recibe un aviso claro ("Hernán cambió esta obra mientras la editabas; revisá antes de guardar"). §4 de la prueba: "dos pestañas editando lo mismo".
- **Obtenido:** Laura ve "Guardamos los cambios de la obra." El teléfono vuelve a "291 000-0000" sin que nadie lo pida. El historial se lo atribuye a Laura: "Contacto solicitante: teléfono: 291 555-0001 → 291 000-0000". Reproducido también con una sola persona en dos pestañas (obra 1): la pestaña B deshizo "Trabajo: … → … (pestaña A)".
- **Evidencia:** `capturas/OBR-14-dos-personas-editando-pc.png` · scripts `obr/s8.cjs` y `obr/s5-editar.cjs` · `PATCH /api/obras/9` → 200 las dos veces.
- **Causa probable (opcional):** el formulario manda todos los campos (`datosObraPayload`), no solo los que cambió la persona, y el backend no controla versión (`updated_at`). Opciones: mandar solo los campos tocados, y/o devolver 409 si la obra cambió desde que se abrió.

### OBR-03 · Abrir una dirección de la API sin sesión da "El sistema tuvo un problema" (500) en vez de pedir ingresar
- **Severidad:** media
- **Tipo:** error-servidor
- **F-XX / pantalla:** F-19 · descarga de adjuntos `GET /api/adjuntos/{id}` (pasa con cualquier `/api/...`, p. ej. `/api/obras/1`)
- **Persona:** cualquiera (probado sin sesión y con la sesión de Laura abriendo la dirección directo en una pestaña)
- **Pasos:** 1. Abrir en el navegador `http://localhost:8340/api/adjuntos/2` (o `/api/adjuntos/999999`, `/api/obras/1`) sin el encabezado JSON que manda el sistema (por ejemplo, alguien que copió el link de descarga o con la sesión vencida).
- **Esperado:** 401 "Tenés que ingresar para seguir." (es lo que responde el mismo pedido con `Accept: application/json`) o 404 entendible.
- **Obtenido:** 500 `{"message":"El sistema tuvo un problema. Volvé a intentar en un momento."}` y una línea en el canal `errors` por cada intento: "Route [login] not defined." (`RouteNotFoundException`, acción `AdjuntoController@show`). Reproducido 3 veces con curl.
- **Evidencia:** `storage/logs/errors-2026-11-16.log` 16:15:04 · `curl http://localhost:8340/api/obras/1` → 500 · con `-H "Accept: application/json"` → 401.
- **Causa probable (opcional):** el middleware `auth` redirige a `route('login')` cuando el pedido no es JSON y esa ruta no existe en una API. Falta forzar JSON en `/api/*` (o definir `redirectGuestsTo`). Es transversal: el Lead puede unirlo con otro hallazgo igual.

### OBR-04 · El historial muestra las fechas previstas en formato técnico
- **Severidad:** baja
- **Tipo:** mensaje
- **F-XX / pantalla:** F-14 / F-23 · W-23 Historial de cambios · `/obras/1/editar`
- **Persona:** Hernán
- **Pasos:** 1. Editar la obra y cambiar "Fin previsto" de 31/03/2027 a 30/04/2027. 2. Mirar el historial abajo.
- **Esperado:** el mismo formato que la ficha ("20/12/2026 → 30/04/2027"), como se arregló con los importes en B-05.
- **Obtenido:** "Fin previsto: 2027-03-31 → 2027-04-30".
- **Evidencia:** `capturas/F-14-historial-obra-pc.png` · `GET /api/obras/1/historial`.

### OBR-05 · "Tu sector no tiene permiso para ver esto" cuando el sector sí puede ver
- **Severidad:** baja
- **Tipo:** mensaje
- **F-XX / pantalla:** F-16 `/obras/nueva` (Carla) · F-48 `/obras/1/presupuesto-por-rubro` (Pablo)
- **Persona:** Carla (Compras, Obras = Ver) y Pablo (Ingeniería, Presupuesto por rubro = Ver · solo sus obras)
- **Pasos:** 1. Carla abre `/obras/nueva` escribiendo la dirección. 2. Pablo abre el presupuesto por rubro de una obra de Laura escribiendo la dirección.
- **Esperado:** decir lo que pasa: "Tu sector puede ver las obras pero no darlas de alta." / "Ves el presupuesto por rubro solo de las obras donde sos responsable."
- **Obtenido:** "No tenés acceso a esta sección · Obras: tu sector no tiene permiso para ver esto. Si lo necesitás, pedíselo a gerencia." y "Presupuesto por rubro: tu sector no tiene permiso para ver esto. …". Carla sí ve las obras y Pablo sí ve el presupuesto por rubro de sus obras. El bloqueo es correcto (la API da 403); el texto confunde.
- **Evidencia:** `obr/s6-listado.cjs` (CARLA /obras/nueva), `obr/s8.cjs` (PABLO W-13 ajena).

### OBR-06 · Revisión con texto largo: el mensaje no dice cómo escribirla
- **Severidad:** baja
- **Tipo:** mensaje
- **F-XX / pantalla:** F-20 · W-23 · campo "Revisión"
- **Persona:** Hernán
- **Pasos:** 1. Editar la obra y escribir "versión dos" en Revisión. 2. Guardar cambios.
- **Esperado:** "Escribí la revisión como REV01." (RN-OBR-14; es lo que sale con "rev dos").
- **Obtenido:** "La revisión puede tener hasta 10 caracteres." Una persona acorta el texto y vuelve a fallar. Reproducido dos veces (obras 1 y 9).
- **Evidencia:** `capturas/OBR-15-revision-mensaje-pc.png`.

---

### Observaciones (no son bug)

- **OBS-1 · Responsables posibles:** el filtro "Responsable" de W-05 y el desplegable de W-06/W-23 listan a todos los usuarios activos: Carla (Compras), Diego (Gerencia de Administración), Dora (Dirección) y los usuarios de prueba de otros testers. Coincide con la spec (`obras.md` §4.1 "cualquier usuario activo"). Para producto: con unas 50 personas el filtro va a ser largo y elegir a alguien sin obras da "No encontramos obras con esa búsqueda". Convendría filtrar solo por quienes tienen obras y, en el alta, mostrar primero a operaciones.
- **OBS-2 · "Documento de aprobación" sin opción vacía** (arranca en "Orden de compra" aunque diga "(opcional: se puede cargar después)"). Coincide con RN-OBR-16 y si no se carga número ni archivo no se guarda nada.
- **OBS-3 · "Solo sus obras" en Obras:** la configuración inicial le da a Ingeniería Obras = Editar · todas (`identidad.md` §6.3, `GET /api/me`). Por eso Laura y Pablo ven y editan obras ajenas y bajan sus adjuntos (200): es lo correcto. El alcance "solo sus obras" sí se verificó en Presupuesto por rubro: Pablo no ve el botón en la obra de Laura, la API da 403 y el historial no le muestra las líneas de montos.
- **OBS-4 · Guardar sin cambios en Editar** dice "Guardamos los cambios de la obra." (no agrega líneas al historial, bien). En W-13 el mismo caso dice "No hay cambios para guardar.". Conviene usar el mismo texto en los dos.
- **OBS-5 · N.º repetido** muestra el mismo aviso dos veces: arriba "Ese N.º de presupuesto ya está en QA-OBR Piso industrial nave 7." (con link) y en el campo "El presupuesto 2-701-2026 ya está en la obra 'QA-OBR Piso industrial nave 7'.".
- **OBS-6 · Historial del alta de documentos desparejo:** el Certificado N.º 1 dejó "Agregó el certificado …" + "… · Archivo o link: agregado (certificado 1 QA-OBR.pdf)". El N.º 2 dejó solo "Agregó el certificado …" (al quitarlo sí nombra "certificado-2.pdf"). Revisar si la línea del adjunto se registra siempre.
- **OBS-7 · El diálogo de estado ofrece "Confirmada" en una obra Confirmada** (y "Terminada" en una Terminada) como opción habilitada. No pasa nada malo (la API responde "La obra ya está Terminada."), pero es una opción que no lleva a ningún lado.

## Cobertura

| F-XX | Estado | Hallazgos | Qué probé |
|---|---|---|---|
| F-15 | ✅ | OBS-1 | Pestañas "En ejecución (n) · Confirmadas (n) · Terminadas (n)" con contadores que cambian con la búsqueda y el filtro (API). Columnas de W-05 con "—" en pedidos abiertos y % gastado. Vacío: "No hay obras en esta pestaña" / "No encontramos obras con esa búsqueda. Probá con otra parte del N.º…". Orden de Confirmadas (más nueva primero). Al volver desde la ficha se conservan la pestaña y el texto buscado. Carla lo ve sin "+ Nueva obra". |
| F-03 | ✅ | — | Listado: "2701", "2 701 2026", "2-701", "qa-obr planta ejemplo", "NAVE 7", "galpon" (sin tilde, encuentra "Galpón" en Confirmadas y Terminadas), "puerto". Buscador general `/buscar`: Hernán y Carla ven Obras + Proveedores; Laura (sin Proveedores) solo el grupo Obras; las terminadas aparecen marcadas "Terminada". |
| F-16 | ✅ | OBS-2, OBR-01 (alta usa el mismo campo) | W-06 como Hernán: vacío → 11 errores al lado de cada campo en español. " 2 701 2026 " → "2-701-2026" REV01. Oferta PDF con acentos/ñ/espacios. Link sin `https://` completado. Fin antes del inicio → "La fecha de fin no puede ser anterior a la de inicio.". Doble clic en "Guardar obra" → una sola obra. Nace Confirmada. Carla en `/obras/nueva` → sin acceso (OBR-05). |
| F-17 | ✅ | — | Ficha como Hernán, Laura, Carla, Pablo y Diego: botones según quién mira (Carla "Historial de cambios" sin Editar; "Cambiar estado" solo para el responsable y Diego; "Presupuesto por rubro" según permiso). `/obras/999999`, `/obras/abc`, `/obras/999999/editar` y `/presupuesto-por-rubro` → "Obra no encontrada · Puede que el link esté mal o que no tengas acceso a esa obra. · Volver a Obras". `/obras/1/algo` → "No encontramos esta página". F5 conserva todo. |
| F-18 | ✅ | — | Adicional desde la ficha como Laura: N.º sugerido "2-704-2026 A1", solo botón "Guardar", "Guardamos el presupuesto.", la cabecera pasa a "Presupuestos (2)". Quitar adicional con confirmación clara. Diego ve "Guardar y cargar montos por rubro →". Monto de la obra: lo carga Diego; Hernán, Laura, Carla y Pablo no reciben la clave `monto_obra` en la ficha ni las líneas en el historial. |
| F-19 | ⚠️ | OBR-01, OBR-03, OBS-6 | Certificado sin datos → "Completá la fecha del certificado.", "Completá el nombre, por ejemplo Certificado N.º 1.", "Subí al menos un archivo o pegá un link.". `javascript:alert(1)` → "El link tiene que empezar con http:// o https://.". Archivo + link, link sin `https://`, cambiar archivo por link al editar, quitar documento ("Se borran el documento y sus archivos o links. Queda en el historial de la obra."). Descarga de la oferta con nombre original (`oferta-2-704-2026.pdf`; con ñ/acentos en `filename*`), `nosniff`. Adjunto inexistente → 404 por la API. Carla sin permiso de agregar documento → 403 "No tenés permiso para hacer esto.". Carpeta de Drive solo link. |
| F-20 | ⚠️ | OBR-02, OBR-06, OBS-4, OBS-5 | Formulario cargado, errores al lado del campo sin borrar lo escrito (mail "compras@", Drive `javascript:`, número "2-704-26" → "Escribí el año con 4 dígitos…", N.º repetido). Revisión "rev 2" → REV02, OC, mail del comprador, fin previsto, oferta archivo → link "Oferta REV02". Doble clic en "Guardar cambios": un solo cambio. F5 muestra lo guardado. Un 422 en el presupuesto no guarda a medias (probado con trabajo/cliente/mail inválidos + OC nueva: la OC no quedó). |
| F-21 | ✅ | OBS-7 | Hernán en SU obra: Confirmada → En ejecución → Terminada → En ejecución, con avisos "La obra pasó a …" y "Esta obra no tiene pedidos abiertos.". Opción que salta un paso deshabilitada. API: Terminada→Confirmada "De Terminada se vuelve primero a En ejecución.", Confirmada→Terminada "De Confirmada se pasa primero a En ejecución.", estado inventado → 422. Hernán y Pablo en obra ajena, y Carla → 403 "No tenés permiso para hacer esto.". Diego (Estado de obra = Editar) → 200. Pablo en SU obra → 200. |
| F-22 | ✅ | — | Obra Terminada (2-702-2026) en la pestaña Terminadas, buscable y en el buscador general marcada "Terminada". Hernán la edita (planta) → "Guardamos los cambios de la obra." (RN-OBR-25). |
| F-23 | ✅ | OBR-04 | Validación fin ≥ inicio en alta. Edición de fin previsto, se ve "20/12/2026 → 30/04/2027" en la ficha. |
| F-48 | ✅ | OBS-3 | Diego en W-13: 12 rubros, "10.900.000", "1.500.000,50" (coma decimal), negativo → "El monto no puede ser negativo.", "12,345" → "El monto puede tener hasta 2 decimales.", "Revisá los montos marcados.". Total 12.550.000,5. Guardar sin cambios → "No hay cambios para guardar." sin líneas nuevas. Pasar a "Solo el total" con confirmación "Se borran los montos por rubro…" y 25.650.000. F5 conserva. Hernán y Laura (Ver) sin botón Guardar; Carla (Editar) puede cargar. Pablo en obra ajena → sin acceso / 403. |
| F-14 | ✅ | OBR-04, OBS-6 | Quién, qué y cuándo, el más nuevo arriba, en palabras del cliente, sin URLs. **B-05 sigue arreglado**: "MATERIALES: (vacío) → 10.900.000", "INSUMOS: 1.500.000,5 → (vacío)", "Monto del presupuesto: (vacío) → 36.500.000". Guardar sin cambios (Editar y W-13) no agrega líneas (5 → 5). Las líneas de monto de la obra solo las ve Diego. |
| B-01 | ✅ sigue arreglado | — | Pixel 7 (412 px) e iPhone SE: ficha en tarjetas, sin desplazamiento horizontal, ningún botón fuera de pantalla; Editar sin desplazamiento. `capturas/F-17-ficha-obra-pixel7.png`. |
| B-02 | ✅ sigue arreglado | — | Sin "(W-xx)", F-, RN-, DU- ni HU- en listado, alta, ficha, editar, W-13 y buscador (PC y celular). |
| B-03 | ✅ sigue arreglado | — | Barra del celular "Obras · Más" (Hernán y Laura), sin campana ni "Avisos"/"Tareas". |
| B-05 | ✅ sigue arreglado | — | Ver F-14. |

## No probado

- **Alcance "solo sus obras" en la función Obras** (ver lista, ficha por URL y adjuntos de obras ajenas con 404): ningún sector del seeder lo tiene (OBS-3). Para probarlo hay que crear un sector propio, y Hernán y Laura no tienen permiso de Usuarios. Lo podría probar el rol de administración con un sector QA-.
- **Carga de pedidos en obra Terminada / pestañas Pedidos y Rendición:** son de las semanas 2 y 3 (correctamente ocultas).
- **Archivos de 20/21 MB, HEIC, tipos no permitidos:** los cubre el rol de entradas (ENT).
- **Loaders con red lenta:** no medidos aparte (lo cubre el rol de carga). Solo se vio el estado "subiendo…" del archivo y su botón Guardar deshabilitado (ver OBR-01).

Scripts: `C:\Users\GREEN\AppData\Local\Temp\claude\4fe530b1c11d8e8d\f4efcdda-aa94-4434-aafd-2e4822d4d74b\scratchpad\qa\obr\` (`s1-alta` … `s9`).
