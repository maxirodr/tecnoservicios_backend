# Consolidación QC · Prueba de usuario Semana 1 · Ronda 2 (2026-11-17)

Fuente: borradores `va.md`, `vb.md`, `reg.md`, `per.md`, `ent.md`, `uxm.md`, resultados del Lead (PU-005/039, PU-016, PU-021, PU-045; `logs\PU-021-worker-parado.log`, `logs\PU-045-bloqueo.log`) y `capturas\`.
Resultado: `..\verificacion.md` (45 PU) y `..\hallazgos.md` (PU-101 … PU-137; **próximo número libre: PU-138**).

Criterio de estado F-XX (igual que la ronda 1): ✅ sin hallazgos abiertos · ⚠️ con hallazgos alta, media o baja · ❌ con al menos un bloqueante. "Abiertos" = PU-1XX nuevos + PU-0XX que quedaron ⚠️/❌ en `verificacion.md`.

## (a) Cobertura F-XX

| F-XX | Estado | PU | Quién lo cubrió y qué quedó |
|---|---|---|---|
| F-01 Ingreso con mail + código | ⚠️ | PU-004 (⚠️), PU-105, PU-106, PU-109, PU-133 | LEAD: límite por mail (PU-016 ✅), worker caído (PU-021 ✅), bloqueo y destrabe (PU-045 ✅). PER: lo visible sin códigos. REG: vencimiento real con QA-LEAD Vence (2 ingresos). Lo abierto es la sesión vencida y la coherencia de textos. |
| F-02 Menú según el sector | ⚠️ | PU-132 | PER (6 personas, celular "Más", URL directa sin permiso en 6 rutas × 3 ingenieras), ENT (Sofía). PU-014 ✅. |
| F-03 Buscador general | ⚠️ | PU-137 | VA (signos, PU-009 ✅), VB (CUIT, PU-044 ✅), PER (6 personas × 6 búsquedas), UXM (loader, celular). |
| F-10 Mi perfil | ⚠️ | PU-108, PU-127 | PER (Dora, Diego, Laura, Pablo, Carla), ENT (Sofía: vacío, largos, salir con cambios), REG (pisado por Usuarios), UXM (celular). |
| F-14 Historial | ⚠️ | PU-113 | PER (alta, adicional, documentos, edición, estados; montos solo para Diego), VB (fechas dd/mm/aaaa, PU-032 ✅). Importes con un decimal también en el historial ("12.650.000,5"). |
| F-15 Listado de obras por estado | ✅ | — | VA/UXM/REG/ENT (loaders PU-017 ✅), VB ("solo sus obras" PU-023 ✅, pestañas PU-040/042 ✅), PER (contadores, volver conserva pestaña y búsqueda). |
| F-16 Alta de obra | ⚠️ | PU-106, PU-107, PU-111, PU-115, PU-117, PU-118, PU-120, PU-122, PU-128, PU-131 | ENT (vacío, largos, emojis/HTML, mails, fechas, links, N.º), PER (Hernán, OC link y archivo), REG (cambios sin guardar, repetido, sesión), UXM (Pixel 7, iPhone SE, 500 simulado). PU-001/007/012 ✅. |
| F-17 Ficha de la obra | ✅ | — | PER (botones según quién mira, 7 personas, iPhone SE), UXM (celular, teclado), VB (PU-041 en la ficha). |
| F-18 Presupuestos (principal y adicionales) | ⚠️ | PU-112, PU-120, PU-128 | PER (adicional sugerido, editar con link), ENT (N.º vacío, mal escrito, repetido, año fuera de rango), VA (PU-019 ✅). Los datos del principal en Editar obra: ver F-20. |
| F-19 Documentos y contactos | ⚠️ | PU-003 (⚠️), PU-103, PU-104, PU-112, PU-114, PU-117, PU-120, PU-136 | ENT (0 B, 20/21 MB, tipos, links, nombre 300), VA (PU-001/003/011/012), VB (PU-026/027/028/030), PER (certificado con PDF + link), REG (salir mientras guarda), UXM (diálogo en iPhone SE, teclado). |
| F-20 Editar la obra | ❌ | PU-002 (❌), PU-101, PU-102, PU-104, PU-105, PU-109, PU-111, PU-117, PU-118, PU-121, PU-129 | VA (conflicto entre personas), REG (dos pestañas, presupuesto principal, sesión, espacios), ENT (inválidos, sin cambios, carpeta), UXM (carga, aviso fuera de vista), VB/PER (historial, revisión). |
| F-21 Cambiar estado | ⚠️ | PU-104, PU-125 | PER (Hernán hasta Terminada, permisos), VA (Cancelar mientras guarda ✅), ENT (doble clic, API), REG ("Atrás" mientras guarda), UXM (celular). |
| F-22 Obras terminadas consultables | ⚠️ | PU-125 | PER (pestaña, buscable, ficha y edición). |
| F-23 Fechas previstas | ⚠️ | PU-117 | PER (alta y edición; historial legible), ENT (1900/2199 sin aviso). |
| F-48 Presupuesto por rubro | ⚠️ | PU-113, PU-130 | VA (PU-015 ✅, PU-022 ✅), PER (Diego, Laura, Sofía, Pablo, Carla, Dora), ENT (16 variantes de monto, doble clic), UXM (Carla en Pixel 7 e iPhone SE). |
| F-55 Proveedores con buscador | ⚠️ | PU-137 | VB (CUIT, PU-044 ✅; ids raros PU-024 ✅), PER (Carla), UXM (búsqueda y celular), VA (signos). |
| F-56 Alta y edición de proveedor | ⚠️ | PU-013 (⚠️), PU-043 (⚠️), PU-104, PU-105, PU-107, PU-110, PU-114, PU-116, PU-119, PU-131, PU-136 | VA/VB (repetidos, ñ, CUIT, puntos, PU-034/035/036/038), PER (alta, CUIT y mail), ENT (`/proveedores/nuevo` con todos los inválidos), REG (conflicto, sesión, doble clic, Ctrl+clic, atrás), UXM (celular, teclado). |
| F-58 Usuarios: alta, edición y baja | ⚠️ | PU-108, PU-114, PU-131 | PER (Dora: alta, mail repetido, baja con aviso, reactivar, uno mismo; Diego en celular), ENT (validaciones), REG (conflicto 409 ✅), VB (sin cambios ✅), LEAD ("Ingreso trabado" y "Destrabar ingreso"). |
| F-59 Sectores | ⚠️ | PU-124, PU-135 | PER (alta con permisos iniciales, renombrar, sacar con usuario), VA (doble clic ✅), VB (renombrar igual ✅), ENT (validaciones), UXM (celular). |
| F-60 Tabla de permisos | ⚠️ | PU-126, PU-134 | PER (fila QA-PER: cambios, guardar, F5, descartar), VA (doble clic ✅), REG (pestañas con cambios ✅), UXM (celular). |
| F-61 Jerarquía ("depende de") | ✅ | — | PER (círculo indirecto, uno mismo fuera del desplegable, Mi perfil muestra el jefe). |
| F-68 Plazos de la prioridad | ⚠️ | PU-123, PU-132 | PER (Dora solo lectura, Diego sin cambios), ENT (Sofía sin acceso; mirar sin guardar), UXM (celular). |
| **Transversales** | | | |
| Adjuntos (ADR-014) | ⚠️ | PU-103, PU-106, PU-112, PU-115, PU-120, PU-129 | VA/VB/ENT/REG: link en curso bloquea guardar (PU-001 ✅), vacío, tipos, renombrado, palabras sueltas, ñ y espacios, 20/21 MB, nombre del link. |
| Sesión (vencida, borradores, salir) | ⚠️ | PU-004 (⚠️), PU-105, PU-106, PU-109, PU-133 | VA (alta de proveedor, ingreso real), REG (obra y proveedor, ingreso real, cambio de persona simulado), ENT (401 simulado). |
| Conflictos (dos pestañas / dos personas) | ❌ | PU-002 (❌), PU-101, PU-102, PU-108, PU-111 | VA (dos personas en la obra), REG (obra, presupuesto principal, proveedor, usuario, Mi perfil), ENT y UXM (obra). |
| Cambios sin guardar | ⚠️ | PU-006 (⚠️), PU-104, PU-107, PU-114 | VA, REG, ENT (F5, atrás, links, Cancelar, pestañas, Ctrl+clic, diálogos), UXM (teclado del aviso ✅). |
| 403 / 404 | ⚠️ | PU-132 | VB/REG (proveedor no encontrado), PER/ENT (sin acceso, obra no encontrada), VA/Lead (API sin sesión → 401, PU-005 ✅). |
| Celular | ⚠️ | PU-022 (⚠️), PU-041 (⚠️), PU-111, PU-134, PU-135 | UXM (24 pantallas × Pixel 7 e iPhone SE 375×667), VA/VB/PER/REG (Pixel 7 e iPhone SE en sus pantallas). |
| Accesibilidad | ⚠️ | PU-018 (⚠️), PU-111, PU-136 | UXM (teclado, foco, `aria-*`, aviso de cambios sin guardar ✅, errores de campo ✅), VB/REG (pestañas con flechas ✅). Sin lector de pantalla real. |
| Loaders | ✅ | — (a la vista) | UXM (2,5 s y 15 s), VA (PU-017/018/019 ✅), VB (PU-037 ✅), ENT. El anuncio accesible del "Guardando…" está en PU-136 (accesibilidad). |
| Mails y cola | ✅ | — | LEAD (PU-021: worker parado → health 503 `"cola_mail": false` y el mail sale una vez al relanzar; PU-016 límite por mail). |
| Scheduler | ✅ | — | LEAD: `adjuntos:purgar-huerfanos` (0) e `idempotencia:purgar` (0) sin error; `schedule:work` sobrevivió al corte de Redis. |
| Health / canal `errors` | ⚠️ | PU-020 (⚠️), PU-138, PU-139 | Redis parado: 503 en 1,5–1,9 s ✅; MySQL parado: 503 en 9 s (PU-139); la app no se recupera sola (PU-138). LEAD: `/api/health` 200 y 503 con worker parado (0,7–0,9 s); sin sesión → 401 JSON; `errors-2026-11-17.log` nunca se creó en todo el día pese a 401/404/409/419/422/429 de todos los roles; `laravel-2026-11-17.log` sin ERROR/CRITICAL. Redis parado (503 < 3 s) lo completa el Lead. |

## (b) No probado (unificado)

| Qué | Por qué | Rol |
|---|---|---|
| Dos guardados en el mismo segundo | Límite aceptado por Juani (PU-002). | VA, REG |
| PU-002 con dos pestañas de la misma persona en la obra | VA usó dos personas (mismo control del servidor); REG sí usó dos pestañas de Diego (PU-101/102). | VA |
| PU-004 en otros formularios con ingreso real | Solo 2 ingresos autorizados por rol; usuario, Mi perfil, permisos, documentos y adicionales no tienen borrador por diseño. | VA, REG |
| Cambio de persona en la misma pestaña con ingreso real (PU-105) | Se simuló cargando la sesión guardada de la otra persona (sin gastar ingresos). | REG |
| PU-018 hasta la respuesta en Mi perfil y Tabla de permisos | Se cortó la escritura para no tocar datos del seeder. | VA |
| PU-041 en formularios y diálogos (VB) | VB midió Obras, ficha y Usuarios; UXM cubrió 24 pantallas y diálogos. | VB (cubierto por UXM) |
| Ficha de usuario en celular; proveedores búsqueda y ficha en Pixel 7 | Errores de los scripts de UXM al encontrar el elemento (no investigados). | UXM |
| Conflicto 409 en el celular | Solo se midió el 500 en iPhone SE; el aviso sale en el mismo lugar. | UXM |
| Lector de pantalla real (NVDA/VoiceOver) y teclado virtual del celular | Solo árbol de accesibilidad y atributos; Playwright no emula el teclado virtual. | UXM |
| Teclado en "Destrabar ingreso" (foco, anuncio del aviso) | No había usuarios trabados durante el turno de UXM. | UXM |
| Textos de ENT-09 en pantalla (419, 429 del front y backend, idempotencia) | Solo leídos en el código. | ENT |
| Sesión vencida real (sin simular) en ENT | ENT simuló el 401; REG y VA sí la probaron con ingreso real. | ENT |
| "Solo podés ver esta sección" con Diego/Sofía | No hay pantalla en ese caso para ellos; VA y PER lo vieron con Carla y Pablo. | ENT |
| Alcance "solo sus obras" en Obras con un sector propio de PER | Hacía falta pedir un código; VB lo cubrió con QA-VB Vera (PU-023 ✅). | PER |
| Celular de las entradas inválidas | Solo PC; el celular lo cubrió UXM con altas reales. | ENT |

## (c) Observaciones (no son bug)

1. **"¿Salir igual?" (ENT):** el botón destacado es "Salir"; conviene que el principal sea el seguro, "Seguir editando".
2. **"Recuperamos lo que estabas escribiendo." (ENT):** no dice que todavía no está guardado; mejor "… Revisalo y guardá.".
3. **"No hay cambios para guardar." vs "No había cambios para guardar." (ENT):** el presupuesto por rubro (`EditorMontos.tsx:79`) usa el presente; el resto, `MENSAJE_SIN_CAMBIOS` en pasado.
4. **Comillas «» en "Tocá «Agregar link» …" (ENT):** el resto de los textos usa "…"; las simples '…' del N.º repetido sí son hallazgo (PU-128).
5. **Link con espacios: dos textos distintos front/API (VA, PU-010):** "Eso no parece un link. …" vs "El link no puede tener espacios. …"; ninguno es falso.
6. **Carla en `/obras/{id}/editar` ve "Historial de la obra" (VA, PU-014):** es el historial al que llegan Dora y Carla por "Historial de cambios" (PER, F-14); por diseño.
7. **Pestañas sin `aria-controls` (UXM, PU-042):** hay un `role=tabpanel`; funciona con teclado.
8. **`radio` de 13×13 en "Cambiar estado" (UXM):** el toque es sobre el rótulo segmentado (≥ 44 px).
9. **Casillero "Ver inactivos" de 18×18 (VB, UXM):** su rótulo, que también lo marca, mide 111×44.
10. **"¿No te llegó? …" sale desde el primer código (ENT):** está bien porque arranca con "¿No te llegó?".
11. **Teléfono es texto libre (PER):** "abc" se guarda; coincide con la spec §4.1.
12. **Una sola vez, no reproducido (REG):** un `PATCH /api/obras/12` salió 200 justo después de borrar las cookies (probablemente una respuesta en vuelo volvió a poner la cookie); las 6 corridas siguientes dieron 401. No se reporta.
13. **Ronda 1 §(c) 4 promovida:** el N.º de presupuesto repetido con dos avisos y comillas simples, que la ronda 1 dejó como observación, ahora es PU-128.

## (d) Conteo

**PU de la ronda 1 (verificacion.md):** ✅ 32 · ⚠️ 12 (PU-003, 004, 006, 013, 018, 020, 022, 028, 029, 038, 041, 043) · ❌ 1 (PU-002).

**Hallazgos nuevos (hallazgos.md):** 39 · bloqueante 1 · alta 1 · media 11 · baja 26 · 23 regresiones (PU-138 y PU-139 los sumó el Lead).

| Tipo | Bloq. | Alta | Media | Baja | Total |
|---|---|---|---|---|---|
| funcional | 1 | 1 | 6 | 3 | 11 |
| validación | | | | 3 | 3 |
| mensaje | | | 4 | 15 | 19 |
| celular | | | | 2 | 2 |
| accesibilidad | | | | 1 | 1 |
| consistencia-con-documento | | | | 1 | 1 |
| error-servidor | | | 1 | 1 | 2 |
| **Total** | **1** | **1** | **11** | **26** | **39** |

## (e) Datos de prueba sobre usuarios del seeder

- **PER:** para probar Mi perfil, **Dora** y **Diego** quedaron unos segundos con teléfono "abc" / "xyz" (texto libre, se guarda); se **volvieron a dejar vacíos**, como estaban. El nombre no se tocó. PER también editó permisos, pero solo de su sector propio "QA-PER Taller Norte".
- **ENT:** probó Mi perfil de **Sofía** (espacios, 300 caracteres, teléfono de 60; salir con cambios); quedó **igual que estaba** (teléfono vacío).
- **REG:** el teléfono y el nombre que pisó Mi perfil (PU-108) son de **QA-LEAD Vence** (usuario del Lead, no del seeder); el nombre se restauró.
- **VA:** en PU-018 las escrituras de Mi perfil de Diego y de la Tabla de permisos se demoraron y **se cortaron**, para no modificar datos del seeder.
- **LEAD:** `qa-lead-2` quedó bloqueado y Diego lo destrabó (queda en `actividades`, id 197, `bloqueo_ingreso_quitado`).
- Todos los demás datos son propios y ficticios (obras 2-3XX…2-8XX-2026 "QA-…", proveedores/sectores/usuarios "QA-…", `@empresa-ejemplo.com`).

## (f) Firma QC

**Revisión de evidencia:** cada estado de `verificacion.md` y cada PU-1XX citan captura, script o request de su borrador; las 63 capturas `PU-1XX-n.png` son copias de las originales (que se conservan). PU-107, PU-114, PU-118, PU-121, PU-131 y PU-133 quedan sin captura (evidencia por script/red o por código), como en sus borradores.

**Re-verificación en el código de los bloqueante/alta y de los media señalados:**
- **PU-101 (REG-01, bloqueante) — se sostiene.** `tecnoservicios_frontend/src/features/obras/hooks/useObras.ts:89-92` hace `await editarPresupuesto(principal.id, principal.cambios)` sin versión y después `editarObra(id, datos)`; `obras.api.ts:105-107` lleva `updated_at` solo para la obra; en el backend solo `ActualizarObraAction.php` controla versión (409) y `ActualizarPresupuestoAction.php` no controla ni toca la obra.
- **PU-102 (REG-02, alta) — se sostiene.** Misma secuencia: el `PATCH` del presupuesto se confirma antes de que el de la obra devuelva 409; no hay transacción entre los dos.
- **PU-103 (VA-03, media) — se sostiene.** `DocumentoDialog.tsx:78-89` deshabilita "Cancelar" y pasa `ocupado`, pero `AdjuntosField` (línea 128) no recibe `isPending` y `AdjuntosFieldProps` no tiene prop para deshabilitarse.
- **PU-111 (UXM-01, media) — se sostiene.** `ObraFormulario.tsx:118-125` pinta `AvisoConflicto` y el aviso general arriba del formulario; no hay ningún `scrollIntoView` en todo `src/`.
- Además se confirmaron en el código: PU-110 (`dominio.ts` `claveDeNombre` con `normalize('NFD')`), PU-113 (`importes.ts:6` sin `minimumFractionDigits`), PU-105 (`useBorrador.ts:8` clave sin usuario), PU-120 (`AdjuntosField.tsx:29`).

**Cambios de severidad:** ninguno. Los cuatro señalados se sostienen con su severidad de borrador (REG-01 bloqueante, REG-02 alta, VA-03 media, UXM-01 media). En las fusiones quedó la severidad mayor de las partes (todas coincidían).

**Fusiones (deduplicado):**
- VA-01 + VB-02 → **PU-119** (proveedor repetido anunciado dos/tres veces).
- REG-07 + REG-08 → **PU-104** (irse mientras se guarda: misma causa, `useCambiosSinGuardar` no conoce el guardado en curso).
- ENT-05 + nota de VB sobre PU-028 → **PU-117** (fechas lejanas sin aviso: certificados y fechas previstas).
- REG-12 + nota de VA sobre PU-001 ("subir los archivos" con un link) → **PU-120**.
- PER-06 + nota de VB sobre PU-044 (ayudas sin CUIT) → **PU-137**.
- UXM-03 + nota de VA (PU-022, listas de 25 px) + nota de VB (PU-041, logo y "Saltar al contenido") → **PU-134**.
- VA-02 + nota de PER (importes con un decimal también en el historial) → **PU-113**.
- ENT-10 queda como **PU-118** ligado a PU-038 (mismo caso en otra pantalla); ENT-02 = observación 4 de la ronda 1, promovida a **PU-128**.
- No se fusionaron, a propósito: PU-101 y PU-102 (mismo origen, síntomas y severidades distintos; probablemente un solo arreglo); PU-103 y PU-104 (otra puerta del mismo PU-003); PU-121, PU-123 y PU-127 (tres bordes distintos de PU-029).

**Cambios de estado respecto de los borradores:** PU-002 ✅ (VA) → ❌ (REG-01/02, REG-10, UXM-01); PU-003 ✅ → ⚠️ (VA-03, REG-07); PU-004 ✅ → ⚠️ (REG-03/04/05); PU-006 ✅ → ⚠️ (REG-06/08/09); PU-013 ✅ → ⚠️ (VA-01/VB-02, VB-01); PU-018 ✅ (VA) → ⚠️ (UXM-02); PU-022 ✅ (VA) → ⚠️ (UXM-04); PU-029 ✅ (VB) → ⚠️ (PER-02, ENT-01, REG-13); PU-038 ✅ (VB) → ⚠️ (ENT-10, por el mismo criterio que PU-022 y PU-029); PU-041 ✅ (VB) → ⚠️ (UXM-03); PU-039 "a medias" y PU-045 "Lead" → ✅ con los resultados del Lead.

**Privacidad:** revisados borradores, logs y las capturas del ingreso (`PU-016-r2-1`, `PU-045-r2-1`, `PU-045-r2-5`): sin códigos de ingreso válidos (solo `000000` rechazado), sin tokens, cookies ni claves; mails `@empresa-ejemplo.com` / `cliente.com` / `empresa.com` de ejemplo, CUIT y teléfonos ficticios.

— QC final · ronda 2 · 2026-11-17
