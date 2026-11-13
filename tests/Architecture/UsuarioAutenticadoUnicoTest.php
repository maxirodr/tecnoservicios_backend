<?php

declare(strict_types=1);

namespace Tests\Architecture;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

/** TL-06: "el usuario del pedido o 401" vive en un solo lugar (App\Shared\Http\UsuarioAutenticado). */
final class UsuarioAutenticadoUnicoTest extends TestCase
{
    private const UNICO = 'Shared/Http/UsuarioAutenticado.php';

    public function test_ningun_controller_repite_la_lectura_del_usuario_autenticado(): void
    {
        $app = dirname(__DIR__, 2).'/app';
        $copias = [];

        /** @var SplFileInfo $archivo */
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($app, RecursiveDirectoryIterator::SKIP_DOTS)) as $archivo) {
            $ruta = str_replace('\\', '/', substr($archivo->getPathname(), strlen($app) + 1));

            if ($archivo->getExtension() === 'php' && $ruta !== self::UNICO
                && preg_match('/function usuario\(Request /', (string) file_get_contents($archivo->getPathname())) === 1) {
                $copias[] = $ruta;
            }
        }

        $this->assertSame([], $copias);
    }
}
