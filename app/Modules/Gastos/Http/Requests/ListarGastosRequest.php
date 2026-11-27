<?php

declare(strict_types=1);

namespace App\Modules\Gastos\Http\Requests;

use App\Modules\Gastos\Application\FechaEmpresa;
use App\Modules\Gastos\Application\Lecturas\FiltrosDeGastos;
use App\Modules\Gastos\Application\Rubros;
use App\Modules\Gastos\Domain\Enums\TipoDocumento;
use App\Modules\Gastos\Infrastructure\Consultas\PorPago;
use App\Modules\Rendicion\Contracts\RubroDelCatalogo;
use Illuminate\Validation\Rule;

/**
 * `GET /api/gastos` (W-33, RN-GAS-30): `obra_id` = una obra o `sin_obra`. Sin `desde` ni `hasta`, del 1 del
 * mes en curso a hoy; con `desde=` o `hasta=` vacíos, sin ese límite (RN-GAS-35: "toda la obra").
 */
final class ListarGastosRequest extends GastosRequest
{
    public const SIN_OBRA = 'sin_obra';

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rubros = array_map(static fn (RubroDelCatalogo $r): string => $r->clave, $this->container->make(Rubros::class)->todos());

        return [
            'obra_id' => ['nullable', function (string $atributo, mixed $valor, \Closure $falla): void {
                if ($valor !== self::SIN_OBRA && ! (is_string($valor) && ctype_digit($valor)) && ! is_int($valor)) {
                    $falla('El valor no es válido.');
                }
            }],
            'proveedor_id' => ['nullable', 'integer'],
            'rubro' => ['nullable', Rule::in($rubros)],
            'desde' => ['nullable', 'date_format:Y-m-d'],
            'hasta' => ['nullable', 'date_format:Y-m-d'],
            'tipo' => ['nullable', Rule::in(array_column(TipoDocumento::cases(), 'value'))],
            'agrupar' => ['nullable', Rule::in([FiltrosDeGastos::AGRUPAR_PROVEEDOR, FiltrosDeGastos::AGRUPAR_OBRA, FiltrosDeGastos::AGRUPAR_RUBRO])],
            'pago' => ['nullable', Rule::in([PorPago::IMPAGAS, PorPago::PAGAS])],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function filtros(): FiltrosDeGastos
    {
        $porDefecto = ! $this->exists('desde') && ! $this->exists('hasta');
        $obra = $this->input('obra_id');

        return new FiltrosDeGastos(
            $obra === self::SIN_OBRA ? null : self::entero($obra),
            $obra === self::SIN_OBRA,
            self::entero($this->input('proveedor_id')),
            self::texto($this->input('rubro')),
            $porDefecto ? FechaEmpresa::primeroDelMes() : self::texto($this->input('desde')),
            $porDefecto ? FechaEmpresa::hoy() : self::texto($this->input('hasta')),
            $this->filled('tipo') ? TipoDocumento::from($this->string('tipo')->toString()) : null,
            self::texto($this->input('agrupar')),
            self::texto($this->input('pago')),
        );
    }
}
