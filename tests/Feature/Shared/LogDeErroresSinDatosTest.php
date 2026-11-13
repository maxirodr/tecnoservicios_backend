<?php

declare(strict_types=1);

namespace Tests\Feature\Shared;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use RuntimeException;
use Tests\Feature\Identidad\IdentidadTestCase;

/** SEG-02: al log `errors` y a GET /api/errors van las claves del input, nunca sus valores. */
final class LogDeErroresSinDatosTest extends IdentidadTestCase
{
    private const TOKEN = 'test-errors-token-0123456789abcdef';

    private const MAIL = 'laura@empresa-ejemplo.com';

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.errors_token' => self::TOKEN]);
        Cache::flush();
        File::ensureDirectoryExists($this->directorioDeLogs());
    }

    protected function tearDown(): void
    {
        File::delete(File::glob($this->directorioDeLogs().'/errors-*.log'));

        parent::tearDown();
    }

    public function test_ingreso_fallido_no_deja_el_mail_ni_el_codigo_en_el_log(): void
    {
        $this->usuarioDe('Compras', ['email' => self::MAIL]);

        $this->desdeElFront()->postJson('/api/auth/ingresar', ['email' => self::MAIL, 'codigo' => '987654'])
            ->assertUnprocessable();

        $log = $this->contenidoDelLog();
        $this->assertStringContainsString('ValidationException', $log);
        $this->assertStringNotContainsString(self::MAIL, $log);
        $this->assertStringNotContainsString('987654', $log);

        $respuesta = $this->withToken(self::TOKEN)->getJson('/api/errors?search=ValidationException')
            ->assertOk()
            ->assertJsonPath('errors.0.input', ['email', 'codigo'])
            ->assertJsonPath('errors.0.validation_errors', ['codigo']);
        $this->assertStringNotContainsString(self::MAIL, (string) $respuesta->getContent());
    }

    public function test_error_de_servidor_registra_las_claves_y_la_url_sin_query_string(): void
    {
        Route::middleware('api')->post('api/_bug-report/boom', fn () => throw new RuntimeException('fallo de prueba'));

        $this->postJson('/api/_bug-report/boom?buscar=juan@empresa-ejemplo.com', [
            'telefono' => '291-555-0101',
            'cuit' => '20-00000000-1',
            'contactos' => [['email' => 'contacto@empresa-ejemplo.com']],
            'monto' => '1234.56',
        ])->assertStatus(500);

        $log = $this->contenidoDelLog();
        foreach (['juan@empresa-ejemplo.com', '291-555-0101', '20-00000000-1', 'contacto@empresa-ejemplo.com', '1234.56'] as $valor) {
            $this->assertStringNotContainsString($valor, $log);
        }

        $this->withToken(self::TOKEN)->getJson('/api/errors?search=fallo de prueba')
            ->assertOk()
            ->assertJsonPath('errors.0.url', url('/api/_bug-report/boom'))
            ->assertJsonPath('errors.0.input', ['telefono', 'cuit', 'contactos.0.email', 'monto', 'buscar']);
    }

    public function test_entradas_viejas_con_valores_salen_por_el_endpoint_solo_con_las_claves(): void
    {
        $contexto = json_encode([
            'url' => 'http://localhost/api/usuarios?buscar=laura',
            'input' => ['buscar' => 'laura', 'email' => self::MAIL],
            'validation_errors' => ['email' => ['El mail '.self::MAIL.' ya existe.']],
        ]);
        File::put($this->directorioDeLogs().'/errors-2001-01-01.log', "[2001-01-01 10:00:00] testing.WARNING: Vieja {$contexto}\n");

        $respuesta = $this->withToken(self::TOKEN)->getJson('/api/errors?date=2001-01-01')
            ->assertOk()
            ->assertJsonPath('errors.0.url', 'http://localhost/api/usuarios')
            ->assertJsonPath('errors.0.input', ['buscar', 'email'])
            ->assertJsonPath('errors.0.validation_errors', ['email']);

        $this->assertStringNotContainsString(self::MAIL, (string) $respuesta->getContent());
        $this->assertStringNotContainsString('laura', (string) $respuesta->getContent());
    }

    private function directorioDeLogs(): string
    {
        return storage_path('framework/testing/logs');
    }

    private function contenidoDelLog(): string
    {
        return implode("\n", array_map(
            static fn (string $archivo): string => (string) file_get_contents($archivo),
            File::glob($this->directorioDeLogs().'/errors-*.log'),
        ));
    }
}
