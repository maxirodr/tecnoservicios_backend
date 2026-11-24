# Prueba de usuario Semana 2 · Ronda 3 (visual, corta) · Rediseño "Obrador"

> Pegá este archivo como primer mensaje de una sesión nueva de Claude Code, o decile:
> "Leé y ejecutá `C:\NexoSmart\Projects\tecnoservicios_project\tecnoservicios_backend\tasks\prueba-usuario-semana-2-ronda-3-visual.md`".
> Rutas relativas a `…\NexoSmart\Projects\tecnoservicios_project\` (`C:\` o `B:\ProgramFiles\`, según la máquina).

## 0. Qué es esta ronda

- La Semana 2 ya está **aprobada** (ronda 2). Desde entonces, `staging` sumó dos cosas:
  - el **rediseño visual** del front, que cambia la cara de **todas** las pantallas pero no lo que hacen;
  - los arreglos de los bajos PU-701…703.
- Esta ronda es **corta y acotada: una tarde**. Lo único que se busca es que el rediseño no haya roto nada a la vista
  y que el cliente pueda verlo.
- **No se repite la prueba funcional.**
- Para el entorno, las personas y las reglas, se reusa el prompt `tasks/prompt-prueba-usuario-semana-2.md`:
  - **§1 a §6** (entorno, variables, personas, datos ficticios, cómo reportar, severidades);
  - **§7**, la regla del veredicto: aprobada si no hay bloqueante, alta ni media.
- **Ignorá §0 y §2** de ese prompt: los reemplaza este archivo.
- **Dónde va todo:**
  - rama `qa/prueba-usuario-semana-2-ronda-3` desde `origin/staging`;
  - carpeta `docs/qa/prueba-usuario/semana-2-ronda-3/`;
  - hallazgos desde **PU-801**.
- **Trabajá en worktrees propios** (`…_qa_s2r3`). No toques las carpetas principales: otra sesión sigue
  desarrollando.

## 1. Código a probar

- **Front:** `staging` @ `0cbdc4d`.
  - Rediseño mergeado en `2054a31`.
  - Arreglos de PU-701…703 en `0e011f5`.
  - Pantalla de error en `8e94df1` y `0cbdc4d`.
  - Hay dependencias nuevas (`@fontsource-variable/archivo`, `lucide-react`): `npm ci`.
- **Backend:** `staging` @ `432c725`.
  - Sin cambios de código desde la ronda 2 (solo docs).
  - La base `tecnoservicios_prueba_usuario` se puede reusar, con datos de la ronda 2, o resetearla según el §3 del
    prompt.
- **Referencia del antes y el después:** en el front, `docs/ui/capturas/antes/` y `docs/ui/capturas/despues/`, 67
  pares con el mismo nombre. La dirección elegida y los tokens están en `docs/ui/direccion-visual.md` y
  `docs/ui/tokens.md`.

## 2. Qué mirar (dos roles en paralelo, más el Lead)

**Rol A · PC 1366×768.** Pasá por todas las pantallas:
- Inicio, con sus 4 pestañas;
- el pedido: detalle, recorrido y las 5 ventanas de acción;
- Nuevo y Editar pedido;
- Todos los pedidos, Servicios generales, Carga por persona, Calendario y Equipos;
- Obras: listado, ficha, alta y presupuestos;
- Proveedores, Usuarios, Tabla de permisos, Sectores, Plazos, Mi perfil, campana y avisos;
- Ingreso.

En cada una:
1. **Se lee y se entiende:**
   - qué es lo importante;
   - qué está vencido o urgente, y que se note también sin el color (texto o ícono);
   - dónde toco.
2. **Nada roto:**
   - nada superpuesto, cortado ni desbordado;
   - textos largos (tarea y obra de 120 caracteres o más) que no rompan la fila;
   - tablas con muchas filas.
3. **Estados:** vacío, cargando y error. Para el error de una pantalla, ver §3.
4. **Teclado:**
   - foco visible en todo;
   - una parada de Tab por fila;
   - Escape cierra las ventanas;
   - después de Tomar o dejar de seguir, el foco queda en la lista (PU-703).

**Rol B · Celular, Pixel 7 e iPhone SE 375×667.** Las mismas pantallas que A, más:
- **Sin scroll horizontal** en ninguna pantalla.
- **Controles de al menos 44×44 px:** medilos, no a ojo.
- **Tarjetas tocables enteras:** la fila o la tarjeta abre el pedido o la obra, y "Tomar" no lo abre.
- **Al sol:** subí el brillo al máximo o simulá con contraste reducido. Que se lean la prioridad, el estado y la
  entrega.
- **Barra inferior y "Más"** con todos sus ítems.
- **Recorrido de punta a punta:** crear un pedido y completarlo con la foto del remito.

**Lead:**
- **PU-701:**
  - `/calendario?vista=semana&desde=2026-11-99`, `…=9999-12-31` y `/calendario?desde=0000-00-00` caen en la semana
    o el mes de hoy, con la barra visible;
  - una fecha válida en la dirección se respeta.
- **PU-702:** con la red lenta, lo que se escribe mientras se envía el comentario anterior se queda en la caja.
- **PU-703:** lo cubre el rol A.
- **Contraste:** medí 5 pares al azar de la tabla de `docs/ui/tokens.md` (texto ≥ 4,5:1; bordes de controles ≥ 3:1).
- **Peso:** el front sigue cargando rápido con la red lenta, el ingreso y el Inicio. Tomá nota del tiempo, sin
  umbral nuevo.
- **Backend sano:** health, `failed_jobs` y el canal `errors`.

## 3. Pantalla de error nueva (cómo forzarla)

- **Un error del sistema al dibujar la pantalla:**
  - **Qué debe mostrar:**
    - "Esta pantalla no se pudo mostrar" / "No es tu conexión: es un error del sistema. Volvé al inicio y probá
      de nuevo. Si sigue pasando, avisá a sistemas.";
    - "Volver al inicio";
    - el ícono de alerta (no la nube);
    - **la barra visible**.
  - **Cómo forzarlo:** no hay un camino real (PU-701 ya no lo provoca). Si no encontrás uno, anotalo como "no se pudo
    forzar". No toques código.
- **Una pantalla que no baja:**
  - **Cómo forzarla:** DevTools → Network → bloquear un `assets/*.js` de una sección y entrar a esa sección.
  - **Qué debe mostrar:** el mensaje de conexión de siempre, con "Reintentar".

## 4. Reportes y veredicto

- **Los mismos tres archivos de siempre:**
  - `reporte.md`;
  - `verificacion.md`, con PU-701…703 y la pantalla de error;
  - `hallazgos.md`, desde PU-801.
- **Capturas** en `capturas/`.
- **Severidad para lo visual:**
  - **media:** algo ilegible, tapado o que no se puede tocar en el celular, o información que solo se distingue por
    el color;
  - **baja:** un desalineado o un espaciado raro.
- **Gusto:** "me gustaría otro color" no es un hallazgo. Va en una sección "Opiniones" del reporte.
- **Veredicto:** con la regla de siempre.
- **Al terminar:** pusheá la rama y pasale a Juani la lista de archivos.
