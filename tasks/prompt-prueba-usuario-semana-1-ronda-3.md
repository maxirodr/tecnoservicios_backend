# Prompt — Prueba de usuario Semana 1 · Ronda 3 (re-test corto después de los arreglos de la ronda 2)

> Pegá esto como primer mensaje de una sesión nueva de Claude Code abierta en la carpeta del proyecto,
> o decile: "Leé y ejecutá `tecnoservicios_backend\tasks\prompt-prueba-usuario-semana-1-ronda-3.md`".
>
> **Rutas:** este prompt usa rutas relativas a la carpeta del proyecto
> (`…\NexoSmart\Projects\tecnoservicios_project\`). En una máquina es `C:\NexoSmart\Projects\…` y en
> otra `B:\ProgramFiles\NexoSmart\Projects\…`: usá la que corresponda a la máquina donde corrés.

---

## 0. Misión

Sos el **equipo de QA profesional de NexoSmart** del proyecto NS-2026-025 (Tecnoservicios Bahía SA).

Así venimos:
- **Ronda 1:** 45 hallazgos (PU-001…045).
- **Ronda 2:** quedaron ✅ 32 · ⚠️ 12 · ❌ 1 de esos 45, y aparecieron **39 nuevos (PU-101…139)**.
- **Desarrollo:** dice que arregló todos. Están en `main` (y en `staging`), y además pasaron la suite de integración real contra el backend (ver §1, "Qué cambió").

Esta ronda es **corta y enfocada**. Tiene tres objetivos, en este orden:

1. **Verificar los 39 PU de la ronda 2 (PU-101…139)** con sus pasos exactos.
2. **Verificar los 13 que quedaron ⚠️/❌ en la ronda 2**: PU-002 (❌), PU-003, PU-004, PU-006, PU-013, PU-018, PU-020, PU-022, PU-028, PU-029, PU-038, PU-041 y PU-043.
3. **Cazar regresiones** de los arreglos de la ronda 2. Tocaron formularios, diálogos, borradores, conflictos de edición, foco y teclado, el celular, el health y textos.

No repitas la pasada completa de la Semana 1. Lo que estaba ✅ en la ronda 2 se mira solo si queda en el camino de una prueba.

**No arreglás código.** Levantás el entorno, probás, capturás evidencia y escribís reportes, que va a leer la sesión de desarrollo.

El criterio no cambia: **una persona de la empresa, sin saber nada técnico, entiende siempre qué pasó y qué hacer.** Nunca debe aparecer nada de esto:
- SQL, "Server Error", 500 o textos en inglés;
- claves crudas, `undefined`, `null`, `[object Object]` o NaN;
- códigos internos (W-XX, PU-XX, F-XX);
- una pantalla en blanco.

Los loaders van **solo en el componente que recarga**.

## 1. Fuentes (leelas antes de probar)

- **Repos:** `tecnoservicios_backend` y `tecnoservicios_frontend`, los dos en `main` actualizado (`git pull`).
- **Ronda 2, lo más importante:** `tecnoservicios_backend\docs\qa\prueba-usuario\semana-1-ronda-2\`:
  - `hallazgos.md`: cada PU-101…139 con pasos, esperado y obtenido;
  - `verificacion.md`: estado de los 45 de la ronda 1, con el texto exacto de cada ⚠️/❌;
  - `reporte.md`, `capturas\` y `logs\`.
- **Prompts anteriores** (roles, entorno y checklist; **sigue valiendo todo lo que dicen salvo lo que cambia acá**):
  - `tecnoservicios_backend\tasks\prompt-prueba-usuario-semana-1.md` (ronda 1, §3 entorno);
  - `tecnoservicios_backend\tasks\prompt-prueba-usuario-semana-1-ronda-2.md`.
- **Qué cambió:**
  - `git log --oneline --since=2026-11-17` en los dos repos. Los commits de la ronda 2 mencionan su PU entre paréntesis: `fix(...): … (PU-1XX)`.
  - `tecnoservicios_backend\tasks\todo.md` § "Ronda 2 de la prueba de usuario" y § "Integración después de la ronda 2".
- **Especificaciones:** `docs\especificacion\identidad.md`, `obras.md` y `proveedores.md`; `docs\dudas.md`; `docs\adr\` (ADR-015 con sus enmiendas).
- **Documento firmado:** `tecnoservicios\docs\entregas\2026-10-19-documento-sdr\documento-sdr.src.html`.
- `tasks\lessons.md` de los dos repos: trampas de entorno conocidas.

### Lo que ya es "esperado" (no lo reportes como hallazgo)

Decisiones de Juani (vigentes desde la ronda 2):
- **PU-043:** "Corralon Nandu" y "Corralón Ñandú" son *parecidos* (aviso que deja guardar), no repetidos. Buscar "nandu" encuentra "Ñandú".
- **PU-044:** el buscador de proveedores (listado y buscador general) encuentra por CUIT con 4 dígitos o más, con o sin guiones.
- **PU-045:** a la persona bloqueada, el ingreso le muestra "¿No te llegó? …". En Usuarios se ve "Ingreso trabado hasta las HH:MM" y la ficha tiene "Destrabar ingreso".
- **PU-002:** conflicto de edición → "Alguien cambió esto mientras lo tenías abierto…" con "Actualizar". Dos guardados dentro del **mismo segundo** no se detectan: es un límite aceptado.

Decisiones de UX que tomó desarrollo y que **Juani todavía tiene que validar**. Probalas, pero si no te convencen reportalas como **observación** (sección aparte del reporte), **no como hallazgo**:
- Intentar salir mientras guarda → "Estamos guardando. Esperá unos segundos.".
- Cancelar o Escape en un diálogo con cambios → pregunta antes de cerrar.
- Fecha anterior a 2000 o posterior al año actual + 10 → aviso no bloqueante "Revisá la fecha: es de {año}." (PU-028 y PU-117).
- REV00 → "Las revisiones empiezan en REV01." (PU-122).
- "Guardar proveedor" no se deshabilita ante un repetido; el aviso pasa a rojo.

## 2. El equipo y la asignación de roles

La **sesión principal es el QA Lead**. Lanza a los demás roles como subagentes, uno por rol, cada uno con este prompt, su lista de PU y las sesiones guardadas que le reparte el Lead.

**Corren a la vez como máximo 3 subagentes con navegador.** La memoria de las máquinas es justa. Si un rol no usa navegador, puede correr en paralelo con ellos.

| # | Rol | Qué verifica | Navegador |
|---|---|---|---|
| 0 | **QA Lead** (sesión principal) | Entorno, ingreso de cada persona y reparto de sesiones (storageState), **único que resetea la base**, orden de las tandas, consolidación, matriz PU → estado, resumen final. | sí (solo para ingresar) |
| 1 | **Verificador A: conflictos, guardado y salida** | PU-101, 102, 103, 104, 107, 108, 111, 114, 115, 121, 123, 127, más los de la ronda 2 PU-002, 003, 006 y 029. Repite cada uno con **dos pestañas o dos personas** cuando el PU lo pide, y con la demora artificial de red donde la ronda 2 la usó. | sí |
| 2 | **Verificador B: sesión, borradores y adjuntos** | PU-105, 106, 109, 112, 120, 129, más PU-004. Sesión vencida de verdad (ingreso real con un usuario `qa-lead-…`), borrador por persona, subir archivo y agregar link con la sesión vencida, links con ñ y espacios. | sí |
| 3 | **Verificador C: proveedores, textos y datos** | PU-110, 113, 116, 117, 118, 119, 122, 124, 125, 126, 128, 130, 131, 132, 133, 137, más PU-013, 028, 038 y 043. Texto exacto de cada aviso; coherencia entre pantallas. | sí |
| 4 | **QA celular y accesibilidad** | PU-134, 135, 136, más PU-018, 022 y 041. Pixel 7 (412×915) e **iPhone SE (375×667)**. Controles ≥ 44 px, tablas en tarjetas, foco que no se cae del botón mientras guarda, Tab atrapado en los diálogos, lector de pantalla ("Guardando…"). | sí (después de 1–3) |
| 5 | **QA backend, cola y health** | PU-138, 139, más PU-020. Redis parado → 503 en < 3 s y la app **se recupera sola** al volver; MySQL parado (pedíselo al usuario) → health < 3 s y app recuperable; worker de mail parado → `"cola_mail": false`. Canal `errors` y `/api/errors` **sin** 4xx (solo 5xx y jobs fallidos), `failed_jobs`, workers y scheduler. | no (curl/logs) |
| 6 | **Cazador de regresiones** | Todo lo de alrededor de los arreglos: aviso de cambios sin guardar (F5, atrás, links, Ctrl+clic, Cancelar, cerrar pestaña, después de guardar), borradores recuperados y descartados, 409 y "Actualizar" en obra, presupuesto principal y Mi perfil, diálogos que no se cierran mientras guardan, doble clic, pestañas con flechas, listas con "Buscando…", alta de obra y de proveedor completa, adjuntos (archivo y link), cambio de estado, presupuesto por rubro, permisos por sector (Dirección, Gerencia Adm., Jefatura, Ingeniería, Compras). | sí (al final) |
| 7 | **QC final** | Revisa que cada estado tenga evidencia (captura o log), deduplica, controla que lo "esperado" de §1 no se haya reportado como hallazgo, y firma. | no |

Orden sugerido:
- **Tanda 1:** roles 1, 2 y 3 en paralelo, más el 5, que no usa navegador.
- **Tanda 2:** roles 4 y 6.
- **Al final:** QC (7) y consolidación del Lead.

Las pruebas que paran servicios (rol 5) van **en un momento en que nadie más esté probando**. El Lead las coordina.

## 3. Entorno

Igual al §3 del prompt de la ronda 1:
- base propia `tecnoservicios_prueba_usuario`;
- API en :8340 y front en :5316;
- `APP_DEBUG=false`, `MAIL_MAILER=log`, `QUEUE_CONNECTION=redis`;
- prefijos de Redis propios.

Además:
- **MySQL y Redis arriba antes de empezar.** Verificá `select 1` y `PING`.
  - **En la máquina con Laragon:** si no responden, pedile al usuario que los levante desde Laragon, no por consola. Después de un corte, MySQL puede tardar varios minutos (recuperación de InnoDB).
  - **En la máquina sin Laragon** (`B:\…`): MySQL es el MariaDB 10.4 de XAMPP (`C:\xampp\mysql\bin\mysqld --defaults-file=mysql\bin\my.ini --standalone`, desde `C:\xampp`). Redis es un ejecutable portable, no un servicio. Si no están arriba, pedile al usuario que los levante.
- **`migrate:fresh --seed` SOLO sobre `tecnoservicios_prueba_usuario`.** Antes, verificá con tinker que la config apunta ahí. Nunca dropees bases. Nunca toques `tecnoservicios`, `tecnoservicios_testing*` ni `tecnoservicios_integracion`.
- **Workers iguales a `config/supervisor.php`:**
  - `php artisan queue:work redis --queue=default --sleep=3 --tries=3 --backoff=10`
  - `php artisan queue:work redis --queue=mail --sleep=1 --rest=1 --tries=5 --backoff=30`
  - `php artisan schedule:work`

  Sin el worker de `mail` no sale ningún código.
- **Límite de 20 pedidos de código por IP por hora, y bloqueo progresivo por mail** (3 códigos quemados seguidos dan 1 h): todo el equipo sale de la misma IP.
  - El Lead ingresa una vez con cada persona, guarda las sesiones (storageState) y las reparte.
  - Las pruebas de bloqueo, límite y sesión vencida se hacen con usuarios propios (`qa-lead-…@empresa-ejemplo.com`) y al final.
- **`IDENTIDAD_CODIGO_EN_PANTALLA` va apagado en esta prueba**: el código se lee del mail logueado. Ese modo existe solo para servidores de prueba y se ignora con `APP_ENV=production`.
- Al terminar, pará **solo** los procesos que levantaste. No toques los puertos 8000, 5173, 8320 ni 5312.

## 4. Reportes

Carpeta nueva: `tecnoservicios_backend\docs\qa\prueba-usuario\semana-1-ronda-3\`.

1. **`verificacion.md`**: una tabla con **los 52 PU** de esta ronda (PU-101…139, más los 13 ⚠️/❌ de la ronda 2). Columnas: `PU-XXX | título corto | estado (✅ / ⚠️ / ❌) | pantallas revisadas | rol | evidencia | nota`. Un ⚠️ o ❌ lleva el texto exacto de lo que se vio y el PU nuevo que lo detalla, si corresponde.
2. **`hallazgos.md`**: **solo lo nuevo**, con el mismo formato exacto de las rondas anteriores, numerado desde **PU-201**. Cuando un arreglo rompió otra cosa, agregá `- **Regresión de:** PU-1XX / commit`.
3. **`observaciones.md`**: lo que no te convence de las decisiones de UX "a validar por Juani" (§1), con captura.
4. **`reporte.md`**:
   - resumen ejecutivo;
   - entorno: máquina y commits (`git rev-parse --short HEAD` de los dos repos);
   - conteo por estado;
   - hallazgos nuevos por severidad y tipo;
   - tabla F-XX → ✅ / ⚠️ / ❌ solo de las funciones tocadas;
   - lo que no se pudo probar y por qué;
   - **firma de cada rol**.
5. **`capturas\`** (`PU-XXX-r3-n.png`, `PU-2XX-n.png`) y **`logs\`** (solo las líneas de cada hallazgo).

Severidades y reglas iguales a las rondas anteriores:
- texto exacto entre comillas;
- un problema por hallazgo;
- datos ficticios;
- nada de secretos, tokens ni códigos de ingreso en los reportes.

## 5. Git

- Rama `qa/prueba-usuario-semana-1-ronda-3` en el **backend**, creada desde `main` actualizado.
- Commits convencionales en inglés (`docs(qa): …`), terminados en `Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>`.
- No toques git config: la identidad y la autenticación ya están configuradas.
- Pusheá al cerrar cada tanda y al final.
- No mergees a `main`.
- No commitees `.env`, `.claude\bug-report.json` ni los archivos subidos en las pruebas.

## 6. Al terminar

Pará tus procesos, hacé el push final y respondé con:
- PU ✅ / ⚠️ / ❌: cuántos, y cuáles no quedaron;
- hallazgos nuevos por severidad;
- regresiones, una línea cada una;
- observaciones de UX para Juani;
- qué no se pudo probar;
- la ruta de `reporte.md`.
