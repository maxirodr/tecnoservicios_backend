<?php

declare(strict_types=1);

namespace Tests\Unit\Proveedores;

use App\Modules\Proveedores\Domain\SimilitudDeNombres;
use PHPUnit\Framework\TestCase;

/** RN-PRV-08: las cuatro reglas de "parecidos" sobre nombres normalizados fuerte. */
final class SimilitudDeNombresTest extends TestCase
{
    public function test_iguales_son_parecidos_con_puntaje_maximo(): void
    {
        $this->assertTrue(SimilitudDeNombres::sonParecidos('hormigonera sur', 'hormigonera sur'));
        $this->assertSame(1.0, SimilitudDeNombres::puntaje('hormigonera sur', 'hormigonera sur'));
    }

    public function test_uno_contiene_al_otro_con_al_menos_cuatro_letras(): void
    {
        $this->assertTrue(SimilitudDeNombres::sonParecidos('corralon', 'corralon centro bahia ejemplo'));
        $this->assertFalse(SimilitudDeNombres::sonParecidos('cor', 'corralon centro bahia ejemplo'));
    }

    public function test_comparten_la_mitad_de_las_palabras_del_mas_largo(): void
    {
        $this->assertTrue(SimilitudDeNombres::sonParecidos('alquileres andamios oeste', 'andamios oeste norte'));
        $this->assertFalse(SimilitudDeNombres::sonParecidos('transportes ejemplo norte', 'hotel puerto ejemplo'));
    }

    public function test_ochenta_por_ciento_letra_por_letra(): void
    {
        $this->assertTrue(SimilitudDeNombres::sonParecidos('hormigonera sur', 'hormigones sur'));
        $this->assertFalse(SimilitudDeNombres::sonParecidos('ferreteria norte', 'pintureria sur'));
        $this->assertEqualsWithDelta(1 - 2 / 15, SimilitudDeNombres::puntaje('hormigonera sur', 'hormigones sur'), 0.0001);
    }

    public function test_vacio_no_es_parecido_a_nada(): void
    {
        $this->assertFalse(SimilitudDeNombres::sonParecidos('', 'corralon'));
    }
}
