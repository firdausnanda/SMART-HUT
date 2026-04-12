<?php

namespace App\Http\Controllers;

use App\Models\RekapBulananPegawai;
use App\Models\RekapStatistikBulanan;
use App\Services\RekapKepegawaianService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\RekapBulananExport;
use App\Actions\SingleWorkflowAction;
use App\Actions\BulkWorkflowAction;
use App\Enums\WorkflowAction;
use Illuminate\Validation\Rule;
use Carbon\Carbon;

class RekapBulananController extends Controller
{
    private $service;

    public function __construct(RekapKepegawaianService $service)
    {
        $this->service = $service;
        $this->middleware('permission:kepegawaian.view')->only(['index', 'show', 'showPegawai', 'export']);
        $this->middleware('permission:kepegawaian.create')->only(['generate', 'storePegawai']);
        $this->middleware('permission:kepegawaian.edit|kepegawaian.approve')->only(['singleWorkflowAction']);
        $this->middleware('permission:kepegawaian.edit|kepegawaian.approve|kepegawaian.delete')->only(['bulkWorkflowAction']);
        $this->middleware('permission:kepegawaian.delete')->only(['destroyPegawai', 'destroy']);
    }

    public function index(Request $request)
    {
        $year = $request->get('year', now()->year);

        $rekaps = RekapStatistikBulanan::forYear($year)
            ->orderBy('periode_bulan', 'desc')
            ->get();

        return Inertia::render('Kepegawaian/RekapBulanan/Index', [
            'rekaps' => $rekaps,
            'filters' => [
                'year' => (int) $year,
            ]
        ]);
    }

    public function show($year, $month)
    {
        $rekap = RekapStatistikBulanan::where('periode_tahun', $year)
            ->where('periode_bulan', $month)
            ->firstOrFail();

        // Hitung periode bulan sebelumnya untuk fitur perbandingan
        $prevDate = Carbon::create($year, $month, 1)->subMonth();
        $rekapSebelumnya = RekapStatistikBulanan::where('periode_tahun', $prevDate->year)
            ->where('periode_bulan', $prevDate->month)
            ->first([
                'periode_bulan',
                'periode_tahun',
                'status',
                'total_pegawai_aktif',
                'total_pns',
                'total_pppk',
                'kgb_jatuh_bulan_ini',
                'total_pensiun_tahun_ini',
            ]);

        return Inertia::render('Kepegawaian/RekapBulanan/Show', [
            'rekap' => $rekap,
            'rekap_sebelumnya' => $rekapSebelumnya,
            'pendidikanLabels' => array_column(\App\Enums\Pendidikan::cases(), 'value'),
            'golonganLabels' => array_column(\App\Enums\Golongan::cases(), 'value'),
        ]);
    }

    public function showPegawai(Request $request, $year, $month)
    {
        $query = RekapBulananPegawai::forPeriode($year, $month);

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nama_lengkap', 'like', "%{$search}%")
                    ->orWhere('nip', 'like', "%{$search}%");
            });
        }

        $pegawais = $query->paginate(10)->withQueryString();

        $rekap = RekapStatistikBulanan::where('periode_tahun', $year)
            ->where('periode_bulan', $month)
            ->first();

        return Inertia::render('Kepegawaian/RekapBulanan/ShowPegawai', [
            'pegawais' => $pegawais,
            'rekap' => $rekap,
            'periode' => [
                'year' => (int) $year,
                'month' => (int) $month,
            ],
            'filters' => $request->only(['search']),
        ]);
    }

    public function generate(Request $request)
    {
        $request->validate([
            'year' => 'required|integer|min:2020|max:2030',
            'month' => 'required|integer|min:1|max:12',
        ]);

        $this->service->generate($request->year, $request->month, 'manual');

        return redirect()->back()->with('success', "Rekap Kepegawaian bulan {$request->month} tahun {$request->year} berhasil digenerate.");
    }

    public function export($year, $month)
    {
        return Excel::download(new RekapBulananExport($year, $month), "rekap-kepegawaian-{$year}-{$month}.xlsx");
    }

    public function searchPegawai(Request $request)
    {
        $search = $request->get('search');
        if (strlen($search) < 3)
            return response()->json([]);

        $pegawais = \App\Models\Pegawai::where('status', 'final')
            ->where('status_kedudukan', 'Aktif')
            ->where(function ($q) use ($search) {
                $q->where('nama_lengkap', 'like', "%{$search}%")
                    ->orWhere('nip', 'like', "%{$search}%");
            })
            ->limit(10)
            ->get(['id', 'nama_lengkap', 'nip']);

        return response()->json($pegawais);
    }

    public function storePegawai(Request $request, $year, $month)
    {
        $request->validate([
            'pegawai_id' => 'required|exists:pegawais,id',
        ]);

        $pegawai = \App\Models\Pegawai::with(['bezetting', 'latestKgb'])->findOrFail($request->pegawai_id);

        // Cek if already exists in this rekap (including trashed)
        $existingRecord = RekapBulananPegawai::withTrashed()
            ->where('pegawai_id', $pegawai->id)
            ->where('periode_tahun', $year)
            ->where('periode_bulan', $month)
            ->first();

        if ($existingRecord && !$existingRecord->trashed()) {
            return redirect()->back()->with('error', "Pegawai {$pegawai->nama_lengkap} sudah ada dalam rekap periode ini.");
        }

        $retirementDate = $pegawai->retirement_date;
        $snapshotData = [
            'pegawai_id' => $pegawai->id,
            'periode_tahun' => $year,
            'periode_bulan' => $month,
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
            'usia_per_periode' => $pegawai->tanggal_lahir ? $pegawai->tanggal_lahir->diffInYears(\Carbon\Carbon::create($year, $month, 1)->endOfMonth()) : null,
            'tmt_cpns' => $pegawai->tmt_cpns,
            'tmt_pns' => $pegawai->tmt_pns,
            'masa_kerja_tahun' => $pegawai->tmt_cpns ? $pegawai->tmt_cpns->diffInYears(\Carbon\Carbon::create($year, $month, 1)->endOfMonth()) : null,
            'bup' => $pegawai->bup,
            'proyeksi_pensiun' => $retirementDate,
            'bulan_pensiun_tersisa' => $retirementDate ? \Carbon\Carbon::create($year, $month, 1)->diffInMonths($retirementDate, false) : null,
            'gaji_pokok_terakhir' => $pegawai->latestKgb?->gaji_pokok_baru,
            'tmt_kgb_berikutnya' => $pegawai->next_kgb_date,
            'sumber_data' => 'manual',
            'status' => 'draft',
        ];

        if ($existingRecord && $existingRecord->trashed()) {
            $existingRecord->restore();
            $existingRecord->update($snapshotData);
        } else {
            RekapBulananPegawai::create($snapshotData);
        }

        $this->service->recalculateStatistik($year, $month, 'manual');

        return redirect()->back()->with('success', "Pegawai {$pegawai->nama_lengkap} berhasil ditambahkan ke rekap.");
    }

    public function updatePegawai(Request $request, $id)
    {
        $rekapPegawai = RekapBulananPegawai::findOrFail($id);

        $request->validate([
            'nama_lengkap' => 'required|string|max:255',
            'status_pegawai' => 'required|string',
            'pendidikan_terakhir' => 'required|string',
            'pangkat_golongan' => 'nullable|string',
            'unit_kerja' => 'nullable|string',
        ]);

        $rekapPegawai->update($request->all());

        $this->service->recalculateStatistik($rekapPegawai->periode_tahun, $rekapPegawai->periode_bulan, 'manual');

        return redirect()->back()->with('success', "Data snapshot pegawai {$rekapPegawai->nama_lengkap} berhasil diperbarui.");
    }

    public function destroyPegawai($id)
    {
        $rekapPegawai = RekapBulananPegawai::findOrFail($id);
        $year = $rekapPegawai->periode_tahun;
        $month = $rekapPegawai->periode_bulan;
        $name = $rekapPegawai->nama_lengkap;

        $rekapPegawai->delete();

        $this->service->recalculateStatistik($year, $month, 'manual');

        return redirect()->back()->with('success', "Pegawai {$name} berhasil dihapus dari rekap.");
    }

    public function destroy($id)
    {
        $rekap = RekapStatistikBulanan::findOrFail($id);
        $year = $rekap->periode_tahun;
        $month = $rekap->periode_bulan;

        // Delete all related pegawais
        RekapBulananPegawai::where('periode_tahun', $year)
            ->where('periode_bulan', $month)
            ->delete();

        $rekap->delete();

        return redirect()->route('rekap-bulanan.index')->with('success', "Rekap periode {$month}/{$year} berhasil dihapus.");
    }

    public function singleWorkflowAction(Request $request, $id, SingleWorkflowAction $action)
    {
        $request->validate([
            'model_type' => 'required|in:statistik,pegawai',
            'action' => ['required', Rule::enum(WorkflowAction::class)],
            'rejection_note' => 'nullable|string|max:255',
        ]);

        $model = $request->model_type === 'statistik'
            ? RekapStatistikBulanan::findOrFail($id)
            : RekapBulananPegawai::findOrFail($id);

        $workflowAction = WorkflowAction::from($request->action);

        if ($workflowAction === WorkflowAction::REJECT && !$request->filled('rejection_note')) {
            return redirect()->back()->with('error', 'Catatan penolakan wajib diisi.');
        }

        $extraData = [];
        if ($request->filled('rejection_note')) {
            $extraData['rejection_note'] = $request->rejection_note;
        }

        $success = $action->execute(
            model: $model,
            action: $workflowAction,
            user: auth()->user(),
            extraData: $extraData
        );

        if ($success) {
            $message = match ($workflowAction) {
                WorkflowAction::DELETE => 'dihapus',
                WorkflowAction::SUBMIT => 'diajukan untuk verifikasi',
                WorkflowAction::APPROVE => 'disetujui',
                WorkflowAction::REJECT => 'ditolak',
            };
            return redirect()->back()->with('success', "Data berhasil {$message}.");
        }

        return redirect()->back()->with('error', 'Gagal memproses data atau status tidak sesuai.');
    }

    public function bulkWorkflowAction(Request $request, BulkWorkflowAction $bulkAction)
    {
        $request->validate([
            'ids' => 'required|array',
            'model_type' => 'required|in:statistik,pegawai',
            'action' => ['required', Rule::enum(WorkflowAction::class)],
            'rejection_note' => 'nullable|string|max:255',
        ]);

        $modelClass = $request->model_type === 'statistik'
            ? RekapStatistikBulanan::class
            : RekapBulananPegawai::class;

        $workflowAction = WorkflowAction::from($request->action);

        if ($workflowAction === WorkflowAction::REJECT && !$request->filled('rejection_note')) {
            return redirect()->back()->with('error', 'Catatan penolakan wajib diisi.');
        }

        $extraData = [];
        if ($request->filled('rejection_note')) {
            $extraData['rejection_note'] = $request->rejection_note;
        }

        $count = $bulkAction->execute(
            model: $modelClass,
            action: $workflowAction,
            ids: $request->ids,
            user: auth()->user(),
            extraData: $extraData
        );

        $message = match ($workflowAction) {
            WorkflowAction::DELETE => 'dihapus',
            WorkflowAction::SUBMIT => 'diajukan',
            WorkflowAction::APPROVE => 'disetujui',
            WorkflowAction::REJECT => 'ditolak',
        };

        return redirect()->back()->with('success', "{$count} data berhasil {$message}.");
    }
}
