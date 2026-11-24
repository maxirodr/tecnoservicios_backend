<?php

declare(strict_types=1);

namespace Tests\Unit\Rendicion;

use App\Modules\Obras\Contracts\PresupuestadoDeObra;
use App\Modules\Obras\Contracts\PresupuestoDeObra;
use App\Modules\Rendicion\Contracts\CatalogoDeRubros;
use App\Modules\Rendicion\Contracts\RubroDelCatalogo;
use App\Modules\Rendicion\Domain\Calculo\CalculoDeRendicion;
use App\Modules\Rendicion\Domain\Calculo\Decimal;
use App\Modules\Rendicion\Domain\Calculo\Porcentaje;
use App\Modules\Rendicion\Domain\Calculo\Situacion;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/** La cuenta de la rendición (rendicion.md §4.3), sin base: sumas exactas, saldo, % y EPP en INSUMOS. */
final class CalculoDeRendicionTest extends TestCase
{
    public function test_porcentaje_redondea_mitad_para_arriba_a_dos_decimales(): void
    {
        $this->assertSame('54.04', Porcentaje::de('2701827.63', '5000000.00'));
        $this->assertSame('0.01', Porcentaje::de('0.125', '1000'));
        $this->assertSame('-0.01', Porcentaje::de('-0.125', '1000'));
        $this->assertSame('33.33', Porcentaje::de('1', '3'));
        $this->assertSame('66.67', Porcentaje::de('2', '3'));
        $this->assertSame('0.00', Porcentaje::de('-0.001', '1000'));
    }

    public function test_porcentaje_nulo_sin_presupuesto(): void
    {
        $this->assertNull(Porcentaje::de('35000.00', '0.00'));
    }

    public function test_sumas_exactas_al_centavo_sin_float(): void
    {
        $this->assertSame('0.30', Decimal::suma(['0.10', '0.20']));
        $this->assertSame('6727161.68', Decimal::suma(['2701827.63', '130333.30', '700000.00', '328000.00', '190000.00', '35000.00', '42000.00', '520000.00', '2080000.75']));
        $this->expectException(InvalidArgumentException::class);
        Decimal::suma(['1,5']);
    }

    public function test_saldo_es_presupuestado_menos_gastado_y_epp_suma_en_insumos(): void
    {
        $presupuestado = new PresupuestadoDeObra(['materiales' => '1000.00', 'insumos' => '500.00'], '0.00', '1500.00', [new PresupuestoDeObra(1, '1-1-2026', 'REV01', true, 'por_rubro', '1500.00')]);

        $resumen = (new CalculoDeRendicion(self::catalogo()))->calcular($presupuestado, ['materiales' => '1100.00', 'insumos' => '100.00', 'epp' => '50.50']);

        $this->assertSame(Situacion::PorRubro, $resumen->situacion);
        $materiales = $resumen->rubro('materiales');
        $insumos = $resumen->rubro('insumos');
        $this->assertNotNull($materiales);
        $this->assertNotNull($insumos);
        $this->assertSame(['1100.00', '-100.00', '110.00', true], [$materiales->gastado, $materiales->saldo, $materiales->porcentaje, $materiales->pasado]);
        $this->assertSame(['150.50', '349.50', ['epp']], [$insumos->gastado, $insumos->saldo, $insumos->incluye]);
        $this->assertNull($resumen->rubro('epp'));
        $this->assertSame(['1250.50', '249.50', 1], [$resumen->total->gastado, $resumen->total->saldo, $resumen->total->rubrosPasados]);
    }

    public function test_situacion_segun_el_modo_de_carga_de_los_presupuestos(): void
    {
        $p = self::presupuesto(...);

        $this->assertSame(Situacion::SinPresupuesto, Situacion::de([$p('sin_cargar')]));
        $this->assertSame(Situacion::PorRubro, Situacion::de([$p('por_rubro'), $p('sin_cargar')]));
        $this->assertSame(Situacion::SoloTotal, Situacion::de([$p('solo_total')]));
        $this->assertSame(Situacion::Mixta, Situacion::de([$p('por_rubro'), $p('solo_total')]));
    }

    /**
     * @param  'sin_cargar'|'por_rubro'|'solo_total'  $modo
     */
    private static function presupuesto(string $modo): PresupuestoDeObra
    {
        return new PresupuestoDeObra(1, 'X', null, true, $modo, '0.00');
    }

    private static function catalogo(): CatalogoDeRubros
    {
        return new class implements CatalogoDeRubros
        {
            public function imputables(): array
            {
                return [new RubroDelCatalogo(1, 'materiales', 'MATERIALES', false), new RubroDelCatalogo(2, 'insumos', 'INSUMOS', false), new RubroDelCatalogo(3, 'epp', 'EPP', false)];
            }

            public function presupuestables(): array
            {
                return [new RubroDelCatalogo(1, 'materiales', 'MATERIALES', false), new RubroDelCatalogo(2, 'insumos', 'INSUMOS', false)];
            }

            public function resumeEn(string $clave): ?string
            {
                return $clave === 'epp' ? 'insumos' : null;
            }
        };
    }
}
