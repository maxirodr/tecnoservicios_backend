<?php

declare(strict_types=1);

namespace Tests\Unit\Avisos;

use App\Modules\Avisos\Domain\ContextoDelAviso;
use App\Modules\Avisos\Domain\Papel;
use App\Modules\Avisos\Domain\RedactorDeAvisos;
use App\Modules\Avisos\Domain\TipoAviso;
use PHPUnit\Framework\TestCase;

/** Textos y asuntos de avisos.md §6, con las palabras del cliente y datos ficticios (RN-AVI-07). */
final class RedactorDeAvisosTest extends TestCase
{
    private RedactorDeAvisos $redactor;

    protected function setUp(): void
    {
        $this->redactor = new RedactorDeAvisos;
    }

    public function test_asignacion_dice_quien_asigno_la_tarea_y_la_obra(): void
    {
        $c = new ContextoDelAviso('Alquiler de andamio para el sector de bombas', 'Reparación de losa tanque B', 'Laura');

        $this->assertSame('Laura te asignó Alquiler de andamio para el sector de bombas · Reparación de losa tanque B', $this->redactor->texto(TipoAviso::Asignado, Papel::Propietario, $c));
        $this->assertSame('Te asignaron una tarea: Alquiler de andamio para el sector de bombas', $this->redactor->asunto(TipoAviso::Asignado, Papel::Propietario, $c));
    }

    public function test_pedido_sin_obra_dice_servicios_generales(): void
    {
        $c = new ContextoDelAviso('Informe fotográfico semanal', null);

        $this->assertSame('Vence mañana: Informe fotográfico semanal · Servicios generales', $this->redactor->texto(TipoAviso::VenceManana, Papel::Propietario, $c));
        $this->assertNull($this->redactor->asunto(TipoAviso::VenceManana, Papel::Propietario, $c));
    }

    public function test_reasignacion_lleva_el_comentario(): void
    {
        $c = new ContextoDelAviso('Compra de discos de corte diamantados', 'Reparación de losa tanque B', 'Carla', 'Diego tiene la cuenta con el proveedor de discos.');

        $this->assertSame(
            "Carla te reasignó Compra de discos de corte diamantados · Reparación de losa tanque B: 'Diego tiene la cuenta con el proveedor de discos.'",
            $this->redactor->texto(TipoAviso::Reasignado, Papel::Propietario, $c),
        );
    }

    public function test_reasignacion_al_sector_dice_que_lo_toma_el_primero_que_pueda(): void
    {
        $c = new ContextoDelAviso('Compra de discos de corte diamantados', null, 'Carla', sectorNombre: 'Compras');

        $this->assertSame('Carla dejó en Compras (tu sector) Compra de discos de corte diamantados: la toma el primero que pueda', $this->redactor->texto(TipoAviso::Reasignado, Papel::Sector, $c));
        $this->assertSame('Hay una tarea para Compras: Compra de discos de corte diamantados', $this->redactor->asunto(TipoAviso::Reasignado, Papel::Sector, $c));
    }

    public function test_tomado_distingue_al_solicitante_del_seguidor(): void
    {
        $c = new ContextoDelAviso('Solicitud de materiales N.º 3', null, 'Carla');

        $this->assertSame('Carla tomó tu tarea Solicitud de materiales N.º 3 → pasó a En curso', $this->redactor->texto(TipoAviso::Tomado, Papel::Solicitante, $c));
        $this->assertSame('Carla tomó Solicitud de materiales N.º 3 (que seguís) → En curso', $this->redactor->texto(TipoAviso::Tomado, Papel::Seguidor, $c));
    }

    public function test_bloqueado_lleva_el_motivo(): void
    {
        $c = new ContextoDelAviso('Envío de EPP a obra', null, 'Carla', 'Falta el talle de botines de 3 personas de la cuadrilla.');

        $this->assertSame("Carla bloqueó tu tarea Envío de EPP a obra: 'Falta el talle de botines de 3 personas de la cuadrilla.'", $this->redactor->texto(TipoAviso::Bloqueado, Papel::Solicitante, $c));
        $this->assertSame("Carla bloqueó Envío de EPP a obra (que seguís): 'Falta el talle de botines de 3 personas de la cuadrilla.'", $this->redactor->texto(TipoAviso::Bloqueado, Papel::Seguidor, $c));
    }

    public function test_completado_con_comprobante_y_aviso_al_superior(): void
    {
        $c = new ContextoDelAviso('Alquiler de obrador', null, 'Carla', 'Entregado en obra, adjunto remito', 1, 'Laura');

        $this->assertSame("Carla completó tu tarea Alquiler de obrador: 'Entregado en obra, adjunto remito' · comprobante", $this->redactor->texto(TipoAviso::Completado, Papel::Solicitante, $c));
        $this->assertSame("Carla completó Alquiler de obrador, que pidió Laura: 'Entregado en obra, adjunto remito'", $this->redactor->texto(TipoAviso::CompletadoGente, Papel::Superior, $c));
        $this->assertNull($this->redactor->asunto(TipoAviso::CompletadoGente, Papel::Superior, $c));
    }

    public function test_comentado_y_reabierto(): void
    {
        $c = new ContextoDelAviso('Certificado N.º 3', null, 'Diego', 'Mandame la medición de la semana 42.');

        $this->assertSame("Diego comentó en tu tarea Certificado N.º 3: 'Mandame la medición de la semana 42.'", $this->redactor->texto(TipoAviso::Comentado, Papel::Solicitante, $c));
        $this->assertSame("Diego comentó en Certificado N.º 3: 'Mandame la medición de la semana 42.'", $this->redactor->texto(TipoAviso::Comentado, Papel::Propietario, $c));
        $this->assertSame("Diego reabrió Certificado N.º 3 (falta algo): 'Mandame la medición de la semana 42.'", $this->redactor->texto(TipoAviso::Reabierto, Papel::Propietario, $c));
        $this->assertSame('Diego reabrió una tarea que completaste: Certificado N.º 3', $this->redactor->asunto(TipoAviso::Reabierto, Papel::Propietario, $c));
    }

    public function test_comentario_largo_se_recorta_a_200_caracteres(): void
    {
        $extracto = RedactorDeAvisos::extracto(str_repeat('ñ', 600));

        $this->assertSame(str_repeat('ñ', 200).'…', $extracto);
        $this->assertNull(RedactorDeAvisos::extracto('   '));
    }
}
