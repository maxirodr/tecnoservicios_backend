<?php

declare(strict_types=1);

namespace App\Modules\Obras\Http\Requests;

/**
 * `PATCH /api/documentos/{id}`: `{nombre?, fecha?, adjuntos?: [ids]}`. La lista de adjuntos reemplaza
 * la anterior y no puede quedar vacía (obras.md §10.2).
 */
final class ActualizarDocumentoRequest extends ObrasRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'nombre' => ['sometimes', 'required', 'string', 'max:120'],
            'fecha' => ['sometimes', 'nullable', 'date_format:Y-m-d'],
            'adjuntos' => ['sometimes', 'required', 'array', 'min:1', 'max:'.GuardarDocumentoRequest::MAX_ADJUNTOS],
            'adjuntos.*' => ['integer', 'distinct'],
            'tipo' => ['prohibited'],
        ];
    }

    /**
     * @return array{nombre?: string, fecha?: string|null, adjuntos?: list<int>}
     */
    public function datos(): array
    {
        $datos = [];

        if ($this->exists('nombre')) {
            $datos['nombre'] = $this->string('nombre')->trim()->toString();
        }

        if ($this->exists('fecha')) {
            $datos['fecha'] = $this->textoONull('fecha');
        }

        $adjuntos = $this->input('adjuntos');

        if (is_array($adjuntos)) {
            $datos['adjuntos'] = array_values(array_map(intval(...), $adjuntos));
        }

        return $datos;
    }
}
