<?php

declare(strict_types=1);

namespace App\Modules\Importacion\Application\Tareas;

use App\Modules\Identidad\Contracts\UsuarioConsultado;
use App\Modules\Identidad\Contracts\UsuariosConsulta;
use App\Modules\Importacion\Application\Lectura\Sugerencia;
use App\Modules\Importacion\Application\Lectura\TextosDeFaltantes as T;
use App\Modules\Importacion\Domain\Lectura\FilaLeida;
use App\Modules\Importacion\Domain\Lectura\Valores;
use App\Modules\Importacion\Domain\Plantilla\Columnas as C;

/**
 * Hoja Personas (§5.7, RN-IMP-15, DU-20): lo escrito en Propietario o Solicitante es esa persona si es **exactamente**
 * el mail de un usuario; si no, se busca en la hoja Personas. Nada se adivina: el sugerido de "Personas por revisar"
 * es solo una ayuda.
 */
final class PersonasDeLaMinuta
{
    /** @var array<string, string> como figura (sin mayúsculas ni tildes) => mail */
    private array $tabla = [];

    /** @var list<string>|null */
    private ?array $nombres = null;

    public function __construct(private readonly UsuariosConsulta $usuarios) {}

    /**
     * @param  list<FilaLeida>  $filas
     */
    public function cargar(array $filas): void
    {
        $this->tabla = [];

        foreach ($filas as $fila) {
            $como = $fila->texto(C::COMO_FIGURA_MINUTA);
            $mail = $fila->texto(C::MAIL_USUARIO);

            if ($como !== null && $mail !== null) {
                $this->tabla[Valores::clave($como)] = $mail;
            }
        }
    }

    /**
     * La persona o el motivo por el que no se encontró (el texto del faltante o del aviso).
     */
    public function buscar(string $escrito): UsuarioConsultado|string
    {
        $porMail = Valores::esMail($escrito) ? $this->usuarios->porMail($escrito) : null;

        if ($porMail !== null) {
            return $porMail;
        }

        $mail = $this->tabla[Valores::clave($escrito)] ?? null;

        if ($mail === null) {
            return "No encontramos a '{$escrito}' en la hoja Personas.";
        }

        return $this->usuarios->porMail($mail) ?? T::sinUsuario($mail);
    }

    /** RN-IMP-15: activo y de un sector con Tareas ≥ Ver; si no, el faltante. */
    public function motivoComoPropietario(UsuarioConsultado $persona): ?string
    {
        if (! $persona->activo) {
            return "{$persona->nombre} está inactiva: elegí a quién se la pasás.";
        }

        return $this->usuarios->sectorPuede($persona->sectorId, 'pedidos', 'ver') ? null : "{$persona->nombre} es de un sector sin acceso a Tareas.";
    }

    public function sugerido(string $escrito): string
    {
        $this->nombres ??= array_map(static fn (UsuarioConsultado $u): string => $u->nombre, $this->usuarios->activos());

        return Sugerencia::para($escrito, $this->nombres);
    }
}
