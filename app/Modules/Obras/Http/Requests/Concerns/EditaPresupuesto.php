<?php

declare(strict_types=1);

namespace App\Modules\Obras\Http\Requests\Concerns;

use App\Modules\Obras\Application\Presupuestos\DatosAprobacion;
use App\Modules\Obras\Domain\Valores\Revision;
use App\Modules\Obras\Domain\Valores\ValorInvalido;
use Illuminate\Validation\Validator;

/**
 * Corrección parcial de un presupuesto: lo que viene cambia; `aprobacion` reemplaza entera (lo que no
 * viene se borra). La usan `PATCH /api/presupuestos/{id}` (sin prefijo) y `PATCH /api/obras/{id}` con
 * `presupuesto_principal` (PU-101), así las dos rutas validan igual.
 */
trait EditaPresupuesto
{
    use ArmaPresupuestos;

    /** @var array<string, string|null> prefijo => revisión normalizada cuando viene sin N.º */
    private array $revisionesSueltas = [];

    /**
     * @return array<string, mixed>
     */
    protected function reglasEdicionPresupuesto(string $prefijo): array
    {
        return [
            self::clave($prefijo, 'numero') => ['sometimes', 'required', 'string', 'max:60'],
            self::clave($prefijo, 'revision') => ['sometimes', 'nullable', 'string', 'max:20'],
            self::clave($prefijo, 'oferta') => ['sometimes', 'nullable', 'integer'],
            self::clave($prefijo, 'descripcion') => ['sometimes', 'nullable', 'string', 'max:200'],
            ...$this->reglasAprobacion(self::clave($prefijo, 'aprobacion')),
            // El principal no se convierte en adicional ni al revés (RN-OBR-22).
            self::clave($prefijo, 'es_principal') => ['prohibited'],
        ];
    }

    protected function validarEdicionPresupuesto(Validator $validator, string $prefijo): void
    {
        $campoRevision = self::clave($prefijo, 'revision');

        if ($this->exists(self::clave($prefijo, 'numero'))) {
            $this->normalizarPresupuesto($validator, $prefijo, false);
        } elseif ($this->textoONull($campoRevision) !== null && ! $validator->errors()->has($campoRevision)) {
            try {
                $this->revisionesSueltas[$prefijo] = Revision::desde((string) $this->textoONull($campoRevision))->valor;
            } catch (ValorInvalido $e) {
                $validator->errors()->add($campoRevision, $e->getMessage());
            }
        }

        $this->validarAprobacion($validator, self::clave($prefijo, 'aprobacion'));
    }

    /**
     * @return array{numero?: string, revision?: string|null, descripcion?: string|null, oferta?: int|null, aprobacion?: DatosAprobacion|null}
     */
    protected function datosEdicionPresupuesto(string $prefijo): array
    {
        $datos = [];
        $normalizado = $this->normalizado($prefijo);

        if ($normalizado !== null) {
            $datos['numero'] = $normalizado['numero'];

            if ($this->exists(self::clave($prefijo, 'revision')) || $normalizado['revision'] !== null) {
                $datos['revision'] = $normalizado['revision'];
            }
        } elseif ($this->exists(self::clave($prefijo, 'revision'))) {
            $datos['revision'] = $this->revisionesSueltas[$prefijo] ?? null;
        }

        if ($this->exists(self::clave($prefijo, 'descripcion'))) {
            $datos['descripcion'] = $this->textoONull(self::clave($prefijo, 'descripcion'));
        }

        if ($this->exists(self::clave($prefijo, 'oferta'))) {
            $datos['oferta'] = $this->enteroONull(self::clave($prefijo, 'oferta'));
        }

        if ($this->exists(self::clave($prefijo, 'aprobacion'))) {
            $datos['aprobacion'] = $this->datosAprobacion(self::clave($prefijo, 'aprobacion'));
        }

        return $datos;
    }
}
