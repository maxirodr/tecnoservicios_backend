<?php

declare(strict_types=1);

namespace App\Modules\Importacion\Application\Lectura;

use App\Modules\Importacion\Domain\Informe\Faltante;
use App\Modules\Importacion\Domain\Lectura\DatoInvalido;
use App\Modules\Importacion\Domain\Lectura\FilaLeida;
use App\Modules\Importacion\Domain\Lectura\Valores;
use App\Modules\Importacion\Domain\Plantilla\Plantillas;
use Closure;

/**
 * Lee una fila de una plantilla y junta **todos** sus faltantes (RN-IMP-19): cada lectura devuelve el valor o null
 * y, si falta o no se entiende, deja el faltante con el texto de `importacion.md` §8. Nada se redondea ni se adivina.
 */
final class RevisorDeFila
{
    /** @var list<Faltante> */
    public array $faltantes = [];

    public function __construct(
        public readonly string $hoja,
        public readonly FilaLeida $fila,
        public readonly string $sujeto,
        public readonly string $loCompleta,
    ) {}

    public function falta(string $columna, string $que): void
    {
        $this->faltantes[] = new Faltante($this->hoja, $this->fila->numero, $this->sujeto, $columna, $que, $this->loCompleta);
    }

    public function texto(string $columna, bool $obligatorio = false, ?int $largo = null): ?string
    {
        $texto = $this->fila->texto($columna);

        if ($texto === null) {
            if ($obligatorio) {
                $this->falta($columna, TextosDeFaltantes::falta($columna));
            }

            return null;
        }

        if ($largo !== null && mb_strlen($texto) > $largo) {
            $this->falta($columna, "'{$columna}' tiene más de {$largo} caracteres.");

            return null;
        }

        return $texto;
    }

    public function mail(string $columna, bool $obligatorio = false): ?string
    {
        $mail = $this->texto($columna, $obligatorio);

        if ($mail !== null && ! Valores::esMail($mail)) {
            $this->falta($columna, "'{$mail}' no es un mail.");

            return null;
        }

        return $mail === null ? null : mb_strtolower($mail);
    }

    public function link(string $columna, bool $obligatorio = false): ?string
    {
        $texto = $this->texto($columna, $obligatorio);

        return $texto === null ? null : $this->leer($columna, static fn (): string => Valores::link($texto));
    }

    /** "AAAA-MM-DD" */
    public function fecha(string $columna, bool $obligatorio = false): ?string
    {
        $fecha = $this->leer($columna, fn (): ?string => Valores::fecha($this->fila->valor($columna)));

        if ($fecha === null && $obligatorio && $this->fila->texto($columna) === null) {
            $this->falta($columna, TextosDeFaltantes::falta($columna));
        }

        return $fecha;
    }

    /**
     * @return numeric-string|null
     */
    public function importe(string $columna, bool $obligatorio = false): ?string
    {
        $importe = $this->leer($columna, fn (): ?string => Valores::importe($this->fila->valor($columna)));

        if ($importe === null && $obligatorio && $this->fila->texto($columna) === null) {
            $this->falta($columna, TextosDeFaltantes::falta($columna));
        }

        return $importe;
    }

    /**
     * @return numeric-string|null
     */
    public function cantidad(string $columna, bool $obligatorio = false): ?string
    {
        $cantidad = $this->leer($columna, fn (): ?string => Valores::cantidad($this->fila->valor($columna)));

        if ($cantidad === null && $obligatorio && $this->fila->texto($columna) === null) {
            $this->falta($columna, TextosDeFaltantes::falta($columna));
        }

        return $cantidad;
    }

    /**
     * Un valor de una lista desplegable; `false` si está escrito y no es de la lista.
     *
     * @param  array<string, string|null>  $lista
     */
    public function lista(string $columna, array $lista, string $siNoEsDeLaLista, bool $obligatorio = false): string|false|null
    {
        $texto = $this->texto($columna, $obligatorio);

        if ($texto === null) {
            return null;
        }

        $valor = Plantillas::valorDe($lista, $texto);

        if ($valor === false) {
            $this->falta($columna, $siNoEsDeLaLista);
        }

        return $valor;
    }

    public function sinFaltantes(): bool
    {
        return $this->faltantes === [];
    }

    /**
     * @template T
     *
     * @param  Closure(): T  $lectura
     * @return T|null
     */
    private function leer(string $columna, Closure $lectura): mixed
    {
        try {
            return $lectura();
        } catch (DatoInvalido $e) {
            $this->falta($columna, $e->getMessage());

            return null;
        }
    }
}
