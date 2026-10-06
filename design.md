# SMART-HUT: halaman sambutan berbasis peta

## Arah

Halaman ini adalah pintu masuk data kehutanan Dinas Kehutanan Provinsi Jawa Timur. Satu section menyatukan identitas, ajakan masuk, data aktual, dan peta interaktif. Header menempatkan lambang Jawa Timur serta nama Dinas Kehutanan di kiri, dan logo Gerbang Baru Nusantara di kanan. Komposisi hero memakai ruang putih, judul hijau besar di kiri, serta peta Jawa Timur di kanan dengan bidang mint lembut, terinspirasi dari hero 28Byte dan disesuaikan untuk layanan kehutanan. Dial visual: ENERGY 2 / RHYTHM 2 / MOTION 2.

Peta memakai bidang daratan hijau datar, garis batas kabupaten/kota yang tipis, garis pesisir yang lebih tegas, dan bayangan tipis agar bentuk wilayah tetap jelas. Tidak ada label atau titik kota di atas peta. Bidang mint memberi fokus pada peta tanpa mengesankan peta sebaran data.

## Hierarki

1. **Identitas dan maksud:** SMART-HUT, Dinas Kehutanan Provinsi Jawa Timur, pesan singkat, dan jalur masuk.
2. **Peta Jawa Timur:** batas provinsi, 38 kabupaten/kota, dan pulau utama dari sumber GeoJSON, ditampilkan sebagai SVG ringan tanpa label wilayah di atas peta.
3. **Eksplorasi wilayah:** gunakan kontrol zoom untuk melihat garis wilayah lebih dekat, geser mouse, atau kembali ke tampilan provinsi yang utuh.
4. **Data aktual:** hanya `totalData` dari backend, berlabel “Total data terinput”.
5. **Jalur dashboard:** pengunjung melihat tombol Masuk Sekarang sebagai tombol pertama, diikuti tombol Infografis Tahun Berjalan dan Infografis Year on Year. Di desktop, tombol memakai dua kolom seperti sebelumnya; di ponsel ketiganya bertumpuk. Ketiga tombol berukuran sama; jika memilih salah satu dashboard lebih dulu, pengunjung melewati login sebelum menuju dashboard yang dipilih.

Peta menunjukkan batas provinsi dan kabupaten/kota yang disederhanakan, tanpa klaim sebaran hutan. Tidak ada heatmap atau status live.

## Tipografi, warna, dan bentuk

Outfit dipakai untuk judul dan angka karena bentuknya padat serta jelas pada skala besar; Plus Jakarta Sans dipakai untuk isi dan navigasi. Hijau `#1F6E46` adalah identitas, putih hangat `#FBFCF9` memberi ruang baca, dan mint `#DCF3E7` membingkai peta. Kuning cerah `#EAB308` menekankan kata “SMART” pada judul “SMART-HUT” dan tombol Infografis Tahun Berjalan. Bayangan dipakai pada peta untuk memperjelas elevasi visual, bukan sebagai gaya semua komponen.

## Interaksi dan gerak

Kontrol +, −, dan Reset mengubah skala sungguhan; mouse dapat menggeser peta setelah diperbesar. Tombol utama di bawah peta memperbesar atau mengembalikan tampilan utuh, dengan keterangan yang diperbarui lewat `aria-live`. Peta muncul sekali, garis wilayah tergambar bertahap, dan wilayah yang dilewati kursor tersorot halus tanpa label tambahan. Garis pesisir muncul sekali dan perpindahan skala memakai transisi singkat. Preferensi reduced motion menghentikan animasi. Tautan dan tombol memiliki fokus keyboard yang terlihat. Untuk tamu, ajakan masuk menuju halaman login.

## Responsif

Hero memakai tinggi `100svh` dan menyesuaikan ukuran peta serta jarak vertikal supaya desktop dan tablet tetap satu layar. Pada lebar lebih dari 600 px, pesan dan peta berdampingan. Pada ponsel, peta disembunyikan; judul, penjelasan dengan tombol masuk, dan jumlah data disusun rapat sebagai satu kelompok di tengah layar. Pada layar ponsel yang sangat pendek, halaman boleh bergulir agar teks tidak terpotong. Tidak boleh ada scroll horizontal.

## Sumber dan batasan

Geometri provinsi kode 35 dan 38 kabupaten/kota di dalamnya bersumber dari [Indonesia-GeoJSON](https://github.com/AlfianAliM/Indonesia-GeoJSON) berlisensi MIT, disederhanakan untuk tampilan. Untuk kebutuhan pengukuran GIS atau batas administratif resmi, gunakan data geospasial yang lebih rinci.

Pertahankan React, Inertia, Laravel, route yang ada, dan `totalData` dari `WelcomeController`.
