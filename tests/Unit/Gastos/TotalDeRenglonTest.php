<?php

declare(strict_types=1);

namespace Tests\Unit\Gastos;

use App\Modules\Gastos\Domain\Legible;
use App\Modules\Gastos\Domain\TotalDeRenglon;
use PHPUnit\Framework\TestCase;

/** F-41 · gastos.md §4.5: total = round(cantidad × precio, 2), mitad para arriba en valor absoluto. */
final class TotalDeRenglonTest extends TestCase
{
    public function test_redondea_mitad_hacia_arriba(): void
    {
        $this->assertSame('1265432.13', TotalDeRenglon::calcular('12.500', '101234.57'));
        $this->assertSame('0.01', TotalDeRenglon::calcular('0.001', '5.00'));
        $this->assertSame('100.02', TotalDeRenglon::calcular('3', '33.34'));
        $this->assertSame('106250.00', TotalDeRenglon::calcular('2.5', '42500'));
        $this->assertSame('333.00', TotalDeRenglon::calcular('0.333', '1000.01'));
    }

    public function test_negativo_redondea_alejandose_de_cero(): void
    {
        $this->assertSame('-0.01', TotalDeRenglon::calcular('-0.001', '5.00'));
        $this->assertSame('-39200.00', TotalDeRenglon::calcular('-4', '9800.00'));
        $this->assertSame('0.00', TotalDeRenglon::calcular('-0.001', '0.00'));
    }

    public function test_total_del_gasto_es_la_suma_sin_redondear_de_nuevo(): void
    {
        $this->assertSame('905600.00', TotalDeRenglon::suma(['294000.00', '255000.00', '236000.00', '75600.00', '45000.00']));
        $this->assertSame('0.02', TotalDeRenglon::suma(['0.01', '0.01']));
    }

    public function test_legible_sin_ceros_de_mas(): void
    {
        $this->assertSame('30', Legible::cantidad('30.000'));
        $this->assertSame('2,5', Legible::cantidad('2.500'));
        $this->assertSame('1.300.000', Legible::importe('1300000.00'));
        $this->assertSame('9.800,50', Legible::importe('9800.50'));
    }

    public function test_legible_escribe_los_negativos_con_el_signo_menos(): void
    {
        $this->assertSame('−4', Legible::cantidad('-4.000'));
        $this->assertSame('−39.200', Legible::importe('-39200.00'));
        $this->assertSame('0', Legible::cantidad('-0.000'));
    }
}
