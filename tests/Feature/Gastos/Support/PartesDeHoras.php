<?php

declare(strict_types=1);

namespace Tests\Feature\Gastos\Support;

use App\Modules\Obras\Domain\Models\Obra;

/**
 * El parte de horas de `gastos.md` §10 caso 49 (DU-C02), con datos ficticios: Juan Pérez 88,5 h a 5.200 en
 * Nave 3 y 16 h en Losa tanque B; Pablo Gómez 8 h a 4.800 (sin obra, o en la obra que se diga).
 */
trait PartesDeHoras
{
    /**
     * @param  list<array<string, mixed>>  $renglones
     * @param  array<string, mixed>  $cambios
     * @return array<string, mixed>
     */
    protected function parte(array $renglones, array $cambios = []): array
    {
        return [
            'tipo_documento' => 'sin_documento', 'origen' => 'parte_horas',
            'periodo_desde' => '2026-10-01', 'periodo_hasta' => '2026-10-15',
            'renglones' => $renglones, ...$cambios,
        ];
    }

    /**
     * @param  array<string, mixed>  $cambios
     * @return array<string, mixed>
     */
    protected function horas(string $empleado, ?Obra $obra, string $horas, string $valorHora, array $cambios = []): array
    {
        return ['empleado' => $empleado, 'obra_id' => $obra?->id, 'cantidad' => $horas, 'precio_unitario' => $valorHora, ...$cambios];
    }

    /**
     * @return array<string, mixed>
     */
    protected function parteDelCaso49(Obra $nave, Obra $losa, ?Obra $obraDePablo = null): array
    {
        return $this->parte([
            $this->horas('Juan Pérez', $nave, '88.5', '5200.00'),
            $this->horas('Juan Pérez', $losa, '16', '5200.00'),
            $this->horas('Pablo Gómez', $obraDePablo, '8', '4800.00'),
        ]);
    }
}
