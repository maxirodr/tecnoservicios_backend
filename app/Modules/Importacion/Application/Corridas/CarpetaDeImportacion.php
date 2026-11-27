<?php

declare(strict_types=1);

namespace App\Modules\Importacion\Application\Corridas;

use App\Modules\Importacion\Application\FechaEmpresa;
use App\Modules\Importacion\Domain\Lote;
use App\Modules\Importacion\Domain\Modo;

/**
 * La carpeta privada de la importación (RN-IMP-12, `IMPORTACION_DIR`): los comandos **solo** leen archivos de ahí
 * (nada de rutas de afuera ni `..`) y dejan ahí los informes y las plantillas.
 */
final class CarpetaDeImportacion
{
    public const FUERA = 'El archivo tiene que estar en la carpeta de la importación.';

    public function raiz(): string
    {
        $dir = config()->string('importacion.dir');

        if (! is_dir($dir)) {
            mkdir($dir, 0700, true);
        }

        return rtrim((string) realpath($dir), DIRECTORY_SEPARATOR);
    }

    /** La ruta real de un archivo de la carpeta, o null si no está ahí. */
    public function archivo(string $nombre): ?string
    {
        $raiz = $this->raiz();
        $ruta = realpath($raiz.DIRECTORY_SEPARATOR.$nombre);

        if ($ruta === false || ! is_file($ruta) || ! str_starts_with($ruta, $raiz.DIRECTORY_SEPARATOR)) {
            return null;
        }

        return $ruta;
    }

    /** `informe-{obras|tareas}-{ensayo|confirmada}-{AAAAMMDD-HHMM}.xlsx` (§5.8). */
    public function informe(Lote $lote, Modo $modo): string
    {
        return "informe-{$lote->value}-{$modo->value}-".FechaEmpresa::ahora()->format('Ymd-Hi').'.xlsx';
    }

    public function ruta(string $nombre): string
    {
        return $this->raiz().DIRECTORY_SEPARATOR.$nombre;
    }
}
