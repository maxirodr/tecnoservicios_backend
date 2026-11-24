<?php

declare(strict_types=1);

namespace App\Modules\Gastos\Http\Requests;

/** `GET /api/gastos/factura-existente` (RN-GAS-08): el aviso al salir del campo Número. */
final class FacturaExistenteRequest extends GastosRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'proveedor_id' => ['required', 'integer'],
            'numero' => ['required', 'string', 'max:'.self::LARGO_NUMERO],
            'excluir_id' => ['nullable', 'integer'],
        ];
    }

    public function proveedorId(): int
    {
        return $this->integer('proveedor_id');
    }

    public function numero(): string
    {
        return $this->string('numero')->trim()->toString();
    }

    public function excluirId(): ?int
    {
        return self::entero($this->input('excluir_id'));
    }
}
