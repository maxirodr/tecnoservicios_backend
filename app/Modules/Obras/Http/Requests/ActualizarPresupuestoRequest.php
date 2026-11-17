<?php

declare(strict_types=1);

namespace App\Modules\Obras\Http\Requests;

use App\Modules\Obras\Application\Presupuestos\DatosAprobacion;
use App\Modules\Obras\Http\Requests\Concerns\EditaPresupuesto;
use Illuminate\Validation\Validator;

/**
 * `PATCH /api/presupuestos/{id}`: lo que viene cambia; `aprobacion` reemplaza entera (lo que no viene
 * se borra). El principal no se convierte en adicional ni al revés (RN-OBR-22).
 */
final class ActualizarPresupuestoRequest extends ObrasRequest
{
    use EditaPresupuesto;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return $this->reglasEdicionPresupuesto('');
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(fn (Validator $validator) => $this->validarEdicionPresupuesto($validator, ''));
    }

    /**
     * @return array{numero?: string, revision?: string|null, descripcion?: string|null, oferta?: int|null, aprobacion?: DatosAprobacion|null}
     */
    public function datos(): array
    {
        return $this->datosEdicionPresupuesto('');
    }
}
