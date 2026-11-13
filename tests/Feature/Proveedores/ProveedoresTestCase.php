<?php

declare(strict_types=1);

namespace Tests\Feature\Proveedores;

use App\Modules\Identidad\Domain\Models\Funcion;
use App\Modules\Identidad\Domain\Models\Permiso;
use App\Modules\Identidad\Domain\Models\Sector;
use App\Modules\Identidad\Domain\Models\User;
use App\Modules\Proveedores\Domain\Models\Proveedor;
use App\Shared\Actividad\Actividad;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Base de los tests de Proveedores: MySQL real con las migraciones (sectores, matriz §6.3, rubros y el
 * proveedor genérico). Autentica con `actingAs` (sin contraseñas). Todos los datos son ficticios.
 *
 * Proveedores en la configuración inicial: Editar Gerencia y Jefatura de Administración y Compras;
 * Ver Dirección, Gerencia de Operaciones y Jefatura de Proyectos; el resto Sin acceso.
 */
abstract class ProveedoresTestCase extends TestCase
{
    use RefreshDatabase;

    protected const SECTOR_EDITA = 'Compras';

    protected const SECTOR_VE = 'Dirección';

    protected const SECTOR_SIN_ACCESO = 'Ingeniería de proyectos';

    protected function usuarioDe(string $sector): User
    {
        return User::factory()->enSector($sector)->create();
    }

    protected function quienEdita(): User
    {
        return $this->usuarioDe(self::SECTOR_EDITA);
    }

    protected function quienVe(): User
    {
        return $this->usuarioDe(self::SECTOR_VE);
    }

    protected function sinAcceso(): User
    {
        return $this->usuarioDe(self::SECTOR_SIN_ACCESO);
    }

    protected function proveedor(string $nombre, ?string $cuit = null): Proveedor
    {
        return Proveedor::factory()->llamado($nombre)->create(['cuit' => $cuit]);
    }

    /** Fija una celda de la matriz directo en la base (escenarios que la configuración inicial no trae). */
    protected function celda(string $sector, string $funcion, string $nivel, string $alcance = 'todas'): void
    {
        Permiso::query()
            ->where('sector_id', Sector::query()->where('nombre', $sector)->valueOrFail('id'))
            ->where('funcion_id', Funcion::query()->where('clave', $funcion)->valueOrFail('id'))
            ->update(['nivel' => $nivel, 'alcance' => $alcance]);
    }

    protected function actividadDe(Proveedor $proveedor, string $accion): ?Actividad
    {
        return Actividad::query()
            ->where('sujeto_type', $proveedor->getMorphClass())
            ->where('sujeto_id', $proveedor->id)
            ->where('accion', $accion)
            ->first();
    }
}
