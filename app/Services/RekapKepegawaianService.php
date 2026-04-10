<?php

namespace App\Services;

use App\Models\Pegawai;
use App\Models\Bezetting;
use App\Models\RekapBulananPegawai;
use App\Models\RekapStatistikBulanan;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class RekapKepegawaianService
{
    public function generate(int $year, int $month, string $sumber = 'manual'): void
    {
        DB::transaction(function () use ($year, $month, $sumber) {
            // 1. Ambil data pegawai aktif dan final
            $pegawais = Pegawai::with(['bezetting', 'latestKgb'])
                ->where('status', 'final')
                ->where('status_kedudukan', 'Aktif')
                ->get();

            // 2. Simpan snapshot per pegawai
            foreach ($pegawais as $pegawai) {
                $latestKgb = $pegawai->latestKgb;
                $retirementDate = $pegawai->retirement_date;
                
                $snapshotData = [
                    'nip' => $pegawai->nip,
                    'nama_lengkap' => $pegawai->nama_lengkap,
                    'status_pegawai' => $pegawai->status_pegawai,
                    'jenis_kelamin' => $pegawai->jenis_kelamin,
                    'pendidikan_terakhir' => $pegawai->pendidikan_terakhir,
                    'pangkat_golongan' => $pegawai->pangkat_golongan,
                    'bezetting_id' => $pegawai->bezetting_id,
                    'nama_jabatan_snapshot' => $pegawai->bezetting?->nama_jabatan,
                    'unit_kerja' => $pegawai->unit_kerja,
                    'skpd' => $pegawai->skpd,
                    'status_kedudukan' => $pegawai->status_kedudukan,
                    'status_pernikahan' => $pegawai->status_pernikahan,
                    'generasi' => $pegawai->generasi,
                    'tanggal_lahir' => $pegawai->tanggal_lahir,
                    'usia_per_periode' => $pegawai->tanggal_lahir ? $pegawai->tanggal_lahir->diffInYears(Carbon::create($year, $month, 1)->endOfMonth()) : null,
                    'tmt_cpns' => $pegawai->tmt_cpns,
                    'tmt_pns' => $pegawai->tmt_pns,
                    'masa_kerja_tahun' => $pegawai->tmt_cpns ? $pegawai->tmt_cpns->diffInYears(Carbon::create($year, $month, 1)->endOfMonth()) : null,
                    'bup' => $pegawai->bup,
                    'proyeksi_pensiun' => $retirementDate,
                    'bulan_pensiun_tersisa' => $retirementDate ? Carbon::create($year, $month, 1)->diffInMonths($retirementDate, false) : null,
                    'gaji_pokok_terakhir' => $latestKgb?->gaji_pokok_baru,
                    'tmt_kgb_berikutnya' => $pegawai->next_kgb_date,
                    'sumber_data' => $sumber,
                ];

                $key = [
                    'pegawai_id' => $pegawai->id,
                    'periode_tahun' => $year,
                    'periode_bulan' => $month,
                ];

                $existing = RekapBulananPegawai::withTrashed()
                    ->where($key)
                    ->first();

                if ($existing) {
                    if ($existing->trashed()) {
                        $existing->restore();
                    }
                    $existing->update($snapshotData);
                } else {
                    RekapBulananPegawai::create(array_merge($key, $snapshotData));
                }
            }

            // 3. Jalankan kalkulasi statistik
            $this->recalculateStatistik($year, $month, $sumber);
        });
    }

    public function recalculateStatistik(int $year, int $month, string $sumber = 'manual'): void
    {
        $snapshots = RekapBulananPegawai::forPeriode($year, $month)->get();
        $total = $snapshots->count();

        if ($total === 0) return;

        $stats = [
            'total_pegawai_aktif' => $total,
            'total_pns' => $snapshots->where('status_pegawai', 'PNS')->count(),
            'total_pppk' => $snapshots->where('status_pegawai', 'PPPK')->count(),
            'total_honorer' => $snapshots->whereIn('status_pegawai', ['Honorer', 'Tenaga Kontrak', 'Lainnya'])->count(),
            'total_laki' => $snapshots->where('jenis_kelamin', 'L')->count(),
            'total_perempuan' => $snapshots->where('jenis_kelamin', 'P')->count(),
            
            // Pensiun
            'total_pensiun_tahun_ini' => $snapshots->filter(fn($s) => $s->proyeksi_pensiun && $s->proyeksi_pensiun->year == $year)->count(),
            'total_pensiun_bulan_ini' => $snapshots->filter(fn($s) => $s->proyeksi_pensiun && $s->proyeksi_pensiun->year == $year && $s->proyeksi_pensiun->month == $month)->count(),
            'total_pensiun_6_bulan' => $snapshots->whereBetween('bulan_pensiun_tersisa', [0, 6])->count(),

            // JSON Stats
            'statistik_status_pegawai' => $snapshots->groupBy('status_pegawai')->map(fn($g) => $g->count())->toArray(),
            'statistik_pendidikan' => $snapshots->groupBy('pendidikan_terakhir')->map(fn($g) => $g->count())->toArray(),
            'statistik_golongan' => $snapshots->groupBy('pangkat_golongan')->map(fn($g) => $g->count())->toArray(),
            'statistik_generasi' => $snapshots->groupBy('generasi')->map(fn($g) => $g->count())->toArray(),
            'statistik_status_pernikahan' => $snapshots->groupBy('status_pernikahan')->map(fn($g) => $g->count())->toArray(),
            'statistik_unit_kerja' => $snapshots->groupBy('unit_kerja')->map(fn($g) => $g->count())->toArray(),
            
            // Masa Kerja
            'statistik_masa_kerja' => [
                '0-5 Tahun' => $snapshots->whereBetween('masa_kerja_tahun', [0, 5])->count(),
                '6-10 Tahun' => $snapshots->whereBetween('masa_kerja_tahun', [6, 10])->count(),
                '11-15 Tahun' => $snapshots->whereBetween('masa_kerja_tahun', [11, 15])->count(),
                '16-20 Tahun' => $snapshots->whereBetween('masa_kerja_tahun', [16, 20])->count(),
                '> 20 Tahun' => $snapshots->where('masa_kerja_tahun', '>', 20)->count(),
            ],

            // Usia
            'statistik_usia' => [
                '< 30 Tahun' => $snapshots->where('usia_per_periode', '<', 30)->count(),
                '30-40 Tahun' => $snapshots->whereBetween('usia_per_periode', [30, 40])->count(),
                '41-50 Tahun' => $snapshots->whereBetween('usia_per_periode', [41, 50])->count(),
                '> 50 Tahun' => $snapshots->where('usia_per_periode', '>', 50)->count(),
            ],

            // KGB
            'kgb_jatuh_bulan_ini' => $snapshots->filter(fn($s) => $s->tmt_kgb_berikutnya && $s->tmt_kgb_berikutnya->year == $year && $s->tmt_kgb_berikutnya->month == $month)->count(),
            'kgb_jatuh_3_bulan' => $snapshots->filter(fn($s) => $s->tmt_kgb_berikutnya && $s->tmt_kgb_berikutnya->isBetween(Carbon::create($year, $month, 1), Carbon::create($year, $month, 1)->addMonths(3)))->count(),
        ];

        // Bezetting Stats
        $bezettings = Bezetting::all();
        $bezettingStats = [];
        foreach ($bezettings as $bz) {
            $isi = $snapshots->where('bezetting_id', $bz->id)->count();
            $bezettingStats[$bz->nama_jabatan] = [
                'kebutuhan' => $bz->kebutuhan,
                'terisi' => $isi,
                'selisih' => $bz->kebutuhan - $isi,
            ];
        }
        $stats['statistik_bezetting'] = $bezettingStats;

        $key = [
            'periode_tahun' => $year,
            'periode_bulan' => $month,
        ];

        $rekapStats = RekapStatistikBulanan::withTrashed()
            ->where($key)
            ->first();

        $data = array_merge($stats, [
            'dihasilkan_dari' => $sumber,
            'dihasilkan_pada' => now(),
            'status' => 'draft', // Reset to draft if recalculated? Or keep existing? 
        ]);

        if ($rekapStats) {
            if ($rekapStats->trashed()) {
                $rekapStats->restore();
            }
            $rekapStats->update($data);
        } else {
            RekapStatistikBulanan::create(array_merge($key, $data));
        }
    }
}
