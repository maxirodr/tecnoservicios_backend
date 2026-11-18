<?php

declare(strict_types=1);

use App\Shared\Adjuntos\NombreDeLink;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const ACCION_AGREGADO = 'adjunto_agregado';

    /**
     * PU-207: los links pegados sin "Nombre para mostrar" se guardaban con el dominio ("drive.example") y
     * el historial decía "Oferta técnica: agregado (drive.example)". Ahora se nombran por la última parte
     * del link (`NombreDeLink`, la regla del front). Esto recalcula los ya guardados: el nombre del adjunto
     * y la línea "agregado (…)" de su historial (la actividad guarda el nombre, no el link).
     *
     * Idempotente: solo toca los links cuyo nombre todavía es el dominio. La actividad se corrige solo si se
     * puede emparejar sin dudas con su link (misma obra/presupuesto/documento, mismo lugar y misma cantidad
     * de altas con ese dominio que links vigentes). Las de links ya quitados (sin fila en `adjuntos`) quedan
     * con el dominio: no hay de dónde sacar el nombre.
     */
    public function up(): void
    {
        $this->renombrar(
            static fn (stdClass $link): bool => self::esElDominio((string) $link->nombre, (string) $link->url),
            static fn (stdClass $link): string => NombreDeLink::de((string) $link->url),
        );
    }

    /** Vuelve al dominio los links que tienen el nombre calculado (no los que tienen un nombre propio). */
    public function down(): void
    {
        $this->renombrar(
            static fn (stdClass $link): bool => (string) $link->nombre === NombreDeLink::de((string) $link->url)
                && ! self::esElDominio((string) $link->nombre, (string) $link->url),
            static fn (stdClass $link): string => NombreDeLink::dominio((string) $link->url),
        );
    }

    /**
     * @param  Closure(stdClass): bool  $hayQueRenombrar
     * @param  Closure(stdClass): string  $nombreNuevo
     */
    private function renombrar(Closure $hayQueRenombrar, Closure $nombreNuevo): void
    {
        $grupos = [];

        DB::table('adjuntos')->where('tipo', 'link')->orderBy('id')
            ->each(static function (stdClass $link) use ($hayQueRenombrar, $nombreNuevo, &$grupos): void {
                if (! $hayQueRenombrar($link)) {
                    return;
                }

                $nuevo = $nombreNuevo($link);
                if ($nuevo !== (string) $link->nombre) {
                    DB::table('adjuntos')->where('id', $link->id)->update(['nombre' => $nuevo]);
                }

                // También los que no cambian (link sin camino): cuentan para emparejar las actividades.
                if ($link->sujeto_type !== null) {
                    $clave = implode('|', [$link->sujeto_type, $link->sujeto_id, $link->contexto, $link->nombre]);
                    $grupos[$clave] ??= ['link' => $link, 'nuevos' => []];
                    $grupos[$clave]['nuevos'][] = $nuevo;
                }
            });

        foreach ($grupos as $grupo) {
            $this->corregirHistorial($grupo['link'], $grupo['nuevos']);
        }
    }

    /**
     * @param  list<string>  $nuevos  nombres nuevos de los links del grupo, en el orden en que se agregaron
     */
    private function corregirHistorial(stdClass $link, array $nuevos): void
    {
        $actividades = DB::table('actividades')
            ->where('sujeto_type', $link->sujeto_type)
            ->where('sujeto_id', $link->sujeto_id)
            ->where('accion', self::ACCION_AGREGADO)
            ->orderBy('id')
            ->get(['id', 'cambios'])
            ->filter(static fn (stdClass $actividad): bool => self::lineaDelLink($actividad, $link) !== null)
            ->values();

        if ($actividades->count() !== count($nuevos)) {
            return;
        }

        foreach ($actividades as $i => $actividad) {
            $cambios = json_decode((string) $actividad->cambios, true);
            $linea = self::lineaDelLink($actividad, $link);
            if (! is_array($cambios) || $linea === null) {
                continue;
            }

            $cambios[$linea]['despues'] = $nuevos[$i];

            DB::table('actividades')->where('id', $actividad->id)->update([
                'cambios' => json_encode($cambios, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION),
            ]);
        }
    }

    /** Posición (lista `[{campo, antes, despues}]`) o clave (objeto viejo) de la línea de este link. */
    private static function lineaDelLink(stdClass $actividad, stdClass $link): int|string|null
    {
        $cambios = json_decode((string) $actividad->cambios, true);
        if (! is_array($cambios)) {
            return null;
        }

        foreach ($cambios as $clave => $cambio) {
            $campo = array_is_list($cambios) ? ($cambio['campo'] ?? null) : $clave;
            if ($campo === $link->contexto && ($cambio['antes'] ?? null) === null && ($cambio['despues'] ?? null) === $link->nombre) {
                return $clave;
            }
        }

        return null;
    }

    /** Como `esElDominio` de `nombreDeLink` del front: vacío, el dominio (legible o no) o el dominio sin `www.`. */
    private static function esElDominio(string $nombre, string $url): bool
    {
        $nombre = trim($nombre);
        $host = (string) parse_url($url, PHP_URL_HOST);

        return $nombre === ''
            || in_array($nombre, [$host, NombreDeLink::dominio($url)], true)
            || 'www.'.$nombre === $host;
    }
};
