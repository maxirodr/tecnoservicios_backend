<?php

declare(strict_types=1);

namespace App\Modules\Obras\Application\Presupuestos;

use App\Modules\Obras\Domain\Models\Presupuesto;
use App\Shared\Http\ApiResponse;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;

/**
 * RN-OBR-12: el N.º normalizado (con sufijo, sin la revisión) no se repite en ningún presupuesto de
 * ninguna obra, en ningún estado. El 422 trae la obra que ya lo tiene para que el front ponga el link.
 * El índice único de `presupuestos.numero` respalda la regla ante dos altas simultáneas.
 */
final class NumeroUnico
{
    /**
     * @throws HttpResponseException
     */
    public function exigir(string $numero, string $campo, ?int $exceptoPresupuestoId = null): void
    {
        $existente = Presupuesto::query()
            ->with('obra:id,nombre_trabajo')
            ->where('numero', $numero)
            ->when($exceptoPresupuestoId !== null, fn ($q) => $q->whereKeyNot($exceptoPresupuestoId))
            ->first();

        if ($existente === null) {
            return;
        }

        $obra = $existente->obra;
        $mensaje = "El presupuesto {$numero} ya está en la obra '{$obra->nombre_trabajo}'.";

        throw new HttpResponseException(new JsonResponse(
            ApiResponse::body(['obra' => ['id' => $obra->id, 'nombre_trabajo' => $obra->nombre_trabajo]], $mensaje, [$campo => [$mensaje]]),
            422,
        ));
    }

    /**
     * Dos presupuestos del mismo envío con el mismo número (p. ej. principal y adicional iguales).
     *
     * @param  array<string, string>  $numerosPorCampo  campo => número normalizado
     *
     * @throws HttpResponseException
     */
    public function exigirDistintos(array $numerosPorCampo): void
    {
        $vistos = [];

        foreach ($numerosPorCampo as $campo => $numero) {
            if (isset($vistos[$numero])) {
                $mensaje = "El presupuesto {$numero} está cargado dos veces.";

                throw new HttpResponseException(ApiResponse::error($mensaje, 422, [$campo => [$mensaje]]));
            }

            $vistos[$numero] = true;
            $this->exigir($numero, $campo);
        }
    }
}
