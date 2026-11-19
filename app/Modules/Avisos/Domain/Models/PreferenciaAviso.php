<?php

declare(strict_types=1);

namespace App\Modules\Avisos\Domain\Models;

use App\Modules\Avisos\Domain\TipoAviso;
use Illuminate\Database\Eloquent\Model;

/**
 * "Además por mail" de una persona para un tipo (avisos.md §4.2). Sin fila = el valor por defecto del
 * catálogo; solo se guardan los tipos que se pueden cambiar.
 *
 * @property int $id
 * @property int $user_id
 * @property TipoAviso $tipo
 * @property bool $por_mail
 */
final class PreferenciaAviso extends Model
{
    protected $table = 'preferencias_aviso';

    protected $fillable = ['user_id', 'tipo', 'por_mail'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'tipo' => TipoAviso::class,
            'por_mail' => 'boolean',
        ];
    }
}
