<?php

declare(strict_types=1);

namespace App\Modules\Obras\Application\Documentos;

use App\Modules\Obras\Application\Presupuestos\AdjuntosDeObra;
use App\Modules\Obras\Domain\Models\ObraDocumento;
use App\Shared\Actividad\RegistroActividad;
use App\Shared\Adjuntos\Adjunto;
use Illuminate\Database\Connection;

/**
 * RN-OBR-23/24: quita un certificado o documento con sus archivos y links (el archivo físico se
 * borra). Historial: "Quitó el certificado 'Certificado N.º 1' (certificado-1.pdf, 1 link)".
 */
final readonly class QuitarDocumentoAction
{
    public const ACCION = 'documento_quitado';

    public function __construct(
        private Connection $db,
        private AdjuntosDeObra $adjuntos,
        private RegistroActividad $actividad,
    ) {}

    public function handle(ObraDocumento $documento): void
    {
        $this->db->transaction(function () use ($documento): void {
            $detalle = self::detalle($documento);
            $this->adjuntos->quitar($documento->adjuntos->modelKeys(), $documento);
            $documento->delete();
            $this->actividad->registrar($documento->obra, self::ACCION, [
                $documento->tipo->value => ['antes' => "'{$documento->nombre}'{$detalle}", 'despues' => null],
            ]);
        });
    }

    /** Nombres de los archivos y cantidad de links, sin URL ni ruta (RN-OBR-27). */
    private static function detalle(ObraDocumento $documento): string
    {
        $archivos = $documento->adjuntos->filter(static fn (Adjunto $a): bool => $a->esArchivo())->pluck('nombre')->all();
        $links = $documento->adjuntos->count() - count($archivos);
        $partes = $archivos;

        if ($links > 0) {
            $partes[] = $links === 1 ? '1 link' : "{$links} links";
        }

        return $partes === [] ? '' : ' ('.implode(', ', $partes).')';
    }
}
