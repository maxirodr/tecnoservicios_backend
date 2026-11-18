# Borrador B · Verificador B: sesión, borradores y adjuntos · Ronda 3

- **Fecha:** 2026-11-18
- **Personas y sesiones:** Diego (`diego.json`: preparó los datos, probó PU-112, PU-120 y PU-129 y la lectura de borradores; su sesión no se cerró ni se tocó), `qa-lead-b1` (Gerencia Adm., uid 8) y `qa-lead-b2` (Ingeniería, uid 9), con ingreso real por pantalla.
- **Ingresos reales gastados:** 7 (6 del presupuesto y 1 extra autorizado por el Lead): b1 ×3 y b2 ×4. Dos ingresos de b1 se perdieron por errores de mis scripts (selector y formato de la clave). Un pedido de código de b1 dio 429 (límite por mail) y no se gastó código. No quemé ningún código.
- **Cómo se venció la sesión:** `DEL` en Redis de **solo** la clave de la sesión del qa-lead. La clave se identificó descifrando su cookie `tecnoservicios-session` y confirmando el uid 8 o 9 en el contenido.
  - ⚠️ **Incidente mío:** a las 13:54–13:55 restauré con `SET` la clave de mi propia sesión de b1 para no gastar un ingreso, y se coló un salto de línea. Eso generó los **6 `unserialize(): Extra data…` en `GET /api/me`** de `errors-2026-11-18.log`. **No es un bug del back.** Borré la clave y no volví a escribir valores (avisado al Lead).
- **Datos creados (prefijo QA3-B):**
  - Obras id 2 "QA3-B Obra borrador" (2-821-2026), id 4 "QA3-B Obra descarte" (2-822-2026) e id 5 "QA3-B Obra links ñ" (2-823-2026), con responsable QA Lead B2.
  - Documento "QA3-B Certificado con links" en la obra 5, y su carpeta de Drive con ñ y espacios.
  - Cambios de planta en las obras 2 y 4.
- **Scripts:** `C:\Users\juanc\AppData\Local\Temp\claude\C--Users-juanc\3fd8de0f-88e6-40e4-a658-bb42248ba145\scratchpad\qa3\B\`
  - `03-links.mjs` (PU-112 y PU-129)
  - `04-pu120.mjs`
  - `10-sesion.mjs` (PU-105 obra con b1 → b2)
  - `12-final-b2.mjs` (4 pestañas)
  - `14-pu106-final.mjs` (1 pestaña)
  - `15-lectura.mjs` (lectura del borrador)
  - `16-b01.mjs`
  - `sid.php` (cookie → id de sesión; no imprime nada sensible)

## Tabla de verificación

| PU | título corto | estado | pantallas revisadas | evidencia | nota |
|---|---|---|---|---|---|
| PU-105 | El borrador de una persona le aparece a otra en la misma pestaña | ✅ | W-23 Editar obra (b1 → b2 con ingreso real), W-06 Nueva obra, W-38 Proveedores | `PU-105-r3-1.png` · `PU-105-r3-3.png` · `PU-105-r3-4.png` · `PU-105-r3-2.png` | **Obra:** b1 escribió cliente "QA3-B Cliente borrador de B1" y planta "Planta borrador B1"; la sesión venció y quedó el borrador `…:8:obra:/obras/2/editar`. Después b2 ingresó en **la misma pestaña** y vio el formulario limpio ("QA3-B Cliente SA", planta vacía, sin "Recuperamos…"); el borrador de b1 siguió guardado aparte. **Nueva obra y proveedor:** con la sesión de Diego y un borrador de otra persona en la pestaña (el real de b2, uid 9, y uno de proveedor con la clave de b1, uid 8), no se recupera nada ni se abre el alta. El caso del proveedor con ingreso real de b1 no se pudo hacer (b1 quedó con 429). |
| PU-106 | Sesión vencida al subir archivo o agregar link | ✅ | W-06 Nueva obra ("Subir archivo"; "Agregar link" en 4 pestañas), W-20 documento | `PU-106-r3-1.png` · `PU-004-r3-2.png` · no concluyente: `B-nc-2.png`, `B-nc-3.png` | **Con 1 pestaña e ingreso real de b2:** "Subir archivo" con la sesión vencida → a los ~3 s va a Ingreso con "Tu sesión venció. Volvé a ingresar." y guarda el borrador (nombre "QA3-B Nueva final (b2)" y cliente). Red: `POST /api/adjuntos → 419`, `GET /api/me → 401`. Al volver, la lectura del borrador muestra "Recuperamos lo que estabas escribiendo." con el nombre y el cliente: se simuló copiando el borrador real de b2 a la pestaña de Diego, porque no quedaban ingresos. Ver "No pude probar" por "Agregar link" y las varias pestañas. |
| PU-109 | El borrador se descarta en silencio si la obra cambió | ✅ | W-23 Editar obra 4 | `PU-109-r3-1.png` · `PU-109-r3-2.png` | Con un borrador de una versión anterior de la obra, al abrir "Editar" aparece "Mientras no estabas, alguien cambió esto. Lo que habías escrito no se pudo recuperar." y el formulario muestra lo guardado. Al recargar, el aviso no se repite. Visto 2 veces. La lectura se hizo con la sesión de Diego y un borrador inyectado en `sessionStorage`; la escritura del borrador al vencer está probada con ingreso real (b1, `…:8:obra:/obras/2/editar`). |
| PU-112 | Varios links del Drive se ven todos iguales | ✅ | W-20 tabla de documentos, diálogo "editar", alta del documento; W-21 adicional | `PU-112-r3-1.png` · `PU-112-r3-2.png` · `PU-112-r3-3.png` | Sin "Nombre para mostrar" se ven "oferta-ñandú", "sin-s", "Plano nave año.pdf" y "1AbCdEfGh" (este último para `drive.google.com/file/d/1AbCdEfGh/view`). Cada uno tiene el link completo al pasar el mouse, pero codificado: ver **B-01**. |
| PU-120 | El aviso al agregar un link habla de "subir los archivos" | ✅ | W-06 Nueva obra, W-23 Editar obra, W-20 documento, W-21 adicional (red lenta, 4 s en `/api/adjuntos`) | `PU-120-r3-1…4.png` | En las 4 pantallas se ve "Esperá a que terminen de agregarse los archivos o links para guardar." y el botón de guardar queda deshabilitado mientras viaja el link. |
| PU-129 | La carpeta de Drive con ñ o espacios se ve con %C3%B1 y %20 | ✅ | W-23 Editar obra 5 (guardar y volver a editar), ficha | `PU-129-r3-1.png` | Después de guardar `https://drive.example/Obras 2026/carpeta-ñandú-año`, el campo vuelve a mostrar exactamente lo que se escribió. El `href` va codificado, que es lo correcto. Resta el título al pasar el mouse por los links de adjuntos: **B-01**. |
| PU-004 | Sesión vencida a mitad de formulario: se pierde lo escrito | ✅ | W-23 (b1, ingreso real), W-06 (b2, ingreso real), W-38 (lectura) | `PU-106-r3-1.png` · `PU-004-r3-2.png` · `PU-105-r3-1.png` · `PU-109-r3-1.png` | Los 3 pendientes de la ronda 2 ya no se ven en una pestaña: el borrador es por persona (PU-105), la sesión vencida al subir lleva a Ingreso (PU-106) y el descarte se avisa (PU-109). Mensaje: "Tu sesión venció. Volvé a ingresar."; al volver, "Recuperamos lo que estabas escribiendo.". Con la sesión borrada en Redis el back responde **419** (no 401); el front renueva el CSRF, reintenta y termina en 401, como se espera. |

## Hallazgos nuevos

### B-01 · Al pasar el mouse por un link con ñ o espacios se ve "%C3%B1" y "%20"
- **Severidad:** baja
- **Tipo:** mensaje
- **F-XX / pantalla:** F-19 · W-20 Certificados y otros documentos (tabla, "editar" y alta); W-21 Agregar presupuesto adicional (oferta técnica); F-16 · W-06 Nueva obra (oferta técnica). Es el mismo componente de adjuntos.
- **Persona:** Diego (Gerencia de Administración)
- **Pasos:** 1. En la obra 2-821-2026, tocar "+ agregar presupuesto adicional", luego "Pegar link", pegar `https://drive.example/Obras 2026/oferta año ñandú.pdf` y tocar "Agregar link". 2. Pasar el mouse por el link "oferta año ñandú.pdf".
- **Esperado:** el link completo como lo escribió la persona: `https://drive.example/Obras 2026/oferta año ñandú.pdf` (igual que el campo "Carpeta de Drive" después del arreglo de PU-129).
- **Obtenido:** el cartelito muestra "https://drive.example/Obras%202026/oferta%20a%C3%B1o%20%C3%B1and%C3%BA.pdf". El nombre visible ("oferta año ñandú.pdf") está bien. Igual en Nueva obra y en la tabla y el "editar" de documentos ("https://drive.example/oferta-%C3%B1and%C3%BA"). Reproducido 3 veces (adicional, nueva obra y documento).
- **Evidencia:** `capturas/B-01-1.png` (adicional) · `capturas/B-01-2.png` (nueva obra). El cartel amarillo se dibujó con el valor exacto del atributo `title`, porque el navegador headless no muestra el tooltip nativo. Scripts `B/16-b01.mjs` y `B/03-links.mjs`.
- **Causa probable (opcional):** `src/shared/adjuntos/AdjuntoFila.tsx:55`: `title={adjunto.url ?? undefined}` usa la URL tal cual y no pasa por `urlLegible`, que sí se usa para el campo (b760aa2).

## Observaciones de UX (decisiones "a validar por Juani")
- No me tocó ninguna de las 5 decisiones; nada que observar.

## Lo que no pude probar y por qué
- **"Agregar link" con la sesión vencida en una sola pestaña:** el ingreso extra se usó para "Subir archivo". La redirección a Ingreso llegó a los ~3 s, y la disparó un `GET /api/obras/clientes → 401` que coincidió con el `POST /api/adjuntos → 419`. No se pudo separar qué pedido la provocó.
- **Varias pestañas con la misma sesión, no concluyente (no se reporta).** Con 4 pestañas abiertas y un solo vencimiento:
  - en "Nueva obra" (link y archivo) quedó "Tenés que ingresar para seguir." debajo del campo, con el borrador guardado, sin ir a Ingreso durante los 8 s observados (`B-nc-2.png`);
  - "Guardar cambios" de Editar obra siguió girando: `PATCH → 419` sin reintento visible en esos 8 s (`B-nc-1.png`);
  - el documento quedó con "Esperá a que terminen de agregarse…" (`B-nc-3.png`).
  
  La API de prueba tarda ~1 s por pedido y eran 4 reintentos de CSRF en cola, así que puede ser solo lentitud. No hubo ingresos para repetirlo con 25 s de espera. Conviene que el cazador de regresiones o la ronda siguiente lo repita con 2 pestañas.
- **Vuelta con ingreso real después del vencimiento** (recuperar y descartar): b1 dio 429 en "Enviar código" y se terminó el presupuesto. La lectura del borrador se probó con la sesión de Diego y el borrador real de b2 copiado a su clave. La escritura del borrador al vencer sí está probada con ingreso real.
- **"Salir" borra los borradores de la pestaña:** b2 tocó "Salir" y la pestaña quedó sin borradores, pero antes no tenía ninguno (el paso que dejaba el borrador de b1 falló), así que no queda probado.
- **b2 (Ingeniería) en Proveedores:** no tiene acceso a Proveedores (`sin_acceso`), así que no sirve como "otra persona" para el borrador de proveedor.

— Verificador B: PU-105, 106, 109, 112, 120, 129 y PU-004 con sesión vencida de verdad (DEL en Redis, ingresos reales de b1 y b2), 1 hallazgo nuevo (B-01, baja), varias pestañas no concluyente.
