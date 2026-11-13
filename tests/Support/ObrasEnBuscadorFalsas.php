<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Modules\Identidad\Contracts\Permisos;
use App\Modules\Identidad\Domain\Enums\Nivel;
use App\Modules\Identidad\Domain\Models\User;
use App\Shared\Busqueda\FuenteBusqueda;
use App\Shared\Busqueda\ResultadoBusqueda;
use Illuminate\Support\Str;

/**
 * Grupo "Obras" del buscador mientras Obras implementa el suyo (rama propia): obras ficticias en
 * memoria, por N.º de presupuesto, cliente, planta o trabajo, solo con Obras ≥ ver.
 */
final class ObrasEnBuscadorFalsas implements FuenteBusqueda
{
    /** @var list<array{id: int, numero: string, nombre_trabajo: string, cliente: string, estado: string}> */
    private const OBRAS = [
        ['id' => 12, 'numero' => '1-087-2026', 'nombre_trabajo' => 'Reparación de losa tanque B', 'cliente' => 'Terminal Sur SA', 'estado' => 'en_ejecucion'],
        ['id' => 13, 'numero' => '2-131-2026', 'nombre_trabajo' => 'Piso industrial nave 3', 'cliente' => 'Planta Ejemplo SA', 'estado' => 'terminada'],
    ];

    public function __construct(private readonly Permisos $permisos) {}

    public function puedeBuscar(User $user): bool
    {
        return $this->permisos->puede($user, 'obras', Nivel::Ver);
    }

    public function buscar(string $texto, User $user, int $limite): ResultadoBusqueda
    {
        $buscado = Str::of($texto)->ascii()->lower()->toString();
        $encontradas = array_values(array_filter(
            self::OBRAS,
            static fn (array $obra): bool => str_contains(Str::of(implode(' ', $obra))->ascii()->lower()->toString(), $buscado),
        ));

        return new ResultadoBusqueda(count($encontradas), array_slice($encontradas, 0, $limite));
    }
}
