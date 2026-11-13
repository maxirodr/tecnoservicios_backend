<?php

declare(strict_types=1);

namespace Tests\Feature\Shared;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\ValidationRuleParser;
use ReflectionClass;
use Stringable;
use Tests\TestCase;

/**
 * Recorre todos los FormRequest de app/ y verifica contra lang/es/validation.php que cada regla del
 * framework que usan tenga mensaje en español y que cada campo tenga su nombre en palabras del cliente.
 * Las reglas propias (clases o closures) traen su mensaje y no pasan por acá.
 */
final class TraduccionesDeValidacionTest extends TestCase
{
    /** Modificadores que nunca generan un mensaje. */
    private const SIN_MENSAJE = ['sometimes', 'nullable', 'bail', 'exclude', 'exclude_if', 'exclude_unless', 'exclude_with', 'exclude_without'];

    /** Input de ejemplo para que las reglas que dependen de la forma (adicionales del alta) se armen. */
    private const INPUT_DE_EJEMPLO = ['adicionales' => [[]]];

    /** Hoy hay 27 FormRequest concretos: si el recorrido encuentra menos, se rompió el descubrimiento. */
    private const MINIMO_DE_FORM_REQUESTS = 27;

    public function test_el_idioma_de_la_aplicacion_es_espanol(): void
    {
        $this->assertSame('es', config('app.locale'));
        $this->assertSame('es', config('app.fallback_locale'));
        $this->assertDirectoryDoesNotExist(lang_path('en'));
    }

    public function test_cada_regla_que_usan_los_form_requests_tiene_mensaje_en_espanol(): void
    {
        $faltantes = [];

        foreach ($this->reglasPorFormRequest() as $clase => $reglas) {
            foreach ($reglas as $campo => $reglasDelCampo) {
                foreach ($this->nombresDeReglas($reglasDelCampo) as $regla) {
                    if (! Lang::has("validation.{$regla}", 'es', false)) {
                        $faltantes[] = "{$clase} · {$campo} · {$regla}";
                    }
                }
            }
        }

        $this->assertSame([], $faltantes, 'Reglas sin mensaje en lang/es/validation.php.');
    }

    public function test_cada_campo_de_los_form_requests_tiene_nombre_en_palabras_del_cliente(): void
    {
        /** @var array<string, string> $atributos */
        $atributos = Arr::dot((array) trans('validation.attributes', [], 'es'));
        $faltantes = [];

        foreach ($this->reglasPorFormRequest() as $clase => $reglas) {
            foreach (array_keys($reglas) as $campo) {
                $comodin = (string) preg_replace('/\.\d+(?=\.|$)/', '.*', (string) $campo);
                if (! array_key_exists($comodin, $atributos)) {
                    $faltantes[] = "{$clase} · {$comodin}";
                }
            }
        }

        $this->assertSame([], $faltantes, 'Campos sin nombre en validation.attributes de lang/es.');
    }

    /**
     * @return array<string, array<array-key, mixed>> clase => reglas
     */
    private function reglasPorFormRequest(): array
    {
        $reglas = [];

        foreach (File::allFiles(app_path()) as $archivo) {
            if (preg_match('/^(?:abstract |final )?class \w+ extends /m', $archivo->getContents()) !== 1) {
                continue;
            }

            $clase = 'App\\'.str_replace(['/', '.php'], ['\\', ''], $archivo->getRelativePathname());
            if (! class_exists($clase) || ! is_subclass_of($clase, FormRequest::class) || (new ReflectionClass($clase))->isAbstract()) {
                continue;
            }

            $request = $clase::createFrom(Request::create('/', 'POST', self::INPUT_DE_EJEMPLO));
            $request->setContainer($this->app);
            $rules = [$request, 'rules'];
            $this->assertTrue(is_callable($rules), "{$clase} no tiene rules().");

            $deLaClase = $this->app->call($rules);
            $this->assertIsArray($deLaClase);
            $reglas[class_basename($clase)] = $deLaClase;
        }

        $this->assertGreaterThanOrEqual(self::MINIMO_DE_FORM_REQUESTS, count($reglas));

        return $reglas;
    }

    /**
     * @return list<string> reglas del framework en snake_case (`required`, `max`, `enum`…)
     */
    private function nombresDeReglas(mixed $reglas): array
    {
        $nombres = [];

        foreach (is_string($reglas) ? explode('|', $reglas) : (array) $reglas as $regla) {
            $texto = match (true) {
                $regla instanceof Enum => 'enum',
                is_string($regla), $regla instanceof Stringable => (string) $regla,
                default => '',
            };

            if ($texto === '') {
                continue;
            }

            $nombre = Str::snake((string) ValidationRuleParser::parse($texto)[0]);
            if (! in_array($nombre, self::SIN_MENSAJE, true)) {
                $nombres[] = $nombre;
            }
        }

        return $nombres;
    }
}
