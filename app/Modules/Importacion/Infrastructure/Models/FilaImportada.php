<?php

declare(strict_types=1);

namespace App\Modules\Importacion\Infrastructure\Models;

use App\Modules\Importacion\Domain\Resultado;
use Illuminate\Database\Eloquent\Model;

/**
 * Qué pasó con cada obra, gastado, retirado, tarea o documento en una corrida (`importacion_filas`, §11.3).
 * La Clave de una tarea importada en una corrida confirmada es su idempotencia (RN-IMP-08).
 *
 * @property int $id
 * @property int $corrida_id
 * @property string $hoja
 * @property string $clave
 * @property Resultado $resultado
 * @property string|null $sujeto_type
 * @property int|null $sujeto_id
 */
final class FilaImportada extends Model
{
    public $timestamps = false;

    protected $table = 'importacion_filas';

    protected $fillable = ['corrida_id', 'hoja', 'clave', 'resultado', 'sujeto_type', 'sujeto_id'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'corrida_id' => 'integer',
            'resultado' => Resultado::class,
            'sujeto_id' => 'integer',
        ];
    }
}
