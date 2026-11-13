# Shared

Piezas transversales que usan todos los módulos. No contiene reglas de negocio de un módulo.
Lo registra `Providers/SharedServiceProvider.php` (rutas en `routes.php`, prefijo `/api`).

- `Http/ApiResponse.php` — envelope único `{ data, message, errors }` (+ `meta`/`links` en listados paginados).
- `Http/RespondsWithEnvelope.php` — trait para controllers (`ok`, `created`, `fail`).
- `Http/ApiExceptionRenderer.php` — toda excepción → envelope (401, 403, 404, 405, 409, 419, 422, 429, 500 sin detalles en prod).
- `Http/Middleware/RemoveStackHeaders.php` — quita `X-Powered-By` y `Server`.
- `Providers/ModuleServiceProvider.php` — base de los providers de módulo (registra `routes.php` del módulo bajo `/api`).
- `Console/SupervisorSyncCommand.php` — `php artisan supervisor:sync`.
- `Actividad/` — registro de quién hizo qué (ADR-010, F-14).
- `Idempotencia/` — middleware `idempotent` (ADR-011).
- `Adjuntos/` — archivo subido o link (ADR-014, HU-025).

Funciones transversales del documento firmado que viven acá o se apoyan en esto: F-03 (buscador general), F-13 (uso desde el celular: mismo API), F-14 (registro de quién hizo qué).

## Registro de actividad (ADR-010)

Tabla `actividades` (solo inserción). Se escribe **desde el caso de uso, dentro de su transacción**, con el
nombre de negocio de la acción. El usuario es el autenticado (null si lo hace el sistema).

```php
use App\Shared\Actividad\RegistroActividad;

final readonly class EditarObraAction
{
    public function __construct(private RegistroActividad $actividad) {}

    public function handle(Obra $obra, array $datos): Obra
    {
        return DB::transaction(function () use ($obra, $datos) {
            $antes = $obra->only(array_keys($datos));
            $obra->update($datos);
            $cambios = [];
            foreach ($obra->getChanges() as $campo => $despues) {
                if (array_key_exists($campo, $antes)) {
                    $cambios[$campo] = ['antes' => $antes[$campo], 'despues' => $despues];
                }
            }
            if ($cambios !== []) {                       // guardar sin cambios no registra (F-14)
                $this->actividad->registrar($obra, 'editado', $cambios);
            }
            return $obra;
        });
    }
}
```

- Acciones: `creado`, `editado`, `tomado`, `bloqueado`, `retomado`, `comentado`, `reasignado`, `completado`,
  `reabierto`, `estado_cambiado`, `anulado`… Los adjuntos ya dejan `adjunto_agregado` / `adjunto_quitado`
  solos (no los registres de nuevo desde el módulo).
- Leer el historial: `App\Shared\Actividad\HistorialActividad::delSujeto($obra, $porPagina)` → paginado,
  el más nuevo arriba, con `user` cargado. El módulo arma el texto "Qué cambió" con sus palabras y filtra
  lo que el usuario no puede ver (p. ej. montos, RN-OBR-28). El endpoint (`GET /api/obras/{id}/historial`)
  es del módulo.
- `sujeto_type` es `getMorphClass()` del modelo. Si un módulo define alias con `Relation::morphMap`, que lo
  haga antes de escribir la primera actividad (cambiarlo después deja filas viejas con la clase).

## Idempotencia (ADR-011)

Middleware alias `idempotent`. Va en los POST que crean o hacen transiciones, **después** de `auth:sanctum`:

```php
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/pedidos', [PedidoController::class, 'store'])->middleware('idempotent');
    Route::post('/pedidos/{pedido}/completar', [PedidoController::class, 'completar'])->middleware('idempotent');
});
```

- El front manda `Idempotency-Key: <uuid>` (uno por envío de formulario, el mismo en los reintentos).
  Sin cabecera, el request se procesa normal.
- Misma clave, misma ruta (método + path) y mismo payload → la **misma respuesta guardada** (mismo
  código y cuerpo), sin re-ejecutar, con cabecera `Idempotent-Replayed: true`.
- La clave es **por usuario** (`unique(user_id, clave)`): la misma clave de otra persona es otra operación.
- Misma clave con otro payload u otra ruta → 422 `errors.idempotency_key`. Clave que no es UUID → 422.
- El primer envío todavía en proceso → 409 con `Retry-After: 1`. Un "en curso" de más de 60 s se
  considera abandonado y se retoma.
- Solo se guardan las respuestas 2xx: un 422/409/500 libera la clave y el reintento se procesa de nuevo.
- Vigencia 24 h; `idempotencia:purgar` corre cada hora (scheduler, `routes/console.php`).
- Las transiciones siguen siendo idempotentes por sí mismas (completar uno ya completado → 409 del módulo).

## Adjuntos (ADR-014, HU-025)

Endpoints (todos con sesión):

| Método y ruta | Qué hace | Errores |
|---|---|---|
| `POST /api/adjuntos` | multipart `{contexto, archivo}` o JSON `{contexto, url, nombre?}` → `Adjunto` temporal (201). Acepta `Idempotency-Key`. | 401 · 422 tipo por contenido, > `ADJUNTOS_MAX_MB`, link no `http(s)`, contexto no registrado · 429 (30/min por usuario) |
| `GET /api/adjuntos/{id}` | baja el archivo: `Content-Disposition: attachment`, MIME detectado, `nosniff` | 401 · 404 si no existe, es link, no puede ver el dueño o es temporal de otro |
| `DELETE /api/adjuntos/{id}` | temporal: solo quien lo subió · asociado: editar el dueño; borra el archivo y deja `adjunto_quitado` | 401 · 403 ve pero no edita · 404 no lo ve |

### Cómo lo usa un módulo dueño

1. **Implementar `AutorizaAdjuntos`** con la Policy / matriz del recurso (por contexto), por ejemplo en Obras:

```php
use App\Shared\Adjuntos\AutorizaAdjuntos;

final readonly class ObraAdjuntos implements AutorizaAdjuntos
{
    public function __construct(private Permisos $permisos) {}

    public function puedeVer(User $user, Model $sujeto): bool    // $sujeto: Presupuesto / ObraDocumento
    {
        return $this->permisos->puede($user, 'obras', Nivel::Ver) && $this->enAlcance($user, $sujeto);
    }

    public function puedeEditar(User $user, Model $sujeto): bool
    {
        return $this->permisos->puede($user, 'obras', Nivel::Editar) && $this->enAlcance($user, $sujeto);
    }
}
```

2. **Registrar sus contextos** en el `boot()` de su provider (clase o instancia; se resuelve recién al usarse):

```php
public function boot(): void
{
    parent::boot();
    $contextos = $this->app->make(\App\Shared\Adjuntos\ContextosAdjuntos::class);
    $contextos->registrar('presupuesto', ObraAdjuntos::class);
    $contextos->registrar('aprobacion', ObraAdjuntos::class);
    $contextos->registrar('documento_obra', ObraAdjuntos::class);
}
```

   Un contexto no registrado no existe (`POST` → 422). Nombres acordados (modelo-datos.md): `pedido`,
   `comprobante_cierre`, `presupuesto`, `aprobacion`, `documento_obra`, `comprobante_gasto`, `remito`,
   `equipo_presupuesto`, `equipo_inventario` (máx. 30 caracteres, `[a-z_]`).

3. **Asociar en el caso de uso**, dentro de su transacción, con los ids que mandó el front:

```php
DB::transaction(function () use ($datos) {
    $documento = ObraDocumento::create([...]);
    $this->adjuntos->asociar($datos['adjuntos'], $documento, 'documento_obra');   // AdjuntosService
    $this->actividad->registrar($obra, 'documento_agregado', [...]);
});
```

   - Solo asocia temporales **subidos por el usuario autenticado** y del **contexto** pedido; si alguno no
     sirve no asocia ninguno y tira `ValidationException` en `adjuntos` con "Ese adjunto no se puede usar
     acá." (RN-IDE-46). Si el campo del módulo se llama distinto (`oferta`, `aprobacion.adjunto`), capturar
     y relanzar con esa clave.
   - **No autoriza**: el caso de uso ya verificó que el usuario puede editar el recurso (403 si no).
   - Cuántos adjuntos admite el campo (uno / varios / obligatorio) lo valida el FormRequest del módulo.
   - Si la transacción falla, los adjuntos siguen temporales y se purgan a las 24 h.
4. **Quitar / reemplazar** desde el caso de uso: `AdjuntosService::quitar($ids, $sujeto)` (borra fila,
   deja `adjunto_quitado` con el nombre y borra el archivo cuando la transacción confirma). Un gasto
   anulado no llama a `quitar`: conserva sus adjuntos.
5. **Mostrar** los adjuntos del recurso: `Adjunto::query()->whereMorphedTo('sujeto', $documento)->with('subidoPor')`
   y `App\Shared\Adjuntos\Http\AdjuntoResource` (nunca expone `disco` ni `ruta`).
6. Links sueltos que no son adjuntos (p. ej. carpeta de Drive de la obra): validar con
   `App\Shared\Adjuntos\Http\Rules\LinkHttp`.

### Validación y almacenamiento

- Tipo **por contenido** (`DetectorTipoArchivo`, finfo + estructura interna): PDF, JPG, PNG, WEBP, HEIC/HEIF,
  XLSX/XLS, DOCX/DOC, CSV, TXT. Se rechaza todo lo demás (ejecutables, HTML, SVG, ZIP renombrado, texto con HTML).
- Máximo `ADJUNTOS_MAX_MB` (20). PHP (`upload_max_filesize`, `post_max_size`) y nginx (`client_max_body_size`)
  tienen que aceptar al menos eso.
- Disco `ADJUNTOS_DISK` (por defecto `adjuntos` = `storage/app/private`, sin `serve`), ruta
  `adjuntos/AAAA/MM/<uuid>` sin extensión. El nombre original solo se muestra y va en `Content-Disposition`.
- `adjuntos:purgar-huerfanos` corre cada hora: borra fila y archivo de los temporales con más de 24 h.
