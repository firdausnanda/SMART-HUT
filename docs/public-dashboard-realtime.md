# Realtime dashboard publik

Dashboard utama dan YoY menerima pemberitahuan ketika data berstatus `final` dibuat, disunting, dihapus, atau dipulihkan. Pemberitahuan hanya berisi domain, tahun, dan CDK; browser mengambil ulang statistik lewat rute dashboard yang sudah ada. Jika koneksi WebSocket terputus, dashboard tetap memeriksa data setiap lima menit.

## Konfigurasi produksi

Isi `.env` di server dengan `APP_URL` yang sesuai alamat publik dan tiga kredensial Reverb unik: `REVERB_APP_ID`, `REVERB_APP_KEY`, dan `REVERB_APP_SECRET`. Jika host dashboard berbeda dari `APP_URL`, isi `REVERB_ALLOWED_ORIGINS` dengan host publik yang diizinkan, dipisahkan koma. Jangan menaruh secret di konfigurasi frontend. `docker-compose.yml` sudah mengatur `BROADCAST_CONNECTION=reverb`, cache database bersama, proses Reverb, serta pekerja antrean `dashboard-broadcasts`.

Jalankan ulang image aplikasi, worker, dan Reverb secara bersamaan setelah perubahan kode. Proxy di depan aplikasi harus meneruskan WebSocket Upgrade pada `/app/` melalui HTTPS/WSS; Nginx dalam container sudah meneruskan `/app/` dan `/apps/` ke Reverb. Pastikan proxy luar mengizinkan koneksi panjang dan tidak memotong Upgrade. Deployment tidak memerlukan migrasi baru jika tabel cache dan jobs Laravel yang sudah digunakan aplikasi tersedia.

## Verifikasi

1. Buka dashboard utama dan YoY di browser pengguna yang berwenang. Pada alat jaringan browser, pastikan koneksi `wss://<host>/app/<key>` berhasil dan permintaan `/broadcasting/auth` untuk kanal privat berstatus 200.
2. Finalisasi atau edit satu laporan pada CDK tertentu. Pastikan kedua dashboard dengan tahun/CDK terkait memperbarui statistik tanpa memindahkan slide, pilihan tahun, CDK, atau satuan. Dashboard CDK lain tidak perlu memuat ulang.
3. Uji penghapusan, pemulihan, dan pembatalan transaksi. Perubahan yang dibatalkan tidak boleh memicu pembaruan.
4. Putuskan koneksi WebSocket sementara. Dashboard tetap memperoleh data baru melalui pemeriksaan lima menit.

Jika koneksi WSS gagal, periksa dukungan Upgrade pada proxy luar, origin Reverb, kredensial, dan status antrean `dashboard-broadcasts`. Jika koneksi berhasil tetapi data terlambat, periksa pekerja antrean serta koneksi cache database bersama.
