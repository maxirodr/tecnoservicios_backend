<?php

declare(strict_types=1);

namespace Tests\Unit\Obras;

use App\Modules\Obras\Domain\Valores\NumeroPresupuesto;
use App\Modules\Obras\Domain\Valores\ValorInvalido;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/** RN-OBR-10/11: formato `P-NNN-AAAA` con sufijo de adicional y validación tolerante. */
final class NumeroPresupuestoTest extends TestCase
{
    private const ANIO = 2026;

    /**
     * @return array<string, array{string, string}>
     */
    public static function escritos(): array
    {
        return [
            'guiones' => ['2-131-2026', '2-131-2026'],
            'espacios' => [' 2 131 2026 ', '2-131-2026'],
            'barras' => ['2/131/2026', '2-131-2026'],
            'puntos' => ['2.131.2026', '2-131-2026'],
            'guion bajo' => ['2_131_2026', '2-131-2026'],
        ];
    }

    #[DataProvider('escritos')]
    public function test_normaliza_separadores(string $escrito, string $guardado): void
    {
        $this->assertSame($guardado, NumeroPresupuesto::desde($escrito, self::ANIO)->valor());
    }

    public function test_completa_numero_a_tres_digitos(): void
    {
        $this->assertSame('2-031-2026', NumeroPresupuesto::desde('2-31-2026', self::ANIO)->valor());
        $this->assertSame('1-1234-2026', NumeroPresupuesto::desde('1-1234-2026', self::ANIO)->valor());
    }

    public function test_normaliza_sufijo_de_adicional(): void
    {
        foreach (['2-131-2026 a1', '2-131-2026-A1', '2-131-2026A1'] as $escrito) {
            $this->assertSame('2-131-2026 A1', NumeroPresupuesto::desde($escrito, self::ANIO)->valor());
        }
    }

    public function test_separa_revision_escrita_en_el_numero(): void
    {
        $numero = NumeroPresupuesto::desde('2-131-2026 REV01', self::ANIO);

        $this->assertSame('2-131-2026', $numero->valor());
        $this->assertSame('REV01', $numero->revisionEscrita?->valor);
    }

    public function test_rechaza_anio_de_dos_digitos(): void
    {
        $this->assertRechaza('2-131-26', 'Escribí el año con 4 dígitos, por ejemplo 2-131-2026.');
    }

    public function test_rechaza_anio_fuera_de_rango(): void
    {
        $this->assertRechaza('2-131-1999', 'El año tiene que estar entre 2000 y 2027.');
        $this->assertRechaza('2-131-2028', 'El año tiene que estar entre 2000 y 2027.');
    }

    public function test_rechaza_dos_presupuestos_juntos(): void
    {
        $this->assertRechaza('3-115117-2026', 'Eso parecen dos presupuestos: cargá uno como principal y el otro como adicional.');
    }

    public function test_rechaza_texto_sin_numero(): void
    {
        $this->assertRechaza('PLANTA 2-131-2026', 'Escribí solo el número, por ejemplo 2-131-2026.');
        $this->assertRechaza('presupuesto de la nave', 'Escribí solo el número, por ejemplo 2-131-2026.');
    }

    private function assertRechaza(string $escrito, string $mensaje): void
    {
        try {
            NumeroPresupuesto::desde($escrito, self::ANIO);
            $this->fail("Se aceptó {$escrito}");
        } catch (ValorInvalido $e) {
            $this->assertSame($mensaje, $e->getMessage());
        }
    }
}
