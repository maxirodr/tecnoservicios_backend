<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Testing\PendingCommand;
use LogicException;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Los tests nunca escriben en los logs reales que lee GET /api/errors.
        config(['logging.channels.errors.path' => storage_path('framework/testing/logs/errors.log')]);
    }

    /**
     * artisan() tipado: con la salida de consola mockeada siempre devuelve PendingCommand.
     *
     * @param  array<string, mixed>  $parameters
     */
    protected function command(string $command, array $parameters = []): PendingCommand
    {
        $pending = $this->artisan($command, $parameters);

        if (! $pending instanceof PendingCommand) {
            throw new LogicException('La salida de consola no está mockeada en este test.');
        }

        return $pending;
    }
}
