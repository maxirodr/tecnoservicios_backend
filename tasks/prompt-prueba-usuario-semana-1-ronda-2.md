# Prompt — Prueba de usuario Semana 1 · Ronda 2 (re-test después de los arreglos)

> Pegá esto como primer mensaje de una sesión nueva de Claude Code, o decile:
> "Leé y ejecutá `C:\NexoSmart\Projects\tecnoservicios_project\tecnoservicios_backend\tasks\prompt-prueba-usuario-semana-1-ronda-2.md`".

---

## 0. Misión

Sos el **equipo de QA profesional de NexoSmart** del proyecto NS-2026-025 (Tecnoservicios Bahía SA).
La ronda 1 de la prueba de usuario de la Semana 1 encontró **45 hallazgos (PU-001…PU-045)**; desarrollo
los arregló y ya están en `main` de los dos repos. Esta ronda tiene tres objetivos:

1. **Verificar cada arreglo** como usuario, con los mismos pasos de la ronda 1: ¿está resuelto de verdad,
   en todas las pantallas donde aparecía?
2. **Buscar regresiones**: los arreglos tocaron formularios, diálogos, listas, loaders, celular, ingreso,
   adjuntos y el health. Todo lo que está alrededor se pudo romper (ya pasó una vez: el aviso de
   "cambios sin guardar" frenaba el link al proveedor repetido).
3. **Repasar la Semana 1 completa** con ojos frescos, con el mismo criterio que la ronda 1.

**Igual que en la ronda 1, no arreglás código.** Solo levantás el entorno, probás, capturás evidencia
y escribís reportes, que va a leer la sesión de desarrollo.

El criterio no cambia: **una persona de la empresa, sin saber nada técnico, entiende siempre qué pasó y
qué hacer.** Nunca SQL, "Server Error", 500, textos en inglés, claves crudas (`validation.required`),
`undefined`, `null`, `[object Object]`, NaN ni pantalla en blanco. Loaders **solo en el componente que
recarga**; nada de spinner de página completa por una acción parcial.

## 1. Fuentes (leelas antes de probar)

- Backend: `C:\NexoSmart\Projects\tecnoservicios_project\tecnoservicios_backend`
- Frontend: `C:\NexoSmart\Projects\tecnoservicios_project\tecnoservicios_frontend`
- **Ronda 1, lo más importante:** `tecnoservicios_backend\docs\qa\prueba-usuario\semana-1\hallazgos.md`
  (cada PU con pasos, esperado y obtenido), `reporte.md`, `borradores\consolidacion.md`, `capturas\`.
- Prompt de la ronda 1 (roles, entorno, checklist completo; **seguí valiendo todo lo que dice salvo lo que
  cambia acá**): `tecnoservicios_backend\tasks\prompt-prueba-usuario-semana-1.md`
- Qué se cambió: `git log --oneline` de los dos repos desde el 2026-11-16 (commits `fix(...)`, `feat(...)`
  y los merges "week 1 user test fixes"). `tasks\todo.md` § "Revisión arreglos de la prueba de usuario".
- Documento firmado: `C:\NexoSmart\Projects\tecnoservicios_project\tecnoservicios\docs\entregas\2026-10-19-documento-sdr\documento-sdr.html`
- Especificaciones: `docs\especificacion\identidad.md`, `obras.md`, `proveedores.md`; decisiones en
  `docs\dudas.md` (DU-Q04…Q06 son nuevas) y `docs\adr\` (ADR-015 tiene una enmienda nueva).
- `tasks\lessons.md` (trampas de entorno conocidas).

### Decisiones nuevas de Juani (son el "esperado", no hallazgos)
- **PU-043:** en proveedores la ñ ya no es n para los repetidos: "Corralon Nandu" y "Corralón Ñandú" son
  **parecidos** (aviso que deja guardar), no repetidos. Buscar "nandu" sigue encontrando "Ñandú".
- **PU-044:** el buscador de proveedores (listado y buscador general) encuentra por **CUIT** con 4 dígitos
  o más, con o sin guiones. Por rubro, no.
- **PU-045:** quien queda bloqueado por equivocarse con el código ve en el ingreso "¿No te llegó? Si
  probaste varias veces, esperá una hora o pedile a gerencia que lo revise." (el bloqueo sigue sin
  delatarse). En Usuarios se ve "Ingreso trabado hasta las HH:MM" y, en su ficha, "Destrabar ingreso"
  (queda en el historial).
- **PU-002:** si dos personas editan lo mismo, la segunda ve arriba "Alguien cambió esto mientras lo
  tenías abierto. Actualizá y revisá." con "Actualizar". Límite conocido y aceptado: dos guardados dentro
  del **mismo segundo** no se detectan.

## 2. El equipo

Los mismos roles de la ronda 1 (§2 de ese prompt), con este reparto:

| Rol | Foco en la ronda 2 |
|---|---|
| **QA Lead** (sesión principal) | Entorno, reparto, único que resetea la base, consolidación, matriz PU → estado, resumen final. |
| **Verificador de arreglos** (2 subagentes: PU-001…022 y PU-023…045) | Reproduce **cada PU con los pasos exactos de la ronda 1** (incluida la demora artificial de red donde se usó) y marca ✅ resuelto / ⚠️ resuelto a medias / ❌ sigue. Revisa **todas** las pantallas listadas en el PU, no solo la primera. |
| **Cazador de regresiones** | Prueba lo nuevo y lo que está cerca: aviso de cambios sin guardar (F5, atrás, links, Cancelar, cerrar pestaña, después de guardar, links "Ver la ficha de…" y "Ese N.º ya está en…"), borradores recuperados ("Recuperamos lo que estabas escribiendo."), conflicto 409 y "Actualizar", sesión vencida y vuelta a la misma pantalla, doble clic, diálogos que no se cierran mientras guardan, "No había cambios para guardar.", pestañas con flechas del teclado, listas con "Buscando…", tarjetas en el celular, alta de proveedor desde `/proveedores/nuevo`. |
| **Tester funcional por persona** (Dirección, Gerencia Adm., Jefatura, Ingeniería, Compras) | Pasada completa de la Semana 1 como en la ronda 1, más corta en lo que ya estaba ✅. |
| **QA de entradas y UX writer** | Todos los formularios con entradas inválidas y todos los textos nuevos: ¿se entienden?, ¿son coherentes entre pantallas? |
| **QA de backend, cola y health** | Logs, `failed_jobs`, canal `errors` (ahora **no** debe tener 4xx, solo 5xx y jobs fallidos), `/api/health` (ver §4), workers y scheduler. |
| **QA celular y accesibilidad** | Pixel 7 (412×915) e iPhone SE (375×667, forzado). Controles ≥ 44 px, tablas en tarjetas, pestañas deslizables. |
| **QC final** | Revisa que cada estado esté respaldado por evidencia, deduplica, firma. |

## 3. Entorno

Igual al §3 del prompt de la ronda 1 (base propia `tecnoservicios_prueba_usuario`, API :8340, front
:5316, `APP_DEBUG=false`, `MAIL_MAILER=log`, `QUEUE_CONNECTION=redis`, prefijos de Redis propios), con
estos cambios aprendidos en la ronda 1:

- **Antes de empezar:** MySQL **y Redis** arriba en Laragon (verificá los dos; si no, pedile al usuario
  que los levante desde Laragon, no por consola). Después de un corte, MySQL puede tardar varios minutos
  en aceptar conexiones (recuperación de InnoDB): esperá a que el log diga "ready for connections".
- La base `tecnoservicios_prueba_usuario` ya existe; **`migrate:fresh --seed` solo sobre ella**,
  verificando antes con tinker que la config apunta ahí. Hay migraciones nuevas (proveedores: clave del
  nombre con ñ). **Nunca dropees bases.**
- **Workers iguales a `config/supervisor.php`** (sin el de `mail` no sale ningún código):
  `php artisan queue:work redis --queue=default --sleep=3 --tries=3 --backoff=10` y
  `php artisan queue:work redis --queue=mail --sleep=1 --rest=1 --tries=5 --backoff=30`, más `php artisan schedule:work`.
- **Límite de 20 pedidos de código por IP por hora:** todo el equipo sale de la misma IP. El Lead ingresa
  una vez con cada persona, guarda las sesiones (storageState de Playwright) y las reparte. Las pruebas
  de bloqueo y de límite se hacen con usuarios propios (`qa-lead-…@empresa-ejemplo.com`) y al final.
- La memoria de esta máquina es justa (14 GB): no más de **3 navegadores a la vez**; cerrá los que no uses.
- Al terminar, pará **solo** los procesos que levantaste.

## 4. Qué probar además de los PU

- **Health (`GET /api/health`):** 200 con todo bien; con **Redis parado** responde **503 en menos de 3 s**
  con `"redis": false` (antes tardaba 51 s); con el **worker de mail parado** y un pedido de código
  esperando más de 2 minutos, `"cola_mail": false` y 503. Si podés, también con MySQL parado (pedíselo al
  usuario). En todos los casos la app muestra un mensaje entendible y se recupera sola al volver.
- **Sin sesión:** abrir `http://localhost:8340/api/obras/1` y `/api/adjuntos/1` en el navegador → 401 JSON
  entendible, nunca 500; y nada nuevo en el canal `errors`.
- **Canal `errors` / `/api/errors`:** después de usar formularios con datos inválidos, **no** debe haber
  entradas de validación (422) ni 401/403/404/409/429.
- **Re-verificar B-01…B-05** del QC (`docs\qc\entrega-1.md`).

## 5. Reportes

Carpeta nueva: `C:\NexoSmart\Projects\tecnoservicios_project\tecnoservicios_backend\docs\qa\prueba-usuario\semana-1-ronda-2\`

1. **`verificacion.md`**: tabla con **los 45 PU**: `PU-XXX | título corto | estado (✅ / ⚠️ / ❌) | pantallas
   revisadas | evidencia | nota`. Un ⚠️ o ❌ lleva el texto exacto de lo que se vio y el PU nuevo que lo
   detalla, si corresponde.
2. **`hallazgos.md`**: **solo lo nuevo** (regresiones y cosas que la ronda 1 no vio), con el **mismo
   formato exacto de la ronda 1** pero numerado desde **PU-101**. Agregá la línea
   `- **Regresión de:** PU-0XX / commit` cuando un arreglo rompió otra cosa.
3. **`reporte.md`**: resumen ejecutivo, entorno (commits `git rev-parse --short HEAD` de los dos repos),
   conteo de PU por estado, hallazgos nuevos por severidad y tipo, tabla F-XX → ✅ / ⚠️ / ❌, lo que no se
   pudo probar y por qué, firmas de cada rol.
4. `capturas\` (`PU-XXX-n.png`, `PU-1XX-n.png`) y `logs\` (solo las líneas de cada hallazgo).

Severidades y reglas iguales a la ronda 1: texto exacto entre comillas, un problema por hallazgo, todo
dato ficticio, nada de secretos, tokens ni códigos de ingreso en los reportes.

## 6. Git

- Rama `qa/prueba-usuario-semana-1-ronda-2` en el **backend**. Commits convencionales en inglés
  (`docs(qa): …`) que terminan con `Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>`.
- Pusheá al cerrar cada bloque grande y al final. No mergees a `main`. No commitees `.env`,
  `.claude\bug-report.json` ni archivos subidos de prueba.

## 7. Al terminar

Pará tus procesos, push final y respondé con: PU ✅ / ⚠️ / ❌ (cuántos y cuáles no quedaron), hallazgos
nuevos por severidad, las regresiones en una línea cada una, qué no se pudo probar, y la ruta de `reporte.md`.
