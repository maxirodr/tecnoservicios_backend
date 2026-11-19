<?php

declare(strict_types=1);

namespace App\Modules\Pedidos\Http\Requests;

/**
 * Base de las acciones con un texto obligatorio (motivo, comentario de cierre, de reapertura, comentario):
 * cada una dice su campo, su largo y su mensaje de la spec.
 */
abstract class TextoObligatorioRequest extends PedidosRequest
{
    abstract protected function campo(): string;

    abstract protected function mensajeVacio(): string;

    protected function largo(): int
    {
        return self::LARGO_TEXTO;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [$this->campo() => ['required', 'string', 'max:'.$this->largo()], ...$this->otrasReglas()];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [...parent::messages(), $this->campo().'.required' => $this->mensajeVacio()];
    }

    public function texto(): string
    {
        return $this->string($this->campo())->trim()->toString();
    }

    /**
     * @return array<string, mixed>
     */
    protected function otrasReglas(): array
    {
        return [];
    }
}
