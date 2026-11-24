# Semana 2 · Ronda 2 · Estado parcial (cortada el 2026-11-20 ~14:55 Argentina a pedido de Juani)

## Entorno
- Worktrees en `staging`: backend `tecnoservicios_backend_qa_s2r2` (rama `qa/prueba-usuario-semana-2-ronda-2` desde `origin/staging` **`9ca659a`**), front `tecnoservicios_frontend_qa_s2r2` (**`c816c89`**). `.env` copiado, `composer install`, `npm ci`, `config:clear`; tinker → `tecnoservicios_prueba_usuario`; `migrate:fresh --seed`; 12 claves de sesión viejas de la prueba borradas en Redis.
- Variables del §3 (token de `/api/errors` sin `\r`), workers `default` y `mail` iguales a `config/supervisor.php`, `schedule:work`, front sin `VITE_MODULOS_ENTREGADOS`.
- Ingresos: 7 del Lead (Diego dio de alta a Dora y a `qa-lead-s2a/b/reg/reg2/bk`). Los roles no usaron ingresos reales.
- Prompt: §0 y §2 actualizados para la ronda 2 (commit `a05a989`).

## Hecho
| Rol | Estado | Resultado |
|---|---|---|
| A · Listas, obra y calendario (PC) | terminado (`borradores/a.md`) | **PU-601, 602, 603 (PC), 611, 612, 613, 614: ✅** (cada uno 2 veces o más). Hallazgos nuevos: **A-01 (baja)** calendario con un día imposible en la dirección → sin barra y "No pudimos cargar esta información / Revisá tu conexión…" (`RangeError` en consola); **A-02 (baja)** años fuera de rango → títulos sin sentido ("Diciembre 1899") y 422 "Escribí la fecha como AAAA-MM-DD." sin que la persona escriba nada. Misma causa (`calendarioUrl.ts`). |
| B · Avisos, recorrido y diálogos (PC) | terminado (`borradores/b.md`) | **PU-604, 605, 606, 607, 608, 609, 610, 615, 616: ✅** (PU-604: 9 corridas, máx. 8,8 s). Regresión de avisos limpia (el actor no recibió lo suyo en 0 de 56; mail de asignación una vez). Hallazgo nuevo: **B-01 (baja según B; a revisar por QC: se pierde texto escrito)** con red lenta, lo que se escribe en el comentario mientras se envía el anterior se borra sin aviso ~3 s después (reproducido 4 veces; `logs/B-01.log`). |
| Lead · rol 7 | parcial | Health 200, `failed_jobs` 0, canal `errors` vacío (`GET /api/errors` → "No logs for date 2026-11-20"), workers sin errores. **No llegó a la corrida del scheduler** (arrancó 14:13 Argentina; la próxima era 15:00). |

## Falta
- **Rol C · Celular y teclado:** cortado a mitad (sin borrador). Alcanzó a dejar capturas de PU-601 en Pixel 7 e iPhone SE (`PU-601-r2-p7-*`, `PU-601-r2-se-*`). Falta: PU-603 en el celular (5 puntos por tarjeta, "Tomar" no abre, obra y "cuándo" en Avisos), PU-605 en `/avisos` del celular, PU-601 celular firmado, regresión (barra, W-43, completar con foto) y teclado en PC (una parada de Tab por fila; A ya lo vio bien en PC).
- Corrida de `avisos:recordatorios` en el scheduler.
- QC y consolidación: `hallazgos.md` (desde **PU-701**: A-01, A-02, B-01), `reporte.md` con el veredicto §7.

## Veredicto anticipado
Si C no encuentra nada medio o más y QC sostiene B-01 en baja: **APROBADA** (los 16 PU de la ronda 1 en ✅ en lo verificado; nuevos solo bajos). Si QC sube B-01 (pierde texto escrito) a media o más, no aprueba.
