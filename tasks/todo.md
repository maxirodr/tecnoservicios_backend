# Tareas

Plan de trabajo con ítems chequeables. Cada tarea termina con una sección de revisión.

## Sprint 0 — cerrado (2026-11-12)
- [x] Base del backend (Laravel 13, módulos, envelope, /health, /errors, CI)
- [x] Base del frontend (React 19, barra por permisos, cliente API, CI)
- [x] Arquitectura, modelo de datos, ADR-001…014 aprobados por Juani
- [x] Backlog semana 1 (25 historias, todas listas), matriz F-01…F-68, plan de pruebas
- [x] Especificación de Identidad, Obras y Proveedores; 43 dudas resueltas
- [x] ADR-014: archivo o link en todos los documentos (amplía D-022)

## Semana 1 — a retomar el 2026-11-13
Plan (brief de agentes: rehacerlo con las mismas reglas; está en `tasks/brief-agentes-semana1.md`):
- [x] Tanda 1 en paralelo (2026-11-13, sin push ni merge; esperando OK de Juani):
  - Backend Identidad (HU-001…011): rama `feat/w1-identidad`, base `tecnoservicios_testing`
  - Backend transversal (HU-025 adjuntos, RegistroActividad, middleware idempotent): worktree propio + base `tecnoservicios_testing_shared` (ya creada). Copiar `vendor/` y correr `composer dump-autoload` antes de lanzar el agente (tarda varios minutos en esta máquina)
  - Frontend identidad + barra + AdjuntosField (HU-001…011, 023, 024, 025 UI): rama `feat/w1-identidad`
- [x] Ingreso con código por mail (ADR-015) + pendientes del merge, back y front — en main
- [ ] Integración front ↔ backend real (hasta ahora todo el front está probado con API mockeada; confirmar D-UI-03/04/05)
- [ ] `modelo-datos.md`: reflejar desvíos de Obras (`presupuesto_montos` en Obras, `modo_carga` columna) y Proveedores (`nombre_busqueda`, CUIT char(11))
- [x] Tanda 2 (2026-11-13, en main): backend Obras (HU-012…019, base `tecnoservicios_testing_obras`) y Proveedores + buscador (HU-020…022, base `tecnoservicios_testing_proveedores`); front de Obras y Proveedores
- [ ] Revisión del Tech Lead, `docs/seguridad.md`, QC `docs/qc/entrega-1.md`, notas de entrega, merge a main
- [x] Contratos compartidos: interfaces `Permisos` y `Jerarquia` en `app/Modules/Identidad/Contracts/`; 12 claves de función de `especificacion/identidad.md`; front en puerto 5310 (agregarlo a SANCTUM_STATEFUL_DOMAINS)

## Pendientes de Juani
- Servidor de staging y producción (para `docs/deploy.md` y la URL de `/bug-report`)
- Avisarle al cliente que ADR-014 amplía lo firmado (archivos además de links)
- Avisarle al cliente que ADR-015 cambia F-01 (ingreso con código por mail, sin contraseña)

### Revisión Tanda 1 (2026-11-13)
- Identidad (`feat/w1-identidad`): 149 tests / 753 assertions verde, pint ok, larastan 0. HU-001…011.
- Transversal (`feat/w1-transversal`, worktree `../tecnoservicios_backend_shared`): 118 / 521 verde (base `_shared`), pint ok, larastan 0.
- Front (`feat/w1-identidad`): vitest 112 ok, e2e 26 ok (API mockeada), typecheck/lint/format ok.
- `git merge-tree` identidad × transversal: sin conflictos de texto.
- Al mergear: bindear RegistroActividad real (6 tests con espía → aserción en `actividades`), `idempotent` en POST usuarios/sectores, `AdjuntoResource` lee `nombre`, actualizar `modelo-datos.md` (plazos en una fila, `users.nombre`).
- Entorno de esta máquina: MariaDB 10.4 (XAMPP), Redis portable en scratchpad; PHP `upload_max_filesize` 8M (prod necesita ≥21M).

### Revisión Tanda 2 (2026-11-13)
- Backend en main: 433 tests / 2292 assertions, pint ok, larastan 0 (Identidad + Shared + Proveedores + Obras).
- Front en main: vitest 168 ok, e2e 44 ok (API mockeada), typecheck/lint/format ok.
- Rubros: una sola tabla (`2026_11_13_300001`, modelo en Rendicion); Obras la reusa. Buscador general en Shared (`/api/buscar`), grupos obras y proveedores.

### Integración y revisión (2026-11-13, en main)
- [x] Integración real front ↔ backend: `npm run e2e:integracion` 42 ok (backend 8320 + front 5312, base `tecnoservicios_integracion`). Bug: 422 fuera de formulario mostraba mensaje genérico (arreglado).
- [x] Revisión Tech Lead + seguridad (`docs/seguridad.md`), hallazgos arreglados; SEG-01 bloqueo progresivo y TL-02 (enmienda ADR-003) decididos por Juani.
- [x] `modelo-datos.md` alineado con las migraciones.
- Suites en main: backend 483 / 2633; front unit 215, e2e mock 44, e2e real 42.
- [ ] Mensajes de validación del backend en español (hoy las reglas sin mensaje propio responden en inglés)
- [ ] QC `docs/qc/entrega-1.md` + notas de entrega para el cliente
- [ ] Diseño pendiente: TL-03 (meta paginado), TL-07 (asociar con usuario), TL-08 (Shared → User), renombrar tests viejos a la regla

### Cierre del día (2026-11-13)
- [x] Mensajes del backend en español y sin jerga (lang/es a mano, 27 FormRequest cubiertos)
- [x] QC `docs/qc/entrega-1.md`: apta (18 ✅ · 4 ⚠️ · 0 ❌); bugs B-01…B-05 arreglados
- [x] Notas `docs/entregas/semana-1.md` (BORRADOR) + `CHANGELOG.md`
- Suites en main: backend 518 / 2859; front unit 225, e2e mock 46, e2e real 42.
- Semana 1 ≈ 98 % de lo nuestro. Para cerrar (Juani): staging/prod (límites de subida ≥ 21 MB), avisar ADR-014/015 al cliente, revisar y mandar notas.

## Próxima sesión
1. Levantar MariaDB (XAMPP) y Redis portable; verificar `select 1` y `PING`.
2. Matriz de trazabilidad: sumar tests de los fixes de QC (B-01…B-05).
3. Semana 2 (Pedidos / minuta): Sprint-0 de la semana — especificación `docs/especificacion/pedidos.md`, backlog HU, dudas; después tandas con agentes como en semana 1.

## Prueba de usuario semana 1 (2026-11-16)
Reporte: `docs/qa/prueba-usuario/semana-1/` (45 hallazgos PU-001…045: 2 bloq · 3 altas · 18 medias · 22 bajas).
Juani aprobó PU-043 (ñ ≠ n en duplicados de proveedor), PU-044 (buscar proveedor por CUIT) y PU-045 (pista en el ingreso + gerencia destraba el bloqueo desde Usuarios).
Arreglos en ramas, **sin mergear y sin verificar** (los agentes se cortaron a mitad):
- Backend `fix/w1-prueba-usuario-back`: 11 commits (401 JSON, 4xx fuera de `errors`, búsqueda con signos, links con ñ, minutos de espera, health sin Redis + cola de mail, revisión, fechas del historial, doble punto, 409 por edición vieja con `updated_at`, ñ + CUIT, quitar bloqueo de ingreso). Falta: correr pint/phpstan/suite completa; DU-Q04…Q06 en dudas.md ya escritos, revisar bitácora/ADR-015/especificaciones.
- Front `fix/w1-pu-formularios` (carpeta principal): último commit `wip` con todo lo hecho; faltan tests de PegarLink y verificar todo.
- Front `fix/w1-pu-listas` (worktree `../tecnoservicios_frontend_listas`): último commit `wip`; verificar.
### Próxima sesión
1. Revisar cada rama contra su lista de PU (prompts de los agentes en esta sesión), completar lo que falte y dejar suites en verde.
2. Front: UI de PU-045 (bloqueo en Usuarios + pista "¿No te llegó?…" en el ingreso) y fechas del historial si quedan en el front.
3. Mergear las 3 ramas, `npm run e2e` y `npm run e2e:integracion`, re-probar los PU graves; borrar ramas y el worktree.
4. Actualizar el prompt de la prueba: worker `--queue=mail,default`, iPhone SE 375×667.
5. Semana 2: Juani confirma las 27 dudas (DU-P01…P24, DU-Q01…Q03) → tandas de Pedidos y Avisos.

### Revisión arreglos de la prueba de usuario (2026-11-17)
- [x] Backend `fix/w1-prueba-usuario-back` en main: 557 tests / 3091, pint y larastan 0. PU-043/044/045 hechos y documentados (DU-Q04…Q06, ADR-015 enmienda).
- [x] Front `fix/w1-pu-formularios` + `fix/w1-pu-listas` en main (conflictos resueltos en EditarObra y Proveedores) + UI de PU-045: vitest 310 ok, typecheck/lint/format ok, e2e mock 46/46.
- [x] `npm run e2e:integracion` 42/42 (2026-11-17). Encontró un bug del guard de cambios sin guardar (el link al proveedor/obra existente quedaba frenado): arreglado. Base `tecnoservicios_integracion` creada en esta máquina (el health da 503 sin base).
- [x] Ramas mergeadas y worktree borrados.
- Flaky: `EditarObraPantalla.test.tsx` "guarda los datos de la obra y, aparte, solo lo que cambió del principal" falló 1 vez bajo carga; solo pasa.
- [ ] Re-probar en el navegador los PU graves (001–005).

### Ronda 2 de la prueba de usuario (2026-11-17) — arreglada, falta verificar integración
Reportes: `docs/qa/prueba-usuario/semana-1-ronda-2/` (✅32 ⚠️12 ❌1 de los 45; 39 nuevos PU-101…139). **Todos arreglados y en `main` y `staging`.**
- [x] Backend (`917ff77`, `c310564`): obra + presupuesto principal en un solo `PATCH /api/obras/{id}` con `presupuesto_principal` y versión (PU-101/102); cambios de presupuesto suben `obras.updated_at`; `PATCH /api/me` con `updated_at` (PU-108); 503 si cae MySQL/Redis (PU-138); health < 3 s con MySQL caído, `HEALTH_DB_TIMEOUT=1` (PU-139); buscadores recortan en vez de 422 (PU-118); textos únicos (PU-128/133/122); links guardados legibles (PU-129); `cuit` en el buscador (PU-137); 2 decimales con centavos (PU-113). Suite 573 ok, pint, larastan 0.
- [x] Front (`2189d78`, `e2605e5`): formularios (PU-103…109, 111, 112, 114, 115, 120, 121, 127, 136), textos/listas/celular (PU-110, 113, 116, 117, 119, 122…126, 128…135, 137, 138), subruta `VITE_BASE_PATH`. Vitest 389 ok, typecheck/lint/format/build ok, **e2e mock 46/46**.
- [x] `docs/deploy.md`: staging en `https://clientes.nexosmart.com.ar/tecnoservicios/` (nginx, `.env`, pasos, verificación).
- [x] Rama `staging` creada en los dos repos = `main`.

#### Pendiente para retomar
1. **`npm run e2e:integracion`** (backend 8320 + front 5312, base `tecnoservicios_integracion`; MySQL y Redis arriba en Laragon). No se corrió después de la ronda 2: cambió el contrato de Editar obra y Mi perfil, puede haber specs con textos viejos. Si algo cambia, arreglar en `main` y adelantar `staging` (`git push origin main:staging`).
2. **Ronda 3 de QA** (re-test corto): reusar `tasks/prompt-prueba-usuario-semana-1-ronda-2.md` apuntando a los PU-101…139 y a los ⚠️/❌ de `verificacion.md`, salida en `docs/qa/prueba-usuario/semana-1-ronda-3/`.
3. **Deploy de staging (Juani):** seguir `docs/deploy.md`. Front compilado con `VITE_BASE_PATH=/tecnoservicios` y `VITE_API_URL=https://clientes.nexosmart.com.ar/tecnoservicios`. Backend: `SESSION_PATH=/tecnoservicios`, `SESSION_DOMAIN`, `SANCTUM_STATEFUL_DOMAINS`, `ERRORS_TOKEN` nuevo, `HEALTH_DB_TIMEOUT=1`, workers con `php artisan supervisor:sync`, Redis con AOF, `upload_max_filesize`/`post_max_size` ≥ 21M. Después: `.claude/bug-report.json` con la URL de staging.
4. ~~Con MySQL caído, la API (no el health) tarda ~8 s en dar el 503 porque Laravel reintenta la conexión; decidir si bajar los reintentos.~~ **Hecho (PU-202, rama `fix/w1-r3-back`):** un solo intento de conexión por pedido (`ConectorMySqlSinReintentos` + `BasesCaidas` scoped) y `DB_CONNECT_TIMEOUT=1`. Medido con `artisan serve` y `DB_PORT=3399`: antes 500 en 32–38 s; después 503 en 1,24 s (`/api/me`, `/api/proveedores`, `/api/obras`), con la entrada en `errors` y `user_id`.
5. UX tomada por los agentes, para que Juani la valide: "Estamos guardando. Esperá unos segundos." al intentar salir mientras guarda; Cancelar/Escape en diálogos con cambios pregunta; aviso no bloqueante "Revisá la fecha: es de {año}." (< 2000 o > año actual + 10); REV00 → "Las revisiones empiezan en REV01." (el relevamiento no usa REV00); "Guardar proveedor" no se deshabilita ante un repetido (el aviso pasa a rojo).
6. Semana 2: Juani confirma las 27 dudas (DU-P01…P24, DU-Q01…Q03 al final de `docs/dudas.md`) → tandas de Pedidos y Avisos.
7. Avisar al cliente ADR-014 y ADR-015 (+ enmienda); revisar y mandar las notas de la semana 1 (BORRADOR).

### Integración después de la ronda 2 (2026-11-18)
- [x] `staging` traído a `main` en los dos repos (staging ⊂ main; desde ahora `git push origin main:staging` es fast-forward).
- [x] `IDENTIDAD_CODIGO_EN_PANTALLA` (estaba solo en staging) ahora en main **con traba**: con `APP_ENV=production` se ignora siempre (`CodigoEnPantallaTest`, 3 tests; el de producción falla sin la traba).
- [x] `npm run e2e:integracion` 42/42 (3 corridas: 7,9 · 7,2 · 6,7 min). Los 6 fallos eran specs desactualizados por PU-112 (nombre del link = última parte del path), PU-006/114 (confirmación de cambios sin guardar al cancelar un diálogo sucio) y PU-124 (sacar sector con gente: aviso de entrada, sin botón). Sin bugs de producto.
- Suites en main: backend 576 / 3228, pint y larastan 0; front unit 389, e2e mock 46, e2e real 42; typecheck/lint/format/build ok. Front main `1914ea8`.
- [ ] Ronda 3 de QA: `tasks/prompt-prueba-usuario-semana-1-ronda-3.md` (52 PU, 8 roles; salida en `docs/qa/prueba-usuario/semana-1-ronda-3/`).

### Ronda 3 de la prueba de usuario (2026-11-18) — arreglada
Reporte: `docs/qa/prueba-usuario/semana-1-ronda-3/` (de 52 PU: ✅43 ⚠️9 ❌0; 11 nuevos PU-201…212, 0 regresiones).
- [x] PU-201 (bloqueante): conflicto de montos en presupuesto por rubro → 409 con `updated_at` por presupuesto, como Editar obra.
- [x] PU-202 (alta): MySQL caído → 503 en ~1,2 s (antes 500 a los 38 s) y queda en `errors`; conector sin reintentos, `DB_CONNECT_TIMEOUT=1`.
- [x] PU-203…212 y O-4 (REV01–REV99 en back y front). Fechas en toda la app: `dd/mm/aaaa` y `dd/mm/aaaa HH:MM`.
- Dos migraciones de datos idempotentes (nombres de links por el path, sin comillas en "Quitó…"): correr `migrate` en el deploy.
- Suites en main: backend 598 / 3375, pint y larastan 0; front unit 412, e2e mock 53 (+7 salteados de celular en PC), **e2e real 42/42**; typecheck/lint/format/build ok.
- [ ] Juani decide 3 detalles de `observaciones.md`: O-1 (destino tras "Estamos guardando"; Escape mientras guarda sin aviso) y O-3 (marca de fecha lejana en la ficha).
- [ ] Deploy de staging con lo nuevo (`docs/deploy.md`, incluye `migrate --force`).

## Semana 2 (arranque 2026-11-19)
- [x] Juani aprobó las 27 dudas (va la propuesta); `dudas.md`, backlog y specs sin ⛔ (`8246e31`).
- [x] Ronda 5 de QA = última ronda completa; bajos no frenan (§7 del prompt, `f36c23a`).
- [x] Contratos compartidos en main: eventos de Pedidos con `FotoDelPedido`, `PedidosParaRecordar`, `AvisosDelPedido`, `DestinatariosDeAviso` con nulos (`dc57bae`, `105ccbd`).
- [ ] Tanda 1 (brief `tasks/brief-agentes-semana2.md`), 4 agentes en worktrees: Back Pedidos (HU-059, 026…039), Back Avisos (HU-045…049, HU-060 back), Front Pedidos, Front Avisos. Bases `tecnoservicios_testing_pedidos` / `_avisos` (phpunit.xml skip-worktree).
- [ ] Revisión y merge de la tanda 1; integración real front ↔ back.
- [ ] Tanda 2: HU-040…044, 050, 051…058; QC; notas de entrega semana 2.
