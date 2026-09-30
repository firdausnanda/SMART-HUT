# Modul Master

Modul `Master` memiliki route dan controller untuk 13 data referensi: provinsi, kabupaten/kota, kecamatan, desa, bangunan KTA, sumber dana, komoditas, hasil hutan bukan kayu, hasil hutan kayu, jenis produksi, pengelola wisata, pengelola PS, dan skema perhutanan sosial. Semua 78 route fitur didaftarkan hanya di `Modules/Master/routes/web.php`.

## Batas dan dependensi

- URL, nama route, method, middleware `web`/`auth`/permission, dan parameter tetap sama. Parameter seperti `{bangunan_ktum}`, `{pengelola_wisatum}`, dan `{pengelola_p}` berasal dari penamaan resource Laravel yang sudah dipakai aplikasi.
- Model, relasi, migrasi, seeder, dan factory tetap di `App` karena data referensi ini dipakai modul lain. Halaman React tetap di `resources/js/Pages/MasterData`; nama halaman Inertia tidak berubah.
- Route CDK dan manajemen pengguna tetap di area Admin. Dashboard serta impor domain lain tetap menjadi konsumen model Master, tanpa dipindahkan ke modul ini.
- Modul harus aktif agar route Master tersedia. Modul ini tidak memiliki alur impor queue atau ekspor sendiri, dan tidak mengubah skema database.

## Verifikasi dan rilis lokal

Bandingkan 78 route Master sebelum dan sesudah migrasi pada method, URI, nama, parameter, dan middleware; action controller memang berpindah ke namespace `Modules\Master`. Jalankan `MasterModuleRoutingTest`, `MasterModuleFlowTest`, seluruh suite PHP, build React, dan `php artisan route:cache`. Setelah penerapan di Laragon, bangun ulang cache konfigurasi dan route. Tidak perlu migrasi data.
