<?php

declare(strict_types=1);

namespace Tests\Feature\Shared;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class SupervisorSyncCommandTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->directory = storage_path('framework/testing/supervisor-'.uniqid());
        config(['supervisor.program_prefix' => 'tecnoservicios']);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->directory);

        parent::tearDown();
    }

    #[Test]
    public function it_generates_default_worker_mail_worker_and_scheduler_configs(): void
    {
        Process::fake();
        config(['supervisor.reload' => 'never']);

        $this->command('supervisor:sync', ['--dir' => $this->directory])->assertSuccessful();

        $files = array_map('basename', File::glob($this->directory.'/*.conf'));
        sort($files);
        $this->assertSame([
            'tecnoservicios-scheduler.conf',
            'tecnoservicios-worker-default.conf',
            'tecnoservicios-worker-mail.conf',
        ], $files);

        $default = File::get($this->directory.'/tecnoservicios-worker-default.conf');
        $this->assertStringContainsString('[program:tecnoservicios-worker-default]', $default);
        $this->assertStringContainsString('queue:work redis --queue=default', $default);

        $mail = File::get($this->directory.'/tecnoservicios-worker-mail.conf');
        $this->assertStringContainsString('--queue=mail', $mail);
        $this->assertStringContainsString('--rest=1', $mail, 'El worker de mail descansa 1 s por job: máximo 1 mail/s.');
        $this->assertStringContainsString('numprocs=1', $mail);

        $this->assertStringContainsString('artisan schedule:work', File::get($this->directory.'/tecnoservicios-scheduler.conf'));

        Process::assertNothingRan();
    }

    #[Test]
    public function it_reloads_supervisor_with_reread_and_update_when_available(): void
    {
        Process::fake();
        config(['supervisor.reload' => 'always']);

        $this->command('supervisor:sync', ['--dir' => $this->directory])->assertSuccessful();

        Process::assertRan(fn ($process) => $process->command === ['supervisorctl', 'reread']);
        Process::assertRan(fn ($process) => $process->command === ['supervisorctl', 'update']);
    }

    #[Test]
    public function it_only_generates_and_warns_when_reload_is_disabled(): void
    {
        Process::fake();
        config(['supervisor.reload' => 'always']);

        $this->command('supervisor:sync', ['--dir' => $this->directory, '--no-reload' => true])
            ->expectsOutputToContain('Supervisor no se recargó')
            ->assertSuccessful();

        Process::assertNothingRan();
    }

    #[Test]
    public function it_removes_configs_of_programs_that_no_longer_exist(): void
    {
        Process::fake();
        config(['supervisor.reload' => 'never']);
        File::ensureDirectoryExists($this->directory);
        File::put($this->directory.'/tecnoservicios-viejo.conf', '[program:tecnoservicios-viejo]');
        File::put($this->directory.'/otro-sistema.conf', '[program:otro]');

        $this->command('supervisor:sync', ['--dir' => $this->directory])->assertSuccessful();

        $this->assertFileDoesNotExist($this->directory.'/tecnoservicios-viejo.conf');
        $this->assertFileExists($this->directory.'/otro-sistema.conf', 'Nunca toca .conf de otros sistemas.');
    }

    #[Test]
    public function it_fails_when_supervisorctl_fails(): void
    {
        Process::fake(['*' => Process::result(errorOutput: 'unix:///var/run/supervisor.sock no such file', exitCode: 2)]);
        config(['supervisor.reload' => 'always']);

        $this->command('supervisor:sync', ['--dir' => $this->directory])->assertFailed();
    }
}
