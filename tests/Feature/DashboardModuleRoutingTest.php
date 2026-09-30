<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class DashboardModuleRoutingTest extends TestCase
{
    public function test_dashboard_profile_and_location_routes_keep_their_contracts(): void
    {
        $expected = [
            'dashboard' => ['GET|HEAD', 'dashboard', 'Modules\\Dashboard\\App\\Http\\Controllers\\DashboardController@index', ['web', 'auth', 'verified', 'App\\Http\\Middleware\\CheckDashboardAccess']],
            'public.dashboard' => ['GET|HEAD', 'public/dashboard', 'Modules\\Dashboard\\App\\Http\\Controllers\\DashboardController@publicDashboard', ['web', 'auth']],
            'public.dashboard-yoy' => ['GET|HEAD', 'public/dashboard-yoy', 'Modules\\Dashboard\\App\\Http\\Controllers\\DashboardController@publicYoYDashboard', ['web', 'auth']],
            'dashboard.export-rehab-lahan' => ['GET|HEAD', 'dashboard/export-rehab-lahan', 'Modules\\Dashboard\\App\\Http\\Controllers\\DashboardController@exportRehabLahan', ['web', 'auth']],
            'profile.edit' => ['GET|HEAD', 'profile', 'App\\Http\\Controllers\\ProfileController@edit', ['web', 'auth']],
            'profile.update' => ['PATCH', 'profile', 'App\\Http\\Controllers\\ProfileController@update', ['web', 'auth']],
            'profile.destroy' => ['DELETE', 'profile', 'App\\Http\\Controllers\\ProfileController@destroy', ['web', 'auth']],
            'locations.regencies' => ['GET|HEAD', 'locations/regencies/{provinceId}', 'App\\Http\\Controllers\\LocationController@getRegencies', ['web', 'auth']],
            'locations.districts' => ['GET|HEAD', 'locations/districts/{regencyId}', 'App\\Http\\Controllers\\LocationController@getDistricts', ['web', 'auth']],
            'locations.villages' => ['GET|HEAD', 'locations/villages/{districtId}', 'App\\Http\\Controllers\\LocationController@getVillages', ['web', 'auth']],
        ];

        foreach ($expected as $name => [$method, $uri, $action, $middleware]) {
            $routes = collect(Route::getRoutes()->getRoutes())
                ->filter(fn ($route) => $route->getName() === $name);
            $this->assertCount(1, $routes, $name);
            $route = $routes->first();
            $this->assertSame($method, implode('|', $route->methods()), $name);
            $this->assertSame($uri, $route->uri(), $name);
            $this->assertSame($action, $route->getActionName(), $name);
            foreach ($middleware as $item) {
                $this->assertContains($item, $route->gatherMiddleware(), $name);
            }
        }
    }
}
