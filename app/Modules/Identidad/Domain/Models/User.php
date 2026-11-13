<?php

declare(strict_types=1);

namespace App\Modules\Identidad\Domain\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * Usuario del sistema (identidad.md §4.1). No tiene rol propio: lo que puede hacer sale de la celda
 * de su sector (F-60). No se borra: la baja es `activo = false` (RN-IDE-20). No tiene contraseña: entra
 * con un código por mail (ADR-015).
 *
 * @property int $id
 * @property string $nombre
 * @property string $email
 * @property string|null $telefono
 * @property int $sector_id
 * @property int|null $jefe_id
 * @property bool $activo
 * @property-read Sector $sector
 * @property-read User|null $jefe
 */
#[UseFactory(UserFactory::class)]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $table = 'users';

    protected $fillable = ['nombre', 'email', 'telefono', 'sector_id', 'jefe_id', 'activo'];

    protected $hidden = ['remember_token'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
            'sector_id' => 'integer',
            'jefe_id' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Sector, $this>
     */
    public function sector(): BelongsTo
    {
        return $this->belongsTo(Sector::class, 'sector_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function jefe(): BelongsTo
    {
        return $this->belongsTo(self::class, 'jefe_id');
    }

    /**
     * Personas que dependen directamente de este usuario.
     *
     * @return HasMany<User, $this>
     */
    public function dependientes(): HasMany
    {
        return $this->hasMany(self::class, 'jefe_id');
    }

    /**
     * Sin contraseña (ADR-015): la sesión no se ata a un hash de contraseña. Sanctum guarda este valor
     * en la sesión y lo compara en cada pedido; es fijo, así que no corta sesiones por sí solo.
     */
    public function getAuthPassword(): string
    {
        return '';
    }
}
