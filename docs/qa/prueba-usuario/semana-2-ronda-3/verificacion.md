# Verificación · Prueba de usuario Semana 2 · Ronda 3 (visual) · 2026-11-24

Front `0cbdc4d`, backend `432c725` (`d6483c9` = `432c725` + el prompt de esta ronda). Base de la ronda 2, reusada.

| ID | Título | Estado | Pantallas | Evidencia | Nota |
|---|---|---|---|---|---|
| PU-701 | El calendario con una fecha imposible o fuera de rango en la dirección | ✅ | PC (Laura) | `capturas/PU-701-r3-*.png` · `borradores/lead.md` | 2 vueltas × 7 direcciones. `vista=semana&desde=2026-11-99`, `…=9999-12-31` y `…=1899-12-31` → "Semana del 23 al 29-nov" (la semana de hoy). `desde=0000-00-00` y `desde=9999-12-31` → "Noviembre 2026". Barra visible en todas, sin "Revisá tu conexión", sin 4xx/5xx de la API y sin errores de consola. Las fechas válidas se respetan: `2026-12-09` → "Semana del 7 al 13-dic"; `2027-02-15` → "Febrero 2027". |
| PU-702 | Lo escrito mientras se envía el comentario anterior | ✅ | PC (Laura), red lenta | `capturas/PU-702-r3-1.png`, `PU-702-r3-reemplazo.png` | 4 vueltas con 3,5 s en cada POST. **Reemplazando** el texto durante "Enviando…": la caja queda con lo nuevo (2 de 2). **Agregando** al final: la caja queda con "primero + segundo"; no se pierde nada (2 de 2). Un comentario por envío en la base. Ver Opiniones del reporte. |
| PU-703 | Foco después de "Tomar" o "dejar de seguir" con el teclado | ✅ | PC (Carla en Mis tareas, Hernán en Tareas que sigo) | `capturas/pu703-tomar-{1,2}-pc-r3.png`, `pu703-dejar-{1,2}-pc-r3.png` · `borradores/a.md` | 2 repeticiones de cada caso. Después de "Tomar", el foco queda en el link de la misma fila (ya En curso), con contorno visible. Después de "dejar de seguir", la fila se va y el foco pasa a la fila vecina. Nunca queda en la página. |
| §3 | Pantalla de error del sistema ("Esta pantalla no se pudo mostrar") | no se pudo forzar | PC | `capturas/error-sistema-intento-pc-r3.png` · `borradores/a.md` | Se probaron direcciones raras y una respuesta con forma rota. Todo cae antes en mensajes propios: 404 "Pedido no encontrado", "El sistema respondió algo inesperado…" o un 422. No hay un camino real, como avisaba el prompt. Sí hay test unitario (`RouteError.test.tsx`); no se tocó código. |
| §3 | Una pantalla que no baja (mensaje de conexión con "Reintentar") | no se pudo forzar | PC | `capturas/no-baja-intento-pc-r3.png` · `borradores/lead.md` | El front no tiene código por sección: ningún `lazy()` ni `import()` en `src/`, y el build de producción es un solo `index-*.js`. No existe un `assets/*.js` de una sección para bloquear. Bloquear un módulo en el modo dev deja la página entera en blanco, porque ese módulo es parte del arranque; no es el caso del §3. |

**Regresión general del rediseño:**
- **Sin scroll horizontal** en ninguna pantalla: 37 rutas en Pixel 7 y en SE, y todas las de PC.
- **Consola y red limpias.**
- **Recorrido de punta a punta en el celular:** anduvo (obra → 2 pedidos → completar con la foto del remito).
- **Escape** cierra las 5 ventanas y el foco vuelve al botón que las abrió.
- **Contraste:** los 30 pares de `tokens.md` coinciden con lo medido en la app.
- **Regresiones del rediseño:** PU-803, PU-804 y PU-807, las tres bajas.
