<?php

declare(strict_types=1);

namespace Tests\Feature\Rendicion\Support;

use App\Modules\Identidad\Domain\Models\Funcion;
use App\Modules\Identidad\Domain\Models\Permiso;
use App\Modules\Identidad\Domain\Models\Sector;
use App\Modules\Identidad\Domain\Models\User;
use App\Modules\Obras\Domain\Enums\ModoCarga;
use App\Modules\Obras\Domain\Enums\ObraEstado;
use App\Modules\Obras\Domain\Models\Obra;
use App\Modules\Obras\Domain\Models\Presupuesto;
use App\Modules\Obras\Domain\Models\PresupuestoMonto;
use App\Modules\Rendicion\Domain\Models\Material;
use App\Modules\Rendicion\Domain\Models\Rubro;
use App\Modules\Rendicion\Domain\TextoComparable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Base de los tests de Rendicion: MySQL real con la matriz inicial (identidad.md §6.3), Obras real y Gastos
 * en memoria (`GastadoDeObrasFalso`, el contrato de rendicion.md §12.4.1). Personas y obras ficticias.
 */
abstract class RendicionTestCase extends TestCase
{
    use RefreshDatabase;

    protected GastadoDeObrasFalso $gastado;

    protected function setUp(): void
    {
        parent::setUp();

        $this->gastado = GastadoDeObrasFalso::registrar($this->app);
    }

    /**
     * @param  array<string, mixed>  $atributos
     */
    protected function usuarioDe(string $sector, array $atributos = []): User
    {
        return User::factory()->enSector($sector)->create($atributos);
    }

    /** Ingeniería de proyectos: Rendición, Gastos y Control por cantidad V·P; Monto de obra sin acceso. */
    protected function laura(): User
    {
        return $this->usuarioDe('Ingeniería de proyectos', ['nombre' => 'Laura']);
    }

    /** Jefatura de Proyectos: Rendición y Control por cantidad V·T; Monto de obra sin acceso. */
    protected function hernan(): User
    {
        return $this->usuarioDe('Jefatura de Proyectos', ['nombre' => 'Hernán']);
    }

    /** Jefatura de Administración: todo E·T (Rendición, Monto, Control por cantidad, Gastos). */
    protected function diego(): User
    {
        return $this->usuarioDe('Jefatura de Administración', ['nombre' => 'Diego']);
    }

    /** Gerencia de Operaciones: Rendición, Monto y Control por cantidad V·T. */
    protected function operaciones(): User
    {
        return $this->usuarioDe('Gerencia de Operaciones', ['nombre' => 'Mariela']);
    }

    /** Calidad: sin Rendición, Monto, Gastos ni Control por cantidad. */
    protected function calidad(): User
    {
        return $this->usuarioDe('Calidad', ['nombre' => 'Sofía']);
    }

    protected function obraDe(User $responsable, ObraEstado $estado = ObraEstado::EnEjecucion, ?string $numero = null): Obra
    {
        $obra = Obra::factory()->deResponsable($responsable)->enEstado($estado)->create();

        if ($numero !== null) {
            $this->principal($obra)->update(['numero' => $numero]);
        }

        return $obra;
    }

    protected function principal(Obra $obra): Presupuesto
    {
        return Presupuesto::query()->where('obra_id', $obra->id)->where('es_principal', true)->sole();
    }

    protected function adicional(Obra $obra, string $numero): Presupuesto
    {
        return Presupuesto::query()->create(['obra_id' => $obra->id, 'numero' => $numero, 'es_principal' => false, 'created_by' => $obra->created_by]);
    }

    /**
     * Lo que dejaría W-13 (RN-OBR-31): por rubro (los vacíos no tienen fila) o solo total.
     *
     * @param  array<string, string>  $porRubro
     */
    protected function porRubro(Presupuesto $presupuesto, array $porRubro, ?string $montoObra = null): void
    {
        foreach ($porRubro as $clave => $monto) {
            PresupuestoMonto::query()->create(['presupuesto_id' => $presupuesto->id, 'rubro_id' => $this->rubroId($clave), 'monto' => $monto]);
        }

        $presupuesto->update(['modo_carga' => ModoCarga::PorRubro, 'monto_obra' => $montoObra]);
    }

    protected function soloTotal(Presupuesto $presupuesto, string $total, ?string $montoObra = null): void
    {
        PresupuestoMonto::query()->create(['presupuesto_id' => $presupuesto->id, 'rubro_id' => null, 'monto' => $total]);
        $presupuesto->update(['modo_carga' => ModoCarga::SoloTotal, 'monto_obra' => $montoObra]);
    }

    protected function material(Obra $obra, string $nombre, string $unidad, ?string $cantidad, ?string $precio = null, ?Presupuesto $presupuesto = null): Material
    {
        return Material::query()->create([
            'obra_id' => $obra->id, 'presupuesto_id' => $presupuesto?->id, 'nombre' => $nombre, 'nombre_clave' => TextoComparable::de($nombre),
            'unidad' => $unidad, 'cantidad_presupuestada' => $cantidad, 'precio_presupuestado' => $precio,
            'fuera_de_presupuesto' => $cantidad === null, 'created_by' => $obra->created_by, 'updated_by' => $obra->created_by,
        ]);
    }

    protected function celda(string $sector, string $funcion, string $nivel, string $alcance = 'todas'): void
    {
        Permiso::query()
            ->where('sector_id', Sector::query()->where('nombre', $sector)->valueOrFail('id'))
            ->where('funcion_id', Funcion::query()->where('clave', $funcion)->valueOrFail('id'))
            ->update(['nivel' => $nivel, 'alcance' => $alcance]);
    }

    protected function celdaSinAcceso(string $sector, string $funcion): void
    {
        $this->celda($sector, $funcion, 'sin_acceso');
    }

    protected function rubroId(string $clave): int
    {
        return (int) Rubro::query()->where('clave', $clave)->valueOrFail('id');
    }
}
