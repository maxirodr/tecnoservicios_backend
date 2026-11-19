<?php

declare(strict_types=1);

namespace App\Modules\Pedidos\Application\Lecturas;

use App\Modules\Identidad\Domain\Models\User;
use App\Modules\Pedidos\Application\FechaEmpresa;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * Carga por persona (W-04, RN-PED-49): "Carga de mi gente · semana del 19 al 25-oct", una fila por persona
 * de mi gente y yo. Orden: vencen esta semana y después vencidas, de mayor a menor (resuelta DU-P14). La
 * barra la dibuja el front con `vencen_esta_semana`: no es un puntaje ni decide nada.
 */
final readonly class CargaPorPersona
{
    public function __construct(private GenteConCarga $gente) {}

    /**
     * @return array{semana: array{desde: string, hasta: string}, personas: list<CargaDePersona>}
     *
     * @throws HttpResponseException 403 sin gente a cargo
     */
    public function handle(User $user, ?int $sectorId): array
    {
        $cargas = array_values(array_filter(
            $this->gente->de($user),
            static fn (CargaDePersona $c): bool => $sectorId === null || $c->persona->sector_id === $sectorId,
        ));
        usort($cargas, static fn (CargaDePersona $a, CargaDePersona $b): int => [$b->vencenEstaSemana, $b->vencidas] <=> [$a->vencenEstaSemana, $a->vencidas]);

        return ['semana' => FechaEmpresa::semana(), 'personas' => $cargas];
    }
}
