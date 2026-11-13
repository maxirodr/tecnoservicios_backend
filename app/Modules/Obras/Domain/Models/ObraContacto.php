<?php

declare(strict_types=1);

namespace App\Modules\Obras\Domain\Models;

use App\Modules\Obras\Domain\Enums\TipoContacto;
use Illuminate\Database\Eloquent\Model;

/**
 * Contacto del cliente en la obra (obras.md §4.4): solicitante en planta o comprador.
 *
 * @property int $id
 * @property int $obra_id
 * @property TipoContacto $tipo
 * @property string $nombre
 * @property string $email
 * @property string $telefono
 */
class ObraContacto extends Model
{
    public const CAMPOS = ['nombre', 'email', 'telefono'];

    protected $table = 'obra_contactos';

    protected $fillable = ['obra_id', 'tipo', 'nombre', 'email', 'telefono'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'obra_id' => 'integer',
            'tipo' => TipoContacto::class,
        ];
    }
}
