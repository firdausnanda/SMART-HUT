<?php

namespace App\Http\Controllers;

use App\Models\RehabManggrove;
use App\Actions\BulkWorkflowAction;
use App\Enums\WorkflowAction;
use App\Models\Regencies;
use App\Models\SumberDana;
use Illuminate\Http\Request;
use Inertia\Inertia;

class RehabManggroveController extends Controller
{
  use \App\Traits\HandlesImportFailures;
  public function __construct()
  {
    $this->middleware('permission:rehab.view')->only(['index', 'show']);
    $this->middleware('permission:rehab.create')->only(['create', 'store']);
    $this->middleware('permission:rehab.edit')->only(['edit', 'update', 'submit']);
    $this->middleware('permission:rehab.delete')->only(['destroy']);
    $this->middleware('permission:rehab.approve')->only(['verify', 'approve', 'reject']);
  }

  public function index(Request $request)
  {
    $defaultYear = RehabManggrove::max('year') ?? now()->year;
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
          $qq->where('fund_source', 'like', "%{$search}%")
            ->orWhereHas('village_rel', fn($q) => $q->where('name', 'like', "%{$search}%"))
            ->orWhereHas('district_rel', fn($q) => $q->where('name', 'like', "%{$search}%"))
            ->orWhereHas('regency_rel', fn($q) => $q->where('name', 'like', "%{$search}%"));
        });
      })

      ->when($sortField === 'location', function ($q) use ($sortDirection) {
        $q->leftJoin('m_villages', 'rehab_manggrove.village_id', '=', 'm_villages.id')
          ->orderBy('m_villages.name', $sortDirection);
      })

      ->when($sortField !== 'location', function ($q) use ($sortField, $sortDirection) {
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
      $fixedYears = range(2025, 2021);
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
   * Submit the report for verification.
   */
  public function submit(RehabManggrove $rehabManggrove)
  {
    $rehabManggrove->update(['status' => 'waiting_kasi']);
    return redirect()->back()->with('success', 'Laporan berhasil diajukan untuk verifikasi Kasi.');
  }

  /**
   * Approve the report.
   */
  public function approve(RehabManggrove $rehabManggrove)
  {
    $user = auth()->user();

    if (($user->hasRole('kasi') || $user->hasRole('admin')) && $rehabManggrove->status === 'waiting_kasi') {
      $rehabManggrove->update([
        'status' => 'waiting_cdk',
        'approved_by_kasi_at' => now(),
      ]);
      return redirect()->back()->with('success', 'Laporan disetujui dan diteruskan ke KaCDK.');
    }

    if (($user->hasRole('kacdk') || $user->hasRole('admin')) && $rehabManggrove->status === 'waiting_cdk') {
      $rehabManggrove->update([
        'status' => 'final',
        'approved_by_cdk_at' => now(),
      ]);
      return redirect()->back()->with('success', 'Laporan telah disetujui secara final.');
    }

    return redirect()->back()->with('error', 'Aksi tidak diijinkan.');
  }

  /**
   * Reject the report.
   */
  public function reject(Request $request, RehabManggrove $rehabManggrove)
  {
    $request->validate([
      'rejection_note' => 'required|string|max:255',
    ]);

    $rehabManggrove->update([
      'status' => 'rejected',
      'rejection_note' => $request->rejection_note,
    ]);

    return redirect()->back()->with('success', 'Laporan telah ditolak dengan catatan.');
  }

  public function export(Request $request)
  {
    $year = $request->query('year');
    return \Maatwebsite\Excel\Facades\Excel::download(new \App\Exports\RehabManggroveExport($year), 'rehab-manggrove-' . date('Y-m-d') . '.xlsx');
  }

  public function template()
  {
    return \Maatwebsite\Excel\Facades\Excel::download(new \App\Exports\RehabManggroveTemplateExport, 'template_import_rehab_manggrove.xlsx');
  }

  public function import(Request $request)
  {
    $request->validate(['file' => 'required|mimes:xlsx,csv,xls']);
    $import = new \App\Imports\RehabManggroveImport();
    try {
      \Maatwebsite\Excel\Facades\Excel::import($import, $request->file('file'));
    } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
      return redirect()->back()->with('import_errors', $this->mapImportFailures($e->failures()));
    }
    if ($import->failures()->isNotEmpty()) {
      return redirect()->back()->with('import_errors', $this->mapImportFailures($import->failures()));
    }
    return redirect()->back()->with('success', 'Data berhasil diimport.');
  }

  /**
   * Bulk delete records.
   */
  public function bulkDestroy(Request $request)
  {
    $request->validate([
      'ids' => 'required|array|min:1',
      'ids.*' => 'integer',
    ]);

    $count = app(BulkWorkflowAction::class)->execute(
      RehabManggrove::class,
      WorkflowAction::DELETE,
      $request->ids,
      auth()->user()
    );

    if ($count === 0) {
      return back()->with('error', 'Tidak ada data yang dapat dihapus atau Anda tidak memiliki hak akses.');
    }

    return back()->with('success', "{$count} data berhasil dihapus.");
  }

  /**
   * Bulk submit records.
   */
  public function bulkSubmit(Request $request)
  {
    $request->validate([
      'ids' => 'required|array|min:1',
      'ids.*' => 'integer',
    ]);

    $count = app(BulkWorkflowAction::class)->execute(
      RehabManggrove::class,
      WorkflowAction::SUBMIT,
      $request->ids,
      auth()->user()
    );

    return back()->with('success', "{$count} laporan berhasil diajukan.");
  }

  /**
   * Bulk approve records.
   */
  public function bulkApprove(Request $request)
  {
    $request->validate([
      'ids' => 'required|array|min:1',
      'ids.*' => 'integer',
    ]);

    $count = app(BulkWorkflowAction::class)->execute(
      RehabManggrove::class,
      WorkflowAction::APPROVE,
      $request->ids,
      auth()->user()
    );

    return back()->with('success', "{$count} laporan berhasil disetujui.");
  }

  /**
   * Bulk reject records.
   */
  public function bulkReject(Request $request)
  {
    $request->validate([
      'ids' => 'required|array|min:1',
      'ids.*' => 'integer',
      'rejection_note' => 'required|string|max:255',
    ]);

    $count = app(BulkWorkflowAction::class)->execute(
      RehabManggrove::class,
      WorkflowAction::REJECT,
      $request->ids,
      auth()->user(),
      ['rejection_note' => $request->rejection_note]
    );

    return back()->with('success', "{$count} laporan berhasil ditolak.");
  }
}
