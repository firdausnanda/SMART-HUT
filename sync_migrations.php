<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$files = glob(__DIR__ . '/database/migrations/*.php');
$count = 0;

foreach ($files as $file) {
    $migrationName = str_replace('.php', '', basename($file));
    
    // Check if it's already in migrations table
    $exists = DB::table('migrations')->where('migration', $migrationName)->exists();
    
    if (!$exists) {
        DB::table('migrations')->insert([
            'migration' => $migrationName,
            'batch' => 999 // put it in a separate batch
        ]);
        $count++;
        echo "Marked as run: $migrationName\n";
    }
}

echo "Total $count migrations marked as run.\n";
