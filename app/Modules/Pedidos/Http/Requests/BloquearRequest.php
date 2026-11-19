<?php

declare(strict_types=1);

namespace App\Modules\Pedidos\Http\Requests;

/** `POST /api/pedidos/{id}/bloquear` `{motivo}` (RN-PED-17). */
final class BloquearRequest extends TextoObligatorioRequest
{
    protected function campo(): string
    {
        return 'motivo';
    }

    protected function mensajeVacio(): string
    {
        return 'Contá qué lo traba.';
    }

    protected function largo(): int
    {
        return self::LARGO_TAREA;
    }
}
