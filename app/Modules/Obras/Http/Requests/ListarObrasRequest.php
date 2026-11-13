<?php

declare(strict_types=1);

namespace App\Modules\Obras\Http\Requests;

use App\Modules\Obras\Domain\Enums\ObraEstado;
use Illuminate\Validation\Rule;

/** `GET /api/obras?estado=&buscar=&responsable_id=&page=` (W-05). Abre en En ejecución (RN-OBR-01). */
final class ListarObrasRequest extends ObrasRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'estado' => ['nullable', Rule::enum(ObraEstado::class)],
            'buscar' => ['nullable', 'string', 'max:100'],
            'responsable_id' => ['nullable', 'integer'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function estado(): ObraEstado
    {
        return ObraEstado::tryFrom($this->string('estado')->toString()) ?? ObraEstado::EnEjecucion;
    }

    public function buscar(): ?string
    {
        return $this->textoONull('buscar');
    }

    public function responsableId(): ?int
    {
        return $this->enteroONull('responsable_id');
    }

    public function pagina(): int
    {
        return max(1, $this->integer('page', 1));
    }
}
