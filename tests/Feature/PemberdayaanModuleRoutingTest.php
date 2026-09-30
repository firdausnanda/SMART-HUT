<?php

namespace Tests\Feature;

use App\Jobs\ProcessImportBatch;
use App\Services\Imports\Processors\BaseImportProcessor;
use Illuminate\Support\Facades\Route;
use Modules\Pemberdayaan\App\Services\Imports\Processors\KupsProcessor;
use Modules\Pemberdayaan\App\Services\Imports\Processors\NilaiEkonomiProcessor;
use Modules\Pemberdayaan\App\Services\Imports\Processors\NilaiTransaksiEkonomiProcessor;
use Modules\Pemberdayaan\App\Services\Imports\Processors\PerkembanganKthProcessor;
use Modules\Pemberdayaan\App\Services\Imports\Processors\SkpsProcessor;
use Tests\TestCase;

class PemberdayaanModuleRoutingTest extends TestCase
{
    public function test_five_feature_route_contracts_are_registered_once_by_the_module(): void
    {
        $features = [
            'skps' => 'skp',
            'kups' => 'kup',
            'nilai-ekonomi' => 'nilai_ekonomi',
            'perkembangan-kth' => 'perkembangan_kth',
            'nilai-transaksi-ekonomi' => 'nilai_transaksi_ekonomi',
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

            if ($prefix === 'nilai-transaksi-ekonomi') {
                $expected['repair-imported-data'] = ['POST', "$prefix/repair-imported-data", 'import'];
                $expected['repair-preview'] = ['GET|HEAD', "$prefix/repair-imported-data/preview", 'import'];
            }

            $routes = collect(Route::getRoutes()->getRoutes())
                ->filter(fn ($route) => str_starts_with((string) $route->getName(), "$prefix."));
            $this->assertCount(count($expected), $routes, $prefix);
            $routes = $routes->keyBy(fn ($route) => substr($route->getName(), strlen($prefix) + 1));
            $expectedNames = array_keys($expected);
            sort($expectedNames);
            $this->assertSame($expectedNames, $routes->keys()->sort()->values()->all(), $prefix);

            foreach ($expected as $suffix => [$methods, $uri, $permission]) {
                $route = $routes[$suffix];
                $middleware = $route->gatherMiddleware();
                $this->assertSame($methods, implode('|', $route->methods()), "$prefix.$suffix");
                $this->assertSame($uri, $route->uri(), "$prefix.$suffix");
                $this->assertStringStartsWith('Modules\\Pemberdayaan\\', $route->getActionName(), "$prefix.$suffix");
                $this->assertContains('web', $middleware, "$prefix.$suffix");
                $this->assertContains('auth', $middleware, "$prefix.$suffix");
                $actions = array_map(fn ($action) => "$prefix.$action", explode('|', $permission));
                $this->assertContains('permission:'.implode('|', $actions), $middleware, "$prefix.$suffix");

                if (str_starts_with($suffix, 'repair-')) {
                    $this->assertContains('permission:nilai-transaksi-ekonomi.delete', $middleware, "$prefix.$suffix");
                }
            }
        }
    }

    public function test_existing_batch_names_resolve_to_module_processors(): void
    {
        $expected = [
            'skps' => SkpsProcessor::class,
            'kups' => KupsProcessor::class,
            'nilai-ekonomi' => NilaiEkonomiProcessor::class,
            'perkembangan-kth' => PerkembanganKthProcessor::class,
            'nilai-transaksi-ekonomi' => NilaiTransaksiEkonomiProcessor::class,
        ];
        $resolver = new \ReflectionMethod(ProcessImportBatch::class, 'resolveProcessor');

        foreach ($expected as $moduleName => $processorClass) {
            $this->assertSame($processorClass, $resolver->invoke(new ProcessImportBatch('old-batch-id'), $moduleName), $moduleName);
            $this->assertTrue(is_subclass_of($processorClass, BaseImportProcessor::class), $moduleName);
        }
    }
}
