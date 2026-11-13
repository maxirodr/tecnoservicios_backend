<?php

declare(strict_types=1);

namespace Tests\Feature\Identidad;

use App\Modules\Identidad\Application\Auth\IngresarAction;
use App\Modules\Identidad\Domain\Models\CodigoIngreso;
use App\Modules\Identidad\IdentidadServiceProvider;
use Illuminate\Http\JsonResponse;
use Illuminate\Testing\TestResponse;

/** F-01 · W-16 · HU-001 (ADR-015, RN-IDE-01, 03…06): entrar con el mail y el código. */
final class IngresoTest extends IdentidadTestCase
{
    private const MAIL = 'laura@empresa-ejemplo.com';

    public function test_ingresa_con_su_mail_y_el_codigo_que_le_llego(): void
    {
        $laura = $this->usuarioDe('Ingeniería de proyectos', ['nombre' => 'Laura', 'email' => self::MAIL]);
        $codigo = (string) $this->pedirCodigo(self::MAIL);

        $response = $this->ingresar(self::MAIL, $codigo)
            ->assertOk()
            ->assertJsonPath('data.id', $laura->id)
            ->assertJsonPath('data.sector.nombre', 'Ingeniería de proyectos')
            ->assertJsonPath('message', null)
            ->assertJsonPath('errors', null)
            ->assertJsonMissingPath('data.password')
            ->assertJsonMissingPath('data.eligio_contrasena');

        $this->assertAuthenticatedAs($laura, 'web');
        $this->assertSame($this->getJson('/api/me')->json('data'), $response->json('data'), 'Mismo payload que GET /api/me');
    }

    public function test_mail_se_compara_sin_mayusculas_ni_espacios(): void
    {
        $laura = $this->usuarioDe('Compras', ['email' => self::MAIL]);
        $codigo = (string) $this->pedirCodigo(self::MAIL);

        $this->ingresar(' LAURA@empresa-ejemplo.com ', $codigo)->assertOk()->assertJsonPath('data.id', $laura->id);
    }

    public function test_codigo_incorrecto_o_mail_inexistente_dan_el_mismo_mensaje(): void
    {
        $this->usuarioDe('Compras', ['email' => self::MAIL]);
        $codigo = (string) $this->pedirCodigo(self::MAIL);

        $this->noEntra($this->ingresar(self::MAIL, $this->otroCodigo($codigo)));
        $this->noEntra($this->ingresar('nadie@empresa-ejemplo.com', $codigo));
        $this->noEntra($this->ingresar('otra@empresa-ejemplo.com', '123456'));
    }

    public function test_sin_pedir_codigo_no_se_entra(): void
    {
        $this->usuarioDe('Compras', ['email' => self::MAIL]);

        $this->noEntra($this->ingresar(self::MAIL, '000000'));
    }

    public function test_codigo_con_otra_forma_da_el_mismo_mensaje_y_no_gasta_un_intento(): void
    {
        $this->usuarioDe('Compras', ['email' => self::MAIL]);
        $codigo = (string) $this->pedirCodigo(self::MAIL);

        foreach (['12345', '1234567', 'abcdef', ''] as $malFormado) {
            $this->noEntra($this->ingresar(self::MAIL, $malFormado));
        }

        $this->assertSame(0, CodigoIngreso::query()->firstOrFail()->intentos_fallidos);
        $this->ingresar(self::MAIL, $codigo)->assertOk();
    }

    public function test_codigo_vale_durante_10_minutos(): void
    {
        $this->usuarioDe('Compras', ['email' => self::MAIL]);
        $codigo = (string) $this->pedirCodigo(self::MAIL);

        $this->travel(CodigoIngreso::VIGENCIA_MINUTOS * 60 - 1)->seconds();

        $this->ingresar(self::MAIL, $codigo)->assertOk();
    }

    public function test_codigo_vencido_no_deja_ingresar(): void
    {
        $this->usuarioDe('Compras', ['email' => self::MAIL]);
        $codigo = (string) $this->pedirCodigo(self::MAIL);

        $this->travel(CodigoIngreso::VIGENCIA_MINUTOS * 60 + 1)->seconds();

        $this->noEntra($this->ingresar(self::MAIL, $codigo));
    }

    public function test_codigo_sirve_una_sola_vez(): void
    {
        $this->usuarioDe('Compras', ['email' => self::MAIL]);
        $codigo = (string) $this->pedirCodigo(self::MAIL);

        $this->ingresar(self::MAIL, $codigo)->assertOk();
        $this->postJson('/api/auth/salir')->assertOk();
        $this->app['auth']->forgetGuards();

        $this->noEntra($this->ingresar(self::MAIL, $codigo));
        $this->assertSame(0, CodigoIngreso::query()->count());
    }

    public function test_pedir_un_codigo_nuevo_invalida_el_anterior(): void
    {
        $this->usuarioDe('Compras', ['email' => self::MAIL]);
        $viejo = (string) $this->pedirCodigo(self::MAIL);
        $nuevo = (string) $this->pedirCodigo(self::MAIL);

        if ($viejo !== $nuevo) {
            $this->noEntra($this->ingresar(self::MAIL, $viejo));
        }
        $this->ingresar(self::MAIL, $nuevo)->assertOk();
    }

    public function test_cuarto_intento_fallido_todavia_deja_usar_el_codigo(): void
    {
        $this->usuarioDe('Compras', ['email' => self::MAIL]);
        $codigo = (string) $this->pedirCodigo(self::MAIL);

        for ($i = 1; $i < CodigoIngreso::INTENTOS_MAXIMOS; $i++) {
            $this->noEntra($this->ingresar(self::MAIL, $this->otroCodigo($codigo)));
        }

        $this->ingresar(self::MAIL, $codigo)->assertOk();
    }

    public function test_quinto_intento_fallido_invalida_el_codigo(): void
    {
        $this->usuarioDe('Compras', ['email' => self::MAIL]);
        $codigo = (string) $this->pedirCodigo(self::MAIL);

        for ($i = 0; $i < CodigoIngreso::INTENTOS_MAXIMOS; $i++) {
            $this->noEntra($this->ingresar(self::MAIL, $this->otroCodigo($codigo)));
        }

        $this->assertSame(0, CodigoIngreso::query()->count());
        $this->noEntra($this->ingresar(self::MAIL, $codigo));
    }

    public function test_usuario_inactivo_no_puede_ingresar_aunque_tenga_un_codigo_vigente(): void
    {
        $laura = $this->usuarioDe('Compras', ['email' => self::MAIL]);
        $codigo = (string) $this->pedirCodigo(self::MAIL);
        $laura->forceFill(['activo' => false])->save();

        $this->noEntra($this->ingresar(self::MAIL, $codigo));
    }

    public function test_undecimo_intento_del_mismo_mail_en_un_minuto_devuelve_429(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $this->ingresar(self::MAIL, '000000')->assertUnprocessable();
        }

        $this->ingresar(self::MAIL, '000000')
            ->assertTooManyRequests()
            ->assertExactJson(['data' => null, 'message' => IdentidadServiceProvider::DEMASIADOS_INTENTOS, 'errors' => null]);
    }

    public function test_intentos_de_ingreso_desde_la_misma_ip_tienen_limite(): void
    {
        for ($i = 0; $i < 30; $i++) {
            $this->ingresar("persona{$i}@empresa-ejemplo.com", '000000')->assertUnprocessable();
        }

        $this->ingresar('otra@empresa-ejemplo.com', '000000')->assertTooManyRequests();
    }

    public function test_el_codigo_no_queda_en_el_registro_de_errores(): void
    {
        $log = tempnam(sys_get_temp_dir(), 'errores-');
        $this->assertIsString($log);
        config(['logging.channels.errors' => ['driver' => 'single', 'path' => $log]]);
        $this->app['log']->forgetChannel('errors');

        $this->noEntra($this->ingresar(self::MAIL, '482913'));

        $contenido = (string) file_get_contents($log);
        unlink($log);
        $this->assertStringContainsString('ValidationException', $contenido, 'La validación fallida sí se registra');
        $this->assertStringNotContainsString('482913', $contenido);
    }

    public function test_salir_cierra_la_sesion(): void
    {
        $carla = $this->usuarioDe('Compras');

        $this->actingAs($carla)->postJson('/api/auth/salir')->assertOk()->assertJsonPath('data', null);

        $this->assertGuest('web');
    }

    public function test_salir_sin_sesion_devuelve_401(): void
    {
        $this->postJson('/api/auth/salir')->assertUnauthorized()->assertJsonStructure(['data', 'message', 'errors']);
    }

    public function test_las_rutas_de_contrasena_ya_no_existen(): void
    {
        $carla = $this->usuarioDe('Compras');

        $this->postJson('/api/auth/olvide-contrasena', ['email' => 'carla@empresa-ejemplo.com'])->assertNotFound();
        $this->postJson('/api/auth/elegir-contrasena', [])->assertNotFound();
        $this->actingAs($carla)->putJson('/api/me/contrasena', [])->assertNotFound();
        $this->actingAs($this->administrador())->postJson("/api/usuarios/{$carla->id}/reenviar-bienvenida")->assertNotFound();
    }

    /**
     * @return TestResponse<JsonResponse>
     */
    private function ingresar(string $email, string $codigo): TestResponse
    {
        return $this->desdeElFront()->postJson('/api/auth/ingresar', ['email' => $email, 'codigo' => $codigo]);
    }

    /**
     * @param  TestResponse<JsonResponse>  $response
     */
    private function noEntra(TestResponse $response): void
    {
        $response->assertUnprocessable()->assertJsonPath('errors.codigo', [IngresarAction::CODIGO_INVALIDO]);
        $this->assertGuest('web');
    }

    /** Un código de 6 dígitos distinto del vigente. */
    private function otroCodigo(string $codigo): string
    {
        return str_pad((string) (((int) $codigo + 1) % 1000000), 6, '0', STR_PAD_LEFT);
    }
}
