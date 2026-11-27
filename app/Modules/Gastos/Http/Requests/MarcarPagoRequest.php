<?php

declare(strict_types=1);

namespace App\Modules\Gastos\Http\Requests;

use App\Modules\Gastos\Application\Carga\ReglasDeCabecera;
use App\Modules\Gastos\Application\FechaEmpresa;
use App\Shared\Concurrencia\VersionDeFila;
use Carbon\CarbonImmutable;
use Illuminate\Validation\Validator;

/**
 * `POST /api/gastos/{id}/marcar-paga` (`{fecha?, updated_at}`, sin fecha = hoy) y `…/marcar-impaga`
 * (`{updated_at}`), RN-GAS-59. La fecha del pago no puede ser posterior a hoy (Argentina).
 */
final class MarcarPagoRequest extends GastosRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'fecha' => ['sometimes', 'nullable', 'date_format:Y-m-d'],
            ...VersionDeFila::reglas(),
        ];
    }

    /**
     * @return list<callable(Validator): void>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            $fecha = self::texto($this->input('fecha'));

            if ($fecha !== null && ! $validator->errors()->has('fecha') && $fecha > FechaEmpresa::hoy()) {
                $validator->errors()->add('fecha', ReglasDeCabecera::FECHA_FUTURA);
            }
        }];
    }

    /** La fecha del pago: la que vino o, si no, hoy. */
    public function fecha(): string
    {
        return self::texto($this->input('fecha')) ?? FechaEmpresa::hoy();
    }

    public function versionEsperada(): ?CarbonImmutable
    {
        return VersionDeFila::desde($this->input(VersionDeFila::CAMPO));
    }
}
