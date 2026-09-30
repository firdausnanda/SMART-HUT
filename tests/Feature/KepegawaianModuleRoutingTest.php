<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class KepegawaianModuleRoutingTest extends TestCase
{
    public function test_all_employee_routes_keep_their_public_contract_and_use_module_controllers(): void
    {
        $contracts = [
            'demografi-pegawai' => [
                'store' => ['POST', 'demografi-pegawai', 'create'],
                'index' => ['GET|HEAD', 'demografi-pegawai', 'view'],
                'bulk-delete' => ['POST', 'demografi-pegawai/bulk-delete', 'delete'],
                'bulk-restore' => ['POST', 'demografi-pegawai/bulk-restore', 'edit'],
                'create' => ['GET|HEAD', 'demografi-pegawai/create', 'create'],
                'export' => ['GET|HEAD', 'demografi-pegawai/export', 'export'],
                'import' => ['POST', 'demografi-pegawai/import', 'create'],
                'kgb.update' => ['PUT', 'demografi-pegawai/riwayat-kgb/{riwayat_kgb}', 'edit'],
                'kgb.destroy' => ['DELETE', 'demografi-pegawai/riwayat-kgb/{riwayat_kgb}', 'delete'],
                'template' => ['GET|HEAD', 'demografi-pegawai/template', 'create'],
                'update' => ['PUT|PATCH', 'demografi-pegawai/{demografi_pegawai}', 'edit'],
                'destroy' => ['DELETE', 'demografi-pegawai/{demografi_pegawai}', 'delete'],
                'edit' => ['GET|HEAD', 'demografi-pegawai/{demografi_pegawai}/edit', 'edit'],
                'restore' => ['POST', 'demografi-pegawai/{id}/restore', 'edit'],
                'kgb.store' => ['POST', 'demografi-pegawai/{pegawai}/riwayat-kgb', 'create'],
            ],
            'bezetting-jabatan' => [
                'store' => ['POST', 'bezetting-jabatan', 'create'],
                'index' => ['GET|HEAD', 'bezetting-jabatan', 'view'],
                'bulk-workflow-action' => ['POST', 'bezetting-jabatan/bulk-workflow-action', 'edit|approve|delete'],
                'create' => ['GET|HEAD', 'bezetting-jabatan/create', 'create'],
                'show' => ['GET|HEAD', 'bezetting-jabatan/{bezetting_jabatan}', 'view'],
                'update' => ['PUT|PATCH', 'bezetting-jabatan/{bezetting_jabatan}', 'edit'],
                'destroy' => ['DELETE', 'bezetting-jabatan/{bezetting_jabatan}', 'delete'],
                'edit' => ['GET|HEAD', 'bezetting-jabatan/{bezetting_jabatan}/edit', 'edit'],
                'single-workflow-action' => ['POST', 'bezetting-jabatan/{bezetting_jabatan}/single-workflow-action', 'edit|approve|delete'],
            ],
            'proyeksi-gaji' => [
                'index' => ['GET|HEAD', 'proyeksi-gaji', 'view'],
                'export' => ['GET|HEAD', 'proyeksi-gaji/export', 'export'],
            ],
            'rekap-bulanan' => [
                'index' => ['GET|HEAD', 'rekap-bulanan', 'view'],
                'bulk-workflow-action' => ['POST', 'rekap-bulanan/bulk-workflow-action', 'edit|approve|delete'],
                'export-bezetting' => ['GET|HEAD', 'rekap-bulanan/export-bezetting/{year}/{month}', 'export'],
                'export' => ['GET|HEAD', 'rekap-bulanan/export/{year}/{month}', 'export'],
                'generate' => ['POST', 'rekap-bulanan/generate', 'create'],
                'destroy' => ['DELETE', 'rekap-bulanan/{id}', 'delete'],
                'single-workflow-action' => ['POST', 'rekap-bulanan/{id}/single-workflow-action', 'edit|approve|delete'],
                'show' => ['GET|HEAD', 'rekap-bulanan/{year}/{month}', 'view'],
                'show-pegawai' => ['GET|HEAD', 'rekap-bulanan/{year}/{month}/pegawai', 'view'],
            ],
        ];

        foreach ($contracts as $prefix => $expected) {
            $routes = collect(Route::getRoutes()->getRoutes())
                ->filter(fn ($route) => str_starts_with((string) $route->getName(), "$prefix."));
            $this->assertCount(count($expected), $routes, $prefix);
            $routes = $routes->keyBy(fn ($route) => substr($route->getName(), strlen($prefix) + 1));
            $expectedNames = array_keys($expected);
            sort($expectedNames);
            $this->assertSame($expectedNames, $routes->keys()->sort()->values()->all(), $prefix);

            foreach ($expected as $suffix => [$method, $uri, $permission]) {
                $route = $routes[$suffix];
                $middleware = $route->gatherMiddleware();
                $this->assertSame($method, implode('|', $route->methods()), "$prefix.$suffix");
                $this->assertSame($uri, $route->uri(), "$prefix.$suffix");
                $this->assertStringStartsWith('Modules\\Kepegawaian\\', $route->getActionName(), "$prefix.$suffix");
                $this->assertContains('web', $middleware, "$prefix.$suffix");
                $this->assertContains('auth', $middleware, "$prefix.$suffix");
                $permissionPrefix = $prefix === 'rekap-bulanan' ? 'demografi-pegawai' : $prefix;
                $actions = array_map(fn ($action) => "$permissionPrefix.$action", explode('|', $permission));
                $this->assertContains('permission:'.implode('|', $actions), $middleware, "$prefix.$suffix");
            }
        }
    }
}
