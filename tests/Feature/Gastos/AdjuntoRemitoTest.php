<?php

declare(strict_types=1);

namespace Tests\Feature\Gastos;

use App\Modules\Gastos\Domain\Models\Gasto;
use App\Modules\Identidad\Domain\Models\User;
use App\Modules\Obras\Domain\Models\Obra;
use App\Shared\Adjuntos\Adjunto;
use Illuminate\Http\UploadedFile;
use Tests\Feature\Gastos\Support\GastosTestCase;

/**
 * Contexto de adjuntos `remito` (gastos.md §4.4, identidad.md §6.6; QC S3 M-2 y M-3): de 0 a 1 por retiro;
 * lo baja `gastos` ≥ ver o `control_cantidad` ≥ ver, y lo asocia o quita `gastos = editar` o
 * `control_cantidad = editar`, con alcance.
 */
final class AdjuntoRemitoTest extends GastosTestCase
{
    private Obra $nave;

    protected function setUp(): void
    {
        parent::setUp();

        $this->nave = $this->obra('Piso industrial nave 3');
    }

    public function test_control_cantidad_ver_baja_el_remito_sin_gastos(): void
    {
        $diego = $this->diego();
        $remito = $this->archivo($diego);
        $this->retiro($diego, [$remito]);
        $sofia = $this->sofia();

        $this->actingAs($sofia)->get("/api/adjuntos/{$remito}")->assertNotFound();
        $this->celda('Seguridad e Higiene', 'control_cantidad', 'ver');
        $this->actingAs($sofia)->get("/api/adjuntos/{$remito}")->assertOk();
    }

    public function test_control_cantidad_solo_sus_obras_no_baja_el_remito_de_otra_obra(): void
    {
        $diego = $this->diego();
        $remito = $this->archivo($diego);
        $this->retiro($diego, [$remito]);
        $this->celda('Seguridad e Higiene', 'control_cantidad', 'ver', 'propias');

        $this->actingAs($this->sofia())->get("/api/adjuntos/{$remito}")->assertNotFound();
    }

    public function test_un_retiro_lleva_como_maximo_un_remito(): void
    {
        $diego = $this->diego();

        $this->cargar($diego, $this->datosDelRetiro([$this->link($diego, 'remito'), $this->link($diego, 'remito', 'https://drive.example/remitos/2.pdf')]))
            ->assertUnprocessable()->assertJsonPath('errors.adjuntos.0', 'Son demasiados (máximo 1).');
    }

    public function test_control_cantidad_editar_quita_el_remito_y_con_ver_no(): void
    {
        $diego = $this->diego();
        $remito = $this->link($diego, 'remito');
        $this->retiro($diego, [$remito]);
        $this->celda('Seguridad e Higiene', 'control_cantidad', 'ver');
        $sofia = $this->sofia();

        $this->actingAs($sofia)->deleteJson("/api/adjuntos/{$remito}")->assertForbidden();
        $this->celda('Seguridad e Higiene', 'control_cantidad', 'editar');
        $this->actingAs($this->persona('Sofía B', 'Seguridad e Higiene'))->deleteJson("/api/adjuntos/{$remito}")->assertSuccessful();
        $this->assertFalse(Adjunto::query()->whereKey($remito)->exists());
    }

    public function test_control_cantidad_editar_asocia_el_remito_a_un_retiro_ya_cargado(): void
    {
        $retiro = $this->retiro($this->diego());
        $this->celda('Seguridad e Higiene', 'control_cantidad', 'editar');
        $sofia = $this->sofia();
        $remito = $this->link($sofia, 'remito');
        $version = $this->version($retiro);

        $this->actingAs($sofia)->patchJson("/api/gastos/{$retiro->id}", ['adjuntos' => [$remito], 'updated_at' => $version])
            ->assertOk()
            ->assertJsonPath('message', 'Guardamos los cambios.')
            ->assertJsonCount(1, 'data.adjuntos')
            ->assertJsonPath('data.adjuntos.0.id', $remito);
        $this->assertTrue(Adjunto::query()->whereKey($remito)->where('sujeto_id', $retiro->id)->exists());
    }

    public function test_control_cantidad_editar_no_cambia_otra_cosa_del_retiro(): void
    {
        $retiro = $this->retiro($this->diego());
        $this->celda('Seguridad e Higiene', 'control_cantidad', 'editar');

        $this->actingAs($this->sofia())->patchJson("/api/gastos/{$retiro->id}", ['numero' => '1-999'])->assertForbidden();
        $this->assertSame('0001-00000456', $retiro->fresh()?->numero);
    }

    public function test_control_cantidad_editar_no_asocia_remitos_fuera_de_sus_obras_ni_en_facturas(): void
    {
        $retiro = $this->retiro($this->diego());
        $factura = $this->cargado($this->diego(), $this->facturaDeW10($this->proveedor(), $this->nave, $this->nave));
        $this->celda('Seguridad e Higiene', 'control_cantidad', 'editar', 'propias');
        $sofia = $this->sofia();
        $remito = $this->link($sofia, 'remito');

        $this->actingAs($sofia)->patchJson("/api/gastos/{$retiro->id}", ['adjuntos' => [$remito]])->assertNotFound();
        $this->celda('Seguridad e Higiene', 'control_cantidad', 'editar');
        $this->actingAs($sofia)->patchJson("/api/gastos/{$factura->id}", ['adjuntos' => [$remito]])->assertNotFound();
        $this->assertTrue(Adjunto::query()->whereKey($remito)->whereNull('sujeto_id')->exists());
    }

    /**
     * @param  list<int>  $adjuntos
     * @return array<string, mixed>
     */
    private function datosDelRetiro(array $adjuntos = []): array
    {
        $cemento = $this->material($this->nave, 'Cemento x 50 kg');

        return [
            'tipo_documento' => 'sin_documento', 'origen' => 'retiro_remito', 'numero' => '1-456', 'fecha' => '2026-10-18',
            'proveedor_id' => $this->proveedor('Tecnobahía Ejemplo')->id, 'adjuntos' => $adjuntos,
            'renglones' => [$this->renglon('Cemento x 50 kg', '10', '9800.00', $this->nave, 'materiales', ['material_id' => $cemento->id])],
        ];
    }

    /**
     * @param  list<int>  $adjuntos
     */
    private function retiro(User $user, array $adjuntos = []): Gasto
    {
        return $this->cargado($user, $this->datosDelRetiro($adjuntos));
    }

    private function archivo(User $user): int
    {
        $id = $this->actingAs($user)->post('/api/adjuntos', [
            'contexto' => 'remito',
            'archivo' => UploadedFile::fake()->createWithContent('remito.pdf', "%PDF-1.4\n%prueba\n"),
        ], ['Accept' => 'application/json'])->assertCreated()->json('data.id');
        $this->assertIsInt($id);

        return $id;
    }

    private function version(Gasto $gasto): string
    {
        $version = $this->actingAs($this->diego())->getJson("/api/gastos/{$gasto->id}")->json('data.updated_at');
        $this->assertIsString($version);

        return $version;
    }
}
