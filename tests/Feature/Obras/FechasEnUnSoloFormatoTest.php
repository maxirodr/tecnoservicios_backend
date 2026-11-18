<?php

declare(strict_types=1);

namespace Tests\Feature\Obras;

use Tests\Feature\Obras\Support\ObrasTestCase;

/** PU-209: los textos de fecha que arma el backend van siempre dd/mm/aaaa ("31/12/2099"). */
final class FechasEnUnSoloFormatoTest extends ObrasTestCase
{
    public function test_el_historial_del_certificado_muestra_la_fecha_dd_mm_aaaa(): void
    {
        $laura = $this->laura();
        $obra = $this->obraDe($laura);
        $documento = $this->actingAs($laura)->postJson("/api/obras/{$obra->id}/documentos", [
            'tipo' => 'certificado', 'nombre' => 'Certificado N.º 1', 'fecha' => '2026-11-18', 'adjuntos' => [$this->link($laura, 'documento_obra')],
        ])->assertCreated()->json('data.id');

        $this->actingAs($laura)->patchJson("/api/documentos/{$documento}", ['fecha' => '2099-12-31'])->assertOk();

        $this->actingAs($laura)->getJson("/api/obras/{$obra->id}/historial")
            ->assertJsonPath('data.0.que', 'Certificado N.º 1 · Fecha: 18/11/2026 → 31/12/2099');
    }

    public function test_una_fecha_mal_escrita_pide_dd_mm_aaaa(): void
    {
        $laura = $this->laura();
        $obra = $this->obraDe($laura);

        $this->actingAs($laura)->patchJson("/api/obras/{$obra->id}", ['fecha_fin_prevista' => '31-dic-2099'])
            ->assertUnprocessable()
            ->assertJsonPath('errors.fecha_fin_prevista.0', 'Escribí la fecha como dd/mm/aaaa, por ejemplo 31/12/2099.');
    }
}
