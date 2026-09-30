<?php

namespace Modules\Pemberdayaan\App\Providers;

use Illuminate\Support\ServiceProvider;

class PemberdayaanServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->register(RouteServiceProvider::class);
    }
}
