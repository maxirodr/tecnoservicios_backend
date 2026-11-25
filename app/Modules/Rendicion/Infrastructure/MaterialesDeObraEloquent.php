<?php

declare(strict_types=1);

namespace App\Modules\Rendicion\Infrastructure;

use App\Modules\Gastos\Contracts\FiltroRenglones;
use App\Modules\Gastos\Contracts\GastadoDeObras;
use App\Modules\Rendicion\Application\Materiales\AgregarMaterialInicialAction;
use App\Modules\Rendicion\Contracts\MaterialAgregado;
use App\Modules\Rendicion\Contracts\MaterialInicial;
use App\Modules\Rendicion\Contracts\MaterialesDeObra;
use App\Modules\Rendicion\Contracts\MaterialOpcion;
use App\Modules\Rendicion\Domain\Calculo\Decimal;
use App\Modules\Rendicion\Domain\Materiales\RetiradoDeMaterial;
use App\Modules\Rendicion\Domain\Models\Material;
use App\Modules\Rendicion\Domain\TextoComparable;
use Illuminate\Validation\ValidationException;

/**
 * La lista real de W-48 para Gastos (rendicion.md §12.4.3): opciones del
 * selector (la lista primero, máx. 30, con lo que resta llevar y el precio presupuestado), pertenencia a la
 * obra y el alta de lo "fuera de presupuesto" dentro de la transacción de Gastos (RN-REN-49).
 */
final readonly class MaterialesDeObraEloquent implements MaterialesDeObra
{
    public const MAXIMO_OPCIONES = 30;

    public const NOMBRE_MAXIMO = 150;

    public const UNIDAD_MAXIMA = 20;

    public function __construct(
        private GastadoDeObras $gastado,
        private AgregarMaterialInicialAction $inicial,
    ) {}

    public function opciones(int $obraId, ?string $texto): array
    {
        $buscar = $texto === null ? '' : TextoComparable::de($texto);
        $materiales = Material::query()
            ->where('obra_id', $obraId)
            ->when($buscar !== '', static fn ($q) => $q->where('nombre_clave', 'like', '%'.addcslashes($buscar, '%_\\').'%'))
            ->orderBy('fuera_de_presupuesto')
            ->orderBy('nombre_clave')
            ->limit(self::MAXIMO_OPCIONES)
            ->get();

        $retirado = $materiales->isEmpty()
            ? []
            : RetiradoDeMaterial::porMaterial($this->gastado->renglonesDeObra($obraId, new FiltroRenglones(soloConMaterial: true)));

        return array_values($materiales->map(static fn (Material $m): MaterialOpcion => new MaterialOpcion(
            $m->id,
            $m->nombre,
            $m->unidad,
            $m->fuera_de_presupuesto,
            $m->cantidad_presupuestada === null
                ? null
                : Decimal::resta($m->cantidad_presupuestada, ($retirado[$m->id] ?? RetiradoDeMaterial::nada())->cantidad, Decimal::CANTIDAD),
            $m->precio_presupuestado === null ? null : Decimal::numero($m->precio_presupuestado),
        ))->all());
    }

    public function perteneceAObra(int $materialId, int $obraId): bool
    {
        return Material::query()->whereKey($materialId)->where('obra_id', $obraId)->exists();
    }

    /**
     * @throws ValidationException si falta el nombre o la unidad, o son demasiado largos
     */
    public function crearFueraDePresupuesto(int $obraId, string $nombre, string $unidad, int $userId): int
    {
        $nombre = trim($nombre);
        $unidad = trim($unidad);
        $errores = array_filter([
            'nombre' => $nombre === '' ? 'Escribí el material.' : (mb_strlen($nombre) > self::NOMBRE_MAXIMO ? 'Es demasiado largo (máximo 150 caracteres).' : null),
            'unidad' => $unidad === '' ? 'Escribí la unidad.' : (mb_strlen($unidad) > self::UNIDAD_MAXIMA ? 'Es demasiado largo (máximo 20 caracteres).' : null),
        ]);

        if ($errores !== []) {
            throw ValidationException::withMessages(array_map(static fn (string $m): array => [$m], $errores));
        }

        return Material::query()->createOrFirst(
            ['obra_id' => $obraId, 'nombre_clave' => TextoComparable::claveDeMaterial($nombre)],
            ['nombre' => $nombre, 'unidad' => $unidad, 'fuera_de_presupuesto' => true, 'created_by' => $userId, 'updated_by' => $userId],
        )->id;
    }

    public function agregarALaLista(int $actorId, int $obraId, MaterialInicial $material): MaterialAgregado
    {
        return $this->inicial->handle($actorId, $obraId, $material);
    }
}
