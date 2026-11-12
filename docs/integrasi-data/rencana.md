# Rencana integrasi data SMART-HUT

## Tujuan

SMART-HUT menyediakan data kehutanan yang sudah terverifikasi (`status = final`) kepada sistem lain melalui REST API yang aman, stabil, dan terdokumentasi. Saat ini aplikasi belum punya API integrasi. `routes/api.php` hanya berisi `/user` di belakang `auth:sanctum`, sedangkan pertukaran data massal masih lewat Import/Export Excel.

## Konsumen

Untuk saat ini, API melayani dua klien. Setiap klien punya akun klien integrasi dan token sendiri.

| Klien | Cakupan data | Modul | Arah | Status |
|---|---|---|---|---|
| **SIPETAK** | *belum ditentukan* (seluruh CDK atau CDK tertentu) | *belum ditentukan* | *belum ditentukan* | Perlu kebutuhan data dari tim SIPETAK |
| **CDK Trenggalek** | Hanya data `cdk_id` CDK Wilayah Trenggalek (Trenggalek, Tulungagung, Kediri) | *belum ditentukan* | *belum ditentukan* | Perlu kebutuhan data dari tim CDK Trenggalek |

Kolom *belum ditentukan* diisi dari kebutuhan masing-masing klien pada fase 0. Satu Data Jatim dan konsumen lain bisa ditambahkan nanti sebagai klien baru tanpa mengubah arsitektur.

## Asumsi yang perlu dikonfirmasi

1. **Arah data:** fase 1 hanya **keluar dan baca-saja**. SIPETAK dan CDK Trenggalek membaca data dari SMART-HUT. Data masuk dari sistem lain dibahas pada fase 3.
2. **Data yang dibuka:** hanya data `final`. Data `draft`, `waiting_*`, dan `rejected` tidak pernah keluar.
3. **Kepegawaian tidak dibuka**, karena modul ini berisi data pribadi (`pegawais`, `riwayat_kgbs`). Jika nanti dibutuhkan, yang dibuka hanya rekap agregat.
4. **Format:** JSON REST untuk kedua klien.

## Arsitektur

```
Sistem eksternal ──HTTPS + token──► /api/v1/...  (routes/api.php + Modules/*/routes/api.php)
                                       │
                                       ├─ middleware: auth:sanctum, ability:<modul>.read, throttle:integrasi
                                       ├─ Scope klien: filter cdk_id otomatis sesuai akun klien
                                       ├─ Controller API per modul (baca-saja)
                                       ├─ API Resource (format output stabil, terpisah dari kolom DB)
                                       └─ Query: where status = 'final', tanpa soft-deleted
```

- **Autentikasi:** Laravel Sanctum memakai *personal access token* pada **user klien integrasi** khusus, bukan akun petugas. Setiap token diberi *abilities* per modul, misalnya `rhl.read` dan `perlindungan.read`, dan dapat dicabut kapan saja.
- **Cakupan wilayah per klien:** klien integrasi disimpan di tabel `integration_clients` yang terpisah dari `users`, sehingga tidak punya password atau role web. Tabel ini punya kolom `cdk_id`. Jika `cdk_id` terisi, misalnya untuk CDK Trenggalek, semua query API otomatis dibatasi ke CDK itu lewat `IntegrationClient::applyDataScope()`, sehingga klien tidak bisa membaca data CDK lain meskipun mengirim filter `cdk_id` yang berbeda. Jika `cdk_id` kosong, klien boleh membaca seluruh provinsi. Pola ini sama dengan aturan akses user CDK di aplikasi web.
- **Versi:** semua endpoint berada di `/api/v1`. Perubahan yang merusak kompatibilitas memakai `/api/v2`, dan v1 tetap berjalan minimal 6 bulan.
- **Lokasi kode:** mengikuti struktur modular yang sudah ada. Setiap modul punya `routes/api.php`, `Http/Controllers/Api/`, dan `Http/Resources/`. Endpoint master atau referensi berada di modul `Master`.

## Desain endpoint (v1)

| Kelompok | Endpoint | Sumber tabel |
|---|---|---|
| Referensi | `GET /api/v1/wilayah/{provinsi,kabupaten,kecamatan,desa}` | `m_provinces` … `m_villages` |
| Referensi | `GET /api/v1/master/{sumber-dana,komoditas,kayu,bukan-kayu,cdk}` | tabel `m_*`, `cdks` |
| RHL | `GET /api/v1/rhl/{rehab-lahan,penghijauan,mangrove,rhl-teknis,reboisasi-ps}` | `rehab_lahan`, dst. |
| Perlindungan | `GET /api/v1/perlindungan/{kebakaran-hutan,pengunjung-wisata}` | `kebakaran_hutan`, `pengunjung_wisata` |
| Bina Usaha | `GET /api/v1/bina-usaha/{hasil-hutan-kayu,hasil-hutan-bukan-kayu,pbphh,realisasi-pnbp}` | beserta tabel `*_details` |
| Pemberdayaan | `GET /api/v1/pemberdayaan/{skps,kups,kth,nilai-ekonomi,nilai-transaksi-ekonomi}` | beserta tabel `*_details` |
| Rekap | `GET /api/v1/rekap/{modul}?group_by=tahun,kabupaten` | agregasi, di-cache |

Aturan bersama untuk semua endpoint daftar:

- **Filter:** `tahun`, `bulan`, `kabupaten_id`, `cdk_id`, dan `updated_since` (ISO 8601) untuk sinkronisasi inkremental.
- **Paginasi:** `per_page` dengan nilai maksimal 500, ditambah `meta` dan `links` standar Laravel. Rekap besar memakai *cursor pagination*.
- **Format respons:** nama field memakai bahasa Indonesia `snake_case` dan tidak terikat pada nama kolom DB. Wilayah ditampilkan sebagai kode dan nama. Data header-detail mengembalikan `details[]`. Angka desimal dikirim sebagai number, tanggal sebagai ISO 8601.
- **Field yang tidak ikut keluar:** `created_by`, `updated_by`, `deleted_by`, `rejection_note`, dan semua informasi user.
- **Penghapusan:** data yang dihapus setelah final dilaporkan lewat `GET /api/v1/{modul}/{resource}/deleted?since=` agar konsumen inkremental bisa ikut menghapus.
- **Error:** JSON seragam `{ "message": ..., "errors": ... }` dengan kode 401, 403, 404, 422, dan 429.

## Keamanan dan operasional

- Rate limit `throttle:integrasi`, misalnya 60 permintaan per menit per token, didefinisikan di `RouteServiceProvider`.
- Setiap permintaan API dicatat ke `activity_log` (Spatie): klien, endpoint, parameter, dan jumlah baris. Laravel Pulse dipakai untuk memantau latensi.
- HTTPS wajib. CORS hanya dibuka untuk domain klien yang terdaftar di `config/cors.php`.
- Halaman Admin **Klien Integrasi** dipakai untuk membuat klien, memilih abilities, menerbitkan dan mencabut token, serta melihat waktu terakhir token dipakai (`personal_access_tokens.last_used_at`).

## Dokumentasi

Dokumentasi dibuat bersamaan dengan kode, bukan sesudahnya.

1. **Spesifikasi OpenAPI 3** dibuat otomatis dari kode dengan `knuckleswtf/scribe` atau `dedoc/scramble`. Scramble direkomendasikan karena spesifikasi bisa disusun dari FormRequest dan API Resource tanpa banyak anotasi. Hasilnya `docs/integrasi-data/openapi.yaml`, dan halaman `/docs/api` hanya dapat dibuka admin atau klien.
2. **`docs/integrasi-data/README.md`** sebagai panduan integrator, berisi:
   - cara mendapatkan token dan contoh `curl`;
   - daftar endpoint, filter, dan contoh respons per modul;
   - **kamus data** per resource: nama field, tipe, satuan (ha, batang, m³, Rp), dan arti nilai;
   - pola sinkronisasi inkremental (`updated_since` dan `/deleted`);
   - kebijakan versi, rate limit, dan daftar kode error.
3. **`docs/integrasi-data/CHANGELOG.md`** untuk mencatat perubahan API per versi.
4. Tautan dari `README.md` utama, mengikuti pola yang sudah dipakai untuk `docs/database`.

## Tahapan

| Fase | Isi | Hasil |
|---|---|---|
| **0. Persiapan** | Kumpulkan kebutuhan SIPETAK dan CDK Trenggalek (modul, field, frekuensi sinkronisasi, arah data), tetapkan kamus data, pilih Scramble | Tabel konsumen lengkap dan kamus data disetujui |
| **1. Fondasi** ✅ *(selesai: API, artisan `integrasi:*`, dan halaman admin Klien Integrasi)* | Klien integrasi untuk SIPETAK dan CDK Trenggalek, scope `cdk_id` per klien, abilities, rate limit, kerangka `/api/v1`, format error, logging, halaman Admin Klien Integrasi | Token bisa diterbitkan dan dicabut |
| **2. Endpoint baca** 🟡 *(data keempat modul, `updated_since`, dan `/deleted` selesai; referensi wilayah/master dan rekap menyusul)* | Referensi wilayah dan master, lalu modul yang paling dibutuhkan kedua klien lebih dulu, kemudian modul lainnya, endpoint rekap, `updated_since` dan `/deleted` | Semua data final dapat diakses |
| **3. Opsional: data masuk** | Endpoint `POST` untuk menerima data dari sistem lain. Data masuk sebagai `draft` lewat `import_batches` atau `import_staging_rows` yang sudah ada, sehingga tetap melalui verifikasi KaCDK dan Kasi | Integrasi dua arah tanpa melewati workflow |
| **4. Opsional: push** | Webhook ke klien saat data menjadi `final`, memakai antrean (`jobs`) dan tanda tangan HMAC | Konsumen tidak perlu polling |

## Pengujian

- Feature test per endpoint dengan SQLite in-memory, mengikuti konfigurasi PHPUnit yang ada. Kasus yang diuji: tanpa token → 401; ability salah → 403; token CDK Trenggalek tidak bisa membaca data CDK lain, termasuk lewat filter `cdk_id`; data non-final dan soft-deleted tidak muncul; filter dan paginasi; field sensitif tidak ada di respons; rate limit → 429.
- Uji kontrak: respons dicocokkan dengan `openapi.yaml` agar dokumentasi tidak tertinggal dari kode.
- Uji performa endpoint rekap dan daftar terbesar dengan data produksi anonim. Bila perlu, tambahkan indeks `(status, year)` dan `updated_at`.

## Keputusan yang dibutuhkan

1. **SIPETAK:** data apa yang dibutuhkan (modul dan field), cakupannya seluruh Jawa Timur atau CDK tertentu, dan seberapa sering sinkronisasi dilakukan?
2. **CDK Trenggalek:** modul mana yang dibutuhkan, dan apakah sistem CDK Trenggalek hanya membaca data atau juga perlu mengirim data ke SMART-HUT?
3. Apakah kedua klien membutuhkan data per baris, atau cukup rekap agregat?
