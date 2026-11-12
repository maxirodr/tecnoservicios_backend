# ADR-004 · Idioma del código

- **Estado:** Aceptada (Juani, 2026-11-12) · **Fecha:** 2026-11-12 · **Rol:** CTO + Tech Lead

## Contexto
El brief deja elegir entre `Obra` y `Job`. Traducir "rendición", "rubro", "propietario" o
"N.º de presupuesto" pierde significado y obliga a un glosario mental en cada lectura; el
cliente, el documento, las maquetas y los tests hablan esas palabras.

## Decisión
- **Dominio en español, sin tildes ni ñ** (lenguaje ubicuo): `Obra`, `Presupuesto`, `Pedido`,
  `Gasto`, `Renglon`, `Rubro`, `Proveedor`, `Sector`, `Aviso`, `Rendicion`, `EquipoReparacion`.
  Estados y valores como en el documento: `confirmada`, `en_ejecucion`, `terminada`,
  `no_iniciada`, `en_curso`, `bloqueada`, `completada`, `urgente`, `prioritario`, `normal`.
- **Técnico en inglés**: sufijos y verbos de framework (`CreateObraAction`, `ObraController`,
  `ObraResource`, `PedidoStatus`, `store`, `update`, `handle`).
- **Tablas** en español snake_case plural, con `$table` explícito siempre (`gasto_renglones`,
  `proveedores`, `sectores`): Eloquent pluraliza en inglés.
- **Tests** en español con la regla en el nombre: `test_completar_pedido_sin_comentario_es_rechazado`.
- **Rutas API** en español: `/api/obras`, `/api/pedidos/{pedido}/completar`.
- **UI**: siempre las palabras del cliente.
