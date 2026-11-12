<?php

namespace App\Support\Integrasi;

/**
 * Sumber konten halaman dokumentasi API (/docs/api).
 *
 * Setiap endpoint baru di /api/v1 wajib ditambahkan di endpoints(). Tes
 * IntegrasiApiDocumentationTest memastikan setiap endpoint yang terdokumentasi
 * benar-benar terdaftar sebagai rute, dan sebaliknya.
 */
class ApiDocumentation
{
    public const VERSION = 'v1';

    public static function endpoints(): array
    {
        return [
            [
                'group' => 'Umum',
                'route' => 'api.v1.klien',
                'method' => 'GET',
                'path' => '/klien',
                'title' => 'Identitas klien',
                'description' => 'Mengembalikan identitas klien pemilik token, cakupan wilayah, dan ability token. Gunakan untuk menguji koneksi dan memeriksa konfigurasi token sebelum mengambil data.',
                'ability' => null,
                'params' => [],
                'fields' => [
                    ['name' => 'data.kode', 'type' => 'string', 'description' => 'Kode unik klien.'],
                    ['name' => 'data.nama', 'type' => 'string', 'description' => 'Nama sistem klien.'],
                    ['name' => 'data.cakupan', 'type' => '"provinsi" | "cdk"', 'description' => 'Cakupan data yang bisa dibaca token ini.'],
                    ['name' => 'data.cdk', 'type' => 'object | null', 'description' => 'CDK yang membatasi data (id, kode, nama). Null untuk cakupan provinsi.'],
                    ['name' => 'data.token.nama', 'type' => 'string', 'description' => 'Nama token, mis. produksi.'],
                    ['name' => 'data.token.abilities', 'type' => 'string[]', 'description' => 'Daftar akses data milik token.'],
                    ['name' => 'data.token.kedaluwarsa', 'type' => 'string | null', 'description' => 'Waktu kedaluwarsa token (ISO 8601). Null jika tanpa batas.'],
                    ['name' => 'data.waktu_server', 'type' => 'string', 'description' => 'Waktu server (ISO 8601), berguna untuk sinkronisasi inkremental.'],
                ],
                'example' => [
                    'data' => [
                        'kode' => 'cdk-trenggalek',
                        'nama' => 'Sistem CDK Trenggalek',
                        'cakupan' => 'cdk',
                        'cdk' => ['id' => 3, 'kode' => 'CDK Wilayah Trenggalek', 'nama' => 'CDK Wilayah Trenggalek'],
                        'token' => [
                            'nama' => 'produksi',
                            'abilities' => ['rhl.read', 'wilayah.read'],
                            'kedaluwarsa' => '2027-11-01T08:00:00+07:00',
                        ],
                        'waktu_server' => '2026-11-01T10:00:00+07:00',
                    ],
                ],
            ],
            ...self::rhlEndpoints(),
            ...self::perlindunganEndpoints(),
            ...self::binaUsahaEndpoints(),
            ...self::pemberdayaanEndpoints(),
            ...self::rekapEndpoints(),
        ];
    }

    /**
     * Endpoint rekap per modul: angka ringkasan yang dihitung di server.
     */
    private static function rekapEndpoints(): array
    {
        $params = [
            ['name' => 'tahun', 'type' => 'integer', 'required' => false, 'description' => 'Tahun rekap. Bawaan: tahun terakhir yang punya data final (bukan tahun kalender), lihat data.tahun.'],
            ['name' => 'kabupaten_id', 'type' => 'string', 'required' => false, 'description' => 'Kode kabupaten/kota 4 digit, mis. 3503. Membatasi semua angka ke kabupaten itu.'],
        ];
        $umum = [
            ['name' => 'data.tahun', 'type' => 'integer | null', 'description' => 'Tahun yang dipakai untuk angka berkala. Null bila belum ada data final.'],
            ['name' => 'data.tahun_tersedia', 'type' => 'integer[]', 'description' => 'Tahun yang punya data final, urut naik.'],
        ];
        $penutup = [
            ['name' => 'data.diperbarui_pada', 'type' => 'string | null', 'description' => 'Waktu perubahan terakhir di antara semua data sumber rekap (ISO 8601).'],
            ['name' => 'waktu_server', 'type' => 'string', 'description' => 'Waktu server saat rekap dihitung.'],
        ];
        $periode = fn (string $prefix) => ['name' => "$prefix.periode", 'type' => 'object | null', 'description' => 'Rentang bulan yang punya data final {bulan_awal, bulan_akhir}. Pakai untuk label, mis. "Jan–Sep 2026". Null bila tidak ada data.'];
        $trenggalek = ['id' => '3503', 'nama' => 'KABUPATEN TRENGGALEK'];
        $rhl = fn (string $key, string $label) => [
            ['name' => "data.$key.jumlah_lokasi", 'type' => 'integer', 'description' => "Jumlah lokasi $label pada tahun rekap."],
            ['name' => "data.$key.target_tahunan_ha", 'type' => 'number', 'description' => 'Jumlah target tahunan dalam hektare.'],
            ['name' => "data.$key.realisasi_ha", 'type' => 'number', 'description' => 'Jumlah realisasi dalam hektare.'],
            ['name' => "data.$key.persentase_realisasi", 'type' => 'number | null', 'description' => 'Realisasi dibagi target, dalam persen. Null bila target belum diisi (0).'],
            $periode("data.$key"),
            ['name' => "data.$key.per_kabupaten[]", 'type' => 'object[]', 'description' => 'Angka yang sama per kabupaten {kabupaten: {id, nama}, jumlah_lokasi, target_tahunan_ha, realisasi_ha, persentase_realisasi}.'],
        ];
        $rhlExample = fn (int $lokasi, float $target, float $realisasi, ?float $persen, ?array $periode) => [
            'jumlah_lokasi' => $lokasi,
            'target_tahunan_ha' => $target,
            'realisasi_ha' => $realisasi,
            'persentase_realisasi' => $persen,
            'periode' => $periode,
            'per_kabupaten' => $lokasi ? [['kabupaten' => $trenggalek, 'jumlah_lokasi' => $lokasi, 'target_tahunan_ha' => $target, 'realisasi_ha' => $realisasi, 'persentase_realisasi' => $persen]] : [],
        ];

        return [
            [
                'group' => 'Rehabilitasi Hutan & Lahan (RHL)',
                'route' => 'api.v1.rhl.rekap',
                'method' => 'GET',
                'path' => '/rhl/rekap',
                'title' => 'Rekap RHL',
                'description' => 'Jumlah target dan realisasi luas per kegiatan RHL untuk satu tahun, total dan per kabupaten. Satu baris data = satu lokasi, dan target tahunan tidak berulang per bulan. RHL teknis (satuan unit) tidak termasuk.',
                'ability' => 'rhl.read',
                'params' => $params,
                'fields' => [
                    ...$umum,
                    ...$rhl('rehab_lahan', 'rehabilitasi lahan'),
                    ['name' => 'data.penghijauan_lingkungan, data.rehab_mangrove, data.reboisasi_ps', 'type' => 'object', 'description' => 'Bentuk yang sama dengan data.rehab_lahan untuk penghijauan lingkungan, rehabilitasi mangrove, dan reboisasi area PS.'],
                    ...$penutup,
                ],
                'example' => [
                    'data' => [
                        'tahun' => 2026,
                        'tahun_tersedia' => [2024, 2025, 2026],
                        'rehab_lahan' => $rhlExample(11, 49, 10, 20.4, ['bulan_awal' => 2, 'bulan_akhir' => 11]),
                        'penghijauan_lingkungan' => $rhlExample(10, 0, 35, null, ['bulan_awal' => 3, 'bulan_akhir' => 9]),
                        'rehab_mangrove' => $rhlExample(1, 0.1, 0.1, 100, ['bulan_awal' => 5, 'bulan_akhir' => 5]),
                        'reboisasi_ps' => $rhlExample(0, 0, 0, null, null),
                        'diperbarui_pada' => '2026-10-23T03:00:00+00:00',
                    ],
                    'waktu_server' => '2026-11-12T10:00:00+00:00',
                ],
            ],
            [
                'group' => 'Perlindungan Hutan',
                'route' => 'api.v1.perlindungan.rekap',
                'method' => 'GET',
                'path' => '/perlindungan/rekap',
                'title' => 'Rekap Perlindungan',
                'description' => 'Jumlah kebakaran hutan dan pengunjung wisata alam untuk satu tahun. Pengunjung wisata tidak menyimpan lokasi, sehingga tidak dipecah per kabupaten; sebagai gantinya ada daftar pengelola yang melapor agar kelengkapan angkanya bisa dinilai.',
                'ability' => 'perlindungan.read',
                'params' => $params,
                'fields' => [
                    ...$umum,
                    ['name' => 'data.kebakaran_hutan.jumlah_kejadian', 'type' => 'integer', 'description' => 'Jumlah kejadian kebakaran.'],
                    ['name' => 'data.kebakaran_hutan.luas_terbakar_ha', 'type' => 'number', 'description' => 'Jumlah luas terbakar dalam hektare. Nilai yang tidak bisa dibaca sebagai angka dihitung 0.'],
                    $periode('data.kebakaran_hutan'),
                    ['name' => 'data.kebakaran_hutan.per_kabupaten[]', 'type' => 'object[]', 'description' => 'Per kabupaten {kabupaten: {id, nama}, jumlah_kejadian, luas_terbakar_ha}.'],
                    ['name' => 'data.pengunjung_wisata', 'type' => 'object | null', 'description' => 'Null bila kabupaten_id dipakai, karena data ini tidak punya lokasi.'],
                    ['name' => 'data.pengunjung_wisata.jumlah_pengunjung', 'type' => 'integer', 'description' => 'Jumlah pengunjung seluruh pengelola yang melapor.'],
                    ['name' => 'data.pengunjung_wisata.pendapatan_bruto_rp', 'type' => 'integer', 'description' => 'Jumlah pendapatan bruto dalam rupiah.'],
                    $periode('data.pengunjung_wisata'),
                    ['name' => 'data.pengunjung_wisata.per_pengelola[]', 'type' => 'object[]', 'description' => 'Pengelola yang melapor pada tahun rekap {pengelola: {id, nama}, jumlah_pengunjung, pendapatan_bruto_rp, periode}, dari pengunjung terbanyak.'],
                    ...$penutup,
                ],
                'example' => [
                    'data' => [
                        'tahun' => 2026,
                        'tahun_tersedia' => [2024, 2025, 2026],
                        'kebakaran_hutan' => [
                            'jumlah_kejadian' => 26,
                            'luas_terbakar_ha' => 47.1,
                            'periode' => ['bulan_awal' => 6, 'bulan_akhir' => 9],
                            'per_kabupaten' => [['kabupaten' => $trenggalek, 'jumlah_kejadian' => 14, 'luas_terbakar_ha' => 23]],
                        ],
                        'pengunjung_wisata' => [
                            'jumlah_pengunjung' => 331229,
                            'pendapatan_bruto_rp' => 5300000000,
                            'periode' => ['bulan_awal' => 1, 'bulan_akhir' => 8],
                            'per_pengelola' => [[
                                'pengelola' => ['id' => 14, 'nama' => 'Rimba Madu Sejahtera'],
                                'jumlah_pengunjung' => 331229,
                                'pendapatan_bruto_rp' => 5300000000,
                                'periode' => ['bulan_awal' => 1, 'bulan_akhir' => 8],
                            ]],
                        ],
                        'diperbarui_pada' => '2026-10-23T03:00:00+00:00',
                    ],
                    'waktu_server' => '2026-11-12T10:00:00+00:00',
                ],
            ],
            [
                'group' => 'Bina Usaha',
                'route' => 'api.v1.bina-usaha.rekap',
                'method' => 'GET',
                'path' => '/bina-usaha/rekap',
                'title' => 'Rekap Bina Usaha',
                'description' => 'Jumlah produksi kayu, hasil hutan bukan kayu per komoditas, PNBP, dan izin PBPHH. Target kayu dan PNBP dijumlahkan dari semua baris pada tahun rekap. PBPHH adalah data izin, sehingga tidak bergantung pada tahun.',
                'ability' => 'bina-usaha.read',
                'params' => $params,
                'fields' => [
                    ...$umum,
                    ['name' => 'data.hasil_hutan_kayu.jumlah_catatan', 'type' => 'integer', 'description' => 'Jumlah baris laporan produksi kayu yang dijumlahkan.'],
                    ['name' => 'data.hasil_hutan_kayu.target_volume_m3', 'type' => 'number', 'description' => 'Jumlah target volume dalam m³.'],
                    ['name' => 'data.hasil_hutan_kayu.realisasi_volume_m3', 'type' => 'number', 'description' => 'Jumlah realisasi seluruh jenis kayu dalam m³.'],
                    ['name' => 'data.hasil_hutan_kayu.persentase_realisasi', 'type' => 'number | null', 'description' => 'Realisasi dibagi target, dalam persen. Null bila target 0.'],
                    $periode('data.hasil_hutan_kayu'),
                    ['name' => 'data.hasil_hutan_kayu.per_kabupaten[]', 'type' => 'object[]', 'description' => 'Angka yang sama per kabupaten {kabupaten: {id, nama}, ...}.'],
                    ['name' => 'data.hasil_hutan_bukan_kayu.per_komoditas[]', 'type' => 'object[]', 'description' => 'Realisasi per komoditas dan satuan {komoditas: {id, nama}, satuan, realisasi_volume}. Satuan berbeda tidak dijumlahkan; satuan ditulis huruf kecil.'],
                    ['name' => 'data.hasil_hutan_bukan_kayu.jumlah_catatan, .periode', 'type' => 'integer, object | null', 'description' => 'Jumlah baris laporan dan rentang bulan yang punya data.'],
                    ['name' => 'data.realisasi_pnbp', 'type' => 'object', 'description' => 'PNBP kehutanan {target_pnbp_rp, realisasi_pnbp_rp, persentase_realisasi, periode, per_kabupaten[]}.'],
                    ['name' => 'data.pbphh', 'type' => 'object', 'description' => 'Izin industri {jumlah_izin, jumlah_beroperasi, jumlah_tenaga_kerja, nilai_investasi_rp, per_kabupaten[]}.'],
                    ...$penutup,
                ],
                'example' => [
                    'data' => [
                        'tahun' => 2026,
                        'tahun_tersedia' => [2024, 2025, 2026],
                        'hasil_hutan_kayu' => [
                            'jumlah_catatan' => 412,
                            'target_volume_m3' => 327072,
                            'realisasi_volume_m3' => 316359,
                            'persentase_realisasi' => 96.7,
                            'periode' => ['bulan_awal' => 1, 'bulan_akhir' => 9],
                            'per_kabupaten' => [['kabupaten' => $trenggalek, 'jumlah_catatan' => 150, 'target_volume_m3' => 120500, 'realisasi_volume_m3' => 118200, 'persentase_realisasi' => 98.1]],
                        ],
                        'hasil_hutan_bukan_kayu' => [
                            'jumlah_catatan' => 96,
                            'periode' => ['bulan_awal' => 1, 'bulan_akhir' => 9],
                            'per_komoditas' => [
                                ['komoditas' => ['id' => 27, 'nama' => 'Bambu'], 'satuan' => 'batang', 'realisasi_volume' => 12840],
                                ['komoditas' => ['id' => 4, 'nama' => 'Empon - Empon'], 'satuan' => 'kg', 'realisasi_volume' => 52300],
                            ],
                        ],
                        'realisasi_pnbp' => [
                            'target_pnbp_rp' => 4100000000,
                            'realisasi_pnbp_rp' => 3580000000,
                            'persentase_realisasi' => 87.3,
                            'periode' => ['bulan_awal' => 1, 'bulan_akhir' => 9],
                            'per_kabupaten' => [['kabupaten' => $trenggalek, 'target_pnbp_rp' => 1500000000, 'realisasi_pnbp_rp' => 1320000000, 'persentase_realisasi' => 88]],
                        ],
                        'pbphh' => [
                            'jumlah_izin' => 225,
                            'jumlah_beroperasi' => 219,
                            'jumlah_tenaga_kerja' => 2338,
                            'nilai_investasi_rp' => 48500000000,
                            'per_kabupaten' => [['kabupaten' => $trenggalek, 'jumlah_izin' => 80, 'jumlah_beroperasi' => 78, 'jumlah_tenaga_kerja' => 810, 'nilai_investasi_rp' => 17200000000]],
                        ],
                        'diperbarui_pada' => '2026-10-23T03:00:00+00:00',
                    ],
                    'waktu_server' => '2026-11-12T10:00:00+00:00',
                ],
            ],
            [
                'group' => 'Pemberdayaan Masyarakat',
                'route' => 'api.v1.pemberdayaan.rekap',
                'method' => 'GET',
                'path' => '/pemberdayaan/rekap',
                'title' => 'Rekap Pemberdayaan',
                'description' => 'Jumlah KTH, kelompok Perhutanan Sosial, KUPS, dan nilai transaksi ekonomi. Perkembangan KTH adalah riwayat, sehingga di sini setiap KTH dihitung satu kali per nomor register memakai catatan terbarunya (tahun, bulan, lalu id terbesar); catatan tanpa nomor register dihitung sebagai KTH tersendiri. KTH, Perhutanan Sosial, dan KUPS tidak bergantung pada tahun.',
                'ability' => 'pemberdayaan.read',
                'params' => $params,
                'fields' => [
                    ...$umum,
                    ['name' => 'data.kth.jumlah', 'type' => 'integer', 'description' => 'Jumlah KTH, satu per nomor register.'],
                    ['name' => 'data.kth.jumlah_anggota', 'type' => 'integer', 'description' => 'Jumlah anggota dari catatan terbaru tiap KTH.'],
                    ['name' => 'data.kth.luas_kelola_ha', 'type' => 'number', 'description' => 'Jumlah luas kelola dari catatan terbaru tiap KTH, dalam hektare.'],
                    ['name' => 'data.kth.jumlah_catatan', 'type' => 'integer', 'description' => 'Jumlah baris perkembangan KTH sebelum digabung per nomor register.'],
                    ['name' => 'data.perhutanan_sosial', 'type' => 'object', 'description' => 'Kelompok pemegang SK PS {jumlah_kelompok, jumlah_kk, luas_ps_ha}.'],
                    ['name' => 'data.kups', 'type' => 'object', 'description' => 'KUPS {jumlah, per_kelas[]: {kelas, jumlah}}.'],
                    ['name' => 'data.nilai_transaksi_ekonomi.total_rp', 'type' => 'number', 'description' => 'Jumlah nilai transaksi ekonomi KTH pada tahun rekap, dalam rupiah.'],
                    ['name' => 'data.nilai_transaksi_ekonomi.jumlah_catatan', 'type' => 'integer', 'description' => 'Jumlah baris laporan yang dijumlahkan.'],
                    $periode('data.nilai_transaksi_ekonomi'),
                    ['name' => 'data.nilai_transaksi_ekonomi.per_bulan[]', 'type' => 'object[]', 'description' => 'Per bulan {bulan, jumlah_catatan, total_rp}. jumlah_catatan yang jauh lebih kecil dari bulan lain menandakan bulan itu belum lengkap.'],
                    ['name' => 'data.nilai_ekonomi', 'type' => 'object', 'description' => 'Bentuk yang sama untuk nilai ekonomi kelompok/gapoktan. Dataset terpisah, tidak dijumlahkan ke nilai transaksi ekonomi.'],
                    ['name' => 'data.per_kabupaten[]', 'type' => 'object[]', 'description' => 'Per kabupaten {kabupaten: {id, nama}, kth: {jumlah, jumlah_anggota, luas_kelola_ha}, perhutanan_sosial, nilai_transaksi_ekonomi_rp}. KTH masuk ke kabupaten catatan terbarunya.'],
                    ...$penutup,
                ],
                'example' => [
                    'data' => [
                        'tahun' => 2026,
                        'tahun_tersedia' => [2024, 2025, 2026],
                        'kth' => ['jumlah' => 310, 'jumlah_anggota' => 9698, 'luas_kelola_ha' => 825.9, 'jumlah_catatan' => 323],
                        'perhutanan_sosial' => ['jumlah_kelompok' => 26, 'jumlah_kk' => 22766, 'luas_ps_ha' => 23838],
                        'kups' => ['jumlah' => 12, 'per_kelas' => [['kelas' => 'Blue', 'jumlah' => 5], ['kelas' => 'Gold', 'jumlah' => 6], ['kelas' => 'Platinum', 'jumlah' => 1]]],
                        'nilai_transaksi_ekonomi' => [
                            'total_rp' => 205300000000,
                            'jumlah_catatan' => 2390,
                            'periode' => ['bulan_awal' => 1, 'bulan_akhir' => 10],
                            'per_bulan' => [
                                ['bulan' => 9, 'jumlah_catatan' => 268, 'total_rp' => 24100000000],
                                ['bulan' => 10, 'jumlah_catatan' => 4, 'total_rp' => 310000000],
                            ],
                        ],
                        'nilai_ekonomi' => [
                            'total_rp' => 3700000000,
                            'jumlah_catatan' => 41,
                            'periode' => ['bulan_awal' => 1, 'bulan_akhir' => 9],
                            'per_bulan' => [['bulan' => 9, 'jumlah_catatan' => 5, 'total_rp' => 410000000]],
                        ],
                        'per_kabupaten' => [[
                            'kabupaten' => $trenggalek,
                            'kth' => ['jumlah' => 79, 'jumlah_anggota' => 3400, 'luas_kelola_ha' => 825.9],
                            'perhutanan_sosial' => ['jumlah_kelompok' => 10, 'jumlah_kk' => 9120, 'luas_ps_ha' => 9540],
                            'nilai_transaksi_ekonomi_rp' => 122700000000,
                        ]],
                        'diperbarui_pada' => '2026-10-23T03:00:00+00:00',
                    ],
                    'waktu_server' => '2026-11-12T10:00:00+00:00',
                ],
            ],
        ];
    }

    private static function pemberdayaanEndpoints(): array
    {
        $rincian = [
            ['name' => 'rincian[].komoditas', 'type' => 'object | null', 'description' => 'Komoditas {id, nama}.'],
            ['name' => 'rincian[].volume_produksi', 'type' => 'number', 'description' => 'Volume produksi komoditas.'],
            ['name' => 'rincian[].satuan', 'type' => 'string', 'description' => 'Satuan volume, mis. kg, ton, m3, batang.'],
            ['name' => 'rincian[].nilai_transaksi_rp', 'type' => 'number', 'description' => 'Nilai transaksi komoditas dalam rupiah.'],
        ];

        return self::datasetEndpoints('Pemberdayaan Masyarakat', 'pemberdayaan', 'pemberdayaan.read', [
            'skps' => [
                'title' => 'SK Perhutanan Sosial',
                'description' => 'Kelompok pemegang SK Perhutanan Sosial beserta skema dan luas areanya. Data izin, bukan laporan berkala, sehingga tidak ada tahun/bulan. Desa selalu null.',
                'period' => false,
                'fields' => [
                    ['name' => 'nama_kelompok', 'type' => 'string | null', 'description' => 'Nama kelompok pemegang SK.'],
                    ['name' => 'skema', 'type' => 'object | null', 'description' => 'Skema PS {id, nama}: Hutan Desa, Hutan Kemasyarakatan, Hutan Tanaman Rakyat, Hutan Adat, atau Kemitraan Kehutanan.'],
                    ['name' => 'luas_ps_ha', 'type' => 'number', 'description' => 'Luas areal Perhutanan Sosial dalam hektare.'],
                    ['name' => 'jumlah_kk', 'type' => 'integer', 'description' => 'Jumlah kepala keluarga anggota.'],
                    ['name' => 'potensi', 'type' => 'string', 'description' => 'Potensi kawasan, teks bebas.'],
                ],
                'example' => [
                    'nama_kelompok' => 'KTH Wono Rukun Sejati',
                    'skema' => ['id' => 2, 'nama' => 'Hutan Kemasyarakatan (HKm)'],
                    'luas_ps_ha' => 223,
                    'jumlah_kk' => 301,
                    'potensi' => 'Sengon, Tebu, Jagung, Kelapa, Cengkeh, dan Kopi',
                ],
            ],
            'kups' => [
                'title' => 'KUPS',
                'description' => 'Kelompok Usaha Perhutanan Sosial beserta kelas dan komoditas utamanya. Data kelembagaan, sehingga tidak ada tahun/bulan. Desa selalu null.',
                'period' => false,
                'fields' => [
                    ['name' => 'nama_kups', 'type' => 'string | null', 'description' => 'Nama KUPS.'],
                    ['name' => 'kelas', 'type' => 'string', 'description' => 'Kelas KUPS: Blue, Silver, Gold, atau Platinum.'],
                    ['name' => 'komoditas', 'type' => 'string', 'description' => 'Komoditas utama, teks bebas.'],
                ],
                'example' => [
                    'nama_kups' => 'KUPS Raja Pisang AML',
                    'kelas' => 'Blue',
                    'komoditas' => 'Pisang',
                ],
            ],
            'perkembangan-kth' => [
                'title' => 'Perkembangan KTH',
                'description' => 'Perkembangan Kelompok Tani Hutan: kelas kelembagaan, anggota, dan luas kelola.',
                'fields' => [
                    ['name' => 'nama_kth', 'type' => 'string', 'description' => 'Nama KTH.'],
                    ['name' => 'nomor_register', 'type' => 'string | null', 'description' => 'Nomor register KTH.'],
                    ['name' => 'kelas_kelembagaan', 'type' => '"pemula" | "madya" | "utama"', 'description' => 'Kelas kelembagaan KTH.'],
                    ['name' => 'jumlah_anggota', 'type' => 'integer', 'description' => 'Jumlah anggota.'],
                    ['name' => 'luas_kelola_ha', 'type' => 'number', 'description' => 'Luas kelola dalam hektare.'],
                    ['name' => 'potensi_kawasan', 'type' => 'string | null', 'description' => 'Potensi kawasan, teks bebas.'],
                ],
                'example' => [
                    'nama_kth' => 'Karya Taruna',
                    'nomor_register' => '35/04/15/2009/KTH.159/2003',
                    'kelas_kelembagaan' => 'pemula',
                    'jumlah_anggota' => 26,
                    'luas_kelola_ha' => 12.5,
                    'potensi_kawasan' => 'Hutan Rakyat',
                ],
            ],
            'nilai-ekonomi' => [
                'title' => 'Nilai ekonomi',
                'description' => 'Nilai ekonomi kelompok per bulan, dengan rincian volume dan nilai per komoditas. Desa selalu null.',
                'fields' => [
                    ['name' => 'nama_kelompok', 'type' => 'string', 'description' => 'Nama kelompok.'],
                    ['name' => 'total_nilai_transaksi_rp', 'type' => 'number', 'description' => 'Total nilai transaksi dalam rupiah.'],
                    ...$rincian,
                ],
                'example' => [
                    'nama_kelompok' => 'KTH Argo Makmur Lestari',
                    'total_nilai_transaksi_rp' => 134800000,
                    'rincian' => [['komoditas' => ['id' => 12, 'nama' => 'Pisang'], 'volume_produksi' => 674, 'satuan' => 'ton', 'nilai_transaksi_rp' => 134800000]],
                ],
            ],
            'nilai-transaksi-ekonomi' => [
                'title' => 'Nilai transaksi ekonomi',
                'description' => 'Nilai transaksi ekonomi KTH per bulan, dengan rincian per komoditas. Satu komoditas bisa muncul beberapa kali dalam rincian (beberapa transaksi).',
                'fields' => [
                    ['name' => 'nama_kth', 'type' => 'string', 'description' => 'Nama KTH.'],
                    ['name' => 'total_nilai_transaksi_rp', 'type' => 'number', 'description' => 'Total nilai transaksi dalam rupiah.'],
                    ...$rincian,
                ],
                'example' => [
                    'nama_kth' => 'Tani Makmur',
                    'total_nilai_transaksi_rp' => 5350000,
                    'rincian' => [
                        ['komoditas' => ['id' => 23, 'nama' => 'Log'], 'volume_produksi' => 5, 'satuan' => 'm3', 'nilai_transaksi_rp' => 3450000],
                        ['komoditas' => ['id' => 23, 'nama' => 'Log'], 'volume_produksi' => 2, 'satuan' => 'm3', 'nilai_transaksi_rp' => 1900000],
                    ],
                ],
            ],
        ]);
    }

    private static function binaUsahaEndpoints(): array
    {
        $pengelola = ['name' => 'pengelola', 'type' => 'object | null', 'description' => 'Pengelola {id, nama}: BKPH/KPH untuk Hutan Negara, kelompok tani untuk Perhutanan Sosial. Null untuk Hutan Rakyat.'];
        $jenisHutan = ['name' => 'jenis_hutan', 'type' => 'string', 'description' => 'Hutan Negara, Hutan Rakyat, atau Perhutanan Sosial.'];

        return self::datasetEndpoints('Bina Usaha', 'bina-usaha', 'bina-usaha.read', [
            'hasil-hutan-kayu' => [
                'title' => 'Hasil hutan kayu',
                'description' => 'Produksi kayu per lokasi dan bulan, dengan rincian volume per jenis kayu. Data ini mencatat sampai tingkat kecamatan; desa selalu null.',
                'fields' => [
                    $jenisHutan,
                    $pengelola,
                    ['name' => 'target_volume_m3', 'type' => 'number', 'description' => 'Target volume dalam m³.'],
                    ['name' => 'realisasi_volume_m3', 'type' => 'number', 'description' => 'Jumlah realisasi seluruh rincian, dalam m³.'],
                    ['name' => 'rincian[].kayu', 'type' => 'object | null', 'description' => 'Jenis kayu {id, nama}, mis. Jati.'],
                    ['name' => 'rincian[].realisasi_volume_m3', 'type' => 'number', 'description' => 'Realisasi volume jenis kayu tersebut, dalam m³.'],
                ],
                'example' => [
                    'jenis_hutan' => 'Hutan Negara',
                    'pengelola' => ['id' => 5, 'nama' => 'BKPH Trenggalek'],
                    'target_volume_m3' => 151.85,
                    'realisasi_volume_m3' => 275.53,
                    'rincian' => [['kayu' => ['id' => 1, 'nama' => 'Jati'], 'realisasi_volume_m3' => 275.53]],
                ],
            ],
            'hasil-hutan-bukan-kayu' => [
                'title' => 'Hasil hutan bukan kayu',
                'description' => 'Produksi hasil hutan bukan kayu per lokasi dan bulan. Satuan berbeda per komoditas, sehingga realisasi hanya tersedia per rincian. Desa selalu null.',
                'fields' => [
                    $jenisHutan,
                    $pengelola,
                    ['name' => 'target_volume', 'type' => 'number', 'description' => 'Target volume gabungan, tanpa satuan baku.'],
                    ['name' => 'rincian[].komoditas', 'type' => 'object | null', 'description' => 'Komoditas {id, nama}, mis. Bambu, Madu.'],
                    ['name' => 'rincian[].realisasi_volume', 'type' => 'number', 'description' => 'Realisasi volume komoditas tersebut.'],
                    ['name' => 'rincian[].satuan', 'type' => 'string', 'description' => 'Satuan realisasi: kg, ton, m3, liter, batang, ekor, buah, pcs, ikat, bibit, stup, orang, butir, atau lainnya.'],
                ],
                'example' => [
                    'jenis_hutan' => 'Hutan Rakyat',
                    'pengelola' => null,
                    'target_volume' => 1900.57,
                    'rincian' => [
                        ['komoditas' => ['id' => 27, 'nama' => 'Bambu'], 'realisasi_volume' => 284, 'satuan' => 'batang'],
                        ['komoditas' => ['id' => 4, 'nama' => 'Empon - Empon'], 'realisasi_volume' => 523, 'satuan' => 'kg'],
                    ],
                ],
            ],
            'pbphh' => [
                'title' => 'PBPHH',
                'description' => 'Daftar pemegang izin Perizinan Berusaha Pengolahan Hasil Hutan (industri). Ini data izin, bukan laporan berkala, sehingga tidak ada tahun/bulan dan filter tahun/bulan ditolak. Desa selalu null.',
                'period' => false,
                'fields' => [
                    ['name' => 'nama', 'type' => 'string', 'description' => 'Nama badan usaha.'],
                    ['name' => 'nomor_izin', 'type' => 'string', 'description' => 'Nomor izin/SK, bisa lebih dari satu dan memuat baris baru.'],
                    ['name' => 'nilai_investasi_rp', 'type' => 'integer | null', 'description' => 'Nilai investasi dalam rupiah.'],
                    ['name' => 'jumlah_tenaga_kerja', 'type' => 'integer', 'description' => 'Jumlah tenaga kerja.'],
                    ['name' => 'beroperasi', 'type' => 'boolean', 'description' => 'true jika industri sedang beroperasi.'],
                    ['name' => 'jenis_produksi[].jenis', 'type' => 'object', 'description' => 'Jenis produksi {id, nama}. Satuan tercantum di nama, mis. "Plywood (m3)".'],
                    ['name' => 'jenis_produksi[].kapasitas_izin', 'type' => 'number | null', 'description' => 'Kapasitas produksi sesuai izin, dalam satuan jenis produksi.'],
                ],
                'example' => [
                    'nama' => 'UD. ADHI RAJASA MANDIRI',
                    'nomor_izin' => '11/14.07/01/I/2022',
                    'nilai_investasi_rp' => 550000000,
                    'jumlah_tenaga_kerja' => 6,
                    'beroperasi' => true,
                    'jenis_produksi' => [['jenis' => ['id' => 1, 'nama' => 'Kayu Gergajian (m3)'], 'kapasitas_izin' => 2000]],
                ],
            ],
            'realisasi-pnbp' => [
                'title' => 'Realisasi PNBP',
                'description' => 'Target dan realisasi Penerimaan Negara Bukan Pajak sektor kehutanan per kabupaten, pengelola, dan bulan. Data ini mencatat sampai tingkat kabupaten; kecamatan dan desa selalu null.',
                'fields' => [
                    ['name' => 'jenis_hasil_hutan', 'type' => 'string', 'description' => 'Jenis hasil hutan, mis. Kayu atau Non Kayu.'],
                    ['name' => 'pengelola', 'type' => 'object | null', 'description' => 'Pengelola {id, nama}.'],
                    ['name' => 'target_pnbp_rp', 'type' => 'integer | null', 'description' => 'Target PNBP dalam rupiah.'],
                    ['name' => 'realisasi_pnbp_rp', 'type' => 'integer | null', 'description' => 'Realisasi PNBP dalam rupiah.'],
                ],
                'example' => [
                    'jenis_hasil_hutan' => 'Non Kayu',
                    'pengelola' => ['id' => 4, 'nama' => 'Lancar Jaya (Kediri)'],
                    'target_pnbp_rp' => 10725000,
                    'realisasi_pnbp_rp' => 2040000,
                ],
            ],
        ]);
    }

    private static function rhlEndpoints(): array
    {
        $luas = [
            ['name' => 'target_tahunan_ha', 'type' => 'number', 'description' => 'Target tahunan dalam hektare.'],
            ['name' => 'realisasi_ha', 'type' => 'number', 'description' => 'Realisasi dalam hektare.'],
            ['name' => 'sumber_dana', 'type' => 'string', 'description' => 'Sumber dana, mis. APBN, APBD Provinsi, Swadaya.'],
            ['name' => 'koordinat', 'type' => 'string | null', 'description' => 'Koordinat lokasi seperti yang diinput petugas.'],
        ];
        $luasExample = [
            'target_tahunan_ha' => 5,
            'realisasi_ha' => 5,
            'sumber_dana' => 'APBD Provinsi',
            'koordinat' => null,
        ];

        $datasets = [
            'rehab-lahan' => [
                'title' => 'Rehabilitasi lahan',
                'description' => 'Kegiatan rehabilitasi lahan kritis.',
                'fields' => $luas,
                'example' => $luasExample,
            ],
            'penghijauan-lingkungan' => [
                'title' => 'Penghijauan lingkungan',
                'description' => 'Kegiatan penghijauan di lingkungan masyarakat.',
                'fields' => $luas,
                'example' => $luasExample,
            ],
            'rehab-mangrove' => [
                'title' => 'Rehabilitasi mangrove',
                'description' => 'Pemulihan ekosistem mangrove.',
                'fields' => $luas,
                'example' => $luasExample,
            ],
            'reboisasi-ps' => [
                'title' => 'Reboisasi area PS',
                'description' => 'Reboisasi di area Perhutanan Sosial, beserta pengelolanya.',
                'fields' => [['name' => 'pengelola', 'type' => 'object | null', 'description' => 'Pengelola Perhutanan Sosial {id, nama}.'], ...$luas],
                'example' => ['pengelola' => ['id' => 1, 'nama' => 'KTH Lancar Jaya (Kediri)'], ...$luasExample],
            ],
            'rhl-teknis' => [
                'title' => 'RHL teknis',
                'description' => 'Pembangunan bangunan konservasi tanah dan air (KTA), dengan rincian jumlah unit per jenis bangunan.',
                'fields' => [
                    ['name' => 'target_tahunan_unit', 'type' => 'number', 'description' => 'Target tahunan dalam unit bangunan.'],
                    ['name' => 'sumber_dana', 'type' => 'string', 'description' => 'Sumber dana.'],
                    ['name' => 'rincian[].bangunan_kta', 'type' => 'object | null', 'description' => 'Jenis bangunan KTA {id, nama}, mis. Dam Penahan.'],
                    ['name' => 'rincian[].jumlah_unit', 'type' => 'integer', 'description' => 'Jumlah unit bangunan jenis tersebut.'],
                ],
                'example' => [
                    'target_tahunan_unit' => 3,
                    'sumber_dana' => 'APBN',
                    'rincian' => [['bangunan_kta' => ['id' => 1, 'nama' => 'Dam Penahan'], 'jumlah_unit' => 1]],
                ],
            ],
        ];

        return self::datasetEndpoints('Rehabilitasi Hutan & Lahan (RHL)', 'rhl', 'rhl.read', $datasets);
    }

    private static function perlindunganEndpoints(): array
    {
        return self::datasetEndpoints('Perlindungan Hutan', 'perlindungan', 'perlindungan.read', [
            'kebakaran-hutan' => [
                'title' => 'Kebakaran hutan',
                'description' => 'Kejadian kebakaran hutan per lokasi dan bulan.',
                'fields' => [
                    ['name' => 'pengelola', 'type' => 'object | null', 'description' => 'Pengelola kawasan {id, nama}, mis. KPH Kediri.'],
                    ['name' => 'fungsi_kawasan', 'type' => 'string', 'description' => 'Fungsi kawasan: Hutan Konservasi (HK), Hutan Lindung (HL), atau Hutan Produksi (HP).'],
                    ['name' => 'jumlah_kejadian', 'type' => 'integer', 'description' => 'Jumlah kejadian kebakaran.'],
                    ['name' => 'luas_terbakar_ha', 'type' => 'number | null', 'description' => 'Luas terbakar dalam hektare. Null bila nilai input tidak bisa dibaca sebagai angka.'],
                    ['name' => 'koordinat', 'type' => 'string | null', 'description' => 'Koordinat lokasi seperti yang diinput petugas.'],
                ],
                'example' => [
                    'pengelola' => ['id' => 2, 'nama' => 'KPH Kediri'],
                    'fungsi_kawasan' => 'Hutan Produksi (HP)',
                    'jumlah_kejadian' => 1,
                    'luas_terbakar_ha' => 1.5,
                    'koordinat' => null,
                ],
            ],
            'pengunjung-wisata' => [
                'title' => 'Pengunjung wisata',
                'description' => 'Jumlah pengunjung dan pendapatan objek wisata alam per pengelola dan bulan. Data ini tidak menyimpan lokasi, sehingga tidak ada field wilayah dan filter kabupaten_id.',
                'wilayah' => false,
                'fields' => [
                    ['name' => 'pengelola', 'type' => 'object | null', 'description' => 'Pengelola objek wisata {id, nama}.'],
                    ['name' => 'jumlah_pengunjung', 'type' => 'integer | null', 'description' => 'Jumlah pengunjung pada bulan tersebut.'],
                    ['name' => 'pendapatan_bruto_rp', 'type' => 'integer | null', 'description' => 'Pendapatan bruto dalam rupiah.'],
                ],
                'example' => [
                    'pengelola' => ['id' => 11, 'nama' => 'Tani Sentosa (Trenggalek)'],
                    'jumlah_pengunjung' => 1438,
                    'pendapatan_bruto_rp' => 14380000,
                ],
            ],
        ]);
    }

    /**
     * Pasangan endpoint daftar + /deleted untuk setiap dataset satu modul.
     * Dataset dengan 'wilayah' => false tidak punya field wilayah dan filter kabupaten_id.
     */
    private static function datasetEndpoints(string $group, string $prefix, string $ability, array $datasets): array
    {
        $endpoints = [];
        foreach ($datasets as $key => $dataset) {
            $wilayah = $dataset['wilayah'] ?? true;
            $period = $dataset['period'] ?? true;
            $endpoints[] = [
                'group' => $group,
                'route' => "api.v1.$prefix.$key.index",
                'method' => 'GET',
                'path' => "/$prefix/$key",
                'title' => $dataset['title'],
                'description' => $dataset['description'] . ' Hanya data final, diurutkan dari yang paling lama diperbarui.',
                'ability' => $ability,
                'params' => self::listParams($wilayah, $period),
                'fields' => [...self::commonFields($wilayah, $period), ...$dataset['fields'], ...self::timestampFields()],
                'example' => self::listExample([[...self::exampleCommon($wilayah, $period), ...$dataset['example'], ...self::exampleTimestamps()]]),
            ];
            $endpoints[] = [
                'group' => $group,
                'route' => "api.v1.$prefix.$key.deleted",
                'method' => 'GET',
                'path' => "/$prefix/$key/deleted",
                'title' => $dataset['title'] . ': data yang ditarik',
                'description' => 'ID data yang sejak waktu tertentu tidak lagi dikirim karena dihapus atau statusnya tidak final lagi. Hapus ID ini dari salinan data Anda.',
                'ability' => $ability,
                'params' => self::deletedParams(),
                'fields' => self::deletedFields(),
                'example' => self::listExample([
                    ['id' => 115, 'alasan' => 'dihapus', 'waktu' => '2026-03-10T05:29:39+00:00'],
                    ['id' => 118, 'alasan' => 'tidak_final', 'waktu' => '2026-03-09T01:09:20+00:00'],
                ]),
            ];
        }

        return $endpoints;
    }

    private static function listParams(bool $wilayah = true, bool $period = true): array
    {
        return array_values(array_filter([
            $period ? ['name' => 'tahun', 'type' => 'integer', 'required' => false, 'description' => 'Tahun kegiatan, mis. 2026.'] : null,
            $period ? ['name' => 'bulan', 'type' => 'integer', 'required' => false, 'description' => 'Bulan kegiatan, 1 sampai 12.'] : null,
            $wilayah ? ['name' => 'kabupaten_id', 'type' => 'string', 'required' => false, 'description' => 'Kode kabupaten/kota 4 digit, mis. 3503 untuk Trenggalek.'] : null,
            ['name' => 'cdk_id', 'type' => 'integer', 'required' => false, 'description' => 'ID CDK. Klien tingkat CDK tetap hanya menerima data CDK-nya.'],
            ['name' => 'updated_since', 'type' => 'string (ISO 8601)', 'required' => false, 'description' => 'Hanya data yang diperbarui sejak waktu ini. Isi dengan waktu_server dari sinkronisasi sebelumnya.'],
            ['name' => 'per_page', 'type' => 'integer', 'required' => false, 'description' => 'Jumlah data per halaman, 1 sampai 500. Bawaan 100.'],
            ['name' => 'page', 'type' => 'integer', 'required' => false, 'description' => 'Nomor halaman. Lebih mudah mengikuti links.next.'],
        ]));
    }

    private static function deletedParams(): array
    {
        return [
            ['name' => 'since', 'type' => 'string (ISO 8601)', 'required' => true, 'description' => 'Waktu sinkronisasi terakhir (waktu_server sebelumnya).'],
            ['name' => 'per_page', 'type' => 'integer', 'required' => false, 'description' => 'Jumlah ID per halaman, 1 sampai 1000. Bawaan 500.'],
        ];
    }

    private static function commonFields(bool $wilayah = true, bool $period = true): array
    {
        $fields = array_values(array_filter([
            ['name' => 'id', 'type' => 'integer', 'description' => 'ID data di SMART-HUT. Pakai sebagai kunci saat menyimpan salinan.'],
            $period ? ['name' => 'tahun', 'type' => 'integer', 'description' => 'Tahun kegiatan.'] : null,
            $period ? ['name' => 'bulan', 'type' => 'integer', 'description' => 'Bulan kegiatan (1 sampai 12).'] : null,
            ['name' => 'cdk', 'type' => 'object | null', 'description' => 'CDK pemilik data {id, nama}.'],
        ]));

        if (!$wilayah) {
            return $fields;
        }

        return [
            ...$fields,
            ['name' => 'wilayah.provinsi', 'type' => 'object | null', 'description' => 'Provinsi {id, nama}.'],
            ['name' => 'wilayah.kabupaten', 'type' => 'object | null', 'description' => 'Kabupaten/kota {id, nama}. id berupa kode wilayah 4 digit.'],
            ['name' => 'wilayah.kecamatan', 'type' => 'object | null', 'description' => 'Kecamatan {id, nama}. id 7 digit.'],
            ['name' => 'wilayah.desa', 'type' => 'object | null', 'description' => 'Desa/kelurahan {id, nama}. id 10 digit.'],
        ];
    }

    private static function timestampFields(): array
    {
        return [
            ['name' => 'difinalkan_pada', 'type' => 'string | null', 'description' => 'Waktu data disetujui final (ISO 8601).'],
            ['name' => 'dibuat_pada', 'type' => 'string', 'description' => 'Waktu data pertama kali diinput.'],
            ['name' => 'diperbarui_pada', 'type' => 'string', 'description' => 'Waktu perubahan terakhir. Dipakai filter updated_since.'],
        ];
    }

    private static function deletedFields(): array
    {
        return [
            ['name' => 'data[].id', 'type' => 'integer', 'description' => 'ID data yang harus dihapus dari salinan Anda.'],
            ['name' => 'data[].alasan', 'type' => '"dihapus" | "tidak_final"', 'description' => 'Dihapus di SMART-HUT, atau statusnya kembali ke proses verifikasi.'],
            ['name' => 'data[].waktu', 'type' => 'string', 'description' => 'Waktu penghapusan atau perubahan status.'],
        ];
    }

    private static function exampleCommon(bool $wilayah = true, bool $period = true): array
    {
        $example = $period ? ['id' => 109, 'tahun' => 2026, 'bulan' => 3] : ['id' => 109];
        $example['cdk'] = ['id' => 3, 'nama' => 'CDK Wilayah Trenggalek'];

        if ($wilayah) {
            $example['wilayah'] = [
                'provinsi' => ['id' => '35', 'nama' => 'JAWA TIMUR'],
                'kabupaten' => ['id' => '3503', 'nama' => 'KABUPATEN TRENGGALEK'],
                'kecamatan' => ['id' => '3503060', 'nama' => 'PULE'],
                'desa' => ['id' => '3503060009', 'nama' => 'KARANGANYAR'],
            ];
        }

        return $example;
    }

    private static function exampleTimestamps(): array
    {
        return [
            'difinalkan_pada' => '2026-03-27T11:05:06+00:00',
            'dibuat_pada' => '2026-03-27T02:24:41+00:00',
            'diperbarui_pada' => '2026-03-27T11:05:06+00:00',
        ];
    }

    private static function listExample(array $data): array
    {
        return [
            'data' => $data,
            'links' => ['next' => 'https://<host>/api/v1/...&page=2', 'prev' => null],
            'meta' => ['current_page' => 1, 'last_page' => 21, 'per_page' => 100, 'total' => 2087],
            'waktu_server' => '2026-11-01T10:00:00+00:00',
        ];
    }

    public static function errors(): array
    {
        return [
            ['status' => 401, 'meaning' => 'Token tidak ada, salah, sudah dicabut, atau kedaluwarsa.', 'example' => ['message' => 'Unauthenticated.']],
            ['status' => 403, 'meaning' => 'Klien dinonaktifkan, token bukan milik klien integrasi, atau akses data (ability) kurang.', 'example' => ['message' => 'Klien integrasi dinonaktifkan.']],
            ['status' => 404, 'meaning' => 'Endpoint atau data tidak ditemukan.', 'example' => ['message' => 'Not Found']],
            ['status' => 422, 'meaning' => 'Parameter tidak valid. Detail per parameter ada di "errors".', 'example' => ['message' => 'Tahun harus berupa bilangan bulat.', 'errors' => ['tahun' => ['Tahun harus berupa bilangan bulat.']]]],
            ['status' => 429, 'meaning' => 'Batas permintaan per menit terlampaui. Tunggu sesuai header Retry-After.', 'example' => ['message' => 'Terlalu banyak permintaan. Coba lagi setelah 42 detik.']],
        ];
    }

    public static function changelog(): array
    {
        return [
            ['version' => 'v1', 'date' => '2026-11-12', 'changes' => [
                'Endpoint rekap per modul: GET /rhl/rekap, /perlindungan/rekap, /bina-usaha/rekap, dan /pemberdayaan/rekap. Angka ringkasan dihitung di server, total dan per kabupaten.',
                'Rekap Pemberdayaan menghitung setiap KTH satu kali per nomor register (catatan terbaru).',
                'Rekap memakai tahun terakhir yang punya data final sebagai bawaan, dan menyertakan rentang bulan (periode) data.',
            ]],
            ['version' => 'v1', 'date' => '2026-11-01', 'changes' => [
                'Endpoint data Pemberdayaan: SKPS, KUPS, perkembangan KTH, nilai ekonomi, dan nilai transaksi ekonomi (ability pemberdayaan.read).',
                'Endpoint data Bina Usaha: hasil hutan kayu, hasil hutan bukan kayu, PBPHH, dan realisasi PNBP (ability bina-usaha.read).',
                'Endpoint data Perlindungan: kebakaran hutan dan pengunjung wisata (ability perlindungan.read).',
                'Endpoint data RHL: rehabilitasi lahan, penghijauan lingkungan, rehabilitasi mangrove, reboisasi area PS, dan RHL teknis (ability rhl.read).',
                'Sinkronisasi inkremental lewat parameter updated_since dan endpoint /deleted.',
                'Rilis fondasi API: autentikasi token, cakupan per CDK, rate limit, dan endpoint GET /klien.',
            ]],
        ];
    }
}
