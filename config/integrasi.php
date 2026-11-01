<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Abilities Token Integrasi
    |--------------------------------------------------------------------------
    |
    | Daftar ability yang boleh diberikan pada token klien integrasi. Setiap
    | endpoint /api/v1 mensyaratkan salah satu ability ini.
    |
    */

    'abilities' => [
        'wilayah.read' => 'Referensi wilayah (provinsi s.d. desa)',
        'master.read' => 'Data master (sumber dana, komoditas, CDK, dll.)',
        'rhl.read' => 'Rehabilitasi Lahan & Lingkungan',
        'perlindungan.read' => 'Kebakaran hutan & pengunjung wisata',
        'bina-usaha.read' => 'Hasil hutan, PBPHH, realisasi PNBP',
        'pemberdayaan.read' => 'SKPS, KUPS, KTH, nilai ekonomi',
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limit
    |--------------------------------------------------------------------------
    |
    | Jumlah permintaan per menit untuk setiap klien integrasi.
    |
    */

    'rate_limit_per_minute' => (int) env('INTEGRASI_RATE_LIMIT', 60),

    /*
    |--------------------------------------------------------------------------
    | Pencatatan Akses
    |--------------------------------------------------------------------------
    |
    | Jika aktif, setiap permintaan API dicatat ke activity_log dengan
    | log_name "integrasi".
    |
    */

    'log_requests' => (bool) env('INTEGRASI_LOG_REQUESTS', true),

];
