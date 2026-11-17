<?php

declare(strict_types=1);

namespace App\Modules\Obras\Application\Presupuestos;

use App\Modules\Obras\Application\Obras\HistorialDeCampos;
use App\Modules\Obras\Application\Obras\VersionDeObra;
use App\Modules\Obras\Domain\Models\Presupuesto;
use App\Shared\Actividad\RegistroActividad;
use App\Shared\Adjuntos\Adjunto;
use Illuminate\Database\Connection;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\ValidationException;

/**
 * `PATCH /api/presupuestos/{id}` (F-18, F-20) y `presupuesto_principal` de `PATCH /api/obras/{id}`
 * (PU-101): corrige N.º, revisión (una revisión nueva no es otro presupuesto, RN-OBR-13), descripción,
 * oferta técnica y documento de aprobación. Cambiar un adjunto borra el anterior; el principal no queda
 * sin oferta ni sin revisión. `es_principal` no cambia. Si algo cambió, la obra cambia de versión: quien
 * tiene abierto Editar obra recibe 409 en vez de pisarlo.
 */
final readonly class ActualizarPresupuestoAction
{
    public const ACCION = 'presupuesto_editado';

    private const CAMPOS = ['numero', 'revision', 'descripcion', 'aprobacion_tipo', 'aprobacion_numero'];

    public function __construct(
        private Connection $db,
        private NumeroUnico $numeros,
        private AdjuntosDeObra $adjuntos,
        private RegistroActividad $actividad,
    ) {}

    /**
     * @param  array{numero?: string, revision?: string|null, descripcion?: string|null, oferta?: int|null, aprobacion?: DatosAprobacion|null}  $datos
     * @param  string  $prefijo  de las claves de error (`presupuesto_principal` desde Editar obra)
     *
     * @throws ValidationException|HttpResponseException
     */
    public function handle(Presupuesto $presupuesto, array $datos, string $prefijo = ''): Presupuesto
    {
        $this->exigirObligatoriosDelPrincipal($presupuesto, $datos, $prefijo);

        return $this->db->transaction(function () use ($presupuesto, $datos, $prefijo): Presupuesto {
            // Primero la obra y después el presupuesto, en el mismo orden que Editar obra: sin deadlocks.
            VersionDeObra::bloquear($presupuesto->obra_id);

            if (isset($datos['numero']) && $datos['numero'] !== $presupuesto->numero) {
                $this->numeros->exigir($datos['numero'], self::clave($prefijo, 'numero'), $presupuesto->id);
            }

            $antes = HistorialDeCampos::valores($presupuesto, self::CAMPOS);
            $presupuesto->fill(array_intersect_key($datos, array_flip(['numero', 'revision', 'descripcion'])));
            $adjuntosCambiados = false;

            if (array_key_exists('aprobacion', $datos)) {
                $presupuesto->aprobacion_tipo = $datos['aprobacion']?->tipo;
                $presupuesto->aprobacion_numero = $datos['aprobacion']?->numero;
                $adjuntosCambiados = $this->reemplazar($presupuesto, $presupuesto->adjuntoAprobacion(), $datos['aprobacion']?->adjunto, Presupuesto::CONTEXTO_APROBACION, self::clave($prefijo, 'aprobacion.adjunto'));
            }

            if (array_key_exists('oferta', $datos)) {
                $adjuntosCambiados = $this->reemplazar($presupuesto, $presupuesto->oferta(), $datos['oferta'], Presupuesto::CONTEXTO_OFERTA, self::clave($prefijo, 'oferta')) || $adjuntosCambiados;
            }

            $presupuesto->save();
            $cambios = HistorialDeCampos::diferencias($antes, HistorialDeCampos::valores($presupuesto, self::CAMPOS));

            if ($cambios !== []) {
                $this->actividad->registrar($presupuesto, self::ACCION, $cambios);
            }

            if ($cambios !== [] || $adjuntosCambiados) {
                VersionDeObra::subir($presupuesto->obra_id);
            }

            return $presupuesto->load('adjuntos');
        });
    }

    /**
     * @param  array{revision?: string|null, oferta?: int|null}  $datos
     *
     * @throws ValidationException
     */
    private function exigirObligatoriosDelPrincipal(Presupuesto $presupuesto, array $datos, string $prefijo): void
    {
        if (! $presupuesto->es_principal) {
            return;
        }

        if (array_key_exists('revision', $datos) && $datos['revision'] === null) {
            throw ValidationException::withMessages([self::clave($prefijo, 'revision') => ['La revisión es obligatoria en el presupuesto principal.']]);
        }

        if (array_key_exists('oferta', $datos) && $datos['oferta'] === null) {
            throw ValidationException::withMessages([self::clave($prefijo, 'oferta') => ['La oferta técnica es obligatoria en el presupuesto principal.']]);
        }
    }

    /**
     * El adjunto nuevo reemplaza al anterior, que se borra (obras.md §10.2).
     *
     * @return bool si cambió el adjunto
     *
     * @throws ValidationException
     */
    private function reemplazar(Presupuesto $presupuesto, ?Adjunto $actual, ?int $nuevo, string $contexto, string $campo): bool
    {
        if ($actual?->id === $nuevo) {
            return false;
        }

        if ($nuevo !== null) {
            $this->adjuntos->asociar([$nuevo], $presupuesto, $contexto, $campo);
        }

        if ($actual !== null) {
            $this->adjuntos->quitar([$actual->id], $presupuesto);
        }

        return true;
    }

    private static function clave(string $prefijo, string $campo): string
    {
        return $prefijo === '' ? $campo : "{$prefijo}.{$campo}";
    }
}
