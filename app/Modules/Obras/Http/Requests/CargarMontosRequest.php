<?php

declare(strict_types=1);

namespace App\Modules\Obras\Http\Requests;

use App\Modules\Obras\Application\Montos\CargarMontosAction;
use App\Modules\Obras\Application\Montos\CatalogoRubros;
use App\Modules\Obras\Domain\Enums\ModoCarga;
use App\Modules\Obras\Http\Rules\ImporteValido;
use App\Shared\Domain\Importe;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * `PUT /api/presupuestos/{id}/montos` (W-13): `{modo: por_rubro, montos: {clave: "1500000.00"|null}}`
 * o `{modo: solo_total, total: "25650000.00"}`. Lista cerrada: EPP o un rubro inexistente ("insumo")
 * dan 422 (HU-018 c. 3). Importes como string decimal, sin negativos ni más de 2 decimales.
 */
final class CargarMontosRequest extends ObrasRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $modo = $this->input('modo');

        return [
            'modo' => ['required', Rule::in([ModoCarga::PorRubro->value, ModoCarga::SoloTotal->value])],
            'montos' => [Rule::requiredIf($modo === ModoCarga::PorRubro->value), 'prohibited_unless:modo,'.ModoCarga::PorRubro->value, 'array'],
            'montos.*' => ['nullable', new ImporteValido],
            'total' => [Rule::requiredIf($modo === ModoCarga::SoloTotal->value), 'prohibited_unless:modo,'.ModoCarga::SoloTotal->value, new ImporteValido],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $montos = $this->input('montos');
            $validas = $this->container->make(CatalogoRubros::class)->clavesPresupuestables();

            foreach (is_array($montos) ? array_keys($montos) : [] as $clave) {
                if (! in_array($clave, $validas, true)) {
                    $validator->errors()->add("montos.{$clave}", 'Ese rubro no se presupuesta o no está en la lista.');
                }
            }
        });
    }

    public function modo(): ModoCarga
    {
        return ModoCarga::from($this->string('modo')->toString());
    }

    /**
     * @return array<string, Importe|null>
     */
    public function montos(): array
    {
        if ($this->modo() === ModoCarga::SoloTotal) {
            return [CargarMontosAction::CLAVE_TOTAL => Importe::desde(ImporteValido::texto($this->input('total')))];
        }

        $montos = [];

        foreach ((array) $this->input('montos', []) as $clave => $valor) {
            $montos[(string) $clave] = $valor === null || $valor === '' ? null : Importe::desde(ImporteValido::texto($valor));
        }

        return $montos;
    }
}
