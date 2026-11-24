<?php

declare(strict_types=1);

namespace App\Modules\Gastos\Application\Carga;

use App\Modules\Gastos\Application\Lecturas\GastoLeido;
use App\Modules\Gastos\Domain\Enums\Origen;

/**
 * La confirmación de cada carga (RN-GAS-16, RN-GAS-26): "Factura cargada. Ya se restó en la rendición de
 * Piso industrial nave 3 y Reparación de losa tanque B." (el ✓ lo pone la pantalla).
 */
final class MensajeDeCarga
{
    /**
     * @return list<array{id: int, nombre_trabajo: string}>
     */
    public static function obrasAfectadas(GastoLeido $leido): array
    {
        $obras = [];

        foreach ($leido->renglones as $renglon) {
            if ($renglon->obra !== null) {
                $obras[$renglon->obra->id] = ['id' => $renglon->obra->id, 'nombre_trabajo' => $renglon->obra->nombre_trabajo];
            }
        }

        return array_values($obras);
    }

    /**
     * @param  list<array{id: int, nombre_trabajo: string}>  $obras
     */
    public static function de(Origen $origen, array $obras): string
    {
        $nombres = self::enumerar(array_column($obras, 'nombre_trabajo'));

        return match (true) {
            $origen === Origen::RetiroRemito => 'Retiro cargado. Ya suma en la rendición y en el control por cantidad.',
            $origen === Origen::Amortizacion => 'Amortización cargada.',
            $origen === Origen::Devolucion => 'Devolución cargada. Ya resta en la rendición.',
            $origen === Origen::MontoPeriodo => 'Período cargado. Ya resta en la rendición.',
            $nombres === '' => ($origen === Origen::Factura ? 'Factura cargada.' : 'Gasto cargado.').' No va a ninguna obra (todo sin obra).',
            default => ($origen === Origen::Factura ? 'Factura cargada.' : 'Gasto cargado.')." Ya se restó en la rendición de {$nombres}.",
        };
    }

    /**
     * "A", "A y B", "A, B y C".
     *
     * @param  list<string>  $nombres
     */
    private static function enumerar(array $nombres): string
    {
        $ultimo = array_pop($nombres);

        return $nombres === [] ? (string) $ultimo : implode(', ', $nombres).' y '.$ultimo;
    }
}
