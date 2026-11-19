<?php

declare(strict_types=1);

namespace App\Modules\Avisos\Http\Controllers;

use App\Modules\Avisos\Application\Preferencias\GuardarAvisosPorMailAction;
use App\Modules\Avisos\Application\Preferencias\PreferenciasDeMail;
use App\Modules\Avisos\Http\Requests\GuardarAvisosPorMailRequest;
use App\Shared\Http\RespondsWithEnvelope;
use App\Shared\Http\UsuarioAutenticado;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** F-09 · F-10 (W-19 "Mis avisos"): qué avisos me llegan además por mail. Siempre sobre uno mismo. */
final class AvisosPorMailController
{
    use RespondsWithEnvelope;
    use UsuarioAutenticado;

    public function show(Request $request, PreferenciasDeMail $preferencias): JsonResponse
    {
        return $this->ok($preferencias->deMisAvisos($this->usuario($request)->id));
    }

    public function update(GuardarAvisosPorMailRequest $request, GuardarAvisosPorMailAction $guardar, PreferenciasDeMail $preferencias): JsonResponse
    {
        $userId = $this->usuario($request)->id;
        $guardar->handle($userId, $request->preferencias());

        return $this->ok($preferencias->deMisAvisos($userId), 'Guardamos tus avisos por mail.');
    }
}
