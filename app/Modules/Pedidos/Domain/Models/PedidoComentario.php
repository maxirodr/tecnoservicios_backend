<?php

declare(strict_types=1);

namespace App\Modules\Pedidos\Domain\Models;

use App\Modules\Identidad\Domain\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use LogicException;

/**
 * La ida y vuelta del pedido (F-30, pedidos.md §4.2). Solo inserción: no se edita ni se borra.
 *
 * @property int $id
 * @property int $pedido_id
 * @property int $user_id
 * @property string $texto
 * @property Carbon $created_at
 * @property-read User $autor
 */
final class PedidoComentario extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'pedido_comentarios';

    protected $fillable = ['pedido_id', 'user_id', 'texto'];

    protected static function booted(): void
    {
        self::updating(static fn (): never => throw new LogicException('Los comentarios no se editan.'));
        self::deleting(static fn (): never => throw new LogicException('Los comentarios no se borran.'));
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'pedido_id' => 'integer',
            'user_id' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function autor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
