<?php

declare(strict_types=1);

namespace App\Shared\Adjuntos;

/**
 * Tamaño máximo de un archivo que este servidor deja subir (HU-025 criterio 3). Es el menor entre
 * `adjuntos.max_mb` (20 MB) y lo que aceptan `upload_max_filesize` y `post_max_size` de PHP: si PHP
 * corta antes que la aplicación (seguridad.md §6), la persona igual lee el máximo real en MB y no
 * un "solicitud no válida".
 */
final readonly class LimiteDeSubida
{
    private const BYTES_POR_MB = 1048576;

    private const MULTIPLICADORES = ['K' => 1024, 'M' => self::BYTES_POR_MB, 'G' => 1073741824];

    /**
     * @param  string  $uploadMaxFilesize  valor de php.ini, p. ej. "8M"
     * @param  string  $postMaxSize  valor de php.ini; "0" = sin límite
     */
    public function __construct(
        private int $maximoMb,
        private string $uploadMaxFilesize,
        private string $postMaxSize,
    ) {}

    public static function delServidor(): self
    {
        return new self(
            (int) config('adjuntos.max_mb'),
            (string) ini_get('upload_max_filesize'),
            (string) ini_get('post_max_size'),
        );
    }

    /** MB enteros que se pueden subir de verdad en este servidor. */
    public function maximoMb(): int
    {
        $limites = [$this->maximoMb * self::BYTES_POR_MB];

        foreach ([$this->uploadMaxFilesize, $this->postMaxSize] as $ini) {
            $bytes = self::bytes($ini);

            if ($bytes > 0) {
                $limites[] = $bytes;
            }
        }

        return max(1, intdiv(min($limites), self::BYTES_POR_MB));
    }

    /**
     * "El archivo pesa 25 MB y el máximo es 20 MB." si se sabe cuánto pesa; si PHP lo descartó sin
     * decir el tamaño, "El archivo pesa más de 20 MB, que es el máximo."
     */
    public function mensaje(?int $bytes = null): string
    {
        $maximo = $this->maximoMb();

        // Sin el peso, o con uno que no supera el máximo (no debería cortarse), no se inventa el número.
        if ($bytes === null || $bytes <= $maximo * self::BYTES_POR_MB) {
            return "El archivo pesa más de {$maximo} MB, que es el máximo.";
        }

        return 'El archivo pesa '.self::enMb($bytes)." MB y el máximo es {$maximo} MB.";
    }

    /** 26214400 → "25"; 9017753 → "8,6" (como el resto de los mensajes de tamaño). */
    public static function enMb(int $bytes): string
    {
        $mb = number_format($bytes / self::BYTES_POR_MB, 1, ',', '.');

        return str_ends_with($mb, ',0') ? substr($mb, 0, -2) : $mb;
    }

    /** "8M" → 8388608 · "512K" → 524288 · "0" o vacío → 0 (sin límite). */
    private static function bytes(string $ini): int
    {
        $valor = strtoupper(trim($ini));

        if ($valor === '') {
            return 0;
        }

        $unidad = substr($valor, -1);
        $multiplicador = self::MULTIPLICADORES[$unidad] ?? 1;
        $numero = $multiplicador === 1 ? $valor : substr($valor, 0, -1);

        return (int) $numero * $multiplicador;
    }
}
