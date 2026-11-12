# Especificación funcional · Proveedores

> Módulo `app/Modules/Proveedores` · Entrega: **semana 1** (la pestaña "Gastado por obra" se llena en semana 3)
> Rol: Analista funcional · 2026-11-12
> Contrato: documento firmado v1.1 (cap. 2 "No incluye", cap. 4.3, cap. 8 F-42/F-44, cap. 10) +
> maquetas W-14, W-38 (y W-10 para el alta rápida). Decisiones: D-033, D-036, D-040; C12.
> Lo "Propuesto" del documento está **aprobado**. Lo no especificado → `docs/dudas.md` (DU-A..).
> **Dudas resueltas por Juani el 12-nov-2026** (va la propuesta): las reglas que dependían de una
> duda son definitivas y citan "resuelta DU-XX". Adjuntos (ADR-014): este módulo **no tiene**
> archivos ni links propios (§4.2).
> Los proveedores, CUIT, mails e importes de este archivo son **ficticios** (los de las maquetas).

## 1. Propósito

"Cada proveedor se carga una vez y, al cargar una factura, se elige de la lista y trae sus datos.
Hoy el mismo proveedor aparece escrito de varias formas en sus rendiciones; con la base queda uno
solo" (cap. 10). La base es para **elegir** al cargar gastos (semana 3): no lleva saldos, cuenta
corriente, vencimientos ni pagos, que siguen en Tango (D-033, D-040).

## 2. Funcionalidades y pantallas cubiertas

| F-XX | Qué | Pantalla | Semana 1 |
|---|---|---|---|
| F-55 | Base de proveedores: listado con buscador; cada uno se carga una vez | W-14 | completo |
| F-56 | Alta y edición: nombre (obligatorio); CUIT, contacto y rubro habitual (opcionales); aviso de parecidos | W-38 | completo |
| F-57 | Gastos del proveedor: lo imputado por obra, en su ficha | W-14 "Gastado por obra" | **contrato** (§10.3); se completa en semana 3 |
| F-42 (parte) | Alta rápida desde la carga de la factura | W-10 → W-38 | el endpoint queda listo; la pantalla W-10 es de semana 3 |
| F-44 (parte) | Proveedor genérico para gastos sin comprobante | — | se crea por migración |
| F-03 (parte) | Buscar proveedores por nombre | W-14 | completo dentro de Proveedores (W-18 general es semana 2) |

## 3. Glosario

| Cliente | Código | Qué es |
|---|---|---|
| Proveedor | `Proveedor` / `proveedores` | A quién se le compra. Uno por empresa. |
| Base de proveedores | listado W-14 | La lista única de la que se eligen. |
| Rubro habitual | `rubro_habitual_id` | El rubro en el que más se le compra; sugiere el rubro al cargar renglones (semana 3). |
| Parecidos | `ProveedoresParecidos` (query) | Proveedores ya cargados con nombre parecido o mismo CUIT, que se muestran antes de guardar. |
| Proveedor genérico | `es_generico = true` | El que se usa en los gastos personales de obra sin ticket (F-44). |
| Gastado por obra | `GastosPorProveedor` (puerto) | Renglones e importe imputados a cada obra (y "sin obra"). |

## 4. Datos

### 4.1 Proveedor (`proveedores`) — W-14, W-38

| Campo | Tipo | Oblig. | Validación | Fuente |
|---|---|---|---|---|
| nombre | texto ≤ 150 | **Sí** | no vacío; sin espacios al borde; único según RN-PRV-04 | F-56, W-38 "Solo el nombre es obligatorio" (16-oct) |
| cuit | 11 dígitos | No | tolerante (RN-PRV-06); dígito verificador válido; único si está | F-56 (aprobado) |
| telefono | texto ≤ 50 | No | libre | W-38 "Contacto · teléfono" (aprobado) |
| email | mail ≤ 190 | No | formato mail; minúsculas | W-38 "Contacto · mail" (aprobado) |
| rubro_habitual_id | ref. rubro (lista cerrada de `obras.md` §4.6, los 13 que se imputan) | No | debe existir | W-38 "Rubro habitual" (aprobado) |
| es_generico | booleano | — | solo el de la migración (RN-PRV-12) | F-44 |
| nombre_clave | texto (derivado) | — | normalización suave, para la unicidad (RN-PRV-04) | técnica |
| nombre_normalizado | texto (derivado) | — | normalización fuerte, para parecidos (RN-PRV-08) | técnica, modelo-datos |
| created_by, created_at, updated_by, updated_at | — | — | — | — |

**No hay** campos de saldo, cuenta corriente, condición de pago, vencimientos ni estado de pago
(cap. 8: "Fuera de este módulo, porque siguen en Tango"; W-14 "acá no hay saldos").

### 4.2 Adjuntos: no van en el proveedor

La factura escaneada, los tickets y el remito son del **gasto**, no del proveedor: se cargan como
**archivo o link** en la carga del gasto (contextos `comprobante_gasto` y `remito`, semana 3,
ADR-014; F-43, F-44, F-47). El alta rápida desde la factura (F-42) no sube nada: crea el proveedor
y vuelve a la factura, donde se adjunta. Un proveedor no tiene adjuntos ni campo de link.

## 5. Reglas de negocio

### Listado (F-55, W-14)

- **RN-PRV-01** W-14: a la izquierda, buscador **"Buscar proveedor…"** y la lista con **nombre** y, al costado, su **rubro habitual**; a la derecha, la ficha del elegido con sus datos, botón **Editar** y la sección **"Gastado por obra"**. Botón **"+ Nuevo proveedor"**. *(W-14)*
- **RN-PRV-02** La lista va en orden alfabético por nombre (sin distinguir mayúsculas ni tildes). *(resuelta DU-A20)*
- **RN-PRV-03** El buscador encuentra por **nombre**, por cualquier parte del texto, sin distinguir mayúsculas, tildes ni puntuación ("corralon" encuentra "Corralón Centro"; "s.r.l" encuentra "… SRL"). *(F-03 "proveedores por nombre")*

### Alta y edición (F-56, W-38)

- **RN-PRV-04** **Cada proveedor se carga una vez.** No se puede guardar un nombre que, normalizado **suave** (minúsculas, sin tildes, sin puntuación, espacios simples y sin forma societaria `sa`, `srl`, `sas`, `sh`, `sac`, `saic`, `sacif`, `sca`, `cia`, `y cia`), ya exista: "Hormigonera Sur S.R.L." = "HORMIGONERA SUR" = "hormigonera sur srl". 422: "Ya existe Hormigonera Sur SRL." con link a su ficha. *(F-55 "cada proveedor se carga una vez", D-036, resuelta DU-10)*
- **RN-PRV-05** Solo el **nombre** es obligatorio; CUIT, teléfono, mail y rubro habitual son opcionales y se pueden completar después. *(W-38, W-14 "el nombre alcanza para empezar")*
- **RN-PRV-06** CUIT tolerante: se acepta con o sin guiones o espacios (`30-00000001-5`, `30 00000001 5`, `30000000015`); se guardan los 11 dígitos y se muestra `30-00000001-5`. Si no son 11 dígitos o el dígito verificador no da: 422 "El CUIT no es válido. Revisá los números." *(técnica: un CUIT mal escrito no sirve para cruzar con Tango después)*
- **RN-PRV-07** **El CUIT no se repite**: si ya lo tiene otro proveedor, 422 "Ese CUIT ya es de Corralón Centro." con link. *(F-55; modelo-datos "único si está")*
- **RN-PRV-08** **Aviso de parecidos** (no bloquea): mientras se escribe el nombre (y al escribir el CUIT) el formulario muestra "Parecidos ya cargados: …" con links. Son parecidos los proveedores cuyo nombre normalizado **fuerte** (suave + sin `de`, `del`, `la`, `las`, `el`, `los`, `y`):
  1. es igual, o
  2. uno contiene al otro (con al menos 4 letras), o
  3. comparte al menos la mitad de las palabras, o
  4. se parece al menos un 80 % letra por letra (distancia de Levenshtein ≤ 20 % del más largo).

  Se muestran hasta 5, los más parecidos primero. Se puede guardar igual. *(W-38 aprobado: "Aviso de parecidos para no cargar el mismo proveedor dos veces")*
- **RN-PRV-09** El aviso de parecidos también se muestra al **editar** el nombre (excluyendo al propio proveedor) y en el **alta rápida** desde la factura. *(W-38, F-42)*
- **RN-PRV-10** Alta rápida (F-42): desde la carga de una factura (W-10, semana 3) se abre el mismo formulario W-38; Al guardar, el proveedor queda elegido en la factura y la persona sigue con la factura, donde adjunta el archivo o link (§4.2). Mismo endpoint y mismas reglas. Requiere `proveedores = editar`. *(F-42 aprobado, ADR-014)*
- **RN-PRV-11** No hay baja ni fusión de proveedores en la Etapa 1 (F-56 dice "alta y edición"). Un duplicado que se cuele y no tenga gastos se corrige renombrándolo. La carga inicial (semana 4) entra **depurada**, con una lista de equivalencias de proveedores que revisa y aprueba administración. *(resuelta DU-A19)*

### Proveedor genérico (F-44)

- **RN-PRV-12** Existe **un** proveedor genérico, creado **por migración** (no por seeder), con nombre **"Gastos sin comprobante"** (resuelta DU-A18) y `es_generico = true`. Es el que se elige en los gastos personales de obra sin ticket, "como hoy, sin documento y con proveedor genérico", con el dato "rendido por" en el gasto (semana 3). *(F-44, cap. 4.3, C12)*
- **RN-PRV-13** El genérico **no se edita** (422 "El proveedor genérico no se modifica.") y aparece en la lista con la marca "genérico". No tiene CUIT. *(técnica: si se renombra, se mezcla con proveedores reales)*
- **RN-PRV-14** Que el genérico solo se pueda usar con "sin documento" lo valida Gastos (semana 3); el módulo Proveedores lo expone en `ProveedoresConsulta::generico()`.

### Gastos del proveedor (F-57, W-14)

- **RN-PRV-15** La sección **"Gastado por obra"** de la ficha muestra **Obra · Renglones · Importe** por cada obra donde se imputó algo de ese proveedor, una fila **"(sin obra)"** para lo que no fue a ninguna obra y la fila **Total**. Importes netos sin IVA, de gastos **no anulados**. *(F-57 aprobado, W-14, sus tablas dinámicas)*
- **RN-PRV-16** La sección se muestra solo a quien tiene **Gastos y facturas ≥ ver**; con alcance "solo sus obras" ve solo las filas de sus obras y el total de esas (sin la fila "(sin obra)"). *(F-57 "según permisos", identidad.md §6.3)*
- **RN-PRV-17** Semana 1: la sección muestra "Todavía no hay gastos cargados." (el puerto devuelve vacío). Semana 3: lo implementa Gastos (§10.3). Se arma sola con lo cargado: no hay que actualizar nada (W-37).

## 6. Estados y transiciones

No tiene. (El campo `activo` del modelo de datos queda siempre en `true` en la Etapa 1: no hay baja, RN-PRV-11.)

## 7. Permisos

| Acción | Función (fila) | Nivel | Alcance |
|---|---|---|---|
| Ver la base (W-14) y la ficha | `proveedores` | ver | — |
| Alta, edición, alta rápida | `proveedores` | editar | — |
| Ver "Gastado por obra" | `gastos` | ver | `todas` / `propias` (RN-PRV-16) |
| Elegir un proveedor en la factura (semana 3) | `gastos` | editar | — (usa `GET /api/proveedores/opciones`) |

Configuración inicial (`identidad.md` §6.3): Editar para Gerencia de Administración, Jefatura de
Administración y Compras; Ver para Dirección; Ver para Gerencia de Operaciones y Jefatura de Proyectos
(resuelta DU-02); Sin acceso para Ingeniería de proyectos (W-17) y, mientras la empresa no lo complete, para
el resto (resuelta DU-A02).

## 8. Avisos

No genera avisos.

## 9. Casos borde (datos ficticios)

| # | Caso | Resultado esperado |
|---|---|---|
| 1 | Alta "Hormigonera del Sur" con "Hormigonera Sur SRL" y "Hormigones del Sur" cargados | Aviso: los dos como parecidos (el primero, normalizado fuerte igual: "hormigonera sur"; el segundo, ≥ 80 % letra por letra). Se puede guardar. |
| 2 | Alta "HORMIGONERA SUR" o "Hormigonera Sur S.R.L." con "Hormigonera Sur SRL" cargado | 422 RN-PRV-04 (mismo nombre normalizado suave). |
| 3 | Alta solo con nombre "Ferretería Norte" | 201; CUIT, contacto y rubro vacíos. |
| 4 | CUIT `30-00000001-1` con dígito verificador incorrecto | 422 RN-PRV-06. |
| 5 | CUIT `30000000007` ya cargado en "Corralón Centro" al dar de alta "Corralon Centro Sucursal" | 422 RN-PRV-07 con link. |
| 6 | Editar "Transportes Ejemplo" a "Transporte Ejemplo" | Aviso de parecidos sin incluirse a sí mismo; se guarda. |
| 7 | Buscar "hotel puerto" | Encuentra "Hotel Puerto Ejemplo". |
| 8 | Carla (Compras) intenta editar el proveedor genérico | 422 RN-PRV-13. |
| 9 | Laura (Ingeniería, Proveedores = Sin acceso) pide `GET /api/proveedores` | 403; no tiene el ítem en el menú. |
| 10 | Dirección (Proveedores = Ver, Gastos = Ver todas) abre "Corralón Centro" en semana 3 | Ve la ficha sin "Editar" y "Gastado por obra" completo con "(sin obra)" y Total. |
| 11 | Mail "VENTAS@Corralon-Centro.com" | Se guarda en minúsculas. |
| 12 | Doble clic en "Guardar proveedor" con la misma `Idempotency-Key` | Un solo proveedor. |
| 13 | Rubro habitual EPP | Válido (EPP se imputa, aunque no se presupueste). |
| 14 | Se manda `POST /api/proveedores` con un campo `adjuntos` o `factura_url` | Se ignora (no es un campo del proveedor); la respuesta no lo trae (§4.2). |

## 10. Contrato de API sugerido

Envelope `{ data, message, errors }`; listados con `meta`/`links`. Importes como string decimal.
POST que crean con `Idempotency-Key`.

### 10.1 Endpoints

| Método y ruta | Payload | Respuesta `data` | Errores |
|---|---|---|---|
| `GET /api/proveedores?buscar=&page=` | — | `[{id, nombre, rubro_habitual: {clave, nombre}\|null, es_generico}]` paginado | 403 |
| `GET /api/proveedores/opciones?buscar=` | — | `[{id, nombre, cuit, rubro_habitual, es_generico}]` máx. 20 (para el combobox de la factura) | 403 (sin `proveedores` ≥ ver ni `gastos` = editar) |
| `GET /api/proveedores/parecidos?nombre=&cuit=&excluir_id=` | — | `[{id, nombre, cuit, motivo: "nombre"\|"cuit"}]` máx. 5 | 403 |
| `POST /api/proveedores` | `{nombre, cuit?, telefono?, email?, rubro_habitual?: "materiales"}` | `Proveedor` (201) | 403 · 422 (`errors.nombre` / `errors.cuit` + `errors.existente: {id, nombre}`) |
| `GET /api/proveedores/{id}` | — | `Proveedor` | 403 · 404 |
| `PATCH /api/proveedores/{id}` | mismos campos, opcionales | `Proveedor` | 403 · 404 · 422 (incluye RN-PRV-13) |
| `GET /api/proveedores/{id}/gastado-por-obra` | — | ver §10.3 | 403 (sin `gastos` ≥ ver) · 404 |

No hay endpoints de adjuntos en este módulo: los archivos o links de la factura van por
`POST /api/adjuntos` y se asocian al gasto (`obras.md` §10.5, `identidad.md` §6.6).

```json
// Proveedor
{ "id": 21, "nombre": "Corralón Centro", "cuit": "30-00000000-7",
  "telefono": "291 000-0002", "email": "ventas@corralon-centro.com",
  "rubro_habitual": { "clave": "materiales", "nombre": "MATERIALES" }, "es_generico": false,
  "puede": { "editar": true, "ver_gastado": true } }
```

### 10.2 Lo que Proveedores expone

`App\Modules\Proveedores\Contracts\ProveedoresConsulta`: `existe(ProveedorId)`,
`resumen(ProveedorId): {id, nombre, cuit, rubroHabitual}` (lo que "trae sus datos" al elegirlo en
la factura, F-42), `generico(): ProveedorId`, `esGenerico(ProveedorId): bool`.

### 10.3 Contrato "Gastado por obra" (F-57) — puerto

`App\Modules\Proveedores\Application\Ports\GastadoPorProveedor::porObra(ProveedorId, AlcanceObras): GastadoPorObra`,
implementado por **Gastos** en semana 3 (agregado sobre `gasto_renglones` de gastos no anulados,
agrupado por `obra_id`, con `null` = sin obra). Semana 1: `GastadoPorProveedorNulo` devuelve vacío.

```json
// GET /api/proveedores/21/gastado-por-obra (semana 3; en semana 1 "filas": [] y totales en 0)
{
  "filas": [
    { "obra": { "id": 12, "numero": "2-131-2026", "nombre_trabajo": "Piso industrial nave 3" }, "renglones": 4, "importe": "2489000.00" },
    { "obra": null, "renglones": 1, "importe": "45000.00" }
  ],
  "total": { "renglones": 5, "importe": "2534000.00" }
}
```

## 11. Trazabilidad F-XX → RN → test

| F-XX | Reglas | Tests (PHPUnit, `tests/Feature/Proveedores/…`, `tests/Unit/Proveedores/…`) |
|---|---|---|
| F-55 | RN-PRV-01…04 | `test_listado_ordena_alfabeticamente_sin_tildes` · `test_busca_por_parte_del_nombre_sin_tildes_ni_puntuacion` · `test_no_permite_dos_proveedores_con_el_mismo_nombre_normalizado` · `test_sin_permiso_de_proveedores_devuelve_403` |
| F-56 | RN-PRV-05…09 | `test_alta_solo_con_nombre` · `test_cuit_se_normaliza_a_once_digitos` · `test_cuit_con_digito_verificador_invalido_es_rechazado` · `test_cuit_no_se_repite` · `test_email_se_guarda_en_minusculas` · `test_solo_proveedores_editar_da_de_alta_y_edita` · `test_alta_con_idempotency_key_no_duplica` · Unit `ProveedoresParecidosTest`: `test_nombre_sin_forma_societaria_es_parecido`, `test_nombre_contenido_es_parecido`, `test_mitad_de_palabras_en_comun_es_parecido`, `test_ochenta_por_ciento_de_letras_es_parecido`, `test_parecidos_no_bloquean_el_alta`, `test_al_editar_no_se_muestra_a_si_mismo`, `test_devuelve_hasta_cinco_ordenados` |
| F-42 | RN-PRV-10 | `test_opciones_devuelve_lo_necesario_para_elegir_en_la_factura` (la integración con W-10 se testea en semana 3) |
| F-44 | RN-PRV-12…14 | `test_migracion_crea_un_solo_proveedor_generico` · `test_proveedor_generico_se_llama_gastos_sin_comprobante` · `test_proveedor_generico_no_se_edita` · `test_consulta_expone_el_generico` |
| F-57 | RN-PRV-15…17 | `test_gastado_por_obra_vacio_en_semana_1` · `test_gastado_por_obra_exige_permiso_de_gastos` · (semana 3) `test_gastado_por_obra_agrupa_por_obra_con_fila_sin_obra`, `test_gastado_por_obra_excluye_anulados`, `test_gastado_por_obra_con_alcance_propias_muestra_solo_sus_obras` |
| cap. 10 / D-040 | §4.1 | `test_proveedor_no_expone_saldos_ni_cuenta_corriente` (el recurso no tiene esas claves) |
| ADR-014 | §4.2 | `test_proveedor_ignora_adjuntos_y_links_en_el_payload` |

## 12. Dudas

**Todas resueltas por Juani el 12-nov-2026: va la propuesta** (detalle en `docs/dudas.md`):
DU-02 y DU-A02 (quién ve Proveedores al arrancar: §7), DU-10 (nombre idéntico y CUIT repetido se
rechazan, parecidos avisan: RN-PRV-04, 07, 08), DU-A18 (genérico "Gastos sin comprobante":
RN-PRV-12), DU-A19 (sin fusión; carga inicial depurada: RN-PRV-11), DU-A20 (orden alfabético:
RN-PRV-02). DU-23 → ADR-014: no afecta a este módulo salvo para aclarar que los adjuntos de la
factura son del gasto (§4.2). No quedan dudas abiertas en este módulo.
