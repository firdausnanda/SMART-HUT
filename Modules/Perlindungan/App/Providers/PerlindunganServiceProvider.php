<?php

namespace Modules\Perlindungan\App\Providers;

use Illuminate\Support\ServiceProvider;

class PerlindunganServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->register(RouteServiceProvider::class);
    }
}
