<?php

declare(strict_types=1);

namespace Tests\Feature\Shared\Adjuntos\Support;

use GdImage;
use Illuminate\Http\UploadedFile;
use RuntimeException;
use ZipArchive;

/**
 * Archivos con contenido real (cabeceras de verdad) para probar la detección de tipo por contenido.
 */
final class ArchivosDePrueba
{
    private const LADO_IMAGEN = 4;

    private const BYTES_CABECERA_OLE = 504;

    public static function pdf(string $nombre = 'oferta-ejemplo.pdf'): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($nombre, "%PDF-1.4\n1 0 obj\n<<>>\nendobj\ntrailer\n<<>>\n%%EOF\n");
    }

    public static function heic(string $nombre = 'certificado-1.heic'): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($nombre, "\x00\x00\x00\x18ftypheic\x00\x00\x00\x00mif1heic".str_repeat("\x00", 64));
    }

    public static function png(): UploadedFile
    {
        return self::imagen('foto.png', static fn (GdImage $i): bool => imagepng($i));
    }

    public static function jpg(): UploadedFile
    {
        return self::imagen('foto.jpg', static fn (GdImage $i): bool => imagejpeg($i));
    }

    public static function webp(): UploadedFile
    {
        return self::imagen('foto.webp', static fn (GdImage $i): bool => imagewebp($i));
    }

    public static function xlsx(): UploadedFile
    {
        return self::zip('planilla.xlsx', ['[Content_Types].xml' => '<Types/>', 'xl/workbook.xml' => '<workbook/>']);
    }

    public static function docx(): UploadedFile
    {
        return self::zip('nota.docx', ['[Content_Types].xml' => '<Types/>', 'word/document.xml' => '<document/>']);
    }

    public static function zipRenombradoADocx(): UploadedFile
    {
        return self::zip('nota.docx', ['leeme.txt' => 'hola']);
    }

    public static function xls(): UploadedFile
    {
        return self::ole('planilla-vieja.xls', 'Workbook');
    }

    public static function doc(): UploadedFile
    {
        return self::ole('nota-vieja.doc', 'WordDocument');
    }

    public static function csv(): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('materiales.csv', "codigo,descripcion,cantidad\nA1,Cable,10\nA2,Caño,4\n");
    }

    public static function txt(): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('notas.txt', "Entregar en planta el lunes.\n");
    }

    public static function exeRenombradoAPdf(): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('certificado.pdf', "MZ\x90\x00\x03\x00\x00\x00\x04\x00\x00\x00\xff\xff\x00\x00".str_repeat("\x00", 200));
    }

    public static function html(): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('pagina.html', '<!DOCTYPE html><html><body><script>alert(1)</script></body></html>');
    }

    public static function svg(): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('dibujo.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>');
    }

    public static function htmlRenombradoATxt(): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('notas.txt', "hola\n<script>alert(1)</script>\n");
    }

    /** Un PDF que dice pesar `$megas` MB (el tamaño se controla antes que el contenido). */
    public static function pdfDe(int $megas): UploadedFile
    {
        return UploadedFile::fake()->create('pesado.pdf', $megas * 1024, 'application/pdf');
    }

    /**
     * @param  callable(GdImage): bool  $codificar
     */
    private static function imagen(string $nombre, callable $codificar): UploadedFile
    {
        $imagen = imagecreatetruecolor(self::LADO_IMAGEN, self::LADO_IMAGEN);
        ob_start();
        $codificar($imagen);

        return UploadedFile::fake()->createWithContent($nombre, (string) ob_get_clean());
    }

    /**
     * @param  array<string, string>  $entradas
     */
    private static function zip(string $nombre, array $entradas): UploadedFile
    {
        $path = (string) tempnam(sys_get_temp_dir(), 'adj');
        $zip = new ZipArchive;

        if ($zip->open($path, ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('No se pudo crear el zip de prueba.');
        }

        foreach ($entradas as $entrada => $contenido) {
            $zip->addFromString($entrada, $contenido);
        }

        $zip->close();
        $contenido = (string) file_get_contents($path);
        unlink($path);

        return UploadedFile::fake()->createWithContent($nombre, $contenido);
    }

    private static function ole(string $nombre, string $flujo): UploadedFile
    {
        $cabecera = "\xD0\xCF\x11\xE0\xA1\xB1\x1A\xE1".str_repeat("\x00", self::BYTES_CABECERA_OLE);
        $utf16 = implode("\0", str_split($flujo))."\0";

        return UploadedFile::fake()->createWithContent($nombre, $cabecera.$utf16.str_repeat("\x00", 64));
    }
}
