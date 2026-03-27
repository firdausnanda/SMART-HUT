<?php

namespace App\Http\Controllers;

use App\Models\Bezetting;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Actions\SingleWorkflowAction;
use App\Actions\BulkWorkflowAction;
use App\Enums\WorkflowAction;
use Illuminate\Validation\Rule;

class BezettingJabatanController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:kepegawaian.view')->only(['index', 'show']);
        $this->middleware('permission:kepegawaian.create')->only(['store']); // Bezetting uses store for creating from modal
        $this->middleware('permission:kepegawaian.edit')->only(['update']);
        $this->middleware('permission:kepegawaian.delete')->only(['destroy']);
    }
    public function index(Request $request)
    {
        $query = Bezetting::with('creator')->withCount(['pegawais as realitas' => function ($q) {
            $q->where('status', 'final');
        }]);

        if ($request->has('search')) {
            $search = $request->search;
            $query->where('nama_jabatan', 'like', "%{$search}%");
        }

        $sort = $request->get('sort', 'nama_jabatan');
        $dir = $request->get('dir', 'asc');
        $perPage = $request->get('per_page', 10);

        $allowedSorts = ['nama_jabatan', 'kebutuhan', 'realitas', 'status'];
        if (in_array($sort, $allowedSorts)) {
            $query->orderBy($sort, $dir);
        } else {
            $query->orderBy('nama_jabatan', 'asc');
        }

        $bezettings = $query->paginate($perPage)->withQueryString();

        return Inertia::render('Kepegawaian/Bezetting', [
            'bezettings' => $bezettings,
            'filters' => [
                'search' => $request->search,
                'sort' => $sort,
                'dir' => $dir,
                'per_page' => $perPage,
            ]
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama_jabatan' => 'required|string|max:255|unique:bezettings,nama_jabatan',
            'kebutuhan' => 'required|integer|min:0',
        ]);

        Bezetting::create($validated);

        return redirect()->back()->with('success', 'Jabatan berhasil ditambahkan.');
    }

    public function update(Request $request, Bezetting $bezetting_jabatan)
    {
        $validated = $request->validate([
            'nama_jabatan' => 'required|string|max:255|unique:bezettings,nama_jabatan,' . $bezetting_jabatan->id,
            'kebutuhan' => 'required|integer|min:0',
        ]);

        $bezetting_jabatan->update($validated);

        return redirect()->back()->with('success', 'Jabatan berhasil diperbarui.');
    }

    public function destroy(Bezetting $bezetting_jabatan)
    {
        if ($bezetting_jabatan->pegawais()->exists()) {
            return redirect()->back()->with('error', 'Gagal menghapus jabatan. Masih ada pegawai yang terhubung dengan jabatan ini.');
        }

        $bezetting_jabatan->delete();

        return redirect()->back()->with('success', 'Jabatan berhasil dihapus.');
    }

    public function singleWorkflowAction(Request $request, Bezetting $bezetting_jabatan, SingleWorkflowAction $action)
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
            model: $bezetting_jabatan,
            action: $workflowAction,
            user: /** @var \App\Models\User */ $request->user(),
            extraData: $extraData
        );

        if ($success) {
            $message = match ($workflowAction) {
                WorkflowAction::DELETE => 'dihapus',
                WorkflowAction::SUBMIT => 'diajukan untuk verifikasi',
                WorkflowAction::APPROVE => 'disetujui',
                WorkflowAction::REJECT => 'ditolak',
                default => 'diproses'
            };
            return redirect()->back()->with('success', "Data Jabatan berhasil {$message}.");
        }

        return redirect()->back()->with('error', 'Gagal memproses data atau status tidak sesuai.');
    }

    public function bulkWorkflowAction(Request $request, BulkWorkflowAction $action)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:bezettings,id',
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
            model: Bezetting::class,
            action: $workflowAction,
            ids: $request->ids,
            user: /** @var \App\Models\User */ $request->user(),
            extraData: $extraData
        );

        $message = match ($workflowAction) {
            WorkflowAction::DELETE => 'dihapus',
            WorkflowAction::SUBMIT => 'diajukan',
            WorkflowAction::APPROVE => 'disetujui',
            WorkflowAction::REJECT => 'ditolak',
            default => 'diproses'
        };

        return redirect()->back()->with('success', "{$count} data berhasil {$message}.");
    }
}
