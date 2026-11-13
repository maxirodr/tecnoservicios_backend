<?php

declare(strict_types=1);

namespace App\Modules\Rendicion\Domain\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Rubro de la lista cerrada de su rendición (obras.md §4.6). Catálogo por migración: no se edita
 * desde pantallas en la Etapa 1. Otros módulos lo leen por FK (`rubro_habitual_id`, `rubro_id`).
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
    public $timestamps = false;

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
