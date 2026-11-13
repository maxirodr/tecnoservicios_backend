<?php

declare(strict_types=1);

namespace Tests\Unit\Shared;

use App\Shared\Adjuntos\LimiteDeSubida;
use PHPUnit\Framework\TestCase;

/**
 * B-04 (QC entrega 1): si PHP corta la subida antes que la aplicación, el mensaje dice el tamaño
 * en MB y el máximo real del servidor (HU-025 criterio 3), nunca un genérico.
 */
final class LimiteDeSubidaTest extends TestCase
{
    public function test_el_maximo_es_el_menor_entre_la_aplicacion_y_php(): void
    {
        $this->assertSame(8, (new LimiteDeSubida(20, '8M', '12M'))->maximoMb());
        $this->assertSame(20, (new LimiteDeSubida(20, '64M', '0'))->maximoMb());
        $this->assertSame(20, (new LimiteDeSubida(20, '1G', '1G'))->maximoMb());
        $this->assertSame(1, (new LimiteDeSubida(20, '512K', '0'))->maximoMb());
        $this->assertSame(15, (new LimiteDeSubida(20, '', '15728640'))->maximoMb());
    }

    public function test_mensaje_con_y_sin_el_peso_del_archivo(): void
    {
        $limite = new LimiteDeSubida(20, '8M', '12M');

        $this->assertSame('El archivo pesa 25 MB y el máximo es 8 MB.', $limite->mensaje(25 * 1048576));
        $this->assertSame('El archivo pesa 10,5 MB y el máximo es 8 MB.', $limite->mensaje((int) (10.5 * 1048576)));
        $this->assertSame('El archivo pesa más de 8 MB, que es el máximo.', $limite->mensaje());
    }
}
