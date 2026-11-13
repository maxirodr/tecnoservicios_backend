<?php

declare(strict_types=1);

namespace App\Modules\Obras\Http\Controllers;

use App\Modules\Obras\Application\Acceso\BuscarObra;
use App\Modules\Obras\Application\Historial\EntradaHistorial;
use App\Modules\Obras\Application\Historial\HistorialObraQuery;
use App\Shared\Http\ApiResponse;
use App\Shared\Http\UsuarioAutenticado;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * F-20 / F-14 (W-23): `GET /api/obras/{id}/historial`. Una línea por dato que cambió (HU-016 c. 2);
 * la paginación cuenta acciones (una acción puede traer varias líneas con el mismo cuándo y quién).
 */
final class ObraHistorialController
{
    use UsuarioAutenticado;

    public function __invoke(Request $request, int $obra, BuscarObra $buscar, HistorialObraQuery $historial): JsonResponse
    {
        $user = $this->usuario($request);
        $pagina = $historial->handle($user, $buscar->visible($user, $obra));
        $respuesta = ApiResponse::success($pagina);

        /** @var array{data: list<mixed>} $cuerpo */
        $cuerpo = $respuesta->getData(true);
        $cuerpo['data'] = self::lineas($pagina->items());

        return $respuesta->setData($cuerpo);
    }

    /**
     * @param  array<int, EntradaHistorial>  $entradas
     * @return list<array<string, mixed>>
     */
    private static function lineas(array $entradas): array
    {
        $lineas = [];

        foreach ($entradas as $entrada) {
            foreach ($entrada->lineas as $que) {
                $lineas[] = [
                    'id' => $entrada->id,
                    'cuando' => $entrada->cuando->format(DATE_ATOM),
                    'quien' => $entrada->quienId === null ? null : ['id' => $entrada->quienId, 'nombre' => $entrada->quienNombre],
                    'que' => $que,
                ];
            }
        }

        return $lineas;
    }
}
