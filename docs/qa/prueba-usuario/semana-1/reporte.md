# Reporte · Prueba de usuario completa · Semana 1 · Tecnoservicios (NS-2026-025)

**Fecha:** 2026-11-16 · **Equipo:** QA NexoSmart (QA Lead + 5 testers en paralelo + QC final) · **Detalle:** [`hallazgos.md`](hallazgos.md) (formato §5, PU-001 … PU-045) · cobertura, observaciones y QC: [`borradores/consolidacion.md`](borradores/consolidacion.md) · borradores por rol en `borradores/`.

## 1. Resumen ejecutivo

Se usó el sistema como el cliente, persona por persona, con todo andando de verdad: backend, front, cola Redis con worker y scheduler. Resultado: **45 hallazgos: 2 bloqueantes · 3 altas · 18 medias · 22 bajas.**

Los permisos están sólidos, y ese era el riesgo más grande. La tabla inicial coincide celda por celda con `identidad.md` §6.3, y "solo sus obras" no deja ver ni bajar nada ajeno, ni por pantalla ni por API. También están bien las validaciones al lado del campo, el doble clic en las altas y los mensajes de 403/404. Los cinco bugs del QC anterior (B-01 a B-05) siguen arreglados.

Lo grave está en otro lado: **se pierden datos sin aviso** en cuatro situaciones.
1. Un link que todavía se está agregando cuando se toca Guardar (PU-001).
2. Dos pestañas o dos personas editando la misma obra (PU-002).
3. "Cancelar" mientras se guarda (PU-003).
4. La sesión que vence a mitad de un formulario (PU-004).

A eso se suma un **500 visible**: abrir una dirección `/api/…` sin sesión da "Route [login] not defined" (PU-005).

**Los 5 más graves**
1. **PU-001 · bloqueante.** Si se guarda mientras un link se está agregando, el link se pierde sin aviso. Pasa en la OC del alta de obra y en documentos, y por código también en adicional y editar. Se reprodujo 4 veces, una sin forzar nada, solo con el servidor lento.
2. **PU-002 · bloqueante.** Dos pestañas o dos personas editando la misma obra: la segunda pisa lo que guardó la primera y el historial se lo atribuye a ella.
3. **PU-003 · alta.** "Cancelar" durante el guardado cierra el diálogo, pero el cambio (de estado o de documento) se guarda igual. Con "Pasar a Terminada" archivaría una obra que el usuario cree haber cancelado.
4. **PU-004 · alta.** Si la sesión vence a mitad de un formulario, el sistema lleva al ingreso vacío, sin decir por qué, y se pierde lo escrito.
5. **PU-005 · alta.** `GET /api/obras/1` sin sesión desde el navegador da 500 "El sistema tuvo un problema" y deja "Route [login] not defined" en el canal `errors`. Con `Accept: application/json` da 401, que es lo correcto.

## 2. Entorno usado

| Qué | Valor |
|---|---|
| Backend | rama `main` @ `aae5d9c` (los reportes van en `qa/prueba-usuario-semana-1`) |
| Frontend | rama `main` @ `fc2e79b` |
| Base | MySQL 8.4.3 (Laragon), base propia `tecnoservicios_prueba_usuario` (`migrate:fresh --seed` solo sobre ella, verificado antes con tinker) |
| Redis | 5.0.14 (Laragon), prefijos `tecnoservicios_prueba_` / `tecnoservicios_prueba_cache_` |
| Procesos | API `artisan serve` 127.0.0.1:8340 · front Vite :5316 · worker igual a `config/supervisor.php` (cola `default` y cola `mail` con `--rest=1`) · `schedule:work` |
| Variables | las del §3 del prompt (`APP_DEBUG=false`, `MAIL_MAILER=log`, `QUEUE_CONNECTION=redis`, …) |
| Navegador | Playwright 1.65 / Chromium. PC 1366×768; celular Pixel 7 (412×915) e iPhone SE (375×667, forzado: el preset de Playwright es 320×568) |
| Log base | `laravel-2026-11-16.log` 835 B y `errors-2026-11-16.log` 999 B al empezar (lo anterior es de desarrollo) |

**Incidentes del entorno**
- **Corte externo de MySQL y Redis entre 14:49 y 15:55 UTC.** MySQL registró "Received SHUTDOWN from user root" y Redis cayó a la vez; ninguno de los dos lo apagó el equipo de QA. Se pidió al usuario levantarlos desde Laragon. En el corte murieron el worker y el scheduler y se perdieron todas las sesiones. Se relanzaron los procesos, se volvió a ingresar con cada persona, y cada tester repitió lo que había fallado. Lo que el corte mostró del sistema quedó como hallazgo (PU-020).
- **MySQL cargado por una suite de otro proyecto.** Algunos pedidos tardaron entre 10 y 20 s. Esa lentitud no se tomó como hallazgo; los loaders se juzgaron con una demora artificial controlada.
- **El comando del worker del prompt** (`queue:work --tries=3`) no escucha la cola `mail`, así que no salían los códigos. Se corrigió en el entorno. Hay que actualizar el prompt.
- **El límite de 20 pedidos de código por IP por hora** afectó a todo el equipo, que salía de la misma IP. El Lead ingresó una vez por persona y repartió las sesiones guardadas.

## 3. Personas probadas

| Persona | Sector | Quién la usó |
|---|---|---|
| Diego | Gerencia de Administración | ADM, ENT, OBR, PRV, UXM |
| Dora (`qa-adm-dora@…`, creada por Diego desde Usuarios) | Dirección | ADM |
| Hernán | Jefatura de Proyectos | OBR, PRV, UXM |
| Laura, Sofía, Pablo | Ingeniería de proyectos | OBR, ENT, PRV, UXM, ADM (URL directa) |
| Carla | Compras | PRV, OBR, LEAD (API caída) |
| QA-ADM Ana (sector propio con "solo sus obras") | QA-ADM Pruebas | ADM |
| QA-LEAD Uno / Bloqueo / Vence / Baja | Compras | LEAD (ingreso y sesión) |

## 4. Cobertura F-XX

✅ sin hallazgos · ⚠️ con hallazgos alta/media/baja · ❌ con al menos un bloqueante. El detalle de qué se probó en cada una está en `borradores/consolidacion.md` §(a).

| F-XX | Estado | Hallazgos |
|---|---|---|
| F-01 Ingreso con mail + código | ⚠️ | PU-004, PU-016, PU-021, PU-045 |
| F-02 Menú según el sector | ⚠️ | PU-014 |
| F-03 Buscador general | ⚠️ | PU-009, PU-017, PU-044 |
| F-10 Mi perfil | ⚠️ | PU-006, PU-018, PU-029 |
| F-14 Registro de quién hizo qué | ⚠️ | PU-032 (y PU-002: atribuye el pisado a quien guardó último) |
| F-15 Listado de obras por estado | ⚠️ | PU-017, PU-023, PU-040, PU-042 |
| F-16 Alta de obra | ❌ | PU-001, PU-006, PU-007, PU-012, PU-014, PU-018 |
| F-17 Ficha de la obra con pestañas | ⚠️ | PU-041, PU-042 |
| F-18 Presupuestos (principal y adicionales) | ⚠️ | PU-019 (PU-001 por código) |
| F-19 Documentos y contactos | ❌ | PU-001, PU-003, PU-005, PU-010, PU-011, PU-012, PU-018, PU-026, PU-027, PU-028, PU-030 |
| F-20 Editar la obra | ❌ | PU-002, PU-006, PU-018, PU-029, PU-033, PU-037 |
| F-21 Cambiar estado de la obra | ⚠️ | PU-003, PU-018 |
| F-22 Obras terminadas consultables | ✅ | — |
| F-23 Fechas previstas | ⚠️ | PU-032 |
| F-48 Presupuesto por rubro | ⚠️ | PU-014, PU-015, PU-018, PU-022 |
| F-55 Proveedores con buscador | ⚠️ | PU-009, PU-017, PU-024, PU-044 |
| F-56 Alta y edición de proveedor | ⚠️ | PU-004, PU-013, PU-018, PU-025, PU-029, PU-034, PU-035, PU-036, PU-038, PU-043 |
| F-58 Usuarios: alta, edición y baja | ⚠️ | PU-017, PU-022, PU-029, PU-040 |
| F-59 Sectores | ⚠️ | PU-008, PU-018, PU-031 |
| F-60 Tabla de permisos | ⚠️ | PU-006, PU-008, PU-018, PU-022 |
| F-61 Jerarquía ("depende de") | ✅ | — |
| F-68 Plazos de la prioridad | ✅ | — |
| Adjuntos (ADR-014) | ❌ | PU-001, PU-010, PU-011, PU-012, PU-026, PU-027, PU-030 |
| Cerrar sesión | ✅ | — |
| Sesión vencida | ⚠️ | PU-004 (y PU-020: Redis caído = todas las sesiones perdidas) |
| 403 / 404 | ⚠️ | PU-005, PU-014, PU-024 |
| Celular | ⚠️ | PU-022, PU-040, PU-041 |
| Accesibilidad | ⚠️ | PU-042 |
| Loaders | ⚠️ | PU-017, PU-018, PU-019, PU-037 |
| Mails y cola | ⚠️ | PU-021, PU-045 |
| Scheduler | ✅ | — |
| Errores de servidor forzados | ⚠️ | PU-005, PU-020, PU-039 |

**Errores forzados**
- **API cortada con un alta a medio llenar:** "No pudimos conectarnos con el sistema. Revisá tu conexión y volvé a intentar." Lo escrito queda y, al volver la API, "Guardar" de nuevo lo da de alta una sola vez, sin recargar. ✅
- **Redis caído:** las pantallas dicen "No pudimos cargar esta información". Problemas en PU-020.
- **Worker de mail parado:** el código no llega y nada lo advierte (PU-021). Al relanzar el worker, el mail sale una sola vez.
- **`/api/errors`:** registró el corte y los 500.

## 5. Conteo

**Por severidad:** bloqueante 2 · alta 3 · media 18 · baja 22 · **total 45**

| Tipo | Bloq. | Alta | Media | Baja | Total |
|---|---|---|---|---|---|
| funcional | 2 | 2 | 4 | 3 | 11 |
| mensaje | — | — | 8 | 8 | 16 |
| validación | — | — | — | 3 | 3 |
| loader | — | — | 3 | 1 | 4 |
| error-servidor | — | 1 | 1 | 2 | 4 |
| cola-mail | — | — | 1 | — | 1 |
| celular | — | — | 1 | 2 | 3 |
| accesibilidad | — | — | — | 1 | 1 |
| consistencia-con-documento | — | — | — | 2 | 2 |
| permisos · scheduler | — | — | — | — | 0 |

## 6. Bugs del QC anterior (`docs/qc/entrega-1.md`)

| Bug | Estado |
|---|---|
| B-01 Ficha de la obra en el celular | ✅ sigue arreglado (OBR, UXM: Pixel 7 e iPhone SE 375) |
| B-02 Códigos internos "(W-xx)" en pantalla | ✅ sigue arreglado (ENT, OBR) |
| B-03 Entradas a secciones no entregadas | ✅ sigue arreglado (ENT, OBR, UXM) |
| B-04 Corte en 8 MB con mensaje genérico | ✅ en este entorno: 20 MB entra; 21 MB → "El archivo pesa 21 MB y el máximo es 20 MB.". **No verificado** el 413 con un PHP de 8 MB: sigue la condición de configurar ≥ 21 MB en el servidor del cliente |
| B-05 Importes sin formato en el historial | ✅ sigue arreglado (las fechas del historial son un hallazgo nuevo, PU-032) |

## 7. Lo que no se pudo probar y por qué

- **MySQL caído por separado.** Solo hubo un corte de MySQL y Redis juntos, y no se volvió a pedir otro corte al usuario.
- **Guarda del último administrador (RN-IDE-25).** Exigía dejar el sistema sin administradores tocando a Diego o las filas del seeder que usaban los demás. Queda para una base propia.
- **Refresco del menú al volver a la pestaña (RN-IDE-14).** El foco simulado no disparó el refresco; con F5 sí. El resultado no es concluyente.
- **Dos pestañas en usuario, proveedor, adicional y documentos.** Probablemente pase lo mismo que en PU-002, pero solo se reprodujo en la obra.
- **413 con PHP de 8 MB (B-04).** El entorno tiene límite de 2G.
- **Celular real.** No se probaron Safari/WebKit, el teclado virtual ni la cámara. Solo Chromium emulado, y el teclado se dedujo de `type`/`inputmode`.
- **Lector de pantalla real y contraste en hover/foco.** No se probaron.
- **Sesión vencida "de verdad".** Se simuló borrando las cookies: la sesión dura 90 días y no se tocó la config.
- **Presupuesto por rubro y cambio de estado válido como Carla.** No se hicieron para no tocar obras de otros testers. Por API, el cambio de estado da 403.

## 8. Observaciones para decidir (no son bugs)

El detalle está en `borradores/consolidacion.md` §(c). Las principales:
- **Responsable.** El filtro y el desplegable listan a todos los activos, con ~50 personas.
- **Mail del código.** No trae link al sistema.
- **Límite de 20 códigos por IP por hora.** Con toda la empresa detrás de una misma IP pública, si se pierden las sesiones (PU-020) bloquearía a la mayoría durante una hora.
- **Persistencia de Redis en producción.** Hay que verificarla, porque las sesiones viven ahí.

**Para actualizar el prompt de la prueba:** el comando del worker y el preset de iPhone SE.

## 9. Firmas

| Rol | Firma |
|---|---|
| QA Lead | Levantó y cuidó el entorno, repartió el trabajo y las sesiones y gestionó el corte de MySQL/Redis. Probó ingreso y sesión, mails y cola, scheduler y errores forzados (API y worker). Sumó PU-045 y escribió este reporte. · 2026-11-16 |
| Tester funcional: Gerencia de Administración y Dirección (ADM) | Usuarios, sectores, tabla de permisos (11 × 12 contra §6.3), jerarquía, plazos, Mi perfil, Dora, "solo sus obras" con Ana. Borrador `borradores/adm.md`. |
| Tester funcional: Jefatura e Ingeniería (OBR) | Obras F-15…F-23, F-48, historial, permisos por persona, B-01/B-05. Borrador `borradores/obr.md`. |
| Tester funcional: Compras (PRV) | Proveedores F-55/F-56, buscador general, permisos de Ingeniería y Jefatura, Carla en Obras. Borrador `borradores/prv.md`. |
| QA de entradas y errores + UX writer (ENT) | Cada formulario con entradas inválidas, adjuntos ADR-014 (0 B, 20/21 MB, tipos, links), doble clic, F5, pasada de textos, B-02/B-04. Borrador `borradores/ent.md`. |
| QA de carga y loaders + celular y accesibilidad (UXM) | Loaders con red lenta, Pixel 7 e iPhone SE, teclado y foco, B-01/B-03. Borrador `borradores/uxm.md`. |
| QA de backend, cola y workers | Lo cubrió el Lead: logs, `/api/errors`, `failed_jobs` (0), worker, scheduler. |
| QC final | Consolidó 50 entradas de borrador en 45 hallazgos, fusionó los duplicados, ajustó severidades según §5, copió las capturas `PU-XXX-n` y revisó que no haya datos sensibles. Ver `borradores/consolidacion.md` §(f). · 2026-11-16 |
