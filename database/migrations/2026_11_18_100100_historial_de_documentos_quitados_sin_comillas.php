<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const ACCION = 'documento_quitado';

    /** "'Certificado N.º 1' (certificado-1.pdf, 1 link)" → nombre + detalle opcional entre paréntesis. */
    private const CON_COMILLAS = "/^'(.+)'((?: \\(.*\\))?)$/su";

    /** Lo mismo sin comillas: el detalle es el último " (…)". */
    private const SIN_COMILLAS = '/^(.+?)((?: \\([^()]*\\))?)$/su';

    /**
     * PU-208: "Quitó el certificado 'Certificado N.º 1' (…)" pasa a ir sin comillas, como el resto del
     * historial (PU-128). La actividad guarda el texto armado, así que se corrigen las ya guardadas.
     * Idempotente: solo toca los textos que todavía empiezan y terminan el nombre con comillas.
     */
    public function up(): void
    {
        $this->reescribir(static fn (string $antes): string => (string) preg_replace(self::CON_COMILLAS, '$1$2', $antes));
    }

    public function down(): void
    {
        $this->reescribir(static fn (string $antes): string => str_starts_with($antes, "'")
            ? $antes
            : (string) preg_replace(self::SIN_COMILLAS, "'\$1'\$2", $antes));
    }

    /**
     * @param  Closure(string): string  $texto
     */
    private function reescribir(Closure $texto): void
    {
        DB::table('actividades')->where('accion', self::ACCION)->orderBy('id')
            ->each(static function (stdClass $actividad) use ($texto): void {
                $cambios = json_decode((string) $actividad->cambios, true);
                if (! is_array($cambios)) {
                    return;
                }

                $nuevos = $cambios;
                foreach ($nuevos as $clave => $cambio) {
                    if (is_array($cambio) && is_string($cambio['antes'] ?? null)) {
                        $nuevos[$clave]['antes'] = $texto($cambio['antes']);
                    }
                }

                if ($nuevos !== $cambios) {
                    DB::table('actividades')->where('id', $actividad->id)->update([
                        'cambios' => json_encode($nuevos, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION),
                    ]);
                }
            });
    }
};
