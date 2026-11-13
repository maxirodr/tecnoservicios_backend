<?php

declare(strict_types=1);

namespace Tests\Unit\Identidad;

use App\Modules\Identidad\Domain\CalculadorPrioridad;
use App\Modules\Identidad\Domain\Enums\Prioridad;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

/** RN-IDE-41: días corridos entre pedido y entrega, sin feriados (resuelta DU-04). */
final class PlazosPrioridadTest extends TestCase
{
    private CalculadorPrioridad $calculador;

    private DateTimeImmutable $pedido;

    protected function setUp(): void
    {
        $this->calculador = new CalculadorPrioridad(1, 7);
        $this->pedido = new DateTimeImmutable('2026-11-13 16:30');
    }

    public function test_dias_hasta_urgente_es_urgente(): void
    {
        $this->assertSame(Prioridad::Urgente, $this->calculador->calcular($this->pedido, $this->pedido));
        $this->assertSame(Prioridad::Urgente, $this->calculador->calcular($this->pedido, new DateTimeImmutable('2026-11-14 08:00')));
    }

    public function test_limite_prioritario_es_inclusivo(): void
    {
        $this->assertSame(Prioridad::Prioritario, $this->calculador->calcular($this->pedido, new DateTimeImmutable('2026-11-15')));
        $this->assertSame(Prioridad::Prioritario, $this->calculador->calcular($this->pedido, new DateTimeImmutable('2026-11-20')));
    }

    public function test_mas_que_prioritario_es_normal(): void
    {
        $this->assertSame(Prioridad::Normal, $this->calculador->calcular($this->pedido, new DateTimeImmutable('2026-11-21')));
    }

    public function test_entrega_anterior_al_pedido_es_urgente(): void
    {
        $this->assertSame(Prioridad::Urgente, $this->calculador->calcular($this->pedido, new DateTimeImmutable('2026-11-01')));
    }

    public function test_se_cuentan_dias_corridos_incluido_el_fin_de_semana(): void
    {
        // Viernes → lunes = 3 días corridos: con urgente 1 es Prioritario.
        $viernes = new DateTimeImmutable('2026-11-13');

        $this->assertSame(Prioridad::Prioritario, $this->calculador->calcular($viernes, new DateTimeImmutable('2026-11-16')));
    }
}
