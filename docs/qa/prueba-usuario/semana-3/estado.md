# Estado · Prueba de usuario Semana 3 · ronda 1 · pausada el viernes 27/11 a las ~15:15 (Argentina) · sigue el lunes

**No hay veredicto ni hallazgos consolidados.** Los borradores son parciales; los IDs `R1-NN` y `R2-NN` todavía no se renumeraron.

## Entorno

- **Código:** backend `staging` @ `fb123b7`, front `staging` @ `262ce06`.
- **Base:** `tecnoservicios_prueba_usuario`, reseteada el 27/11 con `migrate:fresh --seed` (verificada con tinker).
- **Usuarios dados de alta con Diego:** Dora (Dirección, id 7), QAS3 Calidad (sin acceso, id 8), QA Lead S3 A (id 9), QA Lead S3 B (id 10).
- **Ingresos reales:** 10, a las 14:42.
- **Procesos:** parados. Los worktrees `_qa_s3` y la base quedan como están.

## Tanda 1 (roles 1, 2 y 6), cortada a mitad

### Rol 1 · Caso de referencia

**Hecho:**
- La obra 1 "Ampliación de nave de ensayo" está cargada por pantalla, con sus presupuestos, monto y 4 materiales.
- Los **21 renglones de §9.2** están verificados en la base. Única diferencia: el concepto del retiro dice "Endurecedor superficial", sin "(bolsa)".
- La factura **0002-00000555 ya está anulada**. La 0004-00000020 **no**.

**Falta:**
- la comparación al centavo de §9.3 y §9.4;
- anular la 0004-00000020 y medir que la rendición se actualice sola;
- terminar el borrador.

### Rol 2 · Carga de facturas y gastos

**Hecho:**
- W-10: el reparto en vivo, la factura repetida (aviso y 422), el alta rápida W-38 y el redondeo de decimales.
- Datos en las obras 5 y 6 `QAS3-R2`.

**Falta:** W-35, W-46, W-33 (incluido el "549.000 de 905.600"), W-34, W-14 y el buscador.

### Rol 6 · Tareas, regresión y backend

**Hecho:** solo los datos: las obras 3 y 4 `QAS3-R6` y el gasto 1, que ya está anulado.

**Falta:** todo lo demás.

## Posibles hallazgos ya vistos (reproducidos 2 veces, falta consolidarlos)

| Posible hallazgo | Visto por | Qué pasa |
|---|---|---|
| R1-01 = R2-02 | Rol 1 y Rol 2 | **W-48 no carga en una obra sin renglones de MATERIALES sin material.** La API devuelve `sin_material: null` y el front exige un texto; la pantalla dice "No pudimos cargar esta información…". |
| R1-02 | Rol 1 | El alta rápida W-38 hace que la factura de atrás muestre errores de validación, sin que se haya tocado "Guardar factura". |
| R2-01 | Rol 2 | Con la base sin gastos, W-33 dice primero "No hay gastos con estos filtros." (es el mes en curso) y recién con "Limpiar filtros" aparece "Todavía no hay gastos cargados.". |
| R2-03 | Rol 2 | El aviso de factura repetida muestra la fecha como "27-nov". DU-G13 pide dd/mm/aaaa. |

## Para el lunes

1. Levantar los procesos con `scratchpad\s3\proc\arrancar.sh`.
2. Ver si siguen andando las sesiones. Si Redis se reinició, hay que volver a ingresar.
3. Relanzar los roles 1, 2 y 6 para que sigan desde su borrador, sin rehacer lo cargado.
4. Seguir con las tandas 2 (roles 3 y 4) y 3 (roles 5 y 8), y cerrar con el QC.
