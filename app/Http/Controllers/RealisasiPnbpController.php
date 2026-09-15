<?php

namespace App\Http\Controllers;

use App\Models\RealisasiPnbp;
use App\Models\PengelolaWisata;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use App\Actions\BulkWorkflowAction;
use App\Actions\SingleWorkflowAction;
use App\Enums\WorkflowAction;
use App\Exports\RealisasiPnbpExport;
use App\Exports\RealisasiPnbpTemplateExport;
use Illuminate\Validation\Rule;
use App\Models\ImportBatch;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\StagingImport;
use App\Services\Imports\RealisasiPnbpImportValidator;
use Maatwebsite\Excel\Validators\ValidationException;
use App\Imports\RealisasiPnbpImport;

class RealisasiPnbpController extends Controller
{
  use \App\Traits\HandlesImportFailures;

  public function index(Request $request)
  {
    $selectedYear = $request->integer('year');
    if (!$selectedYear) {
      $selectedYear = RealisasiPnbp::max('year') ?? now()->year;
    }

    $sortField = $request->query('sort', 'created_at');
    $sortDirection = $request->query('direction', 'desc');

    $datas = RealisasiPnbp::query()
      ->select([
        'realisasi_pnbp.id',
        'realisasi_pnbp.year',
        'realisasi_pnbp.month',
        'realisasi_pnbp.province_id',
        'realisasi_pnbp.regency_id',
        'realisasi_pnbp.id_pengelola_wisata',
        'realisasi_pnbp.types_of_forest_products',
        'realisasi_pnbp.pnbp_target',
        'realisasi_pnbp.pnbp_realization',
        'realisasi_pnbp.status',
        'realisasi_pnbp.rejection_note',
        'realisasi_pnbp.created_at',
        'realisasi_pnbp.created_by',
      ])
      ->with([
        'creator:id,name',
        'regency:id,name',
        'pengelola_wisata:id,name'
      ])
      ->when($selectedYear, function ($query, $year) {
        return $query->where('year', $year);
      })
      ->when($request->search, function ($query, $search) {
        $query->where(function ($q) use ($search) {
          $q->where('types_of_forest_products', 'like', "{$search}%")
            ->orWhereHas('regency', fn($q2) => $q2->where('name', 'like', "{$search}%"))
            ->orWhereHas('pengelola_wisata', fn($q2) => $q2->where('name', 'like', "{$search}%"));
        });
      })
      ->when($sortField === 'pengelola', function ($q) use ($sortDirection) {
        $q->leftJoin('m_pengelola_wisata', 'realisasi_pnbp.id_pengelola_wisata', '=', 'm_pengelola_wisata.id')
          ->orderBy('m_pengelola_wisata.name', $sortDirection);
      })
      ->when(!in_array($sortField, ['pengelola']), function ($q) use ($sortField, $sortDirection) {
        $user = auth()->user();

        if ($sortField === 'created_at' && $sortDirection === 'desc') {
          if ($user->hasRole('kacdk')) {
            $q->orderByRaw("CASE WHEN status = 'waiting_cdk' THEN 0 ELSE 1 END");
          } elseif ($user->hasRole('kasi')) {
            $q->orderByRaw("CASE WHEN status = 'waiting_kasi' THEN 0 ELSE 1 END");
          }
        }

        match ($sortField) {
          'month' => $q->orderBy('month', $sortDirection),
          'forest_product' => $q->orderBy('types_of_forest_products', $sortDirection),
          'target' => $q->orderBy('pnbp_target', $sortDirection),
          'realization' => $q->orderBy('pnbp_realization', $sortDirection),
          'status' => $q->orderBy('status', $sortDirection),
          default => $q->orderBy('created_at', 'desc'),
        };
      })
      ->paginate($request->integer('per_page', 10))
      ->withQueryString();

    // Stats with caching
    $cacheKey = "pnbp-stats-{$selectedYear}";
    $stats = cache()->remember($cacheKey, 300, function () use ($selectedYear) {
      return [
        'total_count' => RealisasiPnbp::when($selectedYear, fn($q) => $q->where('year', $selectedYear))->count(),
        'verified_count' => RealisasiPnbp::where('status', 'final')
          ->when($selectedYear, fn($q) => $q->where('year', $selectedYear))
          ->count(),
      ];
    });

    // Available Years with caching
    $availableYears = cache()->remember('pnbp-years', 3600, function () {
      $dbYears = RealisasiPnbp::distinct()
        ->pluck('year')
        ->toArray();
      $fixedYears = range(2025, 2021);
      $years = array_unique(array_merge($dbYears, $fixedYears));
      rsort($years);
      return $years;
    });

    return Inertia::render('RealisasiPnbp/Index', [
      'datas' => $datas,
      'stats' => $stats,
      'available_years' => $availableYears,
      'filters' => [
        'year' => (int) $selectedYear,
        'search' => $request->search,
        'sort' => $sortField,
        'direction' => $sortDirection,
        'per_page' => (int) $request->query('per_page', 10),
      ],
    ]);
  }

  public function create()
  {
    return Inertia::render('RealisasiPnbp/Create', [
      'provinces' => DB::table('m_provinces')->where('id', '35')->get(),
      'regencies' => DB::table('m_regencies')->where('province_id', '35')->get(),
      'pengelola_wisata' => PengelolaWisata::all(),
    ]);
  }

  public function store(Request $request)
  {
    $validated = $request->validate([
      'year' => 'required|integer|digits:4',
      'month' => 'required|integer|min:1|max:12',
      'province_id' => 'required|exists:m_provinces,id',
      'regency_id' => 'required|exists:m_regencies,id',
      'id_pengelola_wisata' => 'required|exists:m_pengelola_wisata,id',
      'types_of_forest_products' => 'required|string',
      'pnbp_target' => 'required|string',
      'pnbp_realization' => 'required|string',
    ]);

    RealisasiPnbp::create($validated);

    return redirect()->route('realisasi-pnbp.index')
      ->with('success', 'Data berhasil ditambahkan');
  }

  public function edit(RealisasiPnbp $realisasiPnbp)
  {
    return Inertia::render('RealisasiPnbp/Edit', [
      'data' => $realisasiPnbp->load(['regency']),
      'provinces' => DB::table('m_provinces')->where('id', '35')->get(),
      'regencies' => DB::table('m_regencies')->where('province_id', '35')->get(),
      'pengelola_wisata' => PengelolaWisata::all(),
    ]);
  }

  public function update(Request $request, RealisasiPnbp $realisasiPnbp)
  {
    $validated = $request->validate([
      'year' => 'required|integer|digits:4',
      'month' => 'required|integer|min:1|max:12',
      'province_id' => 'required|exists:m_provinces,id',
      'regency_id' => 'required|exists:m_regencies,id',
      'id_pengelola_wisata' => 'required|exists:m_pengelola_wisata,id',
      'types_of_forest_products' => 'required|string',
      'pnbp_target' => 'required|string',
      'pnbp_realization' => 'required|string',
    ]);

    $realisasiPnbp->update($validated);

    return redirect()->route('realisasi-pnbp.index')
      ->with('success', 'Data berhasil diperbarui');
  }

  public function destroy(RealisasiPnbp $realisasiPnbp)
  {
    $realisasiPnbp->delete();

    return redirect()->route('realisasi-pnbp.index')
      ->with('success', 'Data berhasil dihapus');
  }

  public function singleWorkflowAction(Request $request, RealisasiPnbp $realisasiPnbp, SingleWorkflowAction $action)
  {
    $request->validate([
      'action' => ['required', Rule::enum(WorkflowAction::class)],
      'rejection_note' => 'nullable|string|max:255',
    ]);

    $workflowAction = WorkflowAction::from($request->action);

    match ($workflowAction) {
      WorkflowAction::SUBMIT => $this->authorize('realisasi-pnbp.edit'),
      WorkflowAction::APPROVE, WorkflowAction::REJECT => $this->authorize('realisasi-pnbp.approve'),
      WorkflowAction::DELETE => $this->authorize('realisasi-pnbp.delete'),
    };

    if ($workflowAction === WorkflowAction::REJECT && !$request->filled('rejection_note')) {
      return redirect()->back()->with('error', 'Catatan penolakan wajib diisi.');
    }

    $extraData = [];
    if ($request->filled('rejection_note')) {
      $extraData['rejection_note'] = $request->rejection_note;
    }

    $success = $action->execute(
      model: $realisasiPnbp,
      action: $workflowAction,
      user: auth()->user(),
      extraData: $extraData
    );

    if ($success) {
      cache()->forget("pnbp-stats-{$realisasiPnbp->year}");

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
    return Excel::download(new RealisasiPnbpExport($year), 'realisasi-pnbp-' . date('Y-m-d') . '.xlsx');
  }

  public function template()
  {
    return Excel::download(new RealisasiPnbpTemplateExport, 'template_import_realisasi_pnbp.xlsx');
  }

  public function previewImport(Request $request)
  {
      $this->authorize('realisasi-pnbp.import');
      $request->validate(['file' => 'required|mimes:xlsx,csv,xls']);
      
      $batch = ImportBatch::create([
          'user_id' => Auth::id(),
          'module_name' => 'realisasi-pnbp',
          'filename' => $request->file('file')->getClientOriginalName(),
          'status' => 'pending',
      ]);

      Excel::import(
          new StagingImport($batch->id, new RealisasiPnbpImportValidator()), 
          $request->file('file')
      );

      return redirect()->route('realisasi-pnbp.show-preview', $batch->id);
  }

  public function showPreview(ImportBatch $batch)
  {
      if ($batch->module_name !== 'realisasi-pnbp') abort(404);
      $this->authorize('realisasi-pnbp.import');

      $rows = $batch->stagingRows()->paginate(50);
      
      return Inertia::render('RealisasiPnbp/ImportPreview', [
          'batch' => $batch,
          'rows' => $rows
      ]);
  }

  public function commitImport(ImportBatch $batch)
  {
      if ($batch->module_name !== 'realisasi-pnbp' || $batch->status !== 'pending') abort(400);
      $this->authorize('realisasi-pnbp.import');
      
      $batch->update(['status' => 'processing']);
      
      $validRows = $batch->stagingRows()->where('status', 'valid')->get();
      $importedCount = 0;

      foreach ($validRows as $stagingRow) {
          $row = $stagingRow->data_payload;
          
          $regency = DB::table('m_regencies')
              ->whereRaw('LOWER(name) LIKE ?', ['%' . strtolower(trim($row['nama_kabupatenkota'])) . '%'])
              ->first();

          $pengelolaWisata = DB::table('m_pengelola_wisata')
              ->whereRaw('LOWER(name) LIKE ?', ['%' . strtolower(trim($row['nama_pengelola_wisata'])) . '%'])
              ->first();

          RealisasiPnbp::create([
              'year' => $row['tahun'],
              'month' => $row['bulan_angka_1_12'],
              'province_id' => $regency->province_id,
              'regency_id' => $regency->id,
              'id_pengelola_wisata' => $pengelolaWisata->id,
              'types_of_forest_products' => $row['jenis_hasil_hutan'],
              'pnbp_target' => $row['target_pnbp'],
              'pnbp_realization' => $row['realisasi_pnbp'],
              'status' => 'draft',
              'created_by' => Auth::id(),
          ]);
          
          $importedCount++;
      }

      $batch->update(['status' => 'completed']);
      
      foreach (range(date('Y'), date('Y') - 5) as $y) {
          cache()->forget("pnbp-stats-{$y}");
      }
      
      return redirect()->route('realisasi-pnbp.index')->with('success', "Berhasil mengimport {$importedCount} data PNBP yang valid.");
  }

  public function import(Request $request)
  {
    $request->validate(['file' => 'required|mimes:xlsx,csv,xls']);

    $import = new RealisasiPnbpImport();

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
      'ids.*' => 'exists:realisasi_pnbp,id',
      'action' => 'required|string',
      'rejection_note' => 'nullable|string|max:255',
    ]);

    $workflowAction = WorkflowAction::from($request->action);

    match ($workflowAction) {
      WorkflowAction::SUBMIT => $this->authorize('realisasi-pnbp.edit'),
      WorkflowAction::APPROVE, WorkflowAction::REJECT => $this->authorize('realisasi-pnbp.approve'),
      WorkflowAction::DELETE => $this->authorize('realisasi-pnbp.delete'),
    };

    if ($workflowAction === WorkflowAction::REJECT && !$request->filled('rejection_note')) {
      return redirect()->back()->with('error', 'Catatan penolakan wajib diisi.');
    }

    $extraData = [];
    if ($request->filled('rejection_note')) {
      $extraData['rejection_note'] = $request->rejection_note;
    }

    $count = $action->execute(
      model: RealisasiPnbp::class,
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

