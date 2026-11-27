# Módulo Importacion

**Propósito:** Carga inicial (puesta en marcha): las tareas abiertas de la minuta y las obras en ejecución con su
presupuesto, su gastado acumulado por rubro, su lista de materiales y lo retirado hasta el corte.

**Funciones del documento firmado que cubre:** F-62 (y lo que F-63 necesita del sistema).

**Reglas:** ver `docs/especificacion/importacion.md` (RN-IMP-01…46). Checklist del arranque en `docs/deploy.md`.

## Cómo se usa (solo en el servidor, sin pantalla ni rutas: RN-IMP-01)

```bash
php artisan importacion:plantilla obras|tareas          # plantilla vacía en IMPORTACION_DIR
php artisan importacion:obras obras.xlsx                # ensayo: no guarda nada, deja el informe
php artisan importacion:obras obras.xlsx --confirmar --aprobo=carla@ejemplo.com
php artisan importacion:tareas tareas.xlsx [--confirmar --aprobo=…]   # después de las obras
php artisan importacion:corridas                        # ensayos y cargas, con su huella y conteos
```

## Capas

- `Domain/` — lotes, modos, resultados, lectura tolerante de celdas (`Valores`), hojas y columnas de las plantillas,
  el informe. Sin Facades.
- `Application/` — los dos lotes (`Obras/`, `Tareas/`: leer las hojas, juntar faltantes, guardar por los contratos de
  cada módulo), la corrida (`Corridas/`: carpeta, bloqueo, huella, quién aprueba, ensayo en una transacción que se
  deshace) y los puertos de lectura y escritura de `.xlsx`.
- `Infrastructure/` — OpenSpout (`Xlsx/`), los modelos de `importacion_corridas` e `importacion_filas` y los comandos.
- `routes.php` — vacío a propósito.

Importacion no escribe tablas ajenas: usa `Obras\Contracts\CargaInicialDeObras`, `Pedidos\Contracts\CargaInicialDeTareas`,
`Gastos\Contracts\CargaInicialDeGastos`, `Rendicion\Contracts\MaterialesDeObra::agregarALaLista`,
`Rendicion\Contracts\CatalogoDeRubros` e `Identidad\Contracts\UsuariosConsulta` (§11.4, ADR-003). Lo que el módulo
dueño rechaza llega como `Shared\CargaInicial\CargaRechazada` y va al informe como faltante; la marca de carga inicial y
"quién figura" de las actividades los pone `Shared\Actividad\CargaInicialEnCurso`.
