<?php

declare(strict_types=1);

namespace Tests\Architecture;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

final class ModuleBoundariesTest extends TestCase
{
    private const MODULES = ['Identidad', 'Obras', 'Pedidos', 'Gastos', 'Rendicion', 'Proveedores', 'Avisos', 'Importacion'];

    private const LAYERS = ['Http', 'Application', 'Domain', 'Infrastructure'];

    #[Test]
    public function every_module_has_the_four_layers_its_routes_file_and_its_service_provider(): void
    {
        foreach (self::MODULES as $module) {
            $base = $this->modulesPath().DIRECTORY_SEPARATOR.$module;

            foreach (self::LAYERS as $layer) {
                $this->assertDirectoryExists($base.DIRECTORY_SEPARATOR.$layer, "{$module} sin capa {$layer}");
            }

            $this->assertFileExists($base.DIRECTORY_SEPARATOR.'routes.php');
            $this->assertFileExists($base.DIRECTORY_SEPARATOR.'README.md');
            $this->assertTrue(class_exists("App\\Modules\\{$module}\\{$module}ServiceProvider"), "{$module} sin ServiceProvider");
        }
    }

    #[Test]
    public function no_module_breaks_layer_or_module_boundaries(): void
    {
        $violations = [];

        foreach ($this->moduleFiles() as $file) {
            [$module, $layer] = $this->locate($file);
            foreach (ModuleBoundaryScanner::violations((string) file_get_contents($file->getPathname()), $module, $layer) as $violation) {
                $violations[] = $file->getPathname().': '.$violation;
            }
        }

        $this->assertSame([], $violations, "Violaciones de arquitectura:\n".implode("\n", $violations));
    }

    #[Test]
    public function scanner_flags_domain_using_http_request(): void
    {
        $source = "<?php\nnamespace App\\Modules\\Obras\\Domain;\nuse Illuminate\\Http\\Request;\nfinal class Obra {}";

        $this->assertSame(['Domain usa Illuminate\\Http\\Request'], ModuleBoundaryScanner::violations($source, 'Obras', 'Domain'));
    }

    #[Test]
    public function scanner_flags_domain_using_facades_even_fully_qualified(): void
    {
        $source = "<?php\nnamespace App\\Modules\\Obras\\Domain;\nfinal class Obra { public function x(): void { \\Illuminate\\Support\\Facades\\DB::table('obras'); } }";

        $this->assertSame(['Domain usa Illuminate\\Support\\Facades\\DB'], ModuleBoundaryScanner::violations($source, 'Obras', 'Domain'));
    }

    #[Test]
    public function scanner_flags_domain_using_http_layer_of_any_module(): void
    {
        $own = "<?php\nuse App\\Modules\\Obras\\Http\\ObraResource;";
        $other = "<?php\nuse App\\Modules\\Pedidos\\Http\\Controllers\\PedidoController;";

        $this->assertCount(1, ModuleBoundaryScanner::violations($own, 'Obras', 'Domain'));
        $this->assertCount(1, ModuleBoundaryScanner::violations($other, 'Obras', 'Domain'));
    }

    #[Test]
    public function scanner_flags_a_module_importing_another_modules_infrastructure(): void
    {
        $source = "<?php\nuse App\\Modules\\Obras\\Infrastructure\\ObraModel;";

        $this->assertSame(
            ['Importa Infrastructure del módulo Obras: App\\Modules\\Obras\\Infrastructure\\ObraModel'],
            ModuleBoundaryScanner::violations($source, 'Pedidos', 'Application'),
        );
    }

    /** ADR-003, enmienda 2026-11-13. */
    public function test_application_y_http_pueden_usar_la_infrastructure_de_su_propio_modulo(): void
    {
        $source = "<?php\nuse App\\Modules\\Obras\\Infrastructure\\Consultas\\ObrasQuery;\nuse App\\Modules\\Obras\\Infrastructure\\Consultas\\PorResponsable;";

        $this->assertSame([], ModuleBoundaryScanner::violations($source, 'Obras', 'Application'));
        $this->assertSame([], ModuleBoundaryScanner::violations($source, 'Obras', 'Http'));
    }

    /** ADR-003, enmienda 2026-11-13. */
    public function test_ningun_modulo_usa_la_infrastructure_de_otro_modulo(): void
    {
        $source = "<?php\nuse App\\Modules\\Proveedores\\Infrastructure\\Consultas\\ProveedoresQuery;";

        foreach (['Application', 'Http', 'Infrastructure', 'Domain', ''] as $layer) {
            $this->assertContains(
                'Importa Infrastructure del módulo Proveedores: App\\Modules\\Proveedores\\Infrastructure\\Consultas\\ProveedoresQuery',
                ModuleBoundaryScanner::violations($source, 'Obras', $layer),
                "Desde la capa '{$layer}'",
            );
        }
    }

    /** ADR-003, enmienda 2026-11-13. */
    public function test_domain_no_usa_infrastructure_ni_la_propia(): void
    {
        $own = "<?php\nuse App\\Modules\\Obras\\Infrastructure\\Consultas\\ObrasQuery;";

        $this->assertSame(
            ['Domain usa la capa Infrastructure: App\\Modules\\Obras\\Infrastructure\\Consultas\\ObrasQuery'],
            ModuleBoundaryScanner::violations($own, 'Obras', 'Domain'),
        );
    }

    /** ADR-003, enmienda 2026-11-13: los query objects y sus filtros viven en Infrastructure/Consultas. */
    public function test_los_query_objects_y_filtros_viven_en_infrastructure_consultas(): void
    {
        foreach (self::MODULES as $module) {
            foreach (['Filtros', 'Consultas'] as $carpeta) {
                $this->assertDirectoryDoesNotExist(
                    $this->modulesPath().DIRECTORY_SEPARATOR.$module.DIRECTORY_SEPARATOR.'Application'.DIRECTORY_SEPARATOR.$carpeta,
                    "{$module}/Application/{$carpeta}: va en {$module}/Infrastructure/Consultas",
                );
            }
        }

        foreach (['Obras', 'Proveedores'] as $module) {
            $this->assertDirectoryExists($this->modulesPath().DIRECTORY_SEPARATOR.$module.DIRECTORY_SEPARATOR.'Infrastructure'.DIRECTORY_SEPARATOR.'Consultas');
        }
    }

    #[Test]
    public function scanner_allows_own_infrastructure_contracts_and_http_outside_domain(): void
    {
        $source = "<?php\nuse App\\Modules\\Obras\\Infrastructure\\ObraModel;\nuse App\\Modules\\Pedidos\\Contracts\\PedidosQuery;\nuse Illuminate\\Http\\Request;\nuse Illuminate\\Support\\Facades\\DB;";

        $this->assertSame([], ModuleBoundaryScanner::violations($source, 'Obras', 'Application'));
        $this->assertSame([], ModuleBoundaryScanner::violations($source, 'Obras', 'Http'));
    }

    private function modulesPath(): string
    {
        return dirname(__DIR__, 2).DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.'Modules';
    }

    /**
     * @return list<SplFileInfo>
     */
    private function moduleFiles(): array
    {
        $files = [];
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->modulesPath(), RecursiveDirectoryIterator::SKIP_DOTS));

        foreach ($iterator as $file) {
            if ($file instanceof SplFileInfo && $file->getExtension() === 'php') {
                $files[] = $file;
            }
        }

        return $files;
    }

    /**
     * @return array{0: string, 1: string} [módulo, capa]
     */
    private function locate(SplFileInfo $file): array
    {
        $relative = substr($file->getPathname(), strlen($this->modulesPath()) + 1);
        $parts = preg_split('#[\\\\/]#', $relative) ?: [];
        $module = $parts[0] ?? '';
        $layer = count($parts) > 2 && in_array($parts[1], self::LAYERS, true) ? $parts[1] : '';

        return [$module, $layer];
    }
}
