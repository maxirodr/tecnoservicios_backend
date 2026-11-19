<?php

declare(strict_types=1);

namespace App\Modules\Avisos\Http\Requests;

use App\Modules\Avisos\Domain\TipoAviso;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * `PUT /api/me/avisos-por-mail` con `{preferencias: {"tomado": true, "comentado": false}}` (avisos.md §9).
 * 422 si el tipo no existe o si se intenta cambiar uno fijo: la asignación siempre llega por mail y "vence
 * mañana" solo dentro del sistema (resuelta DU-P19). Mandar el valor que ya tiene un tipo fijo no es un error.
 */
final class GuardarAvisosPorMailRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'preferencias' => ['required', 'array', 'min:1'],
            'preferencias.*' => ['required', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'preferencias.required' => 'Elegí qué avisos te llegan por mail.',
            'preferencias.array' => 'Elegí qué avisos te llegan por mail.',
            'preferencias.min' => 'Elegí qué avisos te llegan por mail.',
            'preferencias.*.required' => 'Indicá si llega por mail o no.',
            'preferencias.*.boolean' => 'Indicá si llega por mail o no.',
        ];
    }

    /**
     * @return list<callable(Validator): void>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            $preferencias = $this->input('preferencias');
            if (! is_array($preferencias)) {
                return;
            }

            foreach ($preferencias as $clave => $porMail) {
                $tipo = TipoAviso::tryFrom((string) $clave);
                if ($tipo === null) {
                    $validator->errors()->add("preferencias.{$clave}", 'Ese aviso no existe.');

                    continue;
                }

                $motivo = $tipo->motivoParaNoCambiar();
                if ($motivo !== null && filter_var($porMail, FILTER_VALIDATE_BOOL) !== $tipo->mailFijo()) {
                    $validator->errors()->add("preferencias.{$clave}", $motivo);
                }
            }
        }];
    }

    /**
     * @return array<string, bool>
     */
    public function preferencias(): array
    {
        /** @var array<string, mixed> $preferencias */
        $preferencias = $this->validated('preferencias');

        return array_map(static fn (mixed $valor): bool => filter_var($valor, FILTER_VALIDATE_BOOL), $preferencias);
    }
}
