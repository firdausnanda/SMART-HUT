# API Integrasi Data SMART-HUT

API ini dipakai sistem lain, saat ini **SIPETAK** dan **sistem CDK Trenggalek**, untuk membaca data SMART-HUT yang sudah final. Semua endpoint bersifat baca-saja dan berada di bawah `/api/v1`. Rencana lengkap ada di [rencana.md](rencana.md).

**Dokumentasi interaktif untuk pengembang klien tersedia di `/docs/api`** (publik, tanpa login). Isinya: contoh kode cURL/JavaScript/PHP, tabel field, kode error, dan fitur *Coba langsung* untuk menguji token. Kontennya diambil dari `app/Support/Integrasi/ApiDocumentation.php` dan `config/integrasi.php`.

> **Status:** semua modul data sudah tersedia: `GET /api/v1/klien`, **RHL** (5 dataset), **Perlindungan** (2), **Bina Usaha** (4), dan **Pemberdayaan** (5). Endpoint referensi wilayah/master dan rekap menyusul.

## Untuk integrator

### Autentikasi

Setiap permintaan wajib membawa token Bearer yang diterbitkan admin SMART-HUT:

```bash
curl https://<host-smart-hut>/api/v1/klien \
  -H "Authorization: Bearer 1|xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"
```

- Token hanya ditampilkan sekali saat diterbitkan. Simpan di secret manager atau variabel lingkungan, jangan di repositori.
- Token bisa memiliki tanggal kedaluwarsa. Setelah lewat, permintaan ditolak dengan 401, lalu minta token baru ke admin.
- Respons selalu JSON, walaupun klien tidak mengirim header `Accept`.

### Cakupan data

- Hanya data berstatus **final** (sudah diverifikasi KaCDK dan Kasi) yang dikirim.
- Klien yang terikat CDK, misalnya CDK Trenggalek, hanya menerima data CDK tersebut. Filter `cdk_id` lain tidak membuka data CDK lain.
- Klien tingkat provinsi, misalnya SIPETAK jika disetujui, menerima data semua CDK.

### Ability

Setiap token membawa daftar ability, dan setiap endpoint data mensyaratkan salah satunya. Token tanpa ability yang sesuai mendapat 403.

| Ability | Data |
|---|---|
| `wilayah.read` | Referensi wilayah (provinsi s.d. desa) |
| `master.read` | Data master (sumber dana, komoditas, CDK, dll.) |
| `rhl.read` | Rehabilitasi Lahan & Lingkungan |
| `perlindungan.read` | Kebakaran hutan & pengunjung wisata |
| `bina-usaha.read` | Hasil hutan, PBPHH, realisasi PNBP |
| `pemberdayaan.read` | SKPS, KUPS, KTH, nilai ekonomi |

### Endpoint

#### `GET /api/v1/klien`

Endpoint ini mengembalikan identitas klien pemilik token. Gunakan untuk menguji koneksi dan memeriksa konfigurasi token. Endpoint ini bisa dipanggil dengan ability apa pun.

```json
{
  "data": {
    "kode": "cdk-trenggalek",
    "nama": "Sistem CDK Trenggalek",
    "cakupan": "cdk",
    "cdk": { "id": 3, "kode": "CDK Wilayah Trenggalek", "nama": "CDK Wilayah Trenggalek" },
    "token": {
      "nama": "produksi",
      "abilities": ["rhl.read", "wilayah.read"],
      "kedaluwarsa": "2027-11-01T08:00:00+07:00"
    },
    "waktu_server": "2026-11-01T10:00:00+07:00"
  }
}
```

`cakupan` bernilai `provinsi` atau `cdk`. Untuk klien tingkat provinsi, `cdk` bernilai `null`. Jika token tidak kedaluwarsa, `kedaluwarsa` bernilai `null`.

#### Data RHL (ability `rhl.read`)

| Endpoint | Data | Satuan |
|---|---|---|
| `GET /api/v1/rhl/rehab-lahan` | Rehabilitasi lahan | ha |
| `GET /api/v1/rhl/penghijauan-lingkungan` | Penghijauan lingkungan | ha |
| `GET /api/v1/rhl/rehab-mangrove` | Rehabilitasi mangrove | ha |
| `GET /api/v1/rhl/reboisasi-ps` | Reboisasi area Perhutanan Sosial, beserta pengelola | ha |
| `GET /api/v1/rhl/rhl-teknis` | Bangunan konservasi tanah dan air, beserta rincian per jenis | unit |

#### Data Perlindungan (ability `perlindungan.read`)

| Endpoint | Data | Nilai |
|---|---|---|
| `GET /api/v1/perlindungan/kebakaran-hutan` | Kejadian kebakaran per lokasi, pengelola, dan fungsi kawasan | `jumlah_kejadian`, `luas_terbakar_ha` |
| `GET /api/v1/perlindungan/pengunjung-wisata` | Pengunjung objek wisata alam per pengelola | `jumlah_pengunjung`, `pendapatan_bruto_rp` |

`pengunjung-wisata` tidak menyimpan lokasi, sehingga tidak ada field `wilayah`, dan filter `kabupaten_id` ditolak dengan 422. Nilai yang di database tersimpan sebagai teks (luas terbakar, pengunjung, pendapatan) dikirim sebagai angka. Format `1,5`, `1.250,75`, dan `2.120` (ribuan) dibaca dengan benar. Nilai yang tidak bisa dibaca dikirim sebagai `null`.

#### Data Bina Usaha (ability `bina-usaha.read`)

| Endpoint | Data | Nilai |
|---|---|---|
| `GET /api/v1/bina-usaha/hasil-hutan-kayu` | Produksi kayu per lokasi, jenis hutan, dan pengelola | `target_volume_m3`, `realisasi_volume_m3`, `rincian[]` per jenis kayu |
| `GET /api/v1/bina-usaha/hasil-hutan-bukan-kayu` | Produksi hasil hutan bukan kayu | `target_volume`, `rincian[]` per komoditas beserta `satuan` |
| `GET /api/v1/bina-usaha/pbphh` | Pemegang izin industri pengolahan hasil hutan | `nilai_investasi_rp`, `jumlah_tenaga_kerja`, `beroperasi`, `jenis_produksi[]` dengan `kapasitas_izin` |
| `GET /api/v1/bina-usaha/realisasi-pnbp` | PNBP kehutanan per kabupaten dan pengelola | `target_pnbp_rp`, `realisasi_pnbp_rp` |

- `pengelola` pada hasil hutan diambil dari BKPH/KPH (Hutan Negara) atau kelompok tani (Perhutanan Sosial). Untuk Hutan Rakyat nilainya `null`.
- Satuan hasil hutan bukan kayu berbeda per komoditas, sehingga realisasinya tidak dijumlahkan.
- `pbphh` adalah data izin, bukan laporan berkala. Tidak ada `tahun`/`bulan`, dan filter `tahun`/`bulan` ditolak dengan 422.
- Tingkat wilayah yang tersimpan berbeda per dataset. Hasil hutan dan PBPHH sampai kecamatan; PNBP sampai kabupaten. Tingkat yang tidak tersimpan dikirim `null`.

#### Data Pemberdayaan (ability `pemberdayaan.read`)

| Endpoint | Data | Nilai |
|---|---|---|
| `GET /api/v1/pemberdayaan/skps` | Kelompok pemegang SK Perhutanan Sosial | `skema`, `luas_ps_ha`, `jumlah_kk`, `potensi` |
| `GET /api/v1/pemberdayaan/kups` | Kelompok Usaha Perhutanan Sosial | `kelas` (Blue/Silver/Gold/Platinum), `komoditas` |
| `GET /api/v1/pemberdayaan/perkembangan-kth` | Perkembangan Kelompok Tani Hutan | `kelas_kelembagaan`, `jumlah_anggota`, `luas_kelola_ha` |
| `GET /api/v1/pemberdayaan/nilai-ekonomi` | Nilai ekonomi kelompok per bulan | `total_nilai_transaksi_rp`, `rincian[]` per komoditas |
| `GET /api/v1/pemberdayaan/nilai-transaksi-ekonomi` | Nilai transaksi ekonomi KTH per bulan | `total_nilai_transaksi_rp`, `rincian[]` per komoditas |

- `skps` dan `kups` adalah data kelembagaan, bukan laporan berkala. Tidak ada `tahun`/`bulan`, dan filter `tahun`/`bulan` ditolak dengan 422.
- `rincian[]` memuat `komoditas`, `volume_produksi`, `satuan`, dan `nilai_transaksi_rp`. Pada nilai transaksi ekonomi, satu komoditas bisa muncul beberapa kali (beberapa transaksi).

Setiap endpoint di atas juga punya `.../deleted?since=` yang mengembalikan ID data yang dihapus atau tidak lagi final sejak waktu tersebut.

- **Filter:** `tahun`, `bulan`, `kabupaten_id` (kode 4 digit, mis. `3503`), `cdk_id`, dan `updated_since` (ISO 8601).
- **Paginasi:** `per_page` 1 sampai 500 (bawaan 100). Ikuti `links.next` sampai bernilai `null`. Total ada di `meta.total`.
- **Urutan:** dari `diperbarui_pada` paling lama, lalu `id`.
- **Format data:** setiap baris memuat `id`, `tahun`, `bulan`, `cdk`, `wilayah` (provinsi s.d. desa, masing-masing `{id, nama}`), nilai target dan realisasi, `sumber_dana`, serta `difinalkan_pada`, `dibuat_pada`, dan `diperbarui_pada`.

```bash
curl "https://<host-smart-hut>/api/v1/rhl/rehab-lahan?tahun=2026&per_page=100" \
  -H "Authorization: Bearer $SMARTHUT_TOKEN"
```

#### Pola sinkronisasi

1. **Pengambilan pertama:** ambil semua halaman, simpan dengan `id` sebagai kunci, dan catat `waktu_server` dari halaman pertama.
2. **Berikutnya:** panggil `?updated_since=<waktu_server tercatat>`, lalu simpan atau timpa data berdasarkan `id`.
3. **Data yang ditarik:** panggil `/deleted?since=<waktu_server tercatat>` dan hapus ID yang dikembalikan dari salinan Anda.
4. Simpan `waktu_server` dari halaman pertama langkah 2 untuk sinkronisasi berikutnya.

Detail lengkap tiap field ada di `/docs/api`.

### Rate limit

Setiap klien dibatasi **60 permintaan per menit** (bisa diatur lewat `INTEGRASI_RATE_LIMIT`). Jika batas terlampaui, API mengembalikan 429 dengan header `Retry-After` (dalam detik). Header `X-RateLimit-Limit` dan `X-RateLimit-Remaining` ada di setiap respons.

### Kode error

Semua error memakai bentuk `{ "message": "..." }`. Error validasi menambahkan `errors` per field.

| Kode | Arti |
|---|---|
| 401 | Token tidak ada, salah, sudah dicabut, atau kedaluwarsa (`"Unauthenticated."`) |
| 403 | Klien dinonaktifkan, token bukan milik klien integrasi, atau ability kurang |
| 404 | Endpoint atau data tidak ditemukan |
| 422 | Parameter tidak valid |
| 429 | Rate limit terlampaui |

## Untuk admin SMART-HUT

Klien dan token dikelola lewat halaman **Pengaturan → Klien Integrasi** (`/integrasi-klien`). Halaman ini hanya muncul untuk role `admin`. Admin bisa:

- menambah, mengubah, menonaktifkan, dan menghapus klien, termasuk memilih cakupan seluruh provinsi atau satu CDK;
- menerbitkan token dengan pilihan akses data dan masa berlaku. Token tampil **sekali** di layar, lengkap dengan tombol salin dan contoh `curl`;
- melihat token aktif, waktu terakhir dipakai, status kedaluwarsa, dan jumlah permintaan 24 jam terakhir, lalu mencabut token.

Perintah artisan di bawah ini melakukan hal yang sama dari server, berguna untuk otomasi atau bila UI tidak bisa diakses. Semua perubahan, baik dari UI maupun artisan, tercatat di `activity_log` dengan `log_name = integrasi`.

### 1. Jalankan migrasi (sekali)

```bash
php artisan migrate
```

Migrasi membuat tabel `integration_clients`. Token disimpan di tabel bawaan Sanctum, `personal_access_tokens`.

### 2. Daftarkan klien

```bash
php artisan integrasi:klien-buat cdk-trenggalek "Sistem CDK Trenggalek" --cdk="CDK Wilayah Trenggalek"
```

```bash
php artisan integrasi:klien-buat sipetak "SIPETAK"
```

`--cdk` menerima id atau kode CDK. Tanpa `--cdk`, klien membaca data seluruh provinsi. Untuk SIPETAK, tetapkan dulu cakupannya sebelum klien didaftarkan.

### 3. Terbitkan token

```bash
php artisan integrasi:token-buat cdk-trenggalek --ability=rhl.read --ability=wilayah.read --nama=produksi --hari=365
```

Token tampil satu kali di terminal. Kirim ke tim klien lewat kanal yang aman. Beri hanya ability yang benar-benar dibutuhkan.

### 4. Pantau dan cabut

```bash
php artisan integrasi:klien-daftar
```

```bash
php artisan integrasi:token-cabut cdk-trenggalek --token=5
```

```bash
php artisan integrasi:token-cabut cdk-trenggalek --semua
```

```bash
php artisan integrasi:klien-status cdk-trenggalek nonaktif
```

`klien-daftar` menampilkan token aktif dan waktu terakhir dipakai. `klien-status nonaktif` menghentikan akses sementara tanpa mencabut token, sehingga akses bisa dipulihkan dengan `aktif`.

### Log akses

Setiap permintaan API dicatat ke `activity_log` (`log_name = integrasi`, `event = api_request`), berisi klien, metode, path, query, status HTTP, durasi, ID token, dan IP. Pencatatan bisa dimatikan dengan `INTEGRASI_LOG_REQUESTS=false`.

## Untuk pengembang

| Bagian | Berkas |
|---|---|
| Rute | `routes/api.php` (grup `v1`) |
| Model klien dan scope data | `app/Models/IntegrationClient.php` (`applyDataScope`) |
| Middleware | `app/Http/Middleware/{ForceJsonResponse,EnsureIntegrationClient,LogIntegrationRequest}.php` |
| Rate limit | `app/Providers/RouteServiceProvider.php` (`integrasi`) |
| Konfigurasi | `config/integrasi.php` |
| Halaman admin | `app/Http/Controllers/IntegrasiKlienController.php`, `resources/js/Pages/IntegrasiKlien/` |
| Penerbitan/pencabutan token | `app/Services/IntegrationTokenService.php` |
| Perintah artisan | `app/Console/Commands/Integrasi*.php` |
| Halaman dokumentasi | `app/Http/Controllers/ApiDocumentationController.php`, `app/Support/Integrasi/ApiDocumentation.php`, `resources/js/Pages/Docs/Api.jsx` |
| Tes | `tests/Feature/IntegrasiApiFoundationTest.php`, `tests/Feature/IntegrasiKlienAdminTest.php`, `tests/Feature/IntegrasiApiDocumentationTest.php` |

Endpoint data baru di fase 2 wajib:

1. memakai grup middleware `v1` dan menambahkan `ability:<modul>.read`;
2. membangun query lewat `$request->user()->applyDataScope(Model::query())`, supaya filter status final dan CDK selalu berlaku;
3. mengembalikan data lewat API Resource, bukan model mentah, dan tidak menyertakan `created_by`, `updated_by`, `deleted_by`, atau `rejection_note`;
4. didaftarkan di `ApiDocumentation::endpoints()` (parameter, field, contoh respons) agar muncul di `/docs/api`. Tes `IntegrasiApiDocumentationTest` gagal jika ada rute `/api/v1` yang belum terdokumentasi.
