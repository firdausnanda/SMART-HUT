# Modul Dashboard

Modul `Dashboard` memiliki empat route dan `DashboardController` untuk dashboard internal, dashboard publik, perbandingan tahunan, serta ekspor ringkasan rehabilitasi lahan. Route hanya didaftarkan di `Modules/Dashboard/routes/web.php`.

## Batas dan dependensi

- URL, nama route, method, dan middleware `web`/`auth` tetap sama. Dashboard internal juga tetap memakai `verified` dan `CheckDashboardAccess`.
- Agregasi membaca model di `App\Models` dari berbagai domain. Ekspor rehabilitasi lahan memakai `Modules\Rhl\App\Exports\RehabLahanExport`. Perubahan namespace controller tidak mengubah cache key, bentuk respons, atau channel realtime.
- Halaman React `Dashboard.jsx` dan seluruh `Public`, termasuk helper khusus dashboard, berada di `Modules/Dashboard/resources/js/Pages`. Nama halaman Inertia tetap sama dan dimuat oleh resolver React pusat. Model, migrasi, event, broadcaster, dan data tetap di lokasi asal.
- Route Profile dan endpoint dropdown lokasi berada langsung di `routes/web.php`, dengan controller tetap di `App\Http\Controllers`. Keduanya dipakai lintas domain dan tidak dimiliki modul Dashboard.
- Modul harus aktif agar empat route Dashboard tersedia. Tidak ada perubahan skema database atau migrasi ulang data.

## Verifikasi dan rilis lokal

Bandingkan method, URI, nama, parameter, dan middleware sepuluh route Dashboard, Profile, dan lokasi sebelum dan sesudah migrasi; hanya action controller Dashboard yang berganti namespace. Jalankan `DashboardModuleRoutingTest`, tes agregasi/dashboard dan Profile, seluruh suite PHP, build React, serta `php artisan route:cache`. Setelah penerapan di Laragon, bangun ulang cache konfigurasi dan route.
