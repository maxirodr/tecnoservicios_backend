<?php

declare(strict_types=1);

namespace Tests\Architecture;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

/**
 * TL-16: un solo estilo para la asignación masiva: `protected $fillable` (y `$hidden`) como propiedad, nunca
 * con los atributos `#[Fillable]`/`#[Hidden]`. Los catálogos de solo lectura (Funcion, Rubro) no declaran
 * ninguno: nada en ellos es asignable en masa.
 */
final class EstiloDeModelosTest extends TestCase
{
    public function test_ningun_modelo_usa_los_atributos_fillable_o_hidden(): void
    {
        $app = dirname(__DIR__, 2).'/app';
        $conAtributo = [];

        /** @var SplFileInfo $archivo */
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($app, RecursiveDirectoryIterator::SKIP_DOTS)) as $archivo) {
            $codigo = $archivo->getExtension() === 'php' ? (string) file_get_contents($archivo->getPathname()) : '';

            if (str_contains($codigo, '#[Fillable(') || str_contains($codigo, '#[Hidden(')) {
                $conAtributo[] = $archivo->getFilename();
            }
        }

        $this->assertSame([], $conAtributo);
    }
}
