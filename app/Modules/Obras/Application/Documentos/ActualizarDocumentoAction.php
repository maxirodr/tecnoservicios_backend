<?php

declare(strict_types=1);

namespace App\Modules\Obras\Application\Documentos;

use App\Modules\Obras\Application\Obras\HistorialDeCampos;
use App\Modules\Obras\Application\Presupuestos\AdjuntosDeObra;
use App\Modules\Obras\Domain\Models\ObraDocumento;
use App\Shared\Actividad\RegistroActividad;
use Illuminate\Database\Connection;
use Illuminate\Validation\ValidationException;

/**
 * `PATCH /api/documentos/{id}` (RN-OBR-23): corrige nombre y fecha; la lista de adjuntos reemplaza
 * la anterior (los que no vienen se quitan y se borran). Siempre queda al menos uno.
 */
final readonly class ActualizarDocumentoAction
{
    public const ACCION = 'documento_editado';

    private const CAMPOS = ['nombre', 'fecha'];

    public function __construct(
        private Connection $db,
        private AdjuntosDeObra $adjuntos,
        private RegistroActividad $actividad,
    ) {}

    /**
     * @param  array{nombre?: string, fecha?: string|null, adjuntos?: list<int>}  $datos
     *
     * @throws ValidationException
     */
    public function handle(ObraDocumento $documento, array $datos): ObraDocumento
    {
        if (array_key_exists('fecha', $datos) && $datos['fecha'] === null && $documento->tipo->exigeFecha()) {
            throw ValidationException::withMessages(['fecha' => ['El certificado lleva fecha.']]);
        }

        return $this->db->transaction(function () use ($documento, $datos): ObraDocumento {
            $antes = HistorialDeCampos::valores($documento, self::CAMPOS);
            $documento->fill(array_intersect_key($datos, array_flip(self::CAMPOS)))->save();

            if (isset($datos['adjuntos'])) {
                $actuales = $documento->adjuntos->modelKeys();
                $this->adjuntos->asociar(array_values(array_diff($datos['adjuntos'], $actuales)), $documento, ObraDocumento::CONTEXTO, 'adjuntos');
                $this->adjuntos->quitar(array_values(array_diff($actuales, $datos['adjuntos'])), $documento);
            }

            $cambios = HistorialDeCampos::diferencias($antes, HistorialDeCampos::valores($documento, self::CAMPOS));

            if ($cambios !== []) {
                $this->actividad->registrar($documento, self::ACCION, $cambios);
            }

            return $documento->load('adjuntos');
        });
    }
}
