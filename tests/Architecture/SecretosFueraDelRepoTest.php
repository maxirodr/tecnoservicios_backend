<?php

declare(strict_types=1);

namespace Tests\Architecture;

use PHPUnit\Framework\TestCase;

/** SEG-10: ningún `.env` con secretos entra al repo; solo el ejemplo. */
final class SecretosFueraDelRepoTest extends TestCase
{
    public function test_gitignore_excluye_todo_env_salvo_el_ejemplo(): void
    {
        $reglas = array_map(trim(...), preg_split('/\R/', (string) file_get_contents(dirname(__DIR__, 2).'/.gitignore')) ?: []);

        $this->assertContains('.env', $reglas);
        $this->assertContains('.env.*', $reglas);
        $this->assertContains('!.env.example', $reglas);
        $this->assertGreaterThan(
            array_search('.env.*', $reglas, true),
            array_search('!.env.example', $reglas, true),
            'La excepción del ejemplo va después de .env.*',
        );
    }
}
