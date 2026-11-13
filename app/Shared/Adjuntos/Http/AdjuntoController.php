<?php

declare(strict_types=1);

namespace App\Shared\Adjuntos\Http;

use App\Modules\Identidad\Domain\Models\User;
use App\Shared\Adjuntos\Application\BuscarAdjuntoParaDescargarAction;
use App\Shared\Adjuntos\Application\QuitarAdjuntoAction;
use App\Shared\Adjuntos\Application\RegistrarLinkAction;
use App\Shared\Adjuntos\Application\SubirArchivoAction;
use App\Shared\Http\RespondsWithEnvelope;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Filesystem\Factory as FilesystemFactory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Adjuntos transversales (ADR-014, obras.md §10.5, identidad.md §6.6).
 */
final class AdjuntoController
{
    use RespondsWithEnvelope;

    public function store(SubirAdjuntoRequest $request, SubirArchivoAction $subir, RegistrarLinkAction $registrarLink): JsonResponse
    {
        $user = self::usuario($request);
        $contexto = $request->string('contexto')->toString();
        $archivo = $request->archivo();

        $adjunto = $archivo !== null
            ? $subir->handle($user, $contexto, $archivo)
            : $registrarLink->handle($user, $contexto, $request->string('url')->toString(), $request->string('nombre')->toString());

        return $this->created(new AdjuntoResource($adjunto->load('subidoPor')));
    }

    public function show(Request $request, int $adjunto, BuscarAdjuntoParaDescargarAction $buscar, FilesystemFactory $filesystem): StreamedResponse
    {
        $encontrado = $buscar->handle(self::usuario($request), $adjunto);

        return $filesystem->disk((string) $encontrado->disco)->download((string) $encontrado->ruta, $encontrado->nombre, [
            'Content-Type' => $encontrado->mime ?? 'application/octet-stream',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function destroy(Request $request, int $adjunto, QuitarAdjuntoAction $quitar): JsonResponse
    {
        $quitar->handle(self::usuario($request), $adjunto);

        return $this->noContent('Adjunto quitado.');
    }

    private static function usuario(Request $request): User
    {
        $user = $request->user();

        if (! $user instanceof User) {
            throw new AuthenticationException;
        }

        return $user;
    }
}
