# Prueba de usuario Semana 2 · Ronda 2 · Qué cambió desde la ronda 1

> Para el QA Lead: entrada de §0 y §2 del prompt `tasks/prompt-prueba-usuario-semana-2.md` (se reusa). Reportes de
> la ronda 1: `docs/qa/prueba-usuario/semana-2/` (ya en `staging`). Veredicto de la ronda 1: **NO APROBADA** por
> PU-601 y PU-602 (medios). Por la regla del §7, esta es una **ronda corta**: verificá los 16 hallazgos con sus
> pasos exactos y hacé una pasada de regresión alrededor de lo que tocaron los arreglos. Carpeta
> `docs/qa/prueba-usuario/semana-2-ronda-2/`, rama `qa/prueba-usuario-semana-2-ronda-2` (desde `staging`), hallazgos
> desde **PU-701**.

## Código a probar (`staging`, 2026-11-20)

- **Backend `staging` @ `fb55537`:** arreglos de PU-606, PU-607 y PU-612 (Excel). Sin migraciones nuevas.
- **Front `staging` @ `c816c89`:** arreglos de PU-601…PU-616.
- Después del pull: `composer install` (ya estaba la librería del Excel) y `config:clear`.

## Qué se arregló y cómo verificarlo

| PU | Sev. | Qué cambió | Cómo verificarlo |
|---|---|---|---|
| PU-601 | media | El campo Obra ya no se pierde cuando llega la lista de obras: queda la obra de la ficha. | Pasos de OBR-02: "+ Nuevo pedido en esta obra" con una obra que esté entre las primeras 20, mirar el campo a 0,3 s, 1,5 s y 5 s, PC y celular. Elegir otra obra y volver a la primera también tiene que andar. |
| PU-602 | media | Sin gente a cargo, Carga por persona dice "Esta sección es para quien tiene gente a cargo." con "Ir a Mis tareas", sin filtro ni leyenda y sin pedir permiso a gerencia. | Laura y Pablo por la dirección `/pedidos/carga-por-persona`. Hernán y Dora (Dirección) siguen viendo la carga. |
| PU-603 | baja | Tocar la fila (PC) o la tarjeta (celular) abre el pedido en Mis tareas, Pedidos que pedí, Tareas de mi gente, Tareas que sigo, Todos los pedidos y Servicios generales. Un solo link por fila; "Tomar" y "dejar de seguir" siguen siendo botones aparte. En el celular, también la obra y el "cuándo" de los avisos. | Pasos de JEF-02 y CEL-01, 5 puntos por tarjeta; tocar "Tomar" no abre el pedido. Con teclado, el foco se marca en la fila. |
| PU-604 | baja | La campana pide el contador cada 10 s (antes 15): de punta a punta entra en los 15 s. | 9 corridas como AVI: ninguna por encima de 15 s. |
| PU-605 | baja | El aviso no repite la obra si el texto ya la trae (panel de la campana y lista del celular). La tabla de PC conserva la columna Obra. | Panel W-02 y `/avisos` en el celular. |
| PU-606 | baja | "Vence mañana una tarea mía" dice "Solo en el sistema", sin casilla. | Mi perfil · Mis avisos con Laura. |
| PU-607 | baja | El recorrido dice "Sistema avisó a Hernán (completado, a su jefe)" en lugar de "(completado gente)". Igual con los demás tipos ("comentario nuevo", etc.). | Pedidos 3 y 10 de la ronda 1, o uno nuevo completado. |
| PU-608 | baja | El recorrido ya no repite el nombre: "fecha · texto". | Detalle de cualquier pedido con historia. |
| PU-609 | baja | En Bloquear, Completar y Reabrir, el "Contá…" en rojo se va apenas se escribe. | Los tres diálogos. |
| PU-610 | baja | Con la entrega antes de la fecha de pedido: "Urgente · la entrega es 1 día antes del pedido." en el formulario y "(1 día antes del pedido)" en el detalle; nunca "-1 días". | Pedido 21 de la ronda 1 o uno nuevo. |
| PU-611 | baja | "Pedido creado. Se le avisó a …" sale una sola vez: no vuelve con F5 ni con Atrás. | Crear, F5, Atrás y Adelante. |
| PU-612 | baja | "(inactivo)" en el Excel (columna Propietario) y en el calendario "Mi gente". | Exportar con el inactivo con pendientes de JEF; calendario Mi gente con Hernán. |
| PU-613 | baja | El calendario guarda vista, semana, Mis tareas / Mi gente y obra en la dirección: vuelve igual después de abrir un pedido. | Semana · Mi gente · una obra → abrir un pedido → Atrás. |
| PU-614 | baja | Sin obras En ejecución, la fila "Servicios generales" aparece igual, con el aviso de que no hay obras. | Pestaña En ejecución vacía con pedidos sin obra abiertos. |
| PU-615 | baja | Un texto más largo que el máximo muestra "… puede tener hasta N caracteres." y no se manda (antes se cortaba sin avisar). También en el comentario opcional de Reasignar. | Comentario, motivo, "¿Qué falta?" y Reasignar con 2001 caracteres. |
| PU-616 | baja | Si completo un pedido que me pedí a mí: el botón dice "Completar", sin "Le avisamos a…", y el resultado es "Pedido completado." | Un pedido de Laura a Laura. |

**Además, antes de la ronda 1** (por si se ve distinto): los presupuestos de la obra van arriba de las pestañas, y
Dirección tiene `tiene_gente` (ve Tareas de mi gente y Carga por persona).

**Verificación de desarrollo:**
- Back: 934/934, pint y phpstan en verde.
- Front: typecheck, lint, formato y build en verde; vitest 591/591; e2e 91/91.
- Cada test nuevo falla sin su arreglo.
- Integración real (`e2e:integracion`, front contra backend): 53/53 en una sola corrida.

## Regresiones a mirar

- Las listas con la fila entera tocable: que los botones de la fila (Tomar, dejar de seguir) y los filtros sigan
  andando, y que no haya dos paradas de Tab por fila.
- El formulario de pedido: el campo Obra en alta normal, en alta desde la obra y en Editar.
- Los diálogos de acción con textos largos y vacíos, con red lenta.
- Calendario con la dirección cambiada a mano (valores inválidos).
