<?php

namespace App\Http\Controllers;

use App\Models\Pegawai;
use App\Models\Bezetting;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Actions\SingleWorkflowAction;
use App\Actions\BulkWorkflowAction;
use App\Enums\WorkflowAction;
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

        return Inertia::render('Kepegawaian/Demografi/Index', [
            'pegawais' => $pegawais,
            'filters' => [
                'search' => $request->search,
                'sort' => $sort,
                'dir' => $dir,
                'per_page' => $perPage,
            ]
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
            'status' => 'nullable|in:draft,waiting_kasi,waiting_cdk,final,rejected'
        ]);

        Pegawai::create($validated);

        return redirect()->route('demografi-pegawai.index')->with('success', 'Data Pegawai berhasil ditambahkan.');
    }

    public function edit(Pegawai $demografi_pegawai)
    {
        $bezettings = Bezetting::all();
        return Inertia::render('Kepegawaian/Demografi/Edit', [
            'pegawai' => $demografi_pegawai,
            'bezettings' => $bezettings,
            'statusPegawaiOptions' => StatusPegawai::options(),
            'statusPernikahanOptions' => StatusPernikahan::options(),
            'agamaOptions' => Agama::options(),
            'statusKedudukanOptions' => StatusKedudukan::options(),
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
            'status' => 'nullable|in:draft,waiting_kasi,waiting_cdk,final,rejected'
        ]);

        $demografi_pegawai->update($validated);

        return redirect()->route('demografi-pegawai.index')->with('success', 'Data Pegawai berhasil diperbarui.');
    }

    public function destroy(Pegawai $demografi_pegawai)
    {
        $demografi_pegawai->delete();
        return redirect()->route('demografi-pegawai.index')->with('success', 'Data Pegawai berhasil dihapus.');
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

    public function singleWorkflowAction(Request $request, Pegawai $demografi_pegawai, SingleWorkflowAction $action)
    {
        $request->validate([
            'action' => ['required', Rule::enum(WorkflowAction::class)],
            'rejection_note' => 'nullable|string|max:255',
        ]);

        $workflowAction = WorkflowAction::from($request->action);

        match ($workflowAction) {
            WorkflowAction::SUBMIT => $this->authorize('kepegawaian.edit'),
            WorkflowAction::APPROVE, WorkflowAction::REJECT => $this->authorize('kepegawaian.approve'),
            WorkflowAction::DELETE => $this->authorize('kepegawaian.delete'),
        };

        if ($workflowAction === WorkflowAction::REJECT && !$request->filled('rejection_note')) {
            return redirect()->back()->with('error', 'Catatan penolakan wajib diisi.');
        }

        $extraData = [];
        if ($request->filled('rejection_note')) {
            $extraData['rejection_note'] = $request->rejection_note;
        }

        $success = $action->execute(
            model: $demografi_pegawai,
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
            return redirect()->back()->with('success', "Data Pegawai berhasil {$message}.");
        }

        return redirect()->back()->with('error', 'Gagal memproses data atau status tidak sesuai.');
    }

    public function bulkWorkflowAction(Request $request, BulkWorkflowAction $action)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:pegawais,id',
            'action' => ['required', Rule::enum(WorkflowAction::class)],
            'rejection_note' => 'nullable|string|max:255',
        ]);

        $workflowAction = WorkflowAction::from($request->action);

        match ($workflowAction) {
            WorkflowAction::SUBMIT => $this->authorize('kepegawaian.edit'),
            WorkflowAction::APPROVE, WorkflowAction::REJECT => $this->authorize('kepegawaian.approve'),
            WorkflowAction::DELETE => $this->authorize('kepegawaian.delete'),
        };

        if ($workflowAction === WorkflowAction::REJECT && !$request->filled('rejection_note')) {
            return redirect()->back()->with('error', 'Catatan penolakan wajib diisi.');
        }

        $extraData = [];
        if ($request->filled('rejection_note')) {
            $extraData['rejection_note'] = $request->rejection_note;
        }

        $count = $action->execute(
            model: Pegawai::class,
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
