<?php

declare(strict_types=1);

namespace Tests\Unit\Proveedores;

use App\Modules\Proveedores\Domain\Cuit;
use PHPUnit\Framework\TestCase;

/** RN-PRV-06: CUIT tolerante con dígito verificador (CUIT ficticios). */
final class CuitTest extends TestCase
{
    public function test_acepta_con_o_sin_guiones_o_espacios_y_guarda_once_digitos(): void
    {
        foreach (['30-00000001-5', '30 00000001 5', '30000000015', ' 30-00000001-5 '] as $texto) {
            $this->assertSame('30000000015', Cuit::desde($texto)?->digitos, $texto);
        }
    }

    public function test_se_muestra_con_guiones(): void
    {
        $this->assertSame('30-00000000-7', Cuit::desde('30000000007')?->formateado());
        $this->assertSame('20-00000002-8', Cuit::formatear('20000000028'));
    }

    public function test_rechaza_digito_verificador_incorrecto(): void
    {
        $this->assertNull(Cuit::desde('30-00000001-1'));
        $this->assertNull(Cuit::desde('30000000000'));
    }

    public function test_rechaza_si_no_son_once_digitos(): void
    {
        foreach (['3000000001', '300000000155', '30-0000000A-5', '30.00000001.5', ''] as $texto) {
            $this->assertNull(Cuit::desde($texto), $texto);
        }
    }

    public function test_resto_uno_no_tiene_verificador_valido(): void
    {
        // 30-00000006: suma 15 + 12 = 27, resto 5 → verificador 6. 30-00000010: suma 15 + 3 = 18, resto 7 → 4.
        $this->assertNotNull(Cuit::desde('30000000066'));
        // 30-00000004: suma 15 + 8 = 23, resto 1 → 10: ningún dígito sirve.
        foreach (range(0, 9) as $digito) {
            $this->assertNull(Cuit::desde('3000000004'.$digito));
        }
    }
}
