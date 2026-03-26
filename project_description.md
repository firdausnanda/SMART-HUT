# 📋 Project Description: SMART-HUT

## 🧾 Identitas Proyek

| Atribut | Detail |
|---|---|
| **Nama Aplikasi** | SMART-HUT |
| **Kepanjangan** | Sistem Informasi Data Kehutanan |
| **Jenis Aplikasi** | Sistem Informasi Web (Web-based Information System) |
| **Framework Backend** | Laravel 10 |
| **Framework Frontend** | React + Inertia.js (SPA) |
| **Database** | MySQL |
| **Status** | Aktif (Production) |
| **Bahasa** | Indonesia |

---

## 📖 Gambaran Umum

**SMART-HUT** adalah aplikasi sistem informasi berbasis web yang dirancang untuk mengelola, memantau, dan melaporkan data kehutanan secara terintegrasi. Aplikasi ini digunakan oleh instansi/dinas kehutanan untuk mendokumentasikan berbagai kegiatan kehutanan mulai dari rehabilitasi lahan, perhutanan sosial, pengelolaan hasil hutan, hingga analisis nilai ekonomi sumber daya hutan.

Sistem ini menerapkan alur kerja (*workflow*) berbasis status untuk setiap data yang diinput, memungkinkan proses verifikasi dan validasi data secara berjenjang (Operator → CDK → Kasi → Final) sebelum data diterima sebagai data resmi.

---

## 🎯 Tujuan Proyek

- Digitalisasi pencatatan dan pelaporan data kehutanan yang sebelumnya dilakukan secara manual.
- Menyediakan **dashboard interaktif** dengan visualisasi data (grafik & statistik) untuk mendukung pengambilan keputusan.
- Memfasilitasi proses **impor/ekspor data massal** via Excel untuk efisiensi input data lapangan.
- Menerapkan **RBAC (Role-Based Access Control)** agar setiap pengguna hanya dapat mengakses dan memodifikasi data sesuai wewenangnya.
- Mempermudah pembuatan **laporan resmi** instansi kehutanan dalam format Excel yang terstandarisasi.

---

## 🚀 Teknologi yang Digunakan

### Backend
| Teknologi | Versi | Fungsi |
|---|---|---|
| **PHP** | >= 8.1 | Bahasa pemrograman utama |
| **Laravel** | 10.x | Framework backend MVC |
| **MySQL** | - | Database relasional |
| **Laravel Sanctum** | ^3.2 | Autentikasi API/session |
| **Spatie Permission** | ^6.x | Role & Permission (RBAC) |
| **Spatie Activity Log** | ^4.x | Pencatatan log aktivitas pengguna |
| **Maatwebsite Excel** | ^3.1 | Import & Export data Excel |
| **Spatie Backup** | ^8.x | Backup database otomatis |
| **Ziggy** | ^2.0 | Generator route Laravel ke JavaScript |
| **Yaza Google Drive Storage** | ^2.0 | Penyimpanan backup ke Google Drive |
| **Lab404 Impersonate** | ^1.7 | Fitur impersonasi user (admin) |
| **OpcodeIO Log Viewer** | ^3.x | Viewer log aplikasi |

### Frontend
| Teknologi | Versi | Fungsi |
|---|---|---|
| **Inertia.js** | ^0.6.3 | Jembatan Laravel ↔ React (SPA tanpa API terpisah) |
| **React** | - | Library UI frontend |
| **Tailwind CSS** | - | Framework CSS utility-first |
| **Vite** | - | Build tool & dev server frontend |
| **Chart.js (React Chartjs 2)** | - | Visualisasi data grafik pada dashboard |

---

## 🔐 Sistem Otorisasi & Role

Aplikasi mengimplementasikan **Role-Based Access Control (RBAC)** menggunakan **Spatie Laravel Permission**. Setiap role memiliki izin (*permission*) yang berbeda-beda per modul.

### Hierarki Role
```
Admin (Super)
  └── Kasi (Kepala Seksi)
        └── KaCDK (Kepala CDK)
              └── PK / PEH / Pelaksana (Operator Lapangan)
```

### Matriks Permission per Modul
| Permission | Pelaksana/PK/PEH | KaCDK | Kasi | Admin |
|---|:---:|:---:|:---:|:---:|
| `*.view` (Lihat) | ✅ | ✅ | ✅ | ✅ |
| `*.create` (Tambah) | ✅ | ❌ | ❌ | ✅ |
| `*.edit` (Edit + Submit) | ✅ | ❌ | ❌ | ✅ |
| `*.delete` (Hapus) | ✅ (draft saja) | ❌ | ❌ | ✅ |
| `*.approve` (Verifikasi/Tolak) | ❌ | ✅ | ✅ | ✅ |

> **Catatan:** KaCDK hanya bisa melakukan verifikasi pertama (`waiting_cdk`), Kasi melanjutkan verifikasi kedua (`waiting_kasi`), dan data berstatus `final` setelah disetujui Kasi.

---

## 🔄 Alur Kerja Data (Workflow)

Setiap data utama mengikuti alur verifikasi berjenjang:

```
┌──────────┐    Submit     ┌───────────────┐   Approve    ┌───────────────┐   Approve    ┌───────────┐
│  DRAFT   │ ───────────► │ WAITING CDK   │ ───────────► │ WAITING KASI  │ ───────────► │   FINAL   │
│ (Operator│              │  (Verifikasi   │              │  (Verifikasi   │              │ (Approved)│
│  Input)  │              │   KaCDK)       │              │   Kasi)        │              └───────────┘
└──────────┘              └───────────────┘              └───────────────┘
                                 │                                │
                          Reject │                         Reject │
                                 ▼                                ▼
                           ┌──────────┐                    ┌──────────┐
                           │ REJECTED │                    │ REJECTED │
                           │(+ Catatan│                    │(+ Catatan│
                           │ Penolakan│                    │ Penolakan│
                           └──────────┘                    └──────────┘
                                 │                                │
                          ───────┘ (Operator edit & re-submit) ──┘
```

### Aksi Workflow yang Tersedia
| Aksi | Trigger | Permission |
|---|---|---|
| `submit` | Operator mengajukan data ke CDK | `*.edit` |
| `approve` | CDK/Kasi menyetujui data | `*.approve` |
| `reject` | CDK/Kasi menolak dengan catatan | `*.approve` |
| `delete` | Menghapus data | `*.delete` |

Semua aksi bisa dilakukan secara **individual** (satu data) maupun **bulk** (banyak data sekaligus dengan checkbox).

---

## 📊 Fitur CRUD Detail per Modul

Setiap modul dalam SMART-HUT memiliki fitur lengkap berikut:

### ✅ Fitur CRUD Standar

#### 1. **Index (Daftar Data)**
- Tampilan tabel dengan **paginasi** (10/25/50 data per halaman)
- **Filter berdasarkan Tahun** — dapat beralih antar tahun dengan dropdown
- **Pencarian real-time** — berdasarkan nama lokasi, sumber dana, komoditas, dll.
- **Sorting kolom** — klik header kolom untuk sort ascending/descending
- **Badge status** — tampilan warna berbeda per status (Draft, Waiting, Final, Rejected)
- **Statistik ringkasan** di bagian atas (total target, total realisasi, jumlah data—di-cache 5 menit)
- **Smart sorting** — data prioritas sesuai role (KaCDK lihat `waiting_cdk` duluan, Kasi lihat `waiting_kasi` duluan)
- **Seleksi massal** (checkbox) untuk bulk action

#### 2. **Create (Tambah Data)**
- Form terstruktur dengan **validasi server-side** menggunakan Laravel Validation
- **Pemilihan lokasi bertingkat** (Provinsi → Kabupaten → Kecamatan → Desa) via AJAX
- Input detail/sub-item (contoh: beberapa komoditas dalam satu entri Nilai Ekonomi)
- Kalkulasi otomatis (total transaksi dihitung dari detail)
- Data tersimpan dengan status awal `draft` dan `created_by` otomatis dari user login
- **Transaksi database** (DB::beginTransaction) untuk menjamin konsistensi data master + detail

#### 3. **Edit (Ubah Data)**
- Form pra-isi (*prefilled*) dengan data yang sudah ada
- Validasi sama dengan form create
- Pada modul berdetail (misal: Nilai Ekonomi, Hasil Hutan): detail lama **dihapus dan diganti** seluruhnya
- Mencatat `updated_by` otomatis dari user login
- Edit hanya bisa dilakukan pada data berstatus `draft` atau `rejected`

#### 4. **Delete (Hapus Data)**
- Soft delete dengan mencatat `deleted_by` sebelum dihapus
- **Proteksi berbasis role**: Kasi/KaCDK tidak bisa menghapus
- **Proteksi berbasis status**: Operator hanya bisa menghapus data berstatus `draft`
- Admin bisa menghapus data dengan status apapun

---

### 📌 Fitur Khusus per Modul

#### Modul dengan Data Master + Detail (Header-Line)
Beberapa modul menggunakan struktur **header + detail** (one-to-many):

| Modul | Tabel Header | Tabel Detail |
|---|---|---|
| Nilai Ekonomi | `nilai_ekonomi` | `nilai_ekonomi_details` (komoditas) |
| Nilai Transaksi Ekonomi | `nilai_transaksi_ekonomi` | `nilai_transaksi_ekonomi_details` |
| Hasil Hutan Kayu | `hasil_hutan_kayu` | `hasil_hutan_kayu_details` |
| Hasil Hutan Bukan Kayu | `hasil_hutan_bukan_kayu` | `hasil_hutan_bukan_kayu_details` |
| RHL Teknis | `rhl_teknis` | `rhl_teknis_details` |

---

## 📥 Fitur Import Excel (Data Massal)

Setiap modul utama mendukung **import data massal melalui file Excel**.

### Alur Import
```
1. User unduh template    →    2. Isi data di Excel    →    3. Upload file    →    4. Validasi    →    5. Simpan
   (tombol "Template")                                        (.xlsx / .xls / .csv)    (baris per baris)
```

### Spesifikasi Teknis Import
| Aspek | Detail |
|---|---|
| **Format File** | `.xlsx`, `.xls`, `.csv` |
| **Library** | `Maatwebsite\Excel` (Laravel Excel) |
| **Validasi** | Laravel Validation per baris; baris gagal dilaporkan dengan nomor baris & pesan error |
| **Error Handling** | Jika ada baris gagal, import tetap dilanjutkan, dan **daftar error** ditampilkan ke user |
| **Template** | Tersedia template resmi yang bisa diunduh, sudah berisi header kolom dan format yang benar |
| **Status Data** | Data yang berhasil diimport otomatis berstatus `draft` |

### Contoh Kolom Template Import (Rehab Lahan)
| Kolom | Tipe | Wajib | Keterangan |
|---|---|:---:|---|
| `year` | Integer | ✅ | Tahun kegiatan (mis: 2024) |
| `month` | Integer 1-12 | ✅ | Bulan kegiatan |
| `regency_id` | Integer | - | ID Kabupaten |
| `district_id` | Integer | - | ID Kecamatan |
| `village_id` | Integer | - | ID Desa |
| `target_annual` | Numeric | ✅ | Target tahunan (Ha) |
| `realization` | Numeric | ✅ | Realisasi (Ha) |
| `fund_source` | String | ✅ | Sumber dana (APBN/APBD/dll) |
| `coordinates` | String | - | Koordinat GPS (opsional) |

---

## 📤 Fitur Export Excel (Laporan)

Setiap modul mendukung **ekspor data ke file Excel** yang siap digunakan sebagai laporan resmi.

### Spesifikasi Teknis Export
| Aspek | Detail |
|---|---|
| **Format Output** | `.xlsx` |
| **Library** | `Maatwebsite\Excel` (Laravel Excel) |
| **Filter** | Export bisa difilter berdasarkan **tahun** yang dipilih di halaman |
| **Nama File** | Format: `[nama-modul]-YYYY-MM-DD.xlsx` (misalnya: `rehab-lahan-2024-12-01.xlsx`) |
| **Isi Laporan** | Seluruh data sesuai filter tahun, termasuk relasi (nama lokasi, nama komoditas, dll.) |

### Daftar Modul dengan Fitur Export
| Modul | File Export |
|---|---|
| Rehab Lahan | `RehabLahanExport.php` |
| Penghijauan Lingkungan | `PenghijauanLingkunganExport.php` |
| Rehab Mangrove | `RehabManggroveExport.php` |
| RHL Teknis | `RhlTeknisExport.php` |
| Reboisasi PS | `ReboisasiPsExport.php` |
| Hasil Hutan Kayu | `HasilHutanKayuExport.php` |
| Hasil Hutan Bukan Kayu | `HasilHutanBukanKayuExport.php` |
| PBPHH | `PbphhExport.php` |
| Nilai Ekonomi | `NilaiEkonomiExport.php` |
| Nilai Transaksi Ekonomi | `NilaiTransaksiEkonomiExport.php` |
| Realisasi PNBP | `RealisasiPnbpExport.php` |
| SK Perhutanan Sosial | `SkpsExport.php` |
| Perkembangan KTH | `PerkembanganKthExport.php` |
| Perkembangan KUPS | `KupsExport.php` |
| Kebakaran Hutan | `KebakaranHutanExport.php` |
| Pengunjung Wisata | `PengunjungWisataExport.php` |

---

## 🗂️ Modul & Fitur Lengkap

### 1. 🌿 Rehabilitasi & Lingkungan

| Modul | Route | Fitur Tersedia |
|---|---|---|
| **Rehabilitasi Lahan (RHL)** | `/rehab-lahan` | CRUD, Import, Export, Template, Workflow (Single & Bulk) |
| **Penghijauan Lingkungan** | `/penghijauan-lingkungan` | CRUD, Import, Export, Template, Workflow (Single & Bulk) |
| **Rehabilitasi Mangrove** | `/rehab-manggrove` | CRUD, Import, Export, Template, Workflow (Single & Bulk) |
| **RHL Teknis** | `/rhl-teknis` | CRUD, Import, Export, Template, Workflow (Single & Bulk) |
| **Reboisasi Area PS** | `/reboisasi-ps` | CRUD, Import, Export, Template, Workflow (Single & Bulk) |

**Field kunci Rehab Lahan:**
- Lokasi (Provinsi/Kab/Kec/Desa), Tahun-Bulan, Sumber Dana, Target Tahunan (Ha), Realisasi (Ha), Koordinat GPS

---

### 2. 🤝 Perhutanan Sosial & Kelompok Tani

| Modul | Route | Fitur Tersedia |
|---|---|---|
| **Perkembangan KTH** | `/perkembangan-kth` | CRUD, Import, Export, Template, Workflow (Single & Bulk) |
| **Perkembangan KUPS** | `/kups` | CRUD, Import, Export, Template, Workflow (Single & Bulk) |
| **SK Perhutanan Sosial (SKPS)** | `/skps` | CRUD, Import, Export, Template, Workflow (Single & Bulk) |

---

### 3. 🌲 Hasil Hutan & Ekonomi

| Modul | Route | Fitur Tersedia |
|---|---|---|
| **Hasil Hutan Kayu** | `/hasil-hutan-kayu` | CRUD (Header+Detail), Import, Export, Template, Workflow |
| **Hasil Hutan Bukan Kayu** | `/hasil-hutan-bukan-kayu` | CRUD (Header+Detail), Import, Export, Template, Workflow |
| **PBPHH** | `/pbphh` | CRUD, Import, Export, Template, Workflow |
| **Nilai Ekonomi (NEKON)** | `/nilai-ekonomi` | CRUD (Header+Detail), Import, Export, Template, Workflow |
| **Nilai Transaksi Ekonomi** | `/nilai-transaksi-ekonomi` | CRUD (Header+Detail), Import, Export, Template, Workflow |
| **Realisasi PNBP** | `/realisasi-pnbp` | CRUD, Import, Export, Template, Workflow |

**Field kunci Nilai Ekonomi:**
- Nama Kelompok, Lokasi, Tahun-Bulan, Detail: Komoditas + Volume Produksi + Satuan + Nilai Transaksi, Auto-hitung Total Transaksi

---

### 4. 🌏 Data Lainnya

| Modul | Route | Fitur Tersedia |
|---|---|---|
| **Kebakaran Hutan** | `/kebakaran-hutan` | CRUD, Import, Export, Template, Workflow |
| **Pengunjung Wisata Alam** | `/pengunjung-wisata` | CRUD, Import, Export, Template, Workflow |

---

### 5. ⚙️ Dashboard & Analitik

| Fitur | Deskripsi |
|---|---|
| **Dashboard Utama** | Grafik statistik per modul (Bar Chart, Line Chart, Pie Chart via Chart.js) |
| **Dashboard Publik** | Versi publik dashboard yang dapat diakses melalui **login Google** |
| **Filter Dinamis** | Filter data dashboard berdasarkan tahun |
| **Export Data Dashboard** | Ekspor data Rehab Lahan dari dashboard |
| **Statistik Real-time** | Counter total data, total target, total realisasi (dengan caching 10 menit) |

---

## 🖥️ Output Aplikasi: Public Dashboard (Visualisasi Chart)

Public Dashboard adalah **halaman output utama** SMART-HUT yang menampilkan visualisasi data kehutanan secara grafis dan dapat diakses oleh publik setelah login menggunakan akun Google.

### 🔑 Cara Akses: Login via Google (Google OAuth)

Public Dashboard menggunakan **Laravel Socialite** untuk autentikasi via Google. Pengguna tidak perlu memiliki akun internal — cukup menggunakan akun Google.

```
Alur Akses:
┌─────────────┐    Klik         ┌──────────────────┐   Google      ┌─────────────────┐
│  Halaman    │ ──────────────► │ Sign in with     │ ──────────► │  Google OAuth   │
│  Login      │  "Sign in with  │  Google          │  Redirect    │  Consent Screen │
│  /login     │   Google"       │  (Socialite)     │              │  (accounts.     │
└─────────────┘                 └──────────────────┘              │   google.com)   │
                                                                   └────────┬────────┘
                                                                            │ Token + Profile
                                                                            ▼
                                                        ┌─────────────────────────────────┐
                                                        │    Laravel Socialite Handler     │
                                                        │  • Ambil profil Google           │
                                                        │  • Buat/temukan user di DB       │
                                                        │  • Login otomatis                │
                                                        │  • Redirect ke Public Dashboard  │
                                                        └────────────────┬────────────────┘
                                                                         │
                                                                         ▼
                                                        ┌─────────────────────────────────┐
                                                        │       PUBLIC DASHBOARD           │
                                                        │     /public/dashboard            │
                                                        │   (14 Slide Carousel Chart)      │
                                                        └─────────────────────────────────┘
```

> **Catatan:** Tombol "Sign in with Google" pada halaman login sudah tersedia dan aktif. Route: `GET /auth/google/redirect` → `socialite.redirect`

---

### 📊 Fitur-Fitur Public Dashboard

| Fitur | Detail |
|---|---|
| **Carousel Otomatis** | 14 slide berpindah otomatis setiap **25 detik** |
| **Navigasi Manual** | Tombol prev/next dan dot indicator untuk berpindah slide |
| **Filter Tahun** | Dropdown pemilihan tahun (menampilkan 6 tahun terakhir) |
| **Auto Refresh Data** | Data diperbarui otomatis setiap **1 menit** tanpa reload halaman |
| **Profile Dropdown** | Menampilkan nama, foto, dan role user yang sedang login |
| **Link Admin Dashboard** | Jika user memiliki role, tampil link ke Admin Dashboard |
| **Responsif** | Layout menyesuaikan ukuran layar (tablet/desktop) |
| **Data Caching** | Setiap kategori statistik di-cache **10 menit** untuk performa optimal |

---

### 📋 14 Slide Modul Visualisasi

Public Dashboard menampilkan **14 slide** dengan data dari seluruh modul kehutanan. Setiap slide hanya menampilkan data berstatus **FINAL** (telah diverifikasi dan disetujui).

#### 🌿 Kategori Pembinaan Hutan (Slide 1–5)

| Slide | Judul | Warna | Data yang Ditampilkan |
|:---:|---|---|---|
| 1 | **Rehabilitasi Lahan** | 🟢 Emerald | Total realisasi (Ha), target, grafik bulanan, distribusi sumber dana, sebaran per kabupaten |
| 2 | **Penghijauan Lingkungan** | 🟦 Teal | Total realisasi (Ha), target, grafik bulanan, distribusi sumber dana, sebaran per kabupaten |
| 3 | **Rehabilitasi Mangrove** | 🔵 Cyan | Total realisasi (Ha), target, grafik bulanan, distribusi sumber dana, sebaran per kabupaten |
| 4 | **Bangunan Konservasi Tanah & Air** | 🟠 Orange | Total realisasi (Unit), target, grafik per jenis bangunan (top 5), sumber dana |
| 5 | **Reboisasi Area Perhutanan Sosial** | 🩷 Pink | Total realisasi (Ha), target, grafik bulanan, sumber dana, distribusi pengelola PS |

#### 🔴 Kategori Perlindungan (Slide 6–7)

| Slide | Judul | Warna | Data yang Ditampilkan |
|:---:|---|---|---|
| 6 | **Kebakaran Hutan** | 🔴 Red | Jumlah kejadian, luas area terbakar, grafik kejadian per bulan, distribusi per pengelola |
| 7 | **Jasa Lingkungan (Wisata Alam)** | 🟣 Indigo | Total pengunjung, total pendapatan kotor, tren bulanan, data per pengelola wisata |

#### 🌲 Kategori Produksi Hutan (Slide 8–11)

| Slide | Judul | Warna | Data yang Ditampilkan |
|:---:|---|---|---|
| 8 | **Produksi Hutan Negara** | 🔵 Blue | Target & realisasi kayu/non-kayu/bambu, grafik bulanan, top 5 komoditas |
| 9 | **Produksi Perhutanan Sosial** | 🔵 Sky | Target & realisasi kayu/non-kayu/bambu, grafik bulanan, top 5 komoditas |
| 10 | **Produksi Hutan Rakyat** | 🔵 Cyan | Target & realisasi kayu/non-kayu/bambu, grafik bulanan, top 5 komoditas |
| 11 | **PBPHH** | ⚫ Slate | Jumlah unit usaha, jumlah tenaga kerja, nilai investasi, distribusi per kabupaten & jenis produksi |

#### 💰 Kategori Ekonomi & Kelembagaan (Slide 12–14)

| Slide | Judul | Warna | Data yang Ditampilkan |
|:---:|---|---|---|
| 12 | **Penerimaan Negara (PNBP)** | 🟡 Amber | Total realisasi vs target, grafik bulanan target vs realisasi, distribusi per kabupaten & pengelola |
| 13 | **Kelembagaan Perhutanan Sosial** | 🟢 Emerald | Jumlah kelompok PS, total luas, total KK, distribusi skema PS, nilai ekonomi per kabupaten, top 5 kelompok |
| 14 | **Kelembagaan Hutan Rakyat** | 🟢 Lime | Jumlah KTH, total luas kelola, total anggota, kelas kelembagaan, NTE per kabupaten, top 5 komoditas |

---

### 📈 Jenis Grafik yang Digunakan

| Jenis Chart | Digunakan Pada |
|---|---|
| **Bar Chart (Vertikal)** | Realisasi bulanan, distribusi per kabupaten |
| **Line Chart** | Tren realisasi vs target per bulan |
| **Doughnut/Pie Chart** | Distribusi sumber dana, skema PS, kelas kelembagaan |
| **Horizontal Bar Chart** | Ranking top 5 komoditas, top 5 kelompok tani |
| **Grouped Bar Chart** | Perbandingan target vs realisasi PNBP per bulan |

---

### 🔒 Keamanan & Privasi Data Public Dashboard

- Hanya data berstatus **`final`** (telah disetujui Kasi) yang ditampilkan
- Data ditampilkan dalam bentuk **agregat** (total, rata-rata, distribusi) — bukan data individual
- Pengguna yang login via Google **tidak secara otomatis mendapat akses admin**; mereka hanya bisa mengakses public dashboard
- Route: `GET /public/dashboard` — hanya bisa diakses setelah autentikasi

---

### 6. 🛠️ Administrasi & Utilitas

| Fitur | Route | Deskripsi |
|---|---|---|
| **Manajemen User** | `/users` | CRUD user, assign role/permission, impersonasi user (admin) |
| **Activity Log** | `/activity-log` | Riwayat seluruh aktivitas CRUD semua user |
| **Backup Database** | `/backups` | Buat backup, list backup, unduh backup, hapus backup (Google Drive) |
| **Log Viewer** | `/log-viewer` | Viewer log error Laravel (khusus admin) |
| **Clear Cache** | `/clear-cache` | Bersihkan cache route, config, view aplikasi |
| **Profile** | `/profile` | Edit profil & ganti password user sendiri |

---

### 7. 📚 Master Data

Data referensi yang digunakan sebagai lookup di seluruh modul:

| Entitas | Route | Keterangan |
|---|---|---|
| Provinsi | `/provinces` | Data provinsi Indonesia |
| Kabupaten/Kota | `/regencies` | Data kabupaten/kota |
| Kecamatan | `/districts` | Data kecamatan |
| Desa/Kelurahan | `/villages` | Data desa/kelurahan |
| Komoditas | `/commodities` | Jenis komoditas hasil hutan |
| Sumber Dana | `/sumber-dana` | Jenis sumber pendanaan (APBN, APBD, dll.) |
| Jenis Produksi | `/jenis-produksi` | Kategori jenis produksi |
| Bangunan KTA | `/bangunan-kta` | Data bangunan KTA |
| Bukan Kayu | `/bukan-kayu` | Jenis HHBK |
| Kayu | `/kayu` | Jenis kayu |
| Pengelola Wisata | `/pengelola-wisata` | Data pengelola objek wisata alam |
| Pengelola PS | `/pengelola-ps` | Data pengelola Perhutanan Sosial |
| Skema PS | `/skema-perhutanan-sosial` | Jenis skema Perhutanan Sosial |

---

## 🏗️ Arsitektur Aplikasi

```
┌─────────────────────────────────────────────────────────────┐
│                       CLIENT (Browser)                       │
│              React + Inertia.js + Tailwind CSS               │
│         (SPA – Single Page Application, no full reload)      │
└──────────────────────────┬──────────────────────────────────┘
                           │  HTTP / Inertia Protocol
┌──────────────────────────▼──────────────────────────────────┐
│                    LARAVEL 10 SERVER                          │
│                                                               │
│  [Routes] → [Middleware (Auth, RBAC, CheckDashboard)]        │
│          → [Controllers] → [Models (Eloquent)]               │
│                                                               │
│  Services:  Import/Export (Excel)  │  Workflow Actions        │
│             Activity Log           │  Cache (5 menit)         │
│             Backup Manager         │  Location API (AJAX)     │
└──────┬────────────────────┬────────────────────┬────────────┘
       ▼                    ▼                    ▼
┌──────────────┐   ┌──────────────┐   ┌───────────────────┐
│   MySQL DB   │   │ Google Drive │   │   File Storage    │
│ (Data Utama  │   │  (Backup DB) │   │  (Upload Lokal/   │
│  36 Tabel)   │   │              │   │   Google Drive)   │
└──────────────┘   └──────────────┘   └───────────────────┘
```

---

## 📄 Lisensi

Aplikasi ini adalah **perangkat lunak proprietary**. Hak cipta dilindungi undang-undang. Dilarang mendistribusikan, memodifikasi, atau menggunakan ulang tanpa izin resmi dari pemilik.

---
