<?php

declare(strict_types=1);

namespace App\Modules\Obras\Application\Obras;

use App\Modules\Identidad\Domain\Enums\ClaveFuncion;
use App\Modules\Identidad\Domain\Models\User;
use App\Modules\Obras\Application\Acceso\AccesoObra;
use App\Modules\Obras\Infrastructure\Consultas\ObrasQuery;
use App\Modules\Obras\Infrastructure\Consultas\PorResponsable;

/**
 * RN-OBR-17 / HU-012 c. 7: clientes ya usados en otras obras (distintos, máx. 10) para que el campo
 * los sugiera y se escriban siempre igual. La búsqueda ignora mayúsculas y tildes, así el front puede
 * avisar "¿Es Planta Ejemplo SA?" cuando lo escrito se parece a uno existente.
 */
final readonly class ClientesSugeridosQuery
{
    public const MAXIMO = 10;

    public function __construct(
        private ObrasQuery $obras,
        private AccesoObra $acceso,
    ) {}

    /**
     * @return list<string>
     */
    public function handle(User $user, ?string $buscar): array
    {
        $filtros = $this->acceso->soloPropias($user, ClaveFuncion::Obras) ? [new PorResponsable($user->id)] : [];

        return $this->obras->clientes($filtros, $buscar, self::MAXIMO);
    }
}
