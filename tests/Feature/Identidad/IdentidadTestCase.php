<?php

declare(strict_types=1);

namespace Tests\Feature\Identidad;

use App\Modules\Identidad\Domain\Models\Funcion;
use App\Modules\Identidad\Domain\Models\Permiso;
use App\Modules\Identidad\Domain\Models\Sector;
use App\Modules\Identidad\Domain\Models\User;
use App\Modules\Identidad\Infrastructure\Mail\CodigoIngresoMail;
use App\Shared\Actividad\Actividad;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Base de los tests de Identidad: MySQL real (`tecnoservicios_testing`) con las migraciones, que
 * traen los 11 sectores, las 12 funciones y la matriz inicial (§6.3). El registro de actividad es
 * el real (tabla `actividades`).
 */
abstract class IdentidadTestCase extends TestCase
{
    use RefreshDatabase;

    protected const ORIGEN_FRONT = 'http://localhost:5310';

    /**
     * Usuario activo en un sector de la configuración inicial.
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

    /**
     * Filas de `actividades` de un tipo de sujeto y una acción, en orden de alta.
     *
     * @param  class-string<Model>  $tipo
     * @return list<Actividad>
     */
    protected function actividades(string $tipo, string $accion): array
    {
        return array_values(Actividad::query()
            ->where('sujeto_type', (new $tipo)->getMorphClass())
            ->where('accion', $accion)
            ->orderBy('id')
            ->get()
            ->all());
    }

    /** Pide un código por la API (como el front) y devuelve el que salió en el mail, o null si no salió ninguno. */
    protected function pedirCodigo(string $email): ?string
    {
        Mail::fake();
        $this->desdeElFront()->postJson('/api/auth/pedir-codigo', ['email' => $email])->assertOk();

        $mail = Mail::queued(CodigoIngresoMail::class)->last();

        return $mail instanceof CodigoIngresoMail ? $mail->codigo : null;
    }

    /** Pedidos con Origin del front: Sanctum los trata como SPA (sesión + cookies). */
    protected function desdeElFront(): static
    {
        return $this->withHeaders(['Origin' => self::ORIGEN_FRONT, 'Referer' => self::ORIGEN_FRONT.'/']);
    }
}
