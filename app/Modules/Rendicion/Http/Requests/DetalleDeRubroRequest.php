<?php

declare(strict_types=1);

namespace App\Modules\Rendicion\Http\Requests;

use App\Modules\Gastos\Contracts\FiltroRenglones;
use App\Modules\Rendicion\Domain\Renglones\OrdenDeRenglones;
use Illuminate\Validation\Rule;

/** `GET /api/obras/{id}/rendicion/rubros/{clave}?orden=fecha|proveedor|total&dir=asc|desc` (W-36, RN-REN-31). */
final class DetalleDeRubroRequest extends RendicionRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'orden' => ['sometimes', 'nullable', Rule::in(OrdenDeRenglones::ORDENES)],
            'dir' => ['sometimes', 'nullable', Rule::in(OrdenDeRenglones::DIRECCIONES)],
        ];
    }

    /**
     * @return 'fecha'|'proveedor'|'total'
     */
    public function orden(): string
    {
        return match ($this->textoONull('orden')) {
            FiltroRenglones::ORDEN_PROVEEDOR => FiltroRenglones::ORDEN_PROVEEDOR,
            FiltroRenglones::ORDEN_TOTAL => FiltroRenglones::ORDEN_TOTAL,
            default => FiltroRenglones::ORDEN_FECHA,
        };
    }

    /**
     * @return 'asc'|'desc'|null
     */
    public function dir(): ?string
    {
        return match ($this->textoONull('dir')) {
            'asc' => 'asc',
            'desc' => 'desc',
            default => null,
        };
    }
}
