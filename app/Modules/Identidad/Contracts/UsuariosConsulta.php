<?php

declare(strict_types=1);

namespace App\Modules\Identidad\Contracts;

/**
 * Personas y sectores para la carga inicial (`importacion.md` §11.4, RN-IMP-04, 15, 33), por mail y por nombre,
 * sin que Importacion lea las tablas de Identidad. Las funciones y niveles son las claves de la matriz
 * (`pedidos`, `obras`… · `ver`, `editar`).
 */
interface UsuariosConsulta
{
    /** El mail se compara normalizado (minúsculas, sin espacios al borde). */
    public function porMail(string $mail): ?UsuarioConsultado;

    /**
     * Todos los usuarios activos, por nombre (para sugerir a quién corresponde un nombre de la minuta).
     *
     * @return list<UsuarioConsultado>
     */
    public function activos(): array;

    /** Sin mayúsculas, tildes ni espacios de más ("compras" = "Compras"). */
    public function sectorPorNombre(string $nombre): ?SectorConsultado;

    /**
     * @return list<SectorConsultado>
     */
    public function sectoresActivos(): array;

    /** La celda del sector de la persona alcanza el nivel; una persona inactiva no puede nada. */
    public function puede(int $userId, string $funcion, string $nivel): bool;

    /** La celda del sector (activo) alcanza el nivel, mire quien mire (RN-PED-03: "un sector con Tareas ≥ Ver"). */
    public function sectorPuede(int $sectorId, string $funcion, string $nivel): bool;
}
