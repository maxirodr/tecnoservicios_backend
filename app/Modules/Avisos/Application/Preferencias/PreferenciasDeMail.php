<?php

declare(strict_types=1);

namespace App\Modules\Avisos\Application\Preferencias;

use App\Modules\Avisos\Domain\Models\PreferenciaAviso;
use App\Modules\Avisos\Domain\Papel;
use App\Modules\Avisos\Domain\TipoAviso;

/**
 * Qué llega además por mail a cada persona (avisos.md §4.2–4.3, RN-AVI-19). Sin fila = el valor por
 * defecto; asignación y reasignación siempre; "vence mañana" y el aviso al superior, nunca. Quien recibe
 * el aviso por ser seguidor nunca recibe el mail (F-07 "dentro del sistema", HU-047 c6).
 */
final class PreferenciasDeMail
{
    public function corresponde(int $userId, TipoAviso $tipo, Papel $papel): bool
    {
        if ($papel === Papel::Seguidor) {
            return false;
        }

        return $this->porMail($userId, $tipo);
    }

    public function porMail(int $userId, TipoAviso $tipo): bool
    {
        if (! $tipo->sePuedeCambiarElMail()) {
            return $tipo->mailFijo();
        }

        $elegido = PreferenciaAviso::query()->where('user_id', $userId)->where('tipo', $tipo->value)->value('por_mail');

        return $elegido === null ? $tipo->mailPorDefecto() : (bool) $elegido;
    }

    /**
     * Filas de "Mis avisos" (W-19) con lo que eligió la persona.
     *
     * `solo_en_el_sistema`: "vence mañana" nunca va por mail y en W-19 no lleva casilla (resuelta DU-P19, PU-606).
     *
     * @return list<array{tipo: string, nombre: string, por_mail: bool, se_puede_cambiar: bool, solo_en_el_sistema: bool}>
     */
    public function deMisAvisos(int $userId): array
    {
        $elegidos = PreferenciaAviso::query()->where('user_id', $userId)->get()
            ->mapWithKeys(static fn (PreferenciaAviso $p): array => [$p->tipo->value => $p->por_mail]);

        return array_map(static fn (TipoAviso $tipo): array => [
            'tipo' => $tipo->value,
            'nombre' => $tipo->nombreEnMisAvisos(),
            'por_mail' => $tipo->sePuedeCambiarElMail() ? (bool) ($elegidos[$tipo->value] ?? $tipo->mailPorDefecto()) : $tipo->mailFijo(),
            'se_puede_cambiar' => $tipo->sePuedeCambiarElMail(),
            'solo_en_el_sistema' => $tipo === TipoAviso::VenceManana,
        ], TipoAviso::deMisAvisos());
    }
}
