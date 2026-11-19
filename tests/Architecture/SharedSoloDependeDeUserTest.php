<?php

declare(strict_types=1);

namespace Tests\Architecture;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

/**
 * TL-08 · ADR-003 (enmienda 2026-11-19): `app/Shared` no depende de ningún módulo, con una sola excepción
 * admitida: el usuario del sistema (`Identidad\Domain\Models\User`).
 */
final class SharedSoloDependeDeUserTest extends TestCase
{
    private const EXCEPCION = 'App\\Modules\\Identidad\\Domain\\Models\\User';

    public function test_shared_no_usa_ningun_modulo_salvo_el_usuario(): void
    {
        $shared = dirname(__DIR__, 2).DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.'Shared';
        $violaciones = [];

        /** @var SplFileInfo $archivo */
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($shared, RecursiveDirectoryIterator::SKIP_DOTS)) as $archivo) {
            if ($archivo->getExtension() !== 'php') {
                continue;
            }

            foreach (ModuleBoundaryScanner::referencedNames((string) file_get_contents($archivo->getPathname())) as $nombre) {
                if (str_starts_with($nombre, 'App\\Modules\\') && $nombre !== self::EXCEPCION) {
                    $violaciones[] = $archivo->getFilename().': '.$nombre;
                }
            }
        }

        $this->assertSame([], $violaciones);
    }

    public function test_el_detector_marca_un_modulo_que_no_es_el_usuario(): void
    {
        $fuente = "<?php\nuse App\\Modules\\Obras\\Domain\\Models\\Obra;\nuse App\\Modules\\Identidad\\Domain\\Models\\User;";
        $nombres = array_filter(
            ModuleBoundaryScanner::referencedNames($fuente),
            static fn (string $n): bool => str_starts_with($n, 'App\\Modules\\') && $n !== self::EXCEPCION,
        );

        $this->assertSame(['App\\Modules\\Obras\\Domain\\Models\\Obra'], array_values($nombres));
    }
}
