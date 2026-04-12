<?php

namespace App\Http\Controllers;

use App\Models\Pegawai;
use App\Models\Bezetting;
use App\Models\RekapStatistikBulanan;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Enums\StatusPegawai;
use App\Enums\StatusPernikahan;
use App\Enums\Agama;
use App\Enums\StatusKedudukan;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\PegawaiExport;
use App\Exports\PegawaiTemplateExport;
use App\Imports\PegawaiImport;
use App\Traits\HandlesImportFailures;
use App\Models\RiwayatKgb;
use Carbon\Carbon;

class DemografiPegawaiController extends Controller
{
    use HandlesImportFailures;
    public function __construct()
    {
        $this->middleware('permission:kepegawaian.view')->only(['index', 'show']);
        $this->middleware('permission:kepegawaian.create')->only(['create', 'store', 'template', 'import']);
        $this->middleware('permission:kepegawaian.edit')->only(['edit', 'update']);
        $this->middleware('permission:kepegawaian.delete')->only(['destroy']);
        $this->middleware('permission:kepegawaian.export')->only(['export']);
    }

    public function index(Request $request)
    {
        // ===== BAGIAN LAMA: Tabel Daftar Pegawai =====
        $query = Pegawai::with('bezetting', 'creator');

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nama_lengkap', 'like', "%{$search}%")
                    ->orWhere('nip', 'like', "%{$search}%");
            });
        }

        $sort = $request->get('sort', 'created_at');
        $dir = $request->get('dir', 'desc');
        $perPage = $request->get('per_page', 10);

        $allowedSorts = ['nama_lengkap', 'nip', 'pangkat_golongan', 'status_kedudukan', 'created_at', 'status'];
        if (in_array($sort, $allowedSorts)) {
            $query->orderBy($sort, $dir);
        } else {
            $query->latest();
        }

        $pegawais = $query->paginate($perPage)->withQueryString();

        // ===== BAGIAN BARU: Dashboard Hub Data =====

        // 1. KPI Cards — query live dari tabel pegawais
        $totalAktif   = Pegawai::where('status_kedudukan', 'Aktif')->count();
        $totalPns     = Pegawai::where('status_pegawai', 'PNS')->where('status_kedudukan', 'Aktif')->count();
        $totalPppk    = Pegawai::where('status_pegawai', 'PPPK')->where('status_kedudukan', 'Aktif')->count();
        $pensiunCount = Pegawai::pensiunDalam(90)->count();
        $kgbCount     = Pegawai::kgbJatuhPadaBulan()->count();
        $kpi = [
            'total_aktif'     => $totalAktif,
            'total_pns'       => $totalPns,
            'total_pppk'      => $totalPppk,
            'pensiun_90_hari' => $pensiunCount,
            'kgb_bulan_ini'   => $kgbCount,
        ];

        // 2. Rekap terakhir (untuk status chip + delta KPI)
        $rekapTerakhir = RekapStatistikBulanan::orderBy('periode_tahun', 'desc')
            ->orderBy('periode_bulan', 'desc')
            ->first([
                'id', 'periode_bulan', 'periode_tahun', 'status',
                'total_pegawai_aktif', 'total_pns', 'total_pppk',
                'kgb_jatuh_bulan_ini', 'total_pensiun_tahun_ini',
            ]);

        // 3. Tren chart 12 bulan
        $trenBulanan = RekapStatistikBulanan::tren12Bulan();

        // 4. Alert: 5 pegawai pensiun terdekat
        $alertPensiun = Pegawai::pensiunDalam(90)
            ->select('id', 'nama_lengkap', 'nip', 'tanggal_lahir', 'bup')
            ->limit(5)
            ->get()
            ->map(fn($p) => [
                'id'               => $p->id,
                'nama_lengkap'     => $p->nama_lengkap,
                'nip'              => $p->nip,
                'proyeksi_pensiun' => $p->retirement_date?->format('Y-m-d'),
            ]);

        // 5. Alert: 5 pegawai KGB bulan ini
        $alertKgb = Pegawai::kgbJatuhPadaBulan()
            ->with('latestKgb')
            ->select('id', 'nama_lengkap', 'nip')
            ->limit(5)
            ->get()
            ->map(fn($p) => [
                'id'                 => $p->id,
                'nama_lengkap'       => $p->nama_lengkap,
                'nip'                => $p->nip,
                'tmt_kgb_berikutnya' => $p->latestKgb?->tmt_kgb_berikutnya?->format('Y-m-d'),
            ]);

        // 6. Rekap pending (perlu tindakan) sesuai role
        $user = $request->user();
        $pendingStatuses = [];
        if ($user->hasRole('admin')) {
            $pendingStatuses = ['draft', 'waiting_kasi', 'waiting_cdk', 'rejected'];
        } else {
            if ($user->hasAnyRole(['pk', 'peh', 'pelaksana'])) {
                $pendingStatuses = array_merge($pendingStatuses, ['draft', 'rejected']);
            }
            if ($user->hasRole('kasi')) {
                $pendingStatuses[] = 'waiting_kasi';
            }
            if ($user->hasRole('kacdk')) {
                $pendingStatuses[] = 'waiting_cdk';
            }
        }

        $rekapPending = RekapStatistikBulanan::whereIn('status', $pendingStatuses)
            ->orderBy('periode_tahun', 'desc')
            ->orderBy('periode_bulan', 'desc')
            ->limit(3)
            ->get(['id', 'periode_bulan', 'periode_tahun', 'status', 'rejection_note']);

        // 7. Timeline rekap bulanan (horizontal strip)
        $timelineYear = $request->get('timeline_year');
        $availableYears = RekapStatistikBulanan::distinct()
            ->orderBy('periode_tahun', 'desc')
            ->pluck('periode_tahun')
            ->toArray();

        $rekapTimelineQuery = RekapStatistikBulanan::query();

        if ($timelineYear) {
            $rekapTimelineQuery->where('periode_tahun', $timelineYear)
                ->orderBy('periode_bulan', 'asc');
        } else {
            $rekapTimelineQuery->orderBy('periode_tahun', 'desc')
                ->orderBy('periode_bulan', 'desc')
                ->take(12);
        }

        $rekapTimeline = $rekapTimelineQuery->get(['id', 'periode_bulan', 'periode_tahun', 'status']);
        
        if (!$timelineYear) {
            $rekapTimeline = $rekapTimeline->reverse()->values();
        }

        return Inertia::render('Kepegawaian/Demografi/Index', [
            // Existing
            'pegawais' => $pegawais,
            'filters'  => [
                'search'        => $request->search,
                'sort'          => $sort,
                'dir'           => $dir,
                'per_page'      => $perPage,
                'timeline_year' => $timelineYear ? (int)$timelineYear : null,
            ],
            // NEW — dashboard props
            'kpi'             => $kpi,
            'rekap_terakhir'  => $rekapTerakhir,
            'tren_bulanan'    => $trenBulanan,
            'alert_pensiun'   => $alertPensiun,
            'alert_kgb'       => $alertKgb,
            'rekap_pending'   => $rekapPending,
            'rekap_timeline'  => $rekapTimeline,
            'available_years' => $availableYears,
        ]);
    }

    public function create()
    {
        $bezettings = Bezetting::all();
        return Inertia::render('Kepegawaian/Demografi/Create', [
            'bezettings' => $bezettings,
            'statusPegawaiOptions' => StatusPegawai::options(),
            'statusPernikahanOptions' => StatusPernikahan::options(),
            'agamaOptions' => Agama::options(),
            'statusKedudukanOptions' => StatusKedudukan::options(),
            'pendidikanOptions' => \App\Enums\Pendidikan::options(),
            'golonganOptions' => \App\Enums\Golongan::options(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nip' => 'required|string|unique:pegawais,nip',
            'nama_lengkap' => 'required|string|max:255',
            'tempat_lahir' => 'required|string|max:255',
            'tanggal_lahir' => 'required|date',
            'jenis_kelamin' => 'required|in:L,P',
            'agama' => ['required', Rule::enum(Agama::class)],
            'pendidikan_terakhir' => 'required|string|max:255',
            'status_pegawai' => ['required', Rule::enum(StatusPegawai::class)],
            'nik' => 'nullable|string|max:255|unique:pegawais,nik',
            'status_pernikahan' => ['nullable', Rule::enum(StatusPernikahan::class)],
            'alamat' => 'nullable|string',
            'tmt_pns' => 'nullable|date',
            'unit_kerja' => 'nullable|string|max:255',
            'skpd' => 'nullable|string|max:255',
            'bezetting_id' => 'nullable|exists:bezettings,id',
            'pangkat_golongan' => 'nullable|string|max:255',
            'tmt_cpns' => 'nullable|date',
            'bup' => 'required|integer',
            'status_kedudukan' => ['required', Rule::enum(StatusKedudukan::class)],
            'kgb_no_sk' => 'nullable|string|max:255',
            'kgb_tanggal_sk' => 'nullable|date',
            'kgb_tmt' => 'nullable|date',
            'kgb_gaji' => 'nullable|numeric|min:0',
        ]);

        $validated['status'] = 'final';

        $pegawai = Pegawai::create($validated);

        if ($request->filled('kgb_no_sk')) {
            RiwayatKgb::create([
                'pegawai_id' => $pegawai->id,
                'no_sk' => $request->kgb_no_sk,
                'tanggal_sk' => $request->kgb_tanggal_sk,
                'tmt_kgb' => $request->kgb_tmt,
                'gaji_pokok_baru' => $request->kgb_gaji,
                'tmt_kgb_berikutnya' => Carbon::parse($request->kgb_tmt)->addYears(2),
                'status' => 'final',
            ]);
        }

        return redirect()->route('demografi-pegawai.index')->with('success', 'Pegawai berhasil ditambahkan.');
    }

    public function edit(Pegawai $demografi_pegawai)
    {
        $demografi_pegawai->load(['riwayatKgb' => function ($q) {
            $q->orderBy('tmt_kgb', 'desc');
        }]);
        $bezettings = Bezetting::all();
        return Inertia::render('Kepegawaian/Demografi/Edit', [
            'pegawai' => $demografi_pegawai,
            'bezettings' => $bezettings,
            'statusPegawaiOptions' => StatusPegawai::options(),
            'statusPernikahanOptions' => StatusPernikahan::options(),
            'agamaOptions' => Agama::options(),
            'statusKedudukanOptions' => StatusKedudukan::options(),
            'pendidikanOptions' => \App\Enums\Pendidikan::options(),
            'golonganOptions' => \App\Enums\Golongan::options(),
        ]);
    }

    public function update(Request $request, Pegawai $demografi_pegawai)
    {
        $validated = $request->validate([
            'nip' => 'required|string|unique:pegawais,nip,' . $demografi_pegawai->id,
            'nama_lengkap' => 'required|string|max:255',
            'tempat_lahir' => 'required|string|max:255',
            'tanggal_lahir' => 'required|date',
            'jenis_kelamin' => 'required|in:L,P',
            'agama' => ['required', Rule::enum(Agama::class)],
            'pendidikan_terakhir' => 'required|string|max:255',
            'status_pegawai' => ['required', Rule::enum(StatusPegawai::class)],
            'nik' => 'nullable|string|max:255|unique:pegawais,nik,' . $demografi_pegawai->id,
            'status_pernikahan' => ['nullable', Rule::enum(StatusPernikahan::class)],
            'alamat' => 'nullable|string',
            'tmt_pns' => 'nullable|date',
            'unit_kerja' => 'nullable|string|max:255',
            'skpd' => 'nullable|string|max:255',
            'bezetting_id' => 'nullable|exists:bezettings,id',
            'pangkat_golongan' => 'nullable|string|max:255',
            'tmt_cpns' => 'nullable|date',
            'bup' => 'required|integer',
            'status_kedudukan' => ['required', Rule::enum(StatusKedudukan::class)],
        ]);

        $validated['status'] = 'final';
        $demografi_pegawai->update($validated);

        return redirect()->route('demografi-pegawai.index')->with('success', 'Data Pegawai berhasil diperbarui.');
    }

    public function destroy(Pegawai $demografi_pegawai)
    {
        $demografi_pegawai->delete();
        return redirect()->route('demografi-pegawai.index')->with('success', 'Data Pegawai berhasil dihapus.');
    }

    public function bulkDelete(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:pegawais,id',
        ]);

        $this->authorize('kepegawaian.delete');

        Pegawai::whereIn('id', $request->ids)->delete();

        return redirect()->back()->with('success', count($request->ids) . ' data berhasil dihapus.');
    }

    public function export()
    {
        return Excel::download(new PegawaiExport, 'data-demografi-pegawai-' . date('Y-m-d') . '.xlsx');
    }

    public function template()
    {
        return Excel::download(new PegawaiTemplateExport, 'template-import-pegawai.xlsx');
    }

    public function import(Request $request)
    {
        $request->validate(['file' => 'required|mimes:xlsx,csv,xls|max:5120']);

        $import = new PegawaiImport();

        try {
            Excel::import($import, $request->file('file'));
        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
            return redirect()->back()->with('import_errors', $this->mapImportFailures($e->failures()));
        }

        if ($import->failures()->isNotEmpty()) {
            return redirect()->back()->with('import_errors', $this->mapImportFailures($import->failures()));
        }

        return redirect()->back()->with('success', 'Data pegawai berhasil diimport.');
    }

    public function storeKgb(Request $request, Pegawai $pegawai)
    {
        $validated = $request->validate([
            'no_sk' => 'required|string|max:255',
            'tanggal_sk' => 'required|date',
            'tmt_kgb' => 'required|date',
            'gaji_pokok_baru' => 'required|numeric|min:0',
        ]);

        $validated['pegawai_id'] = $pegawai->id;
        $validated['tmt_kgb_berikutnya'] = Carbon::parse($validated['tmt_kgb'])->addYears(2);
        $validated['status'] = 'final';

        RiwayatKgb::create($validated);

        return redirect()->back()->with('success', 'Riwayat KGB berhasil ditambahkan.');
    }

    public function updateKgb(Request $request, RiwayatKgb $riwayat_kgb)
    {
        $validated = $request->validate([
            'no_sk' => 'required|string|max:255',
            'tanggal_sk' => 'required|date',
            'tmt_kgb' => 'required|date',
            'gaji_pokok_baru' => 'required|numeric|min:0',
        ]);

        $validated['tmt_kgb_berikutnya'] = Carbon::parse($validated['tmt_kgb'])->addYears(2);
        $validated['status'] = 'final';

        $riwayat_kgb->update($validated);

        return redirect()->back()->with('success', 'Riwayat KGB berhasil diperbarui.');
    }

    public function destroyKgb(RiwayatKgb $riwayat_kgb)
    {
        $riwayat_kgb->delete();
        return redirect()->back()->with('success', 'Riwayat KGB berhasil dihapus.');
    }

}
