<?php

declare(strict_types=1);

namespace App\Modules\Identidad\Domain\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Código de ingreso por mail (ADR-015, RN-IDE-01…07): 6 dígitos, vence a los 10 minutos, 5 intentos
 * fallidos lo invalidan, sirve una vez. Uno vigente por usuario. Solo se guarda el hash.
 *
 * @property int $id
 * @property int $user_id
 * @property string $codigo_hash
 * @property int $intentos_fallidos
 * @property Carbon $vence_at
 * @property-read User $user
 */
final class CodigoIngreso extends Model
{
    public const DIGITOS = 6;

    public const VIGENCIA_MINUTOS = 10;

    public const INTENTOS_MAXIMOS = 5;

    protected $table = 'codigos_ingreso';

    protected $fillable = ['user_id', 'codigo_hash', 'intentos_fallidos', 'vence_at'];

    protected $hidden = ['codigo_hash'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'intentos_fallidos' => 'integer',
            'vence_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function vencido(): bool
    {
        return $this->vence_at->isPast();
    }
}
