<?php

declare(strict_types=1);

namespace App\Modules\Importacion\Application\Corridas;

use App\Modules\Identidad\Contracts\UsuarioConsultado;
use App\Modules\Identidad\Contracts\UsuariosConsulta;
use App\Modules\Importacion\Domain\Lote;

/**
 * Quién aprueba y figura en lo guardado (RN-IMP-04, 10): un usuario **activo** con `rendicion`, `obras` y
 * `estado_obra` = editar (lote obras) o `pedidos` = editar (lote tareas). El ensayo corre por los mismos casos de
 * uso, que necesitan un actor: el de `--aprobo` si se pasó, o el primer usuario activo que podría aprobar.
 */
final readonly class QuienAprueba
{
    private const FUNCIONES = [
        'obras' => ['rendicion', 'obras', 'estado_obra'],
        'tareas' => ['pedidos'],
    ];

    private const EDITAR = 'editar';

    public function __construct(private UsuariosConsulta $usuarios) {}

    /** La persona, o el motivo para "No se guardó nada: {motivo}." */
    public function quien(Lote $lote, bool $confirmar, ?string $aprobo): UsuarioConsultado|string
    {
        if ($aprobo === null || trim($aprobo) === '') {
            return $confirmar ? 'falta --aprobo={mail} de quien aprobó el informe' : $this->cualquiera($lote);
        }

        $persona = $this->usuarios->porMail($aprobo);

        if ($persona === null || ! $persona->activo) {
            return "{$aprobo} no es un usuario activo";
        }

        return $this->puede($persona, $lote) ? $persona : "{$aprobo} no puede aprobar la carga de {$lote->value}";
    }

    private function cualquiera(Lote $lote): UsuarioConsultado|string
    {
        foreach ($this->usuarios->activos() as $persona) {
            if ($this->puede($persona, $lote)) {
                return $persona;
            }
        }

        return "no hay un usuario activo que pueda aprobar la carga de {$lote->value}";
    }

    private function puede(UsuarioConsultado $persona, Lote $lote): bool
    {
        foreach (self::FUNCIONES[$lote->value] as $funcion) {
            if (! $this->usuarios->puede($persona->id, $funcion, self::EDITAR)) {
                return false;
            }
        }

        return true;
    }
}
