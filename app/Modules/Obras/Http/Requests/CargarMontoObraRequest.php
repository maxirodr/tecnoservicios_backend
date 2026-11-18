<?php

declare(strict_types=1);

namespace App\Modules\Obras\Http\Requests;

use App\Modules\Obras\Http\Rules\ImporteValido;
use App\Shared\Concurrencia\VersionDeFila;
use App\Shared\Domain\Importe;
use Carbon\CarbonImmutable;

/**
 * `PUT /api/presupuestos/{id}/monto-obra`: `{monto_obra: "36500000.00" | null, updated_at?}` (RN-OBR-36).
 * Con `updated_at` (el del presupuesto en el GET), 409 si cambió desde entonces (PU-201).
 */
final class CargarMontoObraRequest extends ObrasRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'monto_obra' => ['present', 'nullable', new ImporteValido],
            ...VersionDeFila::reglas(),
        ];
    }

    public function montoObra(): ?Importe
    {
        $valor = $this->input('monto_obra');

        return $valor === null || $valor === '' ? null : Importe::desde(ImporteValido::texto($valor));
    }

    /** `updated_at` que tenía el presupuesto cuando se abrió la pantalla (PU-201). */
    public function versionEsperada(): ?CarbonImmutable
    {
        return VersionDeFila::desde($this->input(VersionDeFila::CAMPO));
    }
}
