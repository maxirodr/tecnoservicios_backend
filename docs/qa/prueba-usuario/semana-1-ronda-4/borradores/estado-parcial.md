# Ronda 4 · Estado parcial (interrumpida el 2026-11-18 ~15:20 a pedido del usuario)

La ronda se cortó a mitad de la tanda 2. Lo pendiente y cómo retomarlo está en `staging`:
`tasks/prueba-usuario-semana-1-ronda-4-pendiente.md`.

## Borradores completos
- `a.md` · Verificador A (conflictos, guardado y salida): terminado.
- `b.md` · Verificador B (textos, datos, links y fechas): terminado.
- `bk.md` · QA backend parte 1 (sin cortes): terminado. Falta la parte 2 (cortes).

## Trabajo parcial sin borrador (agentes cortados)
**QA celular y accesibilidad (CEL)** — scripts y salidas en `scripts/CEL/`, capturas `capturas/PU-041-r4-*`, `PU-210-r4-*`, `PU-212-r4-*`. Lo que alcanzó a medir, **sin conclusión firmada**:
- PU-212: con teclado, "Pegar link", "Cancelar" del link y "Agregar link" (con y sin red lenta) dejan el foco en el campo o en el link agregado, **dentro del diálogo** de documento y en Nueva obra (`out-foco-doc-*.txt`, `out-foco-obra-*.txt`). "Subir archivo" quedó sin salida (`out-foco-doc-archivo-1.txt` vacío).
- O-1.2 en Pixel 7 y iPhone SE: "Cancelar" y tocar afuera muestran "Estamos guardando. Esperá unos segundos." en los diálogos de documento y proveedor, en pantalla y sin recortar (`out-cel-guardando-*.txt`).
- Controles ≥ 44 px: en dos de las pantallas medidas quedaban 3 y 7 controles chicos (`out-cel-p7-1.txt`, `out-cel-se-1.txt`); falta identificar cuáles y en qué pantalla (ver `cel.mjs`). Sin scroll horizontal de página.
- A confirmar: en iPhone SE el diálogo de documento crece mientras guarda y queda más alto que la pantalla (arriba en -149 px con el aviso; `out-sonda-dlg-se-2.txt`).

**Cazador de regresiones (REG)** — scripts en `scripts/REG/` (01…08), capturas `capturas/REG-ok-*.png` (alta y edición de obra y de proveedor, historial, adicional, documento, presupuesto por rubro). Hasta donde llegó no anotó ningún problema, pero **no hay conclusiones firmadas** y faltan: estado, Plazos, Mi perfil, doble clic, buscador general, permisos por sector y la sesión vencida con vuelta por ingreso real.

## Otros archivos
- `scripts/BK/plan-cortes.md` + `relanzar-workers.ps1`, `api-con-sesion.mjs`, `cortes-app.mjs`: plan listo para la parte 2 del backend (rutas de esta máquina: adaptarlas).
- `scripts/pre-migracion/`: evidencia de PU-207 con links cargados antes de la migración (`NOTA-PU-207.md`).
- `scripts/BRIEF.md`, `ingresar.mjs`, `premig.mjs`, `chequear-sesiones.mjs`: brief de los roles y helpers del Lead.
