<?php

declare(strict_types=1);

namespace App\Modules\Proveedores\Domain\Models;

use App\Modules\Proveedores\Domain\NombreProveedor;
use App\Modules\Rendicion\Domain\Models\Rubro;
use Database\Factories\ProveedorFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Proveedor de la base única (proveedores.md §4.1). Sin saldos ni cuenta corriente (D-040). No se
 * borra ni se fusiona (RN-PRV-11). Las formas normalizadas del nombre se derivan al guardar.
 *
 * @property int $id
 * @property string $nombre
 * @property string $nombre_clave
 * @property string $nombre_normalizado
 * @property string $nombre_busqueda
 * @property string|null $cuit 11 dígitos
 * @property string|null $telefono
 * @property string|null $email
 * @property int|null $rubro_habitual_id
 * @property bool $es_generico
 * @property bool $activo
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property-read Rubro|null $rubroHabitual
 */
#[Fillable(['nombre', 'cuit', 'telefono', 'email', 'rubro_habitual_id', 'created_by', 'updated_by'])]
#[UseFactory(ProveedorFactory::class)]
class Proveedor extends Model
{
    /** @use HasFactory<ProveedorFactory> */
    use HasFactory;

    protected $table = 'proveedores';

    /** @var array<string, mixed> */
    protected $attributes = [
        'es_generico' => false,
        'activo' => true,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'rubro_habitual_id' => 'integer',
            'es_generico' => 'boolean',
            'activo' => 'boolean',
            'created_by' => 'integer',
            'updated_by' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Proveedor $proveedor): void {
            $proveedor->nombre_busqueda = NombreProveedor::busqueda($proveedor->nombre);
            $proveedor->nombre_clave = NombreProveedor::clave($proveedor->nombre);
            $proveedor->nombre_normalizado = NombreProveedor::normalizado($proveedor->nombre);
        });
    }

    /**
     * @return BelongsTo<Rubro, $this>
     */
    public function rubroHabitual(): BelongsTo
    {
        return $this->belongsTo(Rubro::class, 'rubro_habitual_id');
    }
}
