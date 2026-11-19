# Borrador A · Verificador A (guardado, salida y conflicto de sesión) · Ronda 5

- **Fecha:** 2026-11-19, de 12:07 a 12:26 (hora local de la máquina; base y logs en UTC = +3 h).
- **Código probado:** worktrees `_qa5` en `staging` (back `6516ba7`, front `d2a4806`). API :8340 y front :5316. Health 200 (0,3 s) al empezar. Memoria libre: ~4,1 GB. No hubo lentitud ni 503 en ningún momento.
- **Personas / sesiones:** Diego (`sesiones/diego.json`: el "otro" que cambia los datos, Tabla de permisos y O-1.1), `qa-lead-r5a` (uid 8) y `qa-lead-r5b` (uid 9), los dos de Gerencia Adm., con **ingreso real**.
- **Ingresos reales gastados: 6 de 6** (r5a ×3: 15:11, 15:12 y 15:13 UTC; r5b ×3: 15:13, 15:14 y 15:15 UTC). Ningún código quemado y ningún 429. Llevo la cuenta en `A/ingresos.txt`.
- **Sesiones vencidas:** solo las mías (`DEL` de la clave de uid 8/9 con la página quieta, `networkidle` + 2,5 s; `EXISTS` → 0 antes de guardar). No toqué ninguna sesión compartida.
- **Datos creados (prefijo QA5A):** obra `QA5A Obra sesión vencida` (id 1, N.º 7-551-2026) y proveedor `QA5A Ferretería Sesión` (id 2, CUIT 30-71955100-5), los dos con Diego desde la pantalla.
- **Tabla de permisos:** "Alcance de Pedidos · RRHH" (sector 10, función 1) quedó en **editar / todas**, como estaba. Verificado en la base: es la única fila de `permisos` modificada (`updated_at` 15:22:45).
- **Scripts y salidas:** `C:\Users\GREEN\AppData\Local\Temp\claude\4fe530b1c11d8e8d\f4efcdda-aa94-4434-aafd-2e4822d4d74b\scratchpad\r5\A\`
  - scripts: `01-datos.mjs`, `02-pu305.mjs`, `03-pu306.mjs`, `04-o11.mjs`;
  - salidas: `out-305-parte1.txt`, `out-305-parte2.txt`, `out-306-a.txt`, `out-306-b.txt`, `out-o11.txt`, `out-o11-b.txt`.

## Verificación

| PU | título corto | estado | pantallas revisadas | evidencia | nota |
|---|---|---|---|---|---|
| PU-305 | Sesión vencida mientras otro cambia el registro: al volver, "Mientras no estabas…" con lo que guardó el otro | ✅ | W-23 Editar obra `/obras/1/editar` · W-14 Editar proveedor (diálogo en `/proveedores/2`) | `PU-305-r5-obraC-1-{401,vuelta,guardado}.png`, `PU-305-r5-provC-2-{401,vuelta,guardado}.png`, control `PU-305-r5-obraK-3-*.png`, `PU-305-r5-provK-4-*.png` · `out-305-parte1/2.txt` | Ver el detalle abajo. Con ingreso real por pantalla en la misma pestaña. |
| PU-306 | Tabla de permisos: si el guardado falla, el error queda a la vista y con el foco | ✅ | W-15 `/usuarios/permisos` (PC 1366×768) | `PU-306-r5-1…20-*-final.png` y `*-guardando.png` · `out-306-a.txt`, `out-306-b.txt` | 20 corridas: 500, 422 y 409, cada uno sin irse, con Atrás, con la barra ("Obras") y con "Sectores". El 500 y el 422, 2 veces cada combinación. |

### Detalle PU-305

En cada caso: Editar → escribir → vence **mi** sesión → (Diego cambia y guarda el mismo registro) → "Guardar" → 419/401 → "Tu sesión venció. Volvé a ingresar." en `/ingresar` → ingreso real por pantalla.

- **Editar obra, con cambio de Diego (r5a, #1):**
  - Diego dejó la planta en "Planta QA5A Diego obraC-1" (`PATCH /api/obras/1` → 200). Yo había escrito "Planta QA5A r5a obraC-1".
  - Al volver: `/obras/1/editar` con **"Mientras no estabas, alguien cambió esto. Lo que habías escrito no se pudo recuperar."** y la planta de Diego en el campo. No aparece "Recuperamos…" y no quedan borradores.
  - Después guardé encima de lo de Diego → `PATCH` 200 ("Guardamos los cambios de la obra."), sin 409. La base tiene lo esperado y el cliente no cambió: no se pisó nada.
- **Editar proveedor, con cambio de Diego (r5a, #2):**
  - Diego cambió el teléfono a 291 455-8102 y el mail.
  - Al volver: `/proveedores/2` con el diálogo "Editar proveedor" **reabierto solo**, el aviso "Mientras no estabas…" y el teléfono y el mail de Diego.
  - Al guardar: 200, "Guardamos los cambios de QA5A Ferretería Sesión.", y el mail de Diego se conservó.
- **Control sin cambio de otro (r5b, #3 obra y #4 proveedor):**
  - Al volver: **"Recuperamos lo que estabas escribiendo."** con lo que yo había escrito ("Planta QA5A r5b obraK-3" y el teléfono 291 455-7104).
  - Al guardar: 200 y en la base queda exactamente eso.
- **Borrador descartado / recuperado no reaparece:** en los 4 casos, al reabrir Editar obra o Editar proveedor después de guardar no hay ningún aviso y `sessionStorage` no tiene ninguna clave `borrador`.
- **Repeticiones:** con el tope de 6 ingresos, cada caso se probó **1 vez**: conflicto obra, conflicto proveedor, control obra y control proveedor (4 ingresos), más el ingreso inicial y el cambio r5a → r5b. Todos pasaron a la primera, así que no hizo falta reproducir ningún hallazgo.

### Detalle PU-306 (mediciones con `getBoundingClientRect`, viewport de 768 px)

- **Al tocar "Guardar cambios":** `scrollY` 427 (el botón está abajo).
- **En las 20 corridas fallidas (500 / 422 / 409 × quedarse / Atrás / barra / Sectores):**
  - La pantalla sube (`scrollY` 0) y el aviso queda en `top 171 / bottom 217`, **dentro de la vista**.
  - El foco queda en el aviso (`div.aviso-en-vista`).
  - Se mantiene "1 cambio sin guardar".
  - La URL sigue siendo `/usuarios/permisos`, sin ningún diálogo abierto.
  - La base no cambia.
- **Textos que se vieron:**
  - 500: "El sistema tuvo un problema. Volvé a intentar en un momento."
  - 422: "El alcance elegido no es válido."
  - 409, con el cuerpo de `ApiExceptionRenderer`: "Alguien cambió esto mientras lo tenías abierto. Recargá y volvé a intentar."
- **Atrás mientras guarda:** la app no se va. Muestra "Estamos guardando. Cuando termine te llevamos." y, al fallar, se queda en la pantalla con el error.

## Decisiones aprobadas

| decisión | estado | dónde se probó | evidencia | nota |
|---|---|---|---|---|
| O-1.1 | ✅ | Tabla de permisos (completa) · control en Editar obra y Editar proveedor | `O-1-1-r5-21…26-ok-*.png` · `PU-306-r5-*-final.png` · `O-1-1-r5-1-obra-ok-barra-*.png`, `O-1-1-r5-2-prov-ok-atras-*.png`, `O-1-1-r5-3-obra-500-atras-*.png`, `O-1-1-r5-5-prov-500-atras-*.png` · `out-o11*.txt` | Ver abajo. |
| O-1.2 | no probado | — | — | No quedó en el camino. El diálogo de proveedor sí mostró "Estamos guardando…" con Atrás. |

### Detalle O-1.1

- **Tabla de permisos, guarda bien (200, demora de 2,5 s):** "Estamos guardando. Cuando termine te llevamos. / No cierres esta pestaña hasta que termine. / Entendido". Después llega sola a donde se eligió, 2 veces cada camino:
  - barra → `/obras`;
  - "Sectores" → `/usuarios/sectores`;
  - Atrás → `/usuarios`.
- **Tabla de permisos, falla:** se queda en la pantalla con el error a la vista y con el foco (ver PU-306).
- **Editar obra:**
  - Guarda bien y se toca la barra: "Estamos guardando…" y llega a `/obras`.
  - Falla (500) y se toca Atrás: se queda en `/obras/1/editar` con "El sistema tuvo un problema…".
- **Editar proveedor:**
  - Guarda bien y se toca Atrás: va a `/proveedores` con 0 diálogos y "Guardamos los cambios de QA5A Ferretería Sesión.".
  - Falla (500) y se toca Atrás: se queda en `/proveedores/2` con el diálogo abierto y el error adentro.
  - La barra no se puede tocar con el diálogo modal abierto: Playwright no logró hacer clic. Es lo normal de un modal, no es hallazgo.

## Regresiones cerca

- **"Salir" y entrar con otra persona:** r5a → menú del usuario → "Salir" (`POST /api/auth/salir` → 200, su clave de sesión borrada) → en la misma pestaña ingresa r5b.
  - Llega a `/obras`. El menú dice "Usuario: QA Lead R5 B" y `/api/me` devuelve r5b.
  - `sessionStorage` no tiene borradores.
  - En `/obras/1/editar`, `/proveedores/nuevo` y `/obras/nueva` no aparece ningún aviso de borrador.
  - Evidencia: `PU-305-r5-salir-r5b.png`.
- **Ingreso normal:** sin pantalla de origen, llega a Obras (`/` → `/obras`).
- **Consola durante PU-305:** solo los errores de red esperados de un 419/401. No hubo errores JS (`pageerror`). No apareció ningún texto técnico en pantalla (el filtro de prohibidos dio vacío).

## Hallazgos nuevos

Ninguno.

## No probado

- **Segunda repetición con ingreso real de los 4 casos de PU-305:** el tope de 6 ingresos alcanzó para 1 vez cada caso, más el ingreso inicial y el cambio r5a → r5b. Todos dieron lo esperado a la primera.
- **O-1.2:** no quedó en el camino.
- **Borrador descartado en Nueva obra y en el alta de proveedor:** no tienen registro previo con versión, así que no aplica. El recuperado en esas pantallas le toca al rol 5.

**Firma:** A · ronda 5 · cubrí PU-305 con ingreso real en Editar obra y Editar proveedor (conflicto + control, sin pisar datos, sin borrador que reaparezca), PU-306 en 20 combinaciones (500/422/409 × 4 caminos, la vista y el foco medidos), O-1.1 completa en la Tabla de permisos más el control en obra y proveedor, y "Salir" r5a → r5b · 6 ingresos reales.
