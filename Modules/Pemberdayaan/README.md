# Modul Pemberdayaan

Modul `Pemberdayaan` memiliki alur SKPS, KUPS, Nilai Ekonomi, Perkembangan KTH, dan Nilai Transaksi Ekonomi. Route, controller, validator impor, pemroses batch, impor langsung, dan ekspor Excel kelima fitur berada di modul ini. Layanan perbaikan data dan pembentuk kunci grup Nilai Transaksi Ekonomi juga berada di sini. Semua route fitur hanya didaftarkan di `Modules/Pemberdayaan/routes/web.php`.

## Batas dan dependensi

- URL, nama route, middleware autentikasi dan permission, serta parameter route tetap sama. Route perbaikan data Nilai Transaksi Ekonomi tetap mewajibkan permission `nilai-transaksi-ekonomi.import` dan `nilai-transaksi-ekonomi.delete` secara bersamaan.
- Nilai `ImportBatch.module_name` tetap `skps`, `kups`, `nilai-ekonomi`, `perkembangan-kth`, dan `nilai-transaksi-ekonomi`. Job `App\Jobs\ProcessImportBatch` memetakan batch lama ke pemroses modul.
- Model `App\Models`, migrasi, factory, dan seeder tetap di lokasi asal. Halaman React kelima fitur berada di `Modules/Pemberdayaan/resources/js/Pages`; nama halaman Inertia tetap sama dan dimuat oleh resolver React pusat.
- Workflow (`App\Actions`), staging (`App\Imports\StagingImport`), `BaseImportProcessor`, model lokasi dan master data adalah layanan bersama. Dashboard, halaman publik, dan activity log tetap memakai model yang sama.
- Modul harus aktif agar route tersedia. Status modul bukan sakelar untuk menghentikan queue karena kelas pemroses masih dapat dimuat Composer.

## Verifikasi dan rilis lokal

Bandingkan 77 route Pemberdayaan sebelum dan sesudah migrasi pada method, URI, nama, parameter, serta middleware; perubahan action controller ke namespace modul memang diharapkan. Jalankan `PemberdayaanModuleRoutingTest`, pengujian Nilai Transaksi Ekonomi yang ada, seluruh suite PHP, build React, serta `php artisan route:cache`. Setelah penerapan, bangun kembali cache konfigurasi dan route, lalu jalankan `php artisan queue:restart` agar worker memakai pemetaan kelas baru. Tidak ada perubahan skema database atau migrasi ulang data.
