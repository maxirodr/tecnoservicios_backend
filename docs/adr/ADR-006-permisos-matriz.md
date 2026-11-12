# ADR-006 · Permisos: matriz sector × función con nivel y alcance

- **Estado:** Propuesta (el alcance "solo sus obras" requiere OK de Juani) · **Fecha:** 2026-11-12 · **Rol:** CTO + Seguridad

## Contexto
D-037 y F-60: "para cada sector y cada módulo se elige editar, solo ver o sin acceso", y la
empresa la cambia cuando quiere. Pero el cap. 3 también dice "las ingenieras ven la rendición
**de sus obras**", y F-35 "todos ven todos los pedidos; se puede restringir". Con tres valores
no se puede expresar "solo las de mis obras".

## Decisión
- Tablas `sectores`, `funciones` (catálogo de filas de la matriz) y `permisos`
  (`sector_id`, `funcion_id`, `nivel` ∈ `sin_acceso|ver|editar`, `alcance` ∈ `todas|propias`).
- El catálogo de funciones y la **configuración inicial del cap. 3** se cargan **por migración**
  (los seeders no corren en producción). Detalle en `docs/especificacion/identidad.md`.
- Una sola puerta: `PermisoService::puede(User, Funcion, Nivel, ?recurso)`, usada por las
  Policies. **Ningún rol escrito en el código.** El menú (F-02) sale del mismo servicio vía
  `GET /api/me` → `permisos: { <funcion>: { nivel, alcance } }`.
- `alcance = propias`: en funciones de obra, solo obras donde la persona es responsable; en
  pedidos, los que pidió, tiene, sigue o son de su gente.
- Reglas del documento que no son matriz viven en Policies, documentadas y testeadas: el
  propietario toma, bloquea y completa su pedido; quien lo pidió lo reabre; el responsable de
  obra cambia su estado (además de quien tenga la función "Estado de obra").
- Jerarquía: `users.jefe_id`. "Mi gente" = subordinados recursivos (CTE recursivo).
- Cada endpoint tiene test de permitido, denegado e IDOR.

## Consecuencias
En W-15, las celdas de funciones de obra suman la opción "solo sus obras". Si Juani no la
aprueba, `alcance` queda fijo en `todas` y la regla de las ingenieras queda escrita en código
(peor: deja de ser configurable por la empresa).
