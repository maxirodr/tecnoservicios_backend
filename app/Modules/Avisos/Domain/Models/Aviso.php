<?php

declare(strict_types=1);

namespace App\Modules\Avisos\Domain\Models;

use App\Modules\Avisos\Domain\TipoAviso;
use App\Modules\Identidad\Domain\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Una línea de la campana y de "Todos los avisos" de una persona (avisos.md §4.1). `datos` es la foto del
 * momento: el texto ya redactado y lo que hace falta para mostrarlo y para el mail sin otra consulta. No se
 * borra (DU-P22); solo cambia `leido_at`.
 *
 * @property int $id
 * @property int $user_id
 * @property TipoAviso $tipo
 * @property int|null $pedido_id
 * @property int|null $actor_id
 * @property array<string, mixed> $datos
 * @property string $dedupe_key
 * @property Carbon|null $leido_at
 * @property Carbon $created_at
 * @property-read User $user
 * @property-read User|null $actor
 */
final class Aviso extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'avisos';

    protected $fillable = ['user_id', 'tipo', 'pedido_id', 'actor_id', 'datos', 'dedupe_key', 'leido_at'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'tipo' => TipoAviso::class,
            'pedido_id' => 'integer',
            'actor_id' => 'integer',
            'datos' => 'array',
            'leido_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function dato(string $clave): ?string
    {
        $valor = $this->datos[$clave] ?? null;

        return is_scalar($valor) ? (string) $valor : null;
    }
}
