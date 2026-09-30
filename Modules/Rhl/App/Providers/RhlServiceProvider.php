<?php

namespace Modules\Rhl\App\Providers;

use Illuminate\Support\ServiceProvider;

class RhlServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->register(RouteServiceProvider::class);
    }
}
