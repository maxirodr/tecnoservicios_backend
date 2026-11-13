<?php

declare(strict_types=1);

namespace Tests\Architecture;

use PhpToken;

/**
 * Detecta violaciones de límites del monolito modular leyendo los nombres de clase
 * que referencia un archivo PHP (imports y nombres calificados).
 */
final class ModuleBoundaryScanner
{
    private const FORBIDDEN_IN_DOMAIN = [
        'Illuminate\\Http',
        'Illuminate\\Support\\Facades',
    ];

    /**
     * @return list<string>
     */
    public static function referencedNames(string $source): array
    {
        $names = [];
        foreach (PhpToken::tokenize($source) as $token) {
            if ($token->is([T_NAME_QUALIFIED, T_NAME_FULLY_QUALIFIED])) {
                $names[] = ltrim($token->text, '\\');
            }
        }

        return array_values(array_unique($names));
    }

    /**
     * @param  string  $module  módulo dueño del archivo (p. ej. "Obras")
     * @param  string  $layer  capa del archivo dentro del módulo (Http, Application, Domain, Infrastructure o '')
     * @return list<string> descripción de cada violación
     */
    public static function violations(string $source, string $module, string $layer): array
    {
        $violations = [];

        foreach (self::referencedNames($source) as $name) {
            if ($layer === 'Domain') {
                foreach (self::FORBIDDEN_IN_DOMAIN as $forbidden) {
                    if ($name === $forbidden || str_starts_with($name, $forbidden.'\\')) {
                        $violations[] = "Domain usa {$name}";
                    }
                }

                if (preg_match('/^App\\\\Modules\\\\\w+\\\\Http(\\\\|$)/', $name) === 1) {
                    $violations[] = "Domain usa la capa Http: {$name}";
                }

                // ADR-003 (enmienda 2026-11-13): Domain no usa Infrastructure, ni la propia.
                if (preg_match('/^App\\\\Modules\\\\\w+\\\\Infrastructure(\\\\|$)/', $name) === 1) {
                    $violations[] = "Domain usa la capa Infrastructure: {$name}";
                }
            }

            if (preg_match('/^App\\\\Modules\\\\(\w+)\\\\Infrastructure(\\\\|$)/', $name, $match) === 1
                && $match[1] !== $module) {
                $violations[] = "Importa Infrastructure del módulo {$match[1]}: {$name}";
            }
        }

        return $violations;
    }
}
