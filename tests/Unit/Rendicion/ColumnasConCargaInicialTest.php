<?php

declare(strict_types=1);

namespace Tests\Unit\Rendicion;

use App\Modules\Rendicion\Domain\Materiales\ColumnasDeFechas;
use PHPUnit\Framework\TestCase;

/** W-48 con carga inicial (`importacion.md` RN-IMP-30, `rendicion.md` RN-REN-50). */
final class ColumnasConCargaInicialTest extends TestCase
{
    public function test_w48_columna_hasta_corte_carga_inicial_fuera_de_las_seis(): void
    {
        $columnas = ColumnasDeFechas::de(['2026-12-01', '2026-12-02', '2026-12-03', '2026-12-04', '2026-12-05', '2026-12-06'], '2026-11-30');

        $this->assertSame(['2026-12-01', '2026-12-02', '2026-12-03', '2026-12-04', '2026-12-05', '2026-12-06'], $columnas->fechas);
        $this->assertSame(['2026-11-30', true, false], [$columnas->hasta, $columnas->cargaInicial, $columnas->agrupaOtras]);
        $this->assertTrue($columnas->esAgrupada('2026-11-30'));
        $this->assertFalse($columnas->esAgrupada('2026-12-01'));
    }

    public function test_las_fechas_viejas_se_juntan_con_la_carga_inicial_incluye_carga_inicial(): void
    {
        $columnas = ColumnasDeFechas::de(['2026-12-01', '2026-12-02', '2026-12-03', '2026-12-04', '2026-12-05', '2026-12-06', '2026-12-07'], '2026-11-30');

        $this->assertSame(['2026-12-02', '2026-12-03', '2026-12-04', '2026-12-05', '2026-12-06', '2026-12-07'], $columnas->fechas);
        $this->assertSame(['2026-12-01', true, true], [$columnas->hasta, $columnas->cargaInicial, $columnas->agrupaOtras]);
    }

    public function test_sin_carga_inicial_sigue_igual_que_antes(): void
    {
        $columnas = ColumnasDeFechas::de(['2026-10-02', '2026-10-08']);

        $this->assertSame([['2026-10-02', '2026-10-08'], null, false, false], [$columnas->fechas, $columnas->hasta, $columnas->cargaInicial, $columnas->agrupaOtras]);
    }
}
