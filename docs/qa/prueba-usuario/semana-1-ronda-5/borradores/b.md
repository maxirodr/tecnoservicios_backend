# Borrador B · Verificador B: fechas, textos y buscador · Ronda 5

- **Fecha:** 2026-11-19, de 12:05 a 12:30 (hora de Argentina; 15:05–15:30 UTC). Probé desde los worktrees `_qa5` en `staging`: back `6516ba7` (+ docs `f36c23a`) y front `d2a4806`. La API estuvo sana todo el rato: health 200 y `errors-2026-11-19.log` sin entradas.
- **Personas y sesiones:**
  - Diego (`diego.json`): obra, ficha, Editar obra, Plazos, Usuarios, destrabar.
  - Laura, Sofía, Carla y Dora: buscador.
  - `qa-lead-r5bk` (id 12): solo como el usuario "trabado". El bloqueo lo puse con `page.route` y no se pidió ningún código.
  - No cerré ni borré ninguna sesión compartida.
- **Ingresos reales gastados:** **0** (tope 0).
- **Escrituras en la base:** solo `UPDATE presupuestos SET created_at=…` sobre **mi** presupuesto (id 2). Lo restauré y lo comprobé.
- **Datos creados (prefijo QA5B):**
  - Obra id 2 "QA5B Obra fechas", presupuesto `2-055-2026` (escrito "2 55 2026"), con inicio previsto 01/12/2026 y fin previsto 31/12/2026.
  - Documentos "QA5B Certificado 1ro" (01/11/2026) y "QA5B Certificado fin de año" (31/12/2026).
  - Los dos 422 de prueba del N.º no crearon obra. Quedaron 2 adjuntos huérfanos de link, que limpia la purga.
- **Plazos:** los cambié a 2/7 y los volví a 1/7 dos veces. Quedaron en **1 / 7** (comprobado en la base).
- **"Destrabar ingreso":** hice 19 `DELETE /api/usuarios/12/bloqueo-ingreso` reales. Como no había bloqueo real, cada uno es idempotente: 200, sin cambios y sin actividad registrada.
- **Scripts:** `C:\Users\GREEN\AppData\Local\Temp\claude\4fe530b1c11d8e8d\f4efcdda-aa94-4434-aafd-2e4822d4d74b\scratchpad\r5\B\`:
  - `01-alta-obra`, `02-documentos`;
  - `03-cargado` (PU-302), `04-editar-fechas`;
  - `05-plazos` (PU-308);
  - `06-buscador` (PU-309);
  - `07-trabado` (PU-310 + nota §8);
  - `08-debug-lista`, `09-destrabar-rep` (B-01).
  - Salidas: `out-07-1.txt`, `out-07-2.txt`.

## Tabla de verificación

| PU | título corto | estado | pantallas revisadas | evidencia | nota |
|---|---|---|---|---|---|
| PU-302 | "Cargado" con el día de Argentina | ✅ | Ficha de obra 2 (tabla Presupuestos) y `GET /api/obras/2`, con el navegador en Argentina y en UTC | `PU-302-r5-control-{ar,utc}.png`, `PU-302-r5-1/3-{ar,utc}.png` (01:30), `PU-302-r5-2/4-{ar,utc}.png` (02:59), `PU-302-r5-restaurado-{ar,utc}.png` · `GET /api/obras/2` → 200 | **Original** `2026-11-19 15:08:45` → API `"created_at":"2026-11-19"` → "19/11/2026 · Diego".<br>**`2026-11-20 01:30:00`** → API `2026-11-19` → "19/11/2026 · Diego" (2 veces, AR y UTC).<br>**`2026-11-19 02:59:00`** → API `2026-11-18` → "18/11/2026 · Diego" (2 veces, AR y UTC).<br>**Restaurado** a `2026-11-19 15:08:45` (la base lo confirma; `updated_at` sin tocar) → "19/11/2026". |
| PU-302 · regresión: fechas de calendario | Fechas previstas y fecha del documento sin cambios | ✅ | Ficha (cabecera "Fechas previstas" y tabla "Certificados y otros documentos"), Editar obra (campos de fecha), diálogo "editar" del documento; AR y UTC | `PU-302-r5-*-{ar,utc}.png`, `PU-302-r5-editar-{ar,utc}.png` | La API manda `fecha_inicio_prevista=2026-12-01`, `fecha_fin_prevista=2026-12-31` y `documentos[].fecha=2026-11-01 / 2026-12-31`.<br>La ficha muestra "01/12/2026 → 31/12/2026", "01/11/2026" y "31/12/2026" en los dos husos.<br>En Editar obra los campos tienen `2026-12-01` / `2026-12-31`, y en el diálogo del documento, `2026-12-31`.<br>Ningún corrimiento de un día. |
| PU-302 · regresión: año del N.º | El N.º usa el año de Argentina | ✅ | Nueva obra (alta real y 2 rechazos) | salida de `01-alta-obra` | "2 55 2026" → se guarda `2-055-2026`.<br>"2-552-2028" → "El año tiene que estar entre 2000 y 2027." (año actual 2026 + 1).<br>"2-551" (sin año) → "Escribí solo el número, por ejemplo 2-131-2026." (el back no completa el año, lo exige).<br>A las 15 UTC el año es el mismo en las dos zonas, así que el borde de fin de año no se pudo forzar (ver no probado). |
| PU-308 | Plazos: los avisos se van al editar | ✅ | W-50 `/usuarios/plazos` (Diego), 2 vueltas | `PU-308-r5-{1,2}-a…e.png` · `PUT /api/plazos-prioridad` → 200 (×4) | 1. "Guardar" sin tocar nada → "No había cambios para guardar.".<br>2. Escribo 7 en Urgente → el aviso **se va** (0).<br>3. "Guardar" → solo "Prioritario tiene que ser mayor que Urgente.".<br>4. 2/7 → "Guardamos los plazos. Valen para los pedidos que se carguen desde ahora.".<br>5. Escribo 1 → "Guardamos…" **se va** (0 alertas).<br>6. Guardo 1/7.<br>Base final: `1 / 7`. |
| PU-309 | Pista del buscador según lo que ve cada uno | ✅ | W-18 `/buscar` con Laura, Sofía, Diego, Carla y Dora; 2 vueltas ("QA5B" y "3071") | `PU-309-r5-{1,2}-{laura,sofia,diego,carla,dora}.png` | **Laura y Sofía** (menú "Inicio · Obras"): "Por N.º de presupuesto, cliente, planta o nombre del trabajo.", y en la búsqueda solo "OBRAS".<br>**Diego, Carla y Dora** (los tres ven Proveedores): "Por N.º de presupuesto, cliente, planta, nombre del trabajo o proveedor (nombre o CUIT)."; con "3071" encuentran "QA5A Ferretería Sesión · 30-71955100-5".<br>La pista está asociada al campo (`aria-describedby`). |
| PU-310 | "Ingreso trabado hasta las HH:MM" en hora de Argentina | ✅ | W-16 `/usuarios` (línea de la fila) y diálogo "Editar usuario · QA Lead R5 BK"; Argentina, UTC y Madrid; 2 vueltas | `PU-310-r5-{1,2}-lista-{buenos_aires,utc,madrid}.png`, `PU-310-r5-{1,2}-dialogo-*.png`, `PU-310-r5-{1,2}-lista-borde{1,2}-{utc,madrid}.png` · `GET /api/usuarios` → 200 | **`hasta` = ahora + 1 h** (13:18 y 13:19 de Argentina): "· Ingreso trabado hasta las 13:18" y "QA Lead R5 BK no puede entrar hasta las 13:18: se equivocó varias veces con el código…", igual en los 3 husos.<br>**Borde** `2026-11-20T02:30Z` (19/11 23:30 en Argentina, pero ya 20/11 en UTC y en Madrid): "hasta las 23:30" en los 3.<br>**`2026-11-20T04:00Z`**: "hasta las 20/11/2026 01:00" en los 3. |
| Nota §8 | "Destrabamos el ingreso de …" aunque la ficha se recargue sin el bloqueo | ⚠️ | Diálogo "Editar usuario · QA Lead R5 BK"; "Destrabar ingreso" real (DELETE 200). Desde ese momento la ficha y la lista vuelven **sin** bloqueo (`page.route`); UTC y Madrid | `NOTA8-r5-1-{utc,madrid}.png`, `NOTA8-r5-2-utc.png` · `B-01-1/2/3.png` | **16 de 19 veces:** "Destrabamos el ingreso de QA Lead R5 BK.", sin el aviso de bloqueo, y al cerrar la fila ya no dice "Ingreso trabado".<br>**3 de 19** (Madrid 1 de 8, UTC 2 de 11): la ficha vuelve sin bloqueo pero **el aviso de éxito no aparece** (esperé hasta ~7 s). Ver **B-01**.<br>De paso: el aviso aparece recién cuando vuelve `GET /api/usuarios/12` (1,1–3,4 s después del clic); mientras tanto el botón dice "Destrabando el ingreso…". |

**Conteo:** ✅ 4 PU (302, 308, 309, 310) · ⚠️ 1 (nota §8 → B-01) · ❌ 0

## Hallazgos nuevos

### B-01 · "Destrabar ingreso": a veces no aparece "Destrabamos el ingreso de …"
- **Severidad:** baja
- **Tipo:** mensaje
- **F-XX / pantalla:** F-60 · W-39 Editar usuario (desde W-16 Usuarios) · `/usuarios` → diálogo "Editar usuario"
- **Persona:** Diego (Gerencia de Administración)
- **Pasos:**
  1. Tener un usuario con el ingreso trabado. Acá, `qa-lead-r5bk` con `bloqueo_ingreso.hasta` puesto con `page.route` en las respuestas `GET /api/usuarios` y `/api/usuarios/12`, sin quemar códigos.
  2. Abrir su diálogo y tocar "Destrabar ingreso". El `DELETE /api/usuarios/12/bloqueo-ingreso` es real y da 200; desde ese momento la intercepción deja de poner el bloqueo, como haría un destrabe real.
  3. Esperar con el diálogo abierto.
- **Esperado:** "Destrabamos el ingreso de QA Lead R5 BK." a la vista (nota §8 de la ronda 4, arreglo de la ronda 5).
- **Obtenido:**
  - El aviso de bloqueo desaparece y el diálogo queda sin ningún aviso: ni éxito ni error. Así se ve en `B-01-1.png` (Madrid) y en `B-01-2.png`, `B-01-3.png` (UTC).
  - Pasó 3 de 19 veces, con la misma secuencia de red que cuando sale bien: `DELETE` → 200, y después `GET /usuarios?activo=true&page=1`, `/usuarios/opciones` y `/usuarios/12` → 200.
  - En una de las tres, todo terminó a los 827 ms del clic y el aviso no apareció en los ~7 s siguientes.
  - Reproducido 3 veces (`07-trabado.mjs 2`, `09-destrabar-rep.mjs 3 UTC` #2 y `09-destrabar-rep.mjs 6 UTC` #4).
  - En las 16 que salen bien se ve un instante (~200 ms) sin ningún aviso antes del éxito: es la misma carrera.
- **Evidencia:** `capturas/B-01-1.png`, `capturas/B-01-2.png`, `capturas/B-01-3.png` · `DELETE /api/usuarios/12/bloqueo-ingreso` → 200 · salidas `out-07-2.txt` y consola de `09-destrabar-rep.mjs`
- **Causa probable (opcional):**
  - `src/features/identidad/components/BloqueoIngreso.tsx:34-37` pasa `onDestrabado` como `onSuccess` **del `mutate`**. En TanStack Query 5 ese callback no se llama si el componente que hizo el `mutate` ya se desmontó.
  - El `onSuccess` del hook (`useQuitarBloqueoIngreso`, `hooks/useUsuarios.ts:50-52`) espera la invalidación. Mientras tanto vuelve `/usuarios/12` sin `bloqueo_ingreso`, y `UsuarioFormulario.tsx:143-153` desmonta `BloqueoIngreso`. Según quién gane la carrera, `setDestrabado` nunca corre.
  - Pasar el aviso al `onSuccess` del `useMutation`, o llamar al `mutate` desde `UsuarioFormulario`, evitaría depender del componente que se va.
- **Regresión de:** no; arreglo incompleto de la nota §8 de la ronda 4 (front `d2a4806`)

## Formato de fecha (decisión aprobada)

| decisión | estado | dónde se probó | evidencia | nota |
|---|---|---|---|---|
| Fechas `dd/mm/aaaa` y `dd/mm/aaaa HH:MM` | ✅ | Ficha: "Cargado", "Fechas previstas" y documentos. Historial de Editar obra ("19/11/2026 12:09 · Diego", 15:09 UTC = 12:09 en Argentina, igual en AR y UTC). Usuarios: "hasta las 13:18" y "hasta las 20/11/2026 01:00". Navegador en Argentina, UTC y Madrid | `PU-302-r5-*.png`, `PU-302-r5-editar-*.png`, `PU-310-r5-*.png` | Sin ningún formato distinto. Los campos `type=date` muestran la máscara del navegador (B-02, límite conocido). |

## Lo que no pude probar y por qué
- **Año del N.º en el borde de fin de año** (31/12 entre las 21:00 y las 24:00 de Argentina, cuando UTC ya es el año siguiente): no se puede forzar la hora del servidor sin tocar el entorno. Probé el tope que depende de ese año ("entre 2000 y 2027") y leí el código: `ArmaPresupuestos.php:67` usa `Carbon::now(app.zona_empresa)`.
- **"Cargado" de un presupuesto adicional:** no lo cargué. Sale del mismo `PresupuestoResource`.
- **Ingreso trabado real:** 0 ingresos por consigna. El bloqueo fue solo en las respuestas (`page.route`), y el destrabe real fue un `DELETE` idempotente sobre `qa-lead-r5bk` sin bloqueo.
- **B-01 con un bloqueo real:** no se puede sin quemar códigos. La carrera depende de la ficha recargada sin `bloqueo_ingreso`, que es justo lo que devuelve el back después de un destrabe real.

---
Firma · B: PU-302 ✅ (01:30 → 19/11 y 02:59 → 18/11, 2 veces cada uno, por API y pantalla, en AR y UTC; restaurado y comprobado). Regresiones de fechas de calendario y del año del N.º ✅. PU-308 ✅ (Plazos 1/7 comprobado), PU-309 ✅ (5 personas), PU-310 ✅ (3 husos y 2 bordes), nota §8 ⚠️ → B-01 (baja, 3 de 19). Formato de fecha ✅. 0 ingresos reales, prefijo QA5B · 2026-11-19.
