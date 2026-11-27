<?php

declare(strict_types=1);

namespace Tests\Unit\Importacion;

use App\Modules\Importacion\Domain\Lectura\DatoInvalido;
use App\Modules\Importacion\Domain\Lectura\PlantillaInvalida;
use App\Modules\Importacion\Domain\Lectura\Valores;
use App\Modules\Importacion\Domain\Lote;
use App\Modules\Importacion\Domain\Plantilla\Plantillas;
use App\Modules\Importacion\Infrastructure\Xlsx\LectorXlsx;
use DateTimeImmutable;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;
use PHPUnit\Framework\TestCase;

/** Lectura tolerante, sin adivinar (`importacion.md` §5 "Formatos comunes", RN-IMP-11, RN-IMP-14, §11.2). */
final class LectorPlantillaTest extends TestCase
{
    public function test_fechas_de_excel_y_texto(): void
    {
        $this->assertSame('2026-11-30', Valores::fecha('30/11/2026'));
        $this->assertSame('2026-11-30', Valores::fecha(' 30/11/2026 '));
        $this->assertSame('2026-11-30', Valores::fecha(new DateTimeImmutable('2026-11-30')));
        $this->assertSame('2026-11-30', Valores::fecha(46356));
        $this->assertNull(Valores::fecha(null));
        $this->expectExceptionObject(new DatoInvalido("La fecha '31/11/2026' no existe."));
        Valores::fecha('31/11/2026');
    }

    public function test_importe_formato_argentino(): void
    {
        $this->assertSame('1250000.50', Valores::importe('1.250.000,50'));
        $this->assertSame('45500.50', Valores::importe(45500.5));
        $this->assertSame('80000.00', Valores::importe('80.000'));
        $this->assertSame('60000.00', Valores::importe(60000));
        $this->assertSame('12000.00', Valores::importe('12000'));
        $this->assertSame('-10.00', Valores::importe('-10'));
    }

    public function test_mas_de_dos_decimales_es_faltante(): void
    {
        $this->assertMensaje("El importe '12,345' tiene más de 2 decimales.", static fn (): ?string => Valores::importe('12,345'));
        $this->assertMensaje("El importe '12.345' tiene más de 2 decimales.", static fn (): ?string => Valores::importe(12.345));
        $this->assertMensaje("La cantidad '1,2345' tiene más de 3 decimales.", static fn (): ?string => Valores::cantidad('1,2345'));
        $this->assertMensaje("El importe '$ 100' no se entiende.", static fn (): ?string => Valores::importe('$ 100'));
        $this->assertSame('1.250', Valores::cantidad('1,25'));
    }

    public function test_las_listas_no_distinguen_mayusculas_ni_tildes(): void
    {
        $this->assertSame('en_ejecucion', Plantillas::valorDe(Plantillas::ESTADOS_OBRA, 'en ejecucion'));
        $this->assertSame('confirmada', Plantillas::valorDe(Plantillas::ESTADOS_OBRA, ' CONFIRMADA '));
        $this->assertFalse(Plantillas::valorDe(Plantillas::ESTADOS_OBRA, 'Terminada'));
        $this->assertTrue(Valores::siNo('sí'));
        $this->assertFalse(Valores::siNo('No'));
    }

    public function test_falta_columna_obligatoria_no_empieza(): void
    {
        $ruta = $this->xlsx(['Obras' => [['N.º de presupuesto', 'Revisión', 'Trabajo']]]);

        try {
            $this->expectExceptionObject(new PlantillaInvalida("Falta la columna 'Cliente' en la hoja Obras."));
            (new LectorXlsx)->leer($ruta, Plantillas::de(Lote::Obras, ['MATERIALES'], ['MATERIALES'], []));
        } finally {
            unlink($ruta);
        }
    }

    public function test_hoja_opcional_vacia_no_frena_y_el_numero_de_fila_es_el_de_la_planilla(): void
    {
        $tareas = Plantillas::de(Lote::Tareas, [], [], ['Compras']);
        $encabezados = $tareas[0]->columnas;
        $fila = array_combine($encabezados, array_fill(0, count($encabezados), ''));
        $fila['Clave'] = 'Solapa · fila 3';
        $ruta = $this->xlsx(['tareas' => [$encabezados, array_fill(0, count($encabezados), ''), array_values($fila)], 'Personas' => [['Como figura en la minuta']]]);

        try {
            $hojas = (new LectorXlsx)->leer($ruta, $tareas);
        } finally {
            unlink($ruta);
        }

        $this->assertSame([], $hojas['Personas']);
        $this->assertCount(1, $hojas['Tareas']);
        $this->assertSame(3, $hojas['Tareas'][0]->numero);
        $this->assertSame('Solapa · fila 3', $hojas['Tareas'][0]->texto('Clave'));
    }

    /**
     * @param  array<string, list<list<string>>>  $hojas
     */
    private function xlsx(array $hojas): string
    {
        $ruta = tempnam(sys_get_temp_dir(), 'plantilla').'.xlsx';
        $writer = new Writer;
        $writer->openToFile($ruta);
        $primera = true;

        foreach ($hojas as $nombre => $filas) {
            if (! $primera) {
                $writer->addNewSheetAndMakeItCurrent();
            }
            $primera = false;
            $writer->getCurrentSheet()->setName($nombre);

            foreach ($filas as $fila) {
                $writer->addRow(Row::fromValues($fila));
            }
        }

        $writer->close();

        return $ruta;
    }

    private function assertMensaje(string $mensaje, callable $lectura): void
    {
        try {
            $lectura();
            $this->fail("Se esperaba: {$mensaje}");
        } catch (DatoInvalido $e) {
            $this->assertSame($mensaje, $e->getMessage());
        }
    }
}
