<?php

declare(strict_types=1);

namespace App\Modules\Obras\Application\Presupuestos;

use App\Shared\Adjuntos\AdjuntosService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

/**
 * Asocia adjuntos temporales a un presupuesto o documento de la obra dentro de la transacción del
 * caso de uso (ADR-014) y reporta el 422 de RN-IDE-46 con el nombre del campo del módulo
 * (`presupuesto.oferta`, `aprobacion.adjunto`, `adjuntos`…).
 */
final readonly class AdjuntosDeObra
{
    public function __construct(private AdjuntosService $adjuntos) {}

    /**
     * @param  array<array-key, int|string>  $ids
     *
     * @throws ValidationException
     */
    public function asociar(array $ids, Model $sujeto, string $contexto, string $campo): void
    {
        try {
            $this->adjuntos->asociar($ids, $sujeto, $contexto);
        } catch (ValidationException $e) {
            throw ValidationException::withMessages([$campo => [AdjuntosService::MENSAJE_NO_UTILIZABLE]]);
        }
    }

    /**
     * @param  array<array-key, int|string>  $ids
     */
    public function quitar(array $ids, Model $sujeto): void
    {
        $this->adjuntos->quitar($ids, $sujeto);
    }
}
