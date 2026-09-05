<?php
$files = glob(__DIR__ . '/database/migrations/*.php');
foreach ($files as $f) {
    if (basename($f) > '2026_01_28') {
        $content = file_get_contents($f);
        if (str_contains($content, 'Schema::table')) {
            echo basename($f) . "\n";
        }
    }
}
