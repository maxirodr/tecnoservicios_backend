<?php

declare(strict_types=1);

namespace Tests\Unit\Pedidos;

use App\Modules\Identidad\Contracts\PlazosPrioridad;
use App\Modules\Identidad\Domain\CalculadorPrioridad;
use App\Modules\Identidad\Domain\Enums\Prioridad;
use App\Modules\Pedidos\Domain\Prioridad\Anticipacion;
use App\Modules\Pedidos\Domain\Prioridad\Manual;
use App\Modules\Pedidos\Domain\Prioridad\PorAnticipacion;
use DateTimeImmutable;
use DateTimeInterface;
use PHPUnit\Framework\TestCase;

/** RN-PED-11…14: estrategias de prioridad. */
final class PrioridadPorAnticipacionTest extends TestCase
{
    public function test_usa_los_plazos_vigentes(): void
    {
        $estrategia = new PorAnticipacion(self::plazos(1, 7));

        $this->assertSame(Prioridad::Prioritario, $estrategia->prioridad(new DateTimeImmutable('2026-10-19'), new DateTimeImmutable('2026-10-23'), Prioridad::Normal));
        $this->assertSame(Prioridad::Urgente, $estrategia->prioridad(new DateTimeImmutable('2026-10-19'), new DateTimeImmutable('2026-10-16'), Prioridad::Normal));
        $this->assertSame(Prioridad::Normal, (new PorAnticipacion(self::plazos(1, 3)))->prioridad(new DateTimeImmutable('2026-10-19'), new DateTimeImmutable('2026-10-23'), Prioridad::Urgente));
    }

    public function test_manual_conserva_la_elegida(): void
    {
        $this->assertSame(Prioridad::Urgente, (new Manual)->prioridad(new DateTimeImmutable('2026-10-01'), new DateTimeImmutable('2026-12-01'), Prioridad::Urgente));
    }

    public function test_sin_fecha_de_pedido_conserva_la_importada(): void
    {
        $this->assertSame(Prioridad::Normal, (new PorAnticipacion(self::plazos(1, 7)))->prioridad(null, new DateTimeImmutable('2026-10-20'), Prioridad::Normal));
    }

    public function test_los_dias_son_corridos_por_fecha_de_calendario_sin_importar_el_huso(): void
    {
        $this->assertSame(4, Anticipacion::dias(new DateTimeImmutable('2026-10-19 00:00:00-03:00'), new DateTimeImmutable('2026-10-23 00:00:00+00:00')));
        $this->assertSame(-3, Anticipacion::dias(new DateTimeImmutable('2026-10-19'), new DateTimeImmutable('2026-10-16')));
    }

    private static function plazos(int $urgente, int $prioritario): PlazosPrioridad
    {
        return new class($urgente, $prioritario) implements PlazosPrioridad
        {
            public function __construct(private readonly int $urgente, private readonly int $prioritario) {}

            public function calcular(DateTimeInterface $fechaPedido, DateTimeInterface $fechaEntrega): Prioridad
            {
                return (new CalculadorPrioridad($this->urgente, $this->prioritario))->calcular($fechaPedido, $fechaEntrega);
            }
        };
    }
}
