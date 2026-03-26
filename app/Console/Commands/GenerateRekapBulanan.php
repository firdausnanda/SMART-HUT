<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Pegawai;
use Carbon\Carbon;

class GenerateRekapBulanan extends Command
{
    protected $signature = 'rekap:bulanan';
    protected $description = 'Generate rekapitulasi demografi pegawai setiap akhir bulan';

    public function handle()
    {
        // Ambil waktu saat ini (misal dijalankan tanggal 31 jam 23:59)
        $now = Carbon::now();
        $tahun = $now->year;
        $bulan = $now->month;

        // Hanya hitung pegawai yang aktif dan datanya sudah disetujui (final)
        $pegawais = Pegawai::where('status_kedudukan', 'Aktif')
            ->where('status', 'final')
            ->get();

        if ($pegawais->isEmpty()) {
            $this->info('Tidak ada data pegawai aktif yang final.');
            return;
        }

        // Proses agregasi data
        $statistikStatus = $pegawais->groupBy('status_pegawai')->map->count();
        $statistikPendidikan = $pegawais->groupBy('pendidikan_terakhir')->map->count();
        $statistikGolongan = $pegawais->groupBy('pangkat_golongan')->map->count();
        $statistikJK = $pegawais->groupBy('jenis_kelamin')->map->count();
        $statistikGenerasi = $pegawais->groupBy('generasi')->map->count();
        $statistikStatusPernikahan = $pegawais->groupBy('status_pernikahan')->map->count();

        // Simpan ke tabel rekap (Gunakan updateOrCreate agar jika dijalankan ulang tidak duplikat)
        DB::table('rekap_demografi_bulanans')->updateOrCreate(
        ['periode_tahun' => $tahun, 'periode_bulan' => $bulan],
        [
            'total_pegawai_aktif' => $pegawais->count(),
            'statistik_status_pegawai' => $statistikStatus->toJson(),
            'statistik_pendidikan' => $statistikPendidikan->toJson(),
            'statistik_golongan' => $statistikGolongan->toJson(),
            'statistik_jenis_kelamin' => $statistikJK->toJson(),
            'statistik_generasi' => $statistikGenerasi->toJson(),
            'statistik_status_pernikahan' => $statistikStatusPernikahan->toJson(),
            'created_at' => $now,
            'updated_at' => $now,
        ]
        );

        $this->info("Rekap demografi bulan {$bulan} tahun {$tahun} berhasil digenerate!");
    }
}