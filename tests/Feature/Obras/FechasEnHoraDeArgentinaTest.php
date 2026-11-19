<?php

declare(strict_types=1);

namespace Tests\Feature\Obras;

use Illuminate\Support\Carbon;
use Tests\Feature\Obras\Support\ObrasTestCase;

/** PU-302: las fechas "del día" que arma el backend son las de Argentina, no las de UTC. */
final class FechasEnHoraDeArgentinaTest extends ObrasTestCase
{
    public function test_cargado_a_las_22_30_de_argentina_dice_ese_dia_y_no_el_siguiente(): void
    {
        $this->travelTo(Carbon::parse('2026-11-20 01:30:00', 'UTC'));
        $laura = $this->laura();
        $obra = $this->crearObra($laura);

        $this->actingAs($laura)->getJson("/api/obras/{$obra->id}")
            ->assertOk()
            ->assertJsonPath('data.presupuestos.0.created_at', '2026-11-19');
    }
}
