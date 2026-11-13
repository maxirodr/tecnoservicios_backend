<?php

declare(strict_types=1);

namespace App\Modules\Obras\Http\Requests;

use App\Modules\Obras\Application\Presupuestos\DatosAprobacion;
use App\Modules\Obras\Domain\Valores\Revision;
use App\Modules\Obras\Domain\Valores\ValorInvalido;
use App\Modules\Obras\Http\Requests\Concerns\ArmaPresupuestos;
use Illuminate\Validation\Validator;

/**
 * `PATCH /api/presupuestos/{id}`: lo que viene cambia; `aprobacion` reemplaza entera (lo que no viene
 * se borra). El principal no se convierte en adicional ni al revés (RN-OBR-22).
 */
final class ActualizarPresupuestoRequest extends ObrasRequest
{
    use ArmaPresupuestos;

    private ?string $revisionSuelta = null;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'numero' => ['sometimes', 'required', 'string', 'max:60'],
            'revision' => ['sometimes', 'nullable', 'string', 'max:20'],
            'oferta' => ['sometimes', 'nullable', 'integer'],
            'descripcion' => ['sometimes', 'nullable', 'string', 'max:200'],
            ...$this->reglasAprobacion('aprobacion'),
            'es_principal' => ['prohibited'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($this->exists('numero')) {
                $this->normalizarPresupuesto($validator, '', false);
            } elseif ($this->textoONull('revision') !== null && ! $validator->errors()->has('revision')) {
                $this->normalizarRevision($validator);
            }

            $this->validarAprobacion($validator, 'aprobacion');
        });
    }

    /**
     * @return array{numero?: string, revision?: string|null, descripcion?: string|null, oferta?: int|null, aprobacion?: DatosAprobacion|null}
     */
    public function datos(): array
    {
        $datos = [];
        $normalizado = $this->normalizado('');

        if ($normalizado !== null) {
            $datos['numero'] = $normalizado['numero'];

            if ($this->exists('revision') || $normalizado['revision'] !== null) {
                $datos['revision'] = $normalizado['revision'];
            }
        } elseif ($this->exists('revision')) {
            $datos['revision'] = $this->revisionSuelta;
        }

        if ($this->exists('descripcion')) {
            $datos['descripcion'] = $this->textoONull('descripcion');
        }

        if ($this->exists('oferta')) {
            $datos['oferta'] = $this->enteroONull('oferta');
        }

        if ($this->exists('aprobacion')) {
            $datos['aprobacion'] = $this->datosAprobacion('aprobacion');
        }

        return $datos;
    }

    private function normalizarRevision(Validator $validator): void
    {
        try {
            $this->revisionSuelta = Revision::desde((string) $this->textoONull('revision'))->valor;
        } catch (ValorInvalido $e) {
            $validator->errors()->add('revision', $e->getMessage());
        }
    }
}
