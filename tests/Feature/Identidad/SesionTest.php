<?php

declare(strict_types=1);

namespace Tests\Feature\Identidad;

use Illuminate\Http\JsonResponse;
use Illuminate\Testing\TestResponse;

/** F-01 · HU-001 (ADR-015, RN-IDE-07 y RN-IDE-10): sesión de 90 días desde el último uso, sin "recordarme". */
final class SesionTest extends IdentidadTestCase
{
    private const MAIL = 'carla@empresa-ejemplo.com';

    private const MINUTOS_90_DIAS = 90 * 24 * 60;

    public function test_sesion_dura_90_dias_y_no_se_cierra_al_cerrar_el_navegador(): void
    {
        $this->assertSame(self::MINUTOS_90_DIAS, config('session.lifetime'));
        $this->assertFalse(config('session.expire_on_close'));
        $this->assertStringContainsString("SESSION_LIFETIME=129600\n", (string) file_get_contents(base_path('.env.example')));

        $cookie = $this->ingresar()->getCookie((string) config('session.cookie'), false);

        $this->assertNotNull($cookie);
        $this->assertGreaterThanOrEqual(now()->addMinutes(self::MINUTOS_90_DIAS)->getTimestamp() - 5, $cookie->getExpiresTime(), 'Cookie persistente de 90 días');
    }

    public function test_cada_uso_renueva_los_90_dias_y_sin_uso_la_sesion_vence(): void
    {
        $sesion = (string) $this->ingresar()->getCookie((string) config('session.cookie'))?->getValue();

        $this->travel(89)->days();
        $this->conLaCookie($sesion)->assertOk();

        $this->travel(89)->days();
        $this->conLaCookie($sesion)->assertOk();

        $this->travel(91)->days();
        $this->conLaCookie($sesion)->assertUnauthorized();
    }

    public function test_ingresar_no_deja_cookie_recordarme(): void
    {
        $response = $this->ingresar();

        foreach ($response->headers->getCookies() as $cookie) {
            $this->assertStringStartsNotWith('remember_', $cookie->getName());
        }
    }

    public function test_usuario_desactivado_pierde_su_sesion_en_el_proximo_pedido(): void
    {
        $carla = $this->usuarioDe('Compras');
        $this->actingAs($carla)->getJson('/api/me')->assertOk();

        $this->actingAs($this->administrador())->patchJson("/api/usuarios/{$carla->id}", ['activo' => false])->assertOk();

        $this->actingAs($carla->fresh() ?? $carla)->getJson('/api/me')->assertUnauthorized();
        $this->assertGuest('web');
    }

    /**
     * @return TestResponse<JsonResponse>
     */
    private function ingresar(): TestResponse
    {
        $this->usuarioDe('Compras', ['email' => self::MAIL]);
        $codigo = (string) $this->pedirCodigo(self::MAIL);

        return $this->desdeElFront()->postJson('/api/auth/ingresar', ['email' => self::MAIL, 'codigo' => $codigo])->assertOk();
    }

    /**
     * Un pedido nuevo del navegador: solo con la cookie de sesión (sin el usuario en memoria del test).
     *
     * @return TestResponse<JsonResponse>
     */
    private function conLaCookie(string $sesion): TestResponse
    {
        $this->app['auth']->forgetGuards();
        $this->app['session']->driver()->flush();

        return $this->desdeElFront()->withCredentials()
            ->withCookie((string) config('session.cookie'), $sesion)
            ->getJson('/api/me');
    }
}
