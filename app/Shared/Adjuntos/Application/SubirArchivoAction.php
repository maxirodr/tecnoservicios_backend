<?php

declare(strict_types=1);

namespace App\Shared\Adjuntos\Application;

use App\Modules\Identidad\Domain\Models\User;
use App\Shared\Adjuntos\Adjunto;
use App\Shared\Adjuntos\DetectorTipoArchivo;
use App\Shared\Adjuntos\TipoAdjunto;
use Illuminate\Contracts\Filesystem\Factory as FilesystemFactory;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Guarda el archivo en el disco privado (`adjuntos/AAAA/MM/<uuid>`, sin extensión) y crea el adjunto
 * temporal (sin sujeto). El archivo ya viene validado (tamaño y tipo por contenido).
 */
final readonly class SubirArchivoAction
{
    private const NOMBRE_POR_DEFECTO = 'archivo';

    private const LARGO_NOMBRE = 200;

    public function __construct(
        private DetectorTipoArchivo $detector,
        private FilesystemFactory $filesystem,
        private ConnectionInterface $db,
    ) {}

    public function handle(User $user, string $contexto, UploadedFile $archivo): Adjunto
    {
        $nombre = self::sanearNombre($archivo->getClientOriginalName());
        $disco = (string) config('adjuntos.disk');
        $carpeta = 'adjuntos/'.Carbon::now()->format('Y/m');
        $uuid = (string) Str::uuid();
        $confirmado = false;

        try {
            $adjunto = $this->guardar($user, $contexto, $archivo, $nombre, $disco, $carpeta, $uuid);
            $confirmado = true;

            return $adjunto;
        } finally {
            // TL-15: si la transacción no confirma (rollback o falla el commit), el archivo no queda huérfano.
            if (! $confirmado) {
                $this->filesystem->disk($disco)->delete($carpeta.'/'.$uuid);
            }
        }
    }

    private function guardar(User $user, string $contexto, UploadedFile $archivo, string $nombre, string $disco, string $carpeta, string $uuid): Adjunto
    {
        return $this->db->transaction(function () use ($user, $contexto, $archivo, $nombre, $disco, $carpeta, $uuid): Adjunto {
            $adjunto = Adjunto::query()->create([
                'contexto' => $contexto,
                'tipo' => TipoAdjunto::Archivo,
                'disco' => $disco,
                'ruta' => $carpeta.'/'.$uuid,
                'nombre' => $nombre,
                'mime' => $this->detector->detectar($archivo->getPathname(), $nombre),
                'tamano_bytes' => (int) $archivo->getSize(),
                'subido_por' => $user->getKey(),
            ]);

            if ($this->filesystem->disk($disco)->putFileAs($carpeta, $archivo, $uuid) === false) {
                throw new RuntimeException('No se pudo guardar el adjunto en el disco.');
            }

            return $adjunto;
        });
    }

    /** Nombre original sin rutas ni caracteres de control: solo se muestra y va en Content-Disposition. */
    private static function sanearNombre(string $original): string
    {
        $base = basename(str_replace('\\', '/', $original));
        $nombre = trim(mb_substr((string) preg_replace('/[\x00-\x1F\x7F:*?"<>|]+/u', '', $base), 0, self::LARGO_NOMBRE));

        return $nombre === '' ? self::NOMBRE_POR_DEFECTO : $nombre;
    }
}
