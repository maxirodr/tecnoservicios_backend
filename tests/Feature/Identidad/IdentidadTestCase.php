<?php

declare(strict_types=1);

namespace Tests\Feature\Identidad;

use App\Modules\Identidad\Domain\Models\Funcion;
use App\Modules\Identidad\Domain\Models\Permiso;
use App\Modules\Identidad\Domain\Models\Sector;
use App\Modules\Identidad\Domain\Models\User;
use App\Shared\Actividad\RegistroActividad;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\RegistroActividadEspia;
use Tests\TestCase;

/**
 * Base de los tests de Identidad: MySQL real (`tecnoservicios_testing`) con las migraciones, que
 * traen los 11 sectores, las 12 funciones y la matriz inicial (§6.3).
 */
abstract class IdentidadTestCase extends TestCase
{
    use RefreshDatabase;

    protected const ORIGEN_FRONT = 'http://localhost:5310';

    protected RegistroActividadEspia $actividad;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actividad = new RegistroActividadEspia;
        $this->app->instance(RegistroActividad::class, $this->actividad);
    }

    /**
     * Usuario activo con contraseña en un sector de la configuración inicial.
     *
     * @param  array<string, mixed>  $atributos
     */
    protected function usuarioDe(string $sector, array $atributos = []): User
    {
        return User::factory()->enSector($sector)->create($atributos);
    }

    /** Gerencia de Administración: Usuarios y permisos = Editar y Plazos = Editar. */
    protected function administrador(): User
    {
        return $this->usuarioDe('Gerencia de Administración', ['nombre' => 'Diego']);
    }

    /** Fija una celda de la matriz directo en la base (para escenarios que la configuración inicial no trae). */
    protected function celda(string $sector, string $funcion, string $nivel): void
    {
        Permiso::query()
            ->where('sector_id', Sector::query()->where('nombre', $sector)->valueOrFail('id'))
            ->where('funcion_id', Funcion::query()->where('clave', $funcion)->valueOrFail('id'))
            ->update(['nivel' => $nivel]);
    }

    /**
     * Payload de `POST /api/usuarios` para Pablo (ficticio) en Ingeniería de proyectos.
     *
     * @param  array<string, mixed>  $cambios
     * @return array<string, mixed>
     */
    protected function datosDeAlta(array $cambios = []): array
    {
        return [
            'nombre' => 'Pablo',
            'email' => 'pablo@empresa-ejemplo.com',
            'sector_id' => Sector::query()->where('nombre', 'Ingeniería de proyectos')->valueOrFail('id'),
            'telefono' => null,
            ...$cambios,
        ];
    }

    /** Pedidos con Origin del front: Sanctum los trata como SPA (sesión + cookies). */
    protected function desdeElFront(): static
    {
        return $this->withHeaders(['Origin' => self::ORIGEN_FRONT, 'Referer' => self::ORIGEN_FRONT.'/']);
    }
}
