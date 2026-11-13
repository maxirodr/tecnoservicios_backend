<?php

declare(strict_types=1);

namespace App\Modules\Identidad\Application\Permisos;

use App\Modules\Identidad\Application\Usuarios\GuardaAdministradores;
use App\Modules\Identidad\Domain\Enums\Alcance;
use App\Modules\Identidad\Domain\Enums\Nivel;
use App\Modules\Identidad\Domain\Models\Funcion;
use App\Modules\Identidad\Domain\Models\Permiso;
use App\Modules\Identidad\Domain\Models\Sector;
use App\Shared\Actividad\RegistroActividad;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * F-60 / HU-008: cambia las celdas recibidas, todo o nada. Valida RN-IDE-38 (editar y "solo sus
 * obras" solo donde la función los admite) y RN-IDE-25 (siempre queda alguien que administre
 * usuarios). Cada celda que cambia queda en `actividades` con antes → después (RN-IDE-37).
 */
final class CambiarCeldasAction
{
    public const NO_ADMITE_EDITAR = 'Esta función no admite Editar.';

    public const NO_ADMITE_ALCANCE = "Esta función no admite 'solo sus obras'.";

    public const SECTOR_NO_DISPONIBLE = 'El sector no existe o está archivado.';

    public function __construct(
        private readonly GuardaAdministradores $administradores,
        private readonly RegistroActividad $actividad,
    ) {}

    /**
     * @param  list<array{sector_id: int, funcion: string, nivel: string, alcance: string}>  $celdas
     */
    public function handle(array $celdas): void
    {
        $funciones = Funcion::query()->get()->keyBy('clave');
        $sectoresActivos = array_values(Sector::query()->where('activo', true)->get()->map(fn (Sector $sector): int => $sector->id)->all());

        $this->validar($celdas, $funciones->all(), $sectoresActivos);

        DB::transaction(function () use ($celdas, $funciones): void {
            $this->administradores->bloquear();

            foreach ($celdas as $celda) {
                /** @var Funcion $funcion */
                $funcion = $funciones->get($celda['funcion']);
                $this->aplicar($celda['sector_id'], $funcion, Nivel::from($celda['nivel']), Alcance::from($celda['alcance']));
            }

            $this->administradores->asegurar('celdas');
        });
    }

    /**
     * @param  list<array{sector_id: int, funcion: string, nivel: string, alcance: string}>  $celdas
     * @param  array<string, Funcion>  $funciones
     * @param  list<int>  $sectoresActivos
     */
    private function validar(array $celdas, array $funciones, array $sectoresActivos): void
    {
        $errores = [];

        foreach ($celdas as $indice => $celda) {
            $funcion = $funciones[$celda['funcion']];

            if (! in_array($celda['sector_id'], $sectoresActivos, true)) {
                $errores["celdas.{$indice}.sector_id"] = [self::SECTOR_NO_DISPONIBLE];
            }

            if (! $funcion->admiteNivel(Nivel::from($celda['nivel']))) {
                $errores["celdas.{$indice}.nivel"] = [self::NO_ADMITE_EDITAR];
            }

            if (! $funcion->admiteAlcance(Alcance::from($celda['alcance']))) {
                $errores["celdas.{$indice}.alcance"] = [self::NO_ADMITE_ALCANCE];
            }
        }

        if ($errores !== []) {
            throw ValidationException::withMessages($errores);
        }
    }

    private function aplicar(int $sectorId, Funcion $funcion, Nivel $nivel, Alcance $alcance): void
    {
        $permiso = Permiso::query()->firstOrNew(['sector_id' => $sectorId, 'funcion_id' => $funcion->id]);
        $antes = ['nivel' => $permiso->exists ? $permiso->nivel->value : null, 'alcance' => $permiso->exists ? $permiso->alcance->value : null];

        $permiso->nivel = $nivel;
        $permiso->alcance = $alcance;

        if ($permiso->exists && ! $permiso->isDirty()) {
            return;
        }

        $permiso->save();

        $this->actividad->registrar($permiso, 'editado', [
            'nivel' => ['antes' => $antes['nivel'], 'despues' => $nivel->value],
            'alcance' => ['antes' => $antes['alcance'], 'despues' => $alcance->value],
        ]);
    }
}
