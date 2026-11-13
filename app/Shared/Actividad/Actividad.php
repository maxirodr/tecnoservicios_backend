<?php

declare(strict_types=1);

namespace App\Shared\Actividad;

use App\Modules\Identidad\Domain\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;
use LogicException;

/**
 * Una línea del registro de quién hizo qué (ADR-010). Solo inserción: no se edita ni se borra.
 *
 * @property int $id
 * @property string $sujeto_type
 * @property int $sujeto_id
 * @property int|null $user_id
 * @property string $accion
 * @property array<string, array{antes: mixed, despues: mixed}>|null $cambios
 * @property Carbon $created_at
 * @property-read User|null $user
 */
final class Actividad extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'actividades';

    protected $fillable = ['sujeto_type', 'sujeto_id', 'user_id', 'accion', 'cambios'];

    protected static function booted(): void
    {
        self::updating(static fn (): never => throw new LogicException('Las actividades son solo inserción.'));
        self::deleting(static fn (): never => throw new LogicException('Las actividades son solo inserción.'));
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sujeto_id' => 'integer',
            'user_id' => 'integer',
            'cambios' => 'array',
            'created_at' => 'datetime',
        ];
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function sujeto(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
