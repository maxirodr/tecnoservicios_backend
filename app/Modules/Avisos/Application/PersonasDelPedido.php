<?php

declare(strict_types=1);

namespace App\Modules\Avisos\Application;

use App\Modules\Avisos\Domain\Padron;
use App\Modules\Identidad\Contracts\Jerarquia;
use App\Modules\Identidad\Domain\Models\Sector;
use App\Modules\Identidad\Domain\Models\User;
use App\Modules\Pedidos\Domain\Events\Participantes;

/**
 * Lo que Avisos necesita saber de Identidad sobre las personas de un pedido: quiénes están activas, las
 * personas activas del sector (si el pedido quedó en el sector), el superior de quien lo pidió (`Jerarquia`)
 * y los nombres para el texto.
 */
final class PersonasDelPedido
{
    public function __construct(private readonly Jerarquia $jerarquia) {}

    public function padron(Participantes $participantes, bool $conSuperior = false): Padron
    {
        $superior = $conSuperior ? $this->superiorDe($participantes->solicitanteId) : null;
        $candidatos = array_filter([
            $participantes->solicitanteId,
            $participantes->propietarioId,
            $superior,
            ...$participantes->seguidorIds,
        ], static fn (?int $id): bool => $id !== null);

        $delSector = $participantes->propietarioId === null ? $this->activosDelSector($participantes->sectorId) : [];

        return new Padron([...$this->activosEntre($candidatos), ...$delSector], $delSector, $superior);
    }

    public function padronDelRecordatorio(?int $propietarioId, int $sectorId): Padron
    {
        $delSector = $propietarioId === null ? $this->activosDelSector($sectorId) : [];
        $propietario = $propietarioId !== null ? $this->activosEntre([$propietarioId]) : [];

        return new Padron([...$propietario, ...$delSector], $delSector);
    }

    public function nombre(?int $userId): ?string
    {
        if ($userId === null) {
            return null;
        }

        $nombre = User::query()->whereKey($userId)->value('nombre');

        return is_string($nombre) ? $nombre : null;
    }

    public function nombreDelSector(int $sectorId): ?string
    {
        $nombre = Sector::query()->whereKey($sectorId)->value('nombre');

        return is_string($nombre) ? $nombre : null;
    }

    private function superiorDe(?int $solicitanteId): ?int
    {
        $solicitante = $solicitanteId !== null ? User::query()->find($solicitanteId) : null;

        return $solicitante !== null ? $this->jerarquia->superior($solicitante)?->id : null;
    }

    /**
     * @param  array<int>  $ids
     * @return list<int>
     */
    private function activosEntre(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        return array_values(User::query()->whereIn('id', array_values($ids))->where('activo', true)->orderBy('id')->pluck('id')->map(static fn (mixed $id): int => (int) $id)->all());
    }

    /**
     * @return list<int>
     */
    private function activosDelSector(int $sectorId): array
    {
        return array_values(User::query()->where('sector_id', $sectorId)->where('activo', true)->orderBy('id')->pluck('id')->map(static fn (mixed $id): int => (int) $id)->all());
    }
}
