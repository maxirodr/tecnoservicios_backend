<?php

declare(strict_types=1);

namespace App\Shared\Idempotencia;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

/**
 * Huella del payload de un request: mismo cuerpo (y mismos archivos) → mismo hash,
 * sin importar el orden de las claves. Sirve para detectar una clave reusada con otros datos.
 */
final class RequestFingerprint
{
    private const ALGORITMO = 'sha256';

    public function hash(Request $request): string
    {
        $payload = [
            'input' => self::normalizar($request->input()),
            'files' => self::normalizar(self::huellasDeArchivos($request->allFiles())),
        ];

        return hash(self::ALGORITMO, (string) json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION));
    }

    /**
     * @param  array<array-key, mixed>  $files
     * @return array<array-key, mixed>
     */
    private static function huellasDeArchivos(array $files): array
    {
        $huellas = [];

        foreach ($files as $clave => $file) {
            $huellas[$clave] = match (true) {
                $file instanceof UploadedFile => (string) hash_file(self::ALGORITMO, $file->getPathname()),
                is_array($file) => self::huellasDeArchivos($file),
                default => null,
            };
        }

        return $huellas;
    }

    private static function normalizar(mixed $valor): mixed
    {
        if (! is_array($valor)) {
            return $valor;
        }

        if (! array_is_list($valor)) {
            ksort($valor);
        }

        return array_map(self::normalizar(...), $valor);
    }
}
