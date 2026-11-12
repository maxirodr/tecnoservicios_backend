<?php

declare(strict_types=1);

namespace Tests\Feature\Shared;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\TestCase;

/**
 * Contrato: ~/.claude/bug-reporting-autofixing/spec/error-endpoint-spec.md
 */
final class BugReportEndpointsTest extends TestCase
{
    private const TOKEN = 'test-errors-token-0123456789abcdef';

    private string $logFile;

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.errors_token' => self::TOKEN]);
        Cache::flush();
        $this->logFile = storage_path('framework/testing/logs/errors-2001-01-01.log');
        File::ensureDirectoryExists(dirname($this->logFile));
    }

    protected function tearDown(): void
    {
        File::delete(File::glob(dirname($this->logFile).'/errors-*.log'));

        parent::tearDown();
    }

    #[Test]
    public function server_errors_are_reported_to_the_errors_channel_with_context_and_read_back_by_the_endpoint(): void
    {
        Route::middleware('api')->post('api/_bug-report/boom', fn () => throw new RuntimeException('fallo de prueba'));

        $this->postJson('/api/_bug-report/boom', ['campo' => 'x', 'password' => 'no-se-loguea'])->assertStatus(500);

        $response = $this->withToken(self::TOKEN)->getJson('/api/errors?search=fallo de prueba')
            ->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath('errors.0.exception_class', RuntimeException::class)
            ->assertJsonPath('errors.0.method', 'POST')
            ->assertJsonPath('errors.0.input', ['campo' => 'x']);

        $this->assertStringNotContainsString('no-se-loguea', (string) $response->getContent());
    }

    #[Test]
    public function validation_failures_are_reported_as_warnings_with_validation_errors(): void
    {
        Route::middleware('api')->post('api/_bug-report/validate', fn (Request $request) => $request->validate(['nombre' => ['required']]));

        $this->postJson('/api/_bug-report/validate')->assertUnprocessable();

        $this->withToken(self::TOKEN)->getJson('/api/errors?search=ValidationException')
            ->assertOk()
            ->assertJsonPath('errors.0.level', 'WARNING')
            ->assertJsonStructure(['errors' => [['validation_errors' => ['nombre']]]]);
    }

    #[Test]
    public function health_reports_each_service_inside_the_envelope(): void
    {
        $this->getJson('/api/health')
            ->assertOk()
            ->assertJsonPath('data.status', 'ok')
            ->assertJsonPath('data.services.database', true)
            ->assertJsonPath('errors', null)
            ->assertJsonStructure(['data' => ['status', 'timestamp', 'services' => ['app', 'database', 'cache', 'redis']]]);
    }

    #[Test]
    public function errors_endpoint_without_token_returns_401_with_contract_key_and_envelope(): void
    {
        $this->getJson('/api/errors')
            ->assertUnauthorized()
            ->assertJsonPath('error', 'Unauthorized')
            ->assertJsonPath('data', null)
            ->assertJsonPath('message', 'Unauthorized')
            ->assertJsonPath('errors', null);
    }

    #[Test]
    public function errors_endpoint_with_wrong_token_returns_401(): void
    {
        $this->getJson('/api/errors?token=wrong')->assertUnauthorized();
    }

    #[Test]
    public function errors_endpoint_bans_ip_after_ten_failed_attempts(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $this->getJson('/api/errors?token=wrong')->assertUnauthorized();
        }

        $this->getJson('/api/errors?token=wrong')->assertForbidden()->assertJsonPath('error', 'Banned');
        $this->getJson('/api/errors?token='.self::TOKEN)->assertForbidden();
    }

    #[Test]
    public function errors_endpoint_returns_404_with_available_dates_when_day_has_no_log(): void
    {
        File::put($this->logFile, '');

        $response = $this->withToken(self::TOKEN)->getJson('/api/errors?date=2000-01-01')
            ->assertNotFound()
            ->assertJsonPath('error', 'No logs for date 2000-01-01');

        $this->assertContains('2001-01-01', $response->json('available_dates'));
    }

    #[Test]
    public function errors_endpoint_returns_parsed_entries_in_contract_shape_and_strips_sensitive_input(): void
    {
        $context = json_encode([
            'exception_class' => 'RuntimeException',
            'action' => 'App\\Http\\Controllers\\FooController@index',
            'method' => 'GET',
            'url' => 'http://localhost/api/foo',
            'user_id' => 5,
            'ip' => '127.0.0.1',
            'input' => ['q' => 'x', 'password' => 'secreta'],
            'file' => '/app/Foo.php:10',
            'trace_short' => ['Foo->bar() (Foo.php:10)'],
        ]);
        File::put($this->logFile, "[2001-01-01 10:00:00] testing.ERROR: Algo se rompió {$context}\n");

        $this->getJson('/api/errors?date=2001-01-01&token='.self::TOKEN)
            ->assertOk()
            ->assertJsonPath('date', '2001-01-01')
            ->assertJsonPath('total', 1)
            ->assertJsonPath('errors.0.level', 'ERROR')
            ->assertJsonPath('errors.0.message', 'Algo se rompió')
            ->assertJsonPath('errors.0.exception_class', 'RuntimeException')
            ->assertJsonPath('errors.0.input', ['q' => 'x'])
            ->assertJsonStructure(['date', 'total', 'errors' => [[
                'timestamp', 'level', 'message', 'action', 'method', 'url', 'user_id', 'ip',
                'input', 'exception_class', 'file', 'trace_short',
            ]]]);

        $this->getJson('/api/errors?date=2001-01-01&search=nada-que-ver&token='.self::TOKEN)
            ->assertOk()
            ->assertJsonPath('total', 0);
    }

    #[Test]
    public function errors_endpoint_with_date_all_lists_dates_included(): void
    {
        File::put($this->logFile, "[2001-01-01 10:00:00] testing.WARNING: Validación {}\n");

        $this->withToken(self::TOKEN)->getJson('/api/errors?date=all')
            ->assertOk()
            ->assertJsonPath('date', 'all');

        $this->assertContains('2001-01-01', $this->withToken(self::TOKEN)->getJson('/api/errors?date=all')->json('dates_included'));
    }
}
