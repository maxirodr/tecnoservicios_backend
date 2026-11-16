# Borrador · Tester funcional de Obras (OBR)

Personas: Hernán (Jefatura de Proyectos), Laura (Ingeniería de proyectos); en solo lectura Diego
(Gerencia de Administración), Carla (Compras), Pablo (Ingeniería). Datos creados (ficticios):

| id | N.º | Trabajo | Responsable | Cómo se creó |
|---|---|---|---|---|
| 1 | 2-701-2026 | QA-OBR Piso industrial nave 7 | Laura | W-06 por pantalla (Hernán) |
| 6 | 2-702-2026 | QA-OBR Galpón de Pablo | Pablo | API (mismo payload que W-06) |
| 7 | 2-703-2026 | QA-OBR Obra de Hernán | Hernán | API |
| 9 | 2-704-2026 | QA-OBR Obra para terminar | Laura | API |

> **Bloqueo de entorno durante la prueba:** desde las 14:50 UTC Redis dejó de responder
> (`errors-2026-11-16.log`: "No connection could be made because the target machine actively
> refused it [tcp://127.0.0.1:6379]", cada 2–3 s) y `GET /api/health` devuelve 500
> "El sistema tuvo un problema. Volvé a intentar en un momento." durante más de 40 minutos.
> Como mi rol no puede levantar procesos, la segunda mitad de la cobertura quedó con scripts
> listos pero sin correr (ver "No probado"). No lo reporto como hallazgo de Obras: corresponde al
> Lead / QA de backend (puede ser la prueba de "Redis caído").
> A las 15:55 UTC la API volvió (health 200), pero **las seis sesiones guardadas
> (`sesiones\*.json`) quedaron inválidas**: `GET /api/me` → 401 "Tenés que ingresar para seguir."
> para diego, hernan, laura, sofia, pablo y carla (las sesiones vivían en Redis y se perdieron al
> reiniciarlo). Por la regla del brief no pedí códigos nuevos; el resto queda a la espera de que
> el Lead regenere las sesiones.

## Hallazgos

Ninguno confirmado en lo que se llegó a recorrer (dos veces cada camino). Observaciones abajo.

### Observaciones (no son bug)

- **OBS-1 · Carla y Diego aparecen como responsables posibles** (filtro "Responsable" de W-05 y
  desplegable "Responsable de obra" de W-06: "Carla · Compras", "Diego · Gerencia de
  Administración"). Es lo que dice la spec (`obras.md` §4.1: "cualquier usuario activo"), así que
  no es bug. Pero el filtro del listado lista a todos los usuarios activos y no a los que de hecho
  son responsables de alguna obra: con 50 personas el desplegable va a ser largo y elegir a
  Carla da "No encontramos obras con esa búsqueda". Sugerencia para producto: en el filtro, solo
  responsables con obras; en el alta, primero operaciones. Capturas `OBR-1-listado-vacio-pc.png`.
- **OBS-2 · "Documento de aprobación" no tiene opción vacía** en W-06: arranca en "Orden de
  compra" aunque el campo diga "(opcional: se puede cargar después)". Coincide con RN-OBR-16 (sin
  tipo se asume OC) y si no se carga número ni adjunto no se guarda nada, así que no es bug; solo
  puede confundir ("¿ya dije que hay OC?").
- **OBS-3 · Laura no tiene "solo sus obras" en Obras:** la configuración inicial le da Obras =
  Editar · todas (`identidad.md` §6.3, verificado en `GET /api/me`). "Solo sus obras" lo tiene en
  Presupuesto por rubro, Gastos, Rendición y Control por cantidad. Por eso Laura y Pablo ven y
  editan obras de las que no son responsables, y es correcto. El alcance "propias" de Obras no se
  pudo probar sin crear un sector propio (ni Hernán ni Laura tienen permiso; no se tocaron sectores).

## Cobertura

| F-XX | Estado | Hallazgos | Qué probé |
|---|---|---|---|
| F-15 | ⚠️ parcial | OBS-1 | Listado vacío (Hernán): "En ejecución (0) · Confirmadas (0) · Terminadas (0)", "No hay obras en esta pestaña"; filtro Responsable (lista). Pestañas con datos, buscador y filtro aplicado: script listo, no corrido (Redis caído). |
| F-16 | ✅ | OBS-2 | W-06 por pantalla como Hernán: enviar vacío → 11 errores al lado de cada campo, en español ("Completá el N.º de presupuesto, por ejemplo 2-131-2026.", "Subí la oferta técnica o pegá su link.", "Elegí el responsable de obra.", …). N.º escrito " 2 701 2026 " → guardado "2-701-2026" REV01. Oferta PDF con acentos, ñ y espacios en el nombre. OC con link sin `https://` → guardado "https://drive.google.com/…". Carpeta de Drive sin `https://` → completada. Fin previsto antes del inicio → "La fecha de fin no puede ser anterior a la de inicio." al lado del campo, sin borrar nada. Doble clic en "Guardar obra" → una sola obra (1 en Confirmadas). Nace Confirmada. |
| F-17 | ✅ (PC) | — | Ficha de la obra 1 como Hernán, Laura, Carla, Pablo y Diego: cabecera (cliente/planta, responsable, N.º de OC, presupuestos, Links "Presupuesto · OC · Carpeta de Drive", fechas "20/12/2026 → 31/03/2027"), pestaña única "Documentos y contactos" (Pedidos y Rendición ocultas en semana 1, correcto). Sin errores de consola ni 4xx/5xx. F5 mantiene los datos. Celular: no llegué (Redis). |
| F-18 | ⚠️ parcial | — | Principal con oferta (archivo) y OC (link) en el alta: se ven en "Presupuestos" y en "OC / OT / contrato / mail de aprobación". Adicionales y monto de la obra: no corrido. |
| F-19 | ⚠️ parcial | — | Ficha muestra oferta como archivo "· 1 KB", OC como link "(link, se abre en otra pestaña)", carpeta de Drive como "Abrir carpeta de Drive de la obra". Agregar/cambiar/quitar documentos y descarga: script listo, no corrido. |
| F-20 | ❌ sin probar | — | Script listo (guardar sin cambios, errores, revisión, OC, oferta archivo→link, dos pestañas, doble clic). |
| F-21 | ⚠️ parcial | — | Botón "Cambiar estado / pasar a Terminada" solo para Laura (responsable) y Diego (Estado de obra = Editar); Hernán y Pablo (no responsables, Estado de obra = Ver) y Carla no lo ven (`puede.cambiar_estado`). Transiciones: script listo, no corrido. |
| F-22 | ❌ sin probar | — | Depende de F-21. |
| F-23 | ✅ | — | Inicio/fin previstos en el alta, validación fin ≥ inicio, se muestran en la ficha. Edición: no corrido. |
| F-48 | ⚠️ parcial | — | Visibilidad del botón "Presupuesto por rubro": Hernán (Ver·todas) y Carla (Editar) lo ven; Pablo (Ver·solo sus obras) no lo ve en la obra de Laura; correcto. Carga de montos: no corrido. |
| F-14 | ⚠️ parcial | — | Alta deja "Creó la obra", "Oferta técnica: agregado (oferta QA-OBR 2-701-2026 ñandú.pdf)", "Archivo o link de la aprobación: agregado (drive.google.com)", quién = Hernán, sin URL. B-05 (importes): no corrido. |
| F-03 | ❌ sin probar | — | Script listo (`/buscar?q=` como Hernán y Carla). |
| B-01 / B-02 / B-03 | B-02 ✅ en ficha PC | — | Sin "(W-xx)" ni códigos en listado, alta y ficha (PC). B-01 (Pixel 7) y B-03: no corrido. |

## No probado

Todo lo siguiente quedó con script escrito en
`C:\Users\GREEN\AppData\Local\Temp\claude\4fe530b1c11d8e8d\f4efcdda-aa94-4434-aafd-2e4822d4d74b\scratchpad\qa\obr\`
(`s3-estado.cjs`, `s4-montos.cjs`, `s5-editar.cjs`, `s6-listado.cjs`, `s7-docs.cjs`) y sin
correr porque la API respondió 500 en todo pedido desde las 14:50 UTC (Redis caído, ver arriba):

- F-21/F-22: transiciones (avanzar, volver, saltar Confirmada→Terminada, estado inventado), 403
  para Hernán/Carla/Pablo en obras ajenas, qué se puede editar en una Terminada.
- F-48 / monto de la obra: carga por rubro, negativo, decimales con coma, cambio a "solo el
  total" con confirmación, `monto_obra` ausente para Laura/Hernán, líneas de monto ocultas en el
  historial; **B-05** (importes formateados en el historial).
- F-20 / F-14: guardar sin cambios (no agrega líneas), errores de edición, oferta archivo→link,
  doble clic, dos pestañas editando la misma obra.
- F-19: certificado sin fecha, archivo + link, `javascript:`, editar quitando el archivo, quitar
  documento, descarga del archivo subido, `GET /api/adjuntos/999999`.
- F-15/F-03: pestañas con datos, buscador ("2701", "2 701 2026", "NAVE 7", "galpon"), filtro por
  responsable, volver desde la ficha conserva filtros, buscador general.
- Rutas raras (`/obras/999999`, `/obras/abc`, `/obras/999999/editar`), Carla en `/obras/nueva` y
  `PATCH /api/obras/1` (403 esperado).
- Celular (Pixel 7 / iPhone SE): listado, ficha (B-01), editar.
- Alcance "solo sus obras" en Obras: la configuración inicial no lo usa (OBS-3).
