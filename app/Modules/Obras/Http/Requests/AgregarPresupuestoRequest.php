<?php

declare(strict_types=1);

namespace App\Modules\Obras\Http\Requests;

use App\Modules\Obras\Application\Presupuestos\DatosPresupuesto;
use App\Modules\Obras\Http\Requests\Concerns\ArmaPresupuestos;
use Illuminate\Validation\Validator;

/**
 * `POST /api/obras/{id}/presupuestos` (W-21): adicional con N.º obligatorio; revisión, oferta técnica,
 * OC y descripción opcionales (resuelta DU-A14).
 */
final class AgregarPresupuestoRequest extends ObrasRequest
{
    use ArmaPresupuestos;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return $this->reglasPresupuesto('', false);
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $this->normalizarPresupuesto($validator, '', false);
            $this->validarAprobacion($validator, 'aprobacion');
        });
    }

    public function datos(): DatosPresupuesto
    {
        return $this->datosPresupuesto('');
    }
}
