# Modul Kepegawaian

Modul `Kepegawaian` memiliki alur Demografi Pegawai, Bezetting Jabatan, Proyeksi Gaji, dan Rekap Bulanan. Route, controller, impor Pegawai, layanan rekap, dan ekspor Excel berada di modul ini. Semua route fitur hanya didaftarkan di `Modules/Kepegawaian/routes/web.php`.

## Batas dan dependensi

- URL, nama route, middleware autentikasi dan permission, serta parameter route tetap sama. Route Rekap Bulanan tetap memakai permission `demografi-pegawai.*`.
- Impor Pegawai berjalan langsung melalui `PegawaiImport`; domain ini tidak memakai `ImportBatch` atau pemroses queue `ProcessImportBatch`.
- Model `App\Models`, migrasi, factory, seeder, dan enum tetap di lokasi asal. Halaman React berada di `Modules/Kepegawaian/resources/js/Pages/Kepegawaian`; nama halaman Inertia tetap sama dan dimuat oleh resolver React pusat.
- `App\Actions` tetap menyediakan workflow bersama. Perintah `rekap:kepegawaian` di `App\Console\Commands\GenerateRekapKepegawaian` dan dashboard publik menggunakan `Modules\Kepegawaian\App\Services\RekapKepegawaianService`.
- Modul harus aktif agar route tersedia. Kelas layanan tetap dimuat Composer untuk perintah dan dashboard.

## Verifikasi dan rilis lokal

Bandingkan 35 route Kepegawaian sebelum dan sesudah migrasi pada method, URI, nama, parameter, serta middleware; perubahan action controller ke namespace modul memang diharapkan. Jalankan `KepegawaianModuleRoutingTest`, `KepegawaianModuleFlowTest`, pengujian workflow dan dashboard publik, seluruh suite PHP, build React, serta `php artisan route:cache`. Setelah penerapan, bangun kembali cache konfigurasi dan route. Tidak ada perubahan skema database atau migrasi ulang data.
