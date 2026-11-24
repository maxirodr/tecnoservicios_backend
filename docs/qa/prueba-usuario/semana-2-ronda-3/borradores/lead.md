# Borrador · Lead · Semana 2 ronda 3 (visual) · 2026-11-24

- **Código:** front `0cbdc4d` (worktree `tecnoservicios_frontend_qa_s2r3`, `npm ci`), backend `d6483c9` = `432c725` + el prompt de esta ronda (worktree `tecnoservicios_backend_qa_s2r3`; `git diff 9ca659a 432c725` fuera de `docs/` y `tasks/`: vacío).
- **Entorno:** API :8340, front Vite dev :5316, workers `default` y `mail`, `schedule:work`. Base `tecnoservicios_prueba_usuario` reusada de la ronda 2 (81 pedidos, 4 obras), sin reset.
- **Ingresos reales:** 3 (Dora, Sofía y Pablo, cuyas sesiones habían vencido), por la pantalla de Ingreso nueva, sin errores. Diego, Hernán, Laura y Carla seguían con sesión.
- **Datos creados:** 4 comentarios `QAS2R3-L …` en el pedido 77 (Laura).
- **Scripts:** `scratchpad\s2r3\L\` (`lead.mjs`, `lead702b.mjs`, `peso-prod.mjs`).

## Verificación

| ID | Qué | Estado | Evidencia | Nota |
|---|---|---|---|---|
| PU-701 | Fechas imposibles en la dirección del calendario | ✅ | `capturas/PU-701-r3-*.png` | 2 vueltas × 7 direcciones, Laura en PC. `vista=semana&desde=2026-11-99`, `…=9999-12-31` y `…=1899-12-31` → "Semana del 23 al 29-nov" (hoy). `desde=0000-00-00` y `desde=9999-12-31` → "Noviembre 2026". Barra visible en todas, sin "Revisá tu conexión", sin respuestas 4xx/5xx de la API y sin errores de consola. Las fechas válidas se respetan: `vista=semana&desde=2026-12-09` → "Semana del 7 al 13-dic"; `desde=2027-02-15` → "Febrero 2027". La dirección conserva el parámetro escrito a mano (no se reescribe): no molesta. |
| PU-702 | Texto escrito mientras se envía el comentario anterior | ✅ | `capturas/PU-702-r3-1.png`, `PU-702-r3-reemplazo.png` | Red lenta (3,5 s en cada POST a la API), pedido 77, Laura. 4 vueltas; "Enviando…" visible en todas. **Reemplazando** el texto durante el envío (2 veces): al terminar, la caja queda con el texto nuevo y el primero se publica. **Agregando** al final del texto anterior (2 veces): la caja queda con "primero + segundo"; no se pierde nada, pero el primer texto sigue ahí aunque ya se publicó. Es lo que hace el arreglo (solo vacía si la caja tiene exactamente lo enviado). Va como opinión, no como hallazgo. En la base, un comentario por envío, sin duplicados. |
| PU-703 | Foco después de Tomar / dejar de seguir | — | — | Lo cubre el rol A (`borradores/a.md`). |

## Contraste (`docs/ui/tokens.md`)

- Medido en la app real: valor de cada variable leído con `getComputedStyle` en `:root`, convertido a sRGB por canvas, ratio WCAG 2.x.
- **5 pares al azar:**

  | Par | Tipo | Documentado | Medido |
  |---|---|---|---|
  | `--color-texto-suave` / `--color-fondo` | texto | 5,60 | 5,60 ✅ |
  | `--senal-borde` / `--color-fondo` | no texto | 4,18 | 4,18 ✅ |
  | `--prioridad-urgente-texto` / `--prioridad-urgente-fondo` | texto | 6,54 | 6,54 ✅ |
  | `--estado-en-curso-texto` / `--estado-en-curso-fondo` | texto | 6,47 | 6,47 ✅ |
  | `--color-borde-fuerte` / `--color-superficie` | no texto | 4,83 | 4,83 ✅ |

- De paso se midieron los 30 pares de la tabla: los 30 coinciden con lo documentado (al centésimo) y pasan el mínimo (4,5 texto, 3 no texto). Todos los colores son opacos.

## Peso

- **Build de producción** (`vite build`, sin `tsc`, a una carpeta del scratchpad):

  | | JS (gzip) | CSS (gzip) | Fuente |
  |---|---|---|---|
  | antes (`c816c89`) | 767 kB (228 kB) | 56 kB (8,9 kB) | — (fuente del sistema) |
  | después (`0cbdc4d`) | 789 kB (235 kB) | 76 kB (12,3 kB) | Archivo variable: baja solo `latin` (90 kB) en Ingreso |

- **Ingreso con la red lenta** (1,6 Mbit/s, 150 ms, sin caché), servido con `vite preview` en :5331/:5332:
  - primer contenido: 2,2 s antes y 2,2 s después;
  - red quieta: 2,3 s antes y 2,8 s después;
  - bytes: 231 kB antes y 330 kB después (+99 kB, casi todo la fuente).
  - En el preview la API no responde (CORS solo admite :5316), así que se mide solo lo estático. El Inicio pide los mismos datos que antes: suma lo mismo que el Ingreso.
- **Modo dev de Vite** (lo que usan los roles): 336 pedidos y 17 MB sin empaquetar. Ingreso e Inicio listos en ~2,5 s sin límite y ~87 s con la red lenta. No representa producción: queda solo como referencia.
- **Dato para §3:** el front no tiene código por sección (ningún `lazy()` ni `import()` en `src/`; el build es un solo `index-*.js`). "Una pantalla que no baja" no se puede forzar como dice el prompt: no hay un `assets/*.js` de una sección para bloquear.
- Aviso de Vite "Some chunks are larger than 500 kB": ya estaba antes del rediseño. Opinión, no hallazgo.

## Backend

- 12:25 Argentina: health 200, con todos los servicios en `true`.
- `failed_jobs` = 0.
- `GET /api/errors`: "No logs for date 2026-11-24" (canal vacío).

## No probado

- Tiempo del Inicio con la API real en el build de producción (CORS solo admite :5316, y no se tocó config).

— Lead QA, 2026-11-24.
