# Reporte · Prueba de usuario Semana 2 · Ronda 4 (corta) · Tecnoservicios (NS-2026-025)

**Fecha:** 2026-11-25 · **Equipo:** QA NexoSmart (QA Lead, rol A en PC 1366×768, rol B en celular Pixel 7 e iPhone SE 375×667) · **Detalle:** [`verificacion.md`](verificacion.md) (PU-801…807) y [`hallazgos.md`](hallazgos.md) (PU-901…903) · **Borradores:** [`borradores/a.md`](borradores/a.md), [`b.md`](borradores/b.md)

## 1. Resumen ejecutivo

**Veredicto: APROBADA.** No hay hallazgos bloqueantes, altos ni medios. Quedan 3 bajos como pendientes menores.

- **Los 2 medios que frenaron la ronda 3 quedaron resueltos:**
  - **PU-801 ✅:** en el Calendario, en PC y en celular, la prioridad se distingue sin color. Urgente lleva ⚠, Prioritario la doble flecha, y en la lista del celular además la palabra. Se probó también en escala de grises y "al sol".
  - **PU-802 ✅:** la tarjeta de Carga por persona se toca entera: 16 de 16 toques fuera del nombre, y una parada de Tab por fila.
- **Bajos de la ronda 3:**
  - PU-804, PU-805, PU-806 y PU-807: ✅.
  - **PU-803: ⚠️.** Anda en 4 de sus 6 pantallas, pero en Tareas de mi gente y Obras · Terminadas la pestaña activa vuelve a quedar cortada cuando llegan los contadores (PU-902).
- **Hallazgos nuevos (3, bajos):**
  - **PU-901:** en PC, la ficha de la obra baja sola unos segundos después de entrar y corta el título. Regresión del arreglo de PU-803 (`07966f5`).
  - **PU-902:** arreglo incompleto de PU-803 (el de arriba).
  - **PU-903:** en el celular, el Inicio sube arriba de todo al tocar una pestaña que todavía no cargó. Probablemente ya estaba.
- **Regresión alrededor de los arreglos:**
  - Calendario: celdas con más de 20 pedidos y textos de 150 caracteres, barras de período y fechas escritas a mano (PU-701).
  - Listas con fila tocable: "Tomar" y "dejar de seguir" no abren el pedido, y el foco queda en la lista (PU-703).
  - Bordes de las tablas, sin scroll horizontal y backend sano.
  - Todo ✅, salvo las tiras de pestañas (PU-901 y PU-903).

## 2. Entorno y commits

- **Front:** `staging` @ `21dfe1a`, en el worktree `tecnoservicios_frontend_qa_s2r4` (`npm ci`), con Vite dev en :5316.
- **Backend:** `staging` @ `3c69efc`, que es `489df9c` más docs; desde `432c725` no hay cambios fuera de `docs/` y `tasks/`. Corre en el worktree `tecnoservicios_backend_qa_s2r4`: API :8340, workers `default` y `mail`, `schedule:work`.
- **Base:** `tecnoservicios_prueba_usuario`, la de las rondas 2 y 3, sin reset.
- **Ingresos reales:** 4. Redis se había reiniciado y las sesiones se perdieron, así que volví a ingresar con Diego, Hernán, Laura y Carla. Los roles gastaron 0.
- **Datos creados:** `QAS2R4-A`, pedidos 94–106 y 109–112; `QAS2R4-B`, pedidos 107 y 108, con período.
- La API anduvo lenta (otra sesión corría tests en la misma máquina). Los roles midieron siempre después de que terminara la carga.

## 3. Verificación de PU-801…807

✅ 6 · ⚠️ 1 · ❌ 0. Ver [`verificacion.md`](verificacion.md).

## 4. Regresión (§3 de las novedades)

| Punto | PC (A) | Celular (B) |
|---|---|---|
| Calendario: celdas con varios pedidos y textos largos | ✅ (más de 20 pedidos por día, 142–158 caracteres) | ✅ |
| Calendario: barras de período (inicio, fin y cruce de mes) | ✅ | ✅ |
| Calendario: fechas escritas a mano (PU-701) | — | ✅ |
| Filas tocables (Mis tareas, Tareas que sigo, Todos los pedidos, Tareas de mi gente): la fila abre el pedido y "Tomar" / "dejar de seguir" no lo abren | ✅ | ✅ |
| Una parada de Tab por fila, y el foco después de la acción (PU-703) | ✅ | — |
| Tiras de pestañas: en PC no se mueven solas | ⚠️ PU-901 (ficha de obra) | — |
| Tiras de pestañas: en el celular la página no salta al tocar | — | ⚠️ PU-903 (Inicio) |
| Tablas con nombre de fila: encabezado y filas del medio | ✅ | — |
| Sin scroll horizontal | ✅ | ✅ |
| Backend: health, `failed_jobs`, canal `errors` | ✅ (Lead, 12:07: health 200, 0, vacío) | |

## 5. Hallazgos nuevos

| ID | Severidad | Título | Regresión |
|---|---|---|---|
| PU-901 | baja | En la ficha de la obra, en PC, la página baja sola unos segundos después de entrar y corta el título | PU-803 (`07966f5`) |
| PU-902 | baja | Tareas de mi gente y Obras · Terminadas: la pestaña activa vuelve a quedar cortada cuando llegan los contadores | arreglo incompleto de PU-803 |
| PU-903 | baja | Inicio en el celular: al tocar una pestaña que no cargó todavía, la página sube arriba de todo | probablemente no |

- **PU-901 y PU-902** salen del mismo hook, `src/shared/ui/useActivaALaVista.ts`, que confirmé en el código:
  - el `scrollIntoView({ block: 'nearest' })` mueve también la página en vertical cuando la tira queda debajo del borde de la pantalla;
  - solo se vuelve a acomodar si cambia la activa o la cantidad de pestañas, no su ancho.
- **Arreglo posible:** desplazar solo la tira, con `scrollLeft` del contenedor, en vez de `scrollIntoView`. Así se cerrarían los dos.

## 6. Lo que no se pudo probar y por qué

- **Lector de pantalla real** (NVDA, TalkBack o VoiceOver): solo se verificó el texto `sr-only` en el DOM.
- **Zoom del navegador al 125–150 % en PC:** no se midió. Con PU-901, el salto probablemente sea mayor.
- **"Tomar" y "dejar de seguir" de verdad en el celular:** se cortaron, porque eran pedidos de otro prefijo. En PC sí se hicieron de verdad.
- **Movimiento reducido del sistema operativo real:** se emuló con `emulateMedia`.
- **"Período" en el formulario del celular:** el formulario compacto no lo trae, así que los pedidos con período se cargaron desde la PC. No es hallazgo de esta ronda.

## 7. Opiniones (gusto, no hallazgos)

- **Calendario en escala de grises:** la doble flecha de Prioritario es fina, y Prioritario y Normal quedan en la misma caja clara. En días con muchos prioritarios, el normal no salta a la vista.
- **Calendario: pedidos con período.** Aparecen dos veces: como barra y como entrega el día de fin.
- **Calendario: vencidos.** Se ven igual que los urgentes de mañana.
- **Carga por persona:** la tarjeta de alguien con 0 abiertas lleva a una lista vacía.
- **Filtro "Propietario":** con el servidor lento, muestra "todos" por 2–3 s antes de pasar a la persona.
- **Título del Calendario en el SE:** "Semana del 23 al 29-nov" parte en "29-" / "nov".

## 8. Veredicto (§7)

**APROBADA.** Con esto, la Semana 2 queda aprobada también en lo visual.

Los pendientes menores son PU-901, PU-902 y PU-903, los tres bajos. PU-901 y PU-902 se resuelven juntos en el hook de las tiras.

## 9. Firmas

- QA Lead (entorno, backend, consolidación y verificación en el código de PU-901…903), 2026-11-25.
- Rol A (PC), 2026-11-25.
- Rol B (celular), 2026-11-25.
