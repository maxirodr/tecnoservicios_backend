<?php

declare(strict_types=1);

namespace Tests\Unit\Shared;

use App\Shared\Texto;
use PHPUnit\Framework\TestCase;

/** PU-034: los mensajes que terminan en un nombre llevan un solo punto. */
final class TextoTest extends TestCase
{
    public function test_agrega_el_punto_si_falta_y_no_lo_duplica(): void
    {
        $this->assertSame('Guardamos Ferretería Norte.', Texto::conPunto('Guardamos Ferretería Norte'));
        $this->assertSame('Guardamos Hormigonera Sur S.R.L.', Texto::conPunto('Guardamos Hormigonera Sur S.R.L.'));
        $this->assertSame('Ya existe Pérez y Cía.', Texto::conPunto('Ya existe Pérez y Cía. '));
    }
}
