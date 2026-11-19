<?php

declare(strict_types=1);

namespace App\Modules\Pedidos\Http\Requests;

use App\Modules\Pedidos\Infrastructure\Consultas\PedidosQuery;
use Closure;

/** `?obra_id=` de las listas de Pedidos: una obra o `sin_obra` ("Servicios generales"). */
trait FiltraPorObra
{
    protected static function reglaDeIdOValor(string $valorEspecial): Closure
    {
        return static function (string $atributo, mixed $valor, Closure $falla) use ($valorEspecial): void {
            if ($valor !== $valorEspecial && ! (is_numeric($valor) && (int) $valor > 0)) {
                $falla('El valor no es válido.');
            }
        };
    }

    /**
     * @return list<mixed>
     */
    protected static function reglaDeObra(): array
    {
        return ['nullable', self::reglaDeIdOValor(PedidosQuery::SIN_OBRA)];
    }

    public function obra(): int|string|null
    {
        return $this->idOValor('obra_id', PedidosQuery::SIN_OBRA);
    }

    protected function idOValor(string $campo, string $valorEspecial): int|string|null
    {
        $valor = $this->input($campo);

        return match (true) {
            $valor === $valorEspecial => $valorEspecial,
            is_numeric($valor) => (int) $valor,
            default => null,
        };
    }
}
