<?php

declare(strict_types=1);

namespace App\Modules\Gastos\Domain\Enums;

/**
 * Qué se carga (gastos.md §4.3): lo fija la carga y no se edita. `saldo_inicial` lo carga la importación
 * de la semana 4, no la API de Gastos.
 */
enum Origen: string
{
    case Factura = 'factura';
    case SinComprobante = 'sin_comprobante';
    case Combustible = 'combustible';
    case ConsumoStock = 'consumo_stock';
    case Amortizacion = 'amortizacion';
    case RetiroRemito = 'retiro_remito';
    case Devolucion = 'devolucion';
    case MontoPeriodo = 'monto_periodo';
    case SaldoInicial = 'saldo_inicial';

    public function tipoDocumento(): TipoDocumento
    {
        return $this === self::Factura ? TipoDocumento::Factura : TipoDocumento::SinDocumento;
    }

    /** W-35: tickets, combustible y consumo de stock (RN-GAS-17). */
    public function esSinDocumentoDeW35(): bool
    {
        return in_array($this, [self::SinComprobante, self::Combustible, self::ConsumoStock], true);
    }

    /** W-46: se carga desde la rendición de una obra, todos los renglones a esa obra (RN-GAS-22). */
    public function seCargaDesdeLaRendicion(): bool
    {
        return in_array($this, [self::Amortizacion, self::RetiroRemito, self::Devolucion, self::MontoPeriodo], true);
    }

    /** Lo que acepta `POST /api/gastos` (la carga inicial es de la importación, semana 4). */
    public function seCargaPorLaApi(): bool
    {
        return $this !== self::SaldoInicial;
    }

    /** Un solo renglón: amortización (uno por obra, RN-GAS-23) y monto por período (RN-GAS-27). */
    public function tieneUnSoloRenglon(): bool
    {
        return $this === self::Amortizacion || $this === self::MontoPeriodo;
    }

    /**
     * @return list<string>
     */
    public static function valoresDeLaApi(): array
    {
        return array_values(array_map(
            static fn (self $o): string => $o->value,
            array_filter(self::cases(), static fn (self $o): bool => $o->seCargaPorLaApi()),
        ));
    }

    /**
     * @return list<string>
     */
    public static function sinDocumentoDeW35(): array
    {
        return [self::SinComprobante->value, self::Combustible->value, self::ConsumoStock->value];
    }
}
