<?php

declare(strict_types=1);

namespace Tests\Feature\Shared\Adjuntos;

use App\Shared\Adjuntos\Application\SubirArchivoAction;
use Closure;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\MySqlConnection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\Feature\Shared\Adjuntos\Support\AdjuntosTestCase;
use Tests\Feature\Shared\Adjuntos\Support\ArchivosDePrueba;

/** TL-15: si la transacción de la subida no confirma, el archivo ya escrito se borra (la purga no lo vería). */
final class SubirAdjuntoSinHuerfanosTest extends AdjuntosTestCase
{
    public function test_si_la_transaccion_no_confirma_no_queda_el_archivo_en_el_disco(): void
    {
        $real = DB::connection();
        $this->assertInstanceOf(MySqlConnection::class, $real);
        $this->app->instance(ConnectionInterface::class, new class($real->getPdo(), $real->getDatabaseName(), $real->getTablePrefix(), $real->getConfig()) extends MySqlConnection
        {
            /** Corre el cuerpo (escribe el archivo) y falla al confirmar. */
            public function transaction(Closure $callback, $attempts = 1): mixed
            {
                $callback($this);

                throw new RuntimeException('Falló el commit.');
            }
        });

        try {
            $this->app->make(SubirArchivoAction::class)->handle($this->usuario(), self::CONTEXTO, ArchivosDePrueba::pdf());
            $this->fail('La subida tenía que fallar.');
        } catch (RuntimeException $e) {
            $this->assertSame('Falló el commit.', $e->getMessage());
        }

        $this->assertSame([], Storage::disk('adjuntos')->allFiles());
    }

    public function test_subida_confirmada_deja_el_archivo(): void
    {
        $adjunto = $this->app->make(SubirArchivoAction::class)->handle($this->usuario(), self::CONTEXTO, ArchivosDePrueba::pdf());

        Storage::disk('adjuntos')->assertExists((string) $adjunto->ruta);
    }
}
