# Modul Bina Usaha

Modul `BinaUsaha` memiliki alur Hasil Hutan Kayu, Hasil Hutan Bukan Kayu, PBPHH, dan Realisasi PNBP. Route, controller, validator impor, pemroses batch, impor langsung, dan ekspor Excel keempat fitur berada di modul ini. Semua route fitur hanya didaftarkan di `Modules/BinaUsaha/routes/web.php`.

## Batas dan dependensi

- URL, nama route, middleware permission, dan parameter route tetap sama. Hasil Hutan Kayu dan Bukan Kayu tetap memakai tiga kelompok permission: `produksi-hutan-negara`, `produksi-perhutanan-sosial`, dan `produksi-hutan-rakyat`. Controller masih memeriksa permission untuk `forest_type` yang dipilih.
- Nilai `ImportBatch.module_name` tetap `hasil-hutan-kayu|<forest_type>` untuk kayu, `hhbk|<forest_type>` untuk bukan kayu, `pbphh`, dan `realisasi-pnbp`. Suffix menyimpan jenis hutan batch lama dan tetap dibaca pemroses.
- Model `App\Models`, migrasi, factory, seeder, serta halaman React di `resources/js/Pages` tetap di lokasi asal. Resolver Inertia dan penyimpanan data tidak berubah.
- Workflow (`App\Actions`), staging (`App\Imports\StagingImport`), job `App\Jobs\ProcessImportBatch`, `BaseImportProcessor`, model lokasi, dan master data adalah layanan bersama. Job memetakan empat prefix batch lama ke pemroses modul ini.
- Modul harus aktif agar route tersedia. Status modul bukan sakelar untuk menghentikan queue karena kelas pemroses masih dapat dimuat Composer.

## Verifikasi dan rilis lokal

Bandingkan 60 route Bina Usaha sebelum dan sesudah migrasi pada method, URI, nama, parameter, serta middleware; perubahan action controller ke namespace modul memang diharapkan. Jalankan `BinaUsahaModuleRoutingTest`, `BinaUsahaModuleFlowTest`, pengujian PBPHH, PNBP dan permission produksi, seluruh suite PHP, build React, serta `php artisan route:cache`. Setelah penerapan, bangun kembali cache konfigurasi dan route, lalu jalankan `php artisan queue:restart` agar worker memakai pemetaan kelas baru. Tidak ada perubahan skema database atau migrasi ulang data.
