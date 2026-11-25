# Estado · Prueba de usuario Semana 3 · ronda 1 · **cortada a pedido** (2026-11-25, ~15 h Argentina)

La ronda se paró a mitad de la **tanda 1** (roles 1, 2 y 6). **No hay veredicto** ni hallazgos consolidados.

- **Código:** backend `staging` @ `473e2da`, front `staging` @ `a78552c`.
- **Entorno:** base `tecnoservicios_prueba_usuario` reseteada con `migrate:fresh --seed` (verificada con tinker). Usuarios dados de alta con Diego: Dora (Dirección, id 7), QAS3 Calidad (Calidad, sin acceso, id 8), QA Lead S3 A (id 9), QA Lead S3 B (id 10). Procesos parados.
- **Ingresos reales:** 10, a las 14:20 Argentina (las sesiones quedaron fuera del repo).

## Hasta dónde llegó cada rol

| Rol | Llegó a | Datos en la base |
|---|---|---|
| 1 · Caso de referencia | Cargó la obra y los renglones de `rendicion.md` §9 por pantalla y comparó; iba a anular la 0004-00000020 y medir la actualización sin recargar. | obra 1 "Ampliación de nave de ensayo", obra 2 `QAS3-R1 Obra de control` |
| 2 · Carga de facturas y gastos | Cargas de W-10/W-35 en curso. | obras 4 y 5 `QAS3-R2` |
| 6 · Tareas, regresión y backend | Textos del backend revisados; tarea de punta a punta en curso. | obras 3 y 6 `QAS3-R6`, 3 tareas |

En total quedaron 32 gastos y 48 renglones. **Ningún rol llegó a escribir su borrador.**

## Capturas

- `capturas/` tiene 46 capturas: 43 `F-…` de lo que anda, más `R1-01-1.png`, `R1-01-2.png` y `R2-01-1.png`.
- Las tres de hallazgo son posibles hallazgos **sin verificar ni describir**: no cuentan como hallazgos.

## Para retomar

La anulación de la 0004-00000020 puede o no haberse hecho: revisarlo en la base antes de seguir. Hay dos caminos:

- **Seguir sobre esta base.** El rol 1 verifica en la base qué renglones de §9.2 están, completa los que falten y hace la anulación.
- **Volver a correr la ronda completa.** Reset y tanda 1 de nuevo: es lo más limpio para la comparación al centavo.
