<?php

declare(strict_types=1);

namespace App\Modules\Gastos\Http\Requests;

use App\Modules\Gastos\Application\Carga\DatosGasto;
use App\Modules\Gastos\Domain\Enums\Origen;
use App\Modules\Gastos\Domain\Enums\TipoDocumento;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * `POST /api/gastos` (GastoAlta, gastos.md §12.2), cualquier carga. `obra_id` de cada renglón tiene que
 * venir (null = sin obra). El `total` que manda el front no se lee: lo calcula el backend (§10 caso 47).
 * `tipo_documento`, si viene, tiene que ser el de la carga (no hay nota de crédito, resuelta DU-G05).
 */
final class CargarGastoRequest extends GastosRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'tipo_documento' => ['nullable', Rule::in(array_column(TipoDocumento::cases(), 'value'))],
            'origen' => ['required', Rule::in(Origen::valoresDeLaApi())],
            'numero' => ['nullable', 'string', 'max:'.self::LARGO_NUMERO],
            'fecha' => ['nullable', 'date_format:Y-m-d'],
            'proveedor_id' => ['nullable', 'integer'],
            'rendido_por' => ['nullable', 'string', 'max:'.self::LARGO_RENDIDO_POR],
            'periodo_desde' => ['nullable', 'date_format:Y-m-d'],
            'periodo_hasta' => ['nullable', 'date_format:Y-m-d'],
            'adjuntos' => ['nullable', 'array', 'max:'.self::MAX_ADJUNTOS],
            'adjuntos.*' => ['integer', 'distinct'],
            'renglones' => ['present', 'array'],
            ...$this->reglasDeRenglones(),
            'confirmar_remito_repetido' => ['sometimes', 'boolean'],
            'confirmar_superposicion' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            ...parent::messages(),
            'origen.required' => 'Elegí qué se carga.',
            'origen.in' => 'Elegí qué se carga.',
            'renglones.present' => 'Cargá al menos un renglón.',
        ];
    }

    /**
     * @return list<callable(Validator): void>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            $origen = Origen::tryFrom($this->string('origen')->toString());

            if ($origen !== null && $this->filled('tipo_documento') && $this->string('tipo_documento')->toString() !== $origen->tipoDocumento()->value) {
                $validator->errors()->add('tipo_documento', 'El valor no es válido.');
            }
        }];
    }

    public function datos(): DatosGasto
    {
        return new DatosGasto(
            Origen::from($this->string('origen')->toString()),
            self::texto($this->input('numero')),
            self::texto($this->input('fecha')),
            self::entero($this->input('proveedor_id')),
            self::texto($this->input('rendido_por')),
            self::texto($this->input('periodo_desde')),
            self::texto($this->input('periodo_hasta')),
            $this->ids('adjuntos'),
            $this->renglones(),
            $this->boolean('confirmar_remito_repetido'),
            $this->boolean('confirmar_superposicion'),
        );
    }
}
