<?php

declare(strict_types=1);

namespace App\Modules\Obras\Application\Obras;

use App\Modules\Identidad\Domain\Enums\ClaveFuncion;
use App\Modules\Identidad\Domain\Models\User;
use App\Modules\Obras\Application\Acceso\AccesoObra;
use App\Modules\Obras\Application\Presupuestos\NumeroUnico;
use App\Modules\Obras\Application\Presupuestos\RegistrarPresupuesto;
use App\Modules\Obras\Domain\Enums\ObraEstado;
use App\Modules\Obras\Domain\Events\ObraCreada;
use App\Modules\Obras\Domain\Models\Obra;
use App\Modules\Obras\Domain\Models\ObraContacto;
use App\Shared\Actividad\RegistroActividad;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Connection;
use Illuminate\Validation\ValidationException;

/**
 * F-16 / HU-012: alta de obra en una sola operación (RN-OBR-19): obra, presupuesto principal,
 * contactos, adjuntos y adicionales. Nace Confirmada (RN-OBR-18). Si algo falla no se crea nada y
 * los adjuntos siguen temporales.
 */
final readonly class CrearObraAction
{
    public const ACCION = 'creado';

    public function __construct(
        private Connection $db,
        private AccesoObra $acceso,
        private NumeroUnico $numeros,
        private RegistrarPresupuesto $presupuestos,
        private RegistroActividad $actividad,
        private Dispatcher $eventos,
    ) {}

    /**
     * @throws ValidationException
     */
    public function handle(User $actor, DatosObra $datos): Obra
    {
        // obras.md §7: con alcance "propias" solo puede ponerse a sí mismo como responsable.
        if ($this->acceso->soloPropias($actor, ClaveFuncion::Obras) && $datos->responsableId !== $actor->id) {
            throw ValidationException::withMessages(['responsable_id' => ['Solo podés ponerte a vos como responsable de la obra.']]);
        }

        return $this->db->transaction(function () use ($actor, $datos): Obra {
            $this->numeros->exigirDistintos(self::numerosPorCampo($datos));

            $obra = Obra::query()->create([
                'nombre_trabajo' => $datos->nombreTrabajo,
                'cliente' => $datos->cliente,
                'planta' => $datos->planta,
                'responsable_id' => $datos->responsableId,
                'estado' => ObraEstado::Confirmada,
                'fecha_inicio_prevista' => $datos->fechaInicioPrevista,
                'fecha_fin_prevista' => $datos->fechaFinPrevista,
                'carpeta_drive_url' => $datos->carpetaDriveUrl,
                'created_by' => $actor->id,
            ]);

            $this->actividad->registrar($obra, self::ACCION);
            $this->presupuestos->handle($obra, $datos->principal, true, $actor->id, 'presupuesto');

            foreach ($datos->contactos as $tipo => $contacto) {
                ObraContacto::query()->create(['obra_id' => $obra->id, 'tipo' => $tipo, ...$contacto]);
            }

            foreach ($datos->adicionales as $indice => $adicional) {
                $this->presupuestos->handle($obra, $adicional, false, $actor->id, "adicionales.{$indice}");
            }

            $this->eventos->dispatch(new ObraCreada($obra->id));

            return $obra;
        });
    }

    /**
     * @return array<string, string>
     */
    private static function numerosPorCampo(DatosObra $datos): array
    {
        $numeros = ['presupuesto.numero' => $datos->principal->numero];

        foreach ($datos->adicionales as $indice => $adicional) {
            $numeros["adicionales.{$indice}.numero"] = $adicional->numero;
        }

        return $numeros;
    }
}
