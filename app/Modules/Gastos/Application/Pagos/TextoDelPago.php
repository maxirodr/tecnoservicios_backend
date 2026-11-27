<?php

declare(strict_types=1);

namespace App\Modules\Gastos\Application\Pagos;

/**
 * Las líneas de pago del historial de W-34 (RN-GAS-60): "marcó la factura como paga (20-11-2026)" o, si ya
 * estaba paga, "cambió la fecha del pago: 20-11-2026 → 21-11-2026".
 */
final class TextoDelPago
{
    /**
     * @param  array<string, mixed>  $cambios
     */
    public static function pagada(array $cambios): string
    {
        $pago = is_array($cambios[CambiarPagoAction::CAMPO] ?? null) ? $cambios[CambiarPagoAction::CAMPO] : [];
        $antes = self::texto($pago['antes'] ?? null);
        $despues = self::texto($pago['despues'] ?? null);

        return $antes === '' ? "marcó la factura como paga ({$despues})" : "cambió la fecha del pago: {$antes} → {$despues}";
    }

    private static function texto(mixed $valor): string
    {
        return is_scalar($valor) ? (string) $valor : '';
    }
}
