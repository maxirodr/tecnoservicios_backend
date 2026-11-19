# Hallazgos · Prueba de usuario Semana 1 · Ronda 5 · Tecnoservicios (NS-2026-025)

- **Fecha:** 2026-11-19 (máquina con Laragon `C:\`)
- **Entorno:** base `tecnoservicios_prueba_usuario` · front `http://localhost:5316` (`staging` @ `d2a4806`, worktree `tecnoservicios_frontend_qa5`) · API `http://localhost:8340` (`staging` @ `6516ba7`, worktree `tecnoservicios_backend_qa5` en `f36c23a` = mismo código + docs)
- **Alcance:** solo lo nuevo de la ronda 5. El estado de los 8 PU verificados (PU-302, PU-305…310 y PU-041) y de la nota del §8 está en `verificacion.md`.
- **Conteo:** 2 hallazgos · bloqueante 0 · alta 0 · media 0 · **baja 2** · **ninguna regresión**.
- **Próximo número libre:** **PU-503**
- **Orden:** por severidad y, dentro de cada una, por rol (A, B, CEL, REG, Lead). Al final de cada **Evidencia**, `origen:` dice de qué borrador sale. Las capturas no se renombraron. Los borradores no se modificaron.

## ID del borrador → PU-5XX

| ID del borrador | Rol | PU nuevo | Severidad final | Estado | Capturas y logs (nombre real) |
|---|---|---|---|---|---|
| B-01 (+ nota de REG en "Ingreso trabado y Destrabar ingreso en PC") | Verificador B, Cazador de regresiones | PU-501 | baja | abierto (pendiente menor, Semana 2) | `B-01-1.png`, `B-01-2.png`, `B-01-3.png`, `REG-ok-r5-usuarios-destrabar.png` · `out-07-2.txt` (scratchpad) · **sin log en `logs/`** |
| CEL-01 | QA celular y accesibilidad | PU-502 | baja | abierto (pendiente menor, Semana 2) | `CEL-01-1.png`, `CEL-01-2.png` · `out-radios-1.txt`, `out-radios-2.txt` (scratchpad) · **sin log en `logs/`** |
| "Ya está cargado: …" una vez (REG, Lead) | Cazador de regresiones | — (observación) | — | no reproducido (1 de 5) | `REG-ok-r5-prov-rapido-*.png` (los 4 intentos que salieron bien) |
| Sesiones viejas en Redis tras el reseteo (REG, Lead) | Cazador de regresiones | — (observación de entorno) | — | efecto del `migrate:fresh` de la base de prueba | — |

## Consolidación (QC)

- **Deduplicado:** REG vio lo mismo que B-01 en PC con otro usuario (uid 11) y ya lo marcó como "no lo duplico". Queda **un solo hallazgo, PU-501**, con las dos fuentes: B 3 de 19 veces, REG 1 de 1.
- **Severidades que sostiene QC (ninguna cambia):**
  - **PU-501 (B-01) queda en baja.** El criterio es si la persona entiende qué pasó.
    - En PU-111 (ronda 2) y en PU-306 (ronda 4), ambas **media**, el guardado **fallaba** y el error quedaba fuera de la vista: la pantalla decía algo distinto de lo que había pasado ("parece que no pasó nada").
    - Acá la acción **sale bien** y la pantalla lo refleja: el aviso "… no puede entrar hasta las …" y el botón "Destrabar ingreso" desaparecen, y la fila de la lista deja de decir "Ingreso trabado" (`B-01-1.png`). No hay ningún mensaje equivocado ni contradictorio. Falta solo la confirmación, y no siempre (3 de 19 en B).
    - El BRIEF define media como "mensaje confuso"; aquí no hay mensaje confuso, hay un aviso de éxito que a veces no llega. Es "detalle de texto": **baja**. Es más leve que PU-308 (ronda 4, baja), donde la pantalla sí mostraba un mensaje que contradecía lo que pasaba.
    - En la ronda 4 lo mismo quedó como nota menor del §8, sin PU. Ahora se reprodujo 4 veces, así que pasa a hallazgo.
  - **PU-502 (CEL-01) queda en baja.** Con teclado se puede usar todo: las flechas cambian la opción y pararse en la opción no marcada no la marca. Es una parada de Tab de más. Los análogos de accesibilidad de rondas anteriores, más graves (el foco salía del diálogo), quedaron en baja: PU-136 (ronda 2) y PU-212 (ronda 3). No es regresión: existe desde `d6da13e`.
- **F-XX normalizados a la numeración de la ronda 4:** B-01 decía "F-60 · W-39"; la Tabla de permisos es F-60 y Usuarios es **F-58 · W-16** (el diálogo "Editar usuario" se abre desde ahí, como en PU-310).
- **Lo "esperado" del §1 no aparece como hallazgo:** B-02 (campo de fecha nativo) sigue como límite conocido; nadie reportó el mismo segundo de PU-002, la ñ, el CUIT ni el historial de un link ya quitado.
- **Observaciones que no son hallazgo:**
  - **"Ya está cargado: QA5R Proveedor Diego D9400. Abrí su ficha."** en vez de "Dimos de alta a …", 1 vez tras un `POST` 201. No se reprodujo en 4 intentos más (a 0, 300 y 600 ms). Sin la segunda reproducción que pide el BRIEF; queda anotado en `reporte.md` §8 para mirar.
  - **Sesiones de la ronda 4 vivas en Redis** que, tras `migrate:fresh --seed`, apuntan a los usuarios nuevos con el mismo id (uid 10 y 11). Es del reseteo de la base de prueba, no de la app; en producción no se resetea. Recomendación para el entorno: borrar las claves de sesión del prefijo de la prueba al resetear.
  - **Texto del 409 en la Tabla de permisos:** "Alguien cambió esto mientras lo tenías abierto. Recargá y volvé a intentar." (en las pantallas de edición dice "Actualizá y revisá."). Es claro y coherente, así que no es hallazgo (§1, último punto).
- **Evidencia:** las capturas citadas existen en `capturas/` (187 archivos). `logs/` está vacía: los `out-*.txt` quedaron en el scratchpad de la sesión (`…\scratchpad\r5\B\out-07-2.txt`, `…\CEL\out-radios-1.txt`, `out-radios-2.txt`, revisados por QC). La consola de `09-destrabar-rep.mjs`, que B cita para 2 de las 3 reproducciones, no se guardó: esas dos tienen solo captura (`B-01-2.png`, `B-01-3.png`).
- Sin datos sensibles: ni códigos de ingreso, ni tokens, ni cookies en los borradores ni acá.

### PU-501 · "Destrabar ingreso": a veces no aparece "Destrabamos el ingreso de …" y el diálogo queda sin ningún aviso
- **Severidad:** baja
- **Tipo:** mensaje
- **F-XX / pantalla:** F-58 · W-16 Usuarios, diálogo "Editar usuario" · `/usuarios`
- **Persona:** Diego (Gerencia de Administración)
- **Pasos:**
  1. Tener un usuario con el ingreso trabado. Acá, `qa-lead-r5bk` con `bloqueo_ingreso.hasta` puesto con `page.route` en `GET /api/usuarios` y `/api/usuarios/12`, sin quemar códigos.
  2. Abrir su diálogo y tocar "Destrabar ingreso". El `DELETE /api/usuarios/12/bloqueo-ingreso` es real (200); desde ese momento la intercepción deja de poner el bloqueo, como pasaría con un destrabe real.
  3. Esperar con el diálogo abierto.
- **Esperado:** "Destrabamos el ingreso de QA Lead R5 BK." a la vista (nota §8 de la ronda 4, arreglada en front `d2a4806`).
- **Obtenido:**
  - El aviso de bloqueo y el botón desaparecen, y el diálogo queda **sin ningún aviso**: ni éxito ni error. La ficha queda bien y la fila ya no dice "Ingreso trabado".
  - Pasó **3 de 19** veces (Madrid 1 de 8, UTC 2 de 11), con la misma secuencia de red que cuando sale bien: `DELETE` 200 y después `GET /usuarios?activo=true&page=1`, `/usuarios/opciones` y `/usuarios/12` → 200. En una, todo terminó a los 827 ms del clic y el aviso no apareció en los ~7 s siguientes.
  - REG lo vio también en PC, con "QA Lead R5 Reg2" (1 de 1).
  - En las 16 que salen bien, hay un instante (~200 ms) sin ningún aviso antes del éxito: es la misma carrera.
- **Evidencia:** `capturas/B-01-1.png` (Madrid) · `capturas/B-01-2.png` · `capturas/B-01-3.png` (UTC) · `capturas/REG-ok-r5-usuarios-destrabar.png` · `DELETE /api/usuarios/12/bloqueo-ingreso` → 200 · `out-07-2.txt` (scratchpad de B: `"Destrabamos el ingreso de QA Lead R5 BK."=0 · aviso de bloqueo=0 · diálogo abierto=1`) · origen: B-01; REG (tabla, "Ingreso trabado y Destrabar ingreso en PC")
- **Causa probable (opcional):** `src/features/identidad/components/BloqueoIngreso.tsx:34-37` pasa `onDestrabado` como `onSuccess` del `mutate`; en TanStack Query 5 ese callback no corre si el componente que hizo el `mutate` ya se desmontó. El `onSuccess` del hook (`useQuitarBloqueoIngreso`, `hooks/useUsuarios.ts:50-52`) espera la invalidación; mientras tanto vuelve `/usuarios/12` sin `bloqueo_ingreso` y `UsuarioFormulario.tsx:143-153` desmonta `BloqueoIngreso`. Pasar el aviso al `onSuccess` del `useMutation`, o llamar al `mutate` desde `UsuarioFormulario`, evitaría depender del componente que se va.
- **Regresión de:** no; arreglo incompleto de la nota §8 de la ronda 4 (front `d2a4806`)

### PU-502 · En los diálogos, Tab se detiene en cada opción de un grupo (Activo / Inactivo, Confirmada / En ejecución) en vez de una sola vez
- **Severidad:** baja
- **Tipo:** accesibilidad
- **F-XX / pantalla:** F-58 · W-16 Usuarios, diálogo "Editar usuario" · `/usuarios`. También F-21 · diálogo "Cambiar estado" de la ficha de obra · `/obras/{id}`
- **Persona:** Diego (Gerencia de Administración)
- **Pasos:** 1. Usuarios, foco en "Carla", Enter. 2. Recorrer el diálogo con Tab. 3. En la ficha de una obra, abrir "Cambiar estado" con Enter y recorrerlo con Tab.
- **Esperado:** cada grupo de opciones (`name="activo"`, `name="estado"`) es **una** sola parada de Tab, en la opción marcada, y se cambia con las flechas, como un grupo de opciones nativo.
- **Obtenido:**
  - **Editar usuario:** Tab pasa por "Activo" (marcada) **y** por "Inactivo" (sin marcar): "→ paradas de Tab en radios (una vuelta): 2".
  - **Cambiar estado:** pasa por "Confirmada" (sin marcar) y por "En ejecución" (marcada). El primer foco del diálogo cae en la opción **no** elegida.
  - Las flechas funcionan (ArrowRight lleva a "Inactivo" y la marca). Pararse con Tab en "Inactivo" no la marca, así que no se pierde nada, pero es una parada de más que un lector anuncia como "Inactivo, botón de opción, no seleccionado".
  - Reproducido 2 veces. En PC se ve igual que en el celular con teclado.
- **Evidencia:** `capturas/CEL-01-1.png` (foco en "Inactivo" sin marcar, Editar usuario · Carla) · `capturas/CEL-01-2.png` (Cambiar estado) · `out-radios-1.txt`, `out-radios-2.txt` (scratchpad de CEL) · origen: CEL-01
- **Causa probable (opcional):** `src/shared/ui/Dialog.tsx:76-87`: la trampa de foco hace `preventDefault()` de todo Tab y salta al siguiente de `enfocables()` (`:38-46`), que incluye cada `input[type=radio]` del grupo, así que el navegador no puede aplicar su regla de "una parada por grupo".
- **Regresión de:** no; existe desde `d6da13e` (la trampa de foco original del diálogo). No es arreglo incompleto de ningún PU.
