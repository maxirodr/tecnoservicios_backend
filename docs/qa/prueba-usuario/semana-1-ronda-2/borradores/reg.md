# Borrador REG · Cazador de regresiones · Ronda 2

- Fecha: 2026-11-17 · front `f930579` · back `c27e382` · base `tecnoservicios_prueba_usuario`
- Personas: Diego (sesión guardada), Hernán y Carla (solo para cargar su sesión en la misma pestaña), QA-LEAD Vence `qa-lead-3@empresa-ejemplo.com` (Compras, **2 ingresos reales** con `h.ingresar`, ambos usados).
- Datos propios: obras 2-501…2-505-2026 ("QA-REG Obra guardia / conflicto / borrador / repetida."), proveedores "QA-REG Ferretería Uno", "QA-REG Pinturería Dos S.A.", "QA-REG Conflicto Tres", "QA-REG Alta …"; sector "QA-REG Sector" y usuario "QA-REG Ulises" (`qa-reg-1@…`).
- Scripts: `C:\Users\GREEN\AppData\Local\Temp\claude\4fe530b1c11d8e8d\f4efcdda-aa94-4434-aafd-2e4822d4d74b\scratchpad\r2\reg\` (`01…25-*.cjs`).
- Canal `errors`: hoy no existe `errors-2026-11-17.log` después de todos los 401/409/419/422 de estas pruebas → OK (no registra 4xx).

## 1. Lo que anda (comprobado, sin hallazgo)

| Qué | Resultado |
|---|---|
| Aviso de cambios sin guardar en Nueva obra / Editar obra: link de la barra, "Atrás" (SPA), "Cancelar", F5 y cerrar pestaña (`beforeunload`) | ✅ "Tenés cambios sin guardar. ¿Salir igual?"; "Seguir editando" conserva lo escrito; Escape cierra solo la pregunta; F5 cancelado conserva el valor |
| Sin cambios / después de guardar | ✅ no pregunta (alta, edición, proveedor, permisos, Mi perfil) |
| "Ese N.º de presupuesto ya está en …" (clic normal) y "Ver la ficha de …" (clic normal) | ✅ salen sin preguntar y el diálogo se cierra (f930579). Ver REG-06 para Ctrl+clic |
| Borrador tras sesión vencida (obra en edición, proveedor alta) | ✅ "Tu sesión venció. Volvé a ingresar." y, al volver, "Recuperamos lo que estabas escribiendo." con lo escrito; se borra al montar (no reaparece después de guardar ni de "Cancelar → Salir"); no se mezcla entre obras distintas (clave con la ruta). **Con ingreso real** (QA-LEAD Vence): vuelve a `/proveedores/4` con el alta abierta y lo escrito. Ver REG-03/04/05 |
| 409 en obra (datos de la obra), proveedor y usuario | ✅ "Alguien cambió esto mientras lo tenías abierto. Actualizá y revisá." + "Actualizar"; con el conflicto puesto la barra igual pregunta; "Actualizar" carga lo guardado y deja de preguntar; el guardado siguiente sale 200. Ver REG-01/02 (presupuesto) y REG-10 (Mi perfil) |
| "No había cambios para guardar." | ✅ Editar obra (también cambio y vuelta atrás), proveedor, usuario y Mi perfil, sin PATCH. Ver REG-13 (espacios) |
| Doble clic | ✅ un solo `POST /api/proveedores` (alta desde `/proveedores/nuevo`), un `PATCH` por recurso en Editar obra, un `PUT /api/permisos` |
| Diálogos ocupados | ✅ proveedor, documento y cambiar estado: Escape y clic afuera no cierran; "Cancelar" y el botón principal deshabilitados. Ver REG-07 (Atrás) |
| Tabla de permisos | ✅ cambiar de pestaña (clic o Enter) con "1 cambio sin guardar" pregunta; después de guardar, no |
| Pestañas con flechas (Obras) | ✅ → ← Inicio Fin mueven foco y elección (con vuelta circular); Tab sale al buscador |
| "Buscando obras…" | ✅ solo en la zona de la lista (`zona-recarga--activa`), en PC y Pixel 7; cambiar de pestaña durante la búsqueda no mezcla resultados |
| Pixel 7 | ✅ sin scroll horizontal en Obras, Proveedores, Usuarios (tarjetas), ficha y edición; botones de "¿Salir igual?" de 44 px |
| `/proveedores/nuevo`, `/abc`, `/0`, `/999999`, `/1e3`, `/%20` | ✅ alta abierta (Diego) / "Solo podés ver esta sección …" (Hernán) / "Proveedor no encontrado · Elegí otro de la lista." (PC y Pixel 7). Alta desde `/nuevo` termina en la ficha con "Dimos de alta a …" |
| "Guardar" con link en curso | ✅ deshabilitado mientras viaja; se libera si falla, si se quita o con "Cancelar"; link escrito sin agregar → "Tocá «Agregar link» para sumar el link que pegaste." con foco en el campo; vacío o cancelado deja guardar. Ver REG-11/12 |

## 2. Hallazgos nuevos

### REG-01 · Editar obra: la Revisión (datos del presupuesto principal) de otra persona se pisa sin aviso
- **Severidad:** bloqueante
- **Tipo:** funcional
- **F-XX / pantalla:** F-20 · W-23 Editar obra · `/obras/{id}/editar`
- **Persona:** Diego (dos pestañas)
- **Pasos:** 1. Abrir "Editar" de la obra 2-502-2026 en dos pestañas (A y B). 2. En A cambiar "Revisión" a "REV25" y "Guardar cambios". 3. En B, sin recargar, cambiar "Revisión" a "REV35" y "Guardar cambios".
- **Esperado:** B recibe "Alguien cambió esto mientras lo tenías abierto. Actualizá y revisá." (decisión de Juani sobre PU-002), igual que cuando chocan datos de la obra.
- **Obtenido:** B ve "Guardamos los cambios de la obra." y la base queda en "REV35": el cambio de A se perdió sin aviso. Red: `PATCH /api/presupuestos/11 → 200`, `PATCH /api/obras/11 → 200` (el `updated_at` de la obra no cambia al editar el presupuesto, así que nunca hay 409). Reproducido 2 veces (REV25→REV35, REV2x→REV32).
- **Evidencia:** `capturas/REG-01-revision-pisada.png` · script `reg/03-conflicto-obra.cjs s2`
- **Causa probable (opcional):** `src/features/obras/hooks/useObras.ts:88-93` manda `editarPresupuesto(principal.id, cambios)` sin `updated_at`; el control de versión de fbffdca solo está en `PATCH /api/obras`. Afecta N.º, revisión, descripción y aprobación del principal.
- **Regresión de:** PU-002 / fbffdca + 669cad2 (arreglo incompleto)

### REG-02 · Editar obra con conflicto: dice que "se pierde lo que cambiaste" pero la Revisión ya quedó guardada
- **Severidad:** alta
- **Tipo:** funcional
- **F-XX / pantalla:** F-20 · W-23 Editar obra · `/obras/{id}/editar`
- **Persona:** Diego (dos pestañas)
- **Pasos:** 1. Abrir "Editar" de 2-502-2026 en A y B. 2. En A cambiar "Cliente" y guardar. 3. En B cambiar "Revisión" a "REV45" y "Planta" a "Planta B…", y "Guardar cambios".
- **Esperado:** con el aviso de conflicto no se guarda nada de B (o se dice qué se guardó).
- **Obtenido:** B ve "Alguien cambió esto mientras lo tenías abierto. Actualizá y revisá." y "Al actualizar vas a ver lo que está guardado ahora y se pierde lo que cambiaste acá.", pero la base ya tiene "REV45" (la planta no). Al tocar "Actualizar" el formulario muestra "REV45": quedó guardado a medias, contra lo que dijo la pantalla. Red: `PATCH /api/presupuestos/11 → 200` y después `PATCH /api/obras/11 → 409`. Reproducido 2 veces (REV45, REV46).
- **Evidencia:** `capturas/REG-02-conflicto-guardado-a-medias.png` · script `reg/03-conflicto-obra.cjs s3`
- **Causa probable (opcional):** misma secuencia de `useObras.ts:88-93`: el presupuesto se guarda antes de saber si la obra tiene conflicto.
- **Regresión de:** PU-002 / fbffdca + 669cad2

### REG-03 · El borrador recuperado de una persona le aparece a otra que entra en la misma pestaña
- **Severidad:** media
- **Tipo:** funcional
- **F-XX / pantalla:** F-01 sesión vencida · W-38 Alta de proveedor `/proveedores` · W-23 Editar obra `/obras/{id}/editar`
- **Persona:** Diego → Carla / QA-LEAD Vence (proveedor); Diego → Hernán (obra)
- **Pasos:** 1. Diego abre "+ Nuevo proveedor", escribe "QA-REG Borrador de Diego" y el mail "diego-privado@empresa-ejemplo.com". 2. La sesión vence (cookies borradas) y toca "Guardar proveedor": "Tu sesión venció. Volvé a ingresar.". 3. En esa misma pestaña entra otra persona (Carla) y va a Proveedores. (Igual con Editar obra 2-503-2026: Diego escribe, vence, entra Hernán.)
- **Esperado:** el borrador es de quien lo escribió; otra persona ve el formulario limpio.
- **Obtenido:** Carla ve el alta abierta con "Recuperamos lo que estabas escribiendo." y el nombre y el mail que escribió Diego; si toca guardar, queda a su nombre. Igual con QA-LEAD Vence, y con Hernán en Editar obra (cliente "QA-REG Cliente borrador …" y planta de Diego). Reproducido 2 veces en proveedores (Carla, QA-LEAD Vence) y 4 en obra (Hernán).
- **Evidencia:** `capturas/REG-03-borrador-de-otra-persona.png` · `capturas/REG-03-borrador-de-otra-persona-obra.png` · scripts `reg/11-borrador-persona.cjs`, `reg/04-sesion-obra.cjs hernan` (el cambio de persona se simuló cargando la sesión guardada de la otra persona en la misma pestaña, sin gastar ingresos)
- **Causa probable (opcional):** `src/shared/forms/useBorrador.ts:51/83`: la clave es `tecnoservicios:borrador:` + ruta/registro, sin el usuario; "Salir" tampoco limpia `sessionStorage`.
- **Regresión de:** PU-004 / ce05073

### REG-04 · El borrador se descarta en silencio si la obra cambió mientras la sesión estaba vencida
- **Severidad:** media
- **Tipo:** mensaje
- **F-XX / pantalla:** F-20 · W-23 Editar obra · `/obras/{id}/editar`
- **Persona:** Diego
- **Pasos:** 1. En "Editar" de 2-503-2026 escribir cliente "QA-REG Cliente borrador …" y planta "Planta borrador". 2. Vence la sesión y toca "Guardar cambios" ("Tu sesión venció. Volvé a ingresar."). 3. Mientras tanto otra persona guarda la obra (planta "Cambio de otro"). 4. Volver a entrar y abrir la misma edición.
- **Esperado:** si lo escrito no se puede recuperar, decirlo (p. ej. "Mientras no estabas alguien cambió la obra; lo que habías escrito no se pudo recuperar.") o mostrarlo para copiarlo.
- **Obtenido:** el formulario aparece con lo guardado por la otra persona, sin "Recuperamos…" ni ningún aviso; lo escrito desapareció (el borrador se borra de `sessionStorage`). Es el mismo "se pierde lo escrito y no se dice por qué" de PU-004. Reproducido 2 veces.
- **Evidencia:** `capturas/REG-04-borrador-descartado.png` · script `reg/04-sesion-obra.cjs viejo`
- **Causa probable (opcional):** `src/shared/forms/useBorrador.ts:86` devuelve `null` si la versión no coincide y el efecto de montaje lo borra, sin avisar.
- **Regresión de:** PU-004 / ce05073

### REG-05 · Si la sesión vence al subir un archivo o pegar un link, queda "Tenés que ingresar para seguir." sin salida y sin borrador
- **Severidad:** media
- **Tipo:** funcional
- **F-XX / pantalla:** F-16 · W-06 Nueva obra `/obras/nueva` (campos "Presupuesto (oferta técnica)" y "OC / OT / contrato…"; el mismo componente está en documentos y adicionales de la ficha)
- **Persona:** Diego
- **Pasos:** 1. En Nueva obra escribir el nombre del trabajo y el cliente. 2. Vence la sesión (cookies borradas). 3. Tocar "Subir archivo" (o "Pegar link" → "Agregar link").
- **Esperado:** lo mismo que al guardar: "Tu sesión venció. Volvé a ingresar." y, al volver, "Recuperamos lo que estabas escribiendo.".
- **Obtenido:** debajo del campo aparece "Tenés que ingresar para seguir." sin botón ni link; no lleva a Ingreso ni guarda borrador (`sessionStorage` vacío). Como la oferta es obligatoria, "Guardar obra" no pasa la validación ("Subí la oferta técnica o pegá su link.") y nunca llega al servidor: la única salida es irse (y "Salir" pierde todo). Con un link escrito, "Guardar obra" encima responde "Tocá «Agregar link» para sumar el link que pegaste.". Red: `POST /api/adjuntos → 401`. Reproducido 3 veces (archivo, link y link con el resto del formulario completo).
- **Evidencia:** `capturas/REG-05-adjunto-sesion-vencida.png` · scripts `reg/24b.cjs link|archivo`, `reg/24-borrador-alta.cjs`
- **Causa probable (opcional):** `src/shared/adjuntos/PegarLink.tsx:73` (`registrarLink`) y `AdjuntosField.tsx:97` (`subirArchivo`) llaman a la API directo, fuera del `QueryClient`, así que el 401 no pasa por `avisarSesionVencida()` ni invalida la sesión.
- **Regresión de:** PU-004 / ce05073 (arreglo incompleto)

### REG-06 · Ctrl+clic en "Ese N.º ya está en …" apaga el aviso de cambios sin guardar; en "Ver la ficha de …" cierra el alta y pierde lo escrito
- **Severidad:** media
- **Tipo:** funcional
- **F-XX / pantalla:** F-16 · W-06 Nueva obra `/obras/nueva` · W-38 Alta de proveedor `/proveedores`
- **Persona:** Diego
- **Pasos:** 1. Nueva obra con todo cargado y el N.º "2-504-2026" (ya usado) → "Guardar obra": "Ese N.º de presupuesto ya está en QA-REG Obra repetida.". 2. Ctrl+clic en "QA-REG Obra repetida." (para verla en otra pestaña). 3. Volver a la pestaña, cambiar el cliente y tocar "Proveedores" en la barra. — Proveedor: "+ Nuevo proveedor", nombre "qa-reg ferreteria uno" y un teléfono → "Guardar proveedor" ("Ya existe QA-REG Ferretería Uno.") → Ctrl+clic en "Ver la ficha de QA-REG Ferretería Uno".
- **Esperado:** Ctrl+clic abre la otra pestaña y deja todo como estaba: el formulario sigue protegido y el alta sigue abierta con lo escrito.
- **Obtenido:** obra: se abre `/obras/13` en otra pestaña, pero desde ahí el formulario ya no pregunta: el link de la barra lleva a `/proveedores` sin "¿Salir igual?" y lo cargado se pierde. Proveedor: se abre la ficha en otra pestaña y en la original el diálogo "Nuevo proveedor" se cierra con lo escrito, sin preguntar. (Clic del medio no tiene el problema.) Reproducido 2 veces cada uno.
- **Evidencia:** scripts `reg/02-repetida.cjs ctrl` ("[link barra tras ctrl+clic] url=/proveedores dialogos=[]") y `reg/05-proveedor.cjs repetido diego ctrl` ("diálogo abierto? 0")
- **Causa probable (opcional):** `ObraFormulario.tsx:130` llama `salida.permitirSalida()` en el `onClick` del link (corre también con Ctrl/Shift/Cmd) y nunca se vuelve a activar; `ProveedorDialog.tsx:131-132` además cierra el diálogo (`onCerrar`).
- **Regresión de:** PU-006 / f930579

### REG-07 · Irse con "Atrás" mientras guarda: lo dado por perdido o cancelado se guarda igual, sin aviso
- **Severidad:** media
- **Tipo:** funcional
- **F-XX / pantalla:** W-38 Editar proveedor `/proveedores/{id}` · F-19 · W-20 Agregar certificado o documento y F-21 · W-22 Cambiar estado `/obras/{id}`
- **Persona:** Diego (red lenta: 6 s en PATCH/POST)
- **Pasos:** 1. Proveedores → "QA-REG Conflicto Tres" → "Editar", cambiar el teléfono y "Guardar proveedor". 2. Mientras guarda, "Atrás" del navegador → aparece "Tenés cambios sin guardar. ¿Salir igual?" / "Si salís, se pierde lo que cambiaste." → "Salir". 3. Ficha de 2-504-2026 → "+ agregar certificado o documento" (o "Cambiar estado" → "Pasar a En ejecución") → confirmar y, mientras guarda, "Atrás".
- **Esperado:** mientras guarda no se puede salir, o se avisa el resultado ("Guardamos…") aunque la persona se haya ido. Nunca decir "se pierde" de algo que se guarda.
- **Obtenido:** proveedor: lleva a `/proveedores` con el diálogo "Editar proveedor" todavía abierto encima de la lista; a los segundos se cierra solo, sin mensaje, y el teléfono nuevo quedó guardado (base: "291 777-5596", el valor tipeado) aunque la pantalla dijo "se pierde lo que cambiaste". Documento y estado: Escape y clic afuera ya no cierran (✅), pero "Atrás" sale a `/obras`, el documento queda guardado (`obra_documentos` = 1) y la obra 2-501-2026 pasa a "en_ejecucion" sin ningún aviso: el caso de PU-003 por otra puerta. Reproducido 2 veces cada uno.
- **Evidencia:** `capturas/REG-07-salir-mientras-guarda.png` · scripts `reg/14-atras-guardando.cjs proveedor`, `reg/15-ficha-dialogos.cjs doc|estado`
- **Causa probable (opcional):** `useCambiosSinGuardar` no sabe que hay un guardado en curso (la pregunta y "Salir" funcionan igual); `DocumentoDialog`/`CambiarEstadoDialog` no frenan la navegación mientras `isPending`.
- **Regresión de:** PU-003 / ce05073 + 669cad2 (arreglo incompleto)

### REG-08 · Editar obra: tocar otra sección mientras guarda no pregunta y, al terminar, devuelve a la ficha
- **Severidad:** media
- **Tipo:** funcional
- **F-XX / pantalla:** F-20 · W-23 Editar obra · `/obras/{id}/editar`
- **Persona:** Diego (red lenta: 6 s en PATCH)
- **Pasos:** 1. Editar 2-501-2026, cambiar la planta y "Guardar cambios". 2. Mientras guarda, tocar "Proveedores" en la barra.
- **Esperado:** o se espera a que termine (pregunta/aviso), o se queda donde la persona eligió ir.
- **Obtenido:** va a Proveedores sin preguntar y, ~6 s después, la saca de ahí y la lleva a `/obras/10` con "Guardamos los cambios de la obra.". Reproducido 2 veces.
- **Evidencia:** `capturas/REG-08-vuelve-a-la-obra.png` · script `reg/14-atras-guardando.cjs obra`
- **Causa probable (opcional):** `ObraFormulario.tsx:88` hace `permitirSalida()` antes de enviar y `EditarObraPantalla.tsx` navega a la ficha después del `await`, aunque la persona ya se haya ido (lo mismo pasaría en Nueva obra).
- **Regresión de:** PU-006 / 669cad2

### REG-09 · Diálogos con cambios: "Cancelar" y Escape tiran lo escrito sin preguntar (y en `/proveedores/nuevo` sí pregunta)
- **Severidad:** baja
- **Tipo:** funcional
- **F-XX / pantalla:** W-38 Alta/edición de proveedor (`/proveedores`, `/proveedores/{id}`) · Editar usuario `/usuarios` · W-20 Agregar documento `/obras/{id}`
- **Persona:** Diego
- **Pasos:** 1. Proveedores → "+ Nuevo proveedor" (o "Editar" en una ficha, o un usuario en Usuarios, o "+ agregar certificado o documento"), escribir un teléfono/nombre. 2. Tocar "Cancelar" o Escape. 3. Comparar con `/proveedores/nuevo` → escribir → "Cancelar".
- **Esperado:** el mismo criterio en todos lados: si en un lugar "Cancelar" con cambios pregunta "Tenés cambios sin guardar. ¿Salir igual?", que pregunte en los demás diálogos (o en ninguno).
- **Obtenido:** en los diálogos se cierra al instante y se pierde lo escrito; abierto como `/proveedores/nuevo`, el mismo "Cancelar" del mismo diálogo pregunta "Tenés cambios sin guardar. ¿Salir igual?" (porque navega). Reproducido 2 veces (proveedor alta y edición, usuario, documento; Cancelar y Escape).
- **Evidencia:** scripts `reg/08-dialogo-cancelar.cjs`, `reg/25-escape-otros.cjs`, `reg/05-proveedor.cjs nuevo`
- **Causa probable (opcional):** `useCambiosSinGuardar` solo frena navegaciones; `Dialog.onCerrar` cierra sin mirar `isDirty`.
- **Regresión de:** PU-006 / ce05073 (inconsistencia nueva)

### REG-10 · Mi perfil pisa sin aviso lo que gerencia cambió en Usuarios
- **Severidad:** media
- **Tipo:** funcional
- **F-XX / pantalla:** F-10 Mi perfil `/perfil` · F-58 Usuarios
- **Persona:** QA-LEAD Vence (Compras) y Diego (Gerencia)
- **Pasos:** 1. QA-LEAD Vence abre Mi perfil. 2. Diego, en Usuarios, le carga el teléfono "291 333-2788" y guarda. 3. QA-LEAD Vence, sin recargar, cambia su nombre y toca guardar.
- **Esperado:** "Alguien cambió esto mientras lo tenías abierto. Actualizá y revisá." (como ya hace Usuarios desde PU-002), o mandar solo lo que cambió.
- **Obtenido:** "Guardamos tus datos." y el teléfono vuelve a vacío: el cambio de gerencia se perdió sin aviso. Reproducido 2 veces (teléfonos 291 333-2788 y 291 333-9168; el nombre se restauró después).
- **Evidencia:** `capturas/REG-10-perfil-pisa.png` · script `reg/21-perfil-pisa.cjs`
- **Causa probable (opcional):** `PerfilPantalla.tsx` manda nombre y teléfono juntos sin `updated_at` (c184727 agregó la versión solo al diálogo de usuario).
- **Regresión de:** PU-002 / c184727 (no cubierto)

### REG-11 · "Cancelar" mientras se agrega un link no lo cancela: el link aparece igual unos segundos después
- **Severidad:** baja
- **Tipo:** funcional
- **F-XX / pantalla:** F-16 · W-06 Nueva obra · "Presupuesto (oferta técnica)" (componente de adjuntos compartido)
- **Persona:** Diego (red lenta: 6 s en `POST /api/adjuntos`)
- **Pasos:** 1. "Pegar link" → `https://drive.example/oferta-reg-cancelar` → "Agregar link". 2. Mientras viaja, tocar "Cancelar" del link. 3. Esperar.
- **Esperado:** el link no se suma (o "Cancelar" queda deshabilitado mientras se agrega, como en los diálogos de PU-003).
- **Obtenido:** el cuadro vuelve a "Subir archivo · Pegar link" y "Guardar obra" se habilita; al llegar la respuesta aparece "drive.example (link, se abre en otra pestaña) quitar" en el campo. Reproducido 2 veces.
- **Evidencia:** `capturas/REG-11-link-cancelado-aparece.png` · script `reg/12-links.cjs cancelar`
- **Causa probable (opcional):** `PegarLink.tsx:73` llama `onAgregado` aunque el componente ya se desmontó por "Cancelar".
- **Regresión de:** PU-001 / ebac793 (zona tocada)

### REG-12 · Mientras se agrega un link el aviso habla de "subir los archivos"
- **Severidad:** baja
- **Tipo:** mensaje
- **F-XX / pantalla:** F-16 · W-06 Nueva obra / W-23 Editar obra
- **Persona:** Diego (red lenta)
- **Pasos:** 1. "Pegar link" → link → "Agregar link". 2. Mirar debajo de "Guardar obra" mientras viaja.
- **Esperado:** un texto que sirva para links, p. ej. "Esperá a que terminen de agregarse los archivos o links para guardar."
- **Obtenido:** "Esperá a que terminen de subir los archivos para guardar." (no hay ningún archivo subiendo). Reproducido 3 veces.
- **Evidencia:** script `reg/12-links.cjs` ("[enviando link] Guardar disabled=true … Esperá a que terminen de subir los archivos para guardar.")
- **Causa probable (opcional):** `MENSAJE_ESPERAR_SUBIDAS` en `AdjuntosField.tsx` se reusa para links desde ebac793.
- **Regresión de:** PU-001 / ebac793

### REG-13 · Editar obra: agregar solo espacios al final dice "Guardamos los cambios de la obra."
- **Severidad:** baja
- **Tipo:** mensaje
- **F-XX / pantalla:** F-20 · W-23 Editar obra
- **Persona:** Diego
- **Pasos:** 1. Editar 2-501-2026, agregar dos espacios al final del cliente. 2. "Guardar cambios".
- **Esperado:** "No había cambios para guardar." (el servidor recorta y no cambia nada: ni historial ni `updated_at`).
- **Obtenido:** sale `PATCH /api/obras/10 → 200` y "Guardamos los cambios de la obra.". Reproducido 2 veces.
- **Evidencia:** scripts `reg/18-sin-cambios.cjs`, `reg/19-espacios.cjs`
- **Causa probable (opcional):** `isDirty` compara el texto crudo, sin recortar.
- **Regresión de:** PU-029 / 669cad2 (borde)

## No probado
- **"Destrabar ingreso" y navegación desde el bloqueo:** no hay ningún bloqueo activo (`bloqueos_ingreso` vacío) y el bloqueo (PU-045) lo hace solo el Lead.
- **Ingreso real con otra persona en la misma pestaña (REG-03):** con 2 ingresos autorizados (ambos usados en el vencimiento real con QA-LEAD Vence), el cambio de persona se simuló cargando la sesión guardada de Carla/Hernán en la misma pestaña; es lo mismo que ve quien ingresa y vuelve a la ruta recordada.
- **Dos guardados en el mismo segundo:** límite aceptado (PU-002).
- **Borradores en usuario, Mi perfil, permisos, documentos y adicionales:** no existen por diseño (solo obra y proveedor tienen borrador); lo dejo anotado para PU-004 ("aplica a todo formulario"), lo verifica el Verificador.
- **iPhone SE:** solo Pixel 7 (lo cubre el QA de celular).
- Observación de una sola vez, no reproducida (no se reporta): en una corrida, un `PATCH /api/obras/12` salió 200 justo después de borrar las cookies; probablemente una respuesta en vuelo volvió a poner la cookie de sesión. Las 6 corridas siguientes dieron 401.
