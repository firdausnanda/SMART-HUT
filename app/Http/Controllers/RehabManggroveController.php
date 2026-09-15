<?php

namespace App\Http\Controllers;

use App\Models\RehabManggrove;
use App\Actions\SingleWorkflowAction;
use App\Actions\BulkWorkflowAction;
use App\Enums\WorkflowAction;
use App\Exports\RehabManggroveExport;
use App\Exports\RehabManggroveTemplateExport;
use App\Imports\RehabManggroveImport;
use App\Imports\StagingImport;
use App\Models\ImportBatch;
use Illuminate\Validation\Rule;
use App\Models\Regencies;
use App\Models\SumberDana;
use App\Services\Imports\RehabManggroveImportValidator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Validators\ValidationException;

class RehabManggroveController extends Controller
{
  use \App\Traits\HandlesImportFailures;

  public function index(Request $request)
  {
    $defaultYear = now()->year;
    $selectedYear = $request->integer('year', $defaultYear);

    $sortField = $request->query('sort', 'created_at');
    $sortDirection = $request->query('direction', 'desc');

    $datas = RehabManggrove::query()
      ->select([
        'rehab_manggrove.id',
        'rehab_manggrove.year',
        'rehab_manggrove.month',
        'rehab_manggrove.regency_id',
        'rehab_manggrove.district_id',
        'rehab_manggrove.village_id',
        'rehab_manggrove.fund_source',
        'rehab_manggrove.target_annual',
        'rehab_manggrove.realization',
        'rehab_manggrove.status',
        'rehab_manggrove.rejection_note',
        'rehab_manggrove.created_at',
        'rehab_manggrove.created_by',
      ])
      ->with([
        'creator:id,name',
        'regency_rel:id,name',
        'district_rel:id,name',
        'village_rel:id,name',
      ])
      ->where('year', $selectedYear)

      ->when($request->search, function ($q, $search) {
        $q->where(function ($qq) use ($search) {
          $qq->where('fund_source', 'like', "{$search}%")
            ->orWhereHas('village_rel', fn($q) => $q->where('name', 'like', "{$search}%"))
            ->orWhereHas('district_rel', fn($q) => $q->where('name', 'like', "{$search}%"))
            ->orWhereHas('regency_rel', fn($q) => $q->where('name', 'like', "{$search}%"));
        });
      })

      ->when($sortField === 'location', function ($q) use ($sortDirection) {
        $q->leftJoin('m_villages', 'rehab_manggrove.village_id', '=', 'm_villages.id')
          ->orderBy('m_villages.name', $sortDirection);
      })

      ->when($sortField !== 'location', function ($q) use ($sortField, $sortDirection) {
        $user = auth()->user();

        if ($sortField === 'created_at' && $sortDirection === 'desc') {
          if ($user->hasRole('kacdk')) {
            $q->orderByRaw("CASE WHEN status = 'waiting_cdk' THEN 0 ELSE 1 END");
          } elseif ($user->hasRole('kasi')) {
            $q->orderByRaw("CASE WHEN status = 'waiting_kasi' THEN 0 ELSE 1 END");
          }
        }

        match ($sortField) {
          'year' => $q->orderBy('year', $sortDirection),
          'month' => $q->orderBy('month', $sortDirection),
          'realization' => $q->orderBy('realization', $sortDirection),
          'fund_source' => $q->orderBy('fund_source', $sortDirection),
          'status' => $q->orderBy('status', $sortDirection),
          default => $q->orderBy('created_at', 'desc'),
        };
      })

      ->paginate($request->integer('per_page', 10))
      ->withQueryString();

    $stats = cache()->remember(
      "rehab-manggrove-stats-{$selectedYear}",
      300,
      fn() => [
        'total_target' => RehabManggrove::where('year', $selectedYear)->where('status', 'final')->sum('target_annual'),
        'total_realization' => RehabManggrove::where('year', $selectedYear)->where('status', 'final')->sum('realization'),
        'total_count' => RehabManggrove::where('year', $selectedYear)->where('status', 'final')->count(),
      ]
    );

    $availableYears = cache()->remember('rehab-manggrove-years', 3600, function () {
      $dbYears = RehabManggrove::distinct()->pluck('year')->toArray();
      $fixedYears = range(now()->year, 2021);
      $years = array_unique(array_merge($dbYears, $fixedYears));
      rsort($years);
      return $years;
    });

    $sumberDana = cache()->remember('sumber-dana', 3600, fn() => SumberDana::select('id', 'name')->get());

    return Inertia::render('RehabManggrove/Index', [
      'datas' => $datas,
      'stats' => $stats,
      'filters' => [
        'year' => (int) $selectedYear,
        'search' => $request->search,
        'sort' => $sortField,
        'direction' => $sortDirection,
        'per_page' => (int) $request->query('per_page', 10),
      ],
      'availableYears' => $availableYears,
      'sumberDana' => $sumberDana
    ]);
  }

  /**
   * Show the form for creating a new resource.
   */
  public function create()
  {
    return Inertia::render('RehabManggrove/Create', [
      'sumberDana' => SumberDana::all()
    ]);
  }

  /**
   * Store a newly created resource in storage.
   */
  public function store(Request $request)
  {
    $validated = $request->validate([
      'year' => 'required|integer',
      'month' => 'required|integer|min:1|max:12',
      'province_id' => 'nullable|exists:m_provinces,id',
      'regency_id' => 'nullable|exists:m_regencies,id',
      'district_id' => 'nullable|exists:m_districts,id',
      'village_id' => 'nullable|exists:m_villages,id',
      'target_annual' => 'required|numeric',
      'realization' => 'required|numeric',
      'fund_source' => 'required|string',
      'coordinates' => 'nullable|string',
    ]);

    RehabManggrove::create($validated);

    return redirect()->route('rehab-manggrove.index')->with('success', 'Data Created Successfully');
  }

  /**
   * Show the form for editing the specified resource.
   */
  public function edit(RehabManggrove $rehabManggrove)
  {
    return Inertia::render('RehabManggrove/Edit', [
      'data' => $rehabManggrove->load(['regency_rel', 'district_rel', 'village_rel']),
      'sumberDana' => SumberDana::all()
    ]);
  }

  /**
   * Update the specified resource in storage.
   */
  public function update(Request $request, RehabManggrove $rehabManggrove)
  {
    $validated = $request->validate([
      'year' => 'required|integer',
      'month' => 'required|integer|min:1|max:12',
      'province_id' => 'nullable|exists:m_provinces,id',
      'regency_id' => 'nullable|exists:m_regencies,id',
      'district_id' => 'nullable|exists:m_districts,id',
      'village_id' => 'nullable|exists:m_villages,id',
      'target_annual' => 'required|numeric',
      'realization' => 'required|numeric',
      'fund_source' => 'required|string',
      'coordinates' => 'nullable|string',
    ]);

    $rehabManggrove->update($validated);

    return redirect()->route('rehab-manggrove.index')->with('success', 'Data Updated Successfully');
  }

  /**
   * Remove the specified resource from storage.
   */
  public function destroy(RehabManggrove $rehabManggrove)
  {
    $rehabManggrove->delete();

    return redirect()->route('rehab-manggrove.index')->with('success', 'Data Deleted Successfully');
  }

  /**
   * Single workflow action.
   */
  public function singleWorkflowAction(Request $request, RehabManggrove $rehabManggrove, SingleWorkflowAction $action)
  {
    $request->validate([
      'action' => ['required', Rule::enum(WorkflowAction::class)],
      'rejection_note' => 'nullable|string|max:255',
    ]);

    $workflowAction = WorkflowAction::from($request->action);

    match ($workflowAction) {
      WorkflowAction::SUBMIT => $this->authorize('rehab-manggrove.edit'),
      WorkflowAction::APPROVE, WorkflowAction::REJECT => $this->authorize('rehab-manggrove.approve'),
      WorkflowAction::DELETE => $this->authorize('rehab-manggrove.delete'),
    };

    if ($workflowAction === WorkflowAction::REJECT && !$request->filled('rejection_note')) {
      return redirect()->back()->with('error', 'Catatan penolakan wajib diisi.');
    }

    $extraData = [];
    if ($request->filled('rejection_note')) {
      $extraData['rejection_note'] = $request->rejection_note;
    }

    $success = $action->execute(
      model: $rehabManggrove,
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
      return redirect()->back()->with('success', "Laporan berhasil {$message}.");
    }

    return redirect()->back()->with('error', 'Gagal memproses laporan atau status tidak sesuai.');
  }

  public function export(Request $request)
  {
    $year = $request->query('year');
    return Excel::download(new RehabManggroveExport($year), 'rehab-manggrove-' . date('Y-m-d') . '.xlsx');
  }

  public function template()
  {
    return Excel::download(new RehabManggroveTemplateExport, 'template_import_rehab_manggrove.xlsx');
  }

  public function previewImport(Request $request)
  {
      $request->validate(['file' => 'required|mimes:xlsx,csv,xls']);
      
      $batch = ImportBatch::create([
          'user_id' => Auth::id(),
          'module_name' => 'rehab-manggrove',
          'filename' => $request->file('file')->getClientOriginalName(),
          'status' => 'pending',
      ]);

      Excel::import(
          new StagingImport($batch->id, new RehabManggroveImportValidator()), 
          $request->file('file')
      );

      return redirect()->route('rehab-manggrove.show-preview', $batch->id);
  }

  public function showPreview(ImportBatch $batch)
  {
      if ($batch->module_name !== 'rehab-manggrove') abort(404);

      $rows = $batch->stagingRows()->paginate(50);
      
      return Inertia::render('RehabManggrove/ImportPreview', [
          'batch' => $batch,
          'rows' => $rows
      ]);
  }

  public function commitImport(ImportBatch $batch)
  {
      if ($batch->module_name !== 'rehab-manggrove' || $batch->status !== 'pending') abort(400);
      
      $batch->update(['status' => 'processing']);
      
      $validRows = $batch->stagingRows()->where('status', 'valid')->get();
      $importedCount = 0;

      foreach ($validRows as $stagingRow) {
          $row = $stagingRow->data_payload;
          
          $regency = DB::table('m_regencies')
              ->where('province_id', 35)
              ->whereRaw('LOWER(name) LIKE ?', ['%' . strtolower(trim($row['nama_kabupaten'])) . '%'])
              ->first();
              
          $district = DB::table('m_districts')
              ->where('regency_id', $regency->id)
              ->whereRaw('LOWER(name) LIKE ?', ['%' . strtolower(trim($row['nama_kecamatan'])) . '%'])
              ->first();

          if (!$district) {
              $district = DB::table('m_districts')
                  ->whereRaw('LOWER(name) LIKE ?', ['%' . strtolower(trim($row['nama_kecamatan'])) . '%'])
                  ->first();
          }

          $village = null;
          if (!empty($row['nama_desa'])) {
              $village = DB::table('m_villages')
                  ->where('district_id', $district->id)
                  ->whereRaw('LOWER(name) LIKE ?', ['%' . strtolower(trim($row['nama_desa'])) . '%'])
                  ->first();
          }

          RehabManggrove::create([
              'year' => $row['tahun'],
              'month' => $row['bulan_angka'],
              'province_id' => 35,
              'regency_id' => $regency->id,
              'district_id' => $district->id,
              'village_id' => $village?->id,
              'target_annual' => $row['target_tahunan_ha'] ?? 0,
              'realization' => $row['realisasi_ha'] ?? 0,
              'fund_source' => strtolower(trim($row['sumber_dana'])) ?? 'other',
              'status' => 'draft',
              'created_by' => Auth::id(),
          ]);
          $importedCount++;
      }

      $batch->update(['status' => 'completed']);
      
      return redirect()->route('rehab-manggrove.index')->with('success', "Berhasil mengimport {$importedCount} data Rehab Manggrove yang valid.");
  }

  public function import(Request $request)
  {
    $request->validate(['file' => 'required|mimes:xlsx,csv,xls']);
    $import = new RehabManggroveImport();
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

  /**
   * Bulk workflow action.
   */
  public function bulkWorkflowAction(Request $request, BulkWorkflowAction $action)
  {
    $request->validate([
      'ids' => 'required|array',
      'ids.*' => 'exists:rehab_manggrove,id',
      'action' => ['required', Rule::enum(WorkflowAction::class)],
      'rejection_note' => 'nullable|string|max:255',
    ]);

    $workflowAction = WorkflowAction::from($request->action);

    match ($workflowAction) {
      WorkflowAction::SUBMIT => $this->authorize('rehab-manggrove.edit'),
      WorkflowAction::APPROVE, WorkflowAction::REJECT => $this->authorize('rehab-manggrove.approve'),
      WorkflowAction::DELETE => $this->authorize('rehab-manggrove.delete'),
    };

    if ($workflowAction === WorkflowAction::REJECT && !$request->filled('rejection_note')) {
      return redirect()->back()->with('error', 'Catatan penolakan wajib diisi.');
    }

    $extraData = [];
    if ($request->filled('rejection_note')) {
      $extraData['rejection_note'] = $request->rejection_note;
    }

    $count = $action->execute(
      model: RehabManggrove::class,
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
