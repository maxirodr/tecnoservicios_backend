# Borrador · QA Lead · Semana 2 · ronda 2 (corta) · 2026-11-20 y 2026-11-24

## Entorno
- Worktrees en `staging`: backend `tecnoservicios_backend_qa_s2r2` (rama `qa/prueba-usuario-semana-2-ronda-2` desde `origin/staging` **`9ca659a`**; arreglos de back en `fb55537`), front `tecnoservicios_frontend_qa_s2r2` (**`c816c89`**). `staging` no se movió entre el 20/11 y el 24/11 (verificado con `git fetch`).
- `.env` copiado, `composer install` + `dump-autoload`, `npm ci`, `config:clear`; tinker → `tecnoservicios_prueba_usuario`; `migrate:fresh --seed` (20/11); 12 claves de sesión viejas de la prueba borradas en Redis antes de ingresar.
- Variables del §3 (token de `/api/errors` de 64 caracteres sin `\r`), workers `default` y `mail` iguales a `config/supervisor.php`, `schedule:work`, front `npm run dev -- --port 5316 --strictPort` sin `VITE_MODULOS_ENTREGADOS`.
- §0 y §2 del prompt actualizados para la ronda 2 (commit `a05a989`).

## Corte y reanudación
- **20/11 ~14:55 Argentina:** Juani pidió parar. A y B habían terminado; C quedó a mitad (corridas del celular de PU-603/PU-605 completas, PU-601 parcial). Se pararon todos los procesos y se dejó `estado-parcial.md` (commit `6750fa5`).
- **24/11 09:04 Argentina:** MySQL (Juani desde Laragon) y Redis (el Lead) apagados → levantados. La base conservó los datos de la ronda (13 usuarios, 71 pedidos); **las sesiones no** (Redis arrancó sin ellas: `/api/me` → 401 con las 4 sesiones guardadas, "Tu sesión venció…", comportamiento correcto). Se volvió a ingresar con Diego, Carla, Laura y Hernán (4 códigos). Mismos commits, mismos worktrees, procesos relanzados con las mismas variables.
- **Ingresos reales de la ronda:** 7 (20/11) + 4 (24/11) = 11, sin 429 ni códigos quemados. Los roles no usaron ingresos reales.

## Rol 7 · Backend, cola y scheduler
| Qué | Resultado |
|---|---|
| `/api/health` | 200 con los 5 servicios en `true` los dos días. |
| `failed_jobs` | 0. |
| Canal `errors` | No se creó ningún `errors-*.log` en el worktree; `GET /api/errors` → "No logs for date 2026-11-20" y "… 2026-11-24". Ni 4xx ni 5xx. |
| Workers y API | Sin FAIL/Exception/ERROR en las salidas de los dos días. |
| `avisos:recordatorios` | Corrida del 24/11 13:00 UTC (10:00 Argentina): DONE 2 s; creó los 4 "vence mañana" previstos (pedidos 1 y 5 → Sofía, 10 → Carla, 50 → Laura), ningún mail. Sin "vence mañana" duplicados en la base (consulta por pedido y persona con más de uno: 0 filas). |
