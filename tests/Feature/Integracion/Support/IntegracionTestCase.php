<?php

declare(strict_types=1);

namespace Tests\Feature\Integracion\Support;

use App\Modules\Identidad\Domain\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Base de los tests que cruzan módulos sin fakes (backlog §7.1): MySQL real con la matriz inicial, Gastos,
 * Rendición y Obras con sus implementaciones reales, disco de adjuntos falso y "hoy" fijo: martes
 * 24-nov-2026, 10:00 en Argentina. Personas ficticias del caso de referencia (rendicion.md §9).
 */
abstract class IntegracionTestCase extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse('2026-11-24 13:00:00', 'UTC'));
        Storage::fake('adjuntos');
        config(['adjuntos.disk' => 'adjuntos']);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    /** Jefatura de Administración: Obras, Gastos, Rendición, Monto y Control por cantidad = Editar·todas. */
    protected function diego(): User
    {
        return User::factory()->enSector('Jefatura de Administración')->create(['nombre' => 'Diego']);
    }

    /** Ingeniería de proyectos: Rendición y Gastos = Ver·solo sus obras; Monto de obra sin acceso. */
    protected function laura(): User
    {
        return User::factory()->enSector('Ingeniería de proyectos')->create(['nombre' => 'Laura']);
    }

    /** Jefatura de Proyectos: responsable de la "Obra de control". */
    protected function hernan(): User
    {
        return User::factory()->enSector('Jefatura de Proyectos')->create(['nombre' => 'Hernán']);
    }
}
