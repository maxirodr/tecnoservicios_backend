<?php

declare(strict_types=1);

namespace Tests\Unit\Shared;

use App\Shared\Domain\Importe;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * B-05 (QC entrega 1): el historial muestra los importes como W-13. Mismos casos que
 * `formatearImporte` del front: sin centavos, ningún decimal; con centavos, siempre dos (PU-113).
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
            'millones con centavos redondos' => ['12650000.50', '12.650.000,50'],
            'diez con cincuenta' => ['10.5', '10,50'],
            'centavos' => ['1500000.05', '1.500.000,05'],
            'centavos sin ceros' => ['1500000.25', '1.500.000,25'],
            'negativo con centavos redondos' => ['-1500.10', '-1.500,10'],
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
