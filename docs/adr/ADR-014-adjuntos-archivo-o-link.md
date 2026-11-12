# ADR-014 · Adjuntos: archivo subido o link

- **Estado:** Aceptada · **Fecha:** 2026-11-12 · **Decide:** Juani (amplía D-022 del documento firmado) · **Rol:** CTO + Seguridad

## Contexto
El documento firmado dice que los archivos siguen en Drive y el sistema guarda solo links
(D-022). Juani decidió (12-nov) que **donde hoy va un link, se pueda subir el archivo o poner el
link, según convenga**: por ejemplo, la foto del remito desde el celular al completar un pedido,
o el PDF de una factura que no está escaneada en el Drive.

## Decisión
- Un solo mecanismo transversal en `app/Shared`: tabla **`adjuntos`** polimórfica.
  Cada adjunto es **un archivo o un link** (`tipo` = `archivo` | `link`), nunca los dos.
- Lugares que lo usan (cada uno es un `contexto`):

| Dónde | Contexto | Cantidad | F-XX / W-XX |
|---|---|---|---|
| Pedido: archivos y links | `pedido` | varios | F-27 · W-08, W-43 |
| Pedido: comprobante de cierre | `comprobante_cierre` | varios | F-31 · W-29, W-42 |
| Presupuesto: oferta técnica | `presupuesto` | uno (obligatorio) | F-16, F-18 · W-06, W-21 |
| Presupuesto: OC / OT / contrato / mail de aprobación | `aprobacion` | uno | F-16 · W-06, W-21 |
| Obra: certificados y otros documentos | `documento_obra` | varios | F-19 · W-20 |
| Gasto: factura o tickets | `comprobante_gasto` | varios | F-43, F-44 · W-10, W-35 |
| Retiros: remito | `remito` | uno | F-47, F-66 · W-46, W-48 |
| Equipo en reparación: presupuesto e inventario | `equipo_presupuesto`, `equipo_inventario` | uno c/u | F-67 · W-49 |

  La **carpeta de Drive de la obra** sigue siendo solo link (una carpeta no se sube).
- **Almacenamiento:** disco privado del servidor (`storage/app/private/adjuntos/AAAA/MM/`),
  con nombre aleatorio (UUID). Entra en el backup diario. Sin servicios pagos nuevos. El disco
  es configurable (`ADJUNTOS_DISK`) para poder pasar a un almacenamiento de objetos sin tocar el código.
- **Descarga:** solo por `GET /api/adjuntos/{id}`. El endpoint autoriza con la Policy del
  recurso dueño (si no podés ver el pedido, no podés bajar su comprobante) y responde con
  `Content-Disposition: attachment`, el MIME detectado y `X-Content-Type-Options: nosniff`.
  Nunca se sirve desde `public/`.
- **Validación en el borde:**
  - Tipos permitidos: PDF, imágenes (JPG, PNG, WEBP, **HEIC/HEIF** de iPhone), Excel, Word,
    CSV y TXT.
  - El tipo se valida por contenido, no por extensión. Ejecutables, HTML y SVG se rechazan.
  - Tamaño máximo: 20 MB por archivo, configurable.
  - Los links tienen que ser `http(s)`.
- **Subida:** `POST /api/adjuntos` (multipart) devuelve un id temporal. El caso de uso del
  recurso (crear pedido, completar, cargar gasto…) lo asocia dentro de su transacción. Los no
  asociados se purgan a las 24 h.
- **Borrar un adjunto** deja actividad (ADR-010) y borra el archivo físico. Un gasto anulado
  conserva sus adjuntos.

## Consecuencias
- Desaparecen las columnas de URL sueltas del modelo (ver `modelo-datos.md`) y la tabla
  `pedido_links`: todo pasa por `adjuntos`.
- El front tiene un componente compartido `AdjuntosField` con "Subir archivo" o "Pegar link",
  y en el celular permite usar la cámara.
- `seguridad.md` suma la sección de adjuntos (tests de tipo rechazado, tamaño e IDOR en la descarga).
- Hay que avisarle al cliente que esto amplía lo firmado (D-022) a su favor. Lo comunica Juani.
