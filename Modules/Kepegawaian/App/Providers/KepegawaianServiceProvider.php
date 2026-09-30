<?php

namespace Modules\Kepegawaian\App\Providers;

use Illuminate\Support\ServiceProvider;

class KepegawaianServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->register(RouteServiceProvider::class);
    }
}
