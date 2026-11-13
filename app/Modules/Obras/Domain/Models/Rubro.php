<?php

declare(strict_types=1);

namespace App\Modules\Obras\Domain\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Rubro de la rendición (lista cerrada, cap. 9, obras.md §4.6). No se edita en la Etapa 1:
 * lo carga la migración del catálogo. EPP no se presupuesta y en el resumen suma en INSUMOS.
 *
 * @property int $id
 * @property string $clave
 * @property string $nombre
 * @property int $orden
 * @property int|null $resume_en_rubro_id
 * @property bool $se_presupuesta
 * @property bool $carga_por_periodo
 */
class Rubro extends Model
{
    protected $table = 'rubros';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'orden' => 'integer',
            'resume_en_rubro_id' => 'integer',
            'se_presupuesta' => 'boolean',
            'carga_por_periodo' => 'boolean',
        ];
    }
}
