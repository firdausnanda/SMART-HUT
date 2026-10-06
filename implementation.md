# Implementasi halaman sambutan SMART-HUT

## Berkas

- `resources/js/Pages/Welcome.jsx`: satu section hero, rute, jumlah data, dan kontrol peta.
- `resources/js/Pages/welcome.css`: komposisi hero, warna, responsivitas, fokus, dan animasi.
- `public/img/logo.webp`: lambang Jawa Timur pada identitas Dinas Kehutanan di kiri header.
- `public/img/logo_gerbang_nusantara.png`: logo Gerbang Baru Nusantara di sisi kanan header.
- `resources/js/Pages/jawaTimurGeometry.js`: path SVG provinsi dan 38 kabupaten/kota serta parameter proyeksi Jawa Timur.
- `scripts/generate_welcome_map.py`: pembangun ulang geometri dari GeoJSON sumber.

## Sumber geometri

Unduh [`provinsi.geojson`](https://github.com/AlfianAliM/Indonesia-GeoJSON/blob/master/provinsi.geojson) dan [`kab_kota.geojson`](https://github.com/AlfianAliM/Indonesia-GeoJSON/blob/master/kab_kota.geojson) dari repositori Indonesia-GeoJSON (lisensi MIT), lalu jalankan `python scripts/generate_welcome_map.py path/to/provinsi.geojson path/to/kab_kota.geojson`. Skrip mengambil feature provinsi kode `35` dan semua 38 kabupaten/kota berkode `35.*`, lalu memproyeksikannya ke viewBox 806 × 543. Untuk siluet provinsi, 11 poligon utama dengan luas lebih dari `0.001` derajat persegi dipakai. Garis kabupaten/kota dipotong mengikuti siluet itu; pulau sangat kecil tidak ditampilkan pada skala halaman ini.

Peta menampilkan siluet provinsi berwarna hijau datar dengan garis batas kabupaten/kota yang lebih tipis daripada garis luar provinsi. Label dan titik kota tidak ditampilkan. Garis wilayah dan siluet memakai parameter proyeksi yang sama agar tetap sejajar.

## Data dan interaksi

`totalData` berasal dari `WelcomeController`, dinormalisasi ke angka, lalu diformat `id-ID`. Dua tombol hero menuju `public.dashboard` dan `public.dashboard-yoy`; tombol Masuk Sekarang menuju `login` bagi pengunjung. Kedua rute dashboard berada di dalam middleware `auth`: pengunjung yang belum masuk diarahkan ke login, lalu kembali ke dashboard pilihannya. Navigasi header tetap hanya tampil bagi pengguna yang sudah masuk dan pada layar lebar. Header menampilkan `logo.webp` serta identitas Dinas Kehutanan di kiri dan `logo_gerbang_nusantara.png` di kanan. Tidak ada data rekaan atau hitung animasi.

Kontrol zoom menggunakan langkah 0,5× dari 1× hingga 2,5×; Reset mengembalikan tampilan awal. Pada layar besar tombol di bawah peta memperbesar atau mengembalikan peta utuh, dan keterangannya berubah melalui `aria-live="polite"`. Pada ponsel, peta dan tautan lompat ke peta disembunyikan agar konten utama tampil rapi dalam satu layar. Pointer mouse dapat menggeser peta setelah diperbesar. Garis pesisir muncul satu kali; `prefers-reduced-motion: reduce` menghentikan animasi.

## Pemeriksaan

- `npm run build`
- `AuthenticationTest`: 5 tes, 12 asersi, termasuk kembali ke dashboard Year on Year setelah login.
- `git diff --check`
- Pratinjau 1280 × 720, 1280 × 600, 800 × 700, 601 × 700, serta ponsel 390 × 844, 360 × 568, dan 320 × 600.
- Uji tinggi dan lebar dokumen terhadap viewport, 38 garis wilayah, zoom, perubahan keterangan, dan konsol browser.
