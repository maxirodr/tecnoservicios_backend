<?php

declare(strict_types=1);

namespace App\Modules\Obras\Http\Requests;

use App\Modules\Obras\Application\Documentos\DatosDocumento;
use App\Modules\Obras\Domain\Enums\TipoDocumento;
use Illuminate\Validation\Rule;

/**
 * `POST /api/obras/{id}/documentos` (W-20, obras.md §4.5): nombre, fecha (obligatoria en el
 * certificado) y 1 a 10 adjuntos, cada uno archivo o link.
 */
final class GuardarDocumentoRequest extends ObrasRequest
{
    public const MAX_ADJUNTOS = 10;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'tipo' => ['required', Rule::enum(TipoDocumento::class)],
            'nombre' => ['required', 'string', 'max:120'],
            'fecha' => [Rule::requiredIf($this->input('tipo') === TipoDocumento::Certificado->value), 'nullable', 'date_format:Y-m-d'],
            'adjuntos' => ['required', 'array', 'min:1', 'max:'.self::MAX_ADJUNTOS],
            'adjuntos.*' => ['integer', 'distinct'],
        ];
    }

    public function datos(): DatosDocumento
    {
        $adjuntos = $this->input('adjuntos', []);

        return new DatosDocumento(
            TipoDocumento::from($this->string('tipo')->toString()),
            $this->string('nombre')->trim()->toString(),
            $this->textoONull('fecha'),
            is_array($adjuntos) ? array_values(array_map(intval(...), $adjuntos)) : [],
        );
    }
}
