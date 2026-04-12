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
        if ($snapshots->isEmpty()) return;

        $stats = array_merge(
            $this->getSummaryStats($snapshots, $year, $month),
            $this->getCategoricalStats($snapshots),
            $this->getRangeStats($snapshots),
            ['statistik_bezetting' => $this->getBezettingStats($snapshots)]
        );

        $key = ['periode_tahun' => $year, 'periode_bulan' => $month];
        $rekapStats = RekapStatistikBulanan::withTrashed()->where($key)->first();

        $data = array_merge($stats, [
            'dihasilkan_dari' => $sumber,
            'dihasilkan_pada' => now(),
            'status' => $rekapStats?->status ?? 'draft',
        ]);

        if ($rekapStats) {
            if ($rekapStats->trashed()) $rekapStats->restore();
            $rekapStats->update($data);
        } else {
            RekapStatistikBulanan::create(array_merge($key, $data));
        }
    }

    private function getSummaryStats($snapshots, int $year, int $month): array
    {
        return [
            'total_pegawai_aktif' => $snapshots->count(),
            'total_pns' => $snapshots->where('status_pegawai', 'PNS')->count(),
            'total_pppk' => $snapshots->where('status_pegawai', 'PPPK')->count(),
            'total_honorer' => $snapshots->whereIn('status_pegawai', ['Honorer', 'Tenaga Kontrak', 'Lainnya'])->count(),
            'total_laki' => $snapshots->where('jenis_kelamin', 'L')->count(),
            'total_perempuan' => $snapshots->where('jenis_kelamin', 'P')->count(),
            
            // Pensiun
            'total_pensiun_tahun_ini' => $snapshots->filter(fn($s) => $s->proyeksi_pensiun?->year == $year)->count(),
            'total_pensiun_bulan_ini' => $snapshots->filter(fn($s) => $s->proyeksi_pensiun?->year == $year && $s->proyeksi_pensiun?->month == $month)->count(),
            'total_pensiun_6_bulan' => $snapshots->whereBetween('bulan_pensiun_tersisa', [0, 6])->count(),

            // KGB
            'kgb_jatuh_bulan_ini' => $snapshots->filter(fn($s) => $s->tmt_kgb_berikutnya?->year == $year && $s->tmt_kgb_berikutnya?->month == $month)->count(),
            'kgb_jatuh_3_bulan' => $snapshots->filter(fn($s) => $s->tmt_kgb_berikutnya && $s->tmt_kgb_berikutnya->isBetween(Carbon::create($year, $month, 1), Carbon::create($year, $month, 1)->addMonths(3)))->count(),
        ];
    }

    private function getCategoricalStats($snapshots): array
    {
        return [
            'statistik_status_pegawai' => $snapshots->groupBy('status_pegawai')->map->count()->toArray(),
            'statistik_pendidikan' => collect(\App\Enums\Pendidikan::cases())->mapWithKeys(fn($p) => [
                $p->value => $snapshots->where('pendidikan_terakhir', $p->value)->count()
            ])->toArray(),
            'statistik_golongan' => collect(\App\Enums\Golongan::cases())->mapWithKeys(fn($g) => [
                $g->value => $snapshots->where('pangkat_golongan', $g->value)->count()
            ])->toArray(),
            'statistik_generasi' => $snapshots->groupBy('generasi')->map->count()->toArray(),
            'statistik_status_pernikahan' => $snapshots->groupBy('status_pernikahan')->map->count()->toArray(),
            'statistik_unit_kerja' => $snapshots->groupBy('unit_kerja')->map->count()->toArray(),
        ];
    }

    private function getRangeStats($snapshots): array
    {
        return [
            'statistik_masa_kerja' => [
                '0-5 Tahun' => $snapshots->whereBetween('masa_kerja_tahun', [0, 5])->count(),
                '6-10 Tahun' => $snapshots->whereBetween('masa_kerja_tahun', [6, 10])->count(),
                '11-15 Tahun' => $snapshots->whereBetween('masa_kerja_tahun', [11, 15])->count(),
                '16-20 Tahun' => $snapshots->whereBetween('masa_kerja_tahun', [16, 20])->count(),
                '> 20 Tahun' => $snapshots->where('masa_kerja_tahun', '>', 20)->count(),
            ],
            'statistik_usia' => [
                '< 30 Tahun' => $snapshots->where('usia_per_periode', '<', 30)->count(),
                '30-40 Tahun' => $snapshots->whereBetween('usia_per_periode', [30, 40])->count(),
                '41-50 Tahun' => $snapshots->whereBetween('usia_per_periode', [41, 50])->count(),
                '> 50 Tahun' => $snapshots->where('usia_per_periode', '>', 50)->count(),
            ],
        ];
    }

    private function getBezettingStats($snapshots): array
    {
        $bezettingStats = [];
        foreach (Bezetting::all() as $bz) {
            $isi = $snapshots->where('bezetting_id', $bz->id)->count();
            $bezettingStats[$bz->nama_jabatan] = [
                'kebutuhan' => $bz->kebutuhan,
                'terisi' => $isi,
                'selisih' => $bz->kebutuhan - $isi,
            ];
        }
        return $bezettingStats;
    }
}
