<?php

namespace App\Http\Controllers;

use App\Models\PenghijauanLingkungan;
use App\Models\SumberDana;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PenghijauanLingkunganController extends Controller
{
  use \App\Traits\HandlesImportFailures;
  public function __construct()
  {
    $this->middleware('permission:penghijauan.view')->only(['index', 'show']);
    $this->middleware('permission:penghijauan.create')->only(['create', 'store']);
    $this->middleware('permission:penghijauan.edit')->only(['edit', 'update', 'submit']);
    $this->middleware('permission:penghijauan.delete')->only(['destroy']);
    $this->middleware('permission:penghijauan.approve')->only(['verify', 'approve', 'reject']);
  }

  public function index(Request $request)
  {
    $defaultYear = PenghijauanLingkungan::max('year') ?? now()->year;
    $selectedYear = $request->integer('year', $defaultYear);

    $sortField = $request->query('sort', 'created_at');
    $sortDirection = $request->query('direction', 'desc');

    $datas = PenghijauanLingkungan::query()
      ->select([
        'penghijauan_lingkungan.id',
        'penghijauan_lingkungan.year',
        'penghijauan_lingkungan.month',
        'penghijauan_lingkungan.regency_id',
        'penghijauan_lingkungan.district_id',
        'penghijauan_lingkungan.village_id',
        'penghijauan_lingkungan.fund_source',
        'penghijauan_lingkungan.target_annual',
        'penghijauan_lingkungan.realization',
        'penghijauan_lingkungan.status',
        'penghijauan_lingkungan.created_at',
        'penghijauan_lingkungan.created_by',
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
        $q->leftJoin('m_villages', 'penghijauan_lingkungan.village_id', '=', 'm_villages.id')
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
      "penghijauan-lingkungan-stats-{$selectedYear}",
      300,
      fn() => [
        'total_target' => PenghijauanLingkungan::where('year', $selectedYear)->where('status', 'final')->sum('target_annual'),
        'total_realization' => PenghijauanLingkungan::where('year', $selectedYear)->where('status', 'final')->sum('realization'),
        'total_count' => PenghijauanLingkungan::where('year', $selectedYear)->where('status', 'final')->count(),
      ]
    );

    $availableYears = cache()->remember('penghijauan-lingkungan-years', 3600, function () {
      $dbYears = PenghijauanLingkungan::distinct()->pluck('year')->toArray();
      $fixedYears = range(2025, 2021);
      $years = array_unique(array_merge($dbYears, $fixedYears));
      rsort($years);
      return $years;
    });

    $sumberDana = cache()->remember(
      'sumber-dana',
      3600,
      fn() => SumberDana::select('id', 'name')->get()
    );

    return Inertia::render('PenghijauanLingkungan/Index', [
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
    return Inertia::render('PenghijauanLingkungan/Create', [
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
      'village' => 'nullable|string',
      'district' => 'nullable|string',
      'coordinates' => 'nullable|string',
    ]);

    PenghijauanLingkungan::create($validated);

    return redirect()->route('penghijauan-lingkungan.index')->with('success', 'Data Created Successfully');
  }

  /**
   * Show the form for editing the specified resource.
   */
  public function edit(PenghijauanLingkungan $penghijauanLingkungan)
  {
    return Inertia::render('PenghijauanLingkungan/Edit', [
      'data' => $penghijauanLingkungan->load(['regency_rel', 'district_rel', 'village_rel']),
      'sumberDana' => SumberDana::all()
    ]);
  }

  /**
   * Update the specified resource in storage.
   */
  public function update(Request $request, PenghijauanLingkungan $penghijauanLingkungan)
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
      'village' => 'nullable|string',
      'district' => 'nullable|string',
      'coordinates' => 'nullable|string',
    ]);

    $penghijauanLingkungan->update($validated);

    return redirect()->route('penghijauan-lingkungan.index')->with('success', 'Data Updated Successfully');
  }

  /**
   * Remove the specified resource from storage.
   */
  public function destroy(PenghijauanLingkungan $penghijauanLingkungan)
  {
    $penghijauanLingkungan->delete();

    return redirect()->route('penghijauan-lingkungan.index')->with('success', 'Data Deleted Successfully');
  }

  /**
   * Submit the report for verification.
   */
  public function submit(PenghijauanLingkungan $penghijauanLingkungan)
  {
    $penghijauanLingkungan->update(['status' => 'waiting_kasi']);
    return redirect()->back()->with('success', 'Laporan berhasil diajukan untuk verifikasi Kasi.');
  }

  /**
   * Approve the report.
   */
  public function approve(PenghijauanLingkungan $penghijauanLingkungan)
  {
    $user = auth()->user();

    if (($user->hasRole('kasi') || $user->hasRole('admin')) && $penghijauanLingkungan->status === 'waiting_kasi') {
      $penghijauanLingkungan->update([
        'status' => 'waiting_cdk',
        'approved_by_kasi_at' => now(),
      ]);
      return redirect()->back()->with('success', 'Laporan disetujui dan diteruskan ke KaCDK.');
    }

    if (($user->hasRole('kacdk') || $user->hasRole('admin')) && $penghijauanLingkungan->status === 'waiting_cdk') {
      $penghijauanLingkungan->update([
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
  public function reject(Request $request, PenghijauanLingkungan $penghijauanLingkungan)
  {
    $request->validate([
      'rejection_note' => 'required|string|max:255',
    ]);

    $penghijauanLingkungan->update([
      'status' => 'rejected',
      'rejection_note' => $request->rejection_note,
    ]);

    return redirect()->back()->with('success', 'Laporan telah ditolak dengan catatan.');
  }

  /**
   * Export data to Excel.
   */
  public function export(Request $request)
  {
    $year = $request->query('year');
    return \Maatwebsite\Excel\Facades\Excel::download(new \App\Exports\PenghijauanLingkunganExport($year), 'penghijauan-lingkungan-' . date('Y-m-d') . '.xlsx');
  }

  /**
   * Download import template.
   */
  public function template()
  {
    return \Maatwebsite\Excel\Facades\Excel::download(new \App\Exports\PenghijauanLingkunganTemplateExport, 'template_import_penghijauan_lingkungan.xlsx');
  }

  /**
   * Import data from Excel.
   */
  public function import(Request $request)
  {
    $request->validate([
      'file' => 'required|mimes:xlsx,csv,xls',
    ]);

    $import = new \App\Imports\PenghijauanLingkunganImport();

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
      'ids' => 'required|array',
      'ids.*' => 'exists:penghijauan_lingkungan,id',
    ]);

    $user = auth()->user();
    $count = 0;

    if ($user->hasAnyRole(['kasi', 'kacdk'])) {
      return redirect()->back()->with('error', 'Aksi tidak diijinkan.');
    }

    if ($user->hasAnyRole(['pk', 'peh', 'pelaksana'])) {
      $count = PenghijauanLingkungan::whereIn('id', $request->ids)
        ->where('status', 'draft')
        ->delete();

      if ($count === 0) {
        return redirect()->back()->with('error', 'Hanya data dengan status draft yang dapat dihapus.');
      }

      return redirect()->back()->with('success', $count . ' data berhasil dihapus.');
    }

    if ($user->hasRole('admin')) {
      $count = PenghijauanLingkungan::whereIn('id', $request->ids)->delete();

      return redirect()->back()->with('success', $count . ' data berhasil dihapus.');
    }

    return redirect()->back()->with('success', count($request->ids) . ' data berhasil dihapus.');
  }

  /**
   * Bulk submit records.
   */
  public function bulkSubmit(Request $request)
  {
    $request->validate([
      'ids' => 'required|array',
      'ids.*' => 'exists:penghijauan_lingkungan,id',
    ]);

    $count = PenghijauanLingkungan::whereIn('id', $request->ids)
      ->whereIn('status', ['draft', 'rejected'])
      ->update(['status' => 'waiting_kasi']);

    return redirect()->back()->with('success', $count . ' laporan berhasil diajukan.');
  }

  /**
   * Bulk approve records.
   */
  public function bulkApprove(Request $request)
  {
    $request->validate([
      'ids' => 'required|array',
      'ids.*' => 'exists:penghijauan_lingkungan,id',
    ]);

    $user = auth()->user();
    $count = 0;

    if ($user->hasRole('kasi') || $user->hasRole('admin')) {
      $count = PenghijauanLingkungan::whereIn('id', $request->ids)
        ->where('status', 'waiting_kasi')
        ->update([
          'status' => 'waiting_cdk',
          'approved_by_kasi_at' => now(),
        ]);
    } elseif ($user->hasRole('kacdk') || $user->hasRole('admin')) {
      $count = PenghijauanLingkungan::whereIn('id', $request->ids)
        ->where('status', 'waiting_cdk')
        ->update([
          'status' => 'final',
          'approved_by_cdk_at' => now(),
        ]);
    }

    return redirect()->back()->with('success', $count . ' laporan berhasil disetujui.');
  }

  /**
   * Bulk reject records.
   */
  public function bulkReject(Request $request)
  {
    $request->validate([
      'ids' => 'required|array',
      'ids.*' => 'exists:penghijauan_lingkungan,id',
      'rejection_note' => 'required|string|max:255',
    ]);

    $user = auth()->user();
    $count = 0;

    if ($user->hasRole('kasi') || $user->hasRole('admin')) {
      $count = PenghijauanLingkungan::whereIn('id', $request->ids)
        ->where('status', 'waiting_kasi')
        ->update([
          'status' => 'rejected',
          'rejection_note' => $request->rejection_note,
        ]);
    } elseif ($user->hasRole('kacdk') || $user->hasRole('admin')) {
      $count = PenghijauanLingkungan::whereIn('id', $request->ids)
        ->where('status', 'waiting_cdk')
        ->update([
          'status' => 'rejected',
          'rejection_note' => $request->rejection_note,
        ]);
    }

    return redirect()->back()->with('success', $count . ' laporan berhasil ditolak.');
  }
}
