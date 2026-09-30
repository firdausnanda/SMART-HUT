<?php

declare(strict_types=1);

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

require dirname(__DIR__) . '/vendor/autoload.php';
$app = require dirname(__DIR__) . '/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

if (DB::connection()->getDriverName() !== 'mysql') {
    throw new RuntimeException('Ekspor DBML ini membutuhkan koneksi MySQL/MariaDB.');
}

$groups = [
    'Master' => [
        'm_provinces', 'm_regencies', 'm_districts', 'm_villages',
        'm_bangunan_kta', 'm_sumber_dana', 'm_commodities', 'm_bukan_kayu',
        'm_kayu', 'm_jenis_produksi', 'm_pengelola_wisata', 'm_pengelola_hutan',
        'm_pengelola_ps', 'm_skema_perhutanan_sosial',
    ],
    'Rhl' => [
        'rehab_lahan', 'penghijauan_lingkungan', 'rehab_manggrove',
        'rhl_teknis', 'rhl_teknis_details', 'reboisasi_ps',
    ],
    'Perlindungan' => ['kebakaran_hutan', 'pengunjung_wisata'],
    'BinaUsaha' => [
        'hasil_hutan_kayu', 'hasil_hutan_kayu_details',
        'hasil_hutan_bukan_kayu', 'hasil_hutan_bukan_kayu_details',
        'pbphh', 'pbphh_jenis_produksi', 'realisasi_pnbp',
    ],
    'Pemberdayaan' => [
        'skps', 'kups', 'perkembangan_kth', 'nilai_ekonomi',
        'nilai_ekonomi_details', 'nilai_transaksi_ekonomi',
        'nilai_transaksi_ekonomi_details',
    ],
    'Kepegawaian' => [
        'pegawais', 'bezettings', 'riwayat_kgbs',
        'rekap_bulanan_pegawai', 'rekap_statistik_bulanan',
    ],
    'AksesDanOrganisasi' => [
        'users', 'password_reset_tokens', 'sessions', 'roles', 'permissions',
        'model_has_roles', 'model_has_permissions', 'role_has_permissions',
        'personal_access_tokens', 'activity_log', 'cdks', 'cdk_regency',
    ],
    'ImporBersama' => ['import_batches', 'import_staging_rows'],
    'Infrastruktur' => [
        'migrations', 'cache', 'cache_locks', 'jobs', 'job_batches',
        'failed_jobs', 'pulse_values', 'pulse_entries', 'pulse_aggregates',
    ],
];

$tables = DB::select(<<<'SQL'
    SELECT TABLE_NAME AS table_name
    FROM information_schema.TABLES
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_TYPE = 'BASE TABLE'
    ORDER BY TABLE_NAME
    SQL);
if ($tables === []) {
    throw new RuntimeException('Database sumber tidak memiliki tabel; DBML yang ada tidak ditimpa.');
}
$columns = DB::select(<<<'SQL'
    SELECT TABLE_NAME AS table_name, COLUMN_NAME AS column_name,
           COLUMN_TYPE AS column_type, IS_NULLABLE AS is_nullable,
           COLUMN_DEFAULT AS column_default, EXTRA AS extra,
           COLUMN_COMMENT AS column_comment
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
    ORDER BY TABLE_NAME, ORDINAL_POSITION
    SQL);
$indexRows = DB::select(<<<'SQL'
    SELECT TABLE_NAME AS table_name, INDEX_NAME AS index_name,
           NON_UNIQUE AS non_unique, COLUMN_NAME AS column_name
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
    ORDER BY TABLE_NAME, INDEX_NAME, SEQ_IN_INDEX
    SQL);
$foreignKeyRows = DB::select(<<<'SQL'
    SELECT k.TABLE_NAME AS table_name, k.CONSTRAINT_NAME AS constraint_name,
           k.COLUMN_NAME AS column_name,
           k.REFERENCED_TABLE_NAME AS referenced_table_name,
           k.REFERENCED_COLUMN_NAME AS referenced_column_name,
           r.DELETE_RULE AS delete_rule, r.UPDATE_RULE AS update_rule
    FROM information_schema.KEY_COLUMN_USAGE k
    JOIN information_schema.REFERENTIAL_CONSTRAINTS r
      ON r.CONSTRAINT_SCHEMA = k.CONSTRAINT_SCHEMA
     AND r.TABLE_NAME = k.TABLE_NAME
     AND r.CONSTRAINT_NAME = k.CONSTRAINT_NAME
    WHERE k.TABLE_SCHEMA = DATABASE() AND k.REFERENCED_TABLE_NAME IS NOT NULL
    ORDER BY k.TABLE_NAME, k.CONSTRAINT_NAME, k.ORDINAL_POSITION
    SQL);

$tableNames = array_map(static fn (object $row): string => $row->table_name, $tables);
$assigned = array_merge(...array_values($groups));
if (count($assigned) !== count(array_unique($assigned))) {
    throw new RuntimeException('Satu tabel tercantum di lebih dari satu kelompok domain.');
}
$unclassified = array_values(array_diff($tableNames, $assigned));
if ($unclassified !== []) {
    $groups['BelumDiklasifikasi'] = $unclassified;
}

$columnsByTable = [];
foreach ($columns as $column) {
    $columnsByTable[$column->table_name][] = $column;
}

$indexes = [];
foreach ($indexRows as $row) {
    $name = $row->index_name;
    $indexes[$row->table_name][$name]['unique'] = (int) $row->non_unique === 0;
    $indexes[$row->table_name][$name]['columns'][] = $row->column_name;
}

$foreignKeys = [];
foreach ($foreignKeyRows as $row) {
    $key = $row->table_name . '.' . $row->constraint_name;
    $foreignKeys[$key]['table'] = $row->table_name;
    $foreignKeys[$key]['referenced_table'] = $row->referenced_table_name;
    $foreignKeys[$key]['columns'][] = $row->column_name;
    $foreignKeys[$key]['referenced_columns'][] = $row->referenced_column_name;
    $foreignKeys[$key]['delete_rule'] = strtolower($row->delete_rule);
    $foreignKeys[$key]['update_rule'] = strtolower($row->update_rule);
}

function dbmlString(string $value): string
{
    return "'" . str_replace(["\\", "'", "\r", "\n"], ["\\\\", "\\'", ' ', ' '], $value) . "'";
}

function dbmlDefault(string $value, string $type): string
{
    if (preg_match('/^-?[0-9]+(?:\.[0-9]+)?$/', $value)
        && preg_match('/^(?:tinyint|smallint|mediumint|int|bigint|decimal|float|double)/i', $type)) {
        return $value;
    }

    if (preg_match('/^current_timestamp(?:\(\))?$/i', $value)) {
        return '`CURRENT_TIMESTAMP`';
    }

    return dbmlString($value);
}

$lines = [
    '// Struktur fisik database SMART-HUT; dibuat dengan php scripts/export-dbml.php.',
    '// Relasi Ref berasal dari foreign key MySQL. Relasi Eloquent tanpa constraint tidak otomatis muncul.',
    '',
    'Project SMART_HUT {',
    "  database_type: 'MySQL'",
    "  Note: 'Skema aktual database; kelompok tabel menunjukkan domain pemilik.'",
    '}',
    '',
];

foreach ($groups as $groupName => $groupTables) {
    $present = array_values(array_intersect($groupTables, $tableNames));
    if ($present === []) {
        continue;
    }

    $lines[] = '// ' . $groupName;
    foreach ($present as $tableName) {
        $lines[] = 'Table ' . $tableName . ' {';
        $tableIndexes = $indexes[$tableName] ?? [];
        $singlePrimary = $tableIndexes['PRIMARY']['columns'] ?? [];
        $singlePrimary = count($singlePrimary) === 1 ? $singlePrimary[0] : null;
        $singleUnique = [];
        foreach ($tableIndexes as $indexName => $index) {
            if ($indexName !== 'PRIMARY' && $index['unique'] && count($index['columns']) === 1) {
                $singleUnique[$index['columns'][0]] = true;
            }
        }

        foreach ($columnsByTable[$tableName] ?? [] as $column) {
            $attributes = [];
            if ($column->column_name === $singlePrimary) {
                $attributes[] = 'pk';
            }
            if (str_contains(strtolower($column->extra), 'auto_increment')) {
                $attributes[] = 'increment';
            }
            if ($column->is_nullable === 'NO' && $column->column_name !== $singlePrimary) {
                $attributes[] = 'not null';
            }
            if (isset($singleUnique[$column->column_name])) {
                $attributes[] = 'unique';
            }
            if ($column->column_default !== null) {
                $attributes[] = 'default: ' . dbmlDefault((string) $column->column_default, $column->column_type);
            }
            if ($column->column_comment !== '') {
                $attributes[] = 'note: ' . dbmlString($column->column_comment);
            }

            $type = $column->column_type;
            if (preg_match('/[\s\x27\x22]/', $type)) {
                $type = '"' . str_replace('"', '\\"', $type) . '"';
            }
            $line = '  ' . $column->column_name . ' ' . $type;
            if ($attributes !== []) {
                $line .= ' [' . implode(', ', $attributes) . ']';
            }
            $lines[] = $line;
        }

        $composite = [];
        foreach ($tableIndexes as $indexName => $index) {
            if (!$index['unique'] || count($index['columns']) < 2) {
                continue;
            }
            $kind = $indexName === 'PRIMARY' ? 'pk' : 'unique';
            $composite[] = '    (' . implode(', ', $index['columns']) . ') [' . $kind . ']';
        }
        if ($composite !== []) {
            $lines[] = '';
            $lines[] = '  indexes {';
            array_push($lines, ...$composite);
            $lines[] = '  }';
        }

        $lines[] = '}';
        $lines[] = '';
    }
}

$lines[] = '// Kelompok domain; satu tabel hanya mempunyai satu pemilik dalam diagram ini.';
foreach ($groups as $groupName => $groupTables) {
    $present = array_values(array_intersect($groupTables, $tableNames));
    if ($present === []) {
        continue;
    }
    $lines[] = 'TableGroup ' . $groupName . ' {';
    foreach ($present as $tableName) {
        $lines[] = '  ' . $tableName;
    }
    $lines[] = '}';
    $lines[] = '';
}

$lines[] = '// Foreign key yang benar-benar terdaftar di MySQL.';
foreach ($foreignKeys as $foreignKey) {
    $source = count($foreignKey['columns']) === 1
        ? $foreignKey['columns'][0]
        : '(' . implode(', ', $foreignKey['columns']) . ')';
    $target = count($foreignKey['referenced_columns']) === 1
        ? $foreignKey['referenced_columns'][0]
        : '(' . implode(', ', $foreignKey['referenced_columns']) . ')';
    $lines[] = 'Ref: ' . $foreignKey['table'] . '.' . $source
        . ' > ' . $foreignKey['referenced_table'] . '.' . $target
        . ' [delete: ' . $foreignKey['delete_rule']
        . ', update: ' . $foreignKey['update_rule'] . ']';
}
$lines[] = '';

$output = dirname(__DIR__) . '/docs/database/erd.dbml';
if (!is_dir(dirname($output)) && !mkdir(dirname($output), 0775, true)) {
    throw new RuntimeException('Gagal membuat direktori dokumentasi database.');
}
if (file_put_contents($output, implode(PHP_EOL, $lines)) === false) {
    throw new RuntimeException('Gagal menulis file DBML.');
}
echo sprintf("DBML: %d tabel, %d foreign key, %d belum diklasifikasi.\n", count($tables), count($foreignKeys), count($unclassified));
