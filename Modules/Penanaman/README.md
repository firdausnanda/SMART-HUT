# Modul Penanaman

`Penanaman` adalah modul mandiri untuk laporan penanaman kabupaten/kota Jawa Timur. Route, controller, dan halaman Inertia berada di modul ini. Model bersama dan migrasi tetap berada di `app/Models` dan `database/migrations`, mengikuti pola repositori.

Operator kabupaten/kota hanya mengakses wilayahnya. Target luas disimpan sekali per wilayah per tahun; input dan tampilan target batang sementara disembunyikan. Nilai batang lama tetap tersimpan, dan target baru memakai nilai awal 0. Laporan lokasi bulanan memuat sumber dana dari master yang sama dengan Penghijauan Lingkungan. Form jenis tanaman sementara disembunyikan; rincian yang sudah tersimpan tetap dipertahankan dan tidak diwajibkan untuk pengajuan. Laporan disimpan sebagai draft, lalu diajukan melalui tombol dan dialog konfirmasi tanpa keterangan wajib. Pengajuan yang lengkap langsung menjadikannya final. Admin provinsi dapat mengembalikan final ke draft dengan alasan; admin atau operator dapat memperbaiki dan mengajukan ulang. Riwayat pengajuan tetap tersimpan. Draft dapat dihapus oleh operator wilayahnya atau admin provinsi setelah konfirmasi; laporan final tidak dapat dihapus.

Aktifkan modul melalui `modules_statuses.json`, jalankan migrasi penanaman, lalu buat akun tiap wilayah di halaman Akun Wilayah dengan email resmi, username, dan password awal. Password disimpan sebagai hash. Verifikasi dengan `PenanamanModuleTest`, pengujian kepemilikan halaman Inertia, dan build React.

Pada halaman Akun Wilayah, admin dapat mengubah nama, email, username, wilayah, serta password operator. Password baru boleh dikosongkan. Akun dapat dihapus setelah konfirmasi; laporan dan target wilayah tetap tersimpan, sementara rujukan pelaku pada laporan lama mengikuti aturan `nullOnDelete`.

`php artisan db:seed --class=PenanamanOperatorSeeder` membuat satu akun awal untuk setiap kabupaten/kota Jawa Timur yang belum memiliki operator. Seeder ini juga dipanggil oleh `DatabaseSeeder` setelah master wilayah. Username awal memakai pola `operator_<jenis wilayah>_<nama wilayah>`, misalnya `operator_kabupaten_malang` atau `operator_kota_malang`. Email awal memakai username tersebut dengan domain `@smarthut.jatim.prov`. Password awal memakai pola `password_<jenis wilayah>_<nama wilayah>`. Admin perlu mengganti password awal sebelum akun diserahkan. Menjalankan seeder ulang tidak mengubah akun yang sudah ada.

## Export dan import

Tombol **Export** menyediakan filter tahun, bulan, status, sumber dana, kabupaten/kota (untuk admin), dan pilihan kolom. Operator hanya dapat mengekspor laporan wilayahnya. File berisi satu baris per laporan; target tahunan tidak diulang pada setiap baris.

Tombol **Import** menyediakan template Excel. Isi kolom tahun, bulan (angka 1–12), nama kabupaten/kota, kecamatan, desa/kelurahan, nama lokasi, koordinat (opsional), realisasi luas (Ha), dan sumber dana. Nama wilayah dan sumber dana harus cocok dengan data master. Unggahan XLSX, XLS, atau CSV dibatasi 10 MB. Halaman pratinjau menandai kesalahan per baris, lalu hanya baris valid yang diproses. Semua laporan hasil impor berstatus **draft** dan tetap perlu diajukan melalui alur Penanaman. Operator tidak dapat mengimpor wilayah lain. Impor menggunakan antrean `imports`; jalankan worker antrean saat `QUEUE_CONNECTION` bukan `sync`.
