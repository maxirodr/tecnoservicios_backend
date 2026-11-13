<?php

declare(strict_types=1);

namespace App\Modules\Obras\Application\Documentos;

use App\Modules\Identidad\Domain\Models\User;
use App\Modules\Obras\Application\Presupuestos\AdjuntosDeObra;
use App\Modules\Obras\Domain\Models\Obra;
use App\Modules\Obras\Domain\Models\ObraDocumento;
use App\Shared\Actividad\RegistroActividad;
use Illuminate\Database\Connection;
use Illuminate\Validation\ValidationException;

/**
 * F-19 / HU-014 criterio 3: agrega un certificado u otro documento con uno o más archivos o links
 * (RN-OBR-23); también a una obra Terminada (RN-OBR-25). Queda en el historial de la obra.
 */
final readonly class AgregarDocumentoAction
{
    public const ACCION = 'documento_agregado';

    public function __construct(
        private Connection $db,
        private AdjuntosDeObra $adjuntos,
        private RegistroActividad $actividad,
    ) {}

    /**
     * @throws ValidationException
     */
    public function handle(User $actor, Obra $obra, DatosDocumento $datos): ObraDocumento
    {
        return $this->db->transaction(function () use ($actor, $obra, $datos): ObraDocumento {
            $documento = ObraDocumento::query()->create([
                'obra_id' => $obra->id,
                'tipo' => $datos->tipo,
                'nombre' => $datos->nombre,
                'fecha' => $datos->fecha,
                'created_by' => $actor->id,
            ]);

            $this->adjuntos->asociar($datos->adjuntos, $documento, ObraDocumento::CONTEXTO, 'adjuntos');
            $this->actividad->registrar($obra, self::ACCION, [$datos->tipo->value => ['antes' => null, 'despues' => $datos->nombre]]);

            return $documento->refresh()->load('adjuntos');
        });
    }
}
