# Modul Perlindungan

Modul `Perlindungan` memiliki alur Kebakaran Hutan dan Pengunjung Wisata: route, controller, validasi dan pemrosesan impor, impor langsung, serta ekspor Excel. Semua route kedua fitur hanya didaftarkan di `routes/web.php` modul ini.

## Batas dan dependensi

- URL, nama route `kebakaran-hutan.*` dan `pengunjung-wisata.*`, permission, parameter `{kebakaran_hutan}` dan `{pengunjung_wisata}`, serta nilai `ImportBatch.module_name` masing-masing adalah kontrak yang tetap.
- Model `App\Models\KebakaranHutan` dan `App\Models\PengunjungWisata`, migrasi database, factory, seeder, serta halaman React `resources/js/Pages/KebakaranHutan` dan `resources/js/Pages/PengunjungWisata` tetap di lokasi asal agar referensi dashboard, activity log, dan resolver Inertia tidak berubah.
- Workflow (`App\Actions`), staging dan job impor (`App\Imports\StagingImport`, `App\Jobs\ProcessImportBatch`), model lokasi, dan `ImportBatch` adalah layanan bersama. Pemetaan job untuk `kebakaran-hutan` menunjuk pemroses di modul ini.
- Nama modul paket `Perlindungan` berbeda dari nilai `module_name` batch impor `kebakaran-hutan` dan `pengunjung-wisata`. Jangan mengganti nilai batch; data lama masih memakainya.
- Modul harus aktif untuk menyediakan route kedua fitur. Job impor masih dapat memuat kelas pemroses melalui Composer ketika modul dinonaktifkan, sehingga jangan gunakan status modul sebagai sakelar untuk menghentikan queue.

## Pengembangan dan rilis

Jalankan pengujian `PerlindunganModuleRoutingTest`, kedua `*ModuleFlowTest`, dan kedua `*ImportModuleTest` setelah mengubah alur. Saat deploy, bersihkan cache konfigurasi dari rilis lama **sebelum** memuat aplikasi dengan paket baru (`php artisan config:clear`), lalu pasang dependensi dari lockfile dan pastikan `modules_statuses.json` mengaktifkan `Perlindungan`. Setelah itu bangun ulang cache konfigurasi (`php artisan config:cache`) dan route (`php artisan route:cache`), periksa route kedua fitur, lalu mulai ulang queue worker agar pemetaan kelas pemroses baru dipakai. Tidak ada migrasi skema khusus untuk pemindahan ini.

RHL, Bina Usaha, Pemberdayaan, dan Kepegawaian sudah dipisahkan ke modul masing-masing. Data bersama, admin, dan Dashboard dikerjakan setelah batas dependensi antar domain stabil.
