<?php

declare(strict_types=1);

namespace App\Modules\Obras\Http\Requests;

use App\Shared\Http\RecortaBusqueda;

/**
 * `GET /api/obras/clientes?buscar=` (RN-OBR-17): sugerencias mientras se escribe el cliente. Hasta el
 * largo del campo cliente; más largo se recorta, nunca 422 (PU-118).
 */
final class ClientesSugeridosRequest extends ObrasRequest
{
    use RecortaBusqueda;

    public const LARGO_MAXIMO = ReglasObra::LARGO_CLIENTE;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'buscar' => ['nullable', 'string', 'max:'.self::LARGO_MAXIMO],
        ];
    }

    public function buscar(): ?string
    {
        return $this->textoONull('buscar');
    }

    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();
        $this->recortarBusqueda('buscar', self::LARGO_MAXIMO);
    }
}
