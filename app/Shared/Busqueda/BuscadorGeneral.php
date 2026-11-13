<?php

declare(strict_types=1);

namespace App\Shared\Busqueda;

use App\Modules\Identidad\Domain\Models\User;
use Illuminate\Support\Str;

/**
 * F-03 · W-18 · HU-022: un solo buscador. Agrupa por tipo con su cantidad, solo los grupos de los
 * módulos a los que la persona tiene acceso; con menos de 2 caracteres no busca todavía.
 */
final readonly class BuscadorGeneral
{
    public const MINIMO_CARACTERES = 2;

    /** Primeros resultados por grupo; la cantidad dice cuántos hay en total. */
    public const POR_GRUPO = 10;

    public function __construct(private FuentesBusqueda $fuentes) {}

    /**
     * @return list<array{tipo: string, cantidad: int, resultados: list<array<string, mixed>>}>
     */
    public function handle(User $user, string $texto): array
    {
        $texto = Str::squish($texto);

        if (mb_strlen($texto) < self::MINIMO_CARACTERES) {
            return [];
        }

        $grupos = [];
        foreach ($this->fuentes->enOrden() as $tipo => $fuente) {
            if (! $fuente->puedeBuscar($user)) {
                continue;
            }

            $resultado = $fuente->buscar($texto, $user, self::POR_GRUPO);
            $grupos[] = ['tipo' => $tipo, 'cantidad' => $resultado->cantidad, 'resultados' => $resultado->resultados];
        }

        return $grupos;
    }
}
