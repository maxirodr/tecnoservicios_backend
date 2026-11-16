# Borrador PRV — Tester funcional de Compras (Carla)

> Rol: Tester funcional de Compras ("PRV"), como **Carla (Compras)**; contraste con Laura y Pablo
> (Ingeniería de proyectos), Hernán (Jefatura de Proyectos) y Diego (Gerencia de Administración).
> Fecha: 2026-11-16. Entorno: front `http://localhost:5316`, API `http://localhost:8340`, base
> `tecnoservicios_prueba_usuario`. Sesiones guardadas por el Lead (regeneradas 16:05 UTC después del
> corte de Redis, que documenta el Lead), sin pedir códigos.
> Datos creados: "QA-PRV Prueba CUIT" (id 2, CUIT 30-71234567-1) y "QA-PRV Corralón Ñandú S.R.L."
> (id 3, CUIT 30-71234569-8, MATERIALES). Scripts: `scratchpad\qa\prv\p1.cjs … p13.cjs`.
> MySQL estaba cargado por otra suite: búsquedas de 1,5 a 3,5 s; no lo tomo como hallazgo.

### PRV-01 · Doble punto en los mensajes cuando el nombre del proveedor termina en punto
- **Severidad:** baja
- **Tipo:** mensaje
- **F-XX / pantalla:** F-56 · W-38 Alta y edición de proveedor · `/proveedores`
- **Persona:** Carla (Compras)
- **Pasos:** 1. "+ Nuevo proveedor". 2. Nombre "QA-PRV Corralón Ñandú S.R.L." (como lo escribe cualquiera con la forma societaria). 3. Guardar proveedor. 4. Intentar dar de alta otra vez "qa-prv CORRALON ÑANDU srl".
- **Esperado:** "Dimos de alta a QA-PRV Corralón Ñandú S.R.L." con un solo punto.
- **Obtenido:** "Dimos de alta a QA-PRV Corralón Ñandú S.R.L.." y, en el repetido, "Ver la ficha de QA-PRV Corralón Ñandú S.R.L..". La API también responde `message: "Guardamos QA-PRV Corralón Ñandú S.R.L.."`. Reproducido 3 veces.
- **Evidencia:** `capturas/F-56-alta-proveedor-guardado-pc.png` · `capturas/PRV-6-nombre-repetido.png`
- **Causa probable (opcional):** `ProveedoresPantalla.tsx` (`` `Dimos de alta a ${proveedor.nombre}.` `` y "Guardamos los cambios de …"), `ProveedorDialog.tsx` ("Ver la ficha de {nombre}."), y el `message` del store en el backend: agregan el punto sin mirar si el nombre ya termina en punto. Pasa con cualquier "S.A.", "S.R.L.", "Cía.".

### PRV-02 · "Ver la ficha de …" queda colgado después de corregir el nombre
- **Severidad:** baja
- **Tipo:** mensaje
- **F-XX / pantalla:** F-56 · W-38 Nuevo proveedor · `/proveedores`
- **Persona:** Carla (Compras)
- **Pasos:** 1. "+ Nuevo proveedor". 2. Nombre igual a uno existente ("qa-prv CORRALON ÑANDU srl") → Guardar. 3. Sale "Ya existe QA-PRV Corralón Ñandú S.R.L." y debajo "Ver la ficha de …". 4. Cambiar el nombre por otro ("QA-PRV Distinto Total").
- **Esperado:** al corregir el nombre se va el error y también el link que lo acompañaba (o queda solo hasta el próximo intento de guardar).
- **Obtenido:** el error "Ya existe …" desaparece pero "Ver la ficha de QA-PRV Corralón Ñandú S.R.L.." sigue debajo del nombre, suelto, sin decir por qué; parece que el nombre nuevo también choca. Reproducido 2 veces (con el nombre y después de borrar el CUIT).
- **Evidencia:** `capturas/PRV-6-cuit-repetido.png` (el link del choque anterior sigue arriba, junto al nombre, mientras el error nuevo está en el CUIT)
- **Causa probable (opcional):** `ProveedorDialog.tsx`: el estado `existente` solo se limpia al volver a enviar; no se limpia al editar el campo. Además el link se dibuja siempre bajo "Nombre", aunque el choque sea por CUIT.

### PRV-03 · La "ñ" cuenta como "n": no deja cargar un proveedor distinto que solo cambia en la ñ
- **Severidad:** baja
- **Tipo:** consistencia-con-documento
- **F-XX / pantalla:** F-56 · W-38 Nuevo proveedor · `/proveedores`
- **Persona:** Carla (Compras)
- **Pasos:** 1. Con "QA-PRV Corralón Ñandú S.R.L." cargado. 2. "+ Nuevo proveedor", nombre "QA-PRV Corralon Nandu". 3. Guardar.
- **Esperado:** RN-PRV-04 dice que el nombre se compara "sin tildes"; la ñ no es una tilde sino otra letra ("Peña" y "Pena", "Muñoz" y "Munoz" son apellidos distintos). Lo esperable es que lo muestre como **parecido** (aviso que deja guardar), no que lo bloquee.
- **Obtenido:** 422 "Ya existe QA-PRV Corralón Ñandú S.R.L." y no deja guardar. Reproducido 2 veces. Para decidir con el cliente: si prefieren que bloquee, no es bug; si no, la ñ debería ir al aviso de parecidos.
- **Evidencia:** request `POST /api/proveedores` → 422 `errors.nombre: ["Ya existe QA-PRV Corralón Ñandú S.R.L."]`
- **Causa probable (opcional):** `app/Modules/Proveedores/Domain/NombreProveedor.php::busqueda()` usa `Str::ascii()`, que convierte ñ → n antes de armar la clave única.

### PRV-04 · Direcciones de proveedor mal escritas muestran la lista como si nada
- **Severidad:** baja
- **Tipo:** funcional
- **F-XX / pantalla:** F-55 · W-14 Proveedores · `/proveedores/abc`, `/proveedores/0`, `/proveedores/-1`, `/proveedores/1.5`, `/proveedores/nuevo`
- **Persona:** Carla (Compras)
- **Pasos:** 1. Abrir a mano `/proveedores/abc` (o cualquiera de las otras).
- **Esperado:** el mismo aviso que con un número que no existe (`/proveedores/999999` muestra "Proveedor no encontrado · Elegí otro de la lista.", que está bien).
- **Obtenido:** la pantalla muestra la lista y "Elegí un proveedor de la lista", como si el link fuera bueno. Quien abre un link viejo o mal copiado no se entera de que ese proveedor no está. `/proveedores/nuevo` tampoco abre el alta (no existe esa dirección; no es un requisito, pero es la que alguien probaría).
- **Evidencia:** `capturas/PRV-0-proveedores-nuevo-url-pc.png` · `capturas/PRV-0-proveedor-inexistente-pc.png`
- **Causa probable (opcional):** `ProveedoresPantalla.tsx`: `elegido` queda en `null` si el parámetro no es un entero positivo, y eso se trata igual que "sin elegir".

### PRV-05 · Con un nombre de más de 150 letras, el aviso de parecidos da error en la red
- **Severidad:** baja
- **Tipo:** error-servidor
- **F-XX / pantalla:** F-56 · W-38 Editar proveedor · `/proveedores/3`
- **Persona:** Carla (Compras)
- **Pasos:** 1. Editar un proveedor. 2. Pegar en Nombre un texto de 151 caracteres.
- **Esperado:** el formulario avisa el límite (lo hace bien: "El nombre puede tener hasta 150 caracteres.") y no se llama a la API con un dato que ya sabe que es inválido.
- **Obtenido:** además del mensaje correcto, el aviso de parecidos pide `GET /api/proveedores/parecidos?nombre=…(151)` y el backend contesta 422, que queda como error en la consola. El usuario no lo ve, pero es un 4xx que no debería pasar. Visto 1 vez (repetible por código).
- **Evidencia:** red `GET /api/proveedores/parecidos?nombre=QA-PRV+LLLL…&cuit=30-71234569-8&excluir_id=3 → 422`
- **Causa probable (opcional):** `Parecidos.tsx` pide parecidos con cualquier largo; podría no consultar si el nombre pasa los 150 caracteres (o el backend recortar y responder vacío).

### PRV-06 · Buscar solo signos ("%%", "--", "__", "..") trae todos los proveedores
- **Severidad:** media
- **Tipo:** funcional
- **F-XX / pantalla:** F-55 · W-14 Proveedores · `/proveedores` y F-03 · W-18 Buscador general · `/buscar`
- **Persona:** Carla (Compras); igual con Hernán y Diego (cualquiera con Proveedores ≥ Ver)
- **Pasos:** 1. En `/buscar`, escribir `%%` (o `--`, `__`, `''`, `..`, o dos emojis). 2. Mirar los grupos. 3. En `/proveedores`, escribir `%%` o `_` en "Buscar proveedor…".
- **Esperado:** "Sin resultados" en los dos grupos (ningún nombre tiene esos signos), igual que hace Obras con la misma búsqueda.
- **Obtenido:** en el buscador general, "OBRAS (0) · Sin resultados." pero "PROVEEDORES (3)" con **todos** los proveedores (Gastos sin comprobante, QA-PRV Corralón Ñandú S.R.L., QA-PRV Prueba CUIT). En el listado, `%%` y `_` muestran la lista completa como si no se hubiera buscado nada. Reproducido 2 veces (p9 y p12), en pantalla y con `GET /api/buscar?q=%25%25` → `proveedores.cantidad: 3`. Con la carga inicial (cientos de proveedores en la semana 4), la persona ve una lista enorme que no tiene nada que ver con lo que buscó. No expone datos a quien no tiene permiso (Laura y Pablo no reciben el grupo).
- **Evidencia:** `capturas/PRV-12-buscador-porcentaje-carla.png` · `capturas/PRV-9-lista-porcentaje.png` · request `GET /api/buscar?q=%%` → 200 con 3 proveedores y 0 obras
- **Causa probable (opcional):** `app/Modules/Proveedores/Infrastructure/Consultas/PorNombre.php`: si `NombreProveedor::busqueda()` deja el texto vacío (solo signos o emojis), devuelve la consulta **sin filtro**. Para el buscador general debería devolver vacío; para el listado, vacío o la lista completa sin que parezca una búsqueda.

### PRV-07 · El buscador del listado no encuentra por CUIT ni por rubro, aunque los muestra
- **Severidad:** baja
- **Tipo:** consistencia-con-documento
- **F-XX / pantalla:** F-55 · W-14 Proveedores · `/proveedores`; F-03 · W-18 `/buscar`
- **Persona:** Carla (Compras)
- **Pasos:** 1. En "Buscar proveedor…" escribir `30-71234569-8` o `30712345698` (el CUIT que se ve debajo de "QA-PRV Corralón Ñandú S.R.L."). 2. Escribir `materiales`.
- **Esperado:** el documento (F-03, RN-PRV-03) pide buscar proveedores **por nombre**, así que no es un incumplimiento: queda para decidir. La lista muestra "MATERIALES · 30-71234569-8" debajo de cada nombre y Compras busca por CUIT cuando tiene la factura en la mano. Si se mantiene solo por nombre, el estado vacío podría decirlo.
- **Obtenido:** "No encontramos proveedores · Probá con otra parte del nombre." para el CUIT con y sin guiones y para el rubro. En el buscador general, el CUIT da "PROVEEDORES (0) · Sin resultados.". Reproducido 2 veces.
- **Evidencia:** `capturas/PRV-12-lista-busca-cuit.png`
- **Causa probable (opcional):** `PorNombre` filtra solo `nombre_busqueda`.

### PRV-08 · El aviso "Guardamos los cambios de …" queda puesto al elegir otro proveedor
- **Severidad:** baja
- **Tipo:** mensaje
- **F-XX / pantalla:** F-56 · W-14/W-38 · `/proveedores/3` → `/proveedores/2`
- **Persona:** Carla (Compras)
- **Pasos:** 1. Editar "QA-PRV Corralón Ñandú S.R.L." y guardar. 2. En la lista, elegir "QA-PRV Prueba CUIT".
- **Esperado:** el aviso verde se va al cambiar de proveedor (o a los pocos segundos).
- **Obtenido:** arriba sigue "Guardamos los cambios de QA-PRV Corralón Ñandú S.R.L.." mientras se ve la ficha de "QA-PRV Prueba CUIT"; parece que se guardó algo en el proveedor que se está mirando. Reproducido con 2 guardados seguidos.
- **Evidencia:** `capturas/PRV-10-aviso-queda-al-cambiar.png`
- **Causa probable (opcional):** `ProveedoresPantalla.tsx`: el estado `aviso` no se limpia al cambiar `:id` (la pantalla no se vuelve a montar porque la ruta es una sola, `/proveedores/:id?`).

### PRV-09 · "Atrás" del navegador después de guardar se queda en el mismo proveedor
- **Severidad:** baja
- **Tipo:** funcional
- **F-XX / pantalla:** F-56 · W-14 · `/proveedores/3`
- **Persona:** Carla (Compras)
- **Pasos:** 1. Ir a `/proveedores` y abrir "QA-PRV Corralón Ñandú S.R.L.". 2. Editar y guardar dos veces. 3. Abrir otro proveedor. 4. Apretar "Atrás" del navegador.
- **Esperado:** cada "Atrás" vuelve a la pantalla anterior: el proveedor anterior y después la lista.
- **Obtenido:** hacen falta 3 "Atrás" sobre `/proveedores/3` (no cambia nada en pantalla) antes de volver a `/proveedores`: cada guardado agrega una entrada repetida al historial del navegador. La persona aprieta "Atrás" y "no pasa nada".
- **Evidencia:** `p10.cjs`: URLs después de 4 "Atrás": `/proveedores/3`, `/proveedores/3`, `/proveedores/3`, `/proveedores`
- **Causa probable (opcional):** `ProveedoresPantalla.tsx` → `guardado()` hace `navigate(rutaProveedor(id))` también al editar, cuando ya se está en esa dirección; con `{ replace: true }` (o sin navegar al editar) se evita.

### PRV-10 · Carla en "Nueva obra" lee que no puede "ver" Obras, pero sí puede verlas
- **Severidad:** baja
- **Tipo:** mensaje
- **F-XX / pantalla:** F-02 / F-16 · W-06 Alta de obra · `/obras/nueva`
- **Persona:** Carla (Compras: Obras = Ver)
- **Pasos:** 1. Como Carla, abrir `/obras/nueva` (link guardado o compartido por alguien de operaciones).
- **Esperado:** algo como "Tu sector puede ver las obras pero no darlas de alta. Si lo necesitás, pedíselo a gerencia.", con un link a Obras.
- **Obtenido:** "No tenés acceso a esta sección · Obras: tu sector no tiene permiso para ver esto. Si lo necesitás, pedíselo a gerencia." Carla tiene Obras en el menú y entra al listado, así que el mensaje contradice lo que ve. Pasa en toda pantalla protegida con `RequirePermiso nivel="editar"`.
- **Evidencia:** `capturas/PRV-13-carla-obras-nueva.png`
- **Causa probable (opcional):** `src/shared/ui/States.tsx:90` (`NoAccessState`) dice siempre "permiso para ver esto", sin mirar si lo que falta es Editar.

## Cobertura

| F-XX | Estado | Hallazgos | Qué probé |
|---|---|---|---|
| F-55 Proveedores con buscador | ⚠️ | PRV-04, PRV-06, PRV-07 | Listado de Carla en orden alfabético, con rubro y CUIT debajo del nombre y el genérico marcado "genérico". Ficha con CUIT `30-71234569-8`, contacto y "Todavía no hay gastos cargados."; sin saldos ni "borrar". Buscador del listado: "corralon nandu", "ÑANDÚ", "s.r.l" y "QA-PRV CORRALÓN" encuentran (ignora mayúsculas, tildes y puntos). "zzzzqqq" y `<script>…` → "No encontramos proveedores · Probá con otra parte del nombre."; el texto se muestra sin ejecutarse. El CUIT y el rubro no encuentran (PRV-07); solo signos trae todo (PRV-06). Id inexistente → "Proveedor no encontrado · Elegí otro de la lista."; ids mal escritos, ver PRV-04. Celular Pixel 7: lista, ficha y "‹ Proveedores" sin scroll horizontal. |
| F-56 Alta y edición | ⚠️ | PRV-01, PRV-02, PRV-03, PRV-05, PRV-08, PRV-09 | Nombre vacío o solo espacios → "Completá el nombre del proveedor." al lado del campo. CUIT con letras o 10 dígitos → "El CUIT tiene 11 números, por ejemplo 30-00000000-7."; dígito verificador malo (con guiones o con espacios) → "El CUIT no es válido. Revisá los números." sin borrar lo escrito; CUIT con puntos se acepta. Mail "ventas@" → "Escribí un mail válido."; mail en mayúsculas se guarda en minúsculas; el nombre se guarda sin los espacios de los bordes. Doble clic en "Guardar proveedor" → 1 POST y 1 proveedor; F5 después de guardar muestra lo guardado. Nombre repetido escrito de otra forma → "Ya existe …" con link; CUIT repetido en otro formato → "Ese CUIT ya es de …" con link, y "(mismo CUIT)" mientras se escribe; el aviso de parecidos deja guardar. Editar sin cambios guarda y no registra nada; renombrar a uno existente o poner el CUIT de otro → 422 con mensaje claro. Emojis se guardan; 150 caracteres sí, 151 no. F5 a mitad de la edición y Escape descartan sin error. No hay baja (RN-PRV-11): correcto. Genérico: sin Editar, nota "No se modifica.", `PATCH` → 422 "El proveedor genérico no se modifica.", `DELETE` → 405. Celular: el formulario entra en pantalla, el error sale al lado del campo y "Guardar proveedor" queda visible. |
| F-14 Historial de proveedores | ✅ (sin pantalla) | — | El documento pide F-14 en pedidos, obras y gastos; proveedores no tiene pantalla de historial y no es exigible. En la base, `actividades` guarda "creado" y "editado" con quién y antes → después; guardar sin cambios no registra nada. |
| Permisos Proveedores | ✅ | — | Laura y Pablo: sin "Proveedores" en el menú (Inicio · Obras). `/proveedores`, `/proveedores/3`, `/proveedores/nuevo` y `/proveedores/999999` → "No tenés acceso a esta sección · Proveedores: tu sector no tiene permiso para ver esto. Si lo necesitás, pedíselo a gerencia.". API listar, ver, parecidos, opciones, editar y crear → 403 "No tenés permiso para hacer esto.". Hernán (Ver, §6.3): ve lista y ficha, sin "+ Nuevo proveedor" ni "Editar"; `PATCH`/`POST` → 403. Diego (Editar): "+ Nuevo proveedor" y "Editar" presentes. |
| F-03 Buscador general | ⚠️ | PRV-06, PRV-07 | Lupa de la barra → `/buscar`. Con 1 carácter o "  a " → "Escribí al menos 2 letras o números.". "qa-prv" → "PROVEEDORES (2)" con "QA-PRV" resaltado; "ÑANDÚ" encuentra obra y proveedor; "zzzzqqq" → "Sin resultados." en cada grupo; `<script>alert(1)</script>` se muestra como texto. Laura y Pablo: solo el grupo Obras, sin Proveedores. Hernán y Diego: ven el grupo Proveedores. |
| F-02 Menú de Carla / Obras | ⚠️ | PRV-10 | Menú de Carla: Inicio · Obras · Proveedores + lupa (Inicio abre Obras hasta que se entregue Pedidos, DU-A12); en celular: Obras · Más. En Obras (§6.3 Ver): ve listado y pestañas, sin "Nueva obra"; ficha sin "Editar" ni cambio de estado (solo bajar la oferta); `/obras/7/editar` muestra solo el historial, sin formulario. `PATCH /api/obras/7` y `POST /api/obras` → 403; `/obras/nueva` → mensaje de PRV-10. |

## No probado

- Carga de **Presupuesto por rubro** como Carla (Compras = Editar en §6.3): lo vi accesible en la ficha, pero F-48 lo cubre otro rol.
- Cambiar el estado de una obra como Carla con un estado válido: no lo intenté para no tocar obras de otros testers. Con un estado inventado el backend contesta 422 porque valida el dato antes que el permiso. Por código, `CambiarEstadoObraAction` exige ser responsable o tener `estado_obra = editar`, y lo cortaría con 403.
- Dos pestañas editando el mismo proveedor: no llegué.
- iPhone SE: solo probé Pixel 7.

