<?php

declare(strict_types=1);

namespace App\Modules\Identidad\Http\Controllers;

use App\Modules\Identidad\Application\Sectores\CrearSectorAction;
use App\Modules\Identidad\Application\Sectores\ListarSectoresQuery;
use App\Modules\Identidad\Application\Sectores\RenombrarSectorAction;
use App\Modules\Identidad\Application\Sectores\SacarSectorAction;
use App\Modules\Identidad\Domain\Models\Sector;
use App\Modules\Identidad\Http\Requests\GuardarSectorRequest;
use App\Modules\Identidad\Http\Resources\SectorResource;
use App\Shared\Http\RespondsWithEnvelope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** F-59 · W-40: sectores. Un archivado no se edita ni se saca desde la pantalla (404). */
final class SectorController
{
    use RespondsWithEnvelope;

    public function index(Request $request, ListarSectoresQuery $listar): JsonResponse
    {
        return $this->ok(SectorResource::collection($listar->handle($request->boolean('incluir_archivados'))));
    }

    public function store(GuardarSectorRequest $request, CrearSectorAction $crear, ListarSectoresQuery $listar): JsonResponse
    {
        return $this->created(new SectorResource($listar->uno($crear->handle($request->nombre()))));
    }

    public function update(GuardarSectorRequest $request, int $sector, RenombrarSectorAction $renombrar, ListarSectoresQuery $listar): JsonResponse
    {
        $renombrado = $renombrar->handle($this->activo($sector), $request->nombre());

        return $this->ok(new SectorResource($listar->uno($renombrado)));
    }

    public function destroy(int $sector, SacarSectorAction $sacar): JsonResponse
    {
        return $this->ok(['resultado' => $sacar->handle($this->activo($sector))]);
    }

    private function activo(int $id): Sector
    {
        return Sector::query()->where('activo', true)->findOrFail($id);
    }
}
