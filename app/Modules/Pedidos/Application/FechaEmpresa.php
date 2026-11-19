<?php

declare(strict_types=1);

namespace App\Modules\Pedidos\Application;

use Carbon\CarbonImmutable;

/**
 * El día se corta en la hora de la empresa (`app.zona_empresa`, Argentina), nunca en UTC: "hoy",
 * "vencido", "mañana" y la fecha de pedido por defecto (RN-PED-04, RN-PED-36).
 */
final class FechaEmpresa
{
    public static function zona(): string
    {
        return config()->string('app.zona_empresa');
    }

    public static function hoy(): CarbonImmutable
    {
        return CarbonImmutable::now(self::zona())->startOfDay();
    }

    /** "2026-10-19" */
    public static function hoyTexto(): string
    {
        return self::hoy()->toDateString();
    }

    public static function mananaTexto(): string
    {
        return self::hoy()->addDay()->toDateString();
    }

    /** Un instante ISO-8601 con el huso de la empresa ("2026-10-14T09:12:00-03:00"). */
    public static function instante(?\DateTimeInterface $momento): ?string
    {
        return $momento === null ? null : CarbonImmutable::instance($momento)->setTimezone(self::zona())->toIso8601String();
    }
}
