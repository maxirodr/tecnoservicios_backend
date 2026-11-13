<?php

declare(strict_types=1);

namespace Tests\Feature\Identidad;

use App\Modules\Identidad\Contracts\Jerarquia;

/** F-61 (dato y cálculo de "mi gente"), RN-IDE-23, caso borde 15 y 20. */
final class JerarquiaTest extends IdentidadTestCase
{
    public function test_mi_gente_incluye_subordinados_indirectos(): void
    {
        $hernan = $this->usuarioDe('Jefatura de Proyectos', ['nombre' => 'Hernán']);
        $laura = $this->usuarioDe('Ingeniería de proyectos', ['nombre' => 'Laura', 'jefe_id' => $hernan->id]);
        $sofia = $this->usuarioDe('Ingeniería de proyectos', ['nombre' => 'Sofía', 'jefe_id' => $hernan->id]);
        $pablo = $this->usuarioDe('Ingeniería de proyectos', ['nombre' => 'Pablo', 'jefe_id' => $sofia->id]);
        $this->usuarioDe('Compras');

        $gente = $this->app->make(Jerarquia::class)->subordinados($hernan);

        $this->assertSame([$laura->id, $sofia->id, $pablo->id], $gente);
        $this->assertSame([], $this->app->make(Jerarquia::class)->subordinados($pablo));
    }

    public function test_superior_es_el_jefe_directo(): void
    {
        $hernan = $this->usuarioDe('Jefatura de Proyectos');
        $sofia = $this->usuarioDe('Ingeniería de proyectos', ['jefe_id' => $hernan->id]);
        $pablo = $this->usuarioDe('Ingeniería de proyectos', ['jefe_id' => $sofia->id]);

        $jerarquia = $this->app->make(Jerarquia::class);

        $this->assertSame($sofia->id, $jerarquia->superior($pablo)?->id);
        $this->assertNull($jerarquia->superior($hernan));
    }

    public function test_superior_para_aviso_de_completado_es_el_jefe_del_solicitante(): void
    {
        // Caso borde 20: Pablo (Ingeniería, depende de Sofía) completa un pedido de Carla (Compras, depende de Diego).
        $diego = $this->usuarioDe('Gerencia de Administración', ['nombre' => 'Diego']);
        $sofia = $this->usuarioDe('Ingeniería de proyectos', ['nombre' => 'Sofía']);
        $carla = $this->usuarioDe('Compras', ['nombre' => 'Carla', 'jefe_id' => $diego->id]);
        $this->usuarioDe('Ingeniería de proyectos', ['nombre' => 'Pablo', 'jefe_id' => $sofia->id]);

        $superior = $this->app->make(Jerarquia::class)->superior($carla);

        $this->assertSame($diego->id, $superior?->id);
        $this->assertNotSame($sofia->id, $superior->id);
    }
}
