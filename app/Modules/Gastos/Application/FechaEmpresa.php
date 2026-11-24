<?php

declare(strict_types=1);

namespace App\Modules\Gastos\Application;

use Carbon\CarbonImmutable;
use DateTimeInterface;

/**
 * El día se corta en la hora de la empresa (`app.zona_empresa`, Argentina), nunca en UTC (RN-GAS-04,
 * §10 caso 15). Los instantes viajan en ISO-8601 con ese huso (gastos.md §12).
 */
final class FechaEmpresa
{
    public static function zona(): string
    {
        return config()->string('app.zona_empresa');
    }

    /** "2026-10-19" */
    public static function hoy(): string
    {
        return CarbonImmutable::now(self::zona())->toDateString();
    }

    /** El 1 del mes en curso (W-33 por defecto, RN-GAS-30). */
    public static function primeroDelMes(): string
    {
        return CarbonImmutable::now(self::zona())->startOfMonth()->toDateString();
    }

    public static function instante(?DateTimeInterface $momento): ?string
    {
        return $momento === null ? null : CarbonImmutable::instance($momento)->setTimezone(self::zona())->toIso8601String();
    }

    /** El día en Argentina de un instante guardado en UTC. */
    public static function diaDe(DateTimeInterface $momento): CarbonImmutable
    {
        return CarbonImmutable::instance($momento)->setTimezone(self::zona());
    }
}
