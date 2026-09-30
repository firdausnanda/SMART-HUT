<?php

namespace Tests\Feature;

use App\Jobs\ProcessImportBatch;
use App\Services\Imports\Processors\BaseImportProcessor;
use Illuminate\Support\Facades\Route;
use Modules\BinaUsaha\App\Services\Imports\Processors\HasilHutanBukanKayuProcessor;
use Modules\BinaUsaha\App\Services\Imports\Processors\HasilHutanKayuProcessor;
use Modules\BinaUsaha\App\Services\Imports\Processors\PbphhProcessor;
use Modules\BinaUsaha\App\Services\Imports\Processors\RealisasiPnbpProcessor;
use Tests\TestCase;

class BinaUsahaModuleRoutingTest extends TestCase
{
    public function test_four_feature_route_contracts_are_registered_once_by_the_module(): void
    {
        $features = [
            'hasil-hutan-kayu' => 'hasil_hutan_kayu',
            'hasil-hutan-bukan-kayu' => 'hasil_hutan_bukan_kayu',
            'pbphh' => 'pbphh',
            'realisasi-pnbp' => 'realisasi_pnbp',
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

            foreach ($expected as $suffix => [$methods, $uri, $action]) {
                $route = $routes[$suffix];
                $middleware = $route->gatherMiddleware();
                $this->assertSame($methods, implode('|', $route->methods()), "$prefix.$suffix");
                $this->assertSame($uri, $route->uri(), "$prefix.$suffix");
                $this->assertStringStartsWith('Modules\\BinaUsaha\\', $route->getActionName(), "$prefix.$suffix");
                $this->assertContains('web', $middleware, "$prefix.$suffix");
                $this->assertContains('auth', $middleware, "$prefix.$suffix");

                if (str_starts_with($prefix, 'hasil-hutan-')) {
                    $permissions = [];
                    foreach (['produksi-hutan-negara', 'produksi-perhutanan-sosial', 'produksi-hutan-rakyat'] as $forest) {
                        foreach (str_contains($action, '|') ? explode('|', $action) : [$action] as $operation) {
                            $permissions[] = "$forest.$operation";
                        }
                    }
                } else {
                    $permissions = array_map(fn ($operation) => "$prefix.$operation", explode('|', $action));
                }
                $this->assertContains('permission:'.implode('|', $permissions), $middleware, "$prefix.$suffix");
            }
        }
    }

    public function test_old_batch_names_with_forest_type_suffix_use_module_processors(): void
    {
        $expected = [
            'hasil-hutan-kayu|Hutan Negara' => HasilHutanKayuProcessor::class,
            'hhbk|Perhutanan Sosial' => HasilHutanBukanKayuProcessor::class,
            'pbphh' => PbphhProcessor::class,
            'realisasi-pnbp' => RealisasiPnbpProcessor::class,
        ];
        $resolver = new \ReflectionMethod(ProcessImportBatch::class, 'resolveProcessor');

        foreach ($expected as $moduleName => $processorClass) {
            $this->assertSame($processorClass, $resolver->invoke(new ProcessImportBatch('old-batch-id'), $moduleName), $moduleName);
            $this->assertTrue(is_subclass_of($processorClass, BaseImportProcessor::class), $moduleName);
        }
    }
}
