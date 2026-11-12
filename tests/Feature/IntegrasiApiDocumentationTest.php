<?php

namespace Tests\Feature;

use App\Support\Integrasi\ApiDocumentation;
use Illuminate\Support\Facades\Route;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\UsesIsolatedUserDatabase;
use Tests\TestCase;

class IntegrasiApiDocumentationTest extends TestCase
{
    use UsesIsolatedUserDatabase;

    public function test_documentation_page_is_public_and_renders_config(): void
    {
        $this->get(route('docs.api'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Docs/Api')
                ->where('version', 'v1')
                ->where('baseUrl', url('/api/v1'))
                ->has('endpoints', count(ApiDocumentation::endpoints()))
                ->has('abilities', count(config('integrasi.abilities')))
                ->where('rateLimitPerMinute', config('integrasi.rate_limit_per_minute'))
                ->has('errors')
                ->has('changelog'));
    }

    public function test_documented_endpoints_match_registered_v1_routes(): void
    {
        $registered = collect(Route::getRoutes()->getRoutes())
            ->filter(fn ($route) => str_starts_with($route->uri(), 'api/v1/') && str_starts_with((string) $route->getName(), 'api.v1.'))
            ->mapWithKeys(fn ($route) => [$route->getName() => $route]);

        $documented = collect(ApiDocumentation::endpoints())->keyBy('route');

        $this->assertEqualsCanonicalizing(
            $registered->keys()->all(),
            $documented->keys()->all(),
            'Setiap endpoint /api/v1 harus terdokumentasi di ApiDocumentation::endpoints(), dan sebaliknya.'
        );

        foreach ($documented as $name => $endpoint) {
            $route = $registered[$name];
            $this->assertContains($endpoint['method'], $route->methods(), $name);
            $this->assertSame('api/v1' . $endpoint['path'], $route->uri(), $name);

            if ($endpoint['ability']) {
                $this->assertArrayHasKey($endpoint['ability'], config('integrasi.abilities'), $name);
                $this->assertContains('ability:' . $endpoint['ability'], $route->gatherMiddleware(), $name);
            }
        }
    }
}
