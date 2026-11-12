<?php

declare(strict_types=1);

namespace App\Shared\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;

final class SupervisorSyncCommand extends Command
{
    protected $signature = 'supervisor:sync
        {--dir= : Directorio donde escribir los .conf (por defecto config supervisor.directory)}
        {--no-reload : Solo generar, no recargar supervisor}';

    protected $description = 'Genera los .conf de Supervisord (workers y scheduler) y recarga supervisor si está disponible';

    public function handle(): int
    {
        $directory = $this->stringOption('dir') ?? $this->configString('supervisor.directory');
        $prefix = $this->configString('supervisor.program_prefix');

        /** @var array<string, array{command: string, numprocs: int, stopwaitsecs: int}> $programs */
        $programs = config('supervisor.programs', []);

        File::ensureDirectoryExists($directory);

        $written = [];
        foreach ($programs as $name => $program) {
            $programName = "{$prefix}-{$name}";
            $path = $directory.DIRECTORY_SEPARATOR."{$programName}.conf";
            File::put($path, $this->renderProgram($programName, $program));
            $written[] = $path;
            $this->components->info("Generado {$path}");
        }

        $this->removeStaleConfigs($directory, $prefix, $written);

        if ($this->option('no-reload') === true || ! $this->shouldReload()) {
            $this->components->warn('Supervisor no se recargó (Windows/local o supervisorctl no disponible). Copiá los .conf al include de supervisord y corré: supervisorctl reread && supervisorctl update');

            return self::SUCCESS;
        }

        foreach (['reread', 'update'] as $action) {
            $result = Process::run(['supervisorctl', $action]);
            if ($result->failed()) {
                $this->components->error("supervisorctl {$action} falló: ".trim($result->errorOutput()));

                return self::FAILURE;
            }
        }

        $this->components->info('Supervisor recargado (reread + update).');

        return self::SUCCESS;
    }

    /**
     * @param  array{command: string, numprocs: int, stopwaitsecs: int}  $program
     */
    private function renderProgram(string $programName, array $program): string
    {
        $php = $this->configString('supervisor.php_binary');
        $artisan = base_path('artisan');
        $logDirectory = rtrim($this->configString('supervisor.log_directory'), '/\\');
        $numprocs = max(1, $program['numprocs']);

        $lines = [
            '; Generado por `php artisan supervisor:sync`. No editar a mano.',
            "[program:{$programName}]",
            'process_name=%(program_name)s_%(process_num)02d',
            "command={$php} {$artisan} {$program['command']}",
            'directory='.base_path(),
            'autostart=true',
            'autorestart=true',
            'stopasgroup=true',
            'killasgroup=true',
            'user='.$this->configString('supervisor.user'),
            "numprocs={$numprocs}",
            'redirect_stderr=true',
            "stdout_logfile={$logDirectory}/supervisor-{$programName}.log",
            'stdout_logfile_maxbytes=10MB',
            'stdout_logfile_backups=5',
            "stopwaitsecs={$program['stopwaitsecs']}",
        ];

        return implode("\n", $lines)."\n";
    }

    /**
     * @param  list<string>  $keep
     */
    private function removeStaleConfigs(string $directory, string $prefix, array $keep): void
    {
        foreach (File::glob($directory.DIRECTORY_SEPARATOR."{$prefix}-*.conf") as $existing) {
            if (! in_array($existing, $keep, true)) {
                File::delete($existing);
                $this->components->warn("Eliminado {$existing} (programa que ya no existe)");
            }
        }
    }

    private function shouldReload(): bool
    {
        return match ($this->configString('supervisor.reload')) {
            'always' => true,
            'never' => false,
            default => PHP_OS_FAMILY !== 'Windows'
                && Process::run(['sh', '-c', 'command -v supervisorctl'])->successful(),
        };
    }

    private function stringOption(string $name): ?string
    {
        $value = $this->option($name);

        return is_string($value) && $value !== '' ? $value : null;
    }

    private function configString(string $key): string
    {
        $value = config($key);

        return is_scalar($value) ? (string) $value : '';
    }
}
