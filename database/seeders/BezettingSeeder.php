<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class BezettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = Carbon::now();

        $data = [
            // ================= KEPALA CABANG DINAS & SUB BAGIAN TU =================
            ['nama_jabatan' => 'Kepala Cabang Dinas', 'kebutuhan' => 1],
            ['nama_jabatan' => 'Kepala Sub Bagian TU', 'kebutuhan' => 1],
            ['nama_jabatan' => 'Penata Kelola Sistem dan Teknologi Informasi', 'kebutuhan' => 1],
            ['nama_jabatan' => 'Penelaah Teknis Kebijakan (TU)', 'kebutuhan' => 2],
            ['nama_jabatan' => 'Pengolah Data dan Informasi (TU)', 'kebutuhan' => 3],
            ['nama_jabatan' => 'Pengadministrasi Perkantoran', 'kebutuhan' => 5],

            // ================= SEKSI RLPM =================
            ['nama_jabatan' => 'Kepala Seksi RLPM', 'kebutuhan' => 1],
            ['nama_jabatan' => 'Penelaah Teknis Kebijakan (RLPM)', 'kebutuhan' => 2],
            ['nama_jabatan' => 'Pengolah Data dan Informasi (RLPM)', 'kebutuhan' => 3],
            ['nama_jabatan' => 'Penyuluh Kehutanan Ahli Muda', 'kebutuhan' => 14],
            ['nama_jabatan' => 'Penyuluh Kehutanan Ahli Pertama', 'kebutuhan' => 31],
            ['nama_jabatan' => 'Penyuluh Kehutanan Mahir', 'kebutuhan' => 11],
            ['nama_jabatan' => 'Penyuluh Kehutanan Penyelia', 'kebutuhan' => 4],
            ['nama_jabatan' => 'Penyuluh Kehutanan Terampil', 'kebutuhan' => 22],
            ['nama_jabatan' => 'Penyuluh Kehutanan Pemula', 'kebutuhan' => 36],

            // ================= SEKSI TKUK =================
            ['nama_jabatan' => 'Kepala Seksi TKUK', 'kebutuhan' => 1],
            ['nama_jabatan' => 'Penelaah Teknis Kebijakan (TKUK)', 'kebutuhan' => 2],
            ['nama_jabatan' => 'Pengolah Data dan Informasi (TKUK)', 'kebutuhan' => 2],
            ['nama_jabatan' => 'Pengendali Ekosistem Hutan Ahli Muda', 'kebutuhan' => 4],
            ['nama_jabatan' => 'Pengendali Ekosistem Hutan Ahli Pertama', 'kebutuhan' => 7],
            ['nama_jabatan' => 'Pengendali Ekosistem Hutan Penyelia', 'kebutuhan' => 4],
            ['nama_jabatan' => 'Pengendali Ekosistem Hutan Mahir', 'kebutuhan' => 6],
            ['nama_jabatan' => 'Pengendali Ekosistem Hutan Terampil', 'kebutuhan' => 7],
            ['nama_jabatan' => 'Pengendali Ekosistem Hutan Pemula', 'kebutuhan' => 8],
        ];

        // Menyisipkan timestamps
        $insertData = array_map(function ($item) use ($now) {
            return array_merge($item, [
            'created_at' => $now,
            'updated_at' => $now,
            ]);
        }, $data);

        DB::table('bezettings')->insert($insertData);
    }
}