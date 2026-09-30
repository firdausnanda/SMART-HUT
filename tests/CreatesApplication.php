<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;

trait CreatesApplication
{
  /**
   * Creates the application.
   */
  public function createApplication(): Application
  {
    if (is_file(__DIR__ . '/../bootstrap/cache/config.php')) {
      throw new \RuntimeException('Hapus cache konfigurasi sebelum menjalankan tes PHPUnit.');
    }

    $app = require __DIR__ . '/../bootstrap/app.php';

    $app->make(Kernel::class)->bootstrap();

    $database = $app['config']->get('database');
    $sqlite = $database['connections']['sqlite'] ?? [];
    if ($app->environment() !== 'testing'
      || ($database['default'] ?? null) !== 'sqlite'
      || ($sqlite['driver'] ?? null) !== 'sqlite'
      || ($sqlite['database'] ?? null) !== ':memory:'
      || !empty($sqlite['url'])) {
      throw new \RuntimeException('Tes PHPUnit harus memakai database SQLite dalam memori.');
    }

    $app['config']->set('database.connections', ['sqlite' => $sqlite]);
    $app['config']->set('activitylog.database_connection', null);

    return $app;
  }
}
