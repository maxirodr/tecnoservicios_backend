<?php

declare(strict_types=1);

namespace App\Modules\Obras\Application\Historial;

use App\Modules\Obras\Application\Montos\CargarMontosAction;
use App\Modules\Obras\Application\Montos\CatalogoRubros;
use App\Modules\Obras\Domain\Enums\AprobacionTipo;
use App\Modules\Obras\Domain\Enums\TipoContacto;
use App\Modules\Obras\Domain\Models\ObraDocumento;
use App\Modules\Obras\Domain\Models\Presupuesto;

/** Nombres de los campos y valores del historial como los dice el cliente (W-23). */
final readonly class EtiquetasHistorial
{
    public const VACIO = '(vacío)';

    private const CAMPOS = [
        'nombre_trabajo' => 'Trabajo',
        'cliente' => 'Cliente',
        'planta' => 'Planta',
        'responsable' => 'Responsable de obra',
        'fecha_inicio_prevista' => 'Inicio previsto',
        'fecha_fin_prevista' => 'Fin previsto',
        'carpeta_drive_url' => 'Carpeta de Drive',
        'numero' => 'N.º de presupuesto',
        'revision' => 'Revisión',
        'descripcion' => 'Descripción',
        'aprobacion_tipo' => 'Documento de aprobación',
        'aprobacion_numero' => 'N.º de OC',
        'modo_carga' => 'Presupuesto por rubro',
        'monto_obra' => 'Monto del presupuesto',
        'nombre' => 'Nombre',
        'fecha' => 'Fecha',
        CargarMontosAction::CLAVE_TOTAL => 'Total',
    ];

    private const CONTACTO = ['nombre' => 'nombre', 'email' => 'mail', 'telefono' => 'teléfono'];

    private const CONTEXTOS = [
        Presupuesto::CONTEXTO_OFERTA => 'Oferta técnica',
        Presupuesto::CONTEXTO_APROBACION => 'Archivo o link de la aprobación',
        ObraDocumento::CONTEXTO => 'Archivo o link',
    ];

    private const MODOS = ['sin_cargar' => 'sin cargar', 'por_rubro' => 'por rubro', 'solo_total' => 'solo el total'];

    private const LINKS = ['carpeta_drive_url'];

    public function __construct(private CatalogoRubros $rubros) {}

    public function campo(string $campo): string
    {
        if (preg_match('/^contacto_(\w+)\.(\w+)$/', $campo, $partes) === 1) {
            $tipo = TipoContacto::tryFrom($partes[1])?->etiqueta() ?? 'Contacto';

            return $tipo.': '.(self::CONTACTO[$partes[2]] ?? $partes[2]);
        }

        return self::CAMPOS[$campo] ?? $this->rubros->todos()->firstWhere('clave', $campo)->nombre ?? $campo;
    }

    public function contexto(string $contexto): string
    {
        return self::CONTEXTOS[$contexto] ?? 'Adjunto';
    }

    public function esLink(string $campo): bool
    {
        return in_array($campo, self::LINKS, true);
    }

    public function valor(string $campo, mixed $valor): string
    {
        if ($valor === null || $valor === '') {
            return self::VACIO;
        }

        $texto = is_scalar($valor) ? (string) $valor : '';

        return match ($campo) {
            'aprobacion_tipo' => AprobacionTipo::tryFrom($texto)?->etiqueta() ?? $texto,
            'modo_carga' => self::MODOS[$texto] ?? $texto,
            default => $texto,
        };
    }
}
