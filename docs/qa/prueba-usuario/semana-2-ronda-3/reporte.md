# Reporte · Prueba de usuario Semana 2 · Ronda 3 (visual, corta) · Rediseño "Obrador" · Tecnoservicios (NS-2026-025)

**Fecha:** 2026-11-24 · **Equipo:** QA NexoSmart (QA Lead, rol A en PC 1366×768, rol B en celular Pixel 7 e iPhone SE 375×667) · **Detalle:** [`verificacion.md`](verificacion.md) (PU-701…703 y la pantalla de error) y [`hallazgos.md`](hallazgos.md) (PU-801…807) · **Borradores:** [`borradores/a.md`](borradores/a.md), [`b.md`](borradores/b.md), [`lead.md`](borradores/lead.md)

## 1. Resumen ejecutivo

**Veredicto: NO APROBADA.** Hay 2 hallazgos medios. Ninguno lo trajo el rediseño, pero los dos caen en la definición de media del §4 de esta ronda:

- **PU-801 · media:** en el Calendario, la prioridad de cada pedido solo se distingue por el color. Pasa en PC y en celular, en mes y en semana. No hay palabra, ícono ni leyenda, y el texto "Urgente" solo existe para lectores de pantalla. Ya era así antes. RN-PED-47 pide "el color de su prioridad", pero no impide sumar la palabra o el ⚠ que ya usa el resto del sistema.
- **PU-802 · media:** en el celular, la tarjeta de Carga por persona no se puede tocar entera. Solo abre el nombre, que mide 42×19 px ("Carla"). Ya era así antes. Esa tabla no tiene la clase `tabla-inicio`, que es la que estira el link en las demás listas (PU-603).

**Lo demás sale bien:**
- **Arreglos de la ronda 2:**
  - **PU-701 ✅:** las fechas imposibles caen en hoy y la barra sigue visible.
  - **PU-702 ✅:** con la red lenta no se pierde lo escrito durante el envío.
  - **PU-703 ✅:** el foco queda en la lista.
- **El rediseño no rompió nada grave a la vista:**
  - ningún scroll horizontal en 37 rutas de celular ni en PC;
  - nada superpuesto que tape información;
  - textos de 120+ caracteres sin romper filas ni tarjetas;
  - estados vacío, cargando y error en todas las pantallas, con la barra visible;
  - Escape y foco correctos en las 5 ventanas;
  - el recorrido de punta a punta en el celular, con la foto del remito, anduvo.
- **Contraste:** los 30 pares de `tokens.md` medidos en la app coinciden con lo documentado y pasan AA.
- **Backend:** sano.
- **Hallazgos bajos:** 5 (PU-803…807). Tres son regresiones del rediseño (PU-803, PU-804 y PU-807).

## 2. Entorno y commits

- **Front:** `staging` @ `0cbdc4d`, en el worktree `tecnoservicios_frontend_qa_s2r3` (`npm ci`), con Vite dev en :5316.
- **Backend:** `staging` @ `d6483c9`, que es `432c725` más el prompt de esta ronda; desde `9ca659a` no hay cambios fuera de `docs/` y `tasks/`. Corre en el worktree `tecnoservicios_backend_qa_s2r3`: API :8340, workers `default` y `mail`, `schedule:work`.
- **Base:** `tecnoservicios_prueba_usuario`, reusada de la ronda 2 sin reset.
- **Ingresos reales:** 3, por la pantalla de Ingreso nueva (Dora, Sofía y Pablo, cuyas sesiones habían vencido). Los roles gastaron 0: la pantalla del código se vio simulando la respuesta.
- **Datos creados:**
  - `QAS2R3-A`: obra 5, pedidos 82–91;
  - `QAS2R3-B`: obra 6, pedidos 92–93, proveedor 2, equipo 1;
  - `QAS2R3-L`: 4 comentarios en el pedido 77.

## 3. Cobertura

| Pantalla | PC (A) | Celular (B) |
|---|---|---|
| Inicio, 4 pestañas, y sus estados | ✅ | ✅ (PU-803 en "Tareas de mi gente") |
| Pedido: detalle, recorrido y 5 ventanas | ✅ | ✅ |
| Nuevo pedido / Editar pedido | ✅ / ⚠️ PU-805 | ✅ |
| Todos los pedidos, Servicios generales | ✅ | ✅ |
| Carga por persona | ⚠️ PU-804 | ⚠️ PU-802, PU-803 |
| Calendario, mes y semana | ⚠️ PU-801 | ⚠️ PU-801 |
| Equipos en reparación | ✅ (sin filas) | ⚠️ PU-806, PU-803 |
| Obras: listado, ficha, alta, presupuestos | ✅ (PU-804 en Presupuestos) | ✅ |
| Proveedores | ✅ | ✅ |
| Usuarios, Sectores, Tabla de permisos, Plazos | ⚠️ PU-804 en Sectores | ⚠️ PU-807, PU-803 |
| Mi perfil, campana y avisos, buscador | ✅ | ✅ |
| Ingreso y código | ✅ | ✅ |
| Barra inferior y "Más" | — | ✅ (103×60 y 378×48) |
| Recorrido de punta a punta | — | ✅ |
| Controles ≥ 44×44 px | — | ✅, salvo el nombre de Carga por persona (PU-802) |
| "Al sol" (contraste 0,6) | — | ✅ Prioridad, estado y entrega se leen (la prioridad del calendario, solo por color: PU-801) |

Teclado en PC:
- foco visible en todo;
- una parada de Tab por fila en las listas (dos en "Tareas que sigo", a propósito, porque la fila tiene su botón);
- Escape cierra las ventanas y la campana.

## 4. Hallazgos nuevos

| ID | Severidad | Título | Regresión |
|---|---|---|---|
| PU-801 | **media** | En el Calendario, la prioridad de cada pedido solo se distingue por el color (PC y celular) | no |
| PU-802 | **media** | Carga por persona en el celular: la tarjeta no se puede tocar, solo el nombre (42×19 px) | no |
| PU-803 | baja | En el celular, la pestaña activa queda fuera de la vista en la tira de pestañas | rediseño 2054a31 |
| PU-804 | baja | En las tablas con nombre de fila sobra una raya en el borde de abajo, debajo de la primera columna | rediseño 2054a31 |
| PU-805 | baja | En Editar pedido, "Cancelar / Guardar" quedan pegados a la tarjeta "Historial de cambios" | probablemente no |
| PU-806 | baja | Equipos en reparación (SE): "Pendiente de aprobación" se mete 4 px en la columna Fecha | no determinado |
| PU-807 | baja | Plazos en el celular: el encabezado de la segunda columna se corta sin aviso | rediseño 2054a31 |

Todos se reprodujeron 2 veces o más. PU-801 junta A-01 y B-02. El Lead verificó en el código las causas de PU-801 y PU-802 y la regresión de PU-807.

## 5. Lead: contraste, peso y backend

- **Contraste:** se midió en la app real (`getComputedStyle` en `:root`, ratio WCAG 2.x).
  - 5 pares al azar: `texto-suave/fondo` 5,60; `senal-borde/fondo` 4,18; `prioridad-urgente` 6,54; `estado-en-curso` 6,47; `borde-fuerte/superficie` 4,83. Todos coinciden con `tokens.md`.
  - Los 30 pares de la tabla coinciden al centésimo y pasan el mínimo.
- **Peso**, build de producción:

  | | JS | CSS |
  |---|---|---|
  | antes (`c816c89`) | 228 kB gzip | 8,9 kB gzip |
  | después (`0cbdc4d`) | 235 kB gzip | 12,3 kB gzip |

  - Además baja la fuente Archivo, de la que el Ingreso pide solo el subconjunto `latin` (90 kB).
  - Ingreso con la red lenta (1,6 Mbit/s, 150 ms, sin caché): primer contenido en 2,2 s antes y 2,2 s después; red quieta en 2,3 s antes y 2,8 s después; 231 kB antes y 330 kB después.
  - El Inicio pide a la API lo mismo que antes. Sin umbral nuevo: solo queda anotado.
  - El modo dev de Vite (17 MB sin empaquetar, ~87 s con la red lenta) no representa producción.
- **Backend:** health 200 con todos los servicios en `true`, `failed_jobs` = 0 y canal `errors` vacío.

## 6. Lo que no se pudo probar y por qué

- **Pantalla de error del sistema:** no se pudo forzar. Las direcciones raras y las respuestas con forma rota caen antes en mensajes propios. No hay un camino real, y no se tocó código.
- **"Una pantalla que no baja":** no se puede forzar como dice el prompt. No hay código por sección (un solo bundle, sin `lazy()`).
- **Lector de pantalla real, cámara real del teléfono, teclado virtual y gestos reales:** no se pueden emular con fidelidad en Chromium headless. La foto se subió como un JPG generado.
- **Envíos reales de las ventanas en PC:** se abrieron, se enviaron vacías y se cerraron. Los envíos reales se cubrieron en el recorrido del celular.
- **Equipos con filas en PC:** no se cargaron. En el celular sí, con 1 equipo.
- **Tiempo del Inicio con la API real en el build de producción:** CORS solo admite :5316 y no se tocó config.
- **"Salir":** no se tocó, para no cerrar las sesiones del seeder.

## 7. Opiniones (gusto, no hallazgos)

- **Desplegable "Obra"** en Inicio, Todos y Calendario: con obras de 120+ caracteres toma todo el renglón y baja los otros filtros. Un ancho máximo lo ordenaría.
- **Títulos con textos largos:** en Editar pedido, el título ocupa 4 líneas. En las ventanas Completar y Reasignar del celular, 6 renglones. Alcanzaría con la tarea sola, o con letra más chica.
- **Todos los pedidos en el celular:** los filtros ocupan toda la primera pantalla. En el SE, el Inicio de Carla no muestra ninguna tarjeta completa sin deslizar. Un "Filtros" plegable ayudaría.
- **Calendario:** el rojo lleno de Urgente es lo más fuerte de la pantalla, más que la fecha de hoy.
- **Mi perfil:** el mail se parte en dos líneas.
- **Pedido completado:** el comprobante figura dos veces.
- **PU-702, caso "agregar al final":** si la persona escribe a continuación del texto que se está enviando, al terminar el primer texto sigue en la caja junto con lo nuevo. No se pierde nada, que es lo que pedía PU-702, pero si se vuelve a tocar "Comentar" el primero sale repetido.
- **Escape dentro de "Pegar link"** cierra toda la ventana Completar, no solo el pegado.
- **Aviso de Vite "chunks > 500 kB":** ya estaba antes del rediseño.

## 8. Veredicto (§7)

**NO APROBADA:** 2 medias (PU-801 y PU-802), ninguna bloqueante ni alta. Las 5 bajas quedan como pendientes menores.

Para una ronda 4 corta alcanza con 3 verificaciones:
- el Calendario con la prioridad también en palabra o ícono, en PC y en celular;
- la tarjeta de Carga por persona tocable entera en el celular;
- de paso, las bajas que se arreglen.

## 9. Firmas

- QA Lead (consolidación, PU-701/702, contraste, peso, backend), 2026-11-24.
- Rol A (PC), 2026-11-24.
- Rol B (celular), 2026-11-24.
