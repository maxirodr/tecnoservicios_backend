<?php

declare(strict_types=1);

namespace App\Modules\Proveedores\Http\Requests;

/** `GET /api/proveedores/parecidos?nombre=&cuit=&excluir_id=` (RN-PRV-08/09). */
final class ParecidosRequest extends ProveedoresRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'nombre' => ['nullable', 'string', 'max:150'],
            'cuit' => ['nullable', 'string', 'max:20'],
            'excluir_id' => ['nullable', 'integer'],
        ];
    }

    public function nombre(): ?string
    {
        return $this->textoOpcional('nombre');
    }

    public function cuit(): ?string
    {
        return $this->textoOpcional('cuit');
    }

    public function excluirId(): ?int
    {
        return $this->filled('excluir_id') ? $this->integer('excluir_id') : null;
    }
}
