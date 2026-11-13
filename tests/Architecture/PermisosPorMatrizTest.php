<?php

declare(strict_types=1);

namespace Tests\Architecture;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

/**
 * HU-009 criterio 4 · ADR-006: ninguna regla pregunta por el nombre de un sector; todas leen la
 * matriz. Los nombres de los sectores solo viven en la migración de configuración inicial.
 */
final class PermisosPorMatrizTest extends TestCase
{
    private const SECTORES = [
        'Dirección', 'Direccion', 'Calidad', 'Gerencia de Operaciones', 'Jefatura de Proyectos',
        'Ingeniería de proyectos', 'Ingenieria de proyectos', 'Seguridad e Higiene', 'Gerencia de Administración',
        'Gerencia de Administracion', 'Jefatura de Administración', 'Jefatura de Administracion', 'Compras',
        'RRHH', 'Pañol y Logística', 'Panol y Logistica',
    ];

    public function test_ninguna_regla_pregunta_por_el_nombre_de_un_sector(): void
    {
        $violaciones = [];
        $app = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(dirname(__DIR__, 2).DIRECTORY_SEPARATOR.'app', RecursiveDirectoryIterator::SKIP_DOTS));

        foreach ($app as $archivo) {
            if (! $archivo instanceof SplFileInfo || $archivo->getExtension() !== 'php') {
                continue;
            }

            $codigo = (string) file_get_contents($archivo->getPathname());
            foreach (self::SECTORES as $sector) {
                if (preg_match('/[\'"]'.preg_quote($sector, '/').'[\'"]/iu', $codigo) === 1) {
                    $violaciones[] = "{$archivo->getPathname()}: '{$sector}'";
                }
            }
        }

        $this->assertSame([], $violaciones, "Reglas atadas al nombre de un sector:\n".implode("\n", $violaciones));
    }
}
