<?php

declare(strict_types=1);

namespace App\Modules\Pedidos\Http\Requests;

/** `POST /api/pedidos/{id}/completar` `{comentario, comprobantes?: [adjunto_id]}` (RN-PED-19). */
final class CompletarRequest extends TextoObligatorioRequest
{
    protected function campo(): string
    {
        return 'comentario';
    }

    protected function mensajeVacio(): string
    {
        return 'Contá cómo se resolvió.';
    }

    protected function otrasReglas(): array
    {
        return [
            'comprobantes' => ['nullable', 'array', 'max:'.self::MAX_ADJUNTOS],
            'comprobantes.*' => ['integer', 'distinct'],
        ];
    }

    /**
     * @return list<int>
     */
    public function comprobantes(): array
    {
        return $this->ids('comprobantes');
    }
}
