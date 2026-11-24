<?php

declare(strict_types=1);

namespace App\Modules\Gastos\Application\Correccion;

use App\Modules\Gastos\Application\Rubros;
use App\Modules\Gastos\Domain\Legible;
use App\Modules\Gastos\Domain\Models\GastoRenglon;
use App\Modules\Obras\Contracts\ObrasConsulta;
use App\Modules\Rendicion\Domain\Models\Material;

/**
 * Un renglón como lo lee la persona en el historial (RN-GAS-42): "Piso industrial nave 3", "MATERIALES",
 * "9.800". Se guarda así en `actividades`, como Obras y Pedidos (el historial dice lo que había entonces).
 */
final class FotoDeRenglon
{
    public const SIN_OBRA = '(sin obra)';

    /** @var array<int, string> */
    private array $obras = [];

    public function __construct(
        private readonly ObrasConsulta $consulta,
        private readonly Rubros $rubros,
    ) {}

    /**
     * @return array{concepto: string, cantidad: string, precio: string, total: string, obra: string, rubro: string, material: string|null, detalle: string|null, fecha: string}
     */
    public function de(GastoRenglon $renglon): array
    {
        return [
            'concepto' => $renglon->concepto,
            'cantidad' => Legible::cantidad($renglon->cantidad),
            'precio' => Legible::importe($renglon->precio_unitario),
            'total' => Legible::importe($renglon->total),
            'obra' => $this->obra($renglon->obra_id),
            'rubro' => $this->rubros->porId($renglon->rubro_id)->nombre ?? '—',
            'material' => $renglon->material_id === null ? null : $this->material($renglon->material_id),
            'detalle' => $renglon->detalle,
            'fecha' => Legible::fecha($renglon->fecha),
        ];
    }

    /** "'Arena fina (m³)' · 2 × 40.000 · Piso industrial nave 3 · MATERIALES" */
    public function resumen(GastoRenglon $renglon): string
    {
        $foto = $this->de($renglon);

        return "'{$foto['concepto']}' · {$foto['cantidad']} × {$foto['precio']} · {$foto['obra']} · {$foto['rubro']}";
    }

    public function obra(?int $obraId): string
    {
        if ($obraId === null) {
            return self::SIN_OBRA;
        }

        return $this->obras[$obraId] ??= $this->consulta->resumen($obraId)->nombreTrabajo ?? self::SIN_OBRA;
    }

    private function material(int $materialId): string
    {
        $nombre = Material::query()->whereKey($materialId)->value('nombre');

        return is_string($nombre) ? $nombre : '—';
    }
}
