# Borrador REG · Cazador de regresiones · Ronda 3

- **Fecha:** 2026-11-18 (14:10 a 14:50 aprox.)
- **Personas y sesiones:** Diego (`diego.json`), Dora, Hernán, Laura y Carla (solo lectura, en permisos). `qa-lead-reg@empresa-ejemplo.com` (Gerencia Adm.) con ingreso real por pantalla.
- **Ingresos reales gastados:** 3 de 3 (`REG/ingresos.txt`).
- **Datos creados (prefijo QA3-REG):**
  - obra `2-971-2026` "QA3-REG Obra completa 16" (id 12), con su adicional `2-971-2026 A19` y el documento "QA3-REG Certificado 706";
  - obra `2-975-2026` "QA3-REG Nueva con sesión vencida" (id 15);
  - proveedor "QA3-REG Ferretería Añelo 489" (id 16, CUIT 30-71977489-6).
- **Mi perfil de Diego:** se tocó el teléfono para probar el 409 y quedó vacío otra vez, como estaba (verificado en la base).
- **Scripts:** `C:\Users\juanc\AppData\Local\Temp\claude\C--Users-juanc\3fd8de0f-88e6-40e4-a658-bb42248ba145\scratchpad\qa3\REG\` (`02`…`18`). Las salidas de la sesión vencida están en `sesf.out` y `ses-1.out`.

## Tabla de verificación

No tengo PU asignados. Esta tabla resume las zonas de regresión que revisé.

| Zona | Qué | Estado | Pantallas | Evidencia | Nota |
|---|---|---|---|---|---|
| Aviso de cambios sin guardar | F5, Atrás, links de la barra, Ctrl+clic, Cancelar, cerrar pestaña, después de guardar | ✅ / ⚠️ | W-06, W-23, proveedor (alta y editar, diálogo y `/proveedores/nuevo`), Mi perfil, Tabla de permisos; **W-13 presupuesto por rubro y Plazos: no** | `03-salida`, `04-atras-spa`, `05-salida-prov`, `09-guard-otras` · `REG-01-*.png` | Funciona igual en todas las pantallas que cubre:<br>• F5 y cerrar la pestaña muestran el aviso del navegador;<br>• Atrás dentro de la app, los links y Cancelar muestran "Tenés cambios sin guardar. ¿Salir igual?";<br>• Ctrl+clic en la barra, la lupa o el logo abre otra pestaña sin preguntar;<br>• después de guardar no pregunta, ni con Atrás ni con Adelante;<br>• después de "Actualizar" en un conflicto, sale sin preguntar.<br>Falta en presupuesto por rubro y en Plazos: **REG-01**. |
| Borradores | recuperado y descartado | ✅ | W-06, W-23 | `REG-ses-A-recuperado.png` · `REG-ses-B-descartado.png` | "Recuperamos lo que estabas escribiendo." con N.º, nombre y planta. "Mientras no estabas, alguien cambió esto. Lo que habías escrito no se pudo recuperar." después de que Diego cambió la obra. Con F5 el aviso no se repite. El borrador recuperado se guardó: obra 15 → "Obra creada. Ya se le pueden cargar pedidos.". Los adjuntos que fallaron con la sesión vencida no vuelven, y eso es correcto. |
| 409 y "Actualizar" | obra, presupuesto principal, Mi perfil | ✅ | W-23 (Planta vs Revisión y Revisión vs Nombre), `/perfil` (2 pestañas de Diego) | `REG-ok-409-perfil.png` · `PATCH /api/obras/12 → 409`, `PATCH /api/me → 409` | "Alguien cambió esto mientras lo tenías abierto. Actualizá y revisá." "Actualizar" carga lo guardado; el reintento da 200 y "Guardamos los cambios de la obra.". El **presupuesto por rubro** no tiene control de conflicto: **REG-02**. |
| Diálogos mientras guardan + doble clic | Cambiar estado, adicional, documento, proveedor, Nueva obra, Editar obra, Mi perfil, presupuesto por rubro | ✅ | W-22, W-21, W-20, W-38, W-06, W-23, F-10, W-13 | `REG-ok-dialogo-guardando.png` · `11-dialogos-guardando`, `13-alta-prov` | Con 3 s de demora, Escape y Cancelar no cierran el diálogo, y la barra no se puede tocar (es un diálogo modal). El foco queda en el botón ("Cambiando el estado…", "Guardando…"). Doble clic → **1** POST/PATCH/PUT en todos los casos. |
| Pestañas con flechas | Obras: En ejecución / Confirmadas / Terminadas | ✅ | W-05 | `12-pestanas-buscando` | → ← Inicio Fin funcionan, con vuelta circular. El foco y `aria-selected` acompañan, y `?estado=` se actualiza. |
| Listas con "Buscando…" | Obras (GET demorado 2,5 s) | ✅ | W-05 | `12-pestanas-buscando` | Mientras busca se ve "Buscando obras…" solo en la tabla. El buscador sigue habilitado y se puede seguir escribiendo. |
| Alta de obra completa | archivo y link; cambiar uno por otro; quitar | ✅ | W-06 → ficha 12 | `02-alta-obra` · `POST /api/obras → 201` (1 sola vez con doble clic) | Probé así:<br>• oferta: primero link, "quitar" y después archivo (uno.pdf);<br>• OC: primero archivo, "quitar" y después link (oc 001.pdf);<br>• fechas, carpeta con espacios y ñ, y contactos.<br>En la base: la oferta es un archivo, la aprobación es un link y la planta es "Planta Añelo". Los adjuntos quitados quedan sin dueño. |
| Alta de proveedor completa | `/proveedores/nuevo` | ✅ | W-38 | `13-alta-prov` · `POST /api/proveedores → 201` (1 vez) | Nombre con ñ, CUIT válido, rubro, teléfono y mail → "Dimos de alta a QA3-REG Ferretería Añelo 489.", y queda en `/proveedores/16`. |
| Cambio de estado | Confirmada → En ejecución | ✅ | W-22 | `11-dialogos-guardando estado` | Se ve "La obra pasó a En ejecución.". Hernán, que es el responsable de la obra 12, ve "Cambiar estado" aunque tiene Estado de obra = Ver: es correcto (RN-OBR-30). |
| Presupuesto por rubro | carga, total, doble clic | ⚠️ | W-13 | `08-rubro` · `REG-02-*.png` | Muestra "1.750.000,50" y "Guardamos el presupuesto por rubro.", con 1 solo PUT. Pero no tiene aviso al salir (**REG-01**) y pisa sin aviso (**REG-02**). |
| Permisos por sector | Dora, Diego, Hernán, Laura, Carla × 17 URLs | ✅ | barra, ficha, alta, editar, rubro, proveedores, usuarios, plazos, perfil, buscador | `14-permisos` (`perm.out`) | Cada sector ve lo que dice la tabla `permisos` y nada más. Por URL directa:<br>• "Tu sector puede ver las obras pero no darlas de alta…" (Dora y Carla);<br>• "No tenés acceso a esta sección Tu sector no tiene permiso para ver Proveedores." (Laura);<br>• "Ves el presupuesto por rubro solo de las obras donde sos responsable." (Laura);<br>• Plazos solo lectura para Dora (sin "Guardar");<br>• quien solo puede ver, en `/obras/12/editar` ve "Historial de la obra".<br>No aparecieron errores de JS ni códigos internos. |
| **Caso abierto: sesión vencida con 2 pestañas** | Nueva obra (link y archivo en curso) + Editar obra (Guardar cambios) | ✅ | W-06, W-23 | `REG-ses-A-3s.png`, `REG-ses-B-25s.png` · `sesf.out` | Sin demora artificial y mirando 25 s:<br>• `POST /api/adjuntos → 419` → csrf → `401`, y `PATCH /api/obras/12 → 419` → csrf → `401`;<br>• A va a Ingreso a los 3,7 s y B a los 4,7 s, las dos con "Tu sesión venció. Volvé a ingresar." y su borrador guardado;<br>• no quedó ningún "Tenés que ingresar para seguir." ni un "Guardar cambios" girando. **No se reproduce.** Ver la nota de abajo sobre la causa probable de la vez anterior. |

## Hallazgos nuevos

### REG-01 · Presupuesto por rubro y Plazos de la prioridad se van sin preguntar con cambios sin guardar
- **Severidad:** media
- **Tipo:** funcional
- **F-XX / pantalla:** F-48 · W-13 Presupuesto por rubro · `/obras/{id}/presupuesto-por-rubro`; Plazos de la prioridad · `/usuarios/plazos`
- **Persona:** Diego (Gerencia de Administración)
- **Pasos:**
  1. En la obra 2-971-2026, tocar "Presupuesto por rubro".
  2. Escribir `123.456` en LOGISTICA, sin guardar.
  3. Tocar "‹ Volver a la obra", o "Proveedores" en la barra, o Atrás, o F5.
  4. En Plazos de la prioridad, cambiar "Urgente hasta" a 2 y tocar "Obras" en la barra o "Sectores".
- **Esperado:** "Tenés cambios sin guardar. ¿Salir igual?", como en Editar obra, Mi perfil y la Tabla de permisos (PU-006). Con F5, el aviso del navegador.
- **Obtenido:**
  - en el presupuesto por rubro se va sin preguntar por las 4 vías (5 veces). Al volver, LOGISTICA está vacío ("") y se perdió lo escrito. Con 12 rubros por presupuesto, es fácil perder una carga entera;
  - en Plazos pasa lo mismo, 2 veces (link de la barra y "Sectores").
- **Evidencia:** `capturas/REG-01-1.png` (rubro con 123.456 antes de salir) · `capturas/REG-01-2.png` (ficha, sin aviso) · `capturas/REG-01-3.png` (después de salir de Plazos con el cambio) · scripts `REG/08b-rubro-guard.mjs`, `REG/09-guard-otras.mjs`
- **Causa probable (opcional):** `PresupuestoPorRubroPantalla.tsx` y la pantalla de Plazos no usan `useCambiosSinGuardar`.

### REG-02 · Presupuesto por rubro: la segunda pestaña o persona pisa sin aviso los montos que guardó la primera
- **Severidad:** bloqueante
- **Tipo:** funcional
- **F-XX / pantalla:** F-48 · W-13 Presupuesto por rubro · `/obras/12/presupuesto-por-rubro`
- **Persona:** Diego (Gerencia de Administración), en dos pestañas
- **Pasos:**
  1. Abrir el presupuesto por rubro de la obra 2-971-2026 en dos pestañas (A y B).
  2. En A, cargar MATERIALES `2000000` e INSUMOS `250.000` y tocar "Guardar".
  3. En B, sin recargar, cargar SUBCONTRATOS `777.000` y tocar "Guardar".
- **Esperado:** "Alguien cambió esto mientras lo tenías abierto. Actualizá y revisá." con "Actualizar", como en Editar obra (PU-002), o que queden los dos cambios.
- **Obtenido:**
  - B ve "Guardamos el presupuesto por rubro." y en la base solo queda `subcontratos=777000`: MATERIALES e INSUMOS de A se borraron sin aviso;
  - el historial se lo atribuye a B: "materiales 1500000.50 → null, insumos 250000.00 → null";
  - `PUT /api/presupuestos/12/montos → 200` las dos veces;
  - reproducido 2 veces (99 y 777.000).
- **Evidencia:** `capturas/REG-02-1.png` (A guardado) · `capturas/REG-02-2.png` (B: "Guardamos el presupuesto por rubro." con solo SUBCONTRATOS) · tabla `actividades` id 131 · scripts `REG/08-rubro.mjs`, `REG/08-rubro2.mjs`
- **Causa probable (opcional):** `app/Modules/Obras/Http/Requests/CargarMontosRequest.php` no pide ni compara `updated_at`. El PUT reemplaza todos los montos con lo que tenía la pantalla.

### REG-03 · El historial nombra los links por el dominio ("agregado (drive.example)") y la ficha los nombra por el archivo
- **Severidad:** baja
- **Tipo:** mensaje
- **F-XX / pantalla:** F-20 · W-23 Historial de cambios (Editar obra / Historial de la obra)
- **Persona:** Diego (Gerencia de Administración); también QA Lead Reg
- **Pasos:**
  1. Nueva obra con la OC como link `https://drive.example/QA3-REG/oc 001.pdf` (o agregar un documento o un adicional con link).
  2. Abrir "Editar" y mirar el Historial de cambios.
- **Esperado:** el mismo nombre que la ficha y la tabla después del arreglo de PU-112: "oc 001.pdf".
- **Obtenido:**
  - el historial dice "Archivo o link de la aprobación: agregado (drive.example)", mientras la ficha muestra "oc 001.pdf";
  - igual en "Adicional 2-971-2026 A19 · Oferta técnica: agregado (drive.example)", en "QA3-REG Certificado 706 · Archivo o link: agregado (drive.example)" y en la obra 15 ("Oferta técnica: agregado (drive.example)");
  - con varios links del Drive, el historial no deja saber cuál se agregó.
  - Visto en 4 links de 2 obras.
- **Evidencia:** `capturas/REG-03-1.png` (obra 12) · `capturas/REG-03-2.png` (obra 15) · script `REG/18-historial.mjs`
- **Causa probable (opcional):** el arreglo de PU-112 (`e2605e5`/front) nombra el link en el front. El back sigue guardando `adjuntos.nombre = dominio` y el historial usa ese nombre. No es una regresión: es un resto de PU-112.

## Observaciones de UX (decisiones "a validar por Juani")
- **Cancelar o Escape mientras guarda:** el diálogo no se cierra, y está bien, pero no dice nada: ni "Estamos guardando. Esperá unos segundos." ni otro aviso. Solo se ve "Guardando…" en el botón (`REG-ok-dialogo-guardando.png`). Es aceptable. Si Juani quiere coherencia con la salida por link, podría mostrar el mismo texto.

## Lo que no pude probar y por qué
- **Primer intento de sesión vencida (ingresos 1 y 2), no válido:** hice el `DEL` mientras la pestaña B tenía 2 GET en vuelo (`/api/obras/clientes`, `/historial`). Laravel volvió a escribir la sesión al terminar esos pedidos y la sesión **no venció**: el PATCH dio 200 (`ses-1.out`).
  - Es una trampa del entorno, no un error de la app.
  - Puede explicar el "no concluyente" del Verificador B: si la sesión resucita a medias, el CSRF queda cruzado.
  - En el intento final hice el `DEL` con las dos pestañas quietas y confirmé con `EXISTS = 0`.
- **La vuelta del intento final no tuvo ingreso real:** ya no quedaban ingresos. Restauré el valor de la sesión de qa-lead-reg bajo el id nuevo de la pestaña, sin editar valores de otros ni reiniciar Redis. El vencimiento y el borrador sí son reales.
- **Cerrar la pestaña:** en headless se ve el `beforeunload`, pero no el texto del diálogo del navegador.
- **Ctrl+R por teclado:** el headless no lo procesa. Lo cubrí con `reload()`.

— Cazador de regresiones (REG): aviso de salida (6 vías × 6 pantallas), borradores recuperado y descartado, 409 en obra/presupuesto principal/perfil, diálogos guardando y doble clic (8 pantallas), pestañas, "Buscando…", alta completa de obra y proveedor con adjuntos, estado, presupuesto por rubro, permisos de 5 sectores × 17 URLs y el caso de sesión vencida con 2 pestañas (no se reproduce). 3 hallazgos nuevos (1 bloqueante, 1 media, 1 baja), 3 ingresos reales.
