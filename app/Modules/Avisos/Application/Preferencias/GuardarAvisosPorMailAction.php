<?php

declare(strict_types=1);

namespace App\Modules\Avisos\Application\Preferencias;

use App\Modules\Avisos\Domain\Models\PreferenciaAviso;
use App\Modules\Avisos\Domain\TipoAviso;

/**
 * Guarda las casillas de "Además por mail" (W-19, RN-AVI-27). Vale desde el próximo aviso. Solo se guardan
 * los tipos que se pueden cambiar; el FormRequest ya rechazó el resto.
 */
final class GuardarAvisosPorMailAction
{
    /**
     * @param  array<string, bool>  $preferencias  tipo => por_mail
     */
    public function handle(int $userId, array $preferencias): void
    {
        $filas = [];
        foreach ($preferencias as $tipo => $porMail) {
            if (TipoAviso::from($tipo)->sePuedeCambiarElMail()) {
                $filas[] = ['user_id' => $userId, 'tipo' => $tipo, 'por_mail' => $porMail];
            }
        }

        if ($filas !== []) {
            PreferenciaAviso::query()->upsert($filas, ['user_id', 'tipo'], ['por_mail', 'updated_at']);
        }
    }
}
