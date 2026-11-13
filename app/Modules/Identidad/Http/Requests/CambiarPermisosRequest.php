<?php

declare(strict_types=1);

namespace App\Modules\Identidad\Http\Requests;

use App\Modules\Identidad\Domain\Enums\Alcance;
use App\Modules\Identidad\Domain\Enums\Nivel;
use Illuminate\Validation\Rule;

/** `PUT /api/permisos` (W-15): solo las celdas cambiadas; todo o nada. */
final class CambiarPermisosRequest extends IdentidadRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'celdas' => ['required', 'array', 'min:1'],
            'celdas.*.sector_id' => ['required', 'integer'],
            'celdas.*.funcion' => ['required', 'string', Rule::exists('funciones', 'clave')],
            'celdas.*.nivel' => ['required', Rule::enum(Nivel::class)],
            'celdas.*.alcance' => ['required', Rule::enum(Alcance::class)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [...parent::messages(), 'celdas.min' => 'Mandá al menos una celda.'];
    }

    /**
     * @return list<array{sector_id: int, funcion: string, nivel: string, alcance: string}>
     */
    public function celdas(): array
    {
        $celdas = [];

        foreach (array_keys((array) $this->input('celdas', [])) as $indice) {
            $celdas[] = [
                'sector_id' => $this->integer("celdas.{$indice}.sector_id"),
                'funcion' => $this->string("celdas.{$indice}.funcion")->toString(),
                'nivel' => $this->string("celdas.{$indice}.nivel")->toString(),
                'alcance' => $this->string("celdas.{$indice}.alcance")->toString(),
            ];
        }

        return $celdas;
    }
}
