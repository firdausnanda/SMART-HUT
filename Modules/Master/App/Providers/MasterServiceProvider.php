<?php

namespace Modules\Master\App\Providers;

use Illuminate\Support\ServiceProvider;

class MasterServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->register(RouteServiceProvider::class);
    }
}
