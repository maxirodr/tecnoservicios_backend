<?php

declare(strict_types=1);

namespace App\Modules\Avisos\Domain;

/**
 * Catálogo de tipos de aviso (avisos.md §4.3) y qué llega por mail (resuelta DU-P19): asignación y
 * reasignación siempre; tomado, completado, bloqueado, comentado y reabierto si la persona lo activa;
 * "vence mañana" y el aviso al superior, nunca.
 */
enum TipoAviso: string
{
    case Asignado = 'asignado';
    case Reasignado = 'reasignado';
    case Tomado = 'tomado';
    case Completado = 'completado';
    case VenceManana = 'vence_manana';
    case Bloqueado = 'bloqueado';
    case Comentado = 'comentado';
    case Reabierto = 'reabierto';
    case CompletadoGente = 'completado_gente';

    /**
     * Filas de "Mis avisos" en el orden de W-19 (el aviso al superior no tiene fila).
     *
     * @return list<self>
     */
    public static function deMisAvisos(): array
    {
        return [self::Asignado, self::Reasignado, self::Tomado, self::Completado, self::VenceManana, self::Bloqueado, self::Comentado, self::Reabierto];
    }

    /** Texto de la fila en W-19. */
    public function nombreEnMisAvisos(): string
    {
        return match ($this) {
            self::Asignado => 'Me asignan una tarea',
            self::Reasignado => 'Me reasignan una tarea',
            self::Tomado => 'Toman (En curso) una tarea que pedí',
            self::Completado => 'Completan una tarea que pedí',
            self::VenceManana => 'Vence mañana una tarea mía',
            self::Bloqueado => 'Bloquean una tarea que pedí, con el motivo',
            self::Comentado => 'Comentan en una tarea mía',
            self::Reabierto => 'Reabren una tarea que completé',
            self::CompletadoGente => 'Completan una tarea de mi gente',
        };
    }

    public function mailPorDefecto(): bool
    {
        return $this->mailFijo();
    }

    /** La asignación y la reasignación llegan siempre por mail: no se pueden destildar. */
    public function mailFijo(): bool
    {
        return $this === self::Asignado || $this === self::Reasignado;
    }

    /** "Vence mañana" (F-37) y el aviso al superior (F-31) llegan solo dentro del sistema. */
    public function soloEnElSistema(): bool
    {
        return $this === self::VenceManana || $this === self::CompletadoGente;
    }

    public function sePuedeCambiarElMail(): bool
    {
        return ! $this->mailFijo() && ! $this->soloEnElSistema();
    }

    /** Por qué no se puede cambiar el mail de este tipo (422 de `PUT /api/me/avisos-por-mail`). */
    public function motivoParaNoCambiar(): ?string
    {
        return match (true) {
            $this->mailFijo() => 'La asignación siempre llega por mail.',
            $this->soloEnElSistema() => 'Este aviso llega solo dentro del sistema.',
            default => null,
        };
    }
}
