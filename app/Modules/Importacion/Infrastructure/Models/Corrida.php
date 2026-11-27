<?php

declare(strict_types=1);

namespace App\Modules\Importacion\Infrastructure\Models;

use App\Modules\Identidad\Domain\Models\User;
use App\Modules\Importacion\Domain\EstadoCorrida;
use App\Modules\Importacion\Domain\Lote;
use App\Modules\Importacion\Domain\Modo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Un ensayo o una carga confirmada (`importacion_corridas`, §11.3). `resumen` = solo conteos (RN-IMP-13).
 *
 * @property int $id
 * @property Lote $lote
 * @property Modo $modo
 * @property string $huella
 * @property int|null $aprobo_id
 * @property EstadoCorrida $estado
 * @property array<string, int>|null $resumen
 * @property string|null $informe
 * @property Carbon $created_at
 * @property Carbon|null $terminada_at
 * @property-read User|null $aprobo
 */
final class Corrida extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'importacion_corridas';

    protected $fillable = ['lote', 'modo', 'huella', 'aprobo_id', 'estado', 'resumen', 'informe', 'terminada_at'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'lote' => Lote::class,
            'modo' => Modo::class,
            'aprobo_id' => 'integer',
            'estado' => EstadoCorrida::class,
            'resumen' => 'array',
            'created_at' => 'datetime',
            'terminada_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function aprobo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'aprobo_id');
    }
}
