# Brief común — Semana 4 · Tanda 1 (NS-2026-025 Tecnoservicios)

Sos parte del equipo de desarrollo. Rutas relativas a `C:\NexoSmart\Projects\tecnoservicios_project\`.

**Las reglas son las del brief de la Semana 3** (`tasks/brief-agentes-semana3.md`, secciones "Leé antes de codear" y
"Reglas de oro"): leelas completas. Valen todas, con estos cambios:
- **Tu contrato:**
  - `docs/especificacion/importacion.md`;
  - `docs/backlog.md`, sección **Semana 4** (HU-088…HU-102);
  - `docs/dudas.md`: DU-20, DU-21, DU-R09 y **DU-I01…I09, todas resueltas: va la propuesta**.
- **Vocabulario (DU-C01):** en todo lo visible se dice **"tarea"**. Las claves internas siguen siendo `pedidos`. Hay un
  test que falla si aparece "pedido" en un texto visible.
- **Datos reales:**
  - los `.xlsx` del cliente (minuta y rendiciones) **no se abren ni se copian ni se commitean**;
  - los tests usan plantillas armadas con datos ficticios, generadas en el test (openspout) o como fixtures ficticios
    en `tests/Fixtures/Importacion/`;
  - ningún dato de una fila leída va a los logs.
- **En paralelo:**
  - **QA prueba la Semana 3** en `…_qa_s3`: puertos 8340 y 5316, base `tecnoservicios_prueba_usuario`. No la toques.
  - Integración real: `tecnoservicios_integracion`, 8320/5312, la usa solo **Front S4** si la necesita.

| Agente | Worktree · rama | Base · puertos | Qué hace |
|---|---|---|---|
| **Back Importación** | `tecnoservicios_backend_gastos` · `feat/w4-importacion` | `tecnoservicios_testing_gastos` | Ver abajo. |
| **Front S4** | `tecnoservicios_frontend_gastos` · `feat/w4-front` | e2e `E2E_PORT=5325` | Ver abajo. |
| **Guías S4** | `tecnoservicios_backend_rendicion` · `docs/w4-guias` (docs) y `tecnoservicios_frontend_rendicion` (solo para capturas, rama `docs/w4-capturas`) | capturas `E2E_PORT=5327` | Ver abajo. |

**Back Importación:**
1. **Primero, la §16 de `importacion.md`:** alineá las otras specs con los diffs mínimos. Son `gastos.md` §4.3,
   RN-GAS-37 y §12.4; `rendicion.md` RN-REN-50 y §12.4.3; `pedidos.md` §4.6; `proveedores.md` RN-PRV-11;
   `modelo-datos.md` y `matriz-trazabilidad.md`.
2. **Después, HU-088…HU-101 en el backend:**
   - los contratos de §11.4, cada uno implementado en su módulo, con `bind`;
   - `importacion_corridas` e `importacion_filas`;
   - los comandos de §11.1, con ensayo por defecto, `--confirmar --aprobo=` y la huella del archivo;
   - la lectura de las plantillas de §5;
   - el informe Excel de vuelta;
   - las equivalencias de rubros y personas;
   - obras, presupuestos y montos; gastado y retirado (`saldo_inicial`); materiales;
   - tareas sin avisos, con la marca `carga_inicial`; documentos;
   - transacción por obra, idempotencia y la carpeta privada.
   - **La carga inicial de gastado solo se anula** (RN-IMP-27): Gastos rechaza corregirla.
3. **El caso de referencia de §13 es test de aceptación obligatorio:** ensayo, confirmación y conteos; correrlo dos
   veces no duplica.
4. **HU-100 y HU-101:** la parte del sistema, es decir el comando de administrador, las entregas encendidas y el
   checklist en `docs/deploy.md` (producción limpia, respaldo antes de confirmar, conteos de §12.3).

**Front S4:**
- **Lo visible de §7 (RN-IMP-40…42):**
  - la marca "Carga inicial", con su ayuda, en el detalle de la tarea en PC y celular;
  - "Solicitante: —" y "Pedida: —";
  - en W-33 y W-34, el documento "Carga inicial", el título de W-34 y **solo Anular** (sin Editar);
  - W-36, con el renglón de carga inicial;
  - W-48, con la columna fija "Hasta {corte} · Carga inicial" (RN-IMP-30);
  - W-37, con el grupo "Sin documento".
- **API:** programás contra la spec con la API mockeada. Lo que el back ya devuelve (`carga_inicial` en las tareas,
  `origen` en los gastos), usalo tal cual.

**Guías S4 (HU-102):**
- **Qué:** en `docs/entregas/semana-4/`, una guía de una página por sector con usuario, según `importacion.md` §12.4.
  - Todas tienen "Tu primer día": qué ves al entrar, cargar una tarea, tomarla y completarla.
  - Administración y compras, además: cargar una factura y leer la rendición.
  - Jefaturas: Tareas de mi gente y Carga por persona.
- **Capturas:** de datos ficticios, sacadas con la infraestructura de `e2e-capturas/` del front y guardadas en
  `docs/entregas/semana-4/capturas/`.
- **Textos:** los de las pantallas, al pie de la letra, y "tarea" en todo.
- **Además:**
  - la planilla de seguimiento del acompañamiento (HU-102 c2);
  - el borrador de las notas de entrega de la semana 4 (`docs/entregas/semana-4.md`, con el formato de
    `semana-2.md`).
- **No toques código.**
