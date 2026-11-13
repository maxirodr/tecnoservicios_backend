<?php

declare(strict_types=1);

namespace App\Modules\Obras\Http\Requests;

use App\Modules\Obras\Http\Rules\ImporteValido;
use App\Shared\Domain\Importe;

/** `PUT /api/presupuestos/{id}/monto-obra`: `{monto_obra: "36500000.00" | null}` (RN-OBR-36). */
final class CargarMontoObraRequest extends ObrasRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'monto_obra' => ['present', 'nullable', new ImporteValido],
        ];
    }

    public function montoObra(): ?Importe
    {
        $valor = $this->input('monto_obra');

        return $valor === null || $valor === '' ? null : Importe::desde(ImporteValido::texto($valor));
    }
}
