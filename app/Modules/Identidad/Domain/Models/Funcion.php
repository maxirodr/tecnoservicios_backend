<?php

declare(strict_types=1);

namespace App\Modules\Identidad\Domain\Models;

use App\Modules\Identidad\Domain\Enums\Alcance;
use App\Modules\Identidad\Domain\Enums\Nivel;
use Illuminate\Database\Eloquent\Attributes\WithoutTimestamps;
use Illuminate\Database\Eloquent\Model;

/**
 * Fila de la tabla de permisos (catálogo cerrado, identidad.md §6.1, cargado por migración).
 *
 * @property int $id
 * @property string $clave
 * @property string $nombre
 * @property bool $admite_editar
 * @property bool $admite_alcance
 * @property int $orden
 */
#[WithoutTimestamps]
class Funcion extends Model
{
    protected $table = 'funciones';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'admite_editar' => 'boolean',
            'admite_alcance' => 'boolean',
            'orden' => 'integer',
        ];
    }

    /** RN-IDE-38: "editar" y "solo sus obras" solo donde la función los admite. */
    public function admiteNivel(Nivel $nivel): bool
    {
        return $nivel !== Nivel::Editar || $this->admite_editar;
    }

    public function admiteAlcance(Alcance $alcance): bool
    {
        return $alcance !== Alcance::Propias || $this->admite_alcance;
    }
}
