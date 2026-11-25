# Seguimiento del acompañamiento (HU-102 c2)

Planilla interna de NexoSmart (QC la completa cada viernes desde la apertura). Mide el criterio de aceptación n.º 4
del documento: "El equipo deja de usar la minuta en Excel porque el sistema le resulta más fácil"
(`qa/plan-de-pruebas.md` §7).

**Cómo se cuenta, cada semana (lunes a domingo):**

- **Filas nuevas en la minuta:** filas agregadas a la minuta (de consulta desde la apertura) con fecha de esa
  semana. Se cuentan mirando la minuta con administración; no se copian datos de las filas a esta planilla.
- **Tareas nuevas en el sistema:** tareas creadas en la semana, sin las de la carga inicial (QC lo cuenta en
  producción: fecha de alta en la semana y `carga_inicial` en falso; solo el número).
- **Usuarios que entraron al menos 3 días** sobre los usuarios activos.
- **Hallazgos:** qué siguen haciendo en el Excel y por qué (qué le falta o qué le cuesta). Se registra, no se
  agregan funciones fuera del documento.

**Cierre:** el acompañamiento se cierra con **dos semanas seguidas sin filas nuevas en la minuta** (y todos los
usuarios activos usándolo). La columna "Racha" cuenta las semanas seguidas en 0; con 2, se cierra.

| Semana | Desde | Hasta | Filas nuevas en la minuta | Tareas nuevas en el sistema | Usuarios que entraron ≥ 3 días / activos | Racha sin filas | Hallazgos | Revisó |
|---|---|---|---|---|---|---|---|---|
| 1 | | | | | / | | | |
| 2 | | | | | / | | | |
| 3 | | | | | / | | | |
| 4 | | | | | / | | | |
| 5 | | | | | / | | | |
| 6 | | | | | / | | | |

**Ejemplo de cómo se lee (ficticio, no es del cliente):**

| Semana | Filas nuevas en la minuta | Tareas nuevas en el sistema | Usuarios ≥ 3 días / activos | Racha sin filas |
|---|---|---|---|---|
| 1 | 4 | 38 | 11 / 13 | 0 |
| 2 | 0 | 45 | 13 / 13 | 1 |
| 3 | 0 | 41 | 13 / 13 | 2 → **se cierra** |

**Cierre del acompañamiento:** fecha ______ · semanas ______ y ______ sin filas nuevas · lo firma Juani.
