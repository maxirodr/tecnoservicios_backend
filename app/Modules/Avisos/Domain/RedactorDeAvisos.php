<?php

declare(strict_types=1);

namespace App\Modules\Avisos\Domain;

/**
 * Textos de los avisos y asuntos de los mails, con las palabras del cliente (avisos.md §6, RN-AVI-07).
 * Comentarios y motivos se recortan a 200 caracteres con "…".
 */
final class RedactorDeAvisos
{
    public const LARGO_EXTRACTO = 200;

    public const SIN_OBRA = 'Servicios generales';

    public static function extracto(?string $texto): ?string
    {
        if ($texto === null || trim($texto) === '') {
            return null;
        }

        $texto = trim($texto);

        return mb_strlen($texto) > self::LARGO_EXTRACTO ? mb_substr($texto, 0, self::LARGO_EXTRACTO).'…' : $texto;
    }

    public function texto(TipoAviso $tipo, Papel $papel, ContextoDelAviso $c): string
    {
        $actor = $c->actorNombre ?? '';
        $cita = self::cita($c->extracto);
        $sigue = $papel === Papel::Seguidor ? ' (que seguís)' : '';

        return match ($tipo) {
            TipoAviso::Asignado => $papel === Papel::Sector
                ? $this->alSector($c)
                : "{$actor} te asignó {$c->tarea} · ".self::obra($c),
            TipoAviso::Reasignado => $papel === Papel::Sector
                ? $this->alSector($c)
                : "{$actor} te reasignó {$c->tarea} · ".self::obra($c).$cita,
            TipoAviso::Tomado => $papel === Papel::Seguidor
                ? "{$actor} tomó {$c->tarea} (que seguís) → En curso"
                : "{$actor} tomó tu tarea {$c->tarea} → pasó a En curso",
            TipoAviso::Bloqueado => $papel === Papel::Seguidor
                ? "{$actor} bloqueó {$c->tarea}{$sigue}{$cita}"
                : "{$actor} bloqueó tu tarea {$c->tarea}{$cita}",
            TipoAviso::Comentado => $papel === Papel::Solicitante
                ? "{$actor} comentó en tu tarea {$c->tarea}{$cita}"
                : "{$actor} comentó en {$c->tarea}{$cita}",
            TipoAviso::Completado => ($papel === Papel::Seguidor
                ? "{$actor} completó {$c->tarea}{$sigue}{$cita}"
                : "{$actor} completó tu tarea {$c->tarea}{$cita}").self::conComprobante($c),
            TipoAviso::CompletadoGente => "{$actor} completó {$c->tarea}, que pidió {$c->solicitanteNombre}{$cita}",
            TipoAviso::Reabierto => "{$actor} reabrió {$c->tarea} (falta algo){$cita}",
            TipoAviso::VenceManana => "Vence mañana: {$c->tarea} · ".self::obra($c),
        };
    }

    /** Asunto del mail; null = ese aviso nunca sale por mail. */
    public function asunto(TipoAviso $tipo, Papel $papel, ContextoDelAviso $c): ?string
    {
        $actor = $c->actorNombre ?? '';

        return match ($tipo) {
            TipoAviso::Asignado, TipoAviso::Reasignado => $papel === Papel::Sector
                ? "Hay una tarea para {$c->sectorNombre}: {$c->tarea}"
                : ($tipo === TipoAviso::Asignado ? 'Te asignaron' : 'Te reasignaron')." una tarea: {$c->tarea}",
            TipoAviso::Tomado => "{$actor} tomó tu tarea: {$c->tarea}",
            TipoAviso::Bloqueado => "{$actor} bloqueó tu tarea: {$c->tarea}",
            TipoAviso::Comentado => $papel === Papel::Solicitante
                ? "{$actor} comentó en tu tarea: {$c->tarea}"
                : "{$actor} comentó en {$c->tarea}",
            TipoAviso::Completado => "{$actor} completó tu tarea: {$c->tarea}",
            TipoAviso::Reabierto => "{$actor} reabrió una tarea que completaste: {$c->tarea}",
            TipoAviso::CompletadoGente, TipoAviso::VenceManana => null,
        };
    }

    private function alSector(ContextoDelAviso $c): string
    {
        return "{$c->actorNombre} dejó en {$c->sectorNombre} (tu sector) {$c->tarea}: la toma el primero que pueda";
    }

    private static function obra(ContextoDelAviso $c): string
    {
        return $c->obraNombre ?? self::SIN_OBRA;
    }

    private static function cita(?string $texto): string
    {
        $extracto = self::extracto($texto);

        return $extracto === null ? '' : ": '{$extracto}'";
    }

    private static function conComprobante(ContextoDelAviso $c): string
    {
        return $c->comprobantes > 0 ? ' · comprobante' : '';
    }
}
