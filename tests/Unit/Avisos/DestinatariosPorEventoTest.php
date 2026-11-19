<?php

declare(strict_types=1);

namespace Tests\Unit\Avisos;

use App\Modules\Avisos\Domain\Destinatario;
use App\Modules\Avisos\Domain\DestinatariosPorEvento;
use App\Modules\Avisos\Domain\Padron;
use App\Modules\Pedidos\Domain\Events\Participantes;
use PHPUnit\Framework\TestCase;

/**
 * avisos.md §5 y §6 (una aserción por fila de la tabla), con las personas ficticias de las maquetas:
 * Laura pide, Carla es la propietaria, Sofía sigue, Hernán es el jefe de Laura, Diego es un tercero.
 */
final class DestinatariosPorEventoTest extends TestCase
{
    private const LAURA = 1;

    private const CARLA = 2;

    private const SOFIA = 3;

    private const HERNAN = 4;

    private const DIEGO = 5;

    private const INACTIVA = 6;

    private const SECTOR = 10;

    private DestinatariosPorEvento $reglas;

    protected function setUp(): void
    {
        $this->reglas = new DestinatariosPorEvento;
    }

    public function test_asignacion_va_al_propietario(): void
    {
        $this->assertSame([[self::CARLA, 'asignado', 'propietario']], $this->plano($this->reglas->asignado($this->pedido(), self::LAURA, $this->padron())));
    }

    public function test_quien_se_carga_un_pedido_a_si_mismo_no_recibe_la_asignacion(): void
    {
        $this->assertSame([], $this->reglas->asignado($this->pedido(solicitante: self::CARLA), self::CARLA, $this->padron()));
    }

    public function test_asignacion_al_sector_va_a_todas_las_personas_activas_menos_quien_carga(): void
    {
        $padron = $this->padron(sector: [self::CARLA, self::DIEGO, self::SOFIA]);

        $this->assertSame(
            [[self::DIEGO, 'asignado', 'sector'], [self::SOFIA, 'asignado', 'sector']],
            $this->plano($this->reglas->asignado($this->pedido(propietario: null), self::CARLA, $padron)),
        );
    }

    public function test_reasignacion_a_persona_va_solo_al_nuevo_propietario(): void
    {
        $this->assertSame([[self::DIEGO, 'reasignado', 'propietario']], $this->plano($this->reglas->reasignado($this->pedido(propietario: self::DIEGO), self::CARLA, $this->padron())));
    }

    public function test_reasignacion_al_sector_va_a_todas_las_personas_activas(): void
    {
        $padron = $this->padron(sector: [self::CARLA, self::DIEGO, self::SOFIA]);

        $this->assertSame(
            [self::DIEGO, self::SOFIA],
            $this->ids($this->reglas->reasignado($this->pedido(propietario: null), self::CARLA, $padron)),
        );
    }

    public function test_tomado_va_al_solicitante_y_seguidores(): void
    {
        $this->assertSame(
            [[self::LAURA, 'tomado', 'solicitante'], [self::SOFIA, 'tomado', 'seguidor'], [self::HERNAN, 'tomado', 'seguidor']],
            $this->plano($this->reglas->tomado($this->pedido(seguidores: [self::SOFIA, self::HERNAN]), self::DIEGO, $this->padron())),
        );
    }

    public function test_bloqueado_va_al_solicitante_y_seguidores(): void
    {
        $this->assertSame(
            [[self::LAURA, 'bloqueado', 'solicitante'], [self::SOFIA, 'bloqueado', 'seguidor']],
            $this->plano($this->reglas->bloqueado($this->pedido(seguidores: [self::SOFIA]), self::CARLA, $this->padron())),
        );
    }

    public function test_comentario_del_solicitante_va_al_propietario(): void
    {
        $this->assertSame([self::CARLA, self::SOFIA], $this->ids($this->reglas->comentado($this->pedido(seguidores: [self::SOFIA]), self::LAURA, $this->padron())));
    }

    public function test_comentario_del_propietario_va_al_solicitante(): void
    {
        $this->assertSame(
            [[self::LAURA, 'comentado', 'solicitante'], [self::SOFIA, 'comentado', 'seguidor']],
            $this->plano($this->reglas->comentado($this->pedido(seguidores: [self::SOFIA]), self::CARLA, $this->padron())),
        );
    }

    public function test_comentario_de_un_tercero_va_a_las_dos_partes(): void
    {
        $this->assertSame([self::CARLA, self::LAURA], $this->ids($this->reglas->comentado($this->pedido(seguidores: [self::SOFIA]), self::SOFIA, $this->padron())));
    }

    public function test_comentario_en_un_pedido_en_el_sector_va_a_las_personas_del_sector(): void
    {
        $padron = $this->padron(sector: [self::CARLA, self::DIEGO]);

        $this->assertSame([self::CARLA, self::DIEGO], $this->ids($this->reglas->comentado($this->pedido(propietario: null), self::LAURA, $padron)));
    }

    public function test_completado_va_al_solicitante_y_al_superior_del_solicitante(): void
    {
        $this->assertSame(
            [[self::LAURA, 'completado', 'solicitante'], [self::HERNAN, 'completado_gente', 'superior'], [self::SOFIA, 'completado', 'seguidor']],
            $this->plano($this->reglas->completado($this->pedido(seguidores: [self::SOFIA]), self::CARLA, $this->padron(superior: self::HERNAN))),
        );
    }

    public function test_completado_no_va_al_superior_de_quien_completo(): void
    {
        $this->assertSame([self::LAURA], $this->ids($this->reglas->completado($this->pedido(propietario: self::HERNAN), self::HERNAN, $this->padron(superior: self::HERNAN))));
    }

    public function test_superior_inactivo_no_recibe_el_completado(): void
    {
        $this->assertSame([self::LAURA], $this->ids($this->reglas->completado($this->pedido(), self::CARLA, $this->padron(superior: self::INACTIVA))));
    }

    public function test_reabierto_va_al_propietario(): void
    {
        $this->assertSame([[self::CARLA, 'reabierto', 'propietario']], $this->plano($this->reglas->reabierto($this->pedido(seguidores: [self::SOFIA]), self::LAURA, $this->padron())));
    }

    public function test_reabierto_en_el_sector_va_a_sus_personas(): void
    {
        $this->assertSame([self::CARLA, self::DIEGO], $this->ids($this->reglas->reabierto($this->pedido(propietario: null), self::LAURA, $this->padron(sector: [self::CARLA, self::DIEGO]))));
    }

    public function test_nadie_recibe_aviso_de_lo_que_hizo_el_mismo(): void
    {
        $pedido = $this->pedido(seguidores: [self::SOFIA]);

        $this->assertNotContains(self::SOFIA, $this->ids($this->reglas->comentado($pedido, self::SOFIA, $this->padron())));
        $this->assertNotContains(self::LAURA, $this->ids($this->reglas->tomado($pedido, self::LAURA, $this->padron())));
    }

    public function test_inactivo_no_recibe_avisos(): void
    {
        $pedido = $this->pedido(propietario: self::INACTIVA, seguidores: [self::INACTIVA, self::SOFIA]);

        $this->assertSame([], $this->reglas->asignado($pedido, self::LAURA, $this->padron()));
        $this->assertSame([self::LAURA, self::SOFIA], $this->ids($this->reglas->tomado($pedido, self::DIEGO, $this->padron())));
    }

    public function test_un_solo_aviso_por_persona_y_evento(): void
    {
        $avisos = $this->reglas->completado($this->pedido(seguidores: [self::HERNAN, self::LAURA]), self::CARLA, $this->padron(superior: self::HERNAN));

        $this->assertSame([[self::LAURA, 'completado', 'solicitante'], [self::HERNAN, 'completado_gente', 'superior']], $this->plano($avisos));
    }

    public function test_sin_solicitante_no_hay_aviso_al_superior(): void
    {
        $avisos = $this->reglas->completado($this->pedido(solicitante: null, seguidores: [self::SOFIA]), self::CARLA, $this->padron(superior: self::HERNAN));

        $this->assertSame([self::SOFIA], $this->ids($avisos));
    }

    public function test_seguidor_no_recibe_asignacion_ni_reasignacion_ni_reapertura(): void
    {
        $pedido = $this->pedido(seguidores: [self::SOFIA]);

        foreach (['asignado', 'reasignado', 'reabierto'] as $evento) {
            $this->assertNotContains(self::SOFIA, $this->ids($this->reglas->{$evento}($pedido, self::LAURA, $this->padron())), $evento);
        }
    }

    public function test_recordatorio_va_al_propietario_o_a_las_personas_del_sector(): void
    {
        $this->assertSame([[self::CARLA, 'vence_manana', 'propietario']], $this->plano($this->reglas->recordatorio(self::CARLA, $this->padron())));
        $this->assertSame([self::CARLA, self::DIEGO], $this->ids($this->reglas->recordatorio(null, $this->padron(sector: [self::CARLA, self::DIEGO]))));
        $this->assertSame([], $this->reglas->recordatorio(self::INACTIVA, $this->padron()));
    }

    /**
     * @param  list<int>  $seguidores
     */
    private function pedido(?int $solicitante = self::LAURA, ?int $propietario = self::CARLA, array $seguidores = []): Participantes
    {
        return new Participantes($solicitante, $propietario, self::SECTOR, $seguidores);
    }

    /**
     * @param  list<int>  $sector
     */
    private function padron(array $sector = [], ?int $superior = null): Padron
    {
        return new Padron([self::LAURA, self::CARLA, self::SOFIA, self::HERNAN, self::DIEGO], $sector, $superior);
    }

    /**
     * @param  list<Destinatario>  $destinatarios
     * @return list<array{0: int, 1: string, 2: string}>
     */
    private function plano(array $destinatarios): array
    {
        return array_map(static fn (Destinatario $d): array => [$d->userId, $d->tipo->value, $d->papel->value], $destinatarios);
    }

    /**
     * @param  list<Destinatario>  $destinatarios
     * @return list<int>
     */
    private function ids(array $destinatarios): array
    {
        return array_map(static fn (Destinatario $d): int => $d->userId, $destinatarios);
    }
}
