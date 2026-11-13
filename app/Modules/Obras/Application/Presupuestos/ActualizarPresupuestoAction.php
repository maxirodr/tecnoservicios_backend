<?php

declare(strict_types=1);

namespace App\Modules\Obras\Application\Presupuestos;

use App\Modules\Obras\Application\Obras\HistorialDeCampos;
use App\Modules\Obras\Domain\Models\Presupuesto;
use App\Shared\Actividad\RegistroActividad;
use App\Shared\Adjuntos\Adjunto;
use Illuminate\Database\Connection;
use Illuminate\Validation\ValidationException;

/**
 * `PATCH /api/presupuestos/{id}` (F-18, F-20): corrige N.º, revisión (una revisión nueva no es otro
 * presupuesto, RN-OBR-13), descripción, oferta técnica y documento de aprobación. Cambiar un adjunto
 * borra el anterior; el principal no queda sin oferta ni sin revisión. `es_principal` no cambia.
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
     *
     * @throws ValidationException
     */
    public function handle(Presupuesto $presupuesto, array $datos): Presupuesto
    {
        $this->exigirObligatoriosDelPrincipal($presupuesto, $datos);

        return $this->db->transaction(function () use ($presupuesto, $datos): Presupuesto {
            if (isset($datos['numero']) && $datos['numero'] !== $presupuesto->numero) {
                $this->numeros->exigir($datos['numero'], 'numero', $presupuesto->id);
            }

            $antes = HistorialDeCampos::valores($presupuesto, self::CAMPOS);
            $presupuesto->fill(array_intersect_key($datos, array_flip(['numero', 'revision', 'descripcion'])));

            if (array_key_exists('aprobacion', $datos)) {
                $presupuesto->aprobacion_tipo = $datos['aprobacion']?->tipo;
                $presupuesto->aprobacion_numero = $datos['aprobacion']?->numero;
                $this->reemplazar($presupuesto, $presupuesto->adjuntoAprobacion(), $datos['aprobacion']?->adjunto, Presupuesto::CONTEXTO_APROBACION, 'aprobacion.adjunto');
            }

            if (array_key_exists('oferta', $datos)) {
                $this->reemplazar($presupuesto, $presupuesto->oferta(), $datos['oferta'], Presupuesto::CONTEXTO_OFERTA, 'oferta');
            }

            $presupuesto->save();
            $cambios = HistorialDeCampos::diferencias($antes, HistorialDeCampos::valores($presupuesto, self::CAMPOS));

            if ($cambios !== []) {
                $this->actividad->registrar($presupuesto, self::ACCION, $cambios);
            }

            return $presupuesto->load('adjuntos');
        });
    }

    /**
     * @param  array{revision?: string|null, oferta?: int|null}  $datos
     *
     * @throws ValidationException
     */
    private function exigirObligatoriosDelPrincipal(Presupuesto $presupuesto, array $datos): void
    {
        if (! $presupuesto->es_principal) {
            return;
        }

        if (array_key_exists('revision', $datos) && $datos['revision'] === null) {
            throw ValidationException::withMessages(['revision' => ['La revisión es obligatoria en el presupuesto principal.']]);
        }

        if (array_key_exists('oferta', $datos) && $datos['oferta'] === null) {
            throw ValidationException::withMessages(['oferta' => ['La oferta técnica es obligatoria en el presupuesto principal.']]);
        }
    }

    /**
     * El adjunto nuevo reemplaza al anterior, que se borra (obras.md §10.2).
     *
     * @throws ValidationException
     */
    private function reemplazar(Presupuesto $presupuesto, ?Adjunto $actual, ?int $nuevo, string $contexto, string $campo): void
    {
        if ($actual?->id === $nuevo) {
            return;
        }

        if ($nuevo !== null) {
            $this->adjuntos->asociar([$nuevo], $presupuesto, $contexto, $campo);
        }

        if ($actual !== null) {
            $this->adjuntos->quitar([$actual->id], $presupuesto);
        }
    }
}
