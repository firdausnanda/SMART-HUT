<?php

namespace App\Http\Controllers;

use App\Models\Skps;
use App\Models\SkemaPerhutananSosial;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Redirect;
use App\Actions\BulkWorkflowAction;
use App\Actions\SingleWorkflowAction;
use App\Enums\WorkflowAction;
use App\Exports\SkpsExport;
use App\Exports\SkpsTemplateExport;
use Illuminate\Validation\Rule;
use App\Models\ImportBatch;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\StagingImport;
use App\Services\Imports\SkpsImportValidator;
use Maatwebsite\Excel\Validators\ValidationException;
use App\Imports\SkpsImport;
use Illuminate\Support\Facades\DB;

class SkpsController extends Controller
{
  use \App\Traits\HandlesImportFailures;
  /**
   * Display a listing of the resource.
   */

  public function index(Request $request)
  {
    $sortField = $request->query('sort', 'created_at');
    $sortDirection = $request->query('direction', 'desc');

    $datas = Skps::query()
      ->select([
        'skps.id',
        'skps.province_id',
        'skps.regency_id',
        'skps.district_id',
        'skps.id_skema_perhutanan_sosial',
        'skps.nama_kelompok',
        'skps.potential',
        'skps.ps_area',
        'skps.number_of_kk',
        'skps.status',
        'skps.rejection_note',
        'skps.created_at',
        'skps.created_by',
      ])
      ->with([
        'creator:id,name',
        'regency:id,name',
        'district:id,name',
        'skema:id,name'
      ])
      ->when($request->search, function ($query, $search) {
        $query->where(function ($q) use ($search) {
          $q->whereHas('regency', fn($q2) => $q2->where('name', 'like', "{$search}%"))
            ->orWhereHas('district', fn($q2) => $q2->where('name', 'like', "{$search}%"))
            ->orWhereHas('skema', fn($q2) => $q2->where('name', 'like', "{$search}%"))
            ->orWhere('nama_kelompok', 'like', "{$search}%");
        });
      })
      ->when($sortField === 'location', function ($q) use ($sortDirection) {
        $q->leftJoin('m_districts', 'skps.district_id', '=', 'm_districts.id')
          ->orderBy('m_districts.name', $sortDirection);
      })
      ->when($sortField === 'skema', function ($q) use ($sortDirection) {
        $q->leftJoin('m_skema_perhutanan_sosial', 'skps.id_skema_perhutanan_sosial', '=', 'm_skema_perhutanan_sosial.id')
          ->orderBy('m_skema_perhutanan_sosial.name', $sortDirection);
      })
      ->when(!in_array($sortField, ['location', 'skema']), function ($q) use ($sortField, $sortDirection) {
        $user = auth()->user();

        if ($sortField === 'created_at' && $sortDirection === 'desc') {
          if ($user->hasRole('kacdk')) {
            $q->orderByRaw("CASE WHEN status = 'waiting_cdk' THEN 0 ELSE 1 END");
          } elseif ($user->hasRole('kasi')) {
            $q->orderByRaw("CASE WHEN status = 'waiting_kasi' THEN 0 ELSE 1 END");
          }
        }

        match ($sortField) {
          'group_name' => $q->orderBy('nama_kelompok', $sortDirection),
          'area' => $q->orderBy('ps_area', $sortDirection),
          'potential' => $q->orderBy('potential', $sortDirection),
          'kk_count' => $q->orderBy('number_of_kk', $sortDirection),
          'status' => $q->orderBy('status', $sortDirection),
          default => $q->orderBy('created_at', 'desc'),
        };
      })
      ->paginate($request->integer('per_page', 10))
      ->withQueryString();

    // Stats with caching
    $stats = cache()->remember('skps-stats', 300, function () {
      return SkemaPerhutananSosial::leftJoin('skps', function ($join) {
        $join->on('m_skema_perhutanan_sosial.id', '=', 'skps.id_skema_perhutanan_sosial')
          ->where('skps.status', 'final');
      })
        ->selectRaw('m_skema_perhutanan_sosial.name, count(skps.id) as total')
        ->groupBy('m_skema_perhutanan_sosial.id', 'm_skema_perhutanan_sosial.name')
        ->get();
    });

    return Inertia::render('Skps/Index', [
      'datas' => $datas,
      'stats' => $stats,
      'filters' => [
        'search' => $request->search,
        'sort' => $sortField,
        'direction' => $sortDirection,
        'per_page' => (int) $request->query('per_page', 10),
      ],
    ]);
  }

  /**
   * Show the form for creating a new resource.
   */
  public function create()
  {
    return Inertia::render('Skps/Create', [
      'skemas' => SkemaPerhutananSosial::all(),
    ]);
  }

  /**
   * Store a newly created resource in storage.
   */
  public function store(Request $request)
  {
    $validated = $request->validate([
      'province_id' => 'required|exists:m_provinces,id',
      'regency_id' => 'required|exists:m_regencies,id',
      'district_id' => 'required|exists:m_districts,id',
      'id_skema_perhutanan_sosial' => 'required|exists:m_skema_perhutanan_sosial,id',
      'nama_kelompok' => 'required|string',
      'potential' => 'required|string',
      'ps_area' => 'required|string',
      'number_of_kk' => 'required|string',
    ]);

    Skps::create($validated);

    return Redirect::route('skps.index')->with('success', 'Data Perkembangan SK PS berhasil ditambahkan.');
  }

  /**
   * Show the form for editing the specified resource.
   */
  public function edit(Skps $skp)
  {
    return Inertia::render('Skps/Edit', [
      'data' => $skp->load(['regency', 'district', 'skema']),
      'skemas' => SkemaPerhutananSosial::all(),
    ]);
  }

  /**
   * Update the specified resource in storage.
   */
  public function update(Request $request, Skps $skp)
  {
    $validated = $request->validate([
      'province_id' => 'required|exists:m_provinces,id',
      'regency_id' => 'required|exists:m_regencies,id',
      'district_id' => 'required|exists:m_districts,id',
      'id_skema_perhutanan_sosial' => 'required|exists:m_skema_perhutanan_sosial,id',
      'nama_kelompok' => 'required|string',
      'potential' => 'required|string',
      'ps_area' => 'required|string',
      'number_of_kk' => 'required|string',
    ]);

    $skp->update($validated);

    return Redirect::route('skps.index')->with('success', 'Data Perkembangan SK PS berhasil diperbarui.');
  }

  /**
   * Remove the specified resource from storage.
   */
  public function destroy(Skps $skp)
  {
    $skp->delete();

    return Redirect::route('skps.index')->with('success', 'Data berhasil dihapus.');
  }

  /**
   * Handle single workflow action.
   */
  public function singleWorkflowAction(Request $request, Skps $skp, SingleWorkflowAction $action)
  {
    $request->validate([
      'action' => ['required', Rule::enum(WorkflowAction::class)],
      'rejection_note' => 'nullable|string|max:255',
    ]);

    $workflowAction = WorkflowAction::from($request->action);

    match ($workflowAction) {
      WorkflowAction::SUBMIT => $this->authorize('skps.edit'),
      WorkflowAction::APPROVE, WorkflowAction::REJECT => $this->authorize('skps.approve'),
      WorkflowAction::DELETE => $this->authorize('skps.delete'),
    };

    if ($workflowAction === WorkflowAction::REJECT && !$request->filled('rejection_note')) {
      return redirect()->back()->with('error', 'Catatan penolakan wajib diisi.');
    }

    $extraData = [];
    if ($request->filled('rejection_note')) {
      $extraData['rejection_note'] = $request->rejection_note;
    }

    $success = $action->execute(
      model: $skp,
      action: $workflowAction,
      user: auth()->user(),
      extraData: $extraData
    );

    if ($success) {
      cache()->forget('skps-stats');

      $message = match ($workflowAction) {
        WorkflowAction::DELETE => 'dihapus',
        WorkflowAction::SUBMIT => 'diajukan untuk verifikasi',
        WorkflowAction::APPROVE => 'disetujui',
        WorkflowAction::REJECT => 'ditolak',
      };
      return redirect()->back()->with('success', "Laporan berhasil {$message}.");
    }

    return redirect()->back()->with('error', 'Gagal memproses laporan atau status tidak sesuai.');
  }

  public function export()
  {
    return Excel::download(new SkpsExport, 'perkembangan-skps-' . date('Y-m-d') . '.xlsx');
  }

  public function template()
  {
    return Excel::download(new SkpsTemplateExport, 'template_import_skps.xlsx');
  }

  public function previewImport(Request $request)
  {
      $this->authorize('skps.import');
      $request->validate(['file' => 'required|mimes:xlsx,csv,xls']);
      
      $batch = ImportBatch::create([
          'user_id' => Auth::id(),
          'module_name' => 'skps',
          'filename' => $request->file('file')->getClientOriginalName(),
          'status' => 'pending',
      ]);

      Excel::import(
          new StagingImport($batch->id, new SkpsImportValidator()), 
          $request->file('file')
      );

      return redirect()->route('skps.show-preview', $batch->id);
  }

  public function showPreview(ImportBatch $batch)
  {
      if ($batch->module_name !== 'skps') abort(404);
      $this->authorize('skps.import');

      $rows = $batch->stagingRows()->paginate(50);
      
      return Inertia::render('Skps/ImportPreview', [
          'batch' => $batch,
          'rows' => $rows
      ]);
  }

  public function commitImport(ImportBatch $batch)
  {
      if ($batch->module_name !== 'skps' || $batch->status !== 'pending') abort(400);
      $this->authorize('skps.import');
      
      $batch->update(['status' => 'processing']);
      
      $validRows = $batch->stagingRows()->where('status', 'valid')->get();
      $importedCount = 0;

      foreach ($validRows as $stagingRow) {
          $row = $stagingRow->data_payload;
          
          $regency = DB::table('m_regencies')
              ->whereRaw('LOWER(name) LIKE ?', ['%' . strtolower(trim($row['nama_kabupatenkota'])) . '%'])
              ->first();

          $district = DB::table('m_districts')
              ->where('regency_id', $regency->id)
              ->whereRaw('LOWER(name) LIKE ?', ['%' . strtolower(trim($row['nama_kecamatan'])) . '%'])
              ->first();

          $skema = DB::table('m_skema_perhutanan_sosial')
              ->whereRaw('LOWER(name) LIKE ?', ['%' . strtolower(trim($row['nama_skema_perhutanan_sosial'])) . '%'])
              ->first();

          Skps::create([
              'province_id' => $regency->province_id,
              'regency_id' => $regency->id,
              'district_id' => $district->id,
              'id_skema_perhutanan_sosial' => $skema->id,
              'nama_kelompok' => $row['nama_kelompok'],
              'potential' => $row['potensi'] ?? $row['potensi_ha'] ?? null,
              'ps_area' => $row['luas_ps_ha'],
              'number_of_kk' => $row['jumlah_kk'],
              'status' => 'draft',
              'created_by' => Auth::id(),
          ]);
          
          $importedCount++;
      }

      $batch->update(['status' => 'completed']);
      cache()->forget('skps-stats');
      
      return redirect()->route('skps.index')->with('success', "Berhasil mengimport {$importedCount} data SK PS yang valid.");
  }

  public function import(Request $request)
  {
    $request->validate(['file' => 'required|mimes:xlsx,csv,xls']);

    $import = new SkpsImport();

    try {
      Excel::import($import, $request->file('file'));
    } catch (ValidationException $e) {
      return redirect()->back()->with('import_errors', $this->mapImportFailures($e->failures()));
    }

    if ($import->failures()->isNotEmpty()) {
      return redirect()->back()->with('import_errors', $this->mapImportFailures($import->failures()));
    }

    return redirect()->back()->with('success', 'Data berhasil diimport.');
  }

  public function bulkWorkflowAction(Request $request, BulkWorkflowAction $action)
  {
    $request->validate([
      'ids' => 'required|array',
      'ids.*' => 'exists:skps,id',
      'action' => 'required|string',
      'rejection_note' => 'nullable|string|max:255',
    ]);

    $workflowAction = WorkflowAction::from($request->action);

    match ($workflowAction) {
      WorkflowAction::SUBMIT => $this->authorize('skps.edit'),
      WorkflowAction::APPROVE, WorkflowAction::REJECT => $this->authorize('skps.approve'),
      WorkflowAction::DELETE => $this->authorize('skps.delete'),
    };

    if ($workflowAction === WorkflowAction::REJECT && !$request->filled('rejection_note')) {
      return redirect()->back()->with('error', 'Catatan penolakan wajib diisi.');
    }

    $extraData = [];
    if ($request->filled('rejection_note')) {
      $extraData['rejection_note'] = $request->rejection_note;
    }

    $count = $action->execute(
      model: Skps::class,
      action: $workflowAction,
      ids: $request->ids,
      user: auth()->user(),
      extraData: $extraData
    );

    if ($count > 0) {
      return redirect()->back()->with('success', 'Aksi berhasil dilakukan pada ' . $count . ' data.');
    }

    $message = match ($workflowAction) {
      WorkflowAction::DELETE => 'dihapus',
      WorkflowAction::SUBMIT => 'diajukan',
      WorkflowAction::APPROVE => 'disetujui',
      WorkflowAction::REJECT => 'ditolak',
    };

    return redirect()->back()->with('success', "{$count} data berhasil {$message}.");
  }
}

