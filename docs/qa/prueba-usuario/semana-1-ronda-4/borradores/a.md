# Borrador A · Verificador A: conflictos, guardado y salida (ronda 4)

- **Fecha:** 2026-11-18, 16:58–17:50 (hora del servidor). Rama `staging` en los dos repos: back `74beea5`, front `15b544f`.
- **Personas / sesiones:** Diego (`diego.json`, Gerencia de Administración; dos pestañas = dos `page` del mismo contexto), Carla (`carla.json`, Compras: presupuesto por rubro y proveedor), Hernán (`hernan.json`, Jefatura de Proyectos: Editar obra), Pablo (`pablo.json`, Ingeniería: Mi perfil).
- **Ingresos reales gastados:** 0
- **Datos creados (prefijo QA4-A):** obras 2-411…2-414-2026 "QA4-A Obra conflictos 1…4" (ids 1–4; la 1 y la 4 quedaron "En ejecución"), "QA4-A Obra salida …" (ids 6, 7 y 8, de las pruebas de O-1.1 en Nueva obra), adicional 2-412-2026 A1 (id 11), proveedores "QA4-A Ferretería Diálogo 29019" (id 2) y "QA4-A Ferretería Atrás 46849" (id 3), usuario "QA4-A Usuario 29019" (id 13, sector Calidad), documento "QA4-A Doc atrás 18583" en la obra 3. Los sectores "QA4-A Sector …" que creé los saqué.
- **Lo que toqué y dejé como estaba:** Plazos de la prioridad (1 / 7), las celdas Pedidos y Equipos en reparación de RRHH (Editar · todas) y el teléfono de Pablo (vacío). Diego no se tocó. Lo comprobé en la base al final.
- **Scripts:** `C:\Users\juanc\AppData\Local\Temp\claude\C--Users-juanc\3fd8de0f-88e6-40e4-a658-bb42248ba145\scratchpad\qa4\A\`:
  - `rubro-conflicto.mjs`, `rubro-adicional.mjs`, `api201.mjs` y `hist.mjs` (PU-201);
  - `guard.mjs` (PU-204 y PU-006);
  - `conflicto-obra.mjs`, `prov.mjs` e `ident.mjs` (PU-002);
  - `o11.mjs`, `o11-dlg.mjs`, `prov-atras.mjs` y `prov-alta-atras.mjs` (O-1.1);
  - `dialogos.mjs` y `dlg-cambios.mjs` (O-1.2 y PU-006 en los diálogos);
  - `restaurar.mjs`.
- **Demora de red:**
  - **O-1.1:** 2,5 s en cada pedido que no es GET. Los casos de falla se forzaron con `page.route` + `fulfill`: 422 con un error del campo, 409 con el cuerpo real del backend y 500 con "El sistema tuvo un problema…".
  - **O-1.2:** 8 s, para que entren Escape, "Cancelar" y el clic afuera durante el mismo guardado.
  - **Conflictos:** entre guardados que chocan se esperó ≥ 1,6 s.
- **Consola:** solo los "Failed to load resource" del navegador por los 409, 422 y 500 esperados o forzados. Ningún error JS ni warning de React.

## Tabla de verificación

| PU | título corto | estado | pantallas revisadas | evidencia | nota |
|---|---|---|---|---|---|
| PU-201 | Presupuesto por rubro: la segunda pestaña o persona pisaba los montos | ✅ | W-13 `/obras/{id}/presupuesto-por-rubro`: dos pestañas de Diego (obras 1 y 2), Diego + Carla (obras 3 y 4), "Monto del presupuesto" contra rubros y al revés (dos pestañas), principal + adicional (obra 2); API §10.3 (obra 4) | `PU-201-r4-tabs-1/2/3.png`, `PU-201-r4-carla-1/2/3.png`, `PU-201-r4-carla-b-1/2/3.png`, `PU-201-r4-montoobra-1/2/3.png`, `PU-201-r4-inverso-1/2/3.png`, `PU-201-r4-adicional-ambos.png`, `PU-201-r4-adicional-solo.png`, `PU-201-r4-historial.png` · `PUT /api/presupuestos/{id}/montos` → 409 · `PUT …/monto-obra` → 409 | **Cómo choca:** A guarda MATERIALES `2000000` e INSUMOS `250.000`; después B guarda SUBCONTRATOS. B ve "Alguien cambió esto mientras lo tenías abierto. Actualizá y revisá." / "Al actualizar vas a ver lo que está guardado ahora y se pierde lo que cambiaste acá." / "Actualizar", y el aviso queda a la vista (y=162).<br>**En la base:** sigue solo lo de A (`materiales=2000000.00, insumos=250000.00`). Con "Actualizar", B ve 2.000.000 / 250.000. Al volver a cargar SUBCONTRATOS y guardar, da 200 y quedan los tres.<br>**Monto del presupuesto:** también choca con los rubros, en los dos sentidos (409 en `montos` y en `monto-obra`).<br>**Principal + adicional:** si B cambia el adicional y el principal, choca en el principal y no guarda nada (no hay guardado a medias). Si B cambia solo el adicional, se guardan los dos cambios.<br>**Historial:** "Carla · SUBCONTRATOS: (vacío) → 777.000", "Diego · MATERIALES: (vacío) → 2.000.000". En `actividades`, `user_id` corresponde a quien guardó en cada caso.<br>**API:** versión vigente → 200. Versión vieja → 409 con `errors.updated_at`. `updated_at: "ayer"` → 422 "El campo versión que tenías abierta no es una fecha válida.". Guardar lo mismo no cambia la versión. Guardar montos sube `obras.updated_at`. |
| PU-204 | Presupuesto por rubro y Plazos se iban sin preguntar | ✅ | W-13 (obra 2) y `/usuarios/plazos`: "‹ Volver a la obra" / "Sectores", la barra ("Proveedores" / "Obras"), Atrás y F5; las mismas 4 vías después de guardar | `PU-204-r4-rubro-link/barra/atras/f5.png`, `PU-204-r4-plazos-link/barra/atras/f5.png`, `PU-204-r4-rubro-guardado-*.png`, `PU-204-r4-plazos-guardado-*.png` | **Con cambios:** link, barra y Atrás muestran "Tenés cambios sin guardar. ¿Salir igual?" / "Si salís, se pierde lo que cambiaste." / "Seguir editando" · "Salir". Con "Seguir editando", lo escrito sigue (LOGISTICA "250300", Urgente "2"). F5 dispara el `beforeunload` del navegador y el valor sigue.<br>**Después de "Guardamos el presupuesto por rubro." / "Guardamos los plazos…":** las 4 vías salen sin preguntar. |
| PU-002 | Dos personas editando lo mismo: la segunda pisaba | ✅ | Obra: Revisión (dos pestañas), Cliente vs Revisión + Planta (Diego + Hernán), N.º de OC (dos pestañas). Proveedor: dos pestañas, y Diego + Carla. Usuario: dos pestañas. Mi perfil (Pablo) vs Usuarios (Diego). Presupuesto por rubro: ver PU-201. | `PU-002-r4-obra-rev-1/2.png`, `PU-002-r4-obra-hernan-1/2.png`, `PU-002-r4-obra-oc-1/2.png`, `PU-002-r4-prov-1.png`, `PU-002-r4-prov-carla-1.png`, `PU-002-r4-usuario-1.png`, `PU-002-r4-perfil-1.png` · `PATCH /api/obras/3`, `/api/obras/4`, `/api/proveedores/2`, `/api/usuarios/13`, `/api/me` → 409 | **En todas:** "Alguien cambió esto mientras lo tenías abierto. Actualizá y revisá." con "Actualizar", en vista y con foco. La base queda con lo de A.<br>**"Actualizar":** carga lo de la otra persona (861 ms). El reintento da 200; en proveedor dice "Guardamos los cambios de QA4-A Ferretería Diálogo 29019.".<br>**Historial de la obra 3:** atribuye bien ("Hernán · Revisión: REV22 → REV68", "Diego · Cliente: …").<br>**Mismo segundo:** no se probó (límite aceptado); entre guardados hubo ≥ 1,6 s. |
| PU-006 | Irse con cambios sin guardar | ✅ | W-23 Editar obra y W-06 Nueva obra ("Cancelar", barra, Atrás y F5); Mi perfil (barra, Atrás y F5); Tabla de permisos ("Sectores", barra, Atrás y F5); diálogos (editar y alta de proveedor, usuario, sector, documento y adicional: "Cancelar", Escape y clic afuera); las mismas pantallas después de guardar | `PU-006-r4-editar-obra-*.png`, `PU-006-r4-nueva-obra-*.png`, `PU-006-r4-perfil-*.png`, `PU-006-r4-permisos-*.png`, `PU-006-r4-dialogo-cancelar.png`, `PU-006-r4-perfil-guardado-*.png`, `PU-006-r4-permisos-guardado-*.png` | **Pantallas con cambios:** en todas sale "Tenés cambios sin guardar. ¿Salir igual?". Con "Seguir editando", lo escrito sigue. F5 dispara el `beforeunload`.<br>**Diálogos:**<br>• con cambios, "Cancelar" y Escape preguntan lo mismo;<br>• el clic afuera no cierra;<br>• sin cambios, "Cancelar" cierra.<br>**Después de guardar** (Mi perfil y Tabla de permisos): ninguna vía pregunta. En Editar obra, al guardar, la pantalla va sola a la ficha. |

**Conteo:** ✅ 4 · ⚠️ 0 · ❌ 0

## Hallazgos nuevos

### A-01 · Tabla de permisos: salir mientras se guarda pregunta "Tenés cambios sin guardar" y la pregunta queda abierta después de guardar
- **Severidad:** media
- **Tipo:** funcional
- **F-XX / pantalla:** F-60 · W-15 Tabla de permisos · `/usuarios/permisos`
- **Persona:** Diego (Gerencia de Administración)
- **Pasos:** 1. Entrar a Usuarios → "Tabla de permisos". 2. Cambiar una celda (fila RRHH, alcance de Pedidos: "todas" → "sus obras"). 3. Con la red lenta (2,5 s en `PUT /api/permisos`), tocar "Guardar cambios". 4. Mientras guarda, tocar "Obras" en la barra (o "Sectores", o Atrás). 5. No tocar nada y esperar a que termine.
- **Esperado:** (O-1.1, como en Plazos, Mi perfil, Editar obra, el presupuesto por rubro y los diálogos) "Estamos guardando. Cuando termine te llevamos." y, al terminar bien, ir a la pantalla elegida.
- **Obtenido:**
  - **Mientras guarda:** sale "Tenés cambios sin guardar. ¿Salir igual?" / "Si salís, se pierde lo que cambiaste." / "Seguir editando" · "Salir", pero el cambio ya se está guardando.
  - **Al terminar (200):** aparece "Guardamos la tabla de permisos. Cada persona lo ve al pasar a otra pantalla o al recargar la página." y la pregunta "Tenés cambios sin guardar. ¿Salir igual?" **sigue abierta encima**, aunque ya no hay nada sin guardar. La persona no va a "Obras": queda en `/usuarios/permisos`.
  - **Si toca "Salir" mientras guarda:** se va, y el guardado termina sin que lo vea.
  - **Reproducido 3 veces:** barra, "Sectores" y Atrás.
  - **Control:** con la misma demora, Plazos, de la misma sección, muestra "Estamos guardando. Cuando termine te llevamos." y lleva a "Obras".
- **Evidencia:** `capturas/A-01-1.png` (mientras guarda) · `capturas/A-01-2.png` (barra: guardado y la pregunta abierta) · `capturas/A-01-3.png` (Atrás) · `logs/A-01.log` · `PUT /api/permisos` → 200 · script `A/o11.mjs permisos`
- **Causa probable (opcional):** `src/features/identidad/pantallas/TablaPermisosPantalla.tsx:45` llama `useCambiosSinGuardar(cambios.size > 0)` sin el segundo argumento (`guardar.isPending`), y `enviar` (líneas 63-68) no llama a `salida.permitirSalida()` ni a `salida.terminoBien()` después del `mutateAsync`. El commit `63fd81b` (O-1) tocó Plazos, Mi perfil y los diálogos, pero no esta pantalla.
- **Regresión de:** no; arreglo incompleto de O-1 (front `63fd81b`; pantalla no cubierta)

### A-02 · Editar proveedor: con Atrás mientras guarda, al terminar va a la lista con el diálogo de edición todavía abierto
- **Severidad:** media
- **Tipo:** funcional
- **F-XX / pantalla:** F-55 · W-14 Proveedores · `/proveedores/{id}` → `/proveedores`
- **Persona:** Diego (Gerencia de Administración)
- **Pasos:** 1. En Proveedores, buscar "QA4-A Ferretería Diálogo" y abrir la ficha (`/proveedores/2`). 2. Tocar "Editar" y cambiar "Contacto · teléfono". 3. Con la red lenta (2,5 s en `PATCH`), tocar "Guardar proveedor". 4. Mientras guarda, tocar Atrás. 5. Esperar a que termine (Atrás estaba frenado con "Estamos guardando. Cuando termine te llevamos.").
- **Esperado:** (O-1.1) al terminar bien, ir a la lista, con el diálogo cerrado, como pasa con el resto de los diálogos (documento, cambiar estado, usuario, sector y alta de proveedor).
- **Obtenido:**
  - La app va a `/proveedores`, pero queda abierto el diálogo "Editar proveedor · QA4-A Ferretería Diálogo 29019", con el teléfono nuevo, "Cancelar" y "Guardar proveedor".
  - No aparece "Guardamos los cambios de …". El cambio sí se guardó (`PATCH /api/proveedores/2` → 200; en la base, `telefono = 291 400-2896`), pero la pantalla parece decir que todavía falta guardar.
  - "Cancelar" lo cierra sin preguntar.
  - **Reproducido 3 veces** (17:27, 17:28 y 17:48).
  - **Control:** el alta ("+ Nuevo proveedor" → Guardar → Atrás) termina bien: va a `/obras`, sin diálogo, y crea el proveedor.
- **Evidencia:** `capturas/A-02-1.png` · `capturas/A-02-2.png` · `capturas/A-02-3.png` · `capturas/O-1-1-r4-prov-atras-ok-2.png` · `logs/A-02.log` · script `A/prov-atras.mjs`
- **Causa probable (opcional):** `src/features/proveedores/components/ProveedorDialog.tsx:94` hace `if (salida.permitirSalida()) return;` antes de `onGuardado(guardado)`, así que el diálogo no se cierra. Como la lista y la ficha son la misma pantalla (`ProveedoresPantalla.tsx`, que abre `ProveedorDialog` en la línea 164), al ir de `/proveedores/2` a `/proveedores` no se desmonta y el diálogo queda abierto. En los otros diálogos, Atrás lleva a otra pantalla y el diálogo desaparece con ella.
- **Regresión de:** no; arreglo incompleto de O-1 (front `63fd81b`)

## Decisiones aprobadas

| decisión | estado | dónde se probó | evidencia | nota |
|---|---|---|---|---|
| O-1.1 · Irse mientras guarda: si guarda bien, lleva al destino; si falla, se queda con el error | ⚠️ | **Pantallas** (link, barra y Atrás × ok / 422 / 409 / 500): Editar obra, Nueva obra, presupuesto por rubro, Plazos, Mi perfil (barra y Atrás), Tabla de permisos. **Diálogos con Atrás** (ok / 500): editar proveedor (ok / 422 / 409 / 500), alta de proveedor, documento, cambiar estado, usuario y sector. | `O-1-1-r4-rubro-*`, `O-1-1-r4-editar-obra-*`, `O-1-1-r4-nueva-obra-*`, `O-1-1-r4-plazos-*`, `O-1-1-r4-perfil-*`, `O-1-1-r4-permisos-*`, `O-1-1-r4-prov-*`, `O-1-1-r4-dlg-*`, `O-1-1-r4-prov-alta-atras.png` | **Lo que anda bien** (rubro, Editar obra, Nueva obra, Plazos, Mi perfil y todos los diálogos salvo editar proveedor):<br>• **mientras guarda:** "Estamos guardando. Cuando termine te llevamos." / "No cierres esta pestaña hasta que termine." / "Entendido";<br>• **ok:** va solo al destino (ficha, `/proveedores`, `/obras`, `/usuarios/sectores`, `/usuarios`);<br>• **422:** se queda con el error del campo;<br>• **409:** se queda con "Alguien cambió esto…" y "Actualizar";<br>• **500:** se queda con "El sistema tuvo un problema. Volvé a intentar en un momento.".<br>**No anda** en la Tabla de permisos (**A-01**) ni en editar proveedor con Atrás (**A-02**).<br>Nueva obra · 409: no aplica (el alta no tiene versión). Con un 409 forzado, la pantalla se queda sin aviso. |
| O-1.2 · Mientras un diálogo guarda, Escape, "Cancelar" y clic afuera avisan y no cierran | ✅ | 14 diálogos que guardan: alta y edición de proveedor; alta y edición de usuario; agregar, renombrar y sacar sector; alta, edición y quitar adicional; alta, edición y quitar documento; cambiar estado | `O-1-2-r4-<diálogo>.png` (14) | **En los 14, con los tres gestos:** dentro del diálogo, junto a los botones, sale "Estamos guardando. Esperá unos segundos." y el diálogo no se cierra (sigue 1 diálogo).<br>**Al terminar:** se cierra solo y muestra el aviso de éxito ("Dimos de alta a …", "Guardamos los cambios de …", "Agregamos …", "Renombramos … a …", "Sacamos …", "Guardamos el presupuesto.", "Quitamos …", "La obra pasó a En ejecución.", "Agregamos el documento a la obra."). El alta de adicional va al presupuesto por rubro, como corresponde a "Guardar y cargar montos por rubro →". |

## Lo que no pude probar y por qué

- **Dos guardados en el mismo segundo:** no se probó (límite aceptado). Siempre hubo ≥ 1,6 s entre guardados.
- **Mi perfil por "link":** la pantalla no tiene un link propio para salir. Se probó con la barra, Atrás y F5.
- **Conflicto con dos personas en "Monto del presupuesto":** Carla (Compras) no ve ese campo (`monto_obra = sin_acceso`). Ese caso se probó con dos pestañas de Diego. Hernán (Jefatura) solo ve el presupuesto por rubro: no puede guardar.
- **409 real en Nueva obra:** el alta no lleva versión. El 409 forzado no corresponde a un caso real; no lo reporto.
- **Nota para el Verificador B (fechas, no es de mi rol):** en Chromium headless, los `<input type="date">` (Fecha del documento, Inicio y Fin previstos) se ven como "mm/dd/yyyy" / "11/18/2026" (`capturas/O-1-2-r4-doc-alta.png`, `O-1-1-r4-nueva-obra-link-409-2.png`). Es el control nativo del navegador, que sigue el idioma del navegador y no el `locale` del contexto. Habría que mirarlo en un Chrome en español antes de tomarlo como hallazgo.

---
Firma · Verificador A: PU-201, PU-204, PU-002 y PU-006 ✅, con dos pestañas y dos personas (Diego, Carla, Hernán, Pablo). Probé O-1.1 en 6 pantallas y 7 diálogos (ok / 422 / 409 / 500), y O-1.2 en 14 diálogos. 2 hallazgos nuevos medios (A-01, A-02). 0 ingresos reales.
