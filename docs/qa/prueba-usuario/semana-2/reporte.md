# Reporte · Prueba de usuario Semana 2 (Pedidos y avisos) · Ronda 1 · Tecnoservicios (NS-2026-025)

**Fecha:** 2026-11-20 · **Equipo:** QA NexoSmart (QA Lead + roles PED, AVI, JEF, OBR, PER, CEL; rol 7 backend lo hizo el Lead; QC final) · **Detalle:** [`hallazgos.md`](hallazgos.md) · borradores en `borradores/` (sin modificar) · capturas en `capturas/` (184) · logs en `logs/` (6)

## 1. Resumen ejecutivo

**Veredicto: NO APROBADA.** Hay **2 hallazgos medios**: **PU-601** ("+ Nuevo pedido en esta obra" muestra "Elegí…" en Obra aunque se guarda en la obra) y **PU-602** (Carga por persona sin gente a cargo culpa al permiso del sector). Van a una **ronda corta** (`semana-2-ronda-2\`, PU desde 701). Detalle en el §10.

Lo importante de la semana **anda de punta a punta**:
- **Avisos:** la tabla §6 de `avisos.md` dio 15/15 en dos vueltas; nadie recibió un aviso de lo que hizo él mismo; los inactivos nunca recibieron nada; ningún aviso duplicado. La campana se actualiza **sola, sin recargar**, 9 de 9 (2 de 9 por encima de 15 s: PU-604, baja).
- **Mail de asignación:** sale **una vez**, por la cola `mail`, con "Ver el pedido" al front; con el worker de mail cortado, el aviso llega igual a la campana y el mail sale **uno solo** al relevantarlo. Los mails que no tienen que salir no salen (seguidor, superior, "vence mañana").
- **"Vence mañana":** sale desde las 8:00 de Argentina, un lunes se recuerda el domingo, una sola vez aunque se corra de nuevo, y el `schedule:work` lo corrió solo a las 12:00 sin duplicar.
- **Celular:** pedido corto con "Se le avisó a…", comentar y **completar con la foto real del remito** (2,6 MB), 3 de 3 en Pixel 7 e iPhone SE.
- **Backend sano toda la ronda:** health 200, `failed_jobs` 0, canal `errors` vacío aun con ~110 ataques del rol PER, ningún 5xx.

**16 hallazgos:** bloqueante 0 · alta 0 · **media 2** · baja 14 · **ninguna regresión**. QC subió **JEF-01 → PU-602** de baja a media (mismo caso que PU-014 de la Semana 1) y unió **JEF-02 + CEL-01** en **PU-603**.

## 2. Entorno y commits

| Qué | Valor |
|---|---|
| Máquina | con Laragon (`C:\NexoSmart\Projects\…`) |
| Backend | `staging` @ **`7e007dd`**, worktree `tecnoservicios_backend_qa_s2` en la rama `qa/prueba-usuario-semana-2` (desde `origin/staging`). `.env` copiado del principal (gitignored), `composer install` + `dump-autoload`. |
| Front | `staging` @ **`d8f2433`**, worktree `tecnoservicios_frontend_qa_s2` (detached en `origin/staging`), `npm ci`. |
| Repos principales | no se tocaron (desarrollo trabaja en `main` @ `b3916d5` y en los worktrees `_avisos` / `_pedidos`). |
| Servicios | MySQL 8.4.3 y Redis 5.0.14.1 de Laragon (`select 1` y `PING` OK). PHP 8.3.30. Memoria libre 4,7–5,8 GB (por debajo de los 6 GB del §3: como máximo 2 navegadores a la vez, nunca 3). |
| Base | `tecnoservicios_prueba_usuario` (verificada con tinker antes de `migrate:fresh --seed`); `app.zona_empresa` = `America/Argentina/Buenos_Aires`. Sesiones viejas de la prueba borradas en Redis al resetear (8 claves). |
| Variables | las del §3 en cada proceso; `ERRORS_TOKEN` nuevo, sin `\r`, fuera del repo. |
| Procesos | `serve --no-reload` :8340 · workers `default` y `mail` iguales a `config/supervisor.php` · `schedule:work` · front `npm run dev -- --port 5316 --strictPort` sin `VITE_MODULOS_ENTREGADOS`. Smoke: health 200, front 200. |
| Scheduler | `schedule:list`: `adjuntos:purgar-huerfanos`, `idempotencia:purgar` y **`avisos:recordatorios`** cada hora. |
| Ingresos reales | **14** en total (Lead 7, OBR 1, PER 6), sin 429 ni códigos quemados. PED, AVI, JEF y CEL: 0. |
| Fechas en la base | nadie movió fechas a mano; los bordes de hora se probaron con `page.clock` (front), `Carbon::setTestNow` en un tinker aparte y la acción del recordatorio dentro de una transacción con rollback. Nada que restaurar. |
| Datos compartidos tocados y restaurados | Plazos 2/4 → vuelta a **1/7** (PED, verificado en la base); Mis avisos de Laura y Sofía vueltos al valor por defecto (AVI); tabla de permisos: solo celdas de sectores propios de QA, las 156 filas de los sectores 1–13 idénticas a las de antes (PER). |

## 3. Personas probadas

| Persona | Sector | Roles que la usaron |
|---|---|---|
| Diego | Gerencia de Administración | PED, AVI, JEF (control), OBR, PER (alta de usuarios y permisos), CEL (Más, obra) |
| Dora | Dirección (alta de Diego) | AVI, JEF, CEL |
| Hernán | Jefatura de Proyectos (con gente a cargo) | PED, AVI, JEF, OBR, CEL |
| Laura | Ingeniería de proyectos | todos los roles |
| Sofía | Ingeniería de proyectos (jefa de Pablo) | PED, AVI, JEF |
| Pablo | Ingeniería de proyectos | PED, AVI, JEF |
| Carla | Compras | PED, AVI, OBR, PER, CEL |
| Usuarios propios de QA | `qa-lead-s2*`, `QAS2-AVI` (activa e inactiva), `QAS2-JEF` (inactivos con y sin pendientes), `QAS2-OBR` (taller, sin pedidos), `QAS2-PER` (Ver, Sin acceso, Editar) | Lead, AVI, JEF, OBR, PER |

Dispositivos: PC 1366×768 (`es-AR`, `America/Argentina/Buenos_Aires`, y en UTC para los bordes de día), Pixel 7 (412×915) e iPhone SE forzado a 375×667.

## 4. Cobertura F-XX

✅ = funciona · ⚠️ = funciona con hallazgos (entre paréntesis la severidad más alta) · ❌ = no funciona.

| F-XX | Estado | Roles | Hallazgos |
|---|---|---|---|
| F-02 Barra de la Semana 2 | ✅ | OBR, CEL | — (Gastos y Rendiciones no aparecen, como corresponde) |
| F-03 Buscador, grupo Pedidos | ✅ | OBR, PER | — |
| F-04 Mis tareas | ⚠️ (baja) | PED, JEF, PER, CEL | PU-603 (celular) |
| F-05 Pedidos que pedí | ⚠️ (baja) | PED, PER, CEL | PU-603 (celular) |
| F-06 Tareas de mi gente | ⚠️ (baja) | JEF, CEL | PU-603 |
| F-07 Tareas que sigo | ✅ | PED, JEF | — (usa la misma celda que PU-603; no se midió el toque fuera de la tarea) |
| F-08 Avisos en la campana | ⚠️ (baja) | AVI, PED | PU-604, PU-605 |
| F-09 Mails | ✅ | AVI, Lead | — |
| F-10 Mis avisos en Mi perfil | ⚠️ (baja) | AVI, OBR | PU-606 |
| F-11 Calendario | ⚠️ (baja) | JEF, PER | PU-612, PU-613 |
| F-12 Carga por persona | ⚠️ (**media**) | JEF | **PU-602** |
| F-13 Celular | ⚠️ (**media**) | CEL | PU-603, PU-605; **PU-601** también en W-47 |
| F-14 Historial | ✅ | PED, OBR | — |
| F-15 Pedidos abiertos y fila Servicios generales | ⚠️ (baja) | OBR, CEL | PU-614 |
| F-17 Pestaña Pedidos de la obra | ⚠️ (**media**) | OBR, CEL | **PU-601** |
| F-21 Pasar a Terminada con pedidos abiertos | ✅ | OBR | — |
| F-24 Crear | ⚠️ (**media**) | PED, PER, CEL | **PU-601** (alta desde la obra), PU-611 |
| F-25 Fecha de pedido | ✅ | PED, PER | — |
| F-26 Prioridad | ⚠️ (baja) | PED, PER | PU-610 |
| F-27 Archivos o links | ✅ | PED, PER, CEL | — |
| F-28 Tomar | ✅ | PED, PER, CEL | — |
| F-29 Bloquear / retomar | ⚠️ (baja) | PED, PER, CEL | PU-609, PU-615 |
| F-30 Comentar | ⚠️ (baja) | PED, PER, CEL | PU-615 |
| F-31 Completar con comprobante | ⚠️ (baja) | PED, PER, CEL | PU-609, PU-616 |
| F-32 Reasignar | ✅ | PED, AVI, PER, CEL | — |
| F-33 Editar | ✅ | PED, PER | — |
| F-34 Recorrido | ⚠️ (baja) | PED | PU-607, PU-608 |
| F-35 Todos los pedidos y Excel | ⚠️ (baja) | JEF, PER | PU-603, PU-612 |
| F-36 Sin obra (Servicios generales) | ⚠️ (baja) | PED, JEF | PU-603 |
| F-37 Recordatorio "vence mañana" | ✅ | AVI, Lead | — |
| F-38 Propietario y seguidores | ✅ | PED, AVI, PER | — |
| F-58 Personas con pedidos abiertos | ✅ | OBR | — |
| F-59 Sectores con pedidos | ✅ | OBR | — |
| F-61 Jerarquía | ✅ | JEF | — |
| F-64 Lista de avisos | ✅ | AVI, CEL | — (en el celular, PU-605 y parte de PU-603) |
| F-65 Reabrir | ⚠️ (baja) | PED, PER | PU-609, PU-615 |
| F-67 Equipos en reparación | ✅ | OBR | — |
| F-68 Plazos aplicados | ✅ | PED | — |
| *(extra)* F-60 Tabla de permisos (Pedidos) | ✅ | PER | — |

**Conteo:** 38 F-XX de la Semana 2 · ✅ 19 · ⚠️ 19 (**4 por un medio**: F-12, F-13, F-17, F-24; 15 solo por bajos) · ❌ 0. Ninguna quedó sin cubrir; los huecos parciales están en el §8.

## 5. §6.1 del backlog, punto por punto ("tiene que estar sí o sí")

| Lo que hacen en la minuta hoy | Historias | Resultado | Quién lo probó |
|---|---|---|---|
| Cargar un pedido con todas sus columnas (tarea, obra o "Servicios generales", propietario, PROVEEDOR, fechas, prioridad, links, notas) | HU-026/027/028/043 | **Funciona** en los dos sentidos (Laura → Carla, Carla → Laura, Diego → Hernán), con datos raros, doble clic (1 pedido) y sesión vencida con borrador. Desde la obra, el campo Obra se ve mal: **PU-601 (media)**. Bajos: PU-610, PU-611. | PED, PER, CEL |
| Mirar la planilla de la obra y la planilla completa, filtrar | HU-042, HU-054 | **Funciona**: los 15 filtros de W-24 coinciden con la base, orden, "N de M", paginado y Excel con todo lo filtrado; pestaña Pedidos de la obra con Abiertos/Todos y sector. Bajos: PU-603, PU-612. | JEF, OBR |
| Cambiar el estado: En curso, Bloqueada con motivo, Completada | HU-032/033/034 | **Funciona**, con 403/409 en castellano para quien no le toca. Bajo: PU-609. | PED, PER, CEL |
| La ida y vuelta en Notas ("falta tal cosa") | HU-029 | **Funciona** (comentarios y reabrir). Bajo: PU-615. | PED, PER, CEL |
| Cambiar el propietario a mano | HU-035 | **Funciona**, a persona (mantiene el estado) y al sector (No iniciada, lo toma el primero). | PED, AVI, PER, CEL |
| Corregir una fecha o un link | HU-031 | **Funciona**, con historial, dos pestañas (409 + Actualizar) y sin avisar (DU-P24). | PED, PER |
| Enterarse sin que lo arroben: asignación (también por mail), tomado, completado, "vence mañana" | HU-045/046/047/048 | **Funciona**: campana sola 9 de 9 (PU-604, baja, 2 medidas > 15 s); mail de asignación una vez por la cola `mail`; tomado y completado a quien lo pidió y a los seguidores; "vence mañana" en la campana del propietario. | AVI, PED, Lead |
| Ver lo mío en una sola lista | HU-038, HU-039 | **Funciona**: Mis tareas y Pedidos que pedí siguen cada paso, con vencido / hoy / mañana bien en Argentina y en UTC. Bajo: PU-603 (celular). | PED, JEF, CEL |
| Desde el celular | HU-051, HU-052 | **Funciona**: tarjetas, contador, pedido corto con "Se le avisó a…", comentar, completar con la foto del remito, tomar/bloquear/reasignar; sin scroll horizontal. Bajo: PU-603. | CEL |
| Que el entorno mande los mails, corra el recordatorio y muestre Pedidos en la barra | HU-060 | **Funciona**: 173 jobs DONE en la cola `mail`, 0 fallidos; `avisos:recordatorios` corrido por el scheduler a las 12:00 sin duplicar; Pedidos en la barra, Gastos y Rendiciones ocultos. | Lead, AVI, OBR |

**Señales de que la prueba peligra (§6.1):**
- *Crear un pedido → aviso en la campana **y** mail de punta a punta:* **verde** en la prueba manual (AVI, Lead). La suite de integración real no es parte de esta prueba de usuario: no se revisó.
- *El recordatorio no corre solo:* **corre** con `schedule:work` (15:00 UTC, 1 aviso nuevo, 0 duplicados). Supervisord del entorno de prueba no está en esta máquina: no se probó (§8).
- *El celular no permite comentar y completar con la foto del remito:* **permite**, 3 de 3 en los dos celulares, con una imagen real de 2,6 MB que Laura descarga.

**Qué mira QC en los días en paralelo:** el 100 % de los propietarios con aviso antes de la entrega y los mails que no deben salir quedan cubiertos en esta ronda (tabla §6 15/15, recordatorio, "nunca por mail" verificado línea por línea en el log). Lo de "ningún pedido de la minuta falta en el sistema" es de los días en paralelo con el cliente, no de esta prueba.

**§6.1 queda cubierta entera.** El único medio que la toca (PU-601) es de cómo **se ve** el campo Obra en un acceso; el pedido se guarda bien.

## 6. Conteo de hallazgos

| Severidad | Cantidad | Hallazgos |
|---|---|---|
| Bloqueante | 0 | — |
| Alta | 0 | — |
| Media | **2** | PU-601, PU-602 |
| Baja | 14 | PU-603 … PU-616 |
| **Total** | **16** | ninguna regresión |

| Tipo | Cantidad | Hallazgos |
|---|---|---|
| funcional | 3 | PU-601, PU-613, PU-614 |
| mensaje | 5 | PU-602, PU-607, PU-610, PU-611, PU-616 |
| consistencia-con-documento | 5 | PU-603, PU-605, PU-606, PU-608, PU-612 |
| validación | 2 | PU-609, PU-615 |
| aviso | 1 | PU-604 |
| permisos · loader · error-servidor · cola-mail · scheduler · celular · accesibilidad | 0 | (PU-603 incluye el celular; se tipificó por el documento porque pasa también en PC) |

**Por rol de origen:** PED 5 · AVI 3 · JEF 4 (uno unido con CEL) · OBR 2 · PER 2 · CEL 1 (unido con JEF-02).

## 7. Backend, cola y scheduler (rol 7, lo hizo el Lead)

| Qué | Resultado |
|---|---|
| `/api/health` | 200 con los 5 servicios en `true` toda la ronda (0,1–1,1 s), salvo el corte provocado del worker de mail. |
| `failed_jobs` | 0 al cierre de cada tanda y al final (confirmado por QC en la base). |
| Canal `errors` | No se creó `errors-2026-11-20.log`; `GET /api/errors` → "No logs for date 2026-11-20". Ni 4xx ni 5xx registrados, aun con los ataques del rol PER. |
| Workers | `default` y `mail` sin FAIL ni Exception; 173 jobs DONE en `mail` (asignaciones, reasignaciones, "completado" de Mis avisos y códigos de ingreso); 159 mails en `laravel-2026-11-20.log`; `AvisoPedidoMail` nunca en la cola `default`. |
| Scheduler | 15:00 UTC (12:00 Argentina): `adjuntos:purgar-huerfanos` DONE 0,8 s, `idempotencia:purgar` DONE 0,9 s, **`avisos:recordatorios` DONE 0,8 s**: 1 aviso `vence_manana` nuevo y **ningún duplicado** (0 filas con más de un "vence mañana" por pedido y persona), aunque AVI había corrido el comando a mano antes. |
| Regresión corta de la Semana 1 | La hizo OBR: ingreso, barra, ficha de la obra, Usuarios/Sectores, buscador (obra, proveedor sin tildes, CUIT sin guiones), Mi perfil: ✅. PU-502 (Tab en grupos de opciones) no se repite en los diálogos de pedido (CEL). |

**Corte del worker de mail** (`logs/LEAD-worker-mail.log`), con solo el worker `mail` parado:
- 14:43:26 UTC Laura crea un pedido para Carla (id 56) → **201** "Creamos el pedido."; el aviso **asignado** para Carla se crea a los 3 s y su contador cambia a los **3,8 s**: la campana no depende del worker de mail. El usuario no ve nada raro.
- `LLEN queues:mail = 1`; health **503 `"cola_mail": false`** a los ~2 min (`HEALTH_QUEUE_MAX_AGE=120`): el monitoreo se entera (el problema de PU-021 de la Semana 1 sigue resuelto).
- Worker relanzado 14:45:43: **un solo** mail "Te asignaron un pedido: QAS2-BK Worker de mail parado" a Carla; health 200 a las 14:45:45; `failed_jobs` 0. ✅
- No se probó, durante el corte, desactivar al destinatario (RN-AVI-20, caso borde 22: el mail no debería salir): §8.

## 8. Lo que no se pudo probar y por qué

| Qué | Por qué | Rol |
|---|---|---|
| Reabrir con el propietario inactivo (DU-P09: elegir el nuevo propietario) | hace falta un pedido completado por alguien que después se desactiva, con sesión; no quedó en el camino de ningún rol | PED, AVI, PER |
| Quitar un comprobante (RN-PED-21) | no quedó en el camino (necesita un segundo cierre) | PED |
| El mail no sale si desactivan al destinatario antes de enviarlo (RN-AVI-20, caso borde 22) | necesita el worker de mail parado; el corte del Lead no lo incluyó | AVI, Lead |
| Bordes de hora del recordatorio en la corrida real (8:00, domingo) | el comando no tiene `--ahora`; se probó la acción con la hora simulada dentro de una transacción con rollback | AVI |
| Recordatorio corriendo bajo **Supervisord** | en esta máquina corre `schedule:work`, no Supervisord | Lead |
| Refresco de la campana al volver a enfocar la pestaña (RN-AVI-22) | no se midió | AVI |
| Asignación al sector **al crear** | el alta exige propietario; el texto y el asunto del aviso al sector se probaron reasignando | AVI |
| Alcance "propias" / "sus obras" en W-24, Excel, calendario y conteo de la obra | ningún sector de la prueba lo tiene y no se cambiaron permisos ajenos | JEF, OBR, PER |
| Escribir Desde/Hasta con el teclado en el campo de fecha nativo | límite conocido B-02 (Playwright); se probaron con `fill` y por URL | JEF |
| "Vencido" del back a la medianoche real | no se puede mover la hora del servidor que atiende; se verificó con `Carbon::setTestNow` en un proceso aparte y el front con `page.clock` | JEF |
| Jerarquía gerencia → jefatura → persona de 3 niveles reales | el seeder no tiene a Hernán dependiendo de una gerencia; se probó Hernán → Sofía → Pablo y Dirección = todos | JEF |
| Cámara real y foto HEIC del iPhone | Chromium headless no abre la cámara y no hay iOS Safari; el input acepta `image/*` y `.heic`, sin `capture` | CEL |
| Lector de pantalla real (TalkBack, VoiceOver, NVDA) | no hay en el entorno; se revisaron roles y nombres por DOM | CEL |
| "Menos de un minuto" con una persona escribiendo (HU-052 c5) | solo con llenado automático (3,4–4,7 s) | CEL |
| "Pegar link" como comprobante y reasignar a una persona desde el celular | no se completaron en el celular (sí en PC) | CEL |
| Subir archivo en Equipos en reparación | OBR probó links; los archivos los cubrió PER en los contextos `pedido` y `comprobante_cierre` | OBR |
| PU-601 con la obra fuera de las primeras 20 | en la base hay 2 obras | OBR |
| Desactivar a alguien con gente a cargo u obras como responsable | ya aprobado en la Semana 1; no se desactivó a nadie del seeder | OBR |
| Pedidos de carga inicial sin solicitante (DU-P10) | no hay datos de carga inicial | PER |
| 413 real del servidor | PHP de Laragon acepta hasta 2 G: 21 MB llega a la validación (422 entendible). **En producción depende de `upload_max_filesize`/`post_max_size` y del proxy: revisarlo en el despliegue** | Lead, PER |
| Suite de integración real (señal de §6.1) | fuera del alcance de la prueba de usuario | — |

## 9. Notas menores (vistas y no reportadas como hallazgo)

- **El detalle del pedido no muestra los seguidores** (PED): W-09 no los dibuja y RN-PED-26/27 no lo piden; la API los trae. Sugerencia de producto, no hallazgo.
- **`pedido_seguidores.created_at` en hora local** mientras el resto guarda UTC (PED): no se ve en pantalla; nota técnica para desarrollo.
- **Ayuda de "Fecha de pedido" en Editar** ("Hoy, si no la cambiás. Para pedidos que llegaron antes por mail.") (PED): tiene sentido al crear, no al editar. Sin captura; nota menor.
- **Diálogo "¿Sacar …?" sigue activo después del 422 por un pedido en el sector** (OBR): parecido a PU-124 (resuelto para usuarios activos). Mirarlo junto con ese arreglo.
- **Buscador con 10 por grupo sin "ver todos"** (OBR): diseño de la Semana 1; con pedidos va a pasar seguido. Para decidir con Juani.
- **"(inactivo)" en masculino para todos** (OBR): no hay dato de género.
- **W-26 con "Estado: Completada"** sigue diciendo "10 abiertas" y los grupos sin completados quedan solo con el título (JEF): se entiende.
- **El `.xlsx` sin anchos de columna, autofiltro ni fila fija** y con "Untitled Spreadsheet" en Propiedades (JEF): DU-P16 no pide más.
- **Filtros Desde/Hasta escritos en el mismo instante por un robot** se pisan (JEF): no pasa a velocidad humana.
- **Con Pedidos = Ver, `GET /api/pedidos/obras-opciones` → 403 en la consola** (PER): es a propósito y no va al canal `errors`.
- **Un link sin `https://` lo completa el front** y la API sin front lo rechaza (PER): ayuda a la persona, no es un agujero.
- **Aviso todavía "No leído" al volver, 1 de 5 vueltas** (CEL): no se reprodujo.
- **Loaders:** solo donde recarga; ningún `undefined`, `null`, `NaN`, inglés ni clave cruda en las pantallas recorridas, salvo PU-607 (PER, QC).

## 10. Veredicto (§7)

**NO APROBADA.**

| Condición | Cumple | Por qué |
|---|---|---|
| 1. Ninguna función ❌, ni ⚠️ por un hallazgo bloqueante, alto o medio | **No** | 0 ❌, pero F-12 (PU-602), F-17, F-24 y F-13 (PU-601) quedan ⚠️ por un medio. |
| 2. §6.1 funciona entera, incluidos el mail de asignación, la campana sola y el "vence mañana" | **Sí** | Los 10 puntos funcionan (§5); mail de asignación una vez, campana sola 9 de 9, "vence mañana" por el scheduler sin duplicar. |
| 3. Sin hallazgos bloqueantes, altos ni medios | **No** | 2 medios: PU-601 y PU-602. |

**Ronda corta `semana-2-ronda-2\`** (este mismo prompt; hallazgos nuevos desde **PU-701**). Verifica solo:
1. **PU-601**: "+ Nuevo pedido en esta obra" con los pasos exactos, en PC y en el celular (W-47), 3 veces; que el campo muestre la obra, que se pueda cambiar a otra obra y a "Sin obra / Servicios generales" y que se guarde lo que se ve.
   - Regresión alrededor de `CampoObra`: Nuevo pedido desde `/pedidos/nuevo` (elegir obra, buscar obra, "Sin obra"), "+ Nuevo pedido sin obra" de W-25, W-43 en el celular y Editar (W-32) cambiando la obra; si se puede, una obra fuera de las primeras 20.
2. **PU-602**: `/pedidos/carga-por-persona` con Laura y Pablo por la dirección: el texto del back ("Esta sección es para quien tiene gente a cargo.") sin filtro ni leyenda; Hernán, Diego y Dora la siguen viendo.
   - Regresión alrededor de `ConsultaError` / `States.tsx`: los demás 403 de pantalla siguen bien — Pedidos y Calendario con Sin acceso, "Solo podés ver esta sección" en Nuevo pedido con Ver, Tareas de mi gente por URL sin gente, y los de la Semana 1 (Proveedores, Usuarios, Plazos: PU-014, PU-132).
3. Si desarrollo arregla bajos en la misma tanda, se mira de paso que no rompan nada, sin reabrir la ronda por ellos.

**Pendientes menores** (bajos: desarrollo los arregla sin otra ronda): PU-603 fila/tarjeta tocable entera · PU-604 campana ≤ 15 s de punta a punta (o aclarar la spec) · PU-605 obra repetida en el aviso · PU-606 casilla de "vence mañana" en Mis avisos · PU-607 "(completado gente)" en el recorrido (**en el borde**: si sigue en la próxima ronda, sube) · PU-608 nombre repetido en el recorrido · PU-609 error que queda después de escribir · PU-610 "-1 días" · PU-611 "Pedido creado." que vuelve con F5/Atrás · PU-612 "(inactivo)" en Excel y calendario · PU-613 calendario que pierde la vista · PU-614 fila Servicios generales sin obras En ejecución · PU-615 texto largo cortado sin avisar · PU-616 "Le avisamos a" uno mismo.

## 11. Firmas

| Rol | Firma |
|---|---|
| 0 · QA Lead | Entorno, 14 ingresos, reparto de sesiones, reseteo único de la base, corte del worker de mail y rol 7 (backend, cola, scheduler) · 2026-11-20 · `borradores/lead.md` |
| 1 · PED (Pedidos de punta a punta, PC) | 11:09–11:31 AR · 0 ingresos reales · plazos restaurados a 1/7 · 5 hallazgos bajos · `borradores/ped.md` |
| 2 · AVI (Avisos y mail) | 11:10–11:45 AR · 0 ingresos reales · tabla §6 15/15 en dos vueltas · 3 hallazgos bajos · `borradores/avi.md` |
| 3 · JEF (Jefaturas, listas y calendario) | 11:31–11:55 AR · 0 ingresos reales · 4 hallazgos (bajos en el borrador; QC subió JEF-01 a media) · `borradores/jef.md` |
| 4 · OBR (Obras, equipos, personas y buscador) | 11:45–12:15 AR · 1 ingreso real · 2 hallazgos (1 media, 1 baja) + regresión de la Semana 1 ✅ · `borradores/obr.md` |
| 5 · PER (Permisos, entradas y errores) | 11:58–12:28 AR · 6 ingresos reales · ~110 ataques sin un 5xx · 2 hallazgos bajos · `borradores/per.md` |
| 6 · CEL (Celular y accesibilidad) | 12:05–12:35 AR · 0 ingresos reales · Pixel 7, iPhone SE y teclado en PC · 1 hallazgo bajo (unido con JEF-02) · `borradores/cel.md` |
| 8 · QC final | Evidencia revisada (184 capturas, 6 logs y las salidas del scratchpad citadas), lo "esperado" de §1 / `dudas.md` excluido, JEF-02 + CEL-01 unidos, AVI-02 y OBR-02 con lo visto por CEL, JEF-01 subido a media por PU-014, el resto sostenido; consultas de solo lectura a la base para PU-601, PU-607, PU-616 y `failed_jobs`. **Veredicto: NO APROBADA (2 medios).** · 2026-11-20 |
