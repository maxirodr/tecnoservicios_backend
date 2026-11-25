<?php

declare(strict_types=1);

namespace Tests\Architecture;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

/**
 * DU-C01: en todo lo que ve el usuario, "pedido(s)" pasa a "tarea(s)". Revisa los textos de `app/`, `lang/` y las
 * vistas de mail. Las claves (`pedidos`, `pedido_id`, `pedidos.fecha_entrega`) no cuentan: no se ven.
 */
final class TextosDicenTareaNoPedidoTest extends TestCase
{
    private const PALABRA = '/(?<![\w.\/\-$])pedidos?(?![\w.\/\-])/iu';

    private const CLAVE = '/^[a-z0-9_.,\-\/:{}$*]*$/';

    /**
     * Textos que dicen "pedido" con otro sentido, archivo => texto.
     * Rendición: el material "pedido" es el que se compró para la obra (los renglones de la factura), no una tarea.
     */
    private const EXCEPCIONES = [
        'app/Modules/Rendicion/Application/Materiales/QuitarMaterialAction.php' => ['1 retiro o pedido', ' retiros o pedidos'],
    ];

    #[Test]
    public function ningun_texto_visible_dice_pedido_en_lugar_de_tarea(): void
    {
        $encontrados = [];

        foreach ($this->archivos() as $relativo => $ruta) {
            $contenido = (string) file_get_contents($ruta);
            $textos = str_ends_with($relativo, '.blade.php') ? [$this->textoDeVista($contenido)] : $this->textosDePhp($contenido);

            foreach ($textos as $texto) {
                if (preg_match(self::PALABRA, $texto) === 1 && preg_match(self::CLAVE, $texto) !== 1
                    && ! in_array($texto, self::EXCEPCIONES[$relativo] ?? [], true)) {
                    $encontrados[] = "{$relativo}: {$texto}";
                }
            }
        }

        $this->assertSame([], $encontrados, 'Para el usuario se dice "tarea" (DU-C01).');
    }

    #[Test]
    public function cada_excepcion_sigue_existiendo(): void
    {
        $base = dirname(__DIR__, 2);

        foreach (self::EXCEPCIONES as $relativo => $textos) {
            $contenido = (string) file_get_contents($base.'/'.$relativo);

            foreach ($textos as $texto) {
                $this->assertStringContainsString($texto, $contenido, "Excepción vieja en {$relativo}: sacala de la lista.");
            }
        }
    }

    /**
     * @return list<string>
     */
    private function textosDePhp(string $codigo): array
    {
        $textos = [];

        foreach (token_get_all($codigo) as $token) {
            if (is_array($token) && in_array($token[0], [T_CONSTANT_ENCAPSED_STRING, T_ENCAPSED_AND_WHITESPACE, T_INLINE_HTML], true)) {
                $textos[] = $token[0] === T_CONSTANT_ENCAPSED_STRING ? substr($token[1], 1, -1) : $token[1];
            }
        }

        return $textos;
    }

    private function textoDeVista(string $vista): string
    {
        return (string) preg_replace(['/\{\{.*?\}\}/s', '/@\w+(\s*\(.*?\))?/'], ' ', $vista);
    }

    /**
     * @return array<string, string> relativo => ruta
     */
    private function archivos(): array
    {
        $base = dirname(__DIR__, 2);
        $archivos = [];

        foreach (['app', 'lang', 'resources/views'] as $carpeta) {
            $iterador = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($base.'/'.$carpeta));

            /** @var SplFileInfo $archivo */
            foreach ($iterador as $archivo) {
                if ($archivo->isFile() && $archivo->getExtension() === 'php') {
                    $ruta = $archivo->getPathname();
                    $archivos[str_replace('\\', '/', substr($ruta, strlen($base) + 1))] = $ruta;
                }
            }
        }

        return $archivos;
    }
}
