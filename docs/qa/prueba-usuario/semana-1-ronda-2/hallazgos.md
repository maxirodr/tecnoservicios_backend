# Hallazgos · Prueba de usuario Semana 1 · Ronda 2 · Tecnoservicios (NS-2026-025)

- **Fecha:** 2026-11-17
- **Entorno:** base `tecnoservicios_prueba_usuario` · front `http://localhost:5316` (`f930579`) · API `http://localhost:8340` (`c27e382`)
- **Alcance:** solo lo nuevo de la ronda 2 (regresiones de los arreglos y cosas que la ronda 1 no vio). El estado de PU-001…PU-045 está en `verificacion.md`.
- **Conteo:** 39 hallazgos · bloqueante 1 · alta 1 · media 11 · baja 26 · 23 regresiones (línea **Regresión de:** con PU/commit) y 2 arreglos sin terminar marcados "no es regresión" (PU-103, PU-117)
- **Próximo número libre:** **PU-140** (PU-138 y PU-139 los sumó el Lead al final)
- **Orden:** por severidad (bloqueante, alta, media, baja) y, dentro de cada una, por tipo. Al final de cada **Evidencia**, `origen:` lista los hallazgos de `borradores\` (VA, VB, REG, PER, ENT, UXM) que se consolidaron. Las capturas `PU-1XX-n.png` son copias de las originales de cada rol (que siguen en `capturas\`).
- **QC:** las causas de PU-101, PU-102, PU-103 y PU-111 se releyeron en el código y se sostienen (ver `borradores\consolidacion.md` §(f)).

### PU-101 · Editar obra: la Revisión (y demás datos del presupuesto principal) que guardó otra persona se pisa sin aviso
- **Severidad:** bloqueante
- **Tipo:** funcional
- **F-XX / pantalla:** F-20 · W-23 Editar obra · `/obras/{id}/editar` (campos del presupuesto principal: N.º, Revisión, descripción, oferta, documento de aprobación)
- **Persona:** Diego (Gerencia de Administración), dos pestañas
- **Pasos:** 1. Abrir "Editar" de la obra 2-502-2026 en dos pestañas (A y B). 2. En A cambiar "Revisión" a "REV25" y "Guardar cambios". 3. En B, sin recargar, cambiar "Revisión" a "REV35" y "Guardar cambios".
- **Esperado:** B recibe "Alguien cambió esto mientras lo tenías abierto. Actualizá y revisá." con "Actualizar" (decisión de Juani sobre PU-002), igual que cuando chocan los datos de la obra.
- **Obtenido:** B ve "Guardamos los cambios de la obra." y la base queda en "REV35": el cambio de A se perdió sin aviso. Red: `PATCH /api/presupuestos/11 → 200` y `PATCH /api/obras/11 → 200` (editar el presupuesto no cambia el `updated_at` de la obra, así que nunca hay 409). Reproducido 2 veces (REV25→REV35 y REV2x→REV32).
- **Evidencia:** `capturas/PU-101-1.png` · script `reg/03-conflicto-obra.cjs s2` · origen: REG-01
- **Causa probable (opcional):** `tecnoservicios_frontend/src/features/obras/hooks/useObras.ts:89-91` manda `editarPresupuesto(principal.id, principal.cambios)` sin versión; el control de versión de `fbffdca` está solo en `ActualizarObraAction` (`PATCH /api/obras`); `app/Modules/Obras/Application/Presupuestos/ActualizarPresupuestoAction.php` no controla versión ni toca la obra. (QC: verificado en el código.)
- **Regresión de:** PU-002 / back `fbffdca` + front `669cad2` (arreglo incompleto)

### PU-102 · Editar obra con conflicto: dice "se pierde lo que cambiaste acá", pero la Revisión ya quedó guardada
- **Severidad:** alta
- **Tipo:** funcional
- **F-XX / pantalla:** F-20 · W-23 Editar obra · `/obras/{id}/editar`
- **Persona:** Diego (Gerencia de Administración), dos pestañas
- **Pasos:** 1. Abrir "Editar" de 2-502-2026 en A y B. 2. En A cambiar "Cliente" y guardar. 3. En B cambiar "Revisión" a "REV45" y "Planta" a "Planta B…" y tocar "Guardar cambios". 4. Tocar "Actualizar".
- **Esperado:** con el aviso de conflicto no se guarda nada de B (o la pantalla dice qué parte sí se guardó).
- **Obtenido:** B ve "Alguien cambió esto mientras lo tenías abierto. Actualizá y revisá." y "Al actualizar vas a ver lo que está guardado ahora y se pierde lo que cambiaste acá.", pero la base ya tiene "REV45" (la planta no). Después de "Actualizar" el formulario muestra "REV45": quedó guardado a medias, contra lo que dijo la pantalla. Red: `PATCH /api/presupuestos/11 → 200` y después `PATCH /api/obras/11 → 409`. Reproducido 2 veces (REV45, REV46).
- **Evidencia:** `capturas/PU-102-1.png` · script `reg/03-conflicto-obra.cjs s3` · origen: REG-02
- **Causa probable (opcional):** misma secuencia de `useObras.ts:89-92`: el presupuesto se guarda (y se confirma) antes de saber si la obra tiene conflicto; no hay transacción entre los dos `PATCH`. Probablemente se arregla junto con PU-101. (QC: verificado en el código.)
- **Regresión de:** PU-002 / back `fbffdca` + front `669cad2`

### PU-103 · Mientras se guarda un documento se puede "quitar" un archivo, y se guarda igual
- **Severidad:** media
- **Tipo:** funcional
- **F-XX / pantalla:** F-19 · W-20 Agregar certificado o documento · `/obras/{id}`
- **Persona:** Laura (Ingeniería de proyectos)
- **Pasos:** 1. Con la red lenta (8 s), en la obra 2-303-2026 tocar "+ agregar certificado o documento", completar fecha y nombre y subir `uno.pdf` y `dos.pdf`. 2. Tocar "Guardar" (queda con spinner y "Cancelar" deshabilitado). 3. Mientras guarda, tocar "quitar" en `dos.pdf`.
- **Esperado:** como con "Cancelar" (arreglo de PU-003), mientras guarda no se puede cambiar lo que se está mandando: "quitar", "Subir archivo", "Pegar link" y los campos quedan deshabilitados.
- **Obtenido:** "quitar" responde: `dos.pdf` desaparece y el campo muestra solo "uno.pdf · 1 KB", pero el documento "QA-VA quitar mientras guarda 1" queda guardado con `uno.pdf` y `dos.pdf`. La persona cree que sacó un archivo que quedó en la obra (el problema de fondo de PU-003). "Subir archivo" y "Pegar link" también siguen activos durante el guardado. Reproducido 2 veces.
- **Evidencia:** `capturas/PU-103-1.png` · `capturas/PU-103-2.png` · `GET /api/obras/3` → documentos "QA-VA quitar mientras guarda 1/2" con `["uno.pdf","dos.pdf"]` · script `va/pu005.cjs` (2.ª parte) · origen: VA-03
- **Causa probable (opcional):** `tecnoservicios_frontend/src/features/obras/components/ficha/DocumentoDialog.tsx:128` no le pasa `guardar.isPending` a `AdjuntosField`, y `AdjuntosFieldProps` (`src/shared/adjuntos/AdjuntosField.tsx:11-25`) no tiene ninguna prop para deshabilitarse. (QC: verificado en el código.)
- **Regresión de:** no es regresión: arreglo incompleto de PU-003 (front `669cad2` deshabilitó "Cancelar", Escape y el cierre, no el contenido del diálogo)

### PU-104 · Irse mientras se guarda: la pantalla dice "se pierde" (o no dice nada) y el cambio se guarda igual; Editar obra encima te devuelve a la ficha
- **Severidad:** media
- **Tipo:** funcional
- **F-XX / pantalla:** W-38 Editar proveedor `/proveedores/{id}` · F-19 · W-20 Agregar certificado o documento y F-21 · W-22 Cambiar estado `/obras/{id}` · F-20 · W-23 Editar obra `/obras/{id}/editar` (por código, también W-06 Nueva obra)
- **Persona:** Diego (Gerencia de Administración), red lenta (6 s en PATCH/POST)
- **Pasos:** 1. Proveedores → "QA-REG Conflicto Tres" → "Editar", cambiar el teléfono y "Guardar proveedor"; mientras guarda, "Atrás" del navegador → "Tenés cambios sin guardar. ¿Salir igual?" / "Si salís, se pierde lo que cambiaste." → "Salir". 2. Ficha de 2-504-2026 → "+ agregar certificado o documento" (o "Cambiar estado" → "Pasar a En ejecución") → confirmar y, mientras guarda, "Atrás". 3. Editar 2-501-2026, cambiar la planta y "Guardar cambios"; mientras guarda, tocar "Proveedores" en la barra.
- **Esperado:** mientras guarda no se puede salir, o se avisa el resultado ("Guardamos…") aunque la persona se haya ido, y se la deja donde eligió ir. Nunca decir "se pierde" de algo que se guarda.
- **Obtenido:** proveedor: lleva a `/proveedores` con el diálogo "Editar proveedor" todavía abierto encima de la lista; a los segundos se cierra solo, sin mensaje, y el teléfono nuevo quedó guardado ("291 777-5596") aunque la pantalla dijo "se pierde lo que cambiaste". Documento y estado: "Atrás" sale a `/obras`, el documento queda guardado y la obra 2-501-2026 pasa a "En ejecución" sin ningún aviso (el caso de PU-003 por otra puerta). Editar obra: va a Proveedores sin preguntar y, ~6 s después, la saca de ahí y la lleva a `/obras/10` con "Guardamos los cambios de la obra.". Reproducido 2 veces cada caso.
- **Evidencia:** `capturas/PU-104-1.png` (proveedor) · `capturas/PU-104-2.png` (vuelta a la obra) · scripts `reg/14-atras-guardando.cjs proveedor|obra`, `reg/15-ficha-dialogos.cjs doc|estado` · origen: REG-07, REG-08
- **Causa probable (opcional):** `useCambiosSinGuardar` no sabe que hay un guardado en curso (la pregunta y "Salir" funcionan igual); `DocumentoDialog`/`CambiarEstadoDialog` no frenan la navegación mientras `isPending`; `ObraFormulario.tsx:88` hace `permitirSalida()` antes de enviar y `EditarObraPantalla.tsx` navega a la ficha después del `await` aunque la persona ya se haya ido.
- **Regresión de:** PU-003 / PU-006 · front `ce05073` + `669cad2` (arreglo incompleto)

### PU-105 · El borrador recuperado de una persona le aparece a otra que entra en la misma pestaña
- **Severidad:** media
- **Tipo:** funcional
- **F-XX / pantalla:** F-01 sesión vencida · W-38 Alta de proveedor `/proveedores` · F-20 · W-23 Editar obra `/obras/{id}/editar`
- **Persona:** Diego → Carla / QA-LEAD Vence (proveedor); Diego → Hernán (obra)
- **Pasos:** 1. Diego abre "+ Nuevo proveedor", escribe "QA-REG Borrador de Diego" y el mail "diego-privado@empresa-ejemplo.com". 2. La sesión vence y toca "Guardar proveedor": "Tu sesión venció. Volvé a ingresar.". 3. En esa misma pestaña entra otra persona (Carla) y va a Proveedores. (Igual con Editar obra 2-503-2026: Diego escribe, vence, entra Hernán.)
- **Esperado:** el borrador es de quien lo escribió; otra persona ve el formulario limpio.
- **Obtenido:** Carla ve el alta abierta con "Recuperamos lo que estabas escribiendo." y el nombre y el mail que escribió Diego; si guarda, queda a su nombre. Igual con QA-LEAD Vence, y con Hernán en Editar obra (cliente "QA-REG Cliente borrador …" y planta de Diego). Reproducido 2 veces en proveedores y 4 en obra.
- **Evidencia:** `capturas/PU-105-1.png` (proveedor) · `capturas/PU-105-2.png` (obra) · scripts `reg/11-borrador-persona.cjs`, `reg/04-sesion-obra.cjs hernan` (el cambio de persona se simuló cargando la sesión guardada de la otra persona en la misma pestaña, sin gastar ingresos) · origen: REG-03
- **Causa probable (opcional):** `src/shared/forms/useBorrador.ts:8` (`PREFIJO = 'tecnoservicios:borrador:'`) + ruta/registro, sin el usuario; "Salir" tampoco limpia `sessionStorage`.
- **Regresión de:** PU-004 / front `ce05073`

### PU-106 · Si la sesión vence al subir un archivo o agregar un link, queda "Tenés que ingresar para seguir." sin salida y sin borrador
- **Severidad:** media
- **Tipo:** funcional
- **F-XX / pantalla:** F-16 · W-06 Nueva obra `/obras/nueva` ("Presupuesto (oferta técnica)" y "OC / OT / contrato…"); el mismo componente está en W-20 documentos, W-21 adicionales y W-23
- **Persona:** Diego (Gerencia de Administración)
- **Pasos:** 1. En Nueva obra escribir el nombre del trabajo y el cliente. 2. Vence la sesión. 3. Tocar "Subir archivo" (o "Pegar link" → "Agregar link").
- **Esperado:** lo mismo que al guardar: "Tu sesión venció. Volvé a ingresar." y, al volver, "Recuperamos lo que estabas escribiendo.".
- **Obtenido:** debajo del campo aparece "Tenés que ingresar para seguir." sin botón ni link; no lleva a Ingreso ni guarda borrador. Como la oferta es obligatoria, "Guardar obra" se frena en "Subí la oferta técnica o pegá su link." y nunca llega al servidor: la única salida es irse, y "Salir" pierde todo. Con un link escrito, "Guardar obra" responde "Tocá «Agregar link» para sumar el link que pegaste.". Red: `POST /api/adjuntos → 401`. Reproducido 3 veces (archivo, link, link con el formulario completo).
- **Evidencia:** `capturas/PU-106-1.png` · scripts `reg/24b.cjs link|archivo`, `reg/24-borrador-alta.cjs` · origen: REG-05
- **Causa probable (opcional):** `src/shared/adjuntos/PegarLink.tsx:73` (`registrarLink`) y `AdjuntosField.tsx:97` (`subirArchivo`) llaman a la API fuera del `QueryClient`, así que el 401 no pasa por `avisarSesionVencida()`.
- **Regresión de:** PU-004 / front `ce05073` (arreglo incompleto)

### PU-107 · Ctrl+clic en "Ese N.º ya está en …" apaga el aviso de cambios sin guardar; en "Ver la ficha de …" cierra el alta y pierde lo escrito
- **Severidad:** media
- **Tipo:** funcional
- **F-XX / pantalla:** F-16 · W-06 Nueva obra `/obras/nueva` · W-38 Alta de proveedor `/proveedores`
- **Persona:** Diego (Gerencia de Administración)
- **Pasos:** 1. Nueva obra con todo cargado y el N.º "2-504-2026" (ya usado) → "Guardar obra": "Ese N.º de presupuesto ya está en QA-REG Obra repetida.". 2. Ctrl+clic en "QA-REG Obra repetida." (para verla en otra pestaña). 3. Volver a la pestaña, cambiar el cliente y tocar "Proveedores" en la barra. 4. Proveedor: "+ Nuevo proveedor", nombre "qa-reg ferreteria uno" y un teléfono → "Guardar proveedor" ("Ya existe QA-REG Ferretería Uno.") → Ctrl+clic en "Ver la ficha de QA-REG Ferretería Uno".
- **Esperado:** Ctrl+clic abre la otra pestaña y deja todo como estaba: el formulario sigue protegido y el alta sigue abierta con lo escrito.
- **Obtenido:** obra: se abre `/obras/13` en otra pestaña, pero el formulario ya no pregunta: el link de la barra lleva a `/proveedores` sin "¿Salir igual?" y lo cargado se pierde. Proveedor: se abre la ficha en otra pestaña y en la original el diálogo "Nuevo proveedor" se cierra con lo escrito, sin preguntar. (El clic del medio no tiene el problema.) Reproducido 2 veces cada uno.
- **Evidencia:** sin captura · scripts `reg/02-repetida.cjs ctrl` ("[link barra tras ctrl+clic] url=/proveedores dialogos=[]") y `reg/05-proveedor.cjs repetido diego ctrl` ("diálogo abierto? 0") · origen: REG-06
- **Causa probable (opcional):** `ObraFormulario.tsx:130` llama `salida.permitirSalida()` en el `onClick` del link (corre también con Ctrl/Shift/Cmd) y no se vuelve a activar; `ProveedorDialog.tsx:131-132` además cierra el diálogo (`onCerrar`).
- **Regresión de:** PU-006 / front `f930579`

### PU-108 · Mi perfil pisa sin aviso lo que gerencia cambió en Usuarios
- **Severidad:** media
- **Tipo:** funcional
- **F-XX / pantalla:** F-10 Mi perfil `/perfil` · F-58 Usuarios
- **Persona:** QA-LEAD Vence (Compras) y Diego (Gerencia de Administración)
- **Pasos:** 1. QA-LEAD Vence abre Mi perfil. 2. Diego, en Usuarios, le carga el teléfono "291 333-2788" y guarda. 3. QA-LEAD Vence, sin recargar, cambia su nombre y toca "Guardar".
- **Esperado:** "Alguien cambió esto mientras lo tenías abierto. Actualizá y revisá." (como ya hace Usuarios desde PU-002), o mandar solo lo que cambió.
- **Obtenido:** "Guardamos tus datos." y el teléfono vuelve a vacío: el cambio de gerencia se perdió sin aviso. Reproducido 2 veces (teléfonos 291 333-2788 y 291 333-9168; el nombre se restauró después).
- **Evidencia:** `capturas/PU-108-1.png` · script `reg/21-perfil-pisa.cjs` · origen: REG-10
- **Causa probable (opcional):** `PerfilPantalla.tsx` manda nombre y teléfono juntos sin `updated_at` (front `c184727` agregó la versión solo al diálogo de usuario).
- **Regresión de:** PU-002 / front `c184727` (no cubierto)

### PU-109 · El borrador se descarta en silencio si la obra cambió mientras la sesión estaba vencida
- **Severidad:** media
- **Tipo:** mensaje
- **F-XX / pantalla:** F-20 · W-23 Editar obra · `/obras/{id}/editar`
- **Persona:** Diego (Gerencia de Administración)
- **Pasos:** 1. En "Editar" de 2-503-2026 escribir cliente "QA-REG Cliente borrador …" y planta "Planta borrador". 2. Vence la sesión y toca "Guardar cambios" ("Tu sesión venció. Volvé a ingresar."). 3. Mientras tanto otra persona guarda la obra (planta "Cambio de otro"). 4. Volver a entrar y abrir la misma edición.
- **Esperado:** si lo escrito no se puede recuperar, decirlo (p. ej. "Mientras no estabas alguien cambió la obra; lo que habías escrito no se pudo recuperar.") o mostrarlo para copiarlo.
- **Obtenido:** el formulario aparece con lo guardado por la otra persona, sin "Recuperamos…" ni ningún aviso; lo escrito desapareció. Es el "se pierde lo escrito y no se dice por qué" de PU-004. Reproducido 2 veces.
- **Evidencia:** `capturas/PU-109-1.png` · script `reg/04-sesion-obra.cjs viejo` · origen: REG-04
- **Causa probable (opcional):** `src/shared/forms/useBorrador.ts:86` devuelve `null` si la versión no coincide y el efecto de montaje lo borra, sin avisar.
- **Regresión de:** PU-004 / front `ce05073`

### PU-110 · El aviso de proveedor repetido sigue tratando la ñ como n y contradice al guardado
- **Severidad:** media
- **Tipo:** mensaje
- **F-XX / pantalla:** F-56 · W-38 Nuevo proveedor · `/proveedores` (aviso "Ya está cargado" mientras se escribe)
- **Persona:** Carla (Compras)
- **Pasos:** 1. Dar de alta "QA-VB Peña Hnos". 2. "+ Nuevo proveedor" y escribir "QA-VB Pena Hnos". 3. Mirar el aviso bajo Nombre y tocar "Guardar proveedor". 4. Repetir con "QA-VB Muñoz e Hijos" / "QA-VB Munoz e Hijos" y "QA-VB Corralón Ñandú S.R.L." / "QA-VB Corralon Nandu". 5. Escribir otra vez "QA-VB Peña Hnos" (el repetido de verdad) y guardar.
- **Esperado:** decisión de Juani para PU-043 (DU-Q04): la ñ no es n, "Pena" y "Peña" son parecidos → "Parecidos ya cargados: QA-VB Peña Hnos · Si es otro proveedor, podés guardar igual."; "Ya está cargado" solo para el mismo nombre de verdad.
- **Obtenido:** en el paso 2 sale "Ya está cargado: QA-VB Peña Hnos. Abrí su ficha." (sin "podés guardar igual"), pero al guardar lo da de alta: "Dimos de alta a QA-VB Pena Hnos.". Igual con "Ya está cargado: QA-VB Muñoz e Hijos. Abrí su ficha." y "Ya está cargado: QA-VB Corralón Ñandú S.R.L. Abrí su ficha.". En el paso 5, con los dos cargados, el aviso apunta al proveedor equivocado: "Ya está cargado: QA-VB Pena Hnos. Abrí su ficha." y, después de guardar, "Ya existe QA-VB Peña Hnos." · "Ver la ficha de QA-VB Peña Hnos.": dos proveedores distintos para el mismo choque. Quien le cree al aviso no carga el proveedor nuevo o abre la ficha de otro. Reproducido 4 veces.
- **Evidencia:** `capturas/PU-110-1.png` (Corralon Nandu) · `capturas/PU-110-2.png` (Pena Hnos) · `capturas/PU-110-3.png` (Munoz e Hijos) · `capturas/PU-110-4.png` (Peña apunta a Pena) · `GET /api/proveedores/parecidos` devuelve los candidatos con `motivo: "nombre"` (bien); `POST /api/proveedores` → 201 · origen: VB-01
- **Causa probable (opcional):** `tecnoservicios_frontend/src/features/proveedores/dominio.ts:28-31` `claveDeNombre()` hace `normalize('NFD')` y quita diacríticos, así que "ñ" → "n"; el backend (`8b3490f`) ya conserva la ñ en la clave. `Parecidos.tsx:39-43` usa esa clave para decidir "Ya está cargado". (QC: verificado en el código.)
- **Regresión de:** PU-043 / back `8b3490f` (el backend cambió la clave; el front quedó con la vieja)

### PU-111 · Al guardar, el aviso de conflicto o de error aparece arriba, fuera de la vista: parece que no pasó nada
- **Severidad:** media
- **Tipo:** mensaje
- **F-XX / pantalla:** F-20 · W-23 Editar obra `/obras/{id}/editar` (409 de conflicto y 500) · F-16 · W-06 Nueva obra `/obras/nueva` (500) · PC 1366×768 e iPhone SE
- **Persona:** Hernán (Jefatura de Proyectos)
- **Pasos:** 1. Abrir Editar obra de 2-802-2026 en dos pestañas. 2. En la segunda, cambiar "Planta (si aplica)" y guardar. 3. En la primera, cambiar la planta, bajar hasta "Guardar cambios" (al pie) y tocarlo. 4. Repetir con un error del servidor (500 simulado en el navegador) en Editar obra y en Nueva obra, en PC y en iPhone SE.
- **Esperado:** el aviso se ve donde está mirando la persona (la pantalla sube hasta el aviso o el foco va a él), como ya pasa con los errores de campo, que llevan el foco al primer campo con error.
- **Obtenido:** el botón deja de girar y vuelve a "Guardar cambios"; en pantalla no cambia nada más. El aviso "Alguien cambió esto mientras lo tenías abierto. Actualizá y revisá. Al actualizar vas a ver lo que está guardado ahora y se pierde lo que cambiaste acá. Actualizar" queda 610 px por encima de lo visible (PC); "El sistema tuvo un problema. Volvé a intentar en un momento." queda a −550 px (Editar, PC), −278 px (Nueva, PC), −1.823 px y −1.664 px (iPhone SE). Parece que se guardó o que no pasó nada. Con teclado, el foco queda en `body` y "Actualizar" está a 29 Shift+Tab. Reproducido 3 veces el 409 y 2 veces el 500.
- **Evidencia:** `capturas/PU-111-1.png` (conflicto) · `capturas/PU-111-2.png` · `capturas/PU-111-3.png` · `capturas/PU-111-4.png` · `capturas/PU-111-5.png` · `capturas/PU-111-6.png` · `PATCH /api/obras/20 → 409` · `uxm/out-teclado4.txt`, `uxm/error-vista.cjs` · origen: UXM-01
- **Causa probable (opcional):** `src/features/obras/components/formulario/ObraFormulario.tsx:118-125` pinta `AvisoConflicto` y el aviso general al principio del formulario sin `scrollIntoView` ni mover el foco (no hay ningún `scrollIntoView` en el front). Mismo patrón en `identidad/components/UsuarioFormulario.tsx:125-134` (no medido). (QC: verificado en el código.)
- **Regresión de:** PU-002 / front `669cad2` (el aviso de conflicto nuevo nace fuera de la vista; el del 500 ya era así)

### PU-112 · Los links se muestran solo con el dominio: varios links del Drive se ven todos iguales
- **Severidad:** media
- **Tipo:** mensaje
- **F-XX / pantalla:** F-19 · W-20 Certificados y otros documentos (tabla y "editar"); W-20 Presupuestos (oferta técnica); W-06/W-21 lista de adjuntos al cargar (mismo componente)
- **Persona:** Diego (Gerencia de Administración)
- **Pasos:** 1. "+ agregar certificado o documento" → pegar `https://drive.example/oferta-ñandú` y `http://drive.example/sin-s` sin "Nombre para mostrar" → "Guardar". 2. Mirar la fila en "Certificados y otros documentos" y abrir "editar".
- **Esperado:** cada link se reconoce sin abrirlo: el nombre del archivo o la última parte del link ("oferta-ñandú", "sin-s") o, si no, "Link 1", "Link 2"; el link completo al pasar el mouse.
- **Obtenido:** las dos filas dicen igual: "drive.example (link, se abre en otra pestaña)" · "drive.example (link, se abre en otra pestaña)", sin `title`. Con links reales todos van a decir "drive.google.com" y, para quitar uno, hay que adivinar cuál es. "Nombre para mostrar" es opcional y nada avisa que conviene completarlo. Visto 2 veces (ficha y diálogo "editar").
- **Evidencia:** `capturas/PU-112-1.png` · `capturas/PU-112-2.png` · `ent/links-dominio.cjs` · origen: ENT-03

### PU-113 · Montos con centavos se muestran con un solo decimal ("1.500.000,5")
- **Severidad:** baja
- **Tipo:** funcional
- **F-XX / pantalla:** F-48 · W-13 Presupuesto por rubro · `/obras/{id}/presupuesto-por-rubro` (PC y celular); también el historial de la obra
- **Persona:** Diego (Gerencia de Administración)
- **Pasos:** 1. En la obra 2-302-2026 escribir `10,50` en MATERIALES y `1.500.000,50` en SUBCONTRATOS. 2. Mirar los totales. 3. Tocar "Guardar" y recargar.
- **Esperado:** pesos con dos decimales: "10,50", "1.500.000,50" (o sin decimales si son cero), como se escribió.
- **Obtenido:** antes de guardar, "Total de la obra" muestra "10,5" y "1.500.000,5". Después de "Guardamos el presupuesto por rubro." y F5, las cajas muestran "10,5" y "1.500.000,5". Igual en el celular ("Total de la obra · 10,5") y en el historial ("12.650.000,5"). Para administración, "1.500.000,5" se lee como un monto mal cargado.
- **Evidencia:** `capturas/PU-113-1.png` · `capturas/PU-113-2.png` (fila SUBCONTRATOS "1.500.000,5") · `capturas/PU-113-3.png` (celular) · script `va/decimales.cjs` · origen: VA-02 (+ nota de PER)
- **Causa probable (opcional):** `src/shared/dominio/importes.ts:6` `new Intl.NumberFormat('es-AR', { maximumFractionDigits: 2 })` sin `minimumFractionDigits: 2` cuando hay centavos (QC: verificado en el código).

### PU-114 · Diálogos con cambios: "Cancelar" y Escape tiran lo escrito sin preguntar (y en `/proveedores/nuevo` sí pregunta)
- **Severidad:** baja
- **Tipo:** funcional
- **F-XX / pantalla:** W-38 Alta/edición de proveedor (`/proveedores`, `/proveedores/{id}`) · F-58 Editar usuario `/usuarios` · F-19 · W-20 Agregar documento `/obras/{id}`
- **Persona:** Diego (Gerencia de Administración)
- **Pasos:** 1. Proveedores → "+ Nuevo proveedor" (o "Editar" en una ficha, o un usuario en Usuarios, o "+ agregar certificado o documento") y escribir algo. 2. Tocar "Cancelar" o Escape. 3. Comparar con `/proveedores/nuevo` → escribir → "Cancelar".
- **Esperado:** el mismo criterio en todos lados: si en un lugar "Cancelar" con cambios pregunta "Tenés cambios sin guardar. ¿Salir igual?", que pregunte en los demás diálogos (o en ninguno).
- **Obtenido:** en los diálogos se cierra al instante y se pierde lo escrito; abierto como `/proveedores/nuevo`, el mismo "Cancelar" del mismo diálogo pregunta "Tenés cambios sin guardar. ¿Salir igual?" (porque navega). Reproducido 2 veces (proveedor alta y edición, usuario, documento; Cancelar y Escape).
- **Evidencia:** sin captura · scripts `reg/08-dialogo-cancelar.cjs`, `reg/25-escape-otros.cjs`, `reg/05-proveedor.cjs nuevo` · origen: REG-09
- **Causa probable (opcional):** `useCambiosSinGuardar` solo frena navegaciones; `Dialog.onCerrar` cierra sin mirar `isDirty`.
- **Regresión de:** PU-006 / front `ce05073` (inconsistencia nueva)

### PU-115 · "Cancelar" mientras se agrega un link no lo cancela: el link aparece igual unos segundos después
- **Severidad:** baja
- **Tipo:** funcional
- **F-XX / pantalla:** F-16 · W-06 Nueva obra · "Presupuesto (oferta técnica)" (componente de adjuntos compartido)
- **Persona:** Diego (Gerencia de Administración), red lenta (6 s en `POST /api/adjuntos`)
- **Pasos:** 1. "Pegar link" → `https://drive.example/oferta-reg-cancelar` → "Agregar link". 2. Mientras viaja, tocar "Cancelar" del link. 3. Esperar.
- **Esperado:** el link no se suma (o "Cancelar" queda deshabilitado mientras se agrega, como en los diálogos de PU-003).
- **Obtenido:** el cuadro vuelve a "Subir archivo · Pegar link" y "Guardar obra" se habilita; al llegar la respuesta aparece "drive.example (link, se abre en otra pestaña) quitar" en el campo. Reproducido 2 veces.
- **Evidencia:** `capturas/PU-115-1.png` · script `reg/12-links.cjs cancelar` · origen: REG-11
- **Causa probable (opcional):** `PegarLink.tsx:73` llama `onAgregado` aunque el componente ya se desmontó por "Cancelar".
- **Regresión de:** PU-001 / front `ebac793` (zona tocada)

### PU-116 · Proveedor con CUIT y mail mal: primero avisa solo el mail y el CUIT recién en el segundo intento
- **Severidad:** baja
- **Tipo:** validación
- **F-XX / pantalla:** F-56 · W-38 Nuevo proveedor · `/proveedores` → "+ Nuevo proveedor"
- **Persona:** Carla (Compras)
- **Pasos:** 1. Nombre "QA-PER Prueba dos errores", CUIT `30-71600601-5` (dígito verificador mal), mail `ventas@`. 2. "Guardar proveedor". 3. Corregir el mail y volver a guardar.
- **Esperado:** los dos errores juntos en la primera vuelta.
- **Obtenido:** vuelta 1: solo "Escribí un mail válido." (no sale pedido a la API). Vuelta 2: "El CUIT no es válido. Revisá los números." (422 de la API). El front valida el largo del CUIT ("El CUIT tiene 11 números, por ejemplo 30-00000000-7.") pero no el dígito verificador. Reproducido 2 veces.
- **Evidencia:** `capturas/PU-116-1.png` (vuelta 1) · `capturas/PU-116-2.png` (vuelta 2) · script `per/41-prov-alta.cjs` · origen: PER-05

### PU-117 · Fechas muy lejanas (1900, 2099, 2199) se aceptan sin aviso en certificados y en fechas previstas de la obra
- **Severidad:** baja
- **Tipo:** validación
- **F-XX / pantalla:** F-19 · W-20 Certificados y otros documentos · `/obras/{id}` · F-16 · W-06 Nueva obra y F-20 · W-23 Editar obra (Inicio previsto / Fin previsto)
- **Persona:** Diego (Gerencia de Administración)
- **Pasos:** 1. "+ agregar certificado o documento" → Certificado, fecha 31/12/2099 → "Guardar". 2. Nueva obra completa con Inicio previsto 01/01/1900 y Fin previsto 31/12/2199 → "Guardar obra". 3. Editar esa obra y volver a guardar.
- **Esperado:** avisar una fecha tan lejana (ronda 1, PU-028: "¿Seguro? La fecha es de 2099."; o "Revisá la fecha: es de 1900."), con el mismo criterio que el N.º de presupuesto ("El año tiene que estar entre 2000 y 2027."). Un año mal tipeado es lo más común.
- **Obtenido:** el certificado se guarda sin preguntar; la tabla ya muestra el año ("31-dic-2099", esa parte de PU-028 quedó). La obra se guarda y la ficha muestra "Fechas previstas 01/01/1900 → 31/12/2199"; al editarla se vuelve a guardar sin aviso.
- **Evidencia:** `capturas/PU-117-1.png` (certificado sin aviso) · `capturas/PU-117-2.png` (tabla "31-dic-2099") · `capturas/PU-117-3.png` (obra 1900→2199) · `ent/obra-alta2.cjs` · origen: VB (nota de PU-028), ENT-05
- **Regresión de:** no es regresión: la parte "aviso de fecha lejana" de PU-028 no se hizo (no hay decisión registrada que la descarte); en fechas previstas no estaba reportado

### PU-118 · Un cliente de 101 a 150 letras hace fallar en silencio las sugerencias (422 en la red)
- **Severidad:** baja
- **Tipo:** validación
- **F-XX / pantalla:** F-16 · W-06 Nueva obra / F-20 · W-23 Editar obra (campo "Cliente *")
- **Persona:** Diego (Gerencia de Administración)
- **Pasos:** 1. Escribir en Cliente un nombre de más de 100 caracteres (el campo acepta hasta 150).
- **Esperado:** las sugerencias no se piden pasado el largo de búsqueda (como quedó en proveedores con PU-038), o el backend acepta hasta 150.
- **Obtenido:** `GET /api/obras/clientes?buscar=…` → 422 `"Es demasiado largo (máximo 100 caracteres)."` en la consola; la persona no ve nada. No llega al canal `errors`. Reproducido 2 veces (310 caracteres desde la pantalla y 151 por API).
- **Evidencia:** sin captura · `ent/obra-alta.cjs` (red: `GET /api/obras/clientes?buscar=ÁÁÁ… → 422`), `ent/explorar.cjs` · origen: ENT-10
- **Regresión de:** PU-038 (no cubierto: el mismo caso en otra pantalla)

### PU-119 · Proveedor repetido: al guardar se ven hasta tres avisos que dicen lo mismo, a veces bajo el campo equivocado
- **Severidad:** baja
- **Tipo:** mensaje
- **F-XX / pantalla:** F-56 · W-38 Nuevo / Editar proveedor · `/proveedores`, `/proveedores/{id}`
- **Persona:** Diego (Gerencia de Administración), Carla (Compras)
- **Pasos:** 1. "+ Nuevo proveedor" con un nombre que ya existe ("qa-va corralón ñandú s.r.l.", "qa-vb ferreteria cia"). 2. Leer el aviso y tocar "Guardar proveedor". 3. Repetir con otro nombre y un CUIT ya cargado (30-70300301-6). 4. Editar "QA-VA Otro Proveedor" y ponerle el nombre de otro.
- **Esperado:** un solo aviso del choque, con su link a la ficha ("Ya está cargado: … Abrí su ficha."), junto al campo que está repetido.
- **Obtenido:** por nombre, bajo Nombre se ven a la vez "Ya existe QA-VB Ferretería Cía." · "Ver la ficha de QA-VB Ferretería Cía." · "Ya está cargado: QA-VB Ferretería Cía. Abrí su ficha.": tres veces el mismo dato y dos links iguales. Por CUIT, "Ya está cargado: QA-VA Corralón Ñandú S.R.L. Abrí su ficha." aparece bajo **Nombre** aunque el nombre es distinto, y al guardar se suman "Ese CUIT ya es de QA-VA Corralón Ñandú S.R.L." · "Ver la ficha de …" bajo CUIT. "Guardar proveedor" sigue habilitado aunque el sistema ya sabe que es un repetido exacto, y responde 422. Igual al editar. Reproducido 5 veces entre dos roles.
- **Evidencia:** `capturas/PU-119-1.png` · `capturas/PU-119-2.png` · `capturas/PU-119-3.png` · `capturas/PU-119-4.png` · `capturas/PU-119-5.png` · `capturas/PU-119-6.png` · `capturas/PU-119-7.png` · `POST /api/proveedores → 422`, `PATCH /api/proveedores/18 → 422` · scripts `va/pu013.cjs` · origen: VA-01, VB-02
- **Causa probable (opcional):** `ProveedorDialog.tsx` muestra el error del 422 con su link y `Parecidos.tsx` sigue mostrando su "Ya está cargado" al mismo tiempo.
- **Regresión de:** PU-013 / PU-035 · front `fe6c562` (el aviso previo se suma al error del servidor sin reemplazarlo)

### PU-120 · Mientras se agrega un link, el aviso habla de "subir los archivos"
- **Severidad:** baja
- **Tipo:** mensaje
- **F-XX / pantalla:** F-16 · W-06 Nueva obra · F-20 · W-23 Editar obra · F-19 · W-20 documento · F-18 · W-21 adicional
- **Persona:** Diego (Gerencia de Administración), red lenta
- **Pasos:** 1. "Pegar link" → link → "Agregar link". 2. Mirar debajo de "Guardar obra" / "Guardar" mientras viaja.
- **Esperado:** un texto que sirva para links, p. ej. "Esperá a que terminen de agregarse los archivos o links para guardar."
- **Obtenido:** "Esperá a que terminen de subir los archivos para guardar." (no hay ningún archivo subiendo). Visto en las 4 pantallas del arreglo de PU-001; reproducido 3 veces.
- **Evidencia:** `capturas/PU-120-1.png` (Nueva obra: "Agregar link" girando y el aviso abajo) · script `reg/12-links.cjs` · origen: REG-12, VA (nota de PU-001)
- **Causa probable (opcional):** `MENSAJE_ESPERAR_SUBIDAS` en `src/shared/adjuntos/AdjuntosField.tsx:29` se reusa para links.
- **Regresión de:** PU-001 / front `ebac793`

### PU-121 · Editar obra: agregar solo espacios al final dice "Guardamos los cambios de la obra."
- **Severidad:** baja
- **Tipo:** mensaje
- **F-XX / pantalla:** F-20 · W-23 Editar obra · `/obras/{id}/editar`
- **Persona:** Diego (Gerencia de Administración)
- **Pasos:** 1. Editar 2-501-2026 y agregar dos espacios al final del cliente. 2. "Guardar cambios".
- **Esperado:** "No había cambios para guardar." (el servidor recorta y no cambia nada: ni historial ni `updated_at`).
- **Obtenido:** sale `PATCH /api/obras/10 → 200` y "Guardamos los cambios de la obra.". Reproducido 2 veces.
- **Evidencia:** sin captura · scripts `reg/18-sin-cambios.cjs`, `reg/19-espacios.cjs` · origen: REG-13
- **Causa probable (opcional):** `isDirty` compara el texto crudo, sin recortar.
- **Regresión de:** PU-029 / front `669cad2` (borde)

### PU-122 · La revisión "REV00" se rechaza con "Escribí la revisión como REV01."
- **Severidad:** baja
- **Tipo:** mensaje
- **F-XX / pantalla:** F-16 · W-06 Alta de obra · `/obras/nueva` (por código, también Editar y el adicional)
- **Persona:** Hernán (Jefatura de Proyectos)
- **Pasos:** 1. Nueva obra con todo completo. 2. En "Revisión *" escribir `REV00` (o `REV0`). 3. "Guardar obra".
- **Esperado:** si la revisión inicial puede ser 00 (habitual en ofertas técnicas), aceptarla; si no, decir por qué, p. ej. "Las revisiones empiezan en REV01." RN-OBR-14 lista las formas aceptadas y no dice nada del 0.
- **Obtenido:** debajo del campo, "Escribí la revisión como REV01.". La persona escribió exactamente con ese formato y no entiende qué cambiar. No sale ningún pedido a la API. Reproducido 2 veces (REV00, REV0).
- **Evidencia:** `capturas/PU-122-1.png` · script `per/22-obra-alta.cjs` · origen: PER-01

### PU-123 · Plazos de la prioridad: "Guardar" sin cambiar nada dice "Guardamos los plazos…"
- **Severidad:** baja
- **Tipo:** mensaje
- **F-XX / pantalla:** F-68 · W-50 Plazos de la prioridad · `/usuarios/plazos`
- **Persona:** Diego (Gerencia de Administración)
- **Pasos:** 1. Usuarios → Plazos de la prioridad. 2. Sin tocar nada (1 / 7), tocar "Guardar".
- **Esperado:** "No había cambios para guardar." y sin `PUT`, como ya hacen Usuario, Mi perfil, Proveedor y Obra tras el arreglo de PU-029.
- **Obtenido:** "Guardamos los plazos. Valen para los pedidos que se carguen desde ahora." y sale `PUT /api/plazos-prioridad` (200). Los valores quedaron igual (1 / 7). Reproducido 2 veces.
- **Evidencia:** `capturas/PU-123-1.png` · `capturas/PU-123-2.png` · scripts `per/14-plazos-perfil.cjs`, `per/17-plazos-sector.cjs` · origen: PER-02
- **Regresión de:** PU-029 (no cubierto: la pantalla no estaba en la lista del PU)

### PU-124 · Sacar un sector con un usuario: pregunta "¿Sacar…?" y ofrece el botón aunque ya avisa que no se puede
- **Severidad:** baja
- **Tipo:** mensaje
- **F-XX / pantalla:** F-59 · W-40 Sectores · `/usuarios/sectores`
- **Persona:** Diego y Dora
- **Pasos:** 1. Sectores. 2. "sacar" en "QA-PER Taller Norte" (1 usuario activo). 3. Tocar "Sacar sector".
- **Esperado:** si no se puede, decirlo de entrada con el texto de RN-IDE-31 ("Antes de sacar …, pasá a su usuario a otro sector.") y sin un botón que no va a funcionar (o desactivado); concordancia en singular.
- **Obtenido:** el diálogo muestra a la vez "¿Sacar QA-PER Taller Norte? Deja de aparecer en la lista, en los desplegables y en la tabla de permisos." y "Tiene 1 usuario activo: antes hay que pasarlos a otro sector." (plural con 1), con "Sacar sector" habilitado. Al tocarlo: `DELETE /api/sectores/20` → 422 y se suma arriba "Antes de sacar QA-PER Taller Norte, pasá a su usuario a otro sector."; quedan los tres textos. Reproducido 2 veces (Diego, Dora).
- **Evidencia:** `capturas/PU-124-1.png` · scripts `per/17-plazos-sector.cjs`, `per/52-sacar.cjs` · origen: PER-03

### PU-125 · En una obra Terminada el botón sigue diciendo "Cambiar estado / pasar a Terminada"
- **Severidad:** baja
- **Tipo:** mensaje
- **F-XX / pantalla:** F-21 / F-22 · W-07 Ficha de la obra · `/obras/{id}`
- **Persona:** Hernán (Jefatura de Proyectos, responsable de la obra)
- **Pasos:** 1. Pasar 2-601-2026 a En ejecución y luego a Terminada. 2. Volver a la ficha.
- **Esperado:** en una obra ya terminada, un rótulo que corresponda (p. ej. "Cambiar estado" o "Volver a En ejecución"); RN-OBR-30 nombra el botón "Cambiar estado / pasar a Terminada" pensando en las obras activas.
- **Obtenido:** "Cambiar estado / pasar a Terminada" con la etiqueta "Terminada" al lado. El diálogo sí se arma bien (Confirmada desactivada, preseleccionada "En ejecución", botón "Pasar a En ejecución"). Reproducido 2 veces.
- **Evidencia:** `capturas/PU-125-1.png` · scripts `per/29-terminadas.cjs`, `per/30-estado2.cjs` · origen: PER-04

### PU-126 · "Rige desde el próximo pedido de cada persona al sistema.": "pedido" se confunde con los Pedidos
- **Severidad:** baja
- **Tipo:** mensaje
- **F-XX / pantalla:** F-60 · W-15 Tabla de permisos · `/usuarios/permisos`
- **Persona:** Diego (Gerencia de Administración)
- **Pasos:** 1. Cambiar dos celdas de "QA-PER Taller" (Proveedores → Ver, Presupuesto por rubro → Ver · sus obras). 2. "Guardar cambios".
- **Esperado:** en el sistema "pedido" es un pedido de trabajo (módulo Pedidos). Algo como "Guardamos la tabla de permisos. Cada persona lo ve la próxima vez que use el sistema (o al recargar)."
- **Obtenido:** "Guardamos la tabla de permisos. Rige desde el próximo pedido de cada persona al sistema." (mismo texto en la ronda 1; nadie lo marcó).
- **Evidencia:** `capturas/PU-126-1.png` · script `per/13-permisos-cambio.cjs` · origen: PER-07

### PU-127 · Mi perfil: "No había cambios para guardar." queda puesto mientras escribís y aparecen errores
- **Severidad:** baja
- **Tipo:** mensaje
- **F-XX / pantalla:** F-10 · Mi perfil · `/perfil`
- **Persona:** Sofía (Ingeniería de proyectos)
- **Pasos:** 1. Mi perfil → "Guardar" sin tocar nada. 2. Borrar el Nombre. 3. "Guardar".
- **Esperado:** el aviso se va apenas se cambia algo (como los avisos de proveedor desde PU-036); queda solo "Completá el nombre.".
- **Obtenido:** arriba sigue "No había cambios para guardar." y bajo el campo "Completá el nombre.": la pantalla dice que no hay cambios mientras marca uno. Igual con nombre de 300 ("El nombre puede tener hasta 120 caracteres.") y teléfono de 60. Reproducido 2 veces. En Editar obra, proveedor y usuario no pasa.
- **Evidencia:** `capturas/PU-127-1.png` · `ent/perfil2.cjs` · origen: ENT-01
- **Causa probable (opcional):** `PerfilPantalla.tsx`: el `mensaje` de "sin cambios" no se limpia al editar un campo.
- **Regresión de:** PU-029 / front `c184727`

### PU-128 · N.º de presupuesto repetido: dos avisos que dicen lo mismo con palabras distintas (y en el adicional, sin link)
- **Severidad:** baja
- **Tipo:** mensaje
- **F-XX / pantalla:** F-16 · W-06 Nueva obra `/obras/nueva`; F-18 · W-21 Agregar presupuesto adicional (ficha de la obra)
- **Persona:** Diego (Gerencia de Administración)
- **Pasos:** 1. Nueva obra con el N.º `2-701-2026`, que ya es de otra obra, y todo lo demás completo → "Guardar obra". 2. En la ficha de otra obra, "+ agregar presupuesto adicional" con N.º `2-701-2026` → "Guardar y cargar montos por rubro →".
- **Esperado:** un solo aviso, el mismo en las dos pantallas y con el link a la obra, p. ej. "Ese N.º de presupuesto ya está en «QA-ENT2 Montaje…». Abrí la obra." bajo el campo N.º.
- **Obtenido:** en Nueva obra, arriba: "Ese N.º de presupuesto ya está en QA-ENT2 Montaje 🏗️ ñandú ÁÉÍÓÚ <script>alert(1)</script>." (con link) y bajo el campo: "El presupuesto 2-701-2026 ya está en la obra 'QA-ENT2 Montaje 🏗️ ñandú ÁÉÍÓÚ <script>alert(1)</script>'.". En el adicional sale solo el segundo, sin link y con comillas simples, que no se usan en ningún otro texto. Reproducido 2 veces en cada pantalla. (La ronda 1 lo había dejado como observación, consolidación §(c) 4.)
- **Evidencia:** `capturas/PU-128-1.png` · `capturas/PU-128-2.png` · `capturas/PU-128-3.png` (adicional) · `POST /api/obras` → 422 · `POST /api/obras/17/presupuestos` → 422 · origen: ENT-02
- **Causa probable (opcional):** `app/Modules/Obras/Application/Presupuestos/NumeroUnico.php:35` arma el texto con `'…'`; `ObraFormulario.tsx:126-134` agrega su propio aviso y no esconde el del campo; `AdicionalDialog.tsx` no tiene el aviso con link.

### PU-129 · La carpeta de Drive con ñ o espacios se ve como "%C3%B1" y "%20" al editar la obra
- **Severidad:** baja
- **Tipo:** mensaje
- **F-XX / pantalla:** F-20 · W-23 Editar obra · `/obras/{id}/editar` (campo "Carpeta de Drive de la obra")
- **Persona:** Diego (Gerencia de Administración)
- **Pasos:** 1. Editar obra → Carpeta de Drive `https://drive.example/Obras 2026/carpeta-ñandú-año` → "Guardar cambios". 2. Volver a "Editar".
- **Esperado:** el campo muestra lo que la persona escribió: `https://drive.example/Obras 2026/carpeta-ñandú-año`.
- **Obtenido:** `https://drive.example/Obras%202026/carpeta-%C3%B1and%C3%BA-a%C3%B1o`. Parece que el sistema lo rompió, aunque el link funciona. Visto en 2-701-2026 y 2-702-2026.
- **Evidencia:** `capturas/PU-129-1.png` · `ent/carpeta-y-repetida.cjs` · origen: ENT-04
- **Causa probable (opcional):** el backend guarda la URL codificada (arreglo de PU-010) y el formulario la muestra tal cual, sin `decodeURI`.
- **Regresión de:** PU-010 / back `167e14d`

### PU-130 · El ejemplo de "Usá coma para los decimales" no usa el punto de miles que usa toda la pantalla
- **Severidad:** baja
- **Tipo:** mensaje
- **F-XX / pantalla:** F-48 · W-13 Presupuesto por rubro
- **Persona:** Diego (Gerencia de Administración)
- **Pasos:** 1. Escribir `1234.56` en un rubro y `abc` en otro. 2. "Guardar".
- **Esperado:** el mismo formato en los dos avisos: "Usá coma para los decimales, por ejemplo 1.234,56."
- **Obtenido:** en la misma pantalla una celda dice "Usá coma para los decimales, por ejemplo 1234,56." y la de al lado "Escribí el monto con números, por ejemplo 1.500.000,50.". La tabla muestra los montos como "1.234,56".
- **Evidencia:** `capturas/PU-130-1.png` · `capturas/PU-130-2.png` · `src/shared/dominio/importes.ts:31` · origen: ENT-06

### PU-131 · El mail mal escrito se avisa de tres maneras distintas
- **Severidad:** baja
- **Tipo:** mensaje
- **F-XX / pantalla:** F-16/F-20 · W-06/W-23 contactos de la obra; F-56 · W-38 proveedor; F-58 Usuarios (alta y edición)
- **Persona:** Diego (Gerencia de Administración)
- **Pasos:** 1. Escribir `ventas@` en el mail de cada formulario. 2. Guardar.
- **Esperado:** un solo texto: "Escribí un mail válido, por ejemplo nombre@empresa.com."
- **Obtenido:** obra: "Escribí un mail válido, por ejemplo compras@cliente.com." · proveedor: "Escribí un mail válido." (sin ejemplo) · usuario: "Escribí un mail válido, por ejemplo nombre@empresa.com.". Reproducido 2 veces en cada uno.
- **Evidencia:** sin captura · `ent/obra-alta.cjs`, `ent/proveedor.cjs`, `ent/usuario.cjs`, `ent/varios.cjs` · origen: ENT-07

### PU-132 · Sin acceso: "Proveedores: tu sector no tiene permiso…" suena a etiqueta, y en Plazos dice "Usuarios:"
- **Severidad:** baja
- **Tipo:** mensaje
- **F-XX / pantalla:** pantallas sin permiso (`NoAccessState`): `/proveedores`, `/usuarios`, `/usuarios/plazos`
- **Persona:** Sofía (Ingeniería de proyectos)
- **Pasos:** 1. Con Sofía, abrir `/proveedores` y `/usuarios/plazos` escribiendo la dirección.
- **Esperado:** "Tu sector no tiene permiso para ver Proveedores. Si lo necesitás, pedíselo a gerencia." y, en Plazos, "…para ver los Plazos de la prioridad." (el permiso es otro: `plazos_prioridad`).
- **Obtenido:** "No tenés acceso a esta sección" · "Proveedores: tu sector no tiene permiso para ver esto. Si lo necesitás, pedíselo a gerencia." y, en `/usuarios/plazos`, "Usuarios: tu sector no tiene permiso para ver esto.…". Visto 2 veces.
- **Evidencia:** `capturas/PU-132-1.png` · `capturas/PU-132-2.png` · `src/shared/ui/States.tsx:135` · origen: ENT-08
- **Regresión de:** PU-014 / front `18abdbc` (texto nuevo)

### PU-133 · Sesión vencida y "esperá" dichos de varias maneras
- **Severidad:** baja
- **Tipo:** mensaje
- **F-XX / pantalla:** transversal (mensajes por defecto del front y del backend)
- **Persona:** —
- **Pasos:** 1. Revisar los textos de `src/shared/api/ApiError.ts`, `app/Shared/Http/ApiExceptionRenderer.php`, `IdentidadServiceProvider.php` e `Idempotent.php`.
- **Esperado:** una sola forma para cada idea: "Tu sesión venció. Volvé a ingresar." para 401 y 419; "Probá de nuevo en …" para todas las esperas.
- **Obtenido:** sesión: "Tu sesión venció. Volvé a ingresar." (401) y "La sesión expiró. Volvé a ingresar." (419). Esperas: "Hiciste demasiados intentos. Esperá un momento y volvé a intentar." (front, 429) · "Fueron demasiadas acciones seguidas. Esperá unos segundos y volvé a intentar." (backend, 429) · "Demasiados intentos. Probá de nuevo en un minuto." · "Ya pediste varios códigos. Probá de nuevo en N minutos." · "El sistema no está disponible en este momento. Probá de nuevo en unos minutos." · "Esta operación todavía se está procesando. Probá de nuevo en unos segundos." ("operación" es la única palabra técnica encontrada). Solo visto en el código; en pantalla, únicamente "Tu sesión venció. Volvé a ingresar." y (Lead) "Ya pediste varios códigos. Probá de nuevo en 8 minutos.".
- **Evidencia:** sin captura · las rutas de los Pasos · origen: ENT-09

### PU-134 · Quedan controles de menos de 44 px en el celular: listas "todas / sus obras" de Permisos, logo y "Saltar al contenido"
- **Severidad:** baja
- **Tipo:** celular
- **F-XX / pantalla:** F-60 Tabla de permisos `/usuarios/permisos` · encabezado de todas las pantallas
- **Persona:** Diego (Gerencia de Administración), Hernán (Jefatura de Proyectos)
- **Pasos:** 1. Abrir la Tabla de permisos en Pixel 7 y en iPhone SE. 2. Medir el alto de las listas de alcance ("todas" / "sus obras") debajo de "Ver"/"Editar". 3. Medir el logo "TECNOSERVICIOS" y "Saltar al contenido" en cualquier pantalla.
- **Esperado:** 44 px de alto como mínimo (PU-041).
- **Obtenido:** las listas de alcance miden 105×25 px ("Alcance de Pedidos · Dirección", "Alcance de Obras · Compras", …; letra de 16 px, bien), al lado de las listas de nivel que sí miden 113×44. El logo "TECNOSERVICIOS" mide 141×23 y "Saltar al contenido" 156×39. Igual en los dos celulares; medido por separado por tres roles.
- **Evidencia:** `capturas/PU-134-1.png` (Pixel 7) · `capturas/PU-134-2.png` (iPhone SE) · `uxm/out-cel-diego-p7.txt`, `out-cel-diego-se.txt` (`selectsChicos`) · origen: UXM-03, VA (nota de PU-022), VB (nota de PU-041)
- **Causa probable (opcional):** `.celda__select--a` (Tabla de permisos) no toma `--alto-control` del celular; el logo es un link en línea sin `min-height`.
- **Regresión de:** PU-041 / front `bfc95ac` (arreglo incompleto)

### PU-135 · Sectores en el celular: la tabla no pasa a tarjetas y "sacar" queda cortado sin aviso
- **Severidad:** baja
- **Tipo:** celular
- **F-XX / pantalla:** F-59 Sectores `/usuarios/sectores`
- **Persona:** Diego (Gerencia de Administración)
- **Pasos:** 1. Abrir Usuarios → Sectores en iPhone SE (375 px) y en Pixel 7 (412 px).
- **Esperado:** como Usuarios y Presupuesto por rubro (PU-022): tarjetas o todo a la vista; si se desliza, "Deslizá para ver más".
- **Obtenido:** la tabla mide 398 px dentro de un recuadro de 341 px (SE) / 378 px (Pixel 7). En el iPhone SE se lee "renombrar ·" y del botón "sacar" se ve solo la "s"; en el Pixel 7 se ve entero por poco, pero la columna "Acciones" se pasa del recuadro. Sin aviso ni sombra. La página no se desplaza a lo ancho. 2 corridas por celular.
- **Evidencia:** `capturas/PU-135-1.png` · `capturas/PU-135-2.png` · `capturas/PU-135-3.png` · `uxm/sectores-se.cjs` ("iphonese … "sacar" L337 R403 66x44 visible-entero=false") · origen: UXM-04
- **Causa probable (opcional):** `SectoresPantalla.tsx` usa `tabla-marco` sin `tabla-marco--tarjetas` ni `--deslizable`.
- **Regresión de:** PU-022 / front `8777360` (no cubierto: Sectores no estaba en la lista del PU)

### PU-136 · Mientras guarda, el foco se cae del botón y sale del diálogo; el lector de pantalla no dice "Guardando…"
- **Severidad:** baja
- **Tipo:** accesibilidad
- **F-XX / pantalla:** F-19 diálogo "Agregar certificado o documento" · F-56 diálogo "Nuevo proveedor" (`/proveedores/nuevo`) · F-20 Editar obra · todos los botones con spinner
- **Persona:** Hernán, Carla (PC, solo teclado)
- **Pasos:** 1. Con la red lenta (4 s en el guardado), completar el diálogo. 2. Con Tab llegar a "Guardar" y apretar Enter. 3. Mientras guarda, apretar Tab dos veces.
- **Esperado:** el foco sigue en el botón (o dentro del diálogo) y se anuncia "Guardando…"; el foco no sale del diálogo.
- **Obtenido:** al apretar Enter el botón queda `disabled` y el foco pasa a `body`; el nombre del botón sigue "Guardar" / "Guardar proveedor" (el spinner es `aria-hidden` y solo hay `aria-busy`). Con Tab durante el guardado el foco sale del diálogo a "Saltar al contenido". En Nuevo proveedor, al terminar, el foco quedó en "Saltar al contenido" de la ficha. Reproducido en 3 formularios.
- **Evidencia:** `capturas/PU-136-1.png` · `uxm/out-teclado3.txt` · `uxm/foco-guardando.cjs` ("guardando: BODY · botón: "Guardar proveedor" disabled=true · Tab guardando → A "Saltar al contenido" [FUERA del diálogo]") · origen: UXM-02
- **Causa probable (opcional):** `shared/ui/Button.tsx` usa `disabled` cuando `cargando` (el navegador saca el foco de un botón deshabilitado); `Dialog.tsx` solo atrapa Tab cuando el foco está en el primero/último control.
- **Regresión de:** PU-018 / front `bfc95ac` (arreglo visual sin equivalente accesible)

### PU-137 · Buscar proveedor por CUIT funciona, pero ni las ayudas ni el buscador general lo dicen ni muestran el CUIT
- **Severidad:** baja
- **Tipo:** consistencia-con-documento
- **F-XX / pantalla:** F-03 · W-18 Buscador · `/buscar` · F-55 · W-14 Proveedores · `/proveedores`
- **Persona:** Dora, Diego, Hernán, Carla
- **Pasos:** 1. Lupa. 2. Escribir "6006" (parte del CUIT 30-71600601-4). 3. En Proveedores, buscar "040" (3 dígitos) y leer el vacío.
- **Esperado:** decisión PU-044: el buscador general y el listado encuentran por CUIT. Que el resultado muestre el CUIT con el que coincidió (como el listado: "MATERIALES · 30-71600601-4") y que las ayudas lo mencionen.
- **Obtenido:** ayuda del buscador "Por N.º de presupuesto, cliente, planta, nombre del trabajo o proveedor."; resultado "PROVEEDORES (1) / QA-PER Herrajes del Sur S.A. · MATERIALES", sin el CUIT: la persona no ve por qué apareció. En el listado, el vacío dice "No encontramos proveedores · Probá con otra parte del nombre.". Igual con las 4 personas.
- **Evidencia:** `capturas/PU-137-1.png` · `capturas/PU-137-2.png` · script `per/50-buscador.cjs` · origen: PER-06, VB (nota de PU-044)

### PU-138 · Si se cae Redis o MySQL, toda la app queda tapada por "No pudimos cargar esta información" y no se recupera sola cuando vuelve
- **Severidad:** media
- **Tipo:** error-servidor
- **F-XX / pantalla:** transversal · cualquier pantalla con sesión (probado en `/obras` → `/proveedores`) · `GET /api/me`
- **Persona:** Hernán (Jefatura de Proyectos)
- **Pasos:** 1. Con la app abierta en Obras, parar Redis (o MySQL desde Laragon). 2. Ir a Proveedores desde la barra. 3. Volver a levantar el servicio. 4. Esperar sin tocar (8 s y 35 s). 5. Ir a "Obras" desde la barra. 6. Tocar "Reintentar".
- **Esperado:** (prompt ronda 2 §4) "la app muestra un mensaje entendible y se recupera sola al volver". La falla de una carga no debería sacar la barra ni el menú (regla de loaders/errores: solo el componente que recarga). `/api/me` con un servicio caído debería responder 503, no 500.
- **Obtenido:** `GET /api/me → 500` y la pantalla entera se reemplaza por "No pudimos cargar esta información · El sistema tuvo un problema. Volvé a intentar en un momento. · Reintentar", **sin barra ni menú** (no se puede ir a otra sección). El mensaje se entiende. Con Redis de vuelta (`/api/health` → 200), la pantalla sigue igual a los 8 s y a los 35 s; solo "Reintentar" la recupera (vuelve a `/obras` con la barra). Con MySQL caído pasa lo mismo a los ~11 s. Las sesiones sobrevivieron al corte (Redis guardó su snapshot).
- **Evidencia:** `capturas/PU-138-1.png` (Redis caído) · `capturas/PU-138-2.png` (tras "Reintentar") · `capturas/PU-139-1.png` (MySQL caído) · `logs/PU-020-redis-parado.log`, `logs/mysql-parado.log` · origen: LEAD
- **Causa probable (opcional):** el error de la query de la sesión (`/api/me`) cae en el `errorElement`/estado de error de `RequireSesion`/`AppLayout`, que reemplaza todo el shell; no hay reintento automático (`retry`/`refetchOnWindowFocus`/`refetchInterval` cuando falló). El backend no distingue "servicio caído" (503) de un error propio (500).

### PU-139 · Con MySQL caído, `/api/health` tarda 9 s en responder
- **Severidad:** baja
- **Tipo:** error-servidor
- **F-XX / pantalla:** transversal · `GET /api/health`
- **Persona:** — (monitoreo)
- **Pasos:** 1. Parar MySQL desde Laragon (Laragon paró también Redis). 2. `GET /api/health` dos veces.
- **Esperado:** que responda rápido (con Redis caído solo, el objetivo del prompt es < 3 s; con `DB_CONNECT_TIMEOUT=2` el chequeo de base no debería pasar de ~2 s).
- **Obtenido:** 503 en **9,37 s** y **9,19 s** con `{"database": false, "cache": false, "redis": false, "cola_mail": false}`. Con solo Redis caído: 503 en 1,46–1,87 s (bien). Con MySQL arriba y Redis caído: 1,28 s.
- **Evidencia:** `logs/mysql-parado.log` · origen: LEAD
- **Causa probable (opcional):** varios chequeos que tocan la base (database, cache/limitador o cola) esperan cada uno el timeout de conexión en serie; o el timeout de PDO en Windows con MySQL apagado no respeta `DB_CONNECT_TIMEOUT`.
