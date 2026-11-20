# Borrador · QA Lead · Semana 2 · ronda 1 (2026-11-20, máquina con Laragon `C:\`)

## Entorno
- **Worktrees fijos en `staging`** (§3 del prompt): backend `tecnoservicios_backend_qa_s2` en la rama `qa/prueba-usuario-semana-2` desde `origin/staging` **`7e007dd`**; front `tecnoservicios_frontend_qa_s2` en **`d8f2433`** (detached en `origin/staging`). `.env` copiado del principal (gitignored), `composer install` + `dump-autoload`, `npm ci`. Los repos principales no se tocaron (la sesión de desarrollo trabaja en `main` @ `b3916d5` y en los worktrees `_avisos` / `_pedidos`).
- MySQL 8.4.3 y Redis 5.0.14.1 de Laragon (`select 1` y `PING` OK). PHP 8.3.30. Memoria libre 4,7–5,8 GB (por debajo de los 6 GB del §3: como máximo 2 navegadores de la prueba a la vez, nunca 3).
- Variables del §3 a cada proceso; `ERRORS_TOKEN` nuevo de 64 caracteres **sin `\r`** (fuera del repo). `config:clear`; tinker → `tecnoservicios_prueba_usuario`, `app.zona_empresa` = `America/Argentina/Buenos_Aires`; `migrate:fresh --seed`.
- **Sesiones viejas de la prueba borradas en Redis** al resetear (§3.5): 8 claves `tecnoservicios_prueba_tecnoservicios_prueba_cache_*` en la db 0.
- Procesos: `serve --no-reload` :8340, workers `default` y `mail` iguales a `config/supervisor.php`, `schedule:work`, front `npm run dev -- --port 5316 --strictPort` sin `VITE_MODULOS_ENTREGADOS`. Smoke: health 200, front 200.
- `schedule:list`: `adjuntos:purgar-huerfanos`, `idempotencia:purgar` y **`avisos:recordatorios`** cada hora.
- **Ingresos:** 7 del Lead (Diego dio de alta a Dora — Dirección — y a `qa-lead-s2a/b/reg/reg2/bk`; después Dora, Hernán, Laura, Sofía, Pablo y Carla) + 1 OBR + 6 PER = **14**, sin 429 ni códigos quemados.

## Rol 7 · Backend, cola y scheduler
| Qué | Resultado |
|---|---|
| `/api/health` | 200 con los 5 servicios en `true` toda la ronda (0,1–1,1 s), salvo el corte del worker de mail (abajo). |
| `failed_jobs` | 0 al cierre de cada tanda y al final. |
| Canal `errors` | No se creó `errors-2026-11-20.log` en toda la ronda; `GET /api/errors` (con token) → "No logs for date 2026-11-20". **Ni 4xx ni 5xx**, aun con los ataques del rol 5. |
| Workers | `default` y `mail` sin FAIL/Exception; 173 jobs DONE en la cola `mail` (asignaciones, reasignaciones, "completado" de Mis avisos y códigos de ingreso); 159 mails en `laravel-2026-11-20.log`. |
| Scheduler | 15:00 UTC (12:00 Argentina): `adjuntos:purgar-huerfanos` DONE 0,8 s, `idempotencia:purgar` DONE 0,9 s, **`avisos:recordatorios` DONE 0,8 s**: creó 1 aviso `vence_manana` nuevo y **ningún duplicado** (consulta por pedido y persona con más de un "vence mañana": 0 filas), aunque AVI había corrido el comando a mano antes. |
| Regresión corta de la Semana 1 | La hizo el rol OBR (ingreso, barra, ficha de obra, Usuarios/Sectores, buscador, Mi perfil): ✅ (ver `obr.md`). |

### Corte del worker de mail (control del criterio n.º 3)
`logs/LEAD-worker-mail.log`. Con **solo** el worker `mail` parado (el resto arriba):
- 14:43:26 UTC Laura crea por API un pedido para Carla (`QAS2-BK Worker de mail parado`, id 56) → **201** "Creamos el pedido."; el aviso **asignado** (id 265) para Carla se crea 14:43:29 y su contador de no leídos cambia a los **3,8 s** (la campana no depende del worker de mail).
- `LLEN queues:mail = 1`; health **503 `"cola_mail": false`** a las 14:45:34 (~2 min, `HEALTH_QUEUE_MAX_AGE=120`).
- Worker relanzado 14:45:43: `AvisoPedidoMail` DONE; **un solo** mail "To: carla@empresa-ejemplo.com · Subject: Te asignaron un pedido: QAS2-BK Worker de mail parado"; health 200 a las 14:45:45; `failed_jobs` 0. ✅

## Notas
- El 413 real no se pudo probar: el PHP de Laragon acepta archivos de hasta 2 G, así que 21 MB llega a la validación de Laravel y da 422 (mensaje entendible). En producción depende de `upload_max_filesize`/`post_max_size` y del proxy: revisarlo en el despliegue.
