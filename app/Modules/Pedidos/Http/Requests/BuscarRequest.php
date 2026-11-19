<?php

declare(strict_types=1);

namespace App\Modules\Pedidos\Http\Requests;

use App\Shared\Http\RecortaBusqueda;

/** `?buscar=` de los selectores y listas de Pedidos: más largo se recorta, nunca 422 (PU-118). */
final class BuscarRequest extends PedidosRequest
{
    use RecortaBusqueda;

    public const LARGO_BUSCAR = 100;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return ['buscar' => ['nullable', 'string', 'max:'.self::LARGO_BUSCAR]];
    }

    public function buscar(): ?string
    {
        return $this->textoONull('buscar');
    }

    protected function prepareForValidation(): void
    {
        $this->recortarBusqueda('buscar', self::LARGO_BUSCAR);
    }
}
