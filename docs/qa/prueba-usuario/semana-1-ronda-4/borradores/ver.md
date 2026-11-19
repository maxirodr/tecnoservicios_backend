# Borrador VER · Re-verificación de los arreglos de B-01, A-01 y A-02 (ronda 4 retomada)

- **Fecha:** 2026-11-19, 09:27–10:12 (hora de Argentina; 12:27–13:12 UTC). Front `staging` @ **`380ddaf`** (merge de `efaf4b1` B-01, `428eb27` A-01, `9d0f84e` A-02). Back sin cambios de código (rama de QA @ `c672382`, solo docs sobre `56e5d8f`).
- **Personas / sesiones:** Diego (`diego.json`, Gerencia de Administración). No hizo falta una segunda persona: los conflictos (409) se forzaron con `page.route` + `fulfill`.
- **Ingresos reales gastados:** 0.
- **Datos creados (prefijo QA4V):** obra 2-871-2026 "QA4V Obra historial hora" (id 2; responsable Hernán; planta cambiada varias veces), proveedor "QA4V Ferretería Atrás" (CUIT 30-71234567-1, teléfono cambiado en cada corrida).
- **Lo que toqué y dejé como estaba:** la celda "Alcance de Pedidos · RRHH" de la Tabla de permisos (se cambió a "sus obras" y se volvió a "todas" en cada corrida; en la base, `permisos` sector 10 / función 1 = `editar` / `todas`, como el seeder; solo cambió `updated_at`). También Plazos de la prioridad (Urgente 1 → 2 → 1; queda `1 / 7`) y el teléfono de Diego en Mi perfil (vacío → "291 455-7777" → vacío). Comprobado en la base al final.
- **Scripts:** `C:\Users\GREEN\AppData\Local\Temp\claude\4fe530b1c11d8e8d\f4efcdda-aa94-4434-aafd-2e4822d4d74b\scratchpad\r4\VER\`: `lib.mjs`, `01-alta-obra.mjs`, `02-b01.mjs` (B-01 en 3 husos), `03-borde.mjs` (borde de día + ingreso trabado), `04-a01.mjs` y `07-a01-vista.mjs` (A-01), `05-restaurar-permiso.mjs`, `06-a02.mjs` (A-02), `08-o11.mjs` (regresión O-1.1), `09-b02.mjs` (B-02). Salidas `out-*.txt` en la misma carpeta.
- **Navegador:** Chromium headless con `--lang=es-AR`, contexto `locale: 'es-AR'`; huso según la prueba (`America/Argentina/Buenos_Aires`, `UTC`, `Europe/Madrid`).
- **Demora de red:** 2,5 s en el pedido que guarda (`PATCH /api/obras/2`, `PUT /api/permisos`, `PATCH /api/proveedores/{id}`, Plazos, Mi perfil). Fallas forzadas con `page.route` + `fulfill` después de los mismos 2,5 s: 422 (con error de campo), 409 ("Alguien cambió esto…") y 500.
- **Consola:** solo "Failed to load resource" por los 422/409/500 forzados. Ningún error JS ni warning de React.
- **Interrupción:** entre ~12:44 y ~13:06 UTC la base estuvo caída (`/api/health` → 503, `database:false`; según el Lead, MySQL se cayó solo y se relanzó desde Laragon con recuperación de InnoDB). Lo visto en ese rato no se usa como hallazgo. La primera corrida de A-02 se cortó por eso (`GET /api/me` → 503); se repitió completa cuando volvió.

## Tabla de verificación

| PU | título corto | estado | pantallas revisadas | evidencia | nota |
|---|---|---|---|---|---|
| B-01 | Fecha y hora en hora de Argentina, sin importar el huso del navegador | ⚠️ | W-23 Editar obra · Historial de cambios (obra 2) en `America/Argentina/Buenos_Aires`, `UTC` y `Europe/Madrid`; borde de día con `cuando` cambiado por `page.route`; Usuarios ("Ingreso trabado hasta las…") y diálogo del usuario ("… no puede entrar hasta las…") en los 3 husos | `B-01-r4b-1-*.png`, `B-01-r4b-2-*.png` (cambio real) · `B-01-r4b-borde-1/2/3-*.png` · `B-01-r4b-trabado-1/2/3-*.png`, `B-01-r4b-trabado-dlg-3-*.png` · `GET /api/obras/2/historial` → 200 | **Historial: ✅.** Cambio guardado a las 09:30:29 de Argentina (API `"cuando":"2026-11-19T12:30:29+00:00"`): en los 3 husos dice "19/11/2026 09:30 · Diego · Planta: Planta QA4V → Planta QA4V v1-208". Segunda vez: 09:31:28 → "19/11/2026 09:31" en los 3.<br>**Borde de día** (cuerpo real, solo `cuando` cambiado), igual en los 3 husos:<br>• `2026-11-19T01:30:00+00:00` → "18/11/2026 22:30";<br>• `…T02:59:00Z` → "18/11/2026 23:59";<br>• `…T03:00:00+00:00` → "19/11/2026 00:00".<br>Formato siempre `dd/mm/aaaa HH:MM`.<br>**Ingreso trabado: ❌** (sigue el huso del navegador). Con `hasta` = 10:35 de Argentina, la línea dice "· Ingreso trabado hasta las 10:35" en Argentina, "**13:35**" en UTC y "**14:35**" en Madrid. El diálogo del usuario dice lo mismo ("QA Lead R4 B no puede entrar hasta las 13:37: …" en UTC). Ver **VER-01**. |
| A-01 | Tabla de permisos: irse mientras guarda | ✅ | W-15 `/usuarios/permisos` (celda "Alcance de Pedidos · RRHH": "todas" → "sus obras"); "Obras" en la barra, pestaña "Sectores" y Atrás; ok, 422, 409 y 500 | `A-01-r4b-1…5-*-ok-guardando/final.png` · `A-01-r4b-6…11-*-{422,409,500}-*.png` · `PUT /api/permisos` → 200 / 422 / 409 / 500 (forzados) | **Mientras guarda** (las 11 corridas): "Estamos guardando. Cuando termine te llevamos." / "No cierres esta pestaña hasta que termine." / "Entendido". Ya no sale "Tenés cambios sin guardar".<br>**Ok** (5 veces: barra ×2, "Sectores", Atrás ×2): al 200 llega sola a `/obras`, `/usuarios/sectores` o `/usuarios`, sin ninguna pregunta abierta. En la base queda `propias`; después la volví a `todas`.<br>**Falla** (6 veces: 422 ×2, 409 ×2, 500 ×2, por barra, "Sectores" y Atrás): se queda en `/usuarios/permisos`, sin diálogo, con "Revisá los datos marcados." / "Alguien cambió esto mientras lo tenías abierto. Actualizá y revisá." / "El sistema tuvo un problema. Volvé a intentar en un momento." y "1 cambio sin guardar". La celda sigue cambiada y la base no cambia. Con Atrás, el aviso queda **arriba, fuera de la vista** (pasa igual sin irse): **VER-02**, baja. |
| A-02 | Editar proveedor + Atrás mientras guarda | ✅ | W-14 `/proveedores/8` ("QA4V Ferretería Atrás", entrando desde la lista) → "Editar" → "Contacto · teléfono" → "Guardar proveedor" → Atrás; ok, 422, 409 y 500 | `A-02-r4b-1/2-ok-guardando/final.png` · `A-02-r4b-3-422-*`, `-4-409-*`, `-5/6-500-*.png` · `PATCH /api/proveedores/8` → 200 / 422 / 409 / 500 (forzados) | **Mientras guarda:** el diálogo sigue con "Guardando…" y encima sale "Estamos guardando. Cuando termine te llevamos.".<br>**Ok** (2 veces): al 200 va a `/proveedores`, con **0 diálogos abiertos** y "Guardamos los cambios de QA4V Ferretería Atrás.". En la base, el teléfono nuevo (`291 400-7229`, `291 400-7011`). Si después toca "Obras", el aviso no queda pegado.<br>**Falla** (422, 409, 500 ×2): se queda en `/proveedores/8` con el diálogo abierto y, dentro, "Revisá los datos marcados." / "Alguien cambió esto mientras lo tenías abierto. Actualizá y revisá." / "El sistema tuvo un problema. Volvé a intentar en un momento.". La base no cambia. |
| B-02 | Campo de fecha según el idioma del navegador (observación, límite conocido) | ✅ (obs.) | W-23 Editar obra, "Inicio previsto" / "Fin previsto", Chromium con `--lang=es-AR` | `B-02-r4b-1.png` | Con el navegador en español: "31/12/2026" (dd/mm/aaaa). El campo vacío muestra la máscara "dd/mm/yyyy" del navegador. No hubo cambio de código: queda como límite conocido, no como hallazgo. |

**Conteo:** ✅ 3 (A-01, A-02, B-02 como observación) · ⚠️ 1 (B-01: historial ✅, ingreso trabado ❌ → VER-01) · ❌ 0

## Hallazgos nuevos

### VER-01 · "Ingreso trabado hasta las HH:MM" sigue el huso del navegador: con la PC en UTC dice 13:35 en vez de 10:35
- **Severidad:** media
- **Tipo:** consistencia-con-documento
- **F-XX / pantalla:** F-60 · W-16 Usuarios · `/usuarios` (línea "Ingreso trabado hasta las…") y diálogo "Editar usuario" ("… no puede entrar hasta las …")
- **Persona:** Diego (Gerencia de Administración)
- **Pasos:** 1. Tener un usuario con el ingreso trabado. Acá, `bloqueo_ingreso.hasta` = `2026-11-19T13:35:29+00:00` (10:35 de Argentina), puesto con `page.route` sobre el cuerpo real de `GET /api/usuarios`, sin quemar códigos. 2. Abrir Usuarios con el navegador en `timezoneId: 'UTC'` y después en `'Europe/Madrid'`. 3. Mirar la línea del usuario y abrir su diálogo.
- **Esperado:** "· Ingreso trabado hasta las 10:35" en cualquier huso, como el historial después del arreglo de B-01 ("toda fecha con hora en hora de Argentina sin importar el huso del navegador").
- **Obtenido:**
  - Argentina: "· Ingreso trabado hasta las 10:35".
  - UTC: "· Ingreso trabado hasta las 13:35".
  - Madrid: "· Ingreso trabado hasta las 14:35".
  - En el diálogo, en UTC: "QA Lead R4 B no puede entrar hasta las 13:37: se equivocó varias veces con el código…".
  - Reproducido 3 veces (`03-borde.mjs 2 1/2/3`). Con la PC en hora de Argentina se ve bien (como ya había visto B en la ronda 4).
- **Evidencia:** `capturas/B-01-r4b-trabado-1/2/3-{buenos_aires,utc,madrid}.png` · `capturas/B-01-r4b-trabado-dlg-3-*.png` · `GET /api/usuarios` → 200
- **Causa probable (opcional):** `src/features/identidad/components/horaDelBloqueo.ts:3-15` usa `toLocaleTimeString`, `toLocaleDateString` y `toDateString` sin `timeZone`. El arreglo `efaf4b1` solo cambió `src/shared/dominio/fechas.ts` (`formatearFechaHora`). Es media y no alta porque solo se ve si la PC no está en hora de Argentina.
- **Regresión de:** no; arreglo incompleto de B-01 (front `efaf4b1`)

### VER-02 · Tabla de permisos: si el guardado falla, el error queda arriba, fuera de la vista
- **Severidad:** baja
- **Tipo:** mensaje
- **F-XX / pantalla:** F-60 · W-15 Tabla de permisos · `/usuarios/permisos`
- **Persona:** Diego (Gerencia de Administración)
- **Pasos:** 1. Entrar a Usuarios → "Tabla de permisos" (PC 1366×768). 2. Cambiar "Alcance de Pedidos · RRHH". 3. Bajar hasta "Guardar cambios" y tocarlo; el guardado falla (500 forzado a los 2,5 s). 4. Variante: tocar Atrás mientras guarda.
- **Esperado:** (O-1.1: "si falla, se queda con el error") el error a la vista, como en las demás pantallas (en la ronda 4, A lo vio "a la vista (y=162)").
- **Obtenido:**
  - "El sistema tuvo un problema. Volvé a intentar en un momento." está en la pantalla, pero 302 px **por encima** de lo visible (`scrollY` 473; el aviso en `top = -302`). El foco vuelve a "Guardar cambios".
  - Lo único visible junto al botón es "1 cambio sin guardar": parece que no pasó nada.
  - Pasa igual sin irse y con Atrás (2 veces cada uno).
  - Con "Obras" en la barra o "Sectores", sí queda a la vista (la página sube).
  - Plazos no sirve de control: entra entera en la pantalla.
- **Evidencia:** `capturas/A-01-r4b-vista-1/2-permisos-quedarse.png` · `capturas/A-01-r4b-vista-1/2-permisos-atras.png` · `capturas/A-01-r4b-10-atras-500-final.png` · `capturas/A-01-r4b-7-atras-422-final.png` · `PUT /api/permisos` → 500 (forzado)
- **Causa probable (opcional):** en `TablaPermisosPantalla.tsx`, `<Aviso tono="error">` va arriba de la tabla y `enviar` no lleva el foco ni el scroll al aviso cuando falla. `428eb27` no tocó esa parte.
- **Regresión de:** no; arreglo incompleto de A-01 (O-1.1 en caso de falla; sin irse pasa igual)

## Decisiones aprobadas

| decisión | estado | dónde se probó | evidencia | nota |
|---|---|---|---|---|
| O-1.1 · Irse mientras guarda: si guarda bien, lleva al destino; si falla, se queda con el error | ✅ | Tabla de permisos (11 corridas) y editar proveedor con Atrás (6). Además, un caso de regresión en Plazos, Mi perfil y Editar obra ("Proveedores" en la barra, 2,5 s) | `A-01-r4b-*`, `A-02-r4b-*`, `O-1-1-r4b-plazos-1-guardando.png`, `O-1-1-r4b-perfil-1-guardando.png`, `O-1-1-r4b-obra-1-guardando.png` | **Las dos pantallas que fallaban ya andan** (A-01, A-02).<br>**Regresión: nada.** Plazos, Mi perfil y Editar obra muestran "Estamos guardando. Cuando termine te llevamos." y, al 200, llegan a `/proveedores`. En la base: Plazos `2 / 7`, teléfono "291 455-7777", planta "Planta QA4V o11-1". Plazos y el teléfono los volví a dejar como estaban.<br>**Detalle de la falla en Permisos:** con Atrás, el error queda fuera de la vista (**VER-02**, baja). |
| Formato de fecha (dd/mm/aaaa y dd/mm/aaaa HH:MM) | ⚠️ | Historial de Editar obra en 3 husos y borde de día; Usuarios e ingreso trabado en 3 husos; campo de fecha con `--lang=es-AR` | `B-01-r4b-*.png`, `B-02-r4b-1.png` | **Formato:** correcto en todo lo que vi.<br>**Hora del historial:** ya es la de Argentina en cualquier huso (B-01 ✅ en el historial).<br>**Falta:** "Ingreso trabado hasta las…" fuera de Argentina (**VER-01**, media). B-02 queda como observación.<br>**"Qué cambió"** sigue bien: "Planta: Planta QA4V → Planta QA4V v1-208", "Oferta técnica: agregado (oferta 1.pdf)", "Creó la obra". |

## Lo que no pude probar y por qué

- **"Cargado" (fecha de carga del presupuesto) después de las 21:00 de Argentina:** no se pudo probar por la hora de la prueba (09:27–10:12). El arreglo fue solo del front, y el back sigue mandando `created_at` como fecha sola en UTC (`app/Modules/Obras/Http/Resources/PresupuestoResource.php:49`, `toDateString()`, con `config/app.php:70` `'timezone' => 'UTC'`). Si se carga entre las 21:00 y las 24:00, probablemente salga el día siguiente. El front no puede corregirlo porque no le llega la hora. Queda para probar de noche o por el back.
- **Ingreso trabado real:** no quemé códigos (0 ingresos reales). El bloqueo se puso solo en la respuesta de `GET /api/usuarios` con `page.route` (cuerpo real; solo cambié `bloqueo_ingreso` de qa-lead-r4b). "Destrabar ingreso" no se tocó.
- **Borde de día con un cambio real:** no se puede guardar a las 22:30. Se usó la respuesta real del historial con `cuando` cambiado, como pedía la consigna.
- **Conflicto con dos personas:** los 409 de A-01 y A-02 fueron forzados (`fulfill`), no un choque real. El choque real ya lo probó A en la ronda 4 (PU-002).
- **Base caída:** entre ~12:44 y ~13:06 UTC (el Lead avisó una caída cerca de las 12:58 y la recuperación de InnoDB).
  - Las corridas de A-01 (12:38–12:44) terminaron con la base respondiendo: las consultas finales salieron bien.
  - La de A-02 se cortó (`GET /api/me` → 503). La repetí entera cuando `/api/health` volvió a dar `database:true`.
  - Ninguna conclusión sale de ese rato.

---
Firma · VER: re-verifiqué B-01 en 3 husos y en el borde de día (historial ✅; ingreso trabado ❌ → VER-01, media), A-01 ✅ (11 corridas: ok/422/409/500), A-02 ✅ (6 corridas) y B-02 como observación ✅. Regresión de O-1.1 en Plazos, Mi perfil y Editar obra: sin cambios. 2 hallazgos nuevos (VER-01 media, VER-02 baja). Diego, 0 ingresos reales, prefijo QA4V · 2026-11-19.
