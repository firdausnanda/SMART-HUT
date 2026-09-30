<?php

namespace Tests\Feature;

use App\Jobs\ProcessImportBatch;
use App\Services\Imports\Processors\BaseImportProcessor;
use Illuminate\Support\Facades\Route;
use Modules\Rhl\App\Services\Imports\Processors\PenghijauanLingkunganProcessor;
use Modules\Rhl\App\Services\Imports\Processors\ReboisasiPsProcessor;
use Modules\Rhl\App\Services\Imports\Processors\RehabLahanProcessor;
use Modules\Rhl\App\Services\Imports\Processors\RehabManggroveProcessor;
use Modules\Rhl\App\Services\Imports\Processors\RhlTeknisProcessor;
use Tests\TestCase;

class RhlModuleRoutingTest extends TestCase
{
    public function test_all_five_rhl_features_keep_their_routes_and_permissions(): void
    {
        $features = [
            'rehab-lahan' => 'rehab_lahan',
            'penghijauan-lingkungan' => 'penghijauan_lingkungan',
            'rehab-manggrove' => 'rehab_manggrove',
            'rhl-teknis' => 'rhl_teknis',
            'reboisasi-ps' => 'reboisasi_ps',
        ];

        foreach ($features as $prefix => $parameter) {
            $expected = [
                'bulk-workflow-action' => ['POST', "$prefix/bulk-workflow-action", 'edit|approve|delete'],
                'commit-import' => ['POST', "$prefix/import-commit/{batch}", 'import'],
                'create' => ['GET|HEAD', "$prefix/create", 'create'],
                'destroy' => ['DELETE', "$prefix/{{$parameter}}", 'delete'],
                'edit' => ['GET|HEAD', "$prefix/{{$parameter}}/edit", 'edit'],
                'export' => ['GET|HEAD', "$prefix/export", 'export'],
                'import' => ['POST', "$prefix/import", 'import'],
                'index' => ['GET|HEAD', $prefix, 'view'],
                'preview-import' => ['POST', "$prefix/import-preview", 'import'],
                'show' => ['GET|HEAD', "$prefix/{{$parameter}}", 'view'],
                'show-preview' => ['GET|HEAD', "$prefix/import-preview/{batch}", 'import'],
                'single-workflow-action' => ['POST', "$prefix/{{$parameter}}/single-workflow-action", 'edit|approve|delete'],
                'store' => ['POST', $prefix, 'create'],
                'template' => ['GET|HEAD', "$prefix/template", 'create'],
                'update' => ['PUT|PATCH', "$prefix/{{$parameter}}", 'edit'],
            ];

            $routes = collect(Route::getRoutes()->getRoutes())
                ->filter(fn ($route) => str_starts_with((string) $route->getName(), "$prefix."));
            $this->assertCount(15, $routes, $prefix);
            $routes = $routes->keyBy(fn ($route) => substr($route->getName(), strlen($prefix) + 1));
            $this->assertSame(array_keys($expected), $routes->keys()->sort()->values()->all(), $prefix);

            foreach ($expected as $suffix => [$methods, $uri, $permission]) {
                $route = $routes[$suffix];
                $middleware = $route->gatherMiddleware();
                $this->assertSame($methods, implode('|', $route->methods()), "$prefix.$suffix");
                $this->assertSame($uri, $route->uri(), "$prefix.$suffix");
                $this->assertStringStartsWith('Modules\\Rhl\\', $route->getActionName(), "$prefix.$suffix");
                $this->assertContains('web', $middleware, "$prefix.$suffix");
                $this->assertContains('auth', $middleware, "$prefix.$suffix");
                $actions = array_map(fn ($action) => "$prefix.$action", explode('|', $permission));
                $this->assertContains('permission:'.implode('|', $actions), $middleware, "$prefix.$suffix");
            }
        }
    }

    public function test_existing_rhl_import_batch_names_resolve_to_module_processors(): void
    {
        $expected = [
            'rehab-lahan' => RehabLahanProcessor::class,
            'penghijauan-lingkungan' => PenghijauanLingkunganProcessor::class,
            'rehab-manggrove' => RehabManggroveProcessor::class,
            'rhl-teknis' => RhlTeknisProcessor::class,
            'reboisasi-ps' => ReboisasiPsProcessor::class,
        ];
        $resolver = new \ReflectionMethod(ProcessImportBatch::class, 'resolveProcessor');

        foreach ($expected as $moduleName => $processorClass) {
            $this->assertSame(
                $processorClass,
                $resolver->invoke(new ProcessImportBatch('old-batch-id'), $moduleName),
                $moduleName
            );
            $this->assertTrue(is_subclass_of($processorClass, BaseImportProcessor::class), $moduleName);
        }
    }
}
