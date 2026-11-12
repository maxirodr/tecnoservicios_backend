<?php

declare(strict_types=1);

namespace Tests\Feature\Shared;

use App\Providers\AppServiceProvider;
use Illuminate\Database\Eloquent\Model;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class ProductionHardeningTest extends TestCase
{
    protected function tearDown(): void
    {
        Model::shouldBeStrict(true);

        parent::tearDown();
    }

    #[Test]
    public function production_forces_https_urls(): void
    {
        $this->app->detectEnvironment(fn (): string => 'production');

        (new AppServiceProvider($this->app))->boot();

        $this->assertStringStartsWith('https://', url('/api/health'));
    }

    #[Test]
    public function outside_production_models_are_strict_to_catch_n_plus_one(): void
    {
        $this->assertTrue(Model::preventsLazyLoading());
    }
}
