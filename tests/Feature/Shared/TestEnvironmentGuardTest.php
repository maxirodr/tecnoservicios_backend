<?php

declare(strict_types=1);

namespace Tests\Feature\Shared;

use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Si este test falla, el resto de la suite no prueba nada: corre contra la base equivocada o sin base.
 */
final class TestEnvironmentGuardTest extends TestCase
{
    #[Test]
    public function tests_run_against_the_separate_mysql_testing_database_and_it_answers(): void
    {
        $this->assertSame('mysql', config('database.default'));
        $this->assertSame('tecnoservicios_testing', DB::connection()->getDatabaseName());
        $this->assertSame(1, (int) DB::scalar('select 1'));
    }

    #[Test]
    public function tests_never_send_real_mail(): void
    {
        $this->assertSame('array', config('mail.default'));
    }
}
