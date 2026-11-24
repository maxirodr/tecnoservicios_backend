<?php

declare(strict_types=1);

namespace App\Modules\Gastos\Http\Requests;

use App\Modules\Gastos\Application\Correccion\CambiosGasto;
use App\Shared\Concurrencia\VersionDeFila;
use Carbon\CarbonImmutable;

/**
 * `PATCH /api/gastos/{id}` (W-34, RN-GAS-37): campos de cabecera opcionales; `renglones` = la lista completa
 * (con `id`, se conserva o cambia; sin `id`, nuevo; el que no viene, se quita); `adjuntos` = la lista
 * completa; `updated_at` = la versión que se abrió (409). Tipo de documento y origen no se leen.
 */
final class CorregirGastoRequest extends GastosRequest
{
    private const CAMPOS_CABECERA = ['numero', 'fecha', 'proveedor_id', 'rendido_por', 'periodo_desde', 'periodo_hasta'];

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'numero' => ['sometimes', 'nullable', 'string', 'max:'.self::LARGO_NUMERO],
            'fecha' => ['sometimes', 'nullable', 'date_format:Y-m-d'],
            'proveedor_id' => ['sometimes', 'nullable', 'integer'],
            'rendido_por' => ['sometimes', 'nullable', 'string', 'max:'.self::LARGO_RENDIDO_POR],
            'periodo_desde' => ['sometimes', 'nullable', 'date_format:Y-m-d'],
            'periodo_hasta' => ['sometimes', 'nullable', 'date_format:Y-m-d'],
            'adjuntos' => ['sometimes', 'array', 'max:'.self::MAX_ADJUNTOS],
            'adjuntos.*' => ['integer', 'distinct'],
            'renglones' => ['sometimes', 'array'],
            'renglones.*.id' => ['nullable', 'integer', 'distinct'],
            ...$this->reglasDeRenglones(),
            'confirmar_remito_repetido' => ['sometimes', 'boolean'],
            'confirmar_superposicion' => ['sometimes', 'boolean'],
            ...VersionDeFila::reglas(),
        ];
    }

    public function versionEsperada(): ?CarbonImmutable
    {
        return VersionDeFila::desde($this->input(VersionDeFila::CAMPO));
    }

    public function cambios(): CambiosGasto
    {
        $cabecera = [];

        foreach (self::CAMPOS_CABECERA as $campo) {
            if ($this->exists($campo)) {
                $valor = $this->input($campo);
                $cabecera[$campo] = is_string($valor) || is_int($valor) ? $valor : null;
            }
        }

        return new CambiosGasto(
            $cabecera,
            $this->exists('renglones') ? $this->renglones() : null,
            $this->exists('adjuntos') ? $this->ids('adjuntos') : null,
            $this->boolean('confirmar_remito_repetido'),
            $this->boolean('confirmar_superposicion'),
        );
    }
}
