# Borrador A · Verificador A: conflictos, guardado y salida (ronda 3)

- **Fecha:** 2026-11-18, 13:40–14:15 (hora del servidor)
- **Personas / sesiones:** Diego (`diego.json`, Gerencia de Administración; dos pestañas = dos `page` del mismo contexto), Hernán (`hernan.json`, Jefatura de Proyectos), Carla (`carla.json`, Compras), Pablo (`pablo.json`, Ingeniería; Mi perfil)
- **Ingresos reales gastados:** 0
- **Datos creados (prefijo QA3-A):** obras 2-701…2-705-2026 "QA3-A Obra conflictos 1…5" (ids 1, 3, 6, 7, 8) y "QA3-A Obra alta guardando" (id 11); proveedor "QA3-A Ferretería Conflicto" (id 10); usuario "QA3-A Usuario Conflicto" (`qa3a.usuario@empresa-ejemplo.com`, id 13); documentos "QA3-A …" en las obras 1, 7 y 8. Varias obras quedaron "En ejecución" (6, 7, 8).
- **Persona del seeder tocada:** Pablo (teléfono cargado por Diego desde Usuarios y nombre desde Mi perfil). **Quedó como estaba:** nombre "Pablo", teléfono vacío (cambia solo su `updated_at`).
- **Scripts:** `C:\Users\juanc\AppData\Local\Temp\claude\C--Users-juanc\3fd8de0f-88e6-40e4-a658-bb42248ba145\scratchpad\qa3\A\` (`lib.mjs`, `crear-obra.mjs`, `conflicto-obra.mjs`, `ficha.mjs`, `prov.mjs`, `obra.mjs`, `ident.mjs`, `extra.mjs`)
- **Demora de red:** 6 s en PATCH/POST (obra, proveedor y estado), 8 s en `POST /api/obras/{id}/documentos` y 6 s en `POST /api/adjuntos` (link), como en la ronda 2. Entre guardados que chocan se esperó ≥ 1,6 s.
- **Consola:** en todas las corridas, solo los "Failed to load resource" del navegador por los 409/422/500 esperados. Ningún error JS ni warning de React.

## Tabla de verificación

| PU | título corto | estado | pantallas revisadas | evidencia | nota |
|---|---|---|---|---|---|
| PU-101 | La Revisión (y el resto del presupuesto principal) se pisaba sin aviso | ✅ | W-23 `/obras/{id}/editar`: Revisión (dos pestañas Diego, 2 veces), N.º de presupuesto, N.º de OC / OT / contrato (aprobación) (dos pestañas Diego); Revisión vs Cliente (Diego + Hernán, 2 veces) | `PU-101-r3-1/2.png`, `PU-101-r3-num-1/2.png`, `PU-101-r3-oc-1/2.png`, `PU-101-r3-hernan1-1/2.png`, `PU-101-r3-hernan2-1/2.png` · `PATCH /api/obras/{id}` → 409 | B ve "Alguien cambió esto mientras lo tenías abierto. Actualizá y revisá." con "Actualizar", y la base queda con lo de A. Con "Actualizar", el formulario muestra lo guardado. Después vuelve a guardar sin problema (200). La descripción del principal no está en el formulario W-23: no se pudo chocar. |
| PU-102 | El conflicto en la obra guardaba igual la Revisión | ✅ | W-23: A cambia el cliente, B cambia Revisión + Planta (dos pestañas Diego, y Diego + Hernán) | `PU-102-r3-1/2.png`, `PU-102-r3-b-1/2.png` · un solo `PATCH /api/obras/{id}` → 409 | Ya no sale `PATCH /api/presupuestos`. Con el 409 no se guarda nada de B: la Revisión y la planta quedan como estaban. Con "Actualizar" se ve lo de A. |
| PU-103 | "Quitar" un archivo mientras guarda el documento | ✅ | W-20 en las obras 8 y 7, red lenta de 8 s (2 veces) | `PU-103-r3-1/2.png`, `PU-103-r3-b-1/2.png` | Mientras guarda quedan deshabilitados "quitar", "Subir archivo", "Pegar link", Fecha y Nombre (no se pueden tocar). Se guarda con `uno.pdf, dos.pdf`, igual que lo que se ve. |
| PU-104 | Irse mientras se guarda | ✅ | Proveedor (atrás, 2 veces), documento (atrás, 2 veces; link de la barra tapado por el diálogo), cambiar estado (atrás, 2 veces), Editar obra (link "Proveedores"), Nueva obra (link "Proveedores") | `PU-104-r3-prov-1.png`, `PU-104-r3-prov-b-1.png`, `PU-104-r3-atrasdoc-1/2.png`, `PU-104-r3-atrasestado-1/2.png`, `PU-104-r3-estado-b-1/2.png`, `PU-104-r3-obra-1.png`, `PU-104-r3-alta-1.png` | En todos sale "Estamos guardando. Esperá unos segundos." / "No cierres esta pestaña hasta que termine." / "Entendido". La persona no se va: al terminar ve "Guardamos los cambios de …", "Agregamos el documento a la obra.", "La obra pasó a En ejecución.", "Guardamos los cambios de la obra." u "Obra creada. Ya se le pueden cargar pedidos.". Ya no dice "se pierde" de algo que se guarda. (Ver observación O-1.) |
| PU-107 | Ctrl+clic en "Ese N.º ya está en …" / "Ya está cargado: …" | ✅ | W-06 Nueva obra (2 veces), alta de proveedor (2 veces) | `PU-107-r3-obra-1.png`, `PU-107-r3-obra-b-1.png`, `PU-107-r3-prov-1.png`, `PU-107-r3-prov-b-1.png` | Ctrl+clic abre `/obras/1` (o `/proveedores/10`) en otra pestaña. En la original, el formulario sigue protegido: el link de la barra pregunta "Tenés cambios sin guardar. ¿Salir igual?". El diálogo "Nuevo proveedor" sigue abierto con lo escrito. |
| PU-108 | Mi perfil pisaba lo que gerencia cargó en Usuarios | ✅ | F-10 `/perfil` (Pablo) vs F-58 Usuarios (Diego), 2 veces | `PU-108-r3-1.png`, `PU-108-r3-b-1.png` · `PATCH /api/me` → 409 | Pablo ve el aviso de conflicto con "Actualizar", y el teléfono que cargó Diego no se pierde. Con "Actualizar" se ve el teléfono de Diego. Después, volver a guardar da 200 y "Guardamos tus datos.". |
| PU-111 | El aviso de conflicto o de error quedaba fuera de la vista | ✅ | W-23 con 409 (6 corridas), con 500 simulado en PC y en iPhone SE (375×667); W-06 con 500 simulado en PC y en iPhone SE | `PU-111-r3-edit-1.png`, `PU-111-r3-edit-se-1.png`, `PU-111-r3-nueva-1.png`, `PU-111-r3-nueva-se-1.png`, `PU-101-r3-1.png` | Al tocar "Guardar" al pie, la pantalla sube hasta el aviso y el foco va a él. 409: y=114 de 768. 500: "El sistema tuvo un problema. Volvé a intentar en un momento." en y=114 (PC) / y=106 de 667 (iPhone SE). Lo mismo en el diálogo de proveedor, en el de usuario y en Mi perfil (409). |
| PU-114 | "Cancelar" y Escape en diálogos con cambios | ✅ | Editar y alta de proveedor, `/proveedores/nuevo`, Editar usuario, Agregar documento; con "Cancelar" y con Escape | `PU-114-r3-prov-cancelar-1.png`, `PU-114-r3-prov-escape-1.png`, `PU-114-r3-usuario-cancelar-1.png`, `PU-114-r3-usuario-escape-1.png`, `PU-114-r3-doc-cancelar-1.png`, `PU-114-r3-doc-escape-1.png` | El mismo criterio en todos: "Tenés cambios sin guardar. ¿Salir igual?" / "Si salís, se pierde lo que cambiaste." / "Seguir editando" · "Salir". (Decisión a validar por Juani: sin observación.) |
| PU-115 | "Cancelar" mientras se agrega un link no lo cancelaba | ✅ | W-06 "Presupuesto (oferta técnica)", red lenta de 6 s en `POST /api/adjuntos` (2 veces) | `PU-115-r3-1.png`, `PU-115-r3-b-1.png` | "Cancelar" sigue habilitado, pero corta el pedido: a los 8 s el campo sigue en "Subir archivo · Pegar link" y el link no aparece. |
| PU-121 | Solo espacios al final decía "Guardamos…" | ✅ | W-23, con espacios al final de Cliente (2 obras), N.º de presupuesto, Revisión, Nombre del trabajo, Comprador · nombre y Planta | `PU-121-r3-1.png`, `PU-121-r3-b-1.png`, `PU-121-r3-x-1.png` | En todos sale "No había cambios para guardar.", sin PATCH y sin cambiar el `updated_at`. |
| PU-123 | Plazos sin cambios decía "Guardamos los plazos…" | ✅ | W-50 `/usuarios/plazos` (Diego): sin tocar, y cambiar y volver al mismo valor | `PU-123-r3-1.png` | En los dos casos sale "No había cambios para guardar." y no hay `PUT` (la base sigue en 1 / 7 con el mismo `updated_at`). |
| PU-127 | En Mi perfil, "No había cambios" seguía puesto junto a los errores | ✅ | F-10 `/perfil` (Pablo) | `PU-127-r3-1.png` | Después de "No había cambios para guardar.", al borrar el nombre queda solo "Completá el nombre.". Lo mismo con un nombre de 300 ("El nombre puede tener hasta 120 caracteres.") y un teléfono de 60 ("El teléfono puede tener hasta 50 caracteres."). |
| PU-002 | Dos personas editando lo mismo: la segunda pisaba | ✅ | Obra (datos + presupuesto principal; dos pestañas y Diego + Hernán), proveedor (dos pestañas Diego, y Diego + Carla), usuario (dos pestañas Diego), Mi perfil vs Usuarios (Pablo + Diego) | ver PU-101/102/108 · `PU-002-r3-prov-1.png`, `PU-002-r3-prov-carla-1.png`, `PU-002-r3-usuario-1.png` · `PATCH /api/proveedores/10` → 409, `PATCH /api/usuarios/13` → 409 | En todas las pantallas sale "Alguien cambió esto mientras lo tenías abierto. Actualizá y revisá." con "Actualizar", y nada se pierde sin aviso. En proveedor, "Actualizar" carga lo de la otra persona y el reintento guarda ("Guardamos los cambios de QA3-A Ferretería Conflicto."). Guardados con ≥ 1,6 s de diferencia: el límite del mismo segundo no se probó (es aceptado). |
| PU-003 | "Cancelar" durante el guardado cerraba, pero guardaba igual | ✅ | W-20 (red lenta): "Cancelar", Escape, clic afuera, "quitar" y "Atrás"; W-22 con "Atrás" | `PU-003-r3-1.png`, ver PU-103 y PU-104 | "Cancelar" está deshabilitado. Escape y el clic afuera no cierran. "quitar" y los campos no se pueden tocar. "Atrás" frena con "Estamos guardando…". Termina con "Agregamos el documento a la obra.". |
| PU-006 | F5 / atrás / Cancelar / pestaña borraban cambios sin preguntar | ✅ | W-23 (F5, "Atrás", "Cancelar"), W-06 (link tras Ctrl+clic), diálogos (Cancelar/Escape) | `PU-006-r3-f5-1.png`, `PU-006-r3-atras-1.png`, `PU-006-r3-cancelar-1.png`, ver PU-107/114 | F5: el navegador pide confirmar (`beforeunload`) y lo escrito sigue. "Atrás" y "Cancelar": "Tenés cambios sin guardar. ¿Salir igual?" y "Planta no guardada" sigue en el campo. Los agujeros de la ronda 2 (PU-107, 114, 104) están cerrados. |
| PU-029 | "Guardar" sin cambios | ✅ | W-23 (obras 6 y 1), Editar proveedor, Editar usuario, Mi perfil, Plazos | `PU-029-r3-obra-1.png`, `PU-029-r3-prov-1.png`, `PU-123-r3-1.png` | En todas las pantallas sale "No había cambios para guardar." y no viaja nada (ni PATCH ni PUT). Los bordes de la ronda 2 (PU-121, 123, 127) están cerrados. |

**Conteo:** ✅ 16 · ⚠️ 0 · ❌ 0

## Hallazgos nuevos

Ninguno. Busqué regresiones alrededor de los arreglos:
- "Actualizar" y reintento después del 409 en obra, proveedor, usuario y Mi perfil;
- conflicto con dos personas que tienen almacenamiento separado (Diego + Hernán, Diego + Carla);
- espacios en 6 campos de W-23;
- salir mientras se guarda en el alta de obra;
- Escape y clic afuera mientras se guarda un documento;
- Ctrl+clic en los dos avisos de repetido;
- tocar un campo y volver al mismo valor en Plazos.

No encontré nada que se rompiera.

Nota: en una sola corrida (Diego + Hernán), con la máquina cargada, "Actualizar" tardó 3,5 s en refrescar el formulario (con "Actualizando…" en el botón). Mi script leyó los campos antes de que terminara y guardó encima, y eso dio un segundo 409 (correcto). Cuando el script esperó a que terminara, lo repetí 2 veces y salió bien: no es un hallazgo.

## Observaciones de UX (decisiones a validar por Juani)

- **O-1 · "Estamos guardando. Esperá unos segundos.":** se entiende y protege bien. Dos detalles para que decida Juani:
  1. Después de "Entendido", al terminar el guardado la persona queda en el destino del guardado (la ficha) y no en la pantalla que había elegido (por ejemplo, Proveedores). Tiene que volver a tocar el link. Es razonable, pero el diálogo no lo dice.
  2. Escape y el clic afuera mientras guarda un diálogo no muestran nada (el diálogo simplemente no se cierra). "Atrás" y los links, en cambio, muestran el aviso. Es aceptable porque el botón está con spinner y "Cancelar" deshabilitado.

  Captura: `capturas/PU-104-r3-atrasdoc-1.png`.
- **"Cancelar/Escape con cambios pregunta":** sin observación. El criterio es el mismo en proveedor (alta, edición y `/proveedores/nuevo`), usuario y documento (`capturas/PU-114-r3-prov-cancelar-1.png`).

## Lo que no pude probar y por qué

- **Descripción del presupuesto principal en conflicto:** el formulario W-23 no tiene ese campo; solo se edita en los adicionales. Choqué Revisión, N.º y N.º de OC (aprobación).
- **Adjunto del documento de aprobación en conflicto** (archivo, no el número): no lo choqué por separado. Va en el mismo `PATCH /api/obras` con versión, igual que el N.º de OC, que sí choqué.
- **Dos guardados en el mismo segundo:** no lo probé (límite aceptado en PU-002).
- **500 real del backend:** lo simulé en el navegador con el cuerpo que manda el backend. No corté servicios: en esta tanda no se para nada.

---
Firma · Verificador A: 16 PU verificados (PU-101, 102, 103, 104, 107, 108, 111, 114, 115, 121, 123, 127, 002, 003, 006, 029) con dos pestañas y dos personas, y con red lenta donde la ronda 2 la usó. Cubrí obra, presupuesto principal, proveedor, usuario y Mi perfil vs Usuarios. 0 ingresos reales.
