<?php

declare(strict_types=1);

namespace Tests\Feature\Identidad;

use App\Modules\Identidad\Application\Usuarios\GuardaAdministradores;
use App\Modules\Identidad\Domain\Enums\ClaveFuncion;
use App\Modules\Identidad\Domain\Models\Sector;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * SEG-12 (RN-IDE-25): dos bajas simultáneas de los dos últimos administradores no pueden dejar cero.
 * Los cambios que tocan administradores toman el mismo cerrojo antes de cambiar nada, así corren en fila.
 */
final class GuardaAdministradoresConcurrenciaTest extends IdentidadTestCase
{
    private const OTRA_CONEXION = 'mysql_concurrente';

    public function test_mientras_un_cambio_tiene_el_cerrojo_otro_no_puede_tomarlo(): void
    {
        app(GuardaAdministradores::class)->bloquear();

        config(['database.connections.'.self::OTRA_CONEXION => config('database.connections.mysql')]);
        $otra = DB::connection(self::OTRA_CONEXION);
        $otra->statement('SET SESSION innodb_lock_wait_timeout = 1');

        try {
            $otra->transaction(function () use ($otra): void {
                $otra->table('funciones')->where('clave', ClaveFuncion::UsuariosPermisos->value)->lockForUpdate()->first();
            });
            $this->fail('La otra conexión tomó el cerrojo mientras lo tenía la primera.');
        } catch (QueryException $e) {
            $this->assertStringContainsString('Lock wait timeout', $e->getMessage());
        } finally {
            DB::purge(self::OTRA_CONEXION);
        }
    }

    public function test_dar_de_baja_a_un_administrador_toma_el_cerrojo_antes_de_cambiarlo(): void
    {
        $diego = $this->administrador();
        $eva = $this->usuarioDe('Gerencia de Administración', ['nombre' => 'Eva']);

        $consultas = $this->consultasDe(fn () => $this->actingAs($diego)
            ->patchJson("/api/usuarios/{$eva->id}", ['activo' => false])->assertOk());

        $this->assertCerrojoAntesDelCambio($consultas, 'update `users`');
    }

    public function test_cambiar_la_matriz_toma_el_cerrojo_antes_de_cambiarla(): void
    {
        $compras = Sector::query()->where('nombre', 'Compras')->valueOrFail('id');

        $consultas = $this->consultasDe(fn () => $this->actingAs($this->administrador())->putJson('/api/permisos', ['celdas' => [
            ['sector_id' => $compras, 'funcion' => 'proveedores', 'nivel' => 'ver', 'alcance' => 'todas'],
        ]])->assertOk());

        $this->assertCerrojoAntesDelCambio($consultas, 'update `permisos`');
    }

    /**
     * @return list<string>
     */
    private function consultasDe(callable $pedido): array
    {
        DB::flushQueryLog();
        DB::enableQueryLog();
        $pedido();
        DB::disableQueryLog();

        return array_map(static fn (array $query): string => (string) $query['query'], DB::getQueryLog());
    }

    /**
     * @param  list<string>  $consultas
     */
    private function assertCerrojoAntesDelCambio(array $consultas, string $cambio): void
    {
        $cerrojo = $this->posicion($consultas, static fn (string $sql): bool => str_contains($sql, 'from `funciones`') && str_contains($sql, 'for update'));
        $escritura = $this->posicion($consultas, static fn (string $sql): bool => str_starts_with($sql, $cambio));
        $control = $this->posicion($consultas, static fn (string $sql): bool => str_contains($sql, 'lock in share mode'));

        $this->assertNotNull($cerrojo, 'Toma el cerrojo');
        $this->assertNotNull($escritura, "Hace el cambio ({$cambio})");
        $this->assertNotNull($control, 'Controla con lectura bloqueante');
        $this->assertLessThan($escritura, $cerrojo, 'El cerrojo va antes del cambio');
        $this->assertGreaterThan($escritura, $control, 'El control va después del cambio');
    }

    /**
     * @param  list<string>  $consultas
     * @param  callable(string): bool  $criterio
     */
    private function posicion(array $consultas, callable $criterio): ?int
    {
        foreach ($consultas as $indice => $sql) {
            if ($criterio($sql)) {
                return $indice;
            }
        }

        return null;
    }
}
