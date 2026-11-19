<?php

declare(strict_types=1);

namespace App\Modules\Pedidos\Http\Requests;

/** `POST /api/pedidos/{id}/comentarios` `{texto}` (RN-PED-25): vacío no se envía (HU-029 c3). */
final class ComentarRequest extends TextoObligatorioRequest
{
    protected function campo(): string
    {
        return 'texto';
    }

    protected function mensajeVacio(): string
    {
        return 'Escribí el comentario.';
    }
}
