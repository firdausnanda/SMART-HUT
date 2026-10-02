# Modul RHL

Modul `Rhl` memiliki alur Rehab Lahan, Penghijauan Lingkungan, Rehab Mangrove, RHL Teknis, dan Reboisasi PS. Route, controller, validator impor, pemroses batch, impor langsung, dan ekspor Excel kelima fitur berada di modul ini. Semua route fitur hanya didaftarkan di `Modules/Rhl/routes/web.php`.

## Batas dan dependensi

- URL, nama route, middleware permission, parameter route, dan nilai `ImportBatch.module_name` tetap sama: `rehab-lahan`, `penghijauan-lingkungan`, `rehab-manggrove`, `rhl-teknis`, dan `reboisasi-ps`.
- Model `App\Models` beserta migrasi, factory, dan seeder tetap di lokasi asal. Halaman React kelima fitur berada di `Modules/Rhl/resources/js/Pages`; nama halaman Inertia tetap sama dan dimuat oleh resolver React pusat.
- Workflow (`App\Actions`), staging (`App\Imports\StagingImport`), job `App\Jobs\ProcessImportBatch`, `BaseImportProcessor`, serta model lokasi dan referensi lain adalah layanan bersama. Job memetakan kelima nilai `module_name` lama ke pemroses modul ini.
- Modul Dashboard menggunakan `Modules\Rhl\App\Exports\RehabLahanExport` untuk unduhan Rehab Lahan.
- Modul harus aktif agar route tersedia. Status modul bukan sakelar untuk menghentikan queue karena kelas pemroses masih dapat dimuat Composer.

## Verifikasi dan rilis lokal

Bandingkan 75 route RHL sebelum dan sesudah migrasi pada method, URI, nama, parameter, serta middleware; perubahan action controller ke namespace modul memang diharapkan. Jalankan `RhlModuleRoutingTest`, pengujian alur RHL dan dashboard, seluruh suite PHP, build React, serta `php artisan route:cache`. Setelah penerapan, bangun kembali cache konfigurasi dan route, lalu jalankan `php artisan queue:restart` agar worker memakai pemetaan kelas baru. Tidak ada perubahan skema database atau migrasi ulang data.
