<?php

declare(strict_types=1);

namespace Tests\Unit\Shared;

use App\Shared\Adjuntos\NombreDeLink;
use PHPUnit\Framework\TestCase;

/** PU-207: el back nombra los links con la misma regla que el front (`nombreDeLink.test.ts`). */
final class NombreDeLinkTest extends TestCase
{
    public function test_la_ultima_parte_del_link_decodificada(): void
    {
        $this->assertSame('oferta-ñandú', NombreDeLink::de('https://drive.example/oferta-%C3%B1and%C3%BA'));
        $this->assertSame('sin-s', NombreDeLink::de('http://drive.example/sin-s'));
        $this->assertSame('plano nave.pdf', NombreDeLink::de('https://drive.example/carpeta/plano%20nave.pdf?usp=sharing'));
        $this->assertSame('oferta tecnica ñandú.pdf', NombreDeLink::de('https://drive.example/QA3-C/oferta tecnica ñandú.pdf'));
        $this->assertSame('oc 001.pdf', NombreDeLink::de('https://drive.example/QA3-REG/oc 001.pdf/'));
    }

    public function test_sin_camino_el_dominio_legible(): void
    {
        $this->assertSame('drive.example', NombreDeLink::de('https://drive.example/'));
        $this->assertSame('drive.example', NombreDeLink::de('https://drive.example'));
        $this->assertSame('ñandú.example', NombreDeLink::de('https://xn--and-6ma2c.example/'));
    }

    public function test_salta_las_partes_que_no_dicen_nada(): void
    {
        $this->assertSame('1AbCdE', NombreDeLink::de('https://drive.example/file/d/1AbCdE/view'));
        $this->assertSame('9XyZ', NombreDeLink::de('https://docs.example/document/d/9XyZ/EDIT'));
    }

    public function test_una_parte_mal_codificada_queda_tal_cual(): void
    {
        $this->assertSame('100%25-ok%', NombreDeLink::de('https://drive.example/100%25-ok%'));
        $this->assertSame('%FF', NombreDeLink::de('https://drive.example/%FF'));
    }

    public function test_un_nombre_larguisimo_se_recorta_al_largo_de_la_columna(): void
    {
        $this->assertSame(NombreDeLink::LARGO_MAXIMO, mb_strlen(NombreDeLink::de('https://drive.example/'.str_repeat('ñ', 300))));
    }
}
