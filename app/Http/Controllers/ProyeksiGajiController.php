<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class ProyeksiGajiController extends Controller
{
    public function index(Request $request)
    {
        $year = $request->input('year', now()->year);
        $month = $request->input('month', now()->month);
        $unitKerja = $request->input('unit_kerja');

        // Fetch all active employees (Status: final in demografi)
        $query = \App\Models\Pegawai::where('status', 'final')
            ->where('status_kedudukan', 'Aktif')
            ->with(['latestKgb']);

        if ($unitKerja) {
            $query->where('unit_kerja', $unitKerja);
        }

        $pegawais = $query->get();

        // Calculate projections
        $proyeksiKgb = $pegawais->map(function ($pegawai) use ($year, $month) {
            $nextDate = $pegawai->next_kgb_date;
            if ($nextDate && ($nextDate->year == $year) && ($month === 'all' || $nextDate->month == $month)) {
                return [
                    'id' => $pegawai->id,
                    'nip' => $pegawai->nip,
                    'nama' => $pegawai->nama_lengkap,
                    'pangkat_golongan' => $pegawai->pangkat_golongan,
                    'unit_kerja' => $pegawai->unit_kerja,
                    'tmt_kgb_terakhir' => $pegawai->latestKgb?->tmt_kgb?->format('Y-m-d'),
                    'tmt_kgb_berikutnya' => $nextDate->format('Y-m-d'),
                    'status' => $nextDate->isPast() ? 'Sudah Waktunya' : ($nextDate->isCurrentMonth() ? 'Bulan Ini' : 'Akan Datang'),
                ];
            }
            return null;
        })->filter()->values();

        $proyeksiPensiun = $pegawais->map(function ($pegawai) use ($year, $month) {
            $retirementDate = $pegawai->retirement_date;
            if ($retirementDate && ($retirementDate->year == $year) && ($month === 'all' || $retirementDate->month == $month)) {
                return [
                    'id' => $pegawai->id,
                    'nip' => $pegawai->nip,
                    'nama' => $pegawai->nama_lengkap,
                    'pangkat_golongan' => $pegawai->pangkat_golongan,
                    'unit_kerja' => $pegawai->unit_kerja,
                    'tanggal_lahir' => $pegawai->tanggal_lahir?->format('Y-m-d'),
                    'bup' => $pegawai->bup,
                    'tmt_pensiun' => $retirementDate->format('Y-m-d'),
                    'status' => $retirementDate->isPast() ? 'Waktunya Pensiun' : ($retirementDate->isCurrentMonth() ? 'Bulan Ini' : 'Akan Datang'),
                ];
            }
            return null;
        })->filter()->values();

        // Get unique unit kerja for filter
        $unitKerjaList = \App\Models\Pegawai::whereNotNull('unit_kerja')->distinct()->pluck('unit_kerja');

        return Inertia::render('Kepegawaian/Proyeksi', [
            'proyeksiKgb' => $proyeksiKgb,
            'proyeksiPensiun' => $proyeksiPensiun,
            'filters' => [
                'year' => (int)$year,
                'month' => $month,
                'unit_kerja' => $unitKerjaList,
                'selected_unit' => $unitKerja,
            ]
        ]);
    }
}
