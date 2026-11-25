<?php

namespace Modules\Penanaman\App\Providers;

use Illuminate\Support\ServiceProvider;

class PenanamanServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->register(RouteServiceProvider::class);
    }
}
