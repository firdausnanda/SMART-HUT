<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class PerlindunganModuleRoutingTest extends TestCase
{
    public function test_pengunjung_wisata_routes_are_registered_once_by_the_module(): void
    {
        $expected = [
            'bulk-workflow-action' => ['POST', 'pengunjung-wisata/bulk-workflow-action', 'edit|approve|delete'],
            'commit-import' => ['POST', 'pengunjung-wisata/import-commit/{batch}', 'import'],
            'create' => ['GET|HEAD', 'pengunjung-wisata/create', 'create'],
            'destroy' => ['DELETE', 'pengunjung-wisata/{pengunjung_wisata}', 'delete'],
            'edit' => ['GET|HEAD', 'pengunjung-wisata/{pengunjung_wisata}/edit', 'edit'],
            'export' => ['GET|HEAD', 'pengunjung-wisata/export', 'export'],
            'import' => ['POST', 'pengunjung-wisata/import', 'import'],
            'index' => ['GET|HEAD', 'pengunjung-wisata', 'view'],
            'preview-import' => ['POST', 'pengunjung-wisata/import-preview', 'import'],
            'show' => ['GET|HEAD', 'pengunjung-wisata/{pengunjung_wisata}', 'view'],
            'show-preview' => ['GET|HEAD', 'pengunjung-wisata/import-preview/{batch}', 'import'],
            'single-workflow-action' => ['POST', 'pengunjung-wisata/{pengunjung_wisata}/single-workflow-action', 'edit|approve|delete'],
            'store' => ['POST', 'pengunjung-wisata', 'create'],
            'template' => ['GET|HEAD', 'pengunjung-wisata/template', 'create'],
            'update' => ['PUT|PATCH', 'pengunjung-wisata/{pengunjung_wisata}', 'edit'],
        ];

        $routes = collect(Route::getRoutes()->getRoutes())
            ->filter(fn ($route) => str_starts_with((string) $route->getName(), 'pengunjung-wisata.'));

        $this->assertCount(count($expected), $routes);
        $routes = $routes->keyBy(fn ($route) => substr($route->getName(), strlen('pengunjung-wisata.')));
        $this->assertSame(array_keys($expected), $routes->keys()->sort()->values()->all());

        foreach ($expected as $name => [$methods, $uri, $permission]) {
            $route = $routes[$name];
            $middleware = $route->gatherMiddleware();

            $this->assertSame($methods, implode('|', $route->methods()), $name);
            $this->assertSame($uri, $route->uri(), $name);
            $this->assertStringStartsWith('Modules\\Perlindungan\\', $route->getActionName());
            $this->assertContains('web', $middleware, $name);
            $this->assertContains('auth', $middleware, $name);
            $qualifiedPermissions = array_map(
                fn ($action) => 'pengunjung-wisata.'.$action,
                explode('|', $permission)
            );
            $this->assertContains('permission:'.implode('|', $qualifiedPermissions), $middleware, $name);
        }
    }

    public function test_kebakaran_hutan_routes_are_registered_once_by_the_module(): void
    {
        $expected = [
            'bulk-workflow-action' => ['POST', 'kebakaran-hutan/bulk-workflow-action', 'edit|approve|delete'],
            'commit-import' => ['POST', 'kebakaran-hutan/import-commit/{batch}', 'import'],
            'create' => ['GET|HEAD', 'kebakaran-hutan/create', 'create'],
            'destroy' => ['DELETE', 'kebakaran-hutan/{kebakaran_hutan}', 'delete'],
            'edit' => ['GET|HEAD', 'kebakaran-hutan/{kebakaran_hutan}/edit', 'edit'],
            'export' => ['GET|HEAD', 'kebakaran-hutan/export', 'export'],
            'import' => ['POST', 'kebakaran-hutan/import', 'import'],
            'index' => ['GET|HEAD', 'kebakaran-hutan', 'view'],
            'preview-import' => ['POST', 'kebakaran-hutan/import-preview', 'import'],
            'show' => ['GET|HEAD', 'kebakaran-hutan/{kebakaran_hutan}', 'view'],
            'show-preview' => ['GET|HEAD', 'kebakaran-hutan/import-preview/{batch}', 'import'],
            'single-workflow-action' => ['POST', 'kebakaran-hutan/{kebakaran_hutan}/single-workflow-action', 'edit|approve|delete'],
            'store' => ['POST', 'kebakaran-hutan', 'create'],
            'template' => ['GET|HEAD', 'kebakaran-hutan/template', 'create'],
            'update' => ['PUT|PATCH', 'kebakaran-hutan/{kebakaran_hutan}', 'edit'],
        ];

        $routes = collect(Route::getRoutes()->getRoutes())
            ->filter(fn ($route) => str_starts_with((string) $route->getName(), 'kebakaran-hutan.'));

        $this->assertCount(count($expected), $routes);
        $routes = $routes->keyBy(fn ($route) => substr($route->getName(), strlen('kebakaran-hutan.')));
        $this->assertSame(array_keys($expected), $routes->keys()->sort()->values()->all());

        foreach ($expected as $name => [$methods, $uri, $permission]) {
            $route = $routes[$name];
            $middleware = $route->gatherMiddleware();

            $this->assertSame($methods, implode('|', $route->methods()), $name);
            $this->assertSame($uri, $route->uri(), $name);
            $this->assertStringStartsWith('Modules\\Perlindungan\\', $route->getActionName());
            $this->assertContains('web', $middleware, $name);
            $this->assertContains('auth', $middleware, $name);
            $qualifiedPermissions = array_map(
                fn ($action) => 'kebakaran-hutan.'.$action,
                explode('|', $permission)
            );
            $this->assertContains('permission:'.implode('|', $qualifiedPermissions), $middleware, $name);
        }

        $allNames = collect(Route::getRoutes()->getRoutes())->pluck('action.as');
        $this->assertNotContains('perlindungan.index', $allNames);
        $this->assertNotContains('api.perlindungan', $allNames);
    }
}
