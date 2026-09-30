# Skema database SMART-HUT

[`erd.dbml`](erd.dbml) adalah dokumentasi struktur fisik database MySQL SMART-HUT. Diagram ini memuat 64 tabel, kolom, primary key, unique key, default, dan 57 foreign key yang terdaftar pada database lokal saat ekspor. `TableGroup` menunjukkan domain pemilik tabel; kelompok bukan database atau schema MySQL yang terpisah.

Untuk melihat diagram, buka `erd.dbml` di editor DBML atau dbdiagram.io. Gunakan kelompok domain untuk menelusuri satu fitur; Dashboard hanya membaca tabel dari domain lain sehingga tidak memiliki kelompok tabel sendiri.

## Memperbarui DBML

Setelah migrasi diterapkan pada database MySQL pengembangan, jalankan dari root proyek:

```bash
php scripts/export-dbml.php
```

Perintah ini hanya membaca metadata `information_schema` dan menulis ulang `docs/database/erd.dbml`. Tidak ada data baris, kata sandi, atau string koneksi yang diekspor. Periksa diff sebelum menyimpan perubahan. Jika ada tabel baru, tambahkan ke kelompok yang sesuai di `scripts/export-dbml.php`; tabel yang belum dikenal akan masuk `BelumDiklasifikasi`.

DBML menggambarkan skema yang benar-benar ada di database sumber. Relasi Eloquent tanpa foreign key MySQL tidak otomatis muncul sebagai `Ref`. Perubahan skema aplikasi tetap dibuat melalui migrasi, bukan dengan mengedit DBML. Empat tabel wilayah dibuat oleh `database/sql/wilayah.sql` melalui `WilayahSeeder`, bukan oleh migrasi pembuat tabel.
