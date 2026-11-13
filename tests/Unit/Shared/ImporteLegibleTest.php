<?php

declare(strict_types=1);

namespace Tests\Unit\Shared;

use App\Shared\Domain\Importe;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * B-05 (QC entrega 1): el historial muestra los importes como W-13. Mismos casos que
 * `formatearImporte` del front (`Intl.NumberFormat('es-AR')`, hasta 2 decimales).
 */
final class ImporteLegibleTest extends TestCase
{
    /**
     * @return array<string, array{string, string}>
     */
    public static function casos(): array
    {
        return [
            'miles sin decimales' => ['2500.00', '2.500'],
            'millones con centavos' => ['12400000.50', '12.400.000,5'],
            'centavos' => ['1500000.05', '1.500.000,05'],
            'menos de mil' => ['200', '200'],
            'cero' => ['0.00', '0'],
            'negativo' => ['-1500.25', '-1.500,25'],
        ];
    }

    #[DataProvider('casos')]
    public function test_importe_se_lee_como_en_argentina(string $valor, string $esperado): void
    {
        $this->assertSame($esperado, Importe::desde($valor)->legible());
    }
}
