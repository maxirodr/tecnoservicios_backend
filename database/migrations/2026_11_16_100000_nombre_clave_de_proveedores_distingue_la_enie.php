<?php

declare(strict_types=1);

use App\Modules\Proveedores\Domain\NombreProveedor;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * PU-043 · DU-Q04: la clave única del nombre conserva la ñ ("Ñandú" ≠ "Nandu"). La columna pasa a
     * `utf8mb4_bin` (en `utf8mb4_unicode_ci` la ñ es igual a la n y el índice único los seguiría
     * juntando) y se recalcula la clave de todos los proveedores. `nombre_busqueda` y
     * `nombre_normalizado` no cambian: la búsqueda y los parecidos siguen tomando la ñ como n.
     */
    public function up(): void
    {
        Schema::table('proveedores', function (Blueprint $table): void {
            $table->string('nombre_clave', 150)->collation('utf8mb4_bin')->change();
        });

        $this->recalcularClaves(conEnie: true);
    }

    /** Si al volver dos proveedores quedan con la misma clave sin ñ, falla: hay que unificarlos antes. */
    public function down(): void
    {
        $this->recalcularClaves(conEnie: false);

        Schema::table('proveedores', function (Blueprint $table): void {
            $table->string('nombre_clave', 150)->collation('utf8mb4_unicode_ci')->change();
        });
    }

    private function recalcularClaves(bool $conEnie): void
    {
        DB::table('proveedores')->select(['id', 'nombre'])->orderBy('id')->each(static function (object $proveedor) use ($conEnie): void {
            $clave = NombreProveedor::clave((string) $proveedor->nombre);

            DB::table('proveedores')->where('id', $proveedor->id)->update([
                'nombre_clave' => $conEnie ? $clave : str_replace('ñ', 'n', $clave),
            ]);
        });
    }
};
