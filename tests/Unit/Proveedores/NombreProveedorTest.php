<?php

declare(strict_types=1);

namespace Tests\Unit\Proveedores;

use App\Modules\Proveedores\Domain\NombreProveedor;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/** proveedores.md §4.1 · RN-PRV-03, 04, 08: las tres formas comparables del nombre. */
final class NombreProveedorTest extends TestCase
{
    /**
     * @return array<string, array{string, string}>
     */
    public static function mismoNombreSuave(): array
    {
        return [
            'mayúsculas' => ['HORMIGONERA SUR', 'hormigonera sur'],
            'S.R.L. con puntos' => ['Hormigonera Sur S.R.L.', 'hormigonera sur'],
            'srl sin puntos' => ['hormigonera sur srl', 'hormigonera sur'],
            'S. R. L. con espacios' => ['Hormigonera  Sur S. R. L.', 'hormigonera sur'],
            'tildes' => ['Corralón Centro', 'corralon centro'],
            'y Cía. S.A.' => ['Pérez y Cía. S.A.', 'perez'],
            'S.A.I.C.' => ['Metalúrgica Ejemplo S.A.I.C.', 'metalurgica ejemplo'],
            'guiones separan' => ['Corralón-Centro', 'corralon centro'],
            'solo la forma queda igual' => ['S.A.', 'sa'],
            'forma en el medio no se saca' => ['SA Transportes Ejemplo', 'sa transportes ejemplo'],
        ];
    }

    #[DataProvider('mismoNombreSuave')]
    public function test_clave_saca_mayusculas_tildes_puntuacion_y_forma_societaria(string $nombre, string $clave): void
    {
        $this->assertSame($clave, NombreProveedor::clave($nombre));
    }

    public function test_busqueda_conserva_la_forma_societaria(): void
    {
        $this->assertSame('hormigonera sur srl', NombreProveedor::busqueda('Hormigonera Sur S.R.L.'));
        $this->assertSame('ferreteria norte', NombreProveedor::busqueda('  Ferretería   Norte '));
    }

    public function test_normalizado_fuerte_saca_articulos_y_la_y(): void
    {
        $this->assertSame('hormigonera sur', NombreProveedor::normalizado('Hormigonera del Sur'));
        $this->assertSame('hormigones sur', NombreProveedor::normalizado('Hormigones del Sur'));
        $this->assertSame('hotel puerto ejemplo', NombreProveedor::normalizado('Hotel El Puerto y Ejemplo'));
        $this->assertSame('los', NombreProveedor::normalizado('Los'));
    }
}
