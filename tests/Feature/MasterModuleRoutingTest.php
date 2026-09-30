<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class MasterModuleRoutingTest extends TestCase
{
    public function test_all_master_resource_routes_keep_their_uri_parameters_and_permissions(): void
    {
        $resources = [
            'provinces' => 'province',
            'regencies' => 'regency',
            'districts' => 'district',
            'villages' => 'village',
            'bangunan-kta' => 'bangunan_ktum',
            'sumber-dana' => 'sumber_dana',
            'commodities' => 'commodity',
            'bukan-kayu' => 'bukan_kayu',
            'kayu' => 'kayu',
            'jenis-produksi' => 'jenis_produksi',
            'pengelola-wisata' => 'pengelola_wisatum',
            'pengelola-ps' => 'pengelola_p',
            'skema-perhutanan-sosial' => 'skema_perhutanan_sosial',
        ];

        foreach ($resources as $prefix => $parameter) {
            $expected = [
                'create' => ['GET|HEAD', "$prefix/create", 'create'],
                'destroy' => ['DELETE', "$prefix/{{$parameter}}", 'delete'],
                'edit' => ['GET|HEAD', "$prefix/{{$parameter}}/edit", 'edit'],
                'index' => ['GET|HEAD', $prefix, 'view'],
                'store' => ['POST', $prefix, 'create'],
                'update' => ['PUT|PATCH', "$prefix/{{$parameter}}", 'edit'],
            ];
            $routes = collect(Route::getRoutes()->getRoutes())
                ->filter(fn ($route) => str_starts_with((string) $route->getName(), "$prefix."));
            $this->assertCount(6, $routes, $prefix);
            $routes = $routes->keyBy(fn ($route) => substr($route->getName(), strlen($prefix) + 1));
            $this->assertSame(array_keys($expected), $routes->keys()->sort()->values()->all(), $prefix);

            foreach ($expected as $suffix => [$method, $uri, $permission]) {
                $route = $routes[$suffix];
                $middleware = $route->gatherMiddleware();
                $this->assertSame($method, implode('|', $route->methods()), "$prefix.$suffix");
                $this->assertSame($uri, $route->uri(), "$prefix.$suffix");
                $this->assertStringStartsWith('Modules\\Master\\', $route->getActionName(), "$prefix.$suffix");
                $this->assertContains('web', $middleware, "$prefix.$suffix");
                $this->assertContains('auth', $middleware, "$prefix.$suffix");
                $this->assertContains("permission:$prefix.$permission", $middleware, "$prefix.$suffix");
            }
        }
    }
}
