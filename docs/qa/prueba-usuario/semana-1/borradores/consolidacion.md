# Consolidación QC · Prueba de usuario Semana 1 (2026-11-16)

Fuente: borradores `adm.md`, `obr.md`, `prv.md`, `ent.md`, `uxm.md`, `lead.md` y `capturas\`. Resultado: `..\hallazgos.md` (PU-001 … PU-044; el próximo número libre es **PU-045**, reservado para el hallazgo del Lead sobre el bloqueo progresivo del ingreso).

Criterio de estado: ✅ sin hallazgos · ⚠️ con hallazgos alta, media o baja · ❌ con al menos un bloqueante.

## (a) Cobertura unificada

| F-XX | Estado | Hallazgos | Quién lo cubrió y qué quedó |
|---|---|---|---|
| F-01 Ingreso con mail + código | ⚠️ | PU-004, PU-016, PU-021, PU-045 | LEAD: mail mal formado, mail inexistente y usuario dado de baja (misma respuesta, no sale mail), código equivocado, con letras, pegado con espacios (entra), vencido (11,6 min), ya usado y quemado tras 5 intentos, más de 10 intentos/min ("Demasiados intentos. Probá de nuevo en un minuto."), reenviar con cuenta regresiva, dos dispositivos, salir. UXM: pantalla de ingreso en celular y teclado. |
| F-02 Menú según el sector | ⚠️ | PU-014 | ADM (Diego, Dora, Laura, Ana; celular "Más"), PRV (Carla, Laura, Pablo, Hernán), UXM (barra PC y celular, B-03). URL directa sin permiso: mensaje claro, sin 403 crudo. |
| F-03 Buscador general | ⚠️ | PU-009, PU-017, PU-044 | OBR (N.º con y sin guiones, sin tildes, terminadas marcadas), PRV (permisos por grupo, mínimo 2 caracteres, `<script>` como texto), ADM (alcance "solo sus obras"), UXM (loader, celular). |
| F-10 Mi perfil | ⚠️ | PU-006, PU-018, PU-029 | ADM (Dora, Diego, Laura; mail/sector solo lectura, `PATCH /api/me` rechaza), ENT (vacío, largos, F5), UXM (guardar con red lenta, celular, teclado). |
| F-14 Registro de quién hizo qué | ⚠️ | PU-032 | OBR (quién/qué/cuándo, orden, B-05, montos solo para Diego), ADM (`actividades` de usuarios, sectores y permisos en la base), PRV (proveedores en la base; sin pantalla, no exigible). PU-002 hace además que el historial atribuya el pisado a quien guardó último. |
| F-15 Listado de obras por estado | ⚠️ | PU-017, PU-023, PU-040, PU-042 | OBR (pestañas, contadores, vacíos, orden, volver desde la ficha), ADM (alcance "solo sus obras"), UXM (loader, celular, teclado). |
| F-16 Alta de obra | ❌ | PU-001, PU-006, PU-007, PU-012, PU-014, PU-018 | ENT (vacío, espacios, largos, emojis/HTML, mails, fechas, carpeta `javascript:`, doble clic, F5, atrás, Cancelar), OBR (alta como Hernán, normalización del N.º), UXM (alta en PC, Pixel 7 e iPhone SE 375). |
| F-17 Ficha de la obra | ⚠️ | PU-041, PU-042 | OBR (botones según quién mira, 404 claros), ENT (datos raros como texto), UXM (celular, B-01; teclado), ADM (ficha ajena con "solo sus obras" → "Obra no encontrada"). |
| F-18 Presupuestos (principal y adicionales) | ⚠️ | PU-019 (PU-001 aplica a W-21 por código, sin reproducir ahí) | ENT (N.º vacío, mal escrito, repetido, año fuera de rango), OBR (adicional, quitar, monto de la obra solo Diego), UXM (abrir con red lenta). |
| F-19 Documentos y contactos | ❌ | PU-001, PU-003, PU-005, PU-010, PU-011, PU-012, PU-018, PU-026, PU-027, PU-028, PU-030 | ENT (0 B, 20/21 MB, .exe, .exe→.pdf, .html, .svg, acentos, 18 variantes de link, descarga con nombre), OBR (certificado vacío, archivo + link, cambiar archivo por link, quitar, 403 de Carla, Drive solo link), UXM (guardar con red lenta, Cancelar a mitad). |
| F-20 Editar la obra | ❌ | PU-002, PU-006, PU-018, PU-029, PU-033, PU-037 | OBR (errores en el campo, revisión, OC, doble clic, F5, 422 sin guardado a medias), ENT (sin cambios, vaciar, dos pestañas), UXM (carga, CLS, celular). |
| F-21 Cambiar estado | ⚠️ | PU-003, PU-018 | OBR (ciclo completo, saltos de estado, permisos por responsable y por función), ENT (doble clic, API), UXM (red lenta, Cancelar a mitad, celular). |
| F-22 Obras terminadas consultables | ✅ | — | OBR (pestaña Terminadas, buscable, editable según RN-OBR-25). |
| F-23 Fechas previstas | ⚠️ | PU-032 | ENT (al revés, mismo día), OBR (edición; el historial las muestra en formato técnico). |
| F-48 Presupuesto por rubro | ⚠️ | PU-014, PU-015, PU-018, PU-022 | OBR (12 rubros, coma decimal, negativo, solo el total, permisos), ENT (16 variantes de monto, doble clic), ADM ("Ver · sus obras"), UXM (red lenta, celular). |
| F-55 Proveedores con buscador | ⚠️ | PU-009, PU-017, PU-024, PU-044 | PRV (orden, genérico, búsqueda sin mayúsculas/tildes, ids raros, celular Pixel 7), UXM (loader, celular). |
| F-56 Alta y edición de proveedor | ⚠️ | PU-004, PU-013, PU-018, PU-025, PU-029, PU-034, PU-035, PU-036, PU-038, PU-043 | PRV (CUIT y dígito verificador, repetidos, mail, doble clic, F5, genérico no editable, 150/151), ENT (validaciones, sin cambios), UXM (red lenta, celular), LEAD (sesión vencida y API caída en el alta). |
| F-58 Usuarios: alta, edición y baja | ⚠️ | PU-017, PU-022, PU-029, PU-040 | ADM (alta, validaciones, mail repetido e inactivo, baja/reactivación, uno mismo, 404, sin mail al dar de alta), ENT (validaciones), UXM (loader, celular). |
| F-59 Sectores | ⚠️ | PU-008, PU-018, PU-031 | ADM (alta, repetidos, renombrar, sacar con y sin usuarios, permisos iniciales RN-IDE-30), ENT (validaciones, doble clic del alta). |
| F-60 Tabla de permisos | ⚠️ | PU-006, PU-008, PU-018, PU-022 | ADM (11 × 12 contra identidad.md §6.3: coincide 100 %; impacto real con Ana; API con valores inválidos), UXM (red lenta, celular). |
| F-61 Jerarquía ("depende de") | ✅ | — | ADM (círculo directo e indirecto, sí mismo, jefe inactivo o inexistente, Mi perfil). |
| F-68 Plazos de la prioridad | ✅ | — | ADM (validaciones, 0/5, F5, solo lectura para Ver), ENT (validaciones sin guardar), UXM (celular). Quedaron en 1 / 7. |
| Adjuntos (ADR-014) | ❌ | PU-001, PU-010, PU-011, PU-012, PU-026, PU-027, PU-030 | 20 MB entra, 21 MB "El archivo pesa 21 MB y el máximo es 20 MB." (front y API); tipos no permitidos; nombre con acentos al descargar; Drive solo link. |
| Cerrar sesión | ✅ | — | LEAD: "Salir" desde el avatar; después `/obras` lleva a ingresar; en otro dispositivo la sesión sigue. |
| Sesión vencida | ⚠️ | PU-004 | LEAD (simulada borrando cookies). Con Redis caído se pierden todas las sesiones (PU-020). |
| 403 / 404 | ⚠️ | PU-005, PU-014, PU-024 | Todos los roles: "No tenés acceso a esta sección…", "Obra no encontrada…", "Proveedor no encontrado…", "No encontramos esta página…"; API 403/404 en español. |
| Celular | ⚠️ | PU-022, PU-040, PU-041 | UXM (Pixel 7 412 px e iPhone SE 375×667, emulados), ADM, PRV y OBR (Pixel 7). B-01 arreglado. |
| Accesibilidad | ⚠️ | PU-042 | UXM: "Saltar al contenido", foco visible, foco atrapado en diálogos, errores con `aria-invalid`/`aria-describedby`, labels, contraste en 4 pantallas. |
| Loaders | ⚠️ | PU-017, PU-018, PU-019, PU-037 | UXM con demoras de 2,5, 8 y 15 s. Navegación sin spinner de página completa (bien). |
| Mails y cola | ⚠️ | PU-021 | LEAD/ADM: un mail por pedido, en español, por la cola `mail`, 1 por segundo con `--rest=1`; alta de usuario no manda mail (RN-IDE-09); worker parado y relanzado. |
| Scheduler | ✅ | — | LEAD: `adjuntos:purgar-huerfanos` e `idempotencia:purgar` sin error; `schedule:list` cada hora. (Murió durante el corte de Redis: PU-020.) |
| Errores de servidor | ⚠️ | PU-005, PU-020, PU-038, PU-039 | LEAD: API cortada en un alta → "No pudimos conectarnos con el sistema. Revisá tu conexión y volvé a intentar.", lo escrito se mantiene y se recupera sin recargar; Redis caído (PU-020). |

## (b) No probado (unificado)

| Qué | Por qué | Rol |
|---|---|---|
| Guarda del último administrador (RN-IDE-25) por baja, cambio de sector o de matriz | Exige dejar 0 administradores tocando a Diego o las filas del seeder que usan los demás; solo se probó "no te podés desactivar a vos mismo". Queda para una base propia. | ADM |
| Refresco del menú al volver a enfocar la pestaña (RN-IDE-14) | El evento de foco simulado del navegador sin pantalla no disparó el refresco; con F5 sí. No concluyente. | ADM |
| Dos pestañas en usuario, proveedor, adicional y documentos | Falta de tiempo (corte de MySQL/Redis). Probablemente el mismo problema de PU-002, que solo se reprodujo en la obra. | ADM, ENT, PRV |
| Link escrito sin "Agregar link" en el campo opcional de la OC | Deducido del código y de PU-001; no reproducido aparte (ver PU-012). | ENT |
| 413 del backend con un PHP de 8 MB (B-04 en el entorno del cliente) | El entorno tiene 2G de límite y no se tocó `php.ini`. | ENT |
| Celular real (Safari/WebKit, teclado virtual, cámara/galería) | Solo Chromium emulando Pixel 7 e iPhone SE; el teclado se dedujo de `type`/`inputmode`. PRV probó solo Pixel 7. | UXM, PRV |
| Lector de pantalla real (NVDA/VoiceOver) y contraste en hover/foco y en Usuarios | Solo atributos y teclado; contraste automático en Obras, Alta, Ficha y Proveedores. | UXM |
| Carga de pedidos en obra Terminada, pestañas Pedidos y Rendición | Son de las semanas 2 y 3 (correctamente ocultas). | OBR |
| Presupuesto por rubro y cambio de estado válido como Carla | Para no tocar obras de otros testers; por código el cambio de estado lo corta con 403. | PRV |
| Loaders y celular específicos de F-14, F-22, F-23 y F-61 | Fuera del alcance de tiempo de UXM; solo lo que se ve en ficha y edición. | UXM |
| MySQL caído por separado | El corte de MySQL coincidió con el de Redis y el canal `errors` no tiene entradas SQLSTATE: el comportamiento ante MySQL caído solo no quedó verificado. | LEAD |
| (cubierto) Bloqueo progresivo del ingreso | Probado por el Lead: PU-045 (distinto de PU-016). | LEAD |

Nota: el alcance "solo sus obras" en la función Obras, que OBR dejó como no probado, sí lo cubrió ADM con un sector QA-ADM (ver F-15/F-17 en la tabla).

## (c) Observaciones (no son bug)

1. **Responsables posibles (OBR OBS-1):** el filtro "Responsable" y el desplegable del alta/edición listan a todos los usuarios activos, como dice `obras.md` §4.1. Con ~50 personas va a ser largo; convendría filtrar por quienes tienen obras y mostrar primero a operaciones. El caso con alcance "solo sus obras" sí es hallazgo: PU-023.
2. **"Documento de aprobación" sin opción vacía (OBR OBS-2):** arranca en "Orden de compra" aunque diga "(opcional: se puede cargar después)"; coincide con RN-OBR-16 y si no se carga número ni archivo no se guarda nada.
3. **Ingeniería con Obras = Editar · todas (OBR OBS-3):** es la configuración de `identidad.md` §6.3; ver y editar obras ajenas es correcto. El alcance "solo sus obras" se verificó en Presupuesto por rubro (OBR) y en Obras (ADM).
4. **Mensajes del N.º de presupuesto repetido o mal escrito (OBR OBS-5 + nota UX de ENT):** el repetido muestra el aviso dos veces (arriba con link y en el campo) y pone el nombre entre comillas simples ('…'), que el resto del sistema no usa; "2-80123-2026" responde "Eso parecen dos presupuestos…", cuando para un error de tipeo sirve más "Revisá el número: tiene que ser como 2-131-2026.".
5. **Historial del alta de documentos desparejo (OBR OBS-6):** el "Certificado N.º 1" dejó la línea "Archivo o link: agregado (…)" y el N.º 2 no. El N.º 2 es el documento de la prueba de carrera de PU-001; conviene revisarlo al arreglar PU-001.
6. **El diálogo de estado ofrece el estado actual (OBR OBS-7):** "Confirmada" en una obra Confirmada; la API responde "La obra ya está Terminada." y no pasa nada malo.
7. **Mail del código sin link al sistema (ADM, LEAD):** la spec no lo pide.
8. **Textos genéricos de la API que la pantalla no muestra hoy (ENT):** "Completá este campo.", "El valor elegido en estado no es válido.", "No existe o no está disponible.". Anotados por si cambia el front.
9. **Diferencia W-15 vs §6.3 en Ingeniería (Gastos y Presupuesto por rubro "Ver · sus obras" vs "Ver") (ADM):** es la duda DU-A04 resuelta.
10. **Tabla de permisos en PC (1.712 px):** el scroll queda dentro de su marco, sin scroll de página (ADM). En celular sí es hallazgo: PU-022.
11. **Entorno — comando del worker del prompt (LEAD):** `queue:work --tries=3` solo escucha `default`; los mails van por `mail` (como `config/supervisor.php`). Se corrigió en el entorno; actualizar el prompt.
12. **Entorno — límite de 20 pedidos de código por IP por hora (LEAD):** con ~50 personas detrás de una misma IP pública, tras una pérdida masiva de sesiones (PU-020) bloquearía a la mayoría durante una hora. Revisar con el cliente.
13. **Entorno — `devices['iPhone SE']` de Playwright es 320×568 (UXM, LEAD):** UXM lo forzó a 375×667; las capturas `*-iphonese.png` de otros roles pueden ser de 320 px. Corregir el helper común.
14. **Entorno — corte de MySQL y Redis 14:49–15:55 UTC y MySQL cargado por otra suite (todos):** lo que falló en el corte se repitió después; la lentitud de base no se tomó como hallazgo (sí en PU-001 porque pierde un dato). El mail de Dora salió ~5 min tarde porque el worker se estaba levantando.
15. **"Guardar sin cambios" (OBR OBS-4)** dejó de ser observación: se promovió a hallazgo junto con ENT-10 (PU-029).

## (d) Estado de B-01 … B-05 (`docs\qc\entrega-1.md`)

| Bug | Estado | Verificó | Cómo |
|---|---|---|---|
| B-01 Ficha de la obra en el celular | ✅ arreglado | OBR, UXM | Pixel 7 e iPhone SE 375: tablas en tarjetas, "editar"/"quitar" dentro de la pantalla, nombre del archivo entero, sin desplazamiento horizontal (`capturas/F-17-ficha-obra-celular-pixel7.png`, `F-17-ficha-obra-celular-iphonese.png`). |
| B-02 Códigos internos "(W-xx)" | ✅ arreglado | ENT, OBR | Sin W-/F-/RN-/DU-/HU- en Mi perfil, Agregar/Editar usuario, Sectores, Pedidos/Avisos, listado, alta, ficha, editar, W-13 y buscador (PC y celular). |
| B-03 Secciones no entregadas | ✅ arreglado | ENT, OBR, UXM | Sin campana; barra del celular "Obras · Más"; `/avisos` y `/pedidos` a mano: "Esta sección todavía no está disponible / Va a estar disponible en una próxima entrega.". |
| B-04 Corte en 8 MB | ✅ arreglado en este entorno · no verificado el 413 | ENT | 20 MB entra (~14 s); 21 MB lo frena el front y el backend con "El archivo pesa 21 MB y el máximo es 20 MB.". El 413 con un PHP de 8 MB no se probó (entorno con 2G); sigue la condición del entorno del cliente (≥ 21 MB). |
| B-05 Importes del historial | ✅ arreglado | ENT, OBR | "MATERIALES: (vacío) → 10.900.000", "HORAS LIQUIDADAS: (vacío) → 9.999.999.999.999,99". Las fechas del historial siguen en formato técnico, pero eso es un hallazgo nuevo (PU-032), no B-05. |

## (e) Conteo

**Por severidad (45):** bloqueante 2 · alta 3 · media 18 · baja 22.

**Por tipo:**

| Tipo | Bloqueante | Alta | Media | Baja | Total |
|---|---|---|---|---|---|
| funcional | 2 | 2 | 4 | 3 | 11 |
| permisos | — | — | — | — | 0 |
| validación | — | — | — | 3 | 3 |
| mensaje | — | — | 8 | 8 | 16 |
| loader | — | — | 3 | 1 | 4 |
| error-servidor | — | 1 | 1 | 2 | 4 |
| cola-mail | — | — | 1 | — | 1 |
| scheduler | — | — | — | — | 0 |
| celular | — | — | 1 | 2 | 3 |
| accesibilidad | — | — | — | 1 | 1 |
| consistencia-con-documento | — | — | — | 2 | 2 |
| **Total** | **2** | **3** | **18** | **22** | **45** |

(Incluye PU-045, sumado por el Lead.)

## (f) Firma de QC

**QC final** · 2026-11-16 · consolidó 50 entradas de borrador (ADM-01…05, OBR-01…06, PRV-01…10, ENT-01…14, UXM-01…09, LEAD-01…06) en 44 hallazgos.

**Fusiones (duplicados):**
- ENT-01 + OBR-01 → PU-001 (alta de obra/OC, documentos y, por código, adicional y editar).
- ENT-07 + OBR-02 → PU-002.
- OBR-05 + PRV-10 → PU-014.
- ADM-01 + ADM-02 → PU-008.
- ENT-08 + ADM-03 → PU-006: es el mismo problema (ningún formulario con cambios sin guardar avisa antes de salir o recargar; falta `useBlocker`/`beforeunload`), con el mismo arreglo.
- OBR-03 + LEAD-05 → PU-005 (ENT también lo vio en el log). LEAD-04 queda aparte (PU-020).
- Encontrados además: ENT-10 + observación OBS-4 de OBR → PU-029; la parte del "doble punto" de ENT-11 → PU-034 (con PRV-01); observación OBS-5 de OBR + nota UX de ENT sobre el N.º repetido → una sola observación.

**Cambios de severidad (según §5):**
- PU-001 (ENT-01 alta, OBR-01 bloqueante) → **bloqueante**: pierde datos.
- PU-002 (ENT-07 y OBR-02, alta) → **bloqueante**: pisa sin aviso un dato ya guardado por otra persona; §5 define bloqueante como "pierde datos".
- PU-005 (OBR-03, media) → **alta**: es un 500 visible en el navegador ("El sistema tuvo un problema…"); §5 pone el 500 visible en alta.
- PU-014 (OBR-05 y PRV-10, baja) → **media**: el texto contradice lo que la persona ve; §5 pone el mensaje confuso en media.
- PU-015 (ENT-13, baja) → **media**: mensaje confuso ("con números" a quien escribió números), igual que ENT-02/ENT-03, que su autor ya puso en media.
- PU-008: ADM-02 (baja) se absorbe en la severidad de ADM-01 (media).

**Evidencia y capturas:** se copiaron 73 capturas a `capturas\PU-XXX-n.png`; las originales quedan intactas. Ninguna captura citada faltaba. Pero:
- `ENT-9-dos-pestanas.png` (citada por ENT-07) es idéntica byte a byte a `ENT-8-guardar-sin-cambios.png` y muestra la ficha, no el pisado: no se usó en PU-002, que se apoya en `OBR-14` y en la base.
- PU-029-1 (`ENT-8-guardar-sin-cambios.png`) muestra la ficha sin el aviso "Guardamos los cambios…" a la vista: evidencia débil (el texto está en el borrador).
- Sin captura (evidencia por request, log o script): PU-005, PU-010, PU-020, PU-025, PU-026, PU-031, PU-038, PU-039, PU-042, PU-043.

**Datos sensibles:** revisados `hallazgos.md` y `logs\`: sin códigos de ingreso de 6 dígitos, tokens ni cookies, y solo datos ficticios (empresa-ejemplo.com, QA-…). El único código de ejemplo de los borradores (el pegado con espacios en `lead.md`, que aquí se escribe `### ###`) no pasó a los reportes. No hubo que editar `logs\`.

**Lead:** PU-045 sumado (no se superpone con PU-016: uno es el límite por cantidad de pedidos, el otro el bloqueo silencioso por códigos quemados). Conteos actualizados.
