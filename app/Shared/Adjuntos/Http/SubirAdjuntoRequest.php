<?php

declare(strict_types=1);

namespace App\Shared\Adjuntos\Http;

use App\Shared\Adjuntos\ContextosAdjuntos;
use App\Shared\Adjuntos\DetectorTipoArchivo;
use App\Shared\Adjuntos\Http\Rules\LinkHttp;
use App\Shared\Adjuntos\Http\Rules\TamanoMaximoArchivo;
use App\Shared\Adjuntos\Http\Rules\TipoPermitidoPorContenido;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;

/**
 * `POST /api/adjuntos`: multipart `{contexto, archivo}` o JSON `{contexto, url, nombre?}` (obras.md §10.5).
 * Subir lo puede cualquier usuario autenticado (RN-IDE-45); el permiso del dueño se mira al asociar.
 */
final class SubirAdjuntoRequest extends FormRequest
{
    private const LARGO_URL = 1000;

    private const LARGO_NOMBRE = 200;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var ContextosAdjuntos $contextos */
        $contextos = $this->container->make(ContextosAdjuntos::class);

        return [
            'contexto' => ['required', 'string', Rule::in($contextos->contextos())],
            'archivo' => [
                'bail', 'required_without:url', 'prohibits:url', 'file',
                new TamanoMaximoArchivo((int) config('adjuntos.max_mb')),
                new TipoPermitidoPorContenido($this->container->make(DetectorTipoArchivo::class)),
            ],
            'url' => ['bail', 'required_without:archivo', 'string', 'max:'.self::LARGO_URL, new LinkHttp],
            'nombre' => ['nullable', 'string', 'max:'.self::LARGO_NOMBRE],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'contexto.required' => 'Falta el contexto del adjunto.',
            'contexto.in' => 'Ese lugar no admite adjuntos.',
            'archivo.required_without' => 'Subí un archivo o pegá un link.',
            'archivo.prohibits' => 'Subí un archivo o pegá un link, no los dos.',
            'archivo.file' => 'El archivo no se pudo subir.',
            'url.required_without' => 'Subí un archivo o pegá un link.',
            'url.string' => LinkHttp::MENSAJE,
            'url.max' => 'El link puede tener hasta '.self::LARGO_URL.' caracteres.',
            'nombre.max' => 'El nombre puede tener hasta '.self::LARGO_NOMBRE.' caracteres.',
        ];
    }

    public function archivo(): ?UploadedFile
    {
        $archivo = $this->file('archivo');

        return $archivo instanceof UploadedFile ? $archivo : null;
    }
}
