<?php

declare(strict_types=1);

namespace Tests\Feature\Importacion\Support;

use App\Modules\Identidad\Domain\Models\User;
use DateTimeInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Reader\XLSX\Reader;
use OpenSpout\Writer\XLSX\Writer;
use Tests\TestCase;

/**
 * Base de los tests de la carga inicial (`importacion.md` §13): "hoy" = 30/11/2026 18:10 en Argentina, una carpeta de
 * la importación propia y vacía, y plantillas **ficticias** armadas por el propio test (los .xlsx del cliente nunca se
 * abren ni se copian, RN-IMP-12). Personas ficticias del caso de referencia.
 */
abstract class ImportacionTestCase extends TestCase
{
    use RefreshDatabase;

    protected string $carpeta;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse('2026-11-30 21:10:00', 'UTC'));
        Storage::fake('adjuntos');
        config(['adjuntos.disk' => 'adjuntos']);
        Mail::fake();
        $this->carpeta = storage_path('framework/testing/importacion-'.Str::random(10));
        File::ensureDirectoryExists($this->carpeta);
        config(['importacion.dir' => $this->carpeta]);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->carpeta);
        Carbon::setTestNow();
        parent::tearDown();
    }

    protected function carla(): User
    {
        return $this->persona('Carla', 'carla@ejemplo.com', 'Jefatura de Administración');
    }

    protected function laura(): User
    {
        return $this->persona('Laura', 'laura@ejemplo.com', 'Ingeniería de proyectos');
    }

    protected function diego(): User
    {
        return $this->persona('Diego', 'diego@ejemplo.com', 'Compras');
    }

    protected function sofia(): User
    {
        return $this->persona('Sofía', 'sofia@ejemplo.com', 'Calidad');
    }

    protected function persona(string $nombre, string $mail, string $sector): User
    {
        return User::query()->where('email', $mail)->first()
            ?? User::factory()->enSector($sector)->create(['nombre' => $nombre, 'email' => $mail]);
    }

    /**
     * Escribe un .xlsx ficticio en la carpeta de la importación: hoja => filas (columna => valor). Los encabezados
     * salen de la primera fila de cada hoja.
     *
     * @param  array<string, list<array<string, string|int|float|DateTimeInterface|null>>>  $hojas
     * @param  array<string, list<string>>  $encabezados  para una hoja sin filas, o con otras columnas
     */
    protected function xlsx(string $archivo, array $hojas, array $encabezados = []): string
    {
        $ruta = $this->carpeta.DIRECTORY_SEPARATOR.$archivo;
        $writer = new Writer;
        $writer->openToFile($ruta);
        $primera = true;

        foreach ($hojas as $nombre => $filas) {
            if (! $primera) {
                $writer->addNewSheetAndMakeItCurrent();
            }
            $primera = false;
            $writer->getCurrentSheet()->setName($nombre);
            $columnas = $encabezados[$nombre] ?? array_keys($filas[0] ?? []);
            $writer->addRow(Row::fromValues($columnas));

            foreach ($filas as $fila) {
                $writer->addRow(Row::fromValues(array_map(static fn (string $c): mixed => $fila[$c] ?? null, $columnas)));
            }
        }

        $writer->close();

        return $archivo;
    }

    /**
     * @param  array<string, mixed>  $opciones
     * @return array{int, string}
     */
    protected function correr(string $comando, array $opciones = []): array
    {
        $codigo = Artisan::call($comando, $opciones);

        return [$codigo, Artisan::output()];
    }

    /**
     * El informe que dejó la corrida, por hoja (sin la fila de encabezados).
     *
     * @return array<string, list<list<mixed>>>
     */
    protected function informe(string $salida): array
    {
        $ruta = preg_match('/Informe: (\S+\.xlsx)/', $salida, $m) === 1 ? $m[1] : '';
        $this->assertFileExists($ruta, 'La salida no trae la ruta del informe');
        $reader = new Reader;
        $reader->open($ruta);
        $hojas = [];

        foreach ($reader->getSheetIterator() as $sheet) {
            $filas = [];

            foreach ($sheet->getRowIterator() as $row) {
                $filas[] = $row->toArray();
            }

            $hojas[$sheet->getName()] = $sheet->getName() === 'Resumen' ? $filas : array_slice($filas, 1);
        }

        $reader->close();

        return $hojas;
    }
}
