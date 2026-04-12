<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Pegawai;
use App\Exports\ProyeksiExport;
use Maatwebsite\Excel\Facades\Excel;

class ProyeksiGajiController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:kepegawaian.view')->only(['index']);
        $this->middleware('permission:kepegawaian.export')->only(['export']);
    }
    public function index(Request $request)
    {
        $year = (int) $request->input('year', now()->year);
        $month = $request->input('month', 'all');

        if ($month !== 'all') {
            $month = (int) $month;
        }

        $unitKerja = $request->input('unit_kerja');

        // Fetch all active employees (Status: final in demografi)
        $query = Pegawai::where('status', 'final')
            ->where('status_kedudukan', 'Aktif')
            ->with(['latestKgb']);

        if ($unitKerja) {
            $query->where('unit_kerja', $unitKerja);
        }

        $pegawais = $query->get();

        // Calculate projections
        $proyeksiKgb = $pegawais->map(function ($pegawai) use ($year, $month) {
            $nextDate = $pegawai->getKgbProjectionForYear($year);
            if ($nextDate && ($month === 'all' || $nextDate->month == $month)) {
                return [
                    'id' => $pegawai->id,
                    'nip' => $pegawai->nip,
                    'nama' => $pegawai->nama_lengkap,
                    'pangkat_golongan' => $pegawai->pangkat_golongan,
                    'unit_kerja' => $pegawai->unit_kerja,
                    'tmt_kgb_terakhir' => $pegawai->latestKgb?->tmt_kgb?->format('Y-m-d'),
                    'tmt_kgb_berikutnya' => $nextDate->format('Y-m-d'),
                    'status' => $nextDate->isCurrentMonth() ? 'Bulan Ini' : ($nextDate->isPast() ? 'Sudah Terlewat' : 'Akan Datang'),
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
                    'status' => $retirementDate->isCurrentMonth() ? 'Bulan Ini' : ($retirementDate->isPast() ? 'Sudah Pensiun' : 'Akan Datang'),
                ];
            }
            return null;
        })->filter()->values();

        // Get unique unit kerja for filter
        $unitKerjaList = Pegawai::whereNotNull('unit_kerja')->distinct()->pluck('unit_kerja');

        return Inertia::render('Kepegawaian/Proyeksi', [
            'proyeksiKgb' => $proyeksiKgb,
            'proyeksiPensiun' => $proyeksiPensiun,
            'filters' => [
                'year' => (int) $year,
                'month' => $month,
                'unit_kerja' => $unitKerja,
            ],
            'unitList' => $unitKerjaList,
        ]);
    }

    public function export(Request $request)
    {
        $year = (int) $request->input('year', now()->year);
        $month = $request->input('month', now()->month);
        if ($month !== 'all') {
            $month = (int) $month;
        }
        $unitKerja = $request->input('unit_kerja');

        // Reuse the same logic as index
        $query = Pegawai::where('status', 'final')
            ->where('status_kedudukan', 'Aktif')
            ->with(['latestKgb']);

        if ($unitKerja) {
            $query->where('unit_kerja', $unitKerja);
        }

        $pegawais = $query->get();

        $proyeksiKgb = $pegawais->map(function ($pegawai) use ($year, $month) {
            $nextDate = $pegawai->getKgbProjectionForYear($year);
            if ($nextDate && ($month === 'all' || $nextDate->month == $month)) {
                return [
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

        $fileName = "Proyeksi_KGB_Pensiun_{$year}" . ($month !== 'all' ? "_{$month}" : "") . ".xlsx";

        return Excel::download(new ProyeksiExport($proyeksiKgb->toArray(), $proyeksiPensiun->toArray()), $fileName);
    }
}
