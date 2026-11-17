<?php

declare(strict_types=1);

namespace App\Shared\Busqueda\Http;

use App\Shared\Http\RecortaBusqueda;
use Illuminate\Foundation\Http\FormRequest;

/** `GET /api/buscar?q=`. Cualquiera con sesión busca; cada grupo controla su propio permiso. */
final class BuscarRequest extends FormRequest
{
    use RecortaBusqueda;

    private const LARGO_MAXIMO = 100;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return ['q' => ['nullable', 'string', 'max:'.self::LARGO_MAXIMO]];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'string' => 'Tiene que ser texto.',
            'max' => 'Es demasiado largo (máximo :max caracteres).',
        ];
    }

    public function texto(): string
    {
        return $this->string('q')->toString();
    }

    /** Más largo que el máximo se recorta, no da 422 (PU-118): el buscador nunca falla por largo. */
    protected function prepareForValidation(): void
    {
        $this->recortarBusqueda('q', self::LARGO_MAXIMO);
    }
}
