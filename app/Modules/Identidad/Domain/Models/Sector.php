<?php

declare(strict_types=1);

namespace App\Modules\Identidad\Domain\Models;

use App\Modules\Identidad\Domain\NombreClave;
use Database\Factories\SectorFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Sector del organigrama (W-40, identidad.md §4.2). "Sacar" = borrar si nunca se usó o archivar
 * (`activo = false`) si tiene historia (RN-IDE-31).
 *
 * @property int $id
 * @property string $nombre
 * @property string $nombre_clave
 * @property bool $es_direccion
 * @property bool $activo
 */
#[Fillable(['nombre', 'es_direccion', 'activo'])]
#[UseFactory(SectorFactory::class)]
class Sector extends Model
{
    /** @use HasFactory<SectorFactory> */
    use HasFactory;

    protected $table = 'sectores';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'es_direccion' => 'boolean',
            'activo' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Sector $sector): void {
            $sector->nombre_clave = NombreClave::de($sector->nombre);
        });
    }

    /**
     * @return HasMany<User, $this>
     */
    public function usuarios(): HasMany
    {
        return $this->hasMany(User::class, 'sector_id');
    }

    /**
     * @return HasMany<Permiso, $this>
     */
    public function permisos(): HasMany
    {
        return $this->hasMany(Permiso::class, 'sector_id');
    }
}
