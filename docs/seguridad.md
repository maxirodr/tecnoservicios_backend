# Seguridad — Etapa 1

> Rol: Tech Lead / Seguridad. Estado: **revisión de cierre de semana 1** (2026-11-13) sobre `main`
> (`fa211b0`) del backend y `main` del front. Decisiones de base: ADR-006 (permisos por matriz),
> ADR-009/015 (autenticación), ADR-011 (idempotencia), ADR-014 (adjuntos). Resumen en `arquitectura.md` §7.
> Este documento se actualiza en cada revisión: los hallazgos cambian de estado, no se borran.

## 1. Modelo de amenazas

**Qué se protege**

| Activo | Por qué importa |
|---|---|
| Importes (presupuestos, `monto_obra`, gastos, rendición) | Información comercial del cliente; `monto_obra` y la rendición se ven solo con permiso |
| Datos personales | Mails, teléfonos de empleados y de contactos de obra; CUIT de proveedores |
| Adjuntos | Ofertas técnicas, OC, certificados, facturas: documentos del cliente en disco privado |
| La matriz de permisos | Quien edita `usuarios_permisos` controla todo el sistema |
| La sesión | Dura 90 días desde el último uso (ADR-015): una cookie robada vale meses |
| El buzón de mail | Sin contraseñas, quien lee el mail de una persona entra como ella (ADR-015) |

**Quién puede atacar**

- **Usuario interno curioso**: tiene sesión válida y quiere ver lo que su sector no ve (montos, obras de otros).
  Es el actor más probable con ≈13 usuarios. Defensa: matriz en cada endpoint + alcance + IDOR → 404.
- **Externo sin cuenta**: conoce la URL de la API y algún mail de la empresa. Puede intentar adivinar el
  código, enumerar cuentas, inundar de mails o abusar de los endpoints públicos (`/api/health`, `/api/errors`).
- **Ex empleado**: conserva una sesión abierta en su celular. Defensa: desactivarlo corta la sesión en el próximo pedido.
- **Sitio malicioso abierto en el navegador de un usuario**: CSRF, XSS por un link guardado, clickjacking.

**Superficie y amenazas principales (STRIDE resumido)**

| Superficie | Amenaza | Control (sección 2) |
|---|---|---|
| `POST /api/auth/pedir-codigo` | Enumeración de cuentas, bombardeo de mails | Respuesta única, throttle por mail y por IP |
| `POST /api/auth/ingresar` | Fuerza bruta del código de 6 dígitos | 5 fallos invalidan el código, throttle; **ver SEG-01** |
| Cookie de sesión | Robo, fijación, CSRF | HttpOnly, SameSite=Lax, Secure en prod, regenerar al entrar, CSRF de Sanctum |
| Endpoints por ID | IDOR (ver la obra de otro, bajar su adjunto) | `BuscarObra::visible` → 404; autorizador por contexto en adjuntos |
| `POST /api/adjuntos` | Subir HTML/SVG/ejecutables, path traversal, llenar el disco | Tipo por contenido, nombre UUID, 20 MB, throttle, purga a 24 h |
| `GET /api/adjuntos/{id}` | XSS servido desde el dominio de la API | `attachment` + `nosniff` + MIME detectado; nunca desde `public/` |
| Links guardados (adjuntos, carpeta de Drive) | `javascript:` en un `href` | Backend solo acepta http(s); **front: ver FE-01** |
| Logs y `/api/errors` | Fuga de datos personales, códigos o del token | Se excluye `codigo`; token con ban; **ver SEG-02, SEG-08** |
| Proxy / IP | IP falsificada para esquivar límites por IP | **Ver SEG-04** |

## 2. Controles implementados (evidencia)

| Control | Implementación | Test |
|---|---|---|
| Autorización por matriz en cada endpoint | Middleware `permiso:<funcion>,<nivel>` (`Identidad/Http/Middleware/VerificarPermiso.php`) + `Permisos::puede/alcance` (`Identidad/Infrastructure/MatrizPermisos.php`, sin cache: un cambio vale desde el próximo pedido) | `TablaDePermisosTest`, `PerfilTest::test_cambio_en_la_matriz_vale_desde_el_proximo_pedido`, tabla de la sección 3 |
| Ningún rol en el código | Nada compara nombres de sector | `tests/Architecture/PermisosPorMatrizTest::test_ninguna_regla_pregunta_por_el_nombre_de_un_sector` |
| Alcance "solo sus obras" + IDOR → 404 | `Obras/Application/Acceso/AccesoObra.php`, `BuscarObra.php` (no se confirma la existencia: misma respuesta que inexistente) | `FichaYEdicionTest::test_obra_ajena_con_alcance_propias_da_404_como_si_no_existiera`, `ListadoObrasTest::test_con_alcance_propias_solo_ve_sus_obras` |
| Usuario o sector inactivo no puede nada | `MatrizPermisos::puede` (inactivo → false; celda de sector archivado no cuenta) | `ConfiguracionInicialTest::test_usuario_inactivo_no_puede_nada_aunque_su_sector_si` |
| No quedar sin administrador | `GuardaAdministradores` dentro de la transacción | `UsuariosEdicionTest::test_no_queda_sin_administrador_de_usuarios`, `TablaDePermisosTest::test_no_se_puede_dejar_sin_administrador_desde_la_tabla` |
| Código de ingreso: 6 dígitos, 10 min, un uso, 5 fallos, guardado como HMAC-SHA256 con `APP_KEY`, `hash_equals`, fila bloqueada al canjear | `Identidad/Infrastructure/CodigosDeIngreso.php`, `Domain/Models/CodigoIngreso.php` | `IngresoTest` (18 casos), `PedirCodigoTest::test_codigo_se_guarda_hasheado_y_nunca_en_claro` |
| Sin enumeración de cuentas | Respuesta única al pedir código; mismo error para código malo, vencido, usuario inactivo o inexistente; un código mal formado no gasta intento | `PedirCodigoTest::test_pedir_codigo_responde_igual_si_el_mail_no_existe`, `IngresoTest::test_codigo_incorrecto_o_mail_inexistente_dan_el_mismo_mensaje` |
| Throttle de ingreso (ADR-015) | Pedir código 3/10 min por mail y 20/h por IP; ingresar 10/min por mail y 30/min por IP (`IdentidadServiceProvider::limites`) | `PedirCodigoTest::test_cuarto_pedido_del_mismo_mail_en_10_minutos_devuelve_429`, `IngresoTest::test_undecimo_intento_del_mismo_mail_en_un_minuto_devuelve_429`, `..._desde_la_misma_ip_tienen_limite` |
| Fijación de sesión | `session()->regenerate()` al entrar (`IngresoController::store`); `invalidate()` + `regenerateToken()` al salir | `IngresoTest::test_salir_cierra_la_sesion` |
| Sesión de 90 días, sin "recordarme" | `config/session.php` (`lifetime` 129600, `expire_on_close` false) | `SesionTest` (4 casos) |
| Desactivar corta la sesión | `AsegurarUsuarioActivo` en el grupo `api` | `SesionTest::test_usuario_desactivado_pierde_su_sesion_en_el_proximo_pedido` |
| Sanctum SPA + CSRF + CORS con credenciales y orígenes explícitos | `bootstrap/app.php` (`statefulApi`), `config/cors.php` (nunca `*`), `config/sanctum.php` | `SpaAuthConfigurationTest` (preflight, origen desconocido rechazado, cookie `XSRF-TOKEN`, stateful) |
| Cookies | `http_only` true, `same_site` lax; `secure` por `SESSION_SECURE_COOKIE` (prod: true, sección 6) | Revisión de configuración |
| Sin datos del stack | `RemoveStackHeaders` (sin `X-Powered-By` ni `Server`); raíz `{"status":"ok"}` sin sesión; sin vista de bienvenida ni webhook de Resend | `SecurityHeadersTest` |
| 500 sin detalle en producción | `ApiExceptionRenderer` (detalle solo con `APP_DEBUG`) | `ApiEnvelopeTest` |
| HTTPS forzado y modelos estrictos fuera de prod | `AppServiceProvider` (`URL::forceHttps`, `shouldBeStrict`) | `ProductionHardeningTest` |
| Mass assignment | `$fillable` explícito en todos los modelos; los casos de uso reciben DTO/arrays armados por el FormRequest; Mi perfil prohíbe `email`, `sector_id`, `jefe_id`, `activo` | `PerfilTest::test_perfil_no_permite_cambiar_mail_sector_ni_jefe`, `AltaObraTest::test_obra_nace_confirmada_aunque_manden_otro_estado`, `AltaProveedorTest::test_proveedor_ignora_adjuntos_y_links_en_el_payload` |
| SQL parametrizado | Todo `whereRaw/selectRaw/DB::select` usa bindings; `LIKE` con comodines escapados en Obras y Proveedores | Revisión (`grep` de `Raw(`) |
| Adjuntos: tipo por contenido | `DetectorTipoArchivo` (finfo + estructura OOXML/OLE; texto con marcas HTML/SVG rechazado) | `SubirAdjuntoTest::test_rechaza_ejecutable_renombrado_a_pdf_por_contenido`, `test_rechaza_html_y_svg`, `test_rechaza_zip_aunque_se_llame_docx` |
| Adjuntos: tamaño | `TamanoMaximoArchivo` (`adjuntos.max_mb` = 20) | `SubirAdjuntoTest::test_rechaza_archivo_de_mas_de_20_mb` |
| Adjuntos: path traversal | Ruta `adjuntos/AAAA/MM/<uuid>` sin extensión; el nombre original se sanea y solo se muestra | `SubirAdjuntoTest::test_nombre_original_se_guarda_sin_rutas_ni_caracteres_de_control`, `test_json_no_expone_la_ruta_en_disco` |
| Adjuntos: descarga autorizada | Disco `adjuntos` con `serve => false`; `GET /api/adjuntos/{id}` con el autorizador del contexto; `attachment` + `nosniff` | `DescargarAdjuntoTest` (8 casos, incluye IDOR y alcance) |
| Adjuntos: asociar solo lo propio | `AdjuntosService::asociar` (temporal, del mismo usuario y del mismo contexto, todo o nada, con bloqueo) | `AsociarAdjuntosTest`, `AltaObraTest::test_alta_con_oferta_de_otro_usuario_da_422` |
| Adjuntos: links solo http(s) | `LinkHttp` | `SubirAdjuntoTest::test_rechaza_link_que_no_es_http_ni_https`, `AltaObraTest::test_carpeta_drive_solo_acepta_link` |
| Adjuntos: abuso | Throttle de subida (30/min por usuario), purga de temporales a las 24 h | `SubirAdjuntoTest::test_subidas_limitadas_por_minuto_por_usuario`, `PurgarAdjuntosHuerfanosTest` |
| Idempotencia sin cruce entre usuarios | Clave única por `(user_id, clave)`; otra ruta u otro payload → 422 | `IdempotentMiddlewareTest` |
| `/api/errors` | Token con `hash_equals`, 10 fallos/h → ban 24 h por IP, entradas sin claves sensibles | `BugReportEndpointsTest` |
| Secretos fuera del repo | `.env`, `.env.backup`, `.env.production`, `.claude/bug-report.json` en `.gitignore`; `phpunit.xml` fuerza tokens vacíos | `git log --all` sin `.env` ni tokens (sección 5) |
| Front sin XSS directo | Sin inserción de HTML crudo (ni la prop de React para HTML ni asignación de HTML al DOM) y sin ejecución de código dinámico; `target="_blank"` siempre con `rel="noopener noreferrer"` | Revisión del front (sección 4.3) |
| Front sin datos en el navegador | Sin `localStorage`/`sessionStorage`/IndexedDB; usuario y permisos solo en la cache de TanStack Query, que "Salir" vacía | `src/features/identidad/hooks/useCuenta.ts` |
| Front: CSRF y sesión | `/sanctum/csrf-cookie` antes de escribir, `X-XSRF-TOKEN`, `credentials: 'include'`, reintento único ante 419; 401/403 invalidan `/api/me` | `src/shared/api/client.ts`, `client.test.ts`, `src/app/queryClient.ts` |
| Front: permisos solo desde `/api/me` | Guards y menú por función, nunca por nombre de sector; el backend vuelve a controlar | `src/shared/permisos/`, `src/app/guards/RequirePermiso.tsx` |

## 3. Autorización por endpoint (route:list de `main`, 2026-11-13: 53 rutas, 49 de `/api`)

Todas las rutas de `/api` llevan el grupo `api` (Sanctum stateful + `AsegurarUsuarioActivo`). Leyenda:
✔ hay test · ✖ falta test · — no aplica (sin alcance o sin recurso por ID).

| Ruta | Autorización | Permitido | Denegado | IDOR / alcance |
|---|---|---|---|---|
| `GET /` | pública | ✔ `SecurityHeadersTest` | — | — |
| `GET /sanctum/csrf-cookie` | pública (Sanctum) | ✔ `SpaAuthConfigurationTest` | — | — |
| `GET /api/health` | pública | ✔ `BugReportEndpointsTest` | — | — |
| `GET /api/errors` | `ERRORS_TOKEN` + ban | ✔ | ✔ sin token / token malo / ban | — |
| `POST /api/auth/pedir-codigo` | pública + throttle | ✔ | ✔ 429 | ✔ no revela cuentas |
| `POST /api/auth/ingresar` | pública + throttle | ✔ | ✔ código malo, inactivo, 429 | — |
| `POST /api/auth/salir` | sesión | ✔ | ✔ 401 | — |
| `GET /api/me` · `PATCH /api/me` | sesión (siempre el propio) | ✔ | ✔ 401 · campos prohibidos | — |
| `GET /api/usuarios/opciones` | sesión (id, nombre y sector; sin mail) | ✔ | ✔ 401 | — |
| `GET /api/usuarios` · `GET /api/usuarios/{id}` | `usuarios_permisos ≥ ver` | ✔ | ✔ | — |
| `POST /api/usuarios` · `PATCH /api/usuarios/{id}` | `usuarios_permisos = editar` | ✔ | ✔ | — |
| `GET /api/sectores` | sesión | ✔ | ✔ 401 | — |
| `POST·PATCH·DELETE /api/sectores…` | `usuarios_permisos = editar` | ✔ | ✔ | — |
| `GET·PUT /api/permisos` | `usuarios_permisos` ver / editar | ✔ | ✔ | — |
| `GET·PUT /api/plazos-prioridad` | `plazos_prioridad` ver / editar | ✔ | ✔ | — |
| `GET /api/obras` | `obras ≥ ver` + alcance | ✔ | ✔ | ✔ |
| `POST /api/obras` | `obras = editar` (+ con "propias" solo se pone a sí mismo) | ✔ | ✔ | ✔ |
| `GET /api/obras/clientes` | `obras ≥ ver` + alcance | ✔ | ✔ | **✖ alcance "propias"** |
| `GET·PATCH /api/obras/{obra}` | `obras` ver / editar + alcance | ✔ | ✔ | ✔ 404 |
| `GET /api/obras/{obra}/historial` | `obras ≥ ver` + alcance | ✔ | **✖** | ✔ 404 |
| `GET /api/obras/{obra}/pedidos-abiertos` | `obras ≥ ver` + alcance | ✔ | **✖** | **✖** (solo 404 de inexistente) |
| `POST /api/obras/{obra}/estado` | `obras ≥ ver` + responsable o `estado_obra = editar` | ✔ | ✔ | ✔ 404 |
| `GET /api/obras/{obra}/presupuestos/sugerir-numero` | `obras ≥ ver` + alcance | ✔ | **✖** | **✖** |
| `POST /api/obras/{obra}/presupuestos` | `obras = editar` + alcance | ✔ | ✔ | ✔ 404 |
| `PATCH·DELETE /api/presupuestos/{id}` | `obras = editar` + alcance | ✔ | ✔ | ✔ |
| `GET /api/obras/{obra}/presupuesto-por-rubro` | `obras ≥ ver` + `presupuesto_rubro ≥ ver` + alcance | ✔ | ✔ | ✔ |
| `PUT /api/presupuestos/{id}/montos` | `presupuesto_rubro = editar` + alcance | ✔ | ✔ | **✖ alcance "propias" en editar** |
| `PUT /api/presupuestos/{id}/monto-obra` | `monto_obra = editar` + alcance | ✔ | ✔ | **✖ alcance "propias"** |
| `POST /api/obras/{obra}/documentos` · `PATCH·DELETE /api/documentos/{id}` | `obras = editar` + alcance | ✔ | ✔ | ✔ |
| `POST /api/adjuntos` | sesión + throttle (el permiso del dueño se mira al asociar) | ✔ | ✔ 401 | ✔ al asociar |
| `GET·DELETE /api/adjuntos/{id}` | autorizador del contexto (ver / editar el dueño) | ✔ | ✔ | ✔ 404 |
| `GET /api/buscar` | sesión; cada grupo filtra por permiso y alcance | ✔ | ✔ | ✔ |
| `GET /api/proveedores` · `GET /api/proveedores/{id}` · `…/parecidos` | `proveedores ≥ ver` | ✔ | ✔ | — |
| `POST·PATCH /api/proveedores…` | `proveedores = editar` | ✔ | ✔ | — |
| `GET /api/proveedores/opciones` | `proveedores ≥ ver` o `gastos = editar` (FormRequest) | ✔ | ✔ | — |
| `GET /api/proveedores/{id}/gastado-por-obra` | `proveedores ≥ ver` + `gastos ≥ ver` + alcance de `gastos` | ✔ | ✔ | ✔ |
| `GET·PUT /storage/{path}` | URL firmada de Laravel (disco `local`) | — | — | **ver SEG-05** |

**Resultado:** ningún endpoint quedó sin autorización. Faltan tests en 6 rutas (marcadas ✖), ver TL-01.

## 4. Revisión de la semana 1

Severidad: **bloqueante** (no se entrega así) · **importante** (se arregla en la semana 2) · **menor** (se arregla
cuando se toque el archivo o se acepta con motivo). Estado inicial de todos: **abierto**.

**No hay hallazgos bloqueantes.**

### 4.1 Backend — seguridad

| ID | Sev. | Dónde | Riesgo | Arreglo propuesto |
|---|---|---|---|---|
| SEG-01 | importante | `app/Modules/Identidad/IdentidadServiceProvider.php:88-98`; `Infrastructure/CodigosDeIngreso.php` | **Fuerza bruta distribuida del código.** Por mail: 3 códigos cada 10 min × 5 intentos = 15 intentos cada 10 min ≈ 2.160 por día. Con IPs rotativas el límite por IP no frena. Probabilidad ≈ 0,2 % por día y ≈ 6 % en 30 días contra una cuenta (y el dueño recibe ~430 mails por día, que es la única alarma) | Tope diario por mail (p. ej. 10 códigos cada 24 h) o bloqueo de 24 h al quemar 3 códigos por fallos, con registro en el log de seguridad. Cambia los límites de ADR-015: lo decide Juani (nota en el ADR) |
| SEG-02 | importante | `bootstrap/app.php:52`; `app/Http/Controllers/Api/Traits/SafeErrorResponse.php:36` | **Datos personales e importes en el log `errors`.** Se guarda el `input` completo menos `password`, `codigo`, `token` y `secret`: mails (cada ingreso fallido es un 422 y se registra con el mail), teléfonos, contactos de obra, CUIT y montos. Además salen por `/api/errors`. Contradice `arquitectura.md` §7 | Registrar solo las **claves** del input (o una lista permitida) y los nombres de campo de `validation_errors`; agregar `codigo`, `email`, `telefono`, `cuit` y montos a `ErrorLogController::SENSITIVE_INPUT_KEYS` (línea 25) por defensa en profundidad; test que verifique que un ingreso fallido no deja el mail en el log |
| SEG-03 | importante | `bootstrap/app.php:25-29`; rutas de los módulos | **Sin límite general de la API autenticada** (solo ingreso y subida de adjuntos tienen throttle). `arquitectura.md` §7 lo promete. Un usuario o script con sesión puede martillar `/api/buscar` o los listados | `$middleware->throttleApi()` con un limitador `api` por usuario (p. ej. 120/min) y test del 429 |
| SEG-04 | importante (prod) | `bootstrap/app.php:28` (`trustProxies(at: '*')`) | Si PHP queda accesible sin nginx delante, o nginx no pisa `X-Forwarded-For`, la IP se falsifica: se esquivan los límites por IP del ingreso y el ban de `/api/errors` | `TRUSTED_PROXIES` con la IP del nginx (127.0.0.1) en lugar de `*`, y en nginx `proxy_set_header X-Forwarded-For $remote_addr;`. Ítem de la sección 6 |
| SEG-05 | menor | `config/filesystems.php:35-41` | El disco `local` tiene `serve => true` con la **misma raíz** que `adjuntos` (`storage/app/private`): registra `GET/PUT /storage/{path}` con URL firmada. Hoy nada genera esas URLs, pero un `temporaryUrl()` sobre `local` bajaría un adjunto sin pasar por la autorización del dueño | `serve => false` en `local` (o raíz distinta) y test de que `/storage/{path}` da 404 |
| SEG-06 | menor | `app/Modules/Identidad/Application/Auth/PedirCodigoAction.php:33-39` | Enumeración por tiempo: con un usuario activo se escribe el código y se encola el mail; con un mail inexistente se responde enseguida. La diferencia es de milisegundos y el throttle por mail la limita | Encolar siempre un job que resuelva el usuario adentro (misma latencia), o aceptar el riesgo por escrito |
| SEG-07 | menor | `app/Shared/Http/Middleware/RemoveStackHeaders.php`; `app/Shared/Adjuntos/Http/AdjuntoController.php:45` | `nosniff` solo en la descarga. Faltan cabeceras globales (`X-Content-Type-Options`, `Referrer-Policy`, `X-Frame-Options`/`frame-ancestors`) | Middleware `SecurityHeaders` global (más `Content-Security-Policy: sandbox` en la descarga) y HSTS en nginx; ampliar `SecurityHeadersTest` |
| SEG-08 | menor | `app/Http/Controllers/Api/ErrorLogController.php:45` | `ERRORS_TOKEN` por `?token=`: queda en los logs de acceso de nginx y en el `url` del propio log `errors` | El contrato admite los dos: usar Bearer desde la skill; no registrar la query de `/api/errors` en nginx; rotar el token (sección 6) |
| SEG-09 | menor | `app/Modules/Identidad/Infrastructure/Mail/CodigoIngresoMail.php:18` | El código viaja en claro en el payload de la cola (Redis) y, si el envío falla, queda en `failed_jobs` (MySQL). Vence a los 10 min | Aceptado por la vigencia corta; Redis con clave (sección 6); `queue:prune-failed` programado |
| SEG-10 | menor | `.gitignore:3-5` | Cubre `.env`, `.env.backup` y `.env.production`, pero no `.env.staging` ni `.env.local` | `.env.*` + `!.env.example` |
| SEG-11 | menor | `config/session.php:39-41` (ADR-015) | La sesión se renueva sin tope absoluto y no hay "cerrar sesión en todos los equipos": un celular perdido queda adentro hasta desactivar al usuario | Riesgo aceptado en ADR-015. Procedimiento: desactivar y reactivar a la persona corta todas sus sesiones. Si hace falta más → `dudas.md` |
| SEG-12 | menor | `app/Modules/Identidad/Application/Usuarios/GuardaAdministradores.php:24` | Sin bloqueo: dos bajas simultáneas de los dos últimos administradores pueden dejar cero | `lockForUpdate()` sobre los administradores activos (o `Cache::lock`) en las acciones que llaman a la guarda |

### 4.2 Backend — Tech Lead (checklist de `CONTRIBUTING.md`)

| ID | Sev. | Dónde | Hallazgo | Arreglo propuesto |
|---|---|---|---|---|
| TL-01 | importante | `tests/Feature/Obras/*` | Faltan tests de **denegado/IDOR** en 6 rutas (tabla de la sección 3): `sugerir-numero` (ni denegado ni IDOR), `pedidos-abiertos` (ni denegado ni IDOR), `historial` (denegado), `obras/clientes` (alcance "propias"), `montos` y `monto-obra` (IDOR con alcance "propias"). El código autoriza bien; falta la prueba que exige el checklist | Un test por regla: `test_sugerir_numero_de_obra_ajena_con_alcance_propias_da_404`, `test_clientes_con_alcance_propias_solo_sugiere_los_de_sus_obras`, etc. |
| TL-02 | importante | `Identidad/Application/Auth/IngresarAction.php:9`, `PedirCodigoAction.php:9-10`; `Obras/Application/Obras/ListarObrasQuery.php:15-18`, `ClientesSugeridosQuery.php:10-11` | **Application depende de la Infrastructure del propio módulo.** ADR-003 define `Application → Domain ← Infrastructure`, pero `ModuleBoundaryScanner` lo permite (`scanner_allows_own_infrastructure_contracts_and_http_outside_domain`). Además los módulos no son consistentes: Proveedores tiene sus filtros en `Application/Filtros` y Obras en `Infrastructure/Consultas` | Decidir una regla: (a) puertos en `Application/Ports` (como ya hace Obras con `PedidosDeObra`) para `CodigosDeIngreso` y `MailsDeCuenta`, y mover los query objects de Obras a `Application`; o (b) enmendar ADR-003 y dejarlo explícito. Recomendado: (a), y que el test de arquitectura lo verifique |
| TL-03 | menor | `Obras/Http/Controllers/ObraController.php:33-40`, `ObraHistorialController.php:28-33` | El controller desarma y rearma el JSON del envelope para agregar `meta` | `ApiResponse::paginated($paginator, $meta)` o `additional()` en la colección |
| TL-04 | menor | `Obras/Application/Obras/ListarObrasQuery.php:42` y `:87` | `porcentajesGastados()` se pide dos veces en "En ejecución" (una consulta repetida cuando Rendicion lo implemente) | Reusar el resultado de la línea 42 |
| TL-05 | menor | `Identidad/Infrastructure/MatrizPermisos.php:36-43` | Cada `puede()`/`alcance()` es una consulta: `AccesoObra::resumen` hace ≈16 por ficha. No es N+1 por fila, pero crece con cada módulo | Cargar las 12 celdas del sector una vez por request (binding *scoped*); sin cache entre requests (RN-IDE-14) |
| TL-06 | menor | `Identidad/Http/Controllers/Concerns/UsuarioAutenticado.php`, `Proveedores/Http/Controllers/Concerns/UsuarioAutenticado.php`, `Obras/Http/Controllers/Concerns/ConUsuario.php`, `Shared/Adjuntos/Http/AdjuntoController.php:56` | El mismo "usuario autenticado o 401" está copiado cuatro veces | Un solo trait en `app/Shared/Http` |
| TL-07 | menor | `Shared/Adjuntos/AdjuntosService.php:54` | `asociar()` toma el usuario del guard: dependencia oculta de HTTP; Importacion (consola) no tiene guard | Recibir el `User` (o el id) como parámetro. Cambia un contrato del brief: coordinar con los módulos |
| TL-08 | menor | `app/Shared/**` (13 imports de `Identidad\Domain\Models\User`) | Shared depende de un módulo (al revés de ADR-003), y el test de arquitectura no escanea `Shared` | Tipar con `Authenticatable`/id, o dejar la excepción escrita en ADR-003 y cubierta por el test |
| TL-09 | menor | `Obras/Http/Controllers/ObraController.php:45`, `Identidad/Http/Controllers/UsuarioController.php:38`; `Identidad/Application/Usuarios/ListarUsuariosQuery.php:34-35`, `OpcionesUsuariosQuery.php:25` | `buscar` sin FormRequest (sin `max`); `LIKE` sin escapar `%`/`_` (no es inyección: usa bindings, pero cambia el resultado) | FormRequest con `max`; `addcslashes` como en `Proveedores/Application/Filtros/PorNombre.php` |
| TL-10 | menor | `app/Http/Controllers/Api/HealthController.php:46,57,68` | `catch (Throwable)` genérico (regla del brief) | Es un health check: capturar las excepciones concretas (`PDOException`, `RedisException`, `ConnectionException`) o dejar la excepción documentada |
| TL-11 | menor | `app/Http/Controllers/Api/ErrorLogController.php` (206 líneas) | Pasa las ~200 líneas | Extraer el parser de entradas a una clase |
| TL-12 | menor | `bootstrap/app.php:73-74` | El comentario dice "sigue el reporte por defecto (laravel.log)" pero `return false` lo corta | Alinear: o se borra el comentario, o no se devuelve `false` |
| TL-13 | menor | `tests/Feature/Shared/SecurityHeadersTest.php`, `SpaAuthConfigurationTest.php`, `ProductionHardeningTest.php`, `BugReportEndpointsTest.php`, `ApiEnvelopeTest.php` | Tests en inglés y sin la regla en español (ADR-004) | Renombrar (`test_la_raiz_no_expone_el_stack`, …) |
| TL-14 | menor | `database/migrations/2026_11_13_300001_create_rubros_table.php:41` | Crea la tabla solo si no existe (venía de ramas paralelas) pero el `down()` la borra siempre | Ya hay una sola migración dueña: quitar el `hasTable` |
| TL-15 | menor | `Shared/Adjuntos/Application/SubirArchivoAction.php:53` | El archivo se escribe dentro de la transacción: si el commit falla queda un archivo sin fila que la purga nunca ve | Borrar el archivo si la transacción no confirma (callback de rollback o escribir después del commit y compensar) |
| TL-16 | menor | Modelos de Identidad/Proveedores (`#[Fillable]`) vs Obras/Shared (`$fillable`) | Dos estilos para lo mismo | Unificar en uno |

Verificado sin hallazgos: `declare(strict_types=1)` y tipos de retorno, envelope en todas las respuestas, FormRequest +
Resources, constantes con nombre en lugar de números mágicos, `catch` específicos fuera del health check,
migraciones con `down()` real, listados paginados (`usuarios`, `proveedores`, `obras`, historial), sin N+1 en el
listado de obras (`test_listado_no_hace_una_consulta_por_obra`), CTE de "mi gente" con `UNION` (no entra en bucle
aunque hubiera un ciclo), Idempotency-Key en todos los POST que crean.

Además: `arquitectura.md` §2 todavía pone `presupuesto_montos` en Rendicion (el dueño real es Obras, ver `modelo-datos.md`).

### 4.3 Front (`tecnoservicios_frontend`, `main`)

| ID | Sev. | Dónde | Riesgo | Arreglo propuesto |
|---|---|---|---|---|
| FE-01 | importante | `src/shared/adjuntos/AdjuntoFila.tsx:58`, `src/features/obras/components/ficha/CabeceraObra.tsx:70`, `src/features/obras/components/ficha/Contactos.tsx:25`; schemas `url: z.string()` (`adjuntos.schemas.ts`, `obras.schemas.ts:114`) | El `href` usa la URL del servidor sin validar el protocolo. Hoy el backend solo guarda http(s) (`LinkHttp`), pero un dato que entre por otro camino (Importación, SQL) se pintaría como link; React bloquea `javascript:`, no otros esquemas | Helper `urlSegura()` (`new URL`, solo `http:`/`https:`) en el schema zod o al renderizar; si no pasa, texto sin link |
| FE-02 | menor | `src/shared/adjuntos/adjuntos.api.ts:44-76` | La descarga no pasa por el cliente HTTP: sin `Accept: application/json` ni `X-Requested-With`, y un 401 no invalida la sesión | Mandar las cabeceras y convertir el 401 en `ApiError(401)` |
| FE-03 | menor | `src/features/identidad/pantallas/IngresoPantalla.tsx:9` | El destino después de entrar acepta `//dominio` (redirect abierto; el valor sale del estado del router, riesgo bajo) | `.regex(/^\/(?!\/)/)` |
| FE-04 | menor | `index.html` / hosting | Sin Content-Security-Policy | `default-src 'self'; connect-src <API>; frame-ancestors 'none'` en el servidor del front |
| FE-05 | menor | `EditarObraPantalla.tsx:34`, `ObraPantalla.tsx:58`, `PresupuestoPorRubroPantalla.tsx:29`, `FichaProveedor.tsx:21`, `RouteError.tsx:7`, `src/app/queryClient.ts:11` | Status HTTP como números mágicos; constantes repetidas en `adjuntos.api.ts` y `CambiarEstadoDialog.tsx` | `ApiError.isNotFound` / `isClientError` y constantes en `ApiError.ts` |
| FE-06 | menor | `src/shared/api/client.ts` (204), `src/features/obras/obras.css` (482) | Pasan las ~200 líneas | Sacar `MENSAJE_POR_STATUS` a `ApiError.ts`; partir `obras.css` por componente |
| FE-07 | menor | `src/**/*.test.tsx` | 46 de 190 tests citan la regla (RN/HU); el resto describe comportamiento | Nombrar por regla al tocar cada archivo |

## 5. Secretos y dependencias (2026-11-13)

- **Historial de git del backend** (41 commits, todas las ramas): ningún `.env` ni `.claude/bug-report.json`
  versionado; sin `APP_KEY`, `ERRORS_TOKEN`, claves de Resend, AWS, GitHub, Slack ni bloques `BEGIN … KEY`
  en `git log -p`. `phpunit.xml` y el CI usan valores vacíos.
- **Front**: solo `.env.example` versionado (`VITE_API_URL`, `VITE_MODULOS_ENTREGADOS`); `.gitignore` excluye `.env` y `.env.*`.
- **`composer audit --locked`**: sin advertencias de seguridad.
- **`npm audit --omit=dev`** (front): 0 vulnerabilidades en 17 dependencias de producción. Con dev: 10 moderadas,
  todas de `minimatch` (GHSA-g3hv-75wg-m249, ReDoS) a través de `eslint`/`typescript-eslint`; no llegan al bundle.
  El arreglo de npm baja de versión mayor: esperar el parche de upstream.

## 6. Checklist para producción

Se tilda en el primer deploy y se repite en cada cambio de servidor. Evidencia: captura o salida del comando en `deploy.md`.

**Aplicación (`.env`)**
- [ ] `APP_ENV=production`, `APP_DEBUG=false`, `LOG_LEVEL=warning` (el ejemplo trae `debug`), `APP_KEY` propio y guardado fuera del servidor.
- [ ] `APP_URL=https://…` (HTTPS forzado por `AppServiceProvider`).
- [ ] `SESSION_SECURE_COOKIE=true`, `SESSION_DOMAIN` = dominio padre del front y la API, `SESSION_SAME_SITE=lax`, `SESSION_DRIVER=redis`.
- [ ] `SANCTUM_STATEFUL_DOMAINS` y `CORS_ALLOWED_ORIGINS` = **solo** el dominio del front en HTTPS (sin `localhost`).
- [ ] `TRUSTED_PROXIES` = IP del nginx (SEG-04), no `*`.
- [ ] `ERRORS_TOKEN` de 64 hex (`openssl rand -hex 32`), distinto en staging y producción; `.claude/bug-report.json` actualizado; **rotarlo** al cambiar de equipo, si aparece en un log o cada 6 meses.
- [ ] `MAIL_MAILER=resend`, `RESEND_API_KEY` con permiso solo de envío, dominio con SPF/DKIM/DMARC (el código de ingreso depende del mail, ADR-015).
- [ ] `MAIL_DOMINIOS_PERMITIDOS` con el dominio de la empresa (alta de usuarios, RN-IDE-26).

**PHP y nginx**
- [ ] `upload_max_filesize` y `post_max_size` **≥ 21 MB** (20 MB de archivo + multipart, ADR-014); `expose_php = Off`.
- [ ] nginx `client_max_body_size 21m;` en el `server` de la API.
- [ ] nginx `server_tokens off;` (el `Server` que agrega nginx no lo puede quitar Laravel).
- [ ] TLS 1.2+ con certificado válido, redirección 80 → 443 y `Strict-Transport-Security: max-age=31536000`.
- [ ] `proxy_set_header X-Forwarded-For $remote_addr;` y `X-Forwarded-Proto $scheme;` (SEG-04).
- [ ] Raíz del sitio en `public/`; `storage/` y `.env` fuera del alcance web; `storage/app/private` sin `location` que lo sirva.
- [ ] No loguear la query string de `/api/errors` en el access log (SEG-08).
- [ ] Front: cabecera CSP y `X-Frame-Options: DENY` (FE-04).

**Servicios**
- [ ] Redis con `requirepass` (o ACL), escuchando solo en 127.0.0.1; `REDIS_PASSWORD` en `.env`.
- [ ] MySQL con usuario propio de la app (sin `root`), solo DML + DDL sobre su base, escuchando en 127.0.0.1.
- [ ] Supervisord arriba (`php artisan supervisor:sync`): worker de la cola `mail` siempre corriendo (sin él nadie entra, ADR-015) y scheduler (purga de adjuntos e idempotencia).
- [ ] `queue:prune-failed` programado (SEG-09).

**Rate limits** (verificar con curl contra staging)
- [ ] Pedir código: 4.º pedido del mismo mail en 10 min → 429; 21.º de la misma IP en 1 h → 429.
- [ ] Ingresar: 11.º intento del mismo mail en 1 min → 429; 31.º de la misma IP → 429.
- [ ] Subida de adjuntos: 31.ª en 1 min → 429.
- [ ] API general: pendiente de SEG-03.

**Backups**
- [ ] Backup diario de MySQL **y** de `storage/app/private/adjuntos` (ADR-014), cifrado, fuera del servidor, con retención definida en `deploy.md`.
- [ ] Prueba de restauración documentada antes de la entrega final.

**Permisos del sistema de archivos**
- [ ] `storage/` y `bootstrap/cache/` escribibles solo por el usuario del worker/PHP-FPM (`www-data`); `.env` con modo 600.

## 7. Riesgos aceptados

| Riesgo | Por qué se acepta | Decidió |
|---|---|---|
| La seguridad de la cuenta es la del buzón de mail | Mismo nivel que "Olvidé mi contraseña"; prioridad: adopción | Juani, ADR-015 |
| Sesión de 90 días renovable, sin tope absoluto | Uso desde el celular en obra sin pedir código seguido; se corta desactivando al usuario | Juani, ADR-015 |
| Subir adjuntos lo puede cualquier usuario autenticado | El permiso real se mira al asociar; temporales purgados a 24 h; throttle por usuario | ADR-014 |
| `/api/health` público informa el estado de DB, cache y Redis | Contrato de bug-report de NexoSmart; no expone versiones ni datos | Contrato NexoSmart |
