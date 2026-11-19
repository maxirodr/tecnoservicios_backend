<?php

declare(strict_types=1);

namespace App\Modules\Avisos\Domain;

use App\Modules\Pedidos\Domain\Events\Participantes;

/**
 * Quién recibe cada aviso (avisos.md §5 y §6, cap. 7 "Avisos del pedido"). Clase pura: no consulta nada,
 * recibe a los participantes del pedido y el padrón. En todos los casos saca al actor (RN-AVI-02), a los
 * inactivos (RN-AVI-03) y deja un solo aviso por persona, el de su papel más directo (RN-AVI-04).
 */
final class DestinatariosPorEvento
{
    /**
     * Al crear: al propietario o, si quedó en el sector, a sus personas activas (RN-AVI-08, DU-16).
     *
     * @return list<Destinatario>
     */
    public function asignado(Participantes $participantes, int $actorId, Padron $padron): array
    {
        return $this->filtrar($this->propietarios($participantes, $padron, TipoAviso::Asignado), $actorId, $padron);
    }

    /**
     * Al nuevo propietario o a las personas activas del sector; ni al anterior ni a quien lo pidió (RN-AVI-09).
     *
     * @return list<Destinatario>
     */
    public function reasignado(Participantes $participantes, int $actorId, Padron $padron): array
    {
        return $this->filtrar($this->propietarios($participantes, $padron, TipoAviso::Reasignado), $actorId, $padron);
    }

    /**
     * A quien lo pidió y a los seguidores (RN-AVI-10, RN-AVI-14).
     *
     * @return list<Destinatario>
     */
    public function tomado(Participantes $participantes, int $actorId, Padron $padron): array
    {
        return $this->delSolicitanteYSeguidores($participantes, $actorId, $padron, TipoAviso::Tomado);
    }

    /**
     * A quien lo pidió y a los seguidores; el motivo viaja en el texto (RN-AVI-10).
     *
     * @return list<Destinatario>
     */
    public function bloqueado(Participantes $participantes, int $actorId, Padron $padron): array
    {
        return $this->delSolicitanteYSeguidores($participantes, $actorId, $padron, TipoAviso::Bloqueado);
    }

    /**
     * A las dos partes (quien lo pidió y el propietario, o las personas del sector) y a los seguidores,
     * nunca al autor: si comenta una parte le llega a la otra; si comenta un tercero, a las dos (RN-AVI-11,
     * DU-22, DU-P23).
     *
     * @return list<Destinatario>
     */
    public function comentado(Participantes $participantes, int $actorId, Padron $padron): array
    {
        return $this->filtrar([
            ...$this->propietarios($participantes, $padron, TipoAviso::Comentado),
            ...$this->solicitante($participantes, TipoAviso::Comentado),
            ...$this->seguidores($participantes, TipoAviso::Comentado),
        ], $actorId, $padron);
    }

    /**
     * A quien lo pidió, a su superior (solo en el sistema) y a los seguidores (RN-AVI-12, DU-13).
     *
     * @return list<Destinatario>
     */
    public function completado(Participantes $participantes, int $actorId, Padron $padron): array
    {
        $superior = $participantes->solicitanteId !== null && $padron->superiorDelSolicitante !== null
            ? [new Destinatario($padron->superiorDelSolicitante, TipoAviso::CompletadoGente, Papel::Superior)]
            : [];

        return $this->filtrar([
            ...$this->solicitante($participantes, TipoAviso::Completado),
            ...$superior,
            ...$this->seguidores($participantes, TipoAviso::Completado),
        ], $actorId, $padron);
    }

    /**
     * Al propietario (el que se eligió al reabrir si el anterior estaba inactivo, o las personas del
     * sector) (RN-AVI-13, DU-P09).
     *
     * @return list<Destinatario>
     */
    public function reabierto(Participantes $participantes, int $actorId, Padron $padron): array
    {
        return $this->filtrar($this->propietarios($participantes, $padron, TipoAviso::Reabierto), $actorId, $padron);
    }

    /**
     * "Vence mañana": al propietario o a las personas activas del sector; nunca a quien lo pidió ni a los
     * seguidores (RN-AVI-16, RN-AVI-18). Lo manda el sistema: no hay actor que sacar.
     *
     * @return list<Destinatario>
     */
    public function recordatorio(?int $propietarioId, Padron $padron): array
    {
        $candidatos = $propietarioId !== null
            ? [new Destinatario($propietarioId, TipoAviso::VenceManana, Papel::Propietario)]
            : $this->personasDelSector($padron, TipoAviso::VenceManana);

        return $this->filtrar($candidatos, null, $padron);
    }

    /**
     * @return list<Destinatario>
     */
    private function delSolicitanteYSeguidores(Participantes $participantes, int $actorId, Padron $padron, TipoAviso $tipo): array
    {
        return $this->filtrar([
            ...$this->solicitante($participantes, $tipo),
            ...$this->seguidores($participantes, $tipo),
        ], $actorId, $padron);
    }

    /**
     * @return list<Destinatario>
     */
    private function propietarios(Participantes $participantes, Padron $padron, TipoAviso $tipo): array
    {
        if ($participantes->propietarioId !== null) {
            return [new Destinatario($participantes->propietarioId, $tipo, Papel::Propietario)];
        }

        return $this->personasDelSector($padron, $tipo);
    }

    /**
     * @return list<Destinatario>
     */
    private function personasDelSector(Padron $padron, TipoAviso $tipo): array
    {
        return array_map(static fn (int $id): Destinatario => new Destinatario($id, $tipo, Papel::Sector), $padron->personasDelSector);
    }

    /**
     * Pedido de carga inicial sin solicitante: no hay a quién (RN-AVI-15).
     *
     * @return list<Destinatario>
     */
    private function solicitante(Participantes $participantes, TipoAviso $tipo): array
    {
        return $participantes->solicitanteId !== null
            ? [new Destinatario($participantes->solicitanteId, $tipo, Papel::Solicitante)]
            : [];
    }

    /**
     * @return list<Destinatario>
     */
    private function seguidores(Participantes $participantes, TipoAviso $tipo): array
    {
        return array_map(static fn (int $id): Destinatario => new Destinatario($id, $tipo, Papel::Seguidor), $participantes->seguidorIds);
    }

    /**
     * @param  list<Destinatario>  $candidatos
     * @return list<Destinatario>
     */
    private function filtrar(array $candidatos, ?int $actorId, Padron $padron): array
    {
        usort($candidatos, static fn (Destinatario $a, Destinatario $b): int => $a->papel->precedencia() <=> $b->papel->precedencia());

        $porPersona = [];
        foreach ($candidatos as $candidato) {
            if ($candidato->userId === $actorId || ! $padron->activo($candidato->userId) || isset($porPersona[$candidato->userId])) {
                continue;
            }

            $porPersona[$candidato->userId] = $candidato;
        }

        return array_values($porPersona);
    }
}
