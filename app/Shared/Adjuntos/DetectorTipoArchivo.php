<?php

declare(strict_types=1);

namespace App\Shared\Adjuntos;

use finfo;
use ZipArchive;

/**
 * Tipo del archivo **por contenido**, nunca por extensión (ADR-014): PDF, imágenes (JPG, PNG, WEBP,
 * HEIC/HEIF), Excel, Word, CSV y TXT. Devuelve el MIME detectado o null si no está permitido
 * (ejecutables, HTML, SVG y todo lo demás).
 */
final class DetectorTipoArchivo
{
    public const XLSX = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';

    public const DOCX = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';

    public const XLS = 'application/vnd.ms-excel';

    public const DOC = 'application/msword';

    private const DIRECTOS = [
        'application/pdf', 'image/jpeg', 'image/png', 'image/webp', 'image/heic', 'image/heif',
        'image/heic-sequence', 'image/heif-sequence', self::XLSX, self::DOCX, self::XLS, self::DOC,
    ];

    private const ZIP = ['application/zip', 'application/x-zip-compressed'];

    private const OLE = ['application/x-ole-storage', 'application/CDFV2', 'application/vnd.ms-office'];

    private const TEXTO = ['text/plain', 'text/csv'];

    /** Bytes del comienzo de un texto que se revisan buscando HTML o SVG camuflado. */
    private const BYTES_A_REVISAR_EN_TEXTO = 4096;

    private const MARCAS_DE_HTML = ['<html', '<!doctype', '<svg', '<script', '<body', '<iframe'];

    public function detectar(string $path, string $nombreOriginal): ?string
    {
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($path);

        return match (true) {
            $mime === false => null,
            in_array($mime, self::DIRECTOS, true) => $mime,
            in_array($mime, self::ZIP, true) => self::ooxml($path),
            in_array($mime, self::OLE, true) => self::officeBinario($path),
            in_array($mime, self::TEXTO, true) => self::texto($path, $mime, $nombreOriginal),
            default => null,
        };
    }

    /** XLSX y DOCX son ZIP: se reconocen por su estructura interna, no por el nombre. */
    private static function ooxml(string $path): ?string
    {
        $zip = new ZipArchive;

        if ($zip->open($path, ZipArchive::RDONLY) !== true) {
            return null;
        }

        $tipo = null;

        if ($zip->locateName('[Content_Types].xml') !== false) {
            for ($i = 0; $i < $zip->numFiles && $tipo === null; $i++) {
                $nombre = (string) $zip->getNameIndex($i);
                $tipo = match (true) {
                    str_starts_with($nombre, 'xl/') => self::XLSX,
                    str_starts_with($nombre, 'word/') => self::DOCX,
                    default => null,
                };
            }
        }

        $zip->close();

        return $tipo;
    }

    /** XLS y DOC viejos son contenedores OLE: se reconocen por el nombre de su flujo principal. */
    private static function officeBinario(string $path): ?string
    {
        $contenido = (string) file_get_contents($path);

        return match (true) {
            str_contains($contenido, self::utf16('Workbook')) || str_contains($contenido, self::utf16('Book')) => self::XLS,
            str_contains($contenido, self::utf16('WordDocument')) => self::DOC,
            default => null,
        };
    }

    private static function texto(string $path, string $mime, string $nombreOriginal): ?string
    {
        $inicio = strtolower((string) file_get_contents($path, false, null, 0, self::BYTES_A_REVISAR_EN_TEXTO));

        foreach (self::MARCAS_DE_HTML as $marca) {
            if (str_contains($inicio, $marca)) {
                return null;
            }
        }

        $esCsv = $mime === 'text/csv' || strtolower(pathinfo($nombreOriginal, PATHINFO_EXTENSION)) === 'csv';

        return $esCsv ? 'text/csv' : 'text/plain';
    }

    private static function utf16(string $texto): string
    {
        return implode("\0", str_split($texto))."\0";
    }
}
