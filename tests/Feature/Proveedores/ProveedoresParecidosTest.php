<?php

declare(strict_types=1);

namespace Tests\Feature\Proveedores;

/** F-56 · W-38 · HU-021 crit. 3 (RN-PRV-08/09): "Parecidos ya cargados". */
final class ProveedoresParecidosTest extends ProveedoresTestCase
{
    public function test_nombre_sin_forma_societaria_es_parecido(): void
    {
        $this->proveedor('Hormigonera Sur SRL');
        $this->proveedor('Hormigones del Sur');
        $this->proveedor('Corralón Centro');

        $parecidos = $this->parecidos('nombre=Hormigonera del Sur');

        $this->assertSame(['Hormigonera Sur SRL', 'Hormigones del Sur'], array_column($parecidos, 'nombre'));
        $this->assertSame(['nombre', 'nombre'], array_column($parecidos, 'motivo'));
    }

    public function test_nombre_contenido_es_parecido(): void
    {
        $this->proveedor('Corralón Centro Bahía Ejemplo');

        $this->assertSame(['Corralón Centro Bahía Ejemplo'], array_column($this->parecidos('nombre=Corralon Centro'), 'nombre'));
        $this->assertSame([], $this->parecidos('nombre=Cor'));
    }

    public function test_mitad_de_palabras_en_comun_es_parecido(): void
    {
        $this->proveedor('Alquileres Andamios Oeste');
        $this->proveedor('Hotel Puerto Ejemplo');

        $this->assertSame(['Alquileres Andamios Oeste'], array_column($this->parecidos('nombre=Andamios Oeste Norte'), 'nombre'));
    }

    public function test_ochenta_por_ciento_de_letras_es_parecido(): void
    {
        $this->proveedor('Ferretería Norte');

        $this->assertSame(['Ferretería Norte'], array_column($this->parecidos('nombre=Ferreteria Nortes'), 'nombre'));
        $this->assertSame([], $this->parecidos('nombre=Pinturería Sur'));
    }

    public function test_mismo_cuit_es_parecido_y_va_primero(): void
    {
        $this->proveedor('Corralón Centro', '30000000007');
        $this->proveedor('Corralon Centro Sucursal');

        $parecidos = $this->parecidos('nombre=Corralon Centro Norte&cuit=30-00000000-7');

        $this->assertSame(['Corralón Centro', 'Corralon Centro Sucursal'], array_column($parecidos, 'nombre'));
        $this->assertSame(['cuit', 'nombre'], array_column($parecidos, 'motivo'));
        $this->assertSame('30-00000000-7', $parecidos[0]['cuit']);
    }

    public function test_al_editar_no_se_muestra_a_si_mismo(): void
    {
        $transportes = $this->proveedor('Transportes Ejemplo');
        $this->proveedor('Transporte Ejemplo Sur');

        $parecidos = $this->parecidos("nombre=Transporte Ejemplo&excluir_id={$transportes->id}");

        $this->assertNotContains($transportes->id, array_column($parecidos, 'id'));
        $this->assertSame(['Transporte Ejemplo Sur'], array_column($parecidos, 'nombre'));
    }

    public function test_devuelve_hasta_cinco_ordenados(): void
    {
        foreach (['Hormigonera Sur', 'Hormigonera Sur Dos', 'Hormigonera Sur Tres', 'Hormigonera Sures', 'Hormigoneras Sur', 'Hormigonera Sur Cuatro Cinco'] as $nombre) {
            $this->proveedor($nombre);
        }

        $parecidos = $this->parecidos('nombre=Hormigonera Sur SA');

        $this->assertCount(5, $parecidos);
        $this->assertSame('Hormigonera Sur', $parecidos[0]['nombre']);
        $this->assertSame(['Hormigoneras Sur', 'Hormigonera Sures'], [$parecidos[1]['nombre'], $parecidos[2]['nombre']]);
    }

    public function test_sin_nombre_ni_cuit_no_hay_parecidos(): void
    {
        $this->proveedor('Corralón Centro', '30000000007');

        $this->assertSame([], $this->parecidos(''));
        $this->assertSame([], $this->parecidos('cuit=30-00000000-1'));
    }

    public function test_parecidos_exige_proveedores_ver(): void
    {
        $this->actingAs($this->sinAcceso())->getJson('/api/proveedores/parecidos?nombre=corralon')->assertForbidden();
        $this->actingAs($this->quienVe())->getJson('/api/proveedores/parecidos?nombre=corralon')->assertOk();
    }

    /**
     * @return list<array{id: int, nombre: string, cuit: string|null, motivo: string}>
     */
    private function parecidos(string $query): array
    {
        /** @var list<array{id: int, nombre: string, cuit: string|null, motivo: string}> $data */
        $data = $this->actingAs($this->quienEdita())->getJson('/api/proveedores/parecidos?'.$query)->assertOk()->json('data');

        return $data;
    }
}
