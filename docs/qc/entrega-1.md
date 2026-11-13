# QC · Entrega 1 (Semana 1 · Base) · NS-2026-025

> **Rol:** QC y release manager · **Fecha:** 2026-11-13 · **Producto probado:** `tecnoservicios_backend`
> y `tecnoservicios_frontend` en `main` (front `7be54f2`), corriendo juntos contra la base descartable
> `tecnoservicios_integracion` (backend 8320 + front 5312), sin mocks.
> **Contra qué:** Documento del sistema firmado (cap. 3, 6, 10, 11, 13 y roadmap del cap. 14), maquetas
> W-XX, `docs/backlog.md` (HU-001…HU-025), ADR-014 y ADR-015, `dudas.md` (todas resueltas por Juani el
> 12-nov) y `qa/matriz-trazabilidad.md`.
> **Datos:** ficticios (personas de ejemplo `@empresa-ejemplo.com`, obras y proveedores de ejemplo,
> importes de prueba sin relación con el cliente).

## Veredicto

**Sí: apta para que el cliente cargue 2–3 obras reales y sus usuarios**, con una condición de
entorno y sin bloqueantes de producto.

- **Condición (antes de mandarles el link):** el entorno donde prueba el cliente tiene que cumplir
  el ítem de `seguridad.md` §6 sobre el tamaño de subida (`upload_max_filesize` y `post_max_size`
  ≥ 21 MB, nginx `client_max_body_size 21m`). En el entorno local de QC PHP corta en 8 MB (B-04): una
  oferta técnica o un certificado escaneado de más de 8 MB no se podría subir.
- **Antes de mandar las notas:** Juani tiene que avisarle al cliente los cambios de ADR-014 y
  ADR-015 (las notas de `docs/entregas/semana-1.md` quedan en BORRADOR por eso).
- **Para la semana 2 (no bloquean la prueba de esta semana, que es de carga en PC):** B-01 (la ficha
  de la obra en el celular), B-02 y B-03 (textos internos y entradas a secciones no entregadas) y B-05.

Resumen: **22 funciones de la entrega 1 → 18 ✅ · 4 ⚠️ · 0 ❌**. Suite de integración: **42/42 en
verde**. 5 bugs (0 bloqueantes · 1 media · 4 menores) y 3 observaciones.

## 1. Evidencia

### 1.1 Suite de integración real (`npm run e2e:integracion`)

Corrida completa el 13-nov sobre `main`: **42 passed (4,4 min)**, 33 en PC (1366 px) y 9 en
celular (Pixel 7, tag `@celular`). Exit 0 (salida en [`entrega-1/suite-integracion.log`](entrega-1/suite-integracion.log)). Los números `#n` de las secciones 2 y 3 son los de esta
corrida:

| # | Test (archivo › caso) |
|---|---|
| 1–4 | `barra.spec.ts` › Dora (Dirección), Carla (Compras), Laura (Ingeniería), Hernán (Jefatura de Proyectos) ven solo lo que habilita su sector |
| 5 | `barra.spec.ts` › Laura edita su perfil y la API le niega proveedores, usuarios y permisos (403) |
| 6 | `buscador.spec.ts` › Compras encuentra obras y proveedores y abre la ficha |
| 7 | `buscador.spec.ts` › Ingeniería no ve el grupo de proveedores |
| 8 | `ingreso.spec.ts` › entro con el código del mail, la sesión sigue al recargar y Salir la corta |
| 9 | `ingreso.spec.ts` › un código equivocado muestra el error y no entra |
| 10 | `ingreso.spec.ts` › un mail no registrado ve el mismo mensaje y no se manda ningún mail |
| 11 | `obras-alcance.spec.ts` › Laura ve el presupuesto por rubro de su obra sin poder cargarlo y no ve el de una ajena |
| 12 | `obras-alcance.spec.ts` › Compras ve las obras sin "Nueva obra" ni "Editar"; el backend niega el cambio; obra inexistente da 404 |
| 13 | `obras-alcance.spec.ts` › Hernán cambia el estado en la suya y no en la de Laura |
| 14 | `obras-alta.spec.ts` › alta con la oferta subida y la OC como link; doble clic no la duplica |
| 15 | `obras-alta.spec.ts` › el N.º repetido no se guarda y lleva a la obra que ya lo tiene |
| 16 | `obras-alta.spec.ts` › la encuentro en el listado, en su pestaña y en el buscador general |
| 17 | `obras-ficha.spec.ts` › agrego un presupuesto adicional con su oferta |
| 18 | `obras-ficha.spec.ts` › cargo el presupuesto por rubro y queda guardado |
| 19 | `obras-ficha.spec.ts` › agrego un certificado con archivo y link, lo bajo y después quito el link |
| 20 | `obras-ficha.spec.ts` › paso la obra a En ejecución y la encuentro en esa pestaña |
| 21 | `obras-ficha.spec.ts` › edito la obra y el historial muestra quién cambió qué |
| 22 | `obras-ficha.spec.ts` › un archivo que no es lo que dice su extensión lo rechaza el backend |
| 23 | `obras-ficha.spec.ts` › paso la obra a Terminada y queda consultable y editable en Terminadas |
| 24 | `permisos.spec.ts` › agrego un sector, le quito Obras y su gente deja de ver el módulo |
| 25 | `permisos.spec.ts` › gerencia cambia los plazos de la prioridad y quedan guardados |
| 26 | `permisos.spec.ts` › Dirección ve las pestañas de Usuarios pero los plazos no se editan |
| 27 | `proveedores.spec.ts` › doy de alta un proveedor; doble clic no lo duplica |
| 28 | `proveedores.spec.ts` › un nombre parecido muestra los ya cargados y el repetido lleva al existente |
| 29 | `proveedores.spec.ts` › edito el proveedor y el genérico no se puede editar |
| 30 | `proveedores.spec.ts` › Dora ve la lista y la ficha sin alta ni edición |
| 31 | `usuarios.spec.ts` › alta, edición y baja: el dado de baja pierde la sesión y no recibe más códigos |
| 32 | `usuarios.spec.ts` › nadie se desactiva a sí mismo |
| 33 | `usuarios.spec.ts` › doble clic en Guardar da de alta una sola persona |
| 34–42 | Celular: 1–4, 6, 8, 9, 16 y 30 repetidos en Pixel 7 |

### 1.2 Recorrido de QC (capturas y sondeos)

Script de capturas propio (fuera de los repos) que reusa el arranque de la suite: levanta los dos
servers, deja la base en cero, carga datos ficticios por la API como lo haría gerencia y recorre
cada pantalla como cada persona. 64 capturas en [`entrega-1/`](entrega-1/) (PC 1366 px y Pixel 7).
En **todas** las pantallas de celular se midió el ancho de la página: **ninguna se desplaza a lo
ancho**. Además se sondearon por la API 34 reglas de los criterios de aceptación (marcadas "sondeo" en la
columna "Evidencia" de las secciones 2 y 3; registro completo en
[`entrega-1/registro-capturas.log`](entrega-1/registro-capturas.log)).

Personas usadas: Diego (Gerencia de Administración), Laura (Ingeniería de proyectos, responsable de
2-131 y 2-140), Hernán (Jefatura de Proyectos), Carla (Compras), Dora (Dirección), Pablo y Sofía
(Ingeniería, para el ingreso).

## 2. Funciones de la entrega 1 (F-XX)

✅ cumple · ⚠️ cumple con una observación abierta (bug no bloqueante o diferencia sin aprobar) · ❌ no cumple.
"s1" = la parte de la semana 1 de una función que se completa después (backlog §1).

| F-XX | Función (documento firmado) | Estado | Evidencia | Nota |
|---|---|---|---|---|
| F-01 | Ingreso (cambiado por ADR-015: mail + código por mail) | ✅ | #8, #9, #10, #31, #39, #40 · `W-16-ingreso-*-pc/celular.png` | Dos pasos en la misma tarjeta; código equivocado → "El código no es válido o venció."; reenviar con espera visible; sesión sigue al recargar. Bloqueo progresivo y 429: tests del backend (no se fuerzan en QC para no ensuciar Redis). |
| F-02 | Menú según el sector | ⚠️ | #1–#5, #24, #34–#37 · `W-17-barra-*.png`, `sin-acceso-proveedores-ingenieria-pc.png` | La barra respeta la tabla (Laura: Inicio · Obras; Carla y Hernán: + Proveedores; Dora y Diego: + Usuarios). URL a mano → "No tenés acceso a esta sección". Observación: la campana y "Avisos" del celular llevan a una sección no entregada (B-03). |
| F-03 | Buscador general (s1: obras y proveedores) | ✅ | #6, #7, #16, #38 · `W-18-*.png` · sondeo "087" y 1 carácter | "tanque" → 1-087; "087" → 1-087; "sur" muestra la Terminada marcada; Ingeniería no recibe el grupo Proveedores; con 1 carácter no busca. |
| F-10 | Mi perfil (s1: datos, sector, jefatura; sin contraseña) | ✅ | #5 · `W-19-mi-perfil-*.png` · sondeo `PATCH /api/me` con sector → 422 | Sector y "depende de" solo a la vista. Texto con "(W-39)": B-02. |
| F-14 | Registro de quién hizo qué (s1: obras) | ⚠️ | #21 · `W-23-editar-obra-pc.png` · sondeo guardar sin cambios (22 → 22 líneas) | Una línea por dato con quién y cuándo, alta, adicional, documentos, montos y estado. Los importes del historial salen sin formato ("2500.00"): B-05. |
| F-15 | Listado de obras por estado (s1) | ✅ | #16, #20, #23 · `W-05-*.png`, `W-47-obras-celular.png` · sondeo filtro responsable | Tres pestañas con cantidad, arranca en En ejecución, "+ 1 adicional", cliente y planta juntos, filtro Responsable. "Pedidos abiertos" y "%" en "—" (DU-A12). |
| F-16 | Alta de obra | ✅ | #14, #15 · `W-06-alta-obra-*.png` · sondeos fechas, links, mail, estado | Nace Confirmada aunque se mande "terminada"; OC opcional; errores en cada campo; fin < inicio, link `ftp:` y mail mal escrito → 422 con su mensaje. |
| F-17 | Ficha de la obra (s1: datos + Documentos y contactos) | ⚠️ | #11, #12, #14 · `W-07-*.png`, `obra-no-encontrada-pc.png`, `W-47-W-20-*.png` | En PC cumple; botones según quién mira (Compras sin Editar). En celular las tablas de documentos no entran: B-01. |
| F-18 | Presupuestos: principal y adicionales | ✅ | #17 · `W-21-adicional-*.png`, `W-07-W-20-ficha-obra-pc.png` | N.º sugerido (A2 si ya está A1); con permiso de montos el botón es "Guardar y cargar montos por rubro →". |
| F-19 | Documentos y contactos (archivo o link, ADR-014) | ⚠️ | #19, #22 · `W-07-W-20-ficha-obra-pc.png` · sondeos 21 MB y `javascript:` | PC cumple: certificados con archivo y link, otros documentos, Drive solo link, contactos. Celular: B-01. Tamaño máximo: B-04. |
| F-20 | Editar la obra con registro | ✅ | #21 · `W-23-editar-obra-*.png` · sondeo PATCH con `estado` | Mismo formulario del alta, estado a la vista y no editable; mandar `estado` se ignora. |
| F-21 | Cambiar estado (s1) | ✅ | #13, #20, #23 · `W-22-*.png` · sondeo Confirmada → Terminada = 422 | De a un paso ("De Confirmada se pasa primero a En ejecución."); responsable o administración. Lista de pedidos abiertos: semana 2. |
| F-22 | Obras terminadas consultables | ✅ | #23 · `W-05-obras-terminadas-pc.png`, `W-18-buscador-sur-*.png` | Se abren, se editan y aparecen en el buscador marcadas "Terminada". |
| F-23 | Fechas previstas | ✅ | `W-07-W-20-ficha-obra-pc.png` · sondeo fin anterior al inicio → 422 | Opcionales; en la ficha "16/11/2026 → 26/02/2027". Ver O-01 sobre el formato del campo. |
| F-48 | Presupuesto por rubro (s1: carga W-13) | ✅ | #11, #18 · `W-13-*.png` · sondeos EPP, "insumo", negativo, 403, monto | 12 rubros como en sus planillas, columna por presupuesto, Total = suma; por rubro o solo el total por presupuesto (DU-08); Ingeniería ve sin editar y sin la fila "Monto del presupuesto"; el monto **no viaja** en el JSON para Ingeniería (verificado en la respuesta real). |
| F-55 | Base de proveedores con buscador | ✅ | #29, #30, #42 · `W-14-*.png` · sondeos "HORMIG", "corralon", 403 | Orden alfabético, búsqueda sin mayúsculas ni tildes, ficha con "Todavía no hay gastos cargados." (F-57 en semana 3), sin saldos. |
| F-56 | Alta y edición de proveedor | ✅ | #27, #28, #29 · `W-38-nuevo-proveedor-parecidos-pc.png` · sondeos CUIT | Parecidos avisan y dejan guardar; idéntico o CUIT repetido → "Ya existe …" / "Ese CUIT ya es de …"; CUIT con dígito malo → error. |
| F-58 | Usuarios: alta, edición y baja | ✅ | #31, #32, #33 · `W-15-usuarios-*.png`, `W-39-*.png` · sondeos mail repetido, 403 | Alta sin mail de bienvenida ("Ya puede entrar con su mail."), mail repetido aunque tenga mayúsculas, baja corta la sesión, nadie se desactiva a sí mismo. |
| F-59 | Sectores | ✅ | #24 · `W-40-sectores-*.png` · sondeo "direccion" repetido | Los 11 del organigrama, Pañol y Logística en 0; renombrar y sacar; no se saca con gente activa. |
| F-60 | Tabla de permisos con configuración inicial | ✅ | #1–#4, #24 · `W-15-tabla-permisos-*.png` · sondeo 403 | 12 módulos × 11 sectores con la configuración de `identidad.md` §6.3 ("sus obras" en Ingeniería); el cambio rige en el próximo pedido. |
| F-61 | Visibilidad por jerarquía (s1: "depende de") | ✅ | #31 · `W-39-editar-usuario-pc.png`, `W-19-mi-perfil-pc.png` · sondeo círculo → 422 | "Así se arma un círculo: Laura ya depende de Hernán." Pantallas de mi gente: semana 2. |
| F-68 | Plazos de la prioridad (s1: W-50) | ✅ | #25, #26 · `W-50-*.png` · sondeos | Arranque 1 / 7; "Prioritario tiene que ser mayor que Urgente."; negativo → error; Dirección solo ve. |

**Conteo: 18 ✅ · 4 ⚠️ (F-02, F-14, F-17, F-19) · 0 ❌.**

## 3. Criterios de aceptación de las historias de la semana 1

✅ verificado · ⚠️ verificado con observación · 🔹 cubierto solo por tests del backend (no se forzó en
QC) · — no aplica (historia reemplazada).

| HU | Criterio | Estado | Evidencia |
|---|---|---|---|
| HU-001 | 1 pide código y le llega un mail con 6 dígitos | ✅ | #8 (código leído del mail real) · `W-16-ingreso-codigo-*.png` |
| | 2 entra con el código; sirve una vez | ✅ / 🔹 | #8 · `IngresoTest::test_codigo_sirve_una_sola_vez` |
| | 3 mail inexistente o inactivo: mismo mensaje, sin mail | ✅ | #10, #31 |
| | 4 código malo/vencido/usado: "El código no es válido o venció."; 5.º intento invalida | ✅ / 🔹 | #9 · `W-16-ingreso-codigo-incorrecto-*.png` · `test_quinto_intento_fallido_invalida_el_codigo` |
| | 5 el código anterior no sirve tras pedir otro | 🔹 | `test_pedir_un_codigo_nuevo_invalida_el_anterior` |
| | 6 sesión de 90 días, sin "recordarme" | ✅ / 🔹 | #8 (recarga) · `SesionTest` |
| | 7 Salir cierra la sesión | ✅ | #8 |
| | 8 límites → 429 | 🔹 | `PedirCodigoTest`, `IngresoTest`, `BloqueoProgresivoTest` |
| | 9 celular sin zoom ni desplazamiento | ✅ | #39, #40 · `W-16-*-celular.png` (sin desplazamiento horizontal) |
| HU-002 | Recuperar contraseña | — | Reemplazada por ADR-015 (no existe la pantalla: verificado) |
| HU-003 | 1 ve nombre, mail, sector, jefatura y teléfono | ✅ | `W-19-mi-perfil-pc.png` |
| | 2 sector y jefatura como texto con la aclaración | ⚠️ | Idem; la aclaración dice "(W-39)" (B-02) |
| | 3 cambiar sector/jefe por API → no cambia | ✅ | sondeo `PATCH /api/me {sector_id}` → 422 "Este dato no se puede cambiar acá." |
| HU-004 | Cambiar contraseña | — | Reemplazada por ADR-015 (Mi perfil no la tiene: verificado) |
| HU-005 | 1 alta activa y aparece en la lista | ✅ | #31, #33 |
| | 2 mail repetido → error y no se crea | ✅ | `W-39-alta-mail-repetido-pc.png` "Ese mail ya es de otro usuario (Laura)." · sondeo con mayúsculas y espacio → 422 |
| | 3 falta nombre, mail o sector → error por campo | 🔹 | tests del backend (`identidad.md` RN-IDE-19) |
| | 4 entra enseguida con su mail, sin mail de alta | ✅ | #31 · alta de Dora en el arranque y su ingreso |
| | 5 doble clic → un solo usuario | ✅ | #33 |
| | 6 "depende de" solo activos y no a sí mismo | 🔹 | tests del backend |
| | 7 sin permiso → 403 | ✅ | sondeo Laura `POST /api/usuarios` → 403 |
| HU-006 | 1 cambio de sector rige en el próximo pedido | ✅ | #24 (sector sin Obras deja de ver el módulo) |
| | 2 círculo de jefatura → error | ✅ | sondeo → 422 "Así se arma un círculo…" |
| | 3 inactivo no entra y pierde la sesión | ✅ | #31 |
| | 4 no existe "borrar" | ✅ | `W-39-editar-usuario-pc.png` (solo Activo / Inactivo) |
| | 5 reactivado vuelve a entrar con código | 🔹 | tests del backend |
| | 6 no se desactiva a sí mismo | ✅ | #32 · sondeo → "No te podés desactivar a vos mismo." |
| HU-007 | 1 11 sectores, Pañol y Logística en 0 | ✅ | `W-40-sectores-pc.png` |
| | 2 sector nuevo = fila nueva con Pedidos/Equipos Editar y Obras Ver | ✅ | #24 |
| | 3 nombre repetido sin mayúsculas ni tildes → error | ✅ | #24 · sondeo "direccion" → "Ya hay un sector con ese nombre." |
| | 4 renombrar conserva usuarios y permisos | 🔹 | tests del backend |
| | 5 con usuarios activos no se saca | ✅ | #24 |
| | 6–7 borrar si nunca se usó / archivar con historia | 🔹 | tests del backend |
| HU-008 | 1 fila por sector, columna por módulo, "sus obras" | ✅ | `W-15-tabla-permisos-pc.png` |
| | 2 el cambio rige sin volver a entrar | ✅ | #24 |
| | 3 guarda de administrador | 🔹 | tests del backend (+ concurrencia, SEG-12) |
| | 4 cada celda queda registrada | 🔹 | tests del backend |
| | 5 sin permiso → 403 | ✅ | #5 · sondeo Laura `PUT /api/permisos` → 403 |
| HU-009 | 1 sin acceso → 403 y "No tenés acceso a esta sección" | ✅ | #5 · `sin-acceso-proveedores-ingenieria-pc.png` |
| | 2 "Ver" → sin botones y 403 | ✅ | #12, #26, #30 · sondeo montos con Ver → 403 |
| | 3 "sus obras" con id ajeno → 403/404 | ✅ | #11 |
| | 4 ninguna regla por nombre de sector | 🔹 | test de arquitectura |
| HU-010 | 1, 6 configuración inicial celda por celda | ✅ / 🔹 | `W-15-tabla-permisos-pc.png` · test de 12 × 11 celdas |
| | 2, 3 Operaciones no edita gastos; Ingeniería ve la rendición de sus obras | 🔹 | semana 3 (rendición y gastos); celdas verificadas en la tabla |
| | 4 monto de la obra no viaja a Ingeniería | ✅ | respuesta real de `GET /api/obras/{id}` y de W-13 para Laura sin `monto_obra` |
| | 5 Gerencia de Administración edita usuarios, Jefatura no | ✅ | `W-15-tabla-permisos-pc.png` |
| HU-011 | 1 arranque 1 / 7 / más de 7 | ✅ | `W-50-plazos-pc.png` |
| | 2 guarda y muestra "más de N días" | ✅ | #25 |
| | 3 urgente ≥ prioritario, negativo o vacío → error | ✅ | `W-50-plazos-error-pc.png` · sondeos → 422 |
| | 4 cambio registrado | 🔹 | tests del backend |
| | 5 sin permiso → 403 | ✅ | #26 |
| HU-012 | 1 alta con lo obligatorio → Confirmada | ✅ | #14 |
| | 2 sin OC se crea igual | ✅ | `W-07-obra-sin-presupuesto-por-rubro-pc.png` (2-140 sin OC) |
| | 3 faltantes → error en cada campo | ✅ | `W-06-alta-obra-errores-pc.png` |
| | 4 planta y fechas; fin < inicio → error | ✅ | sondeo → "La fecha de fin no puede ser anterior a la de inicio." |
| | 5 `estado` en el alta se ignora | ✅ | sondeo `estado: terminada` → nace `confirmada` |
| | 6 N.º repetido → error con link a la obra | ✅ | #15 |
| | 7 cliente con sugerencias | 🔹 | tests del backend (no se capturó el desplegable) |
| | 8 alta registrada | ✅ | `W-23-editar-obra-pc.png` ("Creó la obra") |
| | 9 mail o link mal escrito → error | ✅ | sondeo → `carpeta_drive_url` y `contactos.comprador.email` |
| | 10 oferta como archivo; tipo no permitido rechazado | ✅ / ⚠️ | #14, #22 · límite de tamaño en el entorno: B-04 |
| | 11 carpeta de Drive solo link | ✅ | `W-06-alta-obra-pc.png` ("Pegá el link: una carpeta no se sube.") |
| HU-013 | 1 tres pestañas con cantidad, arranca en En ejecución | ✅ | `W-05-obras-en-ejecucion-pc.png` |
| | 2 N.º con revisión y "+ N adicional", trabajo, cliente y planta, responsable | ✅ | Idem |
| | 3 Terminada en su pestaña y se abre | ✅ | #23 · `W-05-obras-terminadas-pc.png` |
| | 4 filtro Responsable | ✅ | `W-05-obras-filtro-responsable-pc.png` · sondeo |
| | 5 paginado a 25 sin perder pestaña ni filtro | 🔹 | `test_mas_de_25_obras_se_paginan_sin_perder_la_pestana_ni_el_filtro` |
| HU-014 | 1 encabezado y pestañas | ✅ | `W-07-W-20-ficha-obra-pc.png` (solo Documentos y contactos: DU-A12 aprobada) |
| | 2 presupuestos, OC, certificados, Drive y contactos | ✅ / ⚠️ | PC ✅ · celular B-01 |
| | 3 agregar certificado | ✅ | #19 |
| | 4 link abre, archivo se baja; sin permiso 404 | ✅ | #19 · tests de IDOR del backend |
| | 5 quitar certificado o adjunto | ✅ / ⚠️ | #19 · en celular los "quitar" quedan fuera de la pantalla (B-01) |
| | 6 obra inexistente → "Obra no encontrada" | ✅ | #12 · `obra-no-encontrada-pc.png` |
| HU-015 | 1 adicional; con permiso de montos pasa a W-13 | ✅ | #17 · `W-21-adicional-pc.png` |
| | 2 se muestran en orden de alta | ✅ | `W-07-W-20-ficha-obra-pc.png` |
| | 3 solo el N.º es obligatorio | ✅ | `W-21-adicional-pc.png` (el resto "opcional") |
| | 4 N.º sugerido A1, A2… | ✅ | `W-21-adicional-pc.png` (sugiere A2) |
| | 5 en obra Terminada se permite | 🔹 | `test_obra_terminada_admite_adicional` |
| | 6 queda en el registro | ✅ | `W-23-editar-obra-pc.png` |
| HU-016 | 1 mismo formulario sin estado | ✅ | `W-23-editar-obra-pc.png` |
| | 2 una línea por dato | ✅ | #21 |
| | 3 sin cambios no registra | ✅ | sondeo (22 → 22 líneas) |
| | 4 `estado` se ignora | ✅ | sondeo (sigue `en_ejecucion`) |
| | 5 Ver → 403 | ✅ | #12 |
| | 6 mismas validaciones | ✅ | `FichaYEdicionTest` · sondeos del alta |
| HU-017 | 1 responsable pasa a En ejecución y cambia de pestaña | ✅ | #13, #20 |
| | 2 administración cambia cualquiera | ✅ | datos de QC cambiados por Diego |
| | 3 no responsable → 403 | ✅ | #13 |
| | 4 Terminada sale de las activas | ✅ | #23 |
| | 5 solo desde W-22 | ✅ | `W-23-editar-obra-pc.png` |
| | 6 un paso atrás sí; saltos no | ✅ / 🔹 | sondeo salto → 422 · `EstadoObraTest` (vuelta atrás) |
| HU-018 | 1 fila por rubro, columna por presupuesto, total = suma | ✅ | `W-13-presupuesto-por-rubro-pc.png` |
| | 2 guarda y recalcula | ✅ | #18 |
| | 3 EPP o rubro fuera de lista → 422 | ✅ | sondeos `epp` e `insumo` → "Ese rubro no se presupuesta o no está en la lista." |
| | 4 "solo el total" por presupuesto, mezcla con "—" | ✅ | `W-13-presupuesto-por-rubro-pc.png` (A1 solo total) |
| | 5 obra sin presupuesto por rubro se usa igual | ✅ | `W-07-obra-sin-presupuesto-por-rubro-pc.png` |
| | 6 Ver sin editar; API 403 | ✅ | #11 · `W-13-…-solo-ver-ingenieria-pc.png` · sondeo 403 |
| | 7 corrección con historial | ✅ | `W-23-editar-obra-pc.png` (líneas por rubro) |
| | 8 monto del presupuesto solo con permiso, no viaja | ✅ | `W-13-presupuesto-por-rubro-pc.png` vs. la de Ingeniería · verificación del JSON |
| HU-019 | 1 "Presupuesto por rubro: sin cargar" | ✅ | `W-07-obra-sin-presupuesto-por-rubro-pc.png` |
| | 2 elegible para pedidos | 🔹 | semana 2 |
| HU-020 | 1 lista por nombre con CUIT y rubro, paginada | ✅ | `W-14-proveedores-pc.png` |
| | 2 "hormig" sin mayúsculas ni tildes | ✅ | sondeos "HORMIG" y "corralon" |
| | 3 ficha con "Gastado por obra" pendiente | ✅ | `W-14-proveedor-ficha-pc.png` ("Todavía no hay gastos cargados.") |
| | 4 sin saldos ni cuenta corriente | ✅ | Idem |
| | 5 sin acceso → 403 | ✅ | #5 · sondeo Laura → 403 |
| HU-021 | 1 solo el nombre | ✅ | #27 |
| | 2 CUIT, rubro y contacto; CUIT inválido → error | ✅ | sondeo → "El CUIT no es válido. Revisá los números." |
| | 3 parecidos y guarda igual | ✅ | #28 · `W-38-nuevo-proveedor-parecidos-pc.png` |
| | 4 idéntico o CUIT repetido → error con link | ✅ | #28 · sondeos |
| | 5 edición con quién y cuándo | ✅ / 🔹 | #29 · tests del backend (actividad `editado`) |
| | 6 no existe borrar | ✅ | `W-14-proveedor-ficha-pc.png` |
| HU-022 | 1 "087" encuentra 1-087-2026 | ✅ | `W-18-buscador-087-pc.png` |
| | 2 cliente, planta o trabajo, cualquier estado | ✅ | `W-18-buscador-tanque-pc.png`, `W-18-buscador-sur-pc.png` |
| | 3 proveedores solo con acceso | ✅ | #7 · `W-18-buscador-sur-ingenieria-pc.png` |
| | 4 agrupado por tipo con cantidad y abre la ficha | ✅ | #6 |
| | 5 menos de 2 caracteres no busca | ✅ | sondeo `q=2` → sin grupos |
| | 6 grupo sin acceso no viaja | ✅ | #7 |
| HU-023 | 1 solo módulos con Ver o Editar | ✅ | #1–#4 · `W-17-barra-*.png` |
| | 2 refleja cambios sin volver a entrar | ✅ | #24 |
| | 3 URL sin acceso → mensaje y 403 | ✅ | `sin-acceso-proveedores-ingenieria-pc.png` |
| | 4 Ingeniería ve Gastos y Rendiciones en solo lectura | 🔹 | por DU-A12 no se muestran hasta la semana 3; matriz verificada |
| | 5 módulos no entregados no aparecen | ⚠️ | la barra cumple, pero la campana y "Avisos" del celular están (B-03) |
| HU-024 | 1 al entrar llega a Obras | ✅ | las 7 personas llegan a `/obras` |
| | 2 celular 390 px sin desplazamiento en ingreso, obras, ficha y proveedores | ⚠️ | la página no se desplaza en ninguna; la ficha es poco legible (B-01) |
| HU-025 | 1 subir tipos aceptados | ✅ / 🔹 | #14, #19 · `SubirAdjuntoTest` (9 tipos, HEIC) |
| | 2 rechazo por contenido | ✅ | #22 |
| | 3 más de 20 MB → "El archivo pesa N MB…" | ⚠️ | en el entorno de QC el corte es en 8 MB con mensaje genérico (B-04); en el backend, `test_rechaza_archivo_de_mas_de_20_mb` |
| | 4 solo `http(s)` | ✅ | sondeo `javascript:` → 422 |
| | 5 asociado en la misma operación; temporal se borra a las 24 h | 🔹 | `AsociarAdjuntosTest`, `PurgarAdjuntosHuerfanosTest` |
| | 6 descarga con nombre original; sin permiso 404 | ✅ / 🔹 | #19 · `DescargarAdjuntoTest` |
| | 7 quitar borra el archivo y queda en el historial | ✅ | #19 · historial "Archivo o link: agregado/quitado" |
| | 8 cámara o galería en el celular | 🔹 | no se puede probar en un navegador automático; HEIC aceptado en el backend |
| | 9 carpeta de Drive solo link | ✅ | `W-06-alta-obra-pc.png` |

## 4. Bugs encontrados

Durante el QC no se arregló ninguno (rol QC); el estado de las correcciones está al final de esta sección. Severidad: **bloqueante** (impide la prueba de la semana) · **media**
(una función no se puede usar bien en un caso de la entrega) · **menor** (se usa, pero confunde o
desprolija).

### B-01 · Media · La ficha de la obra no se lee en el celular
- **Dónde:** W-47 / W-20 en el celular (Pixel 7, 412 px). F-17, F-19, HU-014, HU-024 c2.
- **Pasos:** 1) Entrar como Diego desde un celular. 2) Abrir una obra con la oferta técnica en PDF
  y un certificado con un archivo. 3) Bajar hasta Documentos y contactos.
- **Esperado:** las tablas de Presupuestos, OC y Certificados se leen y se puede editar o quitar.
- **Obtenido:** el nombre del archivo se parte letra por letra ("o f e r t a - 2 - 1 3 1 …") y las
  columnas "editar" y "quitar" quedan afuera de la pantalla (3 controles "quitar", 0 dentro de los
  412 px). La página no se desplaza a lo ancho, pero la sección es casi ilegible.
- **Captura:** `entrega-1/W-47-W-20-ficha-obra-celular.png`, `entrega-1/W-47-W-20-documentos-celular-detalle.png`.
- **Impacto:** no bloquea la prueba de esta semana (la carga de obras es en la PC); hay que
  resolverlo antes de la semana 2, que es la del celular.

### B-02 · Menor · Se ven códigos internos de pantalla
- **Dónde:** Mi perfil ("Sector y jefatura los cambia gerencia (W-39)."), Agregar y Editar usuario
  ("…de los permisos de su sector (W-15)."), Sectores ("…en la tabla de permisos (W-15).") y Avisos
  ("Se va a construir siguiendo la maqueta W-02.").
- **Pasos:** abrir cada una de esas pantallas.
- **Esperado:** las palabras del cliente, sin códigos del documento (`lessons.md`: cero jerga). Por
  ejemplo "Sector y jefatura los cambia gerencia desde Usuarios."
- **Captura:** `W-19-mi-perfil-pc.png`, `W-39-editar-usuario-pc.png`, `W-40-sectores-pc.png`,
  `avisos-campana-semana-1-pc.png`.
- **Nota:** las maquetas W-19 y W-40 traían el código como referencia del documento; en el sistema
  no le dice nada a la persona.

### B-03 · Menor · Entradas a secciones que todavía no se entregaron
- **Dónde:** campana de la barra (PC y celular) y "Avisos" de la barra inferior del celular. También
  está "Tareas" en la barra del celular (Mis tareas llega en la semana 2); no se verificó a dónde lleva.
- **Pasos:** tocar la campana → "Esta sección todavía no está disponible".
- **Esperado (DU-A12, aprobada):** en la semana 1 se ve solo lo entregado; lo demás aparece en su
  semana.
- **Captura:** `avisos-campana-semana-1-pc.png`, `mas-celular.png`, `W-47-obras-celular.png`.
- **Impacto:** un cliente que ya dejó un sistema por complicado toca la campana y encuentra una
  pantalla vacía. Ocultarla hasta la semana 2 o decirlo en las notas (se dice en las notas).

### B-04 · Menor (entorno) · Archivos de más de 8 MB rechazados con un mensaje genérico
- **Dónde:** subida de adjuntos (HU-025 c1 y c3) en el entorno de prueba.
- **Pasos:** subir como certificado un PDF de 21 MB (o cualquiera entre 8 y 20 MB) en un entorno con
  la configuración de PHP por defecto de la máquina de QC (`upload_max_filesize=8M`,
  `post_max_size=12M`).
- **Obtenido:** 413 "La solicitud no es válida." (sin el detalle de los MB); un archivo válido de
  10 MB también se rechazaría.
- **Esperado:** hasta 20 MB se aceptan; más de 20 MB → "El archivo pesa N MB y el máximo es 20 MB."
- **Causa probable:** configuración del servidor, ya listada en `seguridad.md` §6 (≥ 21 MB en PHP y
  nginx). El mensaje genérico aparece siempre que el corte lo hace el servidor y no la aplicación.
- **Condición del veredicto:** verificar esa configuración en el entorno del cliente antes de
  mandar el link.

### B-05 · Menor · Importes sin formato en el historial de la obra
- **Dónde:** W-23, Historial de cambios.
- **Pasos:** cargar el presupuesto por rubro o el monto del presupuesto y abrir Editar → Historial.
- **Obtenido:** "MATERIALES: (vacío) → 1000.00", "Monto del presupuesto: (vacío) → 2500.00".
- **Esperado:** el mismo formato que W-13 ("1.000", "2.500"), como en sus planillas.
- **Captura:** `W-23-editar-obra-pc.png` (comparar con `W-13-presupuesto-por-rubro-pc.png`).

### Observaciones (no se clasifican como bug)
- **O-01 · Fechas previstas:** en el navegador automático de QC (Chromium en inglés) el campo de
  fecha se muestra "mm/dd/yyyy". En un Chrome en español se ve dd/mm/aaaa; la ficha ya las muestra
  "16/11/2026". Verificar en la PC del cliente durante la prueba.
- **O-02 · "Links" del encabezado de la ficha:** muestra la oferta y la OC como dos cajas con el
  dominio del link ("drive.example") sin decir cuál es cuál; con links de Drive reales las dos dirían
  lo mismo. La maqueta W-07 los nombra "Presupuesto · OC · Carpeta de Drive". Ver diferencias (D-N3).
- **O-03 · Tarjeta de obra en el celular:** "Pedidos abiertos: —" y debajo un "—" sin rótulo (el %
  gastado de la semana 3).

### Estado de las correcciones (rama `fix/w1-qc`, 13-nov)

Las 5 quedan arregladas, cada una con su test. Commits: front (`tecnoservicios_frontend`) y back
(`tecnoservicios_backend`).

| # | Estado | Arreglo | Commits | Tests |
|---|---|---|---|---|
| B-01 | ✅ arreglado | En el celular las tablas de la ficha (Presupuestos, OC/OT/contrato/mail, Certificados y otros documentos e Historial de cambios) se ven como tarjetas, una por fila, con el rótulo de cada dato arriba del valor; "editar" y "quitar" quedan dentro de la tarjeta. En PC no cambia. | front `c9bb557`, `fc2e79b` | `e2e/ficha-celular.spec.ts` (PC y Pixel 7: 4 botones dentro de los 412 px, ningún marco de tabla se desplaza, el nombre del archivo se lee de corrido, sin desplazamiento horizontal) |
| B-02 | ✅ arreglado | Textos sin códigos: "Sector y jefatura los cambia gerencia desde Usuarios.", "…sale de la tabla de permisos de su sector.", "…una fila más en la tabla de permisos.", y las secciones no entregadas dicen "Va a estar disponible en una próxima entrega." | front `864078c` | `src/test/sinCodigosInternos.test.ts` (recorre todos los textos de `src/` y falla con W-XX, F-XX, HU-XX, DU-XX o RN-XX) + control en `src/test/setup.ts`: cualquier test que deje en pantalla un código falla |
| B-03 | ✅ arreglado | La campana (PC y celular), "Avisos" y "Tareas" del celular se encienden con `VITE_MODULOS_ENTREGADOS` como el resto (`avisos`; "Tareas" con `pedidos`). En la semana 1 la barra del celular es Obras · Más. | front `45d642f` | `menuPara.test.ts`, `AppShell.test.tsx`, `navegacion.test.tsx`, `e2e/navegacion.spec.ts`, `e2e-integracion/barra.spec.ts` |
| B-04 | ✅ arreglado (sigue la condición del entorno) | Si PHP corta la subida antes que la aplicación, el backend responde 413 con el tamaño: "El archivo pesa 25 MB y el máximo es 8 MB." (cuerpo más grande que `post_max_size`) o "El archivo pesa más de 8 MB, que es el máximo." (`upload_max_filesize` o multipart vacío). El máximo informado es el real del servidor (el menor entre 20 MB y PHP). El front ya frenaba antes de subir lo de más de 20 MB y muestra el mensaje del 413. **No se tocó el `php.ini`**: la condición del veredicto (≥ 21 MB en el entorno del cliente) sigue en pie. | back `b85d324`, `5692220` · front `d25199c` | `SubidaCortadaPorElServidorTest`, `LimiteDeSubidaTest`, `ErroresEnEspanolTest` · `AdjuntosField.test.tsx` (413) |
| B-05 | ✅ arreglado | El backend arma el texto del historial y ahora reconoce los campos de importe (montos por rubro, total y monto del presupuesto) y los escribe como W-13: "MATERIALES: (vacío) → 1.000", "Monto del presupuesto: (vacío) → 2.500". Mismas reglas que el formateador del front (`es-AR`, hasta 2 decimales). Vale también para lo ya registrado. | back `630ff1b` | `ImporteLegibleTest`, `PresupuestoPorRubroTest::test_historial_muestra_los_importes_como_en_el_presupuesto_por_rubro` (+ los de privacidad ahora buscan el monto con y sin puntos) |

Observaciones: **O-02** aplicada (front `1d22c9a`: los links del encabezado dicen "Presupuesto", "OC"
—o el nombre del documento si es OT, contrato o mail— y "Carpeta de Drive"; test en
`fichaLinks.test.tsx`). **O-03** aplicada (front `7290bb5`: en la tarjeta del celular el "—" dice
"Gastado: —"). **O-01** no se tocó: el formato del campo de fecha lo pone el navegador según su
idioma; se verifica en la PC del cliente. Además, el texto del 419 del front quedó igual al del
backend: "La sesión expiró. Volvé a ingresar." (front `ba21a03`).

## 5. Diferencias contra la maqueta y el documento firmado

### 5.1 Aprobadas por Juani

| # | Diferencia | Pantallas | Decisión |
|---|---|---|---|
| D-A1 | Se entra con el mail y un código por mail; sin contraseña, "Olvidé mi contraseña", "Recordarme" ni elegir método de ingreso. Bloqueo progresivo tras 3 códigos quemados | W-16, W-19 (sin "Cambiar contraseña"), W-39/W-15 (sin "Reenviar mail de bienvenida") · F-01, F-10, F-58 | **ADR-015** (13-nov) y su enmienda. Hay que avisarle al cliente |
| D-A2 | Donde había un link a un documento, se sube el archivo o se pega el link (oferta técnica, OC/OT/contrato/mail, certificados y otros documentos). Carpeta de Drive: solo link | W-06, W-20, W-21, W-23 · F-16, F-18, F-19 | **ADR-014** / DU-23 (12-nov). Hay que avisarle al cliente |
| D-A3 | El sector de cada persona no se cambia desde la lista de W-15 (el "▾" de la maqueta): se cambia en su ventana de edición (W-39) | W-15, W-39 | Juani (W-15 → W-39) |
| D-A4 | Semana 1 sin Pedidos, Calendario, Gastos ni Rendiciones en la barra; "Pedidos abiertos" y "% gastado" en "—"; sin fila "Servicios generales"; la ficha solo con "Documentos y contactos"; W-22 sin lista de pedidos abiertos; Mi perfil sin "Mis avisos" | W-05, W-07, W-17, W-19, W-22, W-47 | **DU-A12** (12-nov) |
| D-A5 | Tabla de permisos con 12 columnas (se suman Equipos en reparación, Estado de obra, Monto de obra y ganancia, Control por cantidad, Plazos de la prioridad) y 11 sectores del organigrama (la maqueta tenía 7 columnas y 10 sectores con nombres cortos) | W-15, W-40 | DU-01, DU-A01, DU-02, DU-A02…DU-A06 |
| D-A6 | "Por rubro / Solo el total" se elige por presupuesto en W-13; fila "Monto del presupuesto" solo para quien lo ve | W-13 | DU-08, DU-19 |
| D-A7 | Proveedor genérico "Gastos sin comprobante" en la lista, no editable | W-14 | DU-A18 |
| D-A8 | Estado de a un paso con vuelta atrás; N.º con formato y revisión REVnn; obligatorios del adicional | W-21, W-22, W-06 | DU-05, DU-07, DU-A14, DU-A16 |

### 5.2 Sin aprobación explícita

Ninguna cambia lo firmado; son decisiones de interfaz del equipo. Se proponen para que Juani las
acepte o las corrija.

| # | Diferencia | Pantalla | Propuesta de QC |
|---|---|---|---|
| D-N1 | Campana y "Avisos" del celular (y "Tareas") visibles sin su sección entregada | W-17, W-41 | Va como B-03: ocultar hasta la semana 2 |
| D-N2 | Códigos "W-XX" en textos de ayuda | W-19, W-39, W-40, Avisos | Va como B-02 |
| D-N3 | El encabezado de la ficha muestra "Links" con el dominio de cada link, sin "Presupuesto · OC · Carpeta de Drive" | W-07 | Rotular cada link (O-02) |
| D-N4 | Selector "Documento de aprobación" (Orden de compra / OT / contrato / mail) en el alta, la edición y el adicional; la maqueta solo tenía "Link a OC…" y "N.º de OC" | W-06, W-21, W-23 | Aceptar: sale del texto firmado de F-16 ("OC / OT / contrato / mail de aprobación") y W-20 ya lo mostraba como "Documento" |
| D-N5 | El buscador general se abre con la lupa de la barra (la maqueta no lo ubica en la barra) | W-17, W-18 | Aceptar |
| D-N6 | En el celular, Proveedores, Usuarios, Mi perfil y Salir viven en "Más" | W-41 | Aceptar (es el patrón de W-41) |
| D-N7 | La tabla de permisos en el celular se desplaza dentro de su marco | W-15 | Aceptar (la página no se desplaza) |
| D-N8 | La maqueta W-05 trae "Buscar…" sin rótulo; el sistema agrega rótulos visibles ("Buscar por N.º de presupuesto, cliente o trabajo", "Responsable") | W-05, W-14 | Aceptar (accesibilidad) |

## 6. Criterios globales del cap. 13

| Criterio / requisito | ¿Aplica en la semana 1? | Resultado |
|---|---|---|
| Sistema web desde la computadora y el celular, sin instalar nada | Sí | ✅ con observación: todo funciona en Chrome PC y Pixel 7, ninguna página se desplaza a lo ancho; la ficha de la obra en el celular es poco legible (B-01) |
| Todo en tiempo real | Parcial (permisos) | ✅ un cambio de permisos rige en el siguiente pedido sin volver a entrar (#24). Avisos y rendición: semanas 2 y 3 |
| Simple: una pantalla clara por tarea, pocos pasos | Sí | ✅ con observación: alta de obra en una pantalla, adicional en una ventana, ingreso en dos pasos. Restan claridad los códigos internos y las secciones vacías (B-02, B-03) |
| Queda registrado quién hizo qué y cuándo | Sí (obras, usuarios, permisos, proveedores) | ✅ historial de la obra completo (B-05 es de formato) |
| Alojado y actualizado por NexoSmart | Al publicar | Pendiente del entorno del cliente; checklist de `seguridad.md` §6 (incluye B-04) |
| 1. El gerente ve gastado vs. presupuestado por rubro | No (semana 3) | La base está: W-13 carga el presupuesto por rubro (F-48 ✅) |
| 2. Una factura se carga una vez en todas las obras | No (semana 3) | Proveedores únicos listos (F-55, F-56 ✅) |
| 3. Ningún pedido se atrasa sin que el propietario se entere | No (semana 2) | Plazos de la prioridad listos (F-68 ✅) |
| 4. Dejan la minuta en Excel | No (semanas 2 y 4) | — |

## 7. Cómo se reproduce

1. `npm run e2e:integracion` en `tecnoservicios_frontend` (MariaDB y Redis arriba, puertos 8320 y
   5312 libres). Resultado: 42 passed.
2. Las capturas y los sondeos los generó un script de Playwright fuera de los repos que importa el
   mismo `global-setup` y `support/` de la suite de integración (datos ficticios por la API, sesiones
   reales con el código leído del mail logueado). El registro de cada captura (URL, título y ancho de
   página) y de cada sondeo (status y respuesta) está en [`entrega-1/registro-capturas.log`](entrega-1/registro-capturas.log).
