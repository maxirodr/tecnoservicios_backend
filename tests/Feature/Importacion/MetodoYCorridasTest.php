<?php

declare(strict_types=1);

namespace Tests\Feature\Importacion;

use App\Modules\Gastos\Contracts\CargaInicialDeGastos;
use App\Modules\Gastos\Contracts\CargaInicialVigente;
use App\Modules\Gastos\Domain\Events\GastoImputado;
use App\Modules\Gastos\Domain\Models\Gasto;
use App\Modules\Importacion\Application\Corridas\CorrerCarga;
use App\Modules\Importacion\Application\Corridas\HojasDelLote;
use App\Modules\Importacion\Domain\Lote;
use App\Modules\Importacion\Infrastructure\Models\Corrida;
use App\Modules\Importacion\Infrastructure\Models\FilaImportada;
use App\Modules\Importacion\Infrastructure\Xlsx\LectorXlsx;
use App\Modules\Obras\Domain\Events\ObraCreada;
use App\Modules\Obras\Domain\Events\ObraEstadoCambiado;
use App\Modules\Obras\Domain\Events\PresupuestoMontosCambiados;
use App\Modules\Obras\Domain\Models\Obra;
use App\Modules\Rendicion\Domain\Events\MaterialCambiado;
use App\Shared\Actividad\Actividad;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use RuntimeException;
use Tests\Feature\Importacion\Support\ImportacionTestCase;
use Tests\Feature\Importacion\Support\PlantillasDelCasoDeReferencia;
use ZipArchive;

/** El método (`importacion.md` §6.1, §6.2, RN-IMP-01…14): comandos, ensayo, confirmación, huella, carpeta y logs. */
final class MetodoYCorridasTest extends ImportacionTestCase
{
    use PlantillasDelCasoDeReferencia;

    protected function setUp(): void
    {
        parent::setUp();

        $this->prepararCasoDeReferencia();
    }

    public function test_no_hay_rutas_de_la_api_para_la_importacion(): void
    {
        $rutas = array_filter(array_map(static fn ($r): string => $r->uri(), Route::getRoutes()->getRoutes()), static fn (string $u): bool => str_contains($u, 'importacion'));

        $this->assertSame([], array_values($rutas));
    }

    public function test_ensayo_no_guarda_nada_ni_dispara_eventos(): void
    {
        $actividades = Actividad::query()->count();
        $eventos = [];
        Event::listen(
            [GastoImputado::class, ObraCreada::class, ObraEstadoCambiado::class, MaterialCambiado::class, PresupuestoMontosCambiados::class],
            static function (object $e) use (&$eventos): void {
                $eventos[] = $e::class;
            },
        );

        [$codigo] = $this->correr('importacion:obras', ['archivo' => 'obras.xlsx']);

        $this->assertSame(0, $codigo);
        $this->assertSame([0, 0, $actividades], [Obra::query()->count(), Gasto::query()->count(), Actividad::query()->count()]);
        $this->assertSame([], $eventos, 'Los eventos salen después del commit: el ensayo no tiene');
        $corrida = Corrida::query()->sole();
        $this->assertSame(['ensayo', 'terminada', null], [$corrida->modo->value, $corrida->estado->value, $corrida->aprobo_id]);
        $this->assertSame(hash_file('sha256', $this->carpeta.'/obras.xlsx'), $corrida->huella);
        $this->assertFileExists($this->carpeta.'/'.$corrida->informe);
        $this->assertEquals(['obras' => 3, 'guardadas' => 2, 'ya_estaban' => 0, 'con_faltantes' => 1, 'faltantes' => 2], $corrida->resumen);
    }

    public function test_confirmar_sin_ensayo_del_mismo_archivo_no_guarda(): void
    {
        $this->correr('importacion:obras', ['archivo' => 'obras.xlsx']);
        // Caso borde 13: se toca la plantilla después del ensayo (un espacio de más: otra huella).
        $hojas = $this->hojasDeObras();
        $hojas['Obras'][0]['Planta'] = 'Planta norte ';
        $this->xlsx('obras.xlsx', $hojas, ['Presupuestos' => self::columnasDePresupuestos()]);

        [$codigo, $salida] = $this->correr('importacion:obras', ['archivo' => 'obras.xlsx', '--confirmar' => true, '--aprobo' => 'carla@ejemplo.com']);

        $this->assertSame(1, $codigo);
        $this->assertStringContainsString('No se guardó nada: primero corré el ensayo de este archivo.', $salida);
        $this->assertSame(0, Obra::query()->count());
    }

    public function test_confirmar_exige_aprobo_activo_con_permiso(): void
    {
        $this->correr('importacion:obras', ['archivo' => 'obras.xlsx']);
        $this->persona('Pablo', 'pablo@ejemplo.com', 'Compras');
        $this->carla()->forceFill(['activo' => false])->save();

        [, $compras] = $this->correr('importacion:obras', ['archivo' => 'obras.xlsx', '--confirmar' => true, '--aprobo' => 'pablo@ejemplo.com']);
        [, $inactiva] = $this->correr('importacion:obras', ['archivo' => 'obras.xlsx', '--confirmar' => true, '--aprobo' => 'carla@ejemplo.com']);
        [$codigo, $tareas] = $this->correr('importacion:tareas', ['archivo' => 'tareas.xlsx', '--confirmar' => true, '--aprobo' => 'nadie@ejemplo.com']);

        $this->assertStringContainsString('No se guardó nada: pablo@ejemplo.com no puede aprobar la carga de obras.', $compras);
        $this->assertStringContainsString('No se guardó nada: carla@ejemplo.com no es un usuario activo.', $inactiva);
        $this->assertStringContainsString('No se guardó nada: nadie@ejemplo.com no es un usuario activo.', $tareas);
        $this->assertSame([1, 0], [$codigo, Obra::query()->count()]);
    }

    public function test_lo_importado_figura_cargado_por_quien_aprobo(): void
    {
        $this->correr('importacion:obras', ['archivo' => 'obras.xlsx']);
        $this->correr('importacion:obras', ['archivo' => 'obras.xlsx', '--confirmar' => true, '--aprobo' => 'carla@ejemplo.com']);
        $carla = $this->carla();

        $this->assertSame([$carla->id], Obra::query()->distinct()->pluck('created_by')->all());
        $this->assertSame([$carla->id], Gasto::query()->distinct()->pluck('cargado_por')->all());
        $this->assertSame([$carla->id], Actividad::query()->distinct()->pluck('user_id')->all());
        $this->assertSame(0, Actividad::query()->where('carga_inicial', false)->count());
        $this->assertSame($carla->id, Corrida::query()->where('modo', 'confirmada')->value('aprobo_id'));
    }

    public function test_importar_dos_veces_no_duplica(): void
    {
        $this->correr('importacion:obras', ['archivo' => 'obras.xlsx']);
        $this->correr('importacion:obras', ['archivo' => 'obras.xlsx', '--confirmar' => true, '--aprobo' => 'carla@ejemplo.com']);
        $this->correr('importacion:obras', ['archivo' => 'obras.xlsx', '--confirmar' => true, '--aprobo' => 'carla@ejemplo.com']);

        $this->assertSame([2, 2], [Obra::query()->count(), Gasto::query()->count()]);
        $segunda = Corrida::query()->where('modo', 'confirmada')->orderByDesc('id')->firstOrFail();
        $this->assertSame(
            ['gastado:1-200-2026' => 'ya_estaba', 'retirado:1-200-2026' => 'ya_estaba'],
            FilaImportada::query()->where('corrida_id', $segunda->id)->whereIn('hoja', ['Gastado', 'Materiales'])->pluck('resultado', 'clave')->map(fn ($r) => $r->value)->all(),
        );
    }

    public function test_una_corrida_a_la_vez(): void
    {
        $bloqueo = Cache::lock(CorrerCarga::BLOQUEO, 60);
        $bloqueo->get();

        [$codigo, $salida] = $this->correr('importacion:obras', ['archivo' => 'obras.xlsx']);
        $bloqueo->release();

        $this->assertSame(1, $codigo);
        $this->assertStringContainsString('Hay otra carga en curso. Esperá a que termine.', $salida);
        $this->assertSame(0, Corrida::query()->count());
    }

    public function test_una_corrida_que_quedo_en_curso_se_marca_interrumpida(): void
    {
        Corrida::query()->create(['lote' => 'obras', 'modo' => 'confirmada', 'huella' => str_repeat('a', 64), 'estado' => 'en_curso']);

        $this->correr('importacion:obras', ['archivo' => 'obras.xlsx']);

        $this->assertSame('interrumpida', Corrida::query()->orderBy('id')->firstOrFail()->estado->value);
    }

    public function test_solo_lee_archivos_de_la_carpeta_de_importacion(): void
    {
        [$codigo, $afuera] = $this->correr('importacion:obras', ['archivo' => '../obras.xlsx']);
        [, $inexistente] = $this->correr('importacion:obras', ['archivo' => base_path('composer.json')]);

        $this->assertSame(1, $codigo);
        $this->assertStringContainsString('El archivo tiene que estar en la carpeta de la importación.', $afuera);
        $this->assertStringContainsString('El archivo tiene que estar en la carpeta de la importación.', $inexistente);
    }

    public function test_falta_columna_obligatoria_no_empieza(): void
    {
        $hojas = $this->hojasDeObras();
        $hojas['Obras'] = array_map(static function (array $f): array {
            unset($f['Fecha de corte']);

            return $f;
        }, $hojas['Obras']);
        $this->xlsx('sin-corte.xlsx', $hojas, ['Presupuestos' => self::columnasDePresupuestos()]);

        [$codigo, $salida] = $this->correr('importacion:obras', ['archivo' => 'sin-corte.xlsx']);

        $this->assertSame(1, $codigo);
        $this->assertStringContainsString("Falta la columna 'Fecha de corte' en la hoja Obras.", $salida);
        $this->assertSame(0, Corrida::query()->count());
    }

    public function test_logs_no_tienen_valores_de_celdas(): void
    {
        $registros = [];
        Event::listen(MessageLogged::class, static function (MessageLogged $m) use (&$registros): void {
            $registros[] = $m->message.' '.json_encode($m->context, JSON_UNESCAPED_UNICODE);
        });

        [, $salida] = $this->correr('importacion:obras', ['archivo' => 'obras.xlsx']);

        foreach (['Ampliación', 'laura@ejemplo.com', '2.347.500', 'VARIOS', 'Planta Ejemplo'] as $valor) {
            $this->assertStringNotContainsString($valor, $salida.implode("\n", $registros));
        }
    }

    public function test_una_obra_que_falla_no_deja_nada_y_la_corrida_sigue(): void
    {
        $registros = [];
        Event::listen(MessageLogged::class, static function (MessageLogged $m) use (&$registros): void {
            $registros[] = [$m->message, $m->context];
        });
        $this->correr('importacion:obras', ['archivo' => 'obras.xlsx']);
        $this->app->bind(CargaInicialDeGastos::class, static fn (): CargaInicialDeGastos => new class implements CargaInicialDeGastos
        {
            public function cargar(int $actorId, int $obraId, string $fechaCorte, string $tipo, array $renglones, array $historial = []): int
            {
                throw new RuntimeException('Ampliación de nave de ensayo 1.250.000');
            }

            public function vigente(int $obraId, string $tipo): ?CargaInicialVigente
            {
                return null;
            }

            public function renglonesHastaFecha(int $obraId, string $fecha): int
            {
                return 0;
            }
        });

        [$codigo, $salida] = $this->correr('importacion:obras', ['archivo' => 'obras.xlsx', '--confirmar' => true, '--aprobo' => 'carla@ejemplo.com']);

        $this->assertSame(1, $codigo);
        $this->assertStringContainsString('Obra 1-200-2026: error inesperado, no se guardó nada de esta obra (ver informe).', $salida);
        $this->assertStringContainsString('1 obras guardadas, 0 ya estaban, 2 con faltantes', $salida);
        $this->assertSame(['Reparación de losa tanque B'], Obra::query()->pluck('nombre_trabajo')->all());
        $this->assertSame('con_errores', Corrida::query()->orderByDesc('id')->firstOrFail()->estado->value);
        $this->assertSame([['Carga inicial: error inesperado', ['corrida' => (int) Corrida::query()->max('id'), 'hoja' => 'Obras', 'fila' => 2, 'tipo' => RuntimeException::class]]], $registros);
    }

    public function test_plantilla_vacia_con_listas_y_como_completar(): void
    {
        [$codigo, $salida] = $this->correr('importacion:plantilla', ['lote' => 'obras']);
        $ruta = $this->carpeta.DIRECTORY_SEPARATOR.'plantilla-obras.xlsx';
        $zip = new ZipArchive;
        $zip->open($ruta);
        $obras = (string) $zip->getFromName('xl/worksheets/sheet1.xml');
        $rubros = (string) $zip->getFromName('xl/worksheets/sheet4.xml');
        $libro = (string) $zip->getFromName('xl/workbook.xml');
        $zip->close();

        $this->assertSame(0, $codigo, $salida);
        $this->assertStringContainsString('<dataValidation type="list"', $obras);
        $this->assertStringContainsString("'Listas'!", $rubros);
        foreach (['Obras', 'Presupuestos', 'Gastado', 'Rubros', 'Materiales', 'Listas', 'Cómo completar'] as $hoja) {
            $this->assertStringContainsString("name=\"{$hoja}\"", $libro);
        }

        $this->correr('importacion:plantilla', ['lote' => 'tareas']);
        $hojas = (new LectorXlsx)->leer(
            $this->carpeta.DIRECTORY_SEPARATOR.'plantilla-tareas.xlsx',
            $this->app->make(HojasDelLote::class)->de(Lote::Tareas),
        );
        $this->assertSame(['Tareas' => [], 'Personas' => []], $hojas, 'La plantilla vacía se lee sin faltar columnas');
    }
}
