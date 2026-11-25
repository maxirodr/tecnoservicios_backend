# Prueba de usuario Semana 2 · Ronda 4 (corta) · Qué cambió desde la ronda 3 (visual)

> Pegá este archivo como primer mensaje de una sesión nueva de Claude Code, o decile:
> "Leé y ejecutá `C:\NexoSmart\Projects\tecnoservicios_project\tecnoservicios_backend\tasks\prueba-usuario-semana-2-ronda-4-novedades.md`".
> Rutas relativas a `…\NexoSmart\Projects\tecnoservicios_project\` (`C:\` o `B:\ProgramFiles\`, según la máquina).

## 0. Qué es esta ronda

- La ronda 3 (visual, `docs/qa/prueba-usuario/semana-2-ronda-3/`) quedó **NO APROBADA** por **PU-801** (prioridad solo
  por color en el Calendario) y **PU-802** (Carga por persona: solo se toca el nombre en el celular), los dos medios.
  También quedaron 5 bajos, PU-803…807.
- Desarrollo arregló los 7. Esta es una **ronda corta**:
  - verificá los 7 con sus pasos exactos, en los mismos dispositivos;
  - hacé una pasada de regresión alrededor de lo que tocaron los arreglos (§3).
- **No se repite la ronda visual completa.**
- **Reusá el prompt `tasks/prueba-usuario-semana-2-ronda-3-visual.md`:**
  - **§0** dónde trabajar y **§1** el entorno: worktrees propios, `npm ci`;
  - **§4** severidades visuales y veredicto.

  Cambian el código (§1 de este archivo), la carpeta, la rama y la numeración:
  - rama `qa/prueba-usuario-semana-2-ronda-4` desde `origin/staging`;
  - carpeta `docs/qa/prueba-usuario/semana-2-ronda-4/`;
  - hallazgos desde **PU-901**.
- **Trabajá en worktrees propios** (`…_qa_s2r4`). No toques las carpetas principales ni las de otros agentes
  (`*_gastos`, `*_rendicion`, `*_fix`): otra sesión sigue desarrollando la Semana 3 en `main`.
- **No mires `main`:** trae la Semana 3 a medio encender. Se prueba `staging`.

## 1. Código a probar

- **Front:** `staging` @ `a3fb83e`, con los arreglos de PU-801…807 en 9 commits desde `0cbdc4d`. No hay dependencias
  nuevas.
- **Backend:** `staging` @ `489df9c`. Sin cambios de código desde la ronda 3: son solo los reportes. La base
  `tecnoservicios_prueba_usuario` se reusa.

## 2. Qué se arregló y cómo verificarlo

| PU | Sev. | Qué cambió | Cómo verificarlo |
|---|---|---|---|
| PU-801 | media | En el Calendario, Urgente lleva el ⚠ (el mismo de la insignia de prioridad) y Prioritario un ícono de doble flecha arriba; Normal sin ícono. En la lista del celular además va la palabra ("Urgente ·", "Prioritario ·") delante de la tarea. | Pasos de A-01 y B-02 (mes y semana, PC, Pixel 7, SE y al sol). Que se distinga sin color: probá en escala de grises (DevTools → Rendering → "Emulate vision deficiencies" → Achromatopsia). Lector de pantalla: sigue leyendo "… · Urgente". Los completados mantienen el ✓. |
| PU-802 | media | La fila o tarjeta de Carga por persona es tocable entera, con un solo link por fila y el foco en la fila. | Pasos de B-01: 2 tarjetas × 2 puntos fuera del nombre en Pixel 7 y SE; con teclado, una parada de Tab por fila. |
| PU-803 | baja | En las tiras de pestañas deslizables, la activa queda a la vista al entrar y al cambiar, sin animación con movimiento reducido. | Las 6 pantallas de B-03 en Pixel 7 y SE. También con `prefers-reduced-motion`. |
| PU-804 | baja | Ya no sobra el borde bajo el nombre de fila en la última fila. | Las 3 tablas de A-02 (zoom). |
| PU-805 | baja | Editar pedido: hay aire entre el pie del formulario y "Historial de cambios". | Pedidos 82, 18 y 91. |
| PU-806 | baja | Equipos en reparación en el SE: "Pendiente de aprobación" se parte en renglones dentro de su columna. | B-04 en SE y en Pixel 7. |
| PU-807 | baja | Plazos en el celular: el encabezado largo se parte en renglones y se lee entero, sin scroll horizontal. | B-05 en Pixel 7 y SE. |

## 3. Regresiones a mirar

- **Calendario:**
  - celdas chicas del mes con varios pedidos y textos largos: el ícono no rompe el renglón ni tapa la tarea;
  - barras de período (inicio y fin);
  - la lista del celular con fechas cambiadas a mano (PU-701 sigue bien).
- **Las demás listas con la fila tocable:** Mis tareas, Tareas que sigo, Todos los pedidos y Tareas de mi gente.
  - "Tomar" y "dejar de seguir" no abren el pedido;
  - una parada de Tab por fila;
  - el foco después de la acción (PU-703).
- **Todas las tiras de pestañas:** Inicio, Pedidos, Obras, Usuarios y la ficha de la obra.
  - En PC no se mueven solas.
  - En el celular, al tocar una pestaña la página no salta.
- **Tablas con nombre de fila:** el borde del encabezado y de las filas del medio sigue igual.
- **Backend sano:** health, `failed_jobs` y el canal `errors`.

## 4. Reportes y veredicto

- **Los tres archivos de siempre:**
  - `reporte.md`;
  - `verificacion.md`, con el estado de PU-801…807;
  - `hallazgos.md`, lo nuevo desde PU-901.
- **Capturas** en `capturas/`.
- **Veredicto:** la regla de siempre: aprobada si no hay bloqueante, alta ni media.
- **Al terminar:** pusheá la rama y pasale a Juani la lista de archivos.
