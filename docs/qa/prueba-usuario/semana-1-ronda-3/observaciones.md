# Observaciones de UX para Juani · Prueba de usuario Semana 1 · Ronda 3 (2026-11-18)

Son las decisiones de UX que tomó desarrollo y que Juani todavía tiene que validar (§1 del prompt de la ronda 3). Se probaron todas y **ninguna se reporta como hallazgo**. Las cinco funcionan como se describieron. Lo que sigue son detalles que a algún rol no lo convencieron del todo, o la confirmación de que no hay nada que observar.

| # | Decisión | ¿Convence? | Roles |
|---|---|---|---|
| O-1 | Intentar salir mientras guarda → "Estamos guardando. Esperá unos segundos." | Sí, con dos detalles | Verificador A, Cazador de regresiones |
| O-2 | Cancelar o Escape en un diálogo con cambios → pregunta antes de cerrar | Sí, sin observación | Verificador A, QA celular |
| O-3 | Fecha anterior a 2000 o posterior al año actual + 10 → "Revisá la fecha: es de {año}." | Sí, con un detalle | Verificador C |
| O-4 | REV00 → "Las revisiones empiezan en REV01." | Sí; el texto de REV100 no | Verificador C |
| O-5 | "Guardar proveedor" no se deshabilita ante un repetido; el aviso pasa a rojo | Sí, sin observación | Verificador C |

## O-1 · "Estamos guardando. Esperá unos segundos."

Se entiende y protege bien. Apareció en proveedor, documento, cambiar estado, Editar obra y Nueva obra, y ya no dice "se pierde" de algo que se guarda (PU-104 ✅). Dos detalles para que decida Juani:

1. **Después de "Entendido", la persona no llega adonde quería ir.** Al terminar el guardado queda en el destino del guardado (la ficha) y no en la pantalla que había elegido (por ejemplo, Proveedores). Tiene que volver a tocar el link. Es razonable, pero el diálogo no lo dice. (Verificador A)
2. **Escape, "Cancelar" y el clic afuera mientras guarda un diálogo no muestran nada.** El diálogo simplemente no se cierra y solo se ve "Guardando…" en el botón. En cambio, "Atrás" y los links de la barra sí muestran "Estamos guardando. Esperá unos segundos.". Los dos roles lo consideran aceptable, porque el botón tiene la ruedita y "Cancelar" está deshabilitado. Si Juani quiere coherencia con la salida por link, se podría mostrar el mismo texto. (Verificador A y Cazador de regresiones)

**Capturas:** `capturas/PU-104-r3-atrasdoc-1.png` (A) · `capturas/REG-ok-dialogo-guardando.png` (REG)

## O-2 · Cancelar o Escape con cambios pregunta antes de cerrar

Sin observación. El criterio es el mismo en todos los diálogos: alta y edición de proveedor, `/proveedores/nuevo`, Editar usuario y Agregar documento. Muestra "Tenés cambios sin guardar. ¿Salir igual?" / "Si salís, se pierde lo que cambiaste." / "Seguir editando" · "Salir". (Verificador A)

Al pasar, el QA celular vio que sin cambios Escape cierra todos los diálogos al instante, y el foco vuelve al botón que los abrió.

**Captura:** `capturas/PU-114-r3-prov-cancelar-1.png` (A)

## O-3 · Aviso de fecha lejana "Revisá la fecha: es de {año}."

Convence. Funciona en el alta, la edición, el certificado y el adicional, no bloquea, y los bordes son correctos (2000 y 2036 no avisan; 1999 y 2037 sí). El detalle es que **el aviso solo está mientras se carga**. Cuando la obra ya se guardó con 1900 → 2199, la ficha muestra "Fechas previstas 01/01/1900 → 31/12/2199" sin ninguna marca, y el aviso recién reaparece al abrir "Editar". Quien mira la ficha no se entera de que la fecha es rara. (Verificador C)

Si Juani valida el aviso, PU-028 y PU-117 quedan ✅ definitivos.

**Capturas:** `capturas/PU-117-r3-1.png` (aviso al cargar) · `capturas/PU-209-1.png` (ficha sin marca; la misma captura muestra los dos formatos de fecha de PU-209)

## O-4 · "Las revisiones empiezan en REV01."

El texto es claro y es el mismo en las tres pantallas (alta, edición y adicional), para REV00, REV0 y rev00. Lo que no convence es el caso vecino: **"REV100" da "Escribí la revisión como REV01."**. Es el mismo texto confuso que tenía PU-122 en su momento: la persona escribió en ese formato y no entiende qué cambiar. Para ese caso, el Verificador C propone algo como "La revisión va de REV01 a REV99.". (Verificador C)

**Captura:** `capturas/PU-122-r3-1.png` · script `C/p22-bordes.mjs`

## O-5 · "Guardar proveedor" habilitado ante un repetido, con el aviso en rojo

Sin observación. Se ve bien: un solo aviso rojo bajo el campo que choca, con el link a la ficha, y el 422 no agrega nada más. (Verificador C)

**Captura:** `capturas/PU-119-r3-1.png`
