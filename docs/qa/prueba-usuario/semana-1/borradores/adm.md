# Borrador · Tester funcional de Gerencia de Administración y Dirección (ADM)

Personas: Diego (Gerencia de Administración, sesión guardada), Dora (Dirección, creada por Diego
desde Usuarios), QA-ADM Ana (sector QA-ADM Pruebas, 1 ingreso para ver el impacto de la tabla de
permisos), Laura (solo para entrar por URL directa a pantallas de administración).
Datos creados (todos ficticios, prefijo QA-ADM):

- Sectores: "QA-ADM Pruebas" (id 12), "QA-ADM Obra 🏗️ Ñandú" (id 13, sacado), "QA-ADM Doble"
  (id 14, renombrado a "QA-ADM Doble Renombrado"), "QA-ADM Sacar Doble" (id 16, sacado),
  "QA-ADM Sacar Simple" (sacado).
- Usuarios: "QA-ADM Ana Ñúñez 👷" `qa-adm-ana@empresa-ejemplo.com` (id 7, QA-ADM Pruebas, depende
  de QA-ADM Baja), "Dora" `qa-adm-dora@empresa-ejemplo.com` (id 8, Dirección), "QA-ADM Beto Editado"
  `qa-adm-beto@empresa-ejemplo.com` (id 9, QA-ADM Pruebas, depende de Ana), "QA-ADM Baja"
  `qa-adm-baja@empresa-ejemplo.com` (id 10, QA-ADM Doble Renombrado; dado de baja y reactivado).
- Obras: 2-601-2026 "QA-ADM Obra de Ana" (id 12, responsable Ana) y 2-602-2026 "QA-ADM Obra ajena"
  (id 13, responsable QA-ADM Beto), ambas Confirmadas, creadas por Diego.
- Tabla de permisos: solo la fila "QA-ADM Pruebas" (Proveedores V, Usuarios y permisos V, Plazos V,
  Rendición V·sus obras). Plazos de la prioridad: se probaron 0/5 y quedaron otra vez en **1 / 7**.

Nota de entorno: entre 14:49 y 15:55 UTC MySQL y Redis estuvieron caídos (corte externo, lo maneja
el Lead). Lo que falló en ese rato se repitió después y no se reporta como hallazgo. El mail del
código de Dora salió ~5 min tarde porque el worker se estaba levantando (no se reporta).

<!-- HALLAZGOS -->

### ADM-01 · Doble clic en "Sacar sector" saca el sector pero muestra "No encontramos lo que buscabas."
- **Severidad:** media
- **Tipo:** funcional
- **F-XX / pantalla:** F-59 · W-40 Sectores · `/usuarios/sectores` (ventana "Sacar · …")
- **Persona:** Diego (Gerencia de Administración)
- **Pasos:** 1. Usuarios → Sectores. 2. En un sector sin usuarios (ej. "QA-ADM Sacar Doble") tocar "sacar". 3. En la ventana, doble clic en "Sacar sector".
- **Esperado:** se manda una sola vez, la ventana se cierra y aparece "Sacamos QA-ADM Sacar Doble." (como pasa con un clic). Doble clic no tiene que crear ni romper nada (plan de pruebas §4 "Concurrencia/repetición").
- **Obtenido:** salen **dos** `DELETE /api/sectores/{id}`: el primero responde 200 y saca el sector; el segundo 404. La ventana queda abierta con el error en rojo **"No encontramos lo que buscabas."** sobre "¿Sacar QA-ADM Sacar Doble?…". El usuario cree que falló, aunque el sector ya no está en la lista. Con un solo clic anda bien ("Sacamos QA-ADM Sacar Simple."). Reproducido 2 veces (sectores id 13 y 16).
- **Evidencia:** `capturas/ADM-s7-sacado.png` · `capturas/ADM-s8-sacar-dblclick.png` · `DELETE /api/sectores/13 → 200` + `→ 404`; `DELETE /api/sectores/16 → 404`
- **Causa probable (opcional):** `src/features/identidad/components/SacarSectorDialog.tsx` — el botón "Sacar sector" llama a `sacar.mutate` en `onClick` sin cortar el segundo clic (el `cargando` llega un render tarde). El alta de usuario y de sector no tienen el problema porque son `<form>` con clave de idempotencia. Mismo patrón que ADM-02.

### ADM-02 · Doble clic en "Guardar cambios" de la tabla de permisos manda el cambio dos veces
- **Severidad:** baja
- **Tipo:** funcional
- **F-XX / pantalla:** F-60 · W-15 Tabla de permisos · `/usuarios/permisos`
- **Persona:** Diego (Gerencia de Administración)
- **Pasos:** 1. Usuarios → Tabla de permisos. 2. Cambiar una celda de un sector propio (ej. "Gastos y facturas · QA-ADM Doble" a Ver). 3. Doble clic en "Guardar cambios".
- **Esperado:** un solo envío.
- **Obtenido:** salen dos `PUT /api/permisos` iguales (ambos 200). Hoy no rompe nada (el segundo no cambia celdas y no duplica el registro en `actividades`, verificado en la base) y el mensaje "Guardamos la tabla de permisos. Rige desde el próximo pedido de cada persona al sistema." aparece bien, pero es el mismo agujero que ADM-01 en un botón que guarda permisos. Reproducido 3 veces.
- **Evidencia:** `capturas/ADM-p4-guardar-dblclick.png` · `PUT /api/permisos` ×2 a los 0,1 s del doble clic
- **Causa probable (opcional):** `src/features/identidad/pantallas/TablaPermisosPantalla.tsx` (`onClick={enviar}` sin guarda ni clave de idempotencia).

### ADM-03 · Tabla de permisos: los cambios sin guardar se pierden sin aviso al cambiar de pestaña o recargar
- **Severidad:** media
- **Tipo:** funcional
- **F-XX / pantalla:** F-60 · W-15 Tabla de permisos · `/usuarios/permisos`
- **Persona:** Diego (Gerencia de Administración)
- **Pasos:** 1. Usuarios → Tabla de permisos. 2. Cambiar 4 celdas de "QA-ADM Pruebas" (la barra dice "4 cambios sin guardar"). 3. Tocar la pestaña "Sectores" (o apretar F5). 4. Volver a "Tabla de permisos".
- **Esperado:** antes de irse, un aviso tipo "Tenés 4 cambios sin guardar. ¿Salir igual?" (la tabla tiene 12 columnas × 14 filas; armar varios cambios y perderlos de un clic es fácil).
- **Obtenido:** se va sin preguntar y al volver la fila está como antes: los 4 cambios se perdieron. Con F5 tampoco aparece el aviso del navegador. Reproducido 2 veces (pestaña y F5).
- **Evidencia:** `capturas/ADM-p1-cambios-sin-guardar.png` · salida del script `adm/07-permisos.cjs` y `adm/12-ana-impacto.cjs` ("F5 con cambio sin guardar → diálogo del navegador: null")
- **Causa probable (opcional):** `TablaPermisosPantalla.tsx` guarda los cambios en estado local sin `useBlocker`/`beforeunload`.

### ADM-04 · Renombrar un sector sin cambiar el nombre dice "Renombramos QA-ADM Doble a QA-ADM Doble."
- **Severidad:** baja
- **Tipo:** mensaje
- **F-XX / pantalla:** F-59 · W-40 Sectores · `/usuarios/sectores` (ventana "Renombrar · …")
- **Persona:** Diego (Gerencia de Administración)
- **Pasos:** 1. Sectores → "renombrar" en "QA-ADM Doble". 2. Dejar el mismo nombre y tocar "Guardar".
- **Esperado:** cerrar sin mensaje, o "No cambiaste el nombre." — no confirmar un cambio que no hubo.
- **Obtenido:** se manda `PATCH /api/sectores/14` (200) y aparece en verde "Renombramos QA-ADM Doble a QA-ADM Doble.".
- **Evidencia:** salida de `adm/14-sectores2.cjs` · `PATCH /api/sectores/14 → 200`
- **Causa probable (opcional):** `src/features/identidad/components/SectorDialog.tsx` arma el texto sin comparar con el nombre anterior.

### ADM-05 · Con "solo sus obras", el filtro "Responsable" del listado ofrece a toda la empresa
- **Severidad:** baja
- **Tipo:** funcional
- **F-XX / pantalla:** F-15 · W-05 Listado de obras · `/obras` (filtro "Responsable")
- **Persona:** QA-ADM Ana (sector QA-ADM Pruebas con Obras = Editar · solo sus obras)
- **Pasos:** 1. Con Obras = Editar·solo sus obras, entrar a Obras. 2. Abrir el desplegable "Responsable".
- **Esperado:** con alcance "solo sus obras" la persona solo ve obras donde ella es responsable, así que el filtro no le sirve: ocultarlo, o dejar solo "Todos" y ella (como ya hace el alta y la edición, que solo ofrecen "QA-ADM Ana Ñúñez 👷").
- **Obtenido:** ofrece 15 personas ("Carla, Diego, Dora, Hernán, Laura, Pablo, …, Sofía"). Cualquiera que elija que no sea ella da una lista vacía "No hay obras en esta pestaña", y parece que esas personas no tienen obras. Visto en las 3 pestañas y al volver a entrar.
- **Evidencia:** `capturas/ADM-o1-ana-listado-obras.png` · salida de `adm/16-alcance-propias.cjs` ("opciones Responsable: …")
- **Causa probable (opcional):** el filtro usa `GET /api/usuarios/opciones` sin mirar el alcance de `obras` de la sesión.

## Cobertura

| F-XX | Estado | Hallazgos | Qué probé |
|---|---|---|---|
| F-58 Usuarios | ✅ | — | Diego y Dora (Dirección). Listado (Nombre · Mail · Sector · Activo, solo activos por defecto, "Ver inactivos", buscar). Alta: vacío ("Completá el nombre." / "Completá el mail." / "Elegí el sector."), nombre de espacios, nombre de 137 ("El nombre puede tener hasta 120 caracteres."), mail mal formado / con espacio / sin dominio ("Escribí un mail válido, por ejemplo nombre@empresa.com."), mail de 207, teléfono de 60, mail repetido exacto y con mayúsculas/espacios ("Ese mail ya es de otro usuario (Diego)."), mail de un inactivo ("Ese mail ya es de otro usuario (QA-ADM Baja, inactivo). Podés reactivarlo."), nombre con emoji/ñ/tildes y mail con mayúsculas y espacios (se guarda en minúsculas y recortado). Errores al lado del campo y sin borrar lo escrito. Doble clic en Guardar del alta: 1 solo POST (4 veces). Edición (nombre, teléfono, jefe) + F5 ok. Baja → aviso "QA-ADM Baja tiene 1 persona a cargo. Quedan como están hasta que las cambies." → sale de activos y de los desplegables; reactivación ok. Uno mismo: radio "Inactivo" deshabilitado con "No te podés desactivar a vos mismo." y la API responde lo mismo (Dora). IDs inexistentes por API: 404 "No encontramos lo que buscabas."; sector inexistente: "No existe o no está disponible.". **Mail al dar de alta: no sale ninguno** (el log no creció tras 4 altas), como pide RN-IDE-09; el único mail del módulo es el del código: 1 por pedido, en español, asunto "Tu código para entrar a Tecnoservicios", "Hola Dora: Tu código… Sirve una sola vez y vence en 10 minutos. Si no lo pediste, ignorá este mail.", sin datos de más y **sin link** (la spec no lo pide; queda como observación). Registro en `actividades` de altas y ediciones (quién, antes → después) verificado en la base. |
| F-59 Sectores | ⚠️ | ADM-01, ADM-04 | Alta: vacío/espacios ("Completá el nombre del sector."), 88 caracteres, repetido "compras", "Cómpras", " COMPRAS " ("Ya hay un sector con ese nombre."), emoji/ñ, doble clic (1 solo POST). Sector nuevo nace con Pedidos E·T, Equipos E, Obras V·T y el resto Sin acceso (RN-IDE-30) ✅. Renombrar a " dirección " (rechaza), renombrar ok (el usuario sigue en el sector). Sacar con 2 usuarios activos: "Antes de sacar QA-ADM Pruebas, pasá a sus 2 usuarios a otro sector." ✅. Sacar sin historia: se borra y sale de la tabla y de los desplegables; alta de usuario en el sector borrado → 422 claro. |
| F-60 Tabla de permisos | ⚠️ | ADM-02, ADM-03 | **Configuración inicial de los 11 sectores × 12 funciones contrastada celda por celda contra identidad.md §6.3: coincide al 100 %.** Contra cap. 3 / W-15 del documento firmado: coincide en todo lo explícito; la diferencia es Ingeniería en Gastos y Presupuesto por rubro "Ver · sus obras" donde W-15 dice "Ver", que es la duda DU-A04 resuelta (no es hallazgo). Cambio en la fila QA-ADM Pruebas + F5 ok; queda en `actividades` (antes → después). Impacto verificado ingresando con QA-ADM Ana: menú pasa a Inicio · Obras · Proveedores · Usuarios; en Usuarios ve las 4 pestañas en solo lectura (sin "+ Agregar", sin desplegables, sin Guardar) y la API le da 403 "No tenés permiso para hacer esto." al dar de alta o cambiar plazos. Al sacarle Proveedores, la API responde 403 al instante y tras F5 ve "No tenés acceso a esta sección · Proveedores: tu sector no tiene permiso para ver esto. Si lo necesitás, pedíselo a gerencia." (restaurado después). API con valores inválidos ("Esta función no admite 'solo sus obras'.", nivel inválido, función y sector inexistentes): 422 en español. La tabla es más ancha que la pantalla en PC (1712 px) pero el scroll queda dentro de su marco, sin scroll de página. |
| F-61 Jerarquía | ✅ | — | Beto → Ana, Ana → Baja (guardan y se ven tras F5). Círculo directo: "Así se arma un círculo: QA-ADM Beto Editado ya depende de QA-ADM Ana Ñúñez 👷."; círculo indirecto (Baja → Beto): "…ya depende de QA-ADM Baja." al lado del campo. Depender de sí mismo: el desplegable no lo ofrece y la API dice "No puede depender de sí mismo.". Jefe dado de baja: no aparece en el desplegable y la API dice "La persona elegida está inactiva."; jefe inexistente: "No existe o no está disponible.". Mi perfil muestra "Depende de: Hernán (Jefatura de Proyectos)" (Laura) y "Nadie" (Diego, Dora). |
| F-68 Plazos de la prioridad | ✅ | — | Diego (editar): vacío ("Completá un número entero de días."), −1 ("No puede ser negativo."), 1.5 ("Tiene que ser un número entero de días."), 3/2 y 3/3 ("Prioritario tiene que ser mayor que Urgente."), 400 ("Puede ser hasta 365 días."), letra "e"; ninguno manda pedido. 0/5 se guarda ("Guardamos los plazos. Valen para los pedidos que se carguen desde ahora."), F5 ok, "Normal: más de 5 días"; restaurado a 1/7 con doble clic (1 solo PUT). API con 1.5, "abc" y −1: 422 en español. Dora y Ana (Ver): solo texto "hasta 1 día", sin botón Guardar. |
| F-10 Mi perfil | ✅ | — | Dora: nombre de espacios / 130 caracteres (errores al lado del campo, sin pedido), cambio de nombre con emoji y teléfono + F5 ok, vuelta al original; mail, sector y jefatura solo lectura; `PATCH /api/me` con mail/sector → "Este dato no se puede cambiar acá.". Diego: nombre vacío rechazado; guardar sin cambios ok (no se modificó su nombre). Laura: solo mirar. Sin sección de contraseña ni de avisos (RN-IDE-17/18) ✅. |
| F-02 Menú | ✅ | — | Diego: Inicio · Obras · Proveedores · Usuarios; Dora (Dirección): Inicio · Obras · Proveedores · Usuarios, Proveedores sin alta, Obras sin "+ Nueva obra", Tabla de permisos editable, Plazos solo lectura (= §6.3); Laura: Inicio · Obras. Celular (Pixel 7) Diego: "Más" → Proveedores · Usuarios · Mi perfil · Salir; Usuarios y Tabla de permisos sin scroll horizontal de página. Laura por URL directa a `/usuarios`, `/usuarios/sectores`, `/usuarios/permisos`, `/usuarios/plazos`: "No tenés acceso a esta sección · Usuarios: tu sector no tiene permiso para ver esto. Si lo necesitás, pedíselo a gerencia." (sin 403 crudo); `/usuarios/999999`, `/usuarios/abc`, `/usuarios/sectores/999`: "No encontramos esta página · Revisá la dirección o volvé al inicio desde la barra.". API de Laura: 403 "No tenés permiso para hacer esto." en usuarios, sectores (alta), permisos y plazos. |
| Alcance "solo sus obras" (F-15, F-17, F-19, F-20, F-03, F-48, RN-IDE-47) | ✅ | ADM-05 | Sector QA-ADM Pruebas con Obras = Editar·sus obras, Presupuesto por rubro = Ver·sus obras y Monto de obra = Ver·sus obras (al final volvió a Obras Ver·todas, Presupuesto y Monto Sin acceso, verificado por API). Diego creó 2-601-2026 "QA-ADM Obra de Ana" (id 12, responsable Ana) y 2-602-2026 "QA-ADM Obra ajena" (id 13, responsable QA-ADM Beto), las dos con oferta PDF. Con Ana: **listado** solo la suya, contadores (0/1/0) y las 3 pestañas por estado correctos; filtrar por "2-602" o por responsable Beto da vacío. **Ficha, editar y presupuesto por rubro de la ajena por URL directa:** "Obra no encontrada · Puede que el link esté mal o que no tengas acceso a esa obra. · Volver a Obras" (sin 403 crudo). **API sobre la ajena:** ficha, historial, PATCH, adicional y cambio de estado → 404 "Obra no encontrada."; documento sin adjuntos → 422 tanto en la ajena como en un id inexistente (no revela que exista); con adjunto válido → 404. **Propia:** ficha 200 con Editar, "+ agregar presupuesto adicional" y "Cambiar estado"; PATCH 200 (planta editada, se ve en el listado y el buscador); ponerle otro responsable (PATCH o alta por API) → 422 "Solo podés ponerte a vos como responsable de la obra."; en el alta y la edición el desplegable solo la ofrece a ella. **Descarga:** oferta propia 200 `application/pdf`; oferta de la ajena 404. **Presupuesto por rubro (Ver·sus obras):** propia 200, ajena 404; cargar montos → 403 "No tenés permiso para hacer esto.". **Monto de obra (Ver·sus obras):** `monto_obra` aparece en la ficha propia; `puede.cargar_monto_obra=false`. **Buscador general** (`/buscar?q=QA-ADM` y "2-602"): solo trae la propia. |
| F-14 (usuarios/permisos) | ✅ | — | RN-IDE-37: sin pantalla en etapa 1; no aparece nada roto. En la base, `actividades` registra alta de sectores y usuarios, cada edición de usuario (campo, antes → después, quién) y cada celda de la tabla. |

## No probado

- **Guarda del último administrador (RN-IDE-25) por baja, cambio de sector o cambio de matriz:** para
  dejar 0 administradores habría que dar de baja o cambiar de sector a Diego o tocar las filas de
  Dirección / Gerencia de Administración, que son del seeder y las usan otros testers. Solo se probó la
  parte "no te podés desactivar a vos mismo" (Diego en pantalla, Dora por API). El código
  (`GuardaAdministradores.php`) y sus tests existen; queda para el Lead con una base propia.
- **Refresco del menú al volver a enfocar la pestaña (RN-IDE-14):** en el navegador sin pantalla el
  evento de foco simulado no disparó el refresco; con F5 sí se ve el cambio. No concluyente, no se reporta.
- **Dos pestañas editando el mismo usuario:** no se llegó por el tiempo perdido en el corte de MySQL/Redis.
