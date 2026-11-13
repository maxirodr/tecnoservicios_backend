<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Support\ErrorLogEntryParser;
use App\Http\Controllers\Controller;
use App\Shared\Http\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * GET /api/errors — contrato: ~/.claude/bug-reporting-autofixing/spec/error-endpoint-spec.md (el contrato manda).
 *
 * 200 devuelve exactamente la forma del contrato ({date, total, errors[], dates_included?}); es la única
 * respuesta de la API fuera del envelope, porque el contrato usa `errors` como lista de entradas.
 * 401/403/404 devuelven el envelope MÁS las claves del contrato (`error`, `available_dates`).
 */
final class ErrorLogController extends Controller
{
    private const MAX_FAILED_ATTEMPTS = 10;

    public function index(Request $request): JsonResponse
    {
        $ip = (string) $request->ip();

        if (Cache::has("errors_banned:{$ip}")) {
            return $this->contractError('Banned', 403);
        }

        $throttleKey = "errors_attempts:{$ip}";
        $attempts = (int) Cache::get($throttleKey, 0);

        if ($attempts >= self::MAX_FAILED_ATTEMPTS) {
            Cache::put("errors_banned:{$ip}", true, now()->addDay());
            Log::warning('errors_log.banned', ['ip' => $ip]);

            return $this->contractError('Banned', 403);
        }

        $token = $request->bearerToken() ?? $request->query('token');
        $expected = config('app.errors_token');

        if (! is_string($token) || $token === '' || ! is_string($expected) || $expected === ''
            || ! hash_equals($expected, $token)) {
            Cache::put($throttleKey, $attempts + 1, now()->addHour());
            Log::warning('errors_log.unauthorized', ['ip' => $ip]);

            return $this->contractError('Unauthorized', 401);
        }

        Cache::forget($throttleKey);

        $date = (string) $request->query('date', now()->toDateString());
        $search = $request->query('search');

        if ($date !== 'all' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) !== 1) {
            return $this->contractError("No logs for date {$date}", 404, ['available_dates' => $this->availableDates()]);
        }

        if ($date === 'all') {
            $logFiles = $this->logFiles();

            if ($logFiles === []) {
                return $this->contractError('No error logs available', 404, ['available_dates' => []]);
            }
        } else {
            $logFile = $this->logDirectory().DIRECTORY_SEPARATOR."errors-{$date}.log";

            if (! is_file($logFile)) {
                return $this->contractError("No logs for date {$date}", 404, ['available_dates' => $this->availableDates()]);
            }

            $logFiles = [$logFile];
        }

        $entries = [];
        foreach ($logFiles as $file) {
            array_push($entries, ...ErrorLogEntryParser::parseFile((string) file_get_contents($file)));
        }

        if (is_string($search) && $search !== '') {
            $needle = mb_strtolower($search);
            $entries = array_values(array_filter($entries, static function (array $entry) use ($needle): bool {
                $haystack = mb_strtolower(implode(' ', [
                    (string) $entry['message'],
                    (string) $entry['action'],
                    (string) $entry['url'],
                    (string) $entry['exception_class'],
                    (string) json_encode($entry['input'] ?? []),
                ]));

                return str_contains($haystack, $needle);
            }));
        }

        $response = [
            'date' => $date,
            'total' => count($entries),
            'errors' => $entries,
        ];

        if ($date === 'all') {
            $response['dates_included'] = array_map($this->dateFromPath(...), $logFiles);
        }

        return new JsonResponse($response);
    }

    /**
     * @return list<string>
     */
    private function logFiles(): array
    {
        $files = glob($this->logDirectory().DIRECTORY_SEPARATOR.'errors-*.log');
        $files = $files === false ? [] : $files;
        sort($files);

        return $files;
    }

    /**
     * @return list<string>
     */
    private function availableDates(): array
    {
        return array_map($this->dateFromPath(...), $this->logFiles());
    }

    /**
     * Mismo directorio que escribe el canal `errors` (config/logging.php).
     */
    private function logDirectory(): string
    {
        $path = config('logging.channels.errors.path');

        return dirname(is_string($path) ? $path : storage_path('logs/errors.log'));
    }

    private function dateFromPath(string $path): string
    {
        return str_replace('errors-', '', basename($path, '.log'));
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    private function contractError(string $error, int $status, array $extra = []): JsonResponse
    {
        $body = ApiResponse::body($extra === [] ? null : $extra, $error, null);

        return new JsonResponse(array_merge($body, ['error' => $error], $extra), $status);
    }
}
