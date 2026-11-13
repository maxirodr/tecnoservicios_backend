<?php

declare(strict_types=1);

namespace App\Modules\Obras\Http\Controllers;

use App\Modules\Obras\Application\Acceso\BuscarObra;
use App\Modules\Obras\Application\Documentos\ActualizarDocumentoAction;
use App\Modules\Obras\Application\Documentos\AgregarDocumentoAction;
use App\Modules\Obras\Application\Documentos\QuitarDocumentoAction;
use App\Modules\Obras\Domain\Models\ObraDocumento;
use App\Modules\Obras\Http\Requests\ActualizarDocumentoRequest;
use App\Modules\Obras\Http\Requests\GuardarDocumentoRequest;
use App\Modules\Obras\Http\Resources\DocumentoResource;
use App\Shared\Http\RespondsWithEnvelope;
use App\Shared\Http\UsuarioAutenticado;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** F-19 (W-20): certificados y otros documentos de la obra, con sus archivos o links. */
final class DocumentoController
{
    use RespondsWithEnvelope;
    use UsuarioAutenticado;

    public function store(GuardarDocumentoRequest $request, int $obra, BuscarObra $buscar, AgregarDocumentoAction $agregar): JsonResponse
    {
        $user = $this->usuario($request);
        $documento = $agregar->handle($user, $buscar->editable($user, $obra), $request->datos());

        return $this->created(self::recurso($documento), 'Agregamos el documento.');
    }

    public function update(ActualizarDocumentoRequest $request, int $documento, BuscarObra $buscar, ActualizarDocumentoAction $actualizar): JsonResponse
    {
        $actualizado = $actualizar->handle($buscar->documentoEditable($this->usuario($request), $documento), $request->datos());

        return $this->ok(self::recurso($actualizado), 'Guardamos los cambios.');
    }

    public function destroy(Request $request, int $documento, BuscarObra $buscar, QuitarDocumentoAction $quitar): JsonResponse
    {
        $quitar->handle($buscar->documentoEditable($this->usuario($request), $documento));

        return $this->noContent('Quitamos el documento.');
    }

    private static function recurso(ObraDocumento $documento): DocumentoResource
    {
        return new DocumentoResource($documento->load('adjuntos.subidoPor'));
    }
}
