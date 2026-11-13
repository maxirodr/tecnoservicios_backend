<?php

declare(strict_types=1);

namespace App\Shared\Adjuntos;

use App\Modules\Identidad\Domain\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * Archivo subido o link (ADR-014). Sin sujeto = temporal: solo lo ve quien lo subió y se purga a las 24 h.
 * `disco` y `ruta` nunca salen en el JSON.
 *
 * @property int $id
 * @property string|null $sujeto_type
 * @property int|null $sujeto_id
 * @property string $contexto
 * @property TipoAdjunto $tipo
 * @property string|null $url
 * @property string|null $disco
 * @property string|null $ruta
 * @property string $nombre
 * @property string|null $mime
 * @property int|null $tamano_bytes
 * @property int $subido_por
 * @property Carbon $created_at
 * @property-read Model|null $sujeto
 * @property-read User $subidoPor
 */
final class Adjunto extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'adjuntos';

    protected $fillable = [
        'contexto', 'tipo', 'url', 'disco', 'ruta', 'nombre', 'mime', 'tamano_bytes', 'subido_por',
    ];

    protected $hidden = ['disco', 'ruta'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tipo' => TipoAdjunto::class,
            'sujeto_id' => 'integer',
            'tamano_bytes' => 'integer',
            'subido_por' => 'integer',
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
    public function subidoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'subido_por');
    }

    public function esTemporal(): bool
    {
        return $this->sujeto_id === null;
    }

    public function esArchivo(): bool
    {
        return $this->tipo === TipoAdjunto::Archivo;
    }

    public function esDe(User $user): bool
    {
        return $this->subido_por === $user->getKey();
    }

    public function perteneceA(Model $sujeto): bool
    {
        return $this->sujeto_type === $sujeto->getMorphClass() && $this->sujeto_id === $sujeto->getKey();
    }
}
