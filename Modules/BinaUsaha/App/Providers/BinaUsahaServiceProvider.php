<?php

namespace Modules\BinaUsaha\App\Providers;

use Illuminate\Support\ServiceProvider;

class BinaUsahaServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->register(RouteServiceProvider::class);
    }
}
