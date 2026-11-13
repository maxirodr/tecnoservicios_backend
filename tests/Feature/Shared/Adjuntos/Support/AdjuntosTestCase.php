<?php

declare(strict_types=1);

namespace Tests\Feature\Shared\Adjuntos\Support;

use App\Modules\Identidad\Contracts\Permisos;
use App\Modules\Identidad\Domain\Enums\Alcance;
use App\Modules\Identidad\Domain\Enums\Nivel;
use App\Modules\Identidad\Domain\Models\User;
use App\Shared\Adjuntos\Adjunto;
use App\Shared\Adjuntos\ContextosAdjuntos;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Tests\Support\PermisosFalsos;
use Tests\TestCase;

/**
 * Base de los tests de adjuntos: disco falso, matriz falsa y dos contextos de prueba
 * (`documento_obra` y `presupuesto`) cuyo dueño es un `DocumentoFalso` autorizado como una obra.
 */
abstract class AdjuntosTestCase extends TestCase
{
    use RefreshDatabase {
        beginDatabaseTransaction as private iniciarTransaccion;
    }

    protected const CONTEXTO = 'documento_obra';

    protected const OTRO_CONTEXTO = 'presupuesto';

    protected PermisosFalsos $permisos;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('adjuntos');
        config(['adjuntos.disk' => 'adjuntos']);

        $this->permisos = new PermisosFalsos;
        $this->app->instance(Permisos::class, $this->permisos);

        $contextos = $this->app->make(ContextosAdjuntos::class);
        $contextos->registrar(self::CONTEXTO, AutorizaDocumentoFalso::class);
        $contextos->registrar(self::OTRO_CONTEXTO, AutorizaDocumentoFalso::class);
    }

    /** La tabla del recurso de prueba se crea antes de abrir la transacción del test (DDL hace commit implícito). */
    public function beginDatabaseTransaction(): void
    {
        if (! Schema::hasTable(DocumentoFalso::TABLA)) {
            Schema::create(DocumentoFalso::TABLA, function (Blueprint $table): void {
                $table->id();
                $table->foreignId('responsable_id')->nullable();
            });
        }

        $this->iniciarTransaccion();
    }

    protected function usuario(): User
    {
        return User::factory()->create();
    }

    protected function conObras(User $user, Nivel $nivel, Alcance $alcance = Alcance::Todas): User
    {
        $this->permisos->otorgar($user, AutorizaDocumentoFalso::FUNCION, $nivel, $alcance);

        return $user;
    }

    protected function documento(?User $responsable = null): DocumentoFalso
    {
        return DocumentoFalso::query()->create(['responsable_id' => $responsable?->getKey()]);
    }

    /**
     * @return TestResponse<JsonResponse>
     */
    protected function subir(User $user, UploadedFile $archivo, string $contexto = self::CONTEXTO): TestResponse
    {
        return $this->actingAs($user)->postJson('/api/adjuntos', ['contexto' => $contexto, 'archivo' => $archivo]);
    }

    protected function adjuntoSubido(User $user, string $contexto = self::CONTEXTO): Adjunto
    {
        return $this->adjuntoDeLaRespuesta($this->subir($user, ArchivosDePrueba::pdf(), $contexto)->assertCreated());
    }

    /**
     * @param  TestResponse<JsonResponse>  $response
     */
    protected function adjuntoDeLaRespuesta(TestResponse $response): Adjunto
    {
        $id = $response->json('data.id');
        $this->assertIsInt($id);

        return Adjunto::query()->whereKey($id)->firstOrFail();
    }

    protected function adjuntoDe(DocumentoFalso $documento, User $subidoPor, string $contexto = self::CONTEXTO): Adjunto
    {
        $adjunto = $this->adjuntoSubido($subidoPor, $contexto);
        $adjunto->forceFill(['sujeto_type' => $documento->getMorphClass(), 'sujeto_id' => $documento->id])->save();

        return $adjunto;
    }
}
