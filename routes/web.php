<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::get('/', [\App\Http\Controllers\WelcomeController::class, 'index']);

Route::middleware('auth')->group(function () {
    require __DIR__ . '/modules/dashboard.php';
    require __DIR__ . '/modules/rhl.php';
    require __DIR__ . '/modules/perlindungan.php';
    require __DIR__ . '/modules/bina_usaha.php';
    require __DIR__ . '/modules/pemberdayaan.php';
    require __DIR__ . '/modules/kepegawaian.php';
    require __DIR__ . '/modules/master.php';
    require __DIR__ . '/modules/admin.php';

    Route::impersonate();
});

require __DIR__ . '/auth.php';
