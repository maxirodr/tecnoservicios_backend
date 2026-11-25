<?php

declare(strict_types=1);

namespace App\Modules\Gastos\Application\Correccion;

use App\Modules\Gastos\Domain\Models\Gasto;
use App\Shared\Adjuntos\AdjuntosService;
use Illuminate\Validation\ValidationException;

/**
 * `adjuntos` del PATCH es la lista completa (gastos.md §12.1): asocia los nuevos (temporales del contexto
 * del gasto, ADR-014) y quita los que no vinieron. Dentro de la transacción del caso de uso.
 */
final readonly class AdjuntosDelGasto
{
    public function __construct(private AdjuntosService $adjuntos) {}

    /**
     * @param  list<int>  $nuevos
     * @return bool si cambió algo
     *
     * @throws ValidationException
     */
    public function reemplazar(int $actorId, Gasto $gasto, array $nuevos): bool
    {
        $actuales = array_map('intval', $gasto->adjuntos()->pluck('id')->all());
        $agregar = array_values(array_diff($nuevos, $actuales));
        $quitar = array_values(array_diff($actuales, $nuevos));
        $contexto = $gasto->contextoAdjuntos();

        if ($contexto !== null) {
            $this->adjuntos->asociar($actorId, $agregar, $gasto, $contexto);
        }

        $this->adjuntos->quitar($quitar, $gasto);

        return $agregar !== [] || $quitar !== [];
    }
}
