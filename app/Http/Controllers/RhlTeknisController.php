<?php

namespace App\Http\Controllers;

use App\Models\BangunanKta;
use App\Models\RhlTeknis;
use App\Models\RhlTeknisDetail;
use App\Models\SumberDana;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class RhlTeknisController extends Controller
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
    $defaultYear = RhlTeknis::max('year') ?? now()->year;
    $selectedYear = $request->integer('year', $defaultYear);

    $sortField = $request->query('sort', 'created_at');
    $sortDirection = $request->query('direction', 'desc');

    $datas = RhlTeknis::query()
      ->select([
        'rhl_teknis.id',
        'rhl_teknis.year',
        'rhl_teknis.month',
        'rhl_teknis.regency_id',
        'rhl_teknis.district_id',
        'rhl_teknis.village_id',
        'rhl_teknis.fund_source',
        'rhl_teknis.target_annual',
        'rhl_teknis.status',
        'rhl_teknis.created_at',
        'rhl_teknis.created_by',
      ])
      ->with([
        'creator:id,name',
        'regency:id,name',
        'district:id,name',
        'village:id,name',
        'details.bangunan_kta:id,name'
      ])
      ->where('year', $selectedYear)

      ->when($request->search, function ($q, $search) {
        $q->where(function ($qq) use ($search) {
          $qq->where('fund_source', 'like', "%{$search}%")
            ->orWhereHas('details.bangunan_kta', fn($q) => $q->where('name', 'like', "%{$search}%"))
            ->orWhereHas('village', fn($q) => $q->where('name', 'like', "%{$search}%"))
            ->orWhereHas('district', fn($q) => $q->where('name', 'like', "%{$search}%"))
            ->orWhereHas('regency', fn($q) => $q->where('name', 'like', "%{$search}%"));
        });
      })

      ->when($sortField === 'location', function ($q) use ($sortDirection) {
        $q->leftJoin('m_villages', 'rhl_teknis.village_id', '=', 'm_villages.id')
          ->orderBy('m_villages.name', $sortDirection);
      })

      ->when($sortField !== 'location', function ($q) use ($sortField, $sortDirection) {
        match ($sortField) {
          'period' => $q->orderBy('month', $sortDirection),
          'target' => $q->orderBy('target_annual', $sortDirection),
          'fund_source' => $q->orderBy('fund_source', $sortDirection),
          'status' => $q->orderBy('status', $sortDirection),
          default => $q->orderBy('created_at', 'desc'),
        };
      })

      ->paginate($request->integer('per_page', 10))
      ->withQueryString();

    $stats = cache()->remember(
      "rhl-teknis-stats-{$selectedYear}",
      300,
      fn() => [
        'total_target' => RhlTeknis::where('year', $selectedYear)->where('status', 'final')->sum('target_annual'),
        'total_units' => RhlTeknis::where('year', $selectedYear)
          ->where('status', 'final')
          ->whereHas('details')
          ->withSum('details', 'unit_amount')
          ->get()
          ->sum('details_sum_unit_amount'),
        'total_count' => RhlTeknis::where('year', $selectedYear)->where('status', 'final')->count(),
      ]
    );

    $availableYears = cache()->remember('rhl-teknis-years', 3600, function () {
      $dbYears = RhlTeknis::distinct()->pluck('year')->toArray();
      $fixedYears = range(2025, 2021);
      $years = array_unique(array_merge($dbYears, $fixedYears));
      rsort($years);
      return $years;
    });

    $sumberDana = cache()->remember('sumber-dana', 3600, fn() => SumberDana::select('id', 'name')->get());

    return Inertia::render('RhlTeknis/Index', [
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

  public function create()
  {
    return Inertia::render('RhlTeknis/Create', [
      'sumberDana' => SumberDana::all(),
      'bangunanKta' => BangunanKta::all()
    ]);
  }

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
      'fund_source' => 'required|string',
      'details' => 'required|array|min:1',
      'details.*.bangunan_kta_id' => 'required|exists:m_bangunan_kta,id',
      'details.*.unit_amount' => 'required|integer|min:0',
    ]);

    DB::transaction(function () use ($validated) {
      $report = RhlTeknis::create([
        'year' => $validated['year'],
        'month' => $validated['month'],
        'province_id' => $validated['province_id'] ?? null,
        'regency_id' => $validated['regency_id'] ?? null,
        'district_id' => $validated['district_id'] ?? null,
        'village_id' => $validated['village_id'] ?? null,
        'target_annual' => $validated['target_annual'],
        'fund_source' => $validated['fund_source'],
      ]);

      foreach ($validated['details'] as $detail) {
        $report->details()->create($detail);
      }
    });

    return redirect()->route('rhl-teknis.index')->with('success', 'Data Berhasil Dibuat');
  }

  public function edit(RhlTeknis $rhl_teknis)
  {
    return Inertia::render('RhlTeknis/Edit', [
      'data' => $rhl_teknis->load('details.bangunan_kta'),
      'sumberDana' => SumberDana::all(),
      'bangunanKta' => BangunanKta::all()
    ]);
  }

  public function update(Request $request, RhlTeknis $rhl_teknis)
  {
    $validated = $request->validate([
      'year' => 'required|integer',
      'month' => 'required|integer|min:1|max:12',
      'province_id' => 'nullable|exists:m_provinces,id',
      'regency_id' => 'nullable|exists:m_regencies,id',
      'district_id' => 'nullable|exists:m_districts,id',
      'village_id' => 'nullable|exists:m_villages,id',
      'target_annual' => 'required|numeric',
      'fund_source' => 'required|string',
      'details' => 'required|array|min:1',
      'details.*.bangunan_kta_id' => 'required|exists:m_bangunan_kta,id',
      'details.*.unit_amount' => 'required|integer|min:0',
    ]);

    DB::transaction(function () use ($validated, $rhl_teknis) {
      $rhl_teknis->update([
        'year' => $validated['year'],
        'month' => $validated['month'],
        'province_id' => $validated['province_id'] ?? null,
        'regency_id' => $validated['regency_id'] ?? null,
        'district_id' => $validated['district_id'] ?? null,
        'village_id' => $validated['village_id'] ?? null,
        'target_annual' => $validated['target_annual'],
        'fund_source' => $validated['fund_source'],
      ]);

      $rhl_teknis->details()->delete();
      foreach ($validated['details'] as $detail) {
        $rhl_teknis->details()->create($detail);
      }
    });

    return redirect()->route('rhl-teknis.index')->with('success', 'Data Berhasil Diperbarui');
  }

  public function destroy(RhlTeknis $rhl_teknis)
  {
    $rhl_teknis->delete();

    return redirect()->route('rhl-teknis.index')->with('success', 'Data Berhasil Dihapus');
  }

  public function submit(RhlTeknis $rhl_teknis)
  {
    $rhl_teknis->update(['status' => 'waiting_kasi']);
    return redirect()->back()->with('success', 'Laporan berhasil diajukan untuk verifikasi Kasi.');
  }

  public function approve(RhlTeknis $rhl_teknis)
  {
    $user = auth()->user();

    if (($user->hasRole('kasi') || $user->hasRole('admin')) && $rhl_teknis->status === 'waiting_kasi') {
      $rhl_teknis->update([
        'status' => 'waiting_cdk',
        'approved_by_kasi_at' => now(),
      ]);
      return redirect()->back()->with('success', 'Laporan disetujui and diteruskan ke KaCDK.');
    }

    if (($user->hasRole('kacdk') || $user->hasRole('admin')) && $rhl_teknis->status === 'waiting_cdk') {
      $rhl_teknis->update([
        'status' => 'final',
        'approved_by_cdk_at' => now(),
      ]);
      return redirect()->back()->with('success', 'Laporan telah disetujui secara final.');
    }

    return redirect()->back()->with('error', 'Aksi tidak diijinkan.');
  }

  public function reject(Request $request, RhlTeknis $rhl_teknis)
  {
    $request->validate([
      'rejection_note' => 'required|string|max:255',
    ]);

    $rhl_teknis->update([
      'status' => 'rejected',
      'rejection_note' => $request->rejection_note,
    ]);

    return redirect()->back()->with('success', 'Laporan telah ditolak dengan catatan.');
  }

  public function export(Request $request)
  {
    $year = $request->query('year');
    return \Maatwebsite\Excel\Facades\Excel::download(new \App\Exports\RhlTeknisExport($year), 'rhl-teknis-' . date('Y-m-d') . '.xlsx');
  }

  public function template()
  {
    return \Maatwebsite\Excel\Facades\Excel::download(new \App\Exports\RhlTeknisTemplateExport, 'template_import_rhl_teknis.xlsx');
  }

  public function import(Request $request)
  {
    $request->validate(['file' => 'required|mimes:xlsx,csv,xls']);
    $import = new \App\Imports\RhlTeknisImport();
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
      'ids.*' => 'exists:rhl_teknis,id',
    ]);

    $user = auth()->user();
    $count = 0;

    if ($user->hasAnyRole(['kasi', 'kacdk'])) {
      return redirect()->back()->with('error', 'Aksi tidak diijinkan.');
    }

    if ($user->hasAnyRole(['pk', 'peh', 'pelaksana'])) {
      $count = RhlTeknis::whereIn('id', $request->ids)
        ->where('status', 'draft')
        ->delete();

      if ($count === 0) {
        return redirect()->back()->with('error', 'Hanya data dengan status draft yang dapat dihapus.');
      }

      return redirect()->back()->with('success', $count . ' data berhasil dihapus.');
    }

    if ($user->hasRole('admin')) {
      $count = RhlTeknis::whereIn('id', $request->ids)->delete();

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
      'ids.*' => 'exists:rhl_teknis,id',
    ]);

    $count = RhlTeknis::whereIn('id', $request->ids)
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
      'ids.*' => 'exists:rhl_teknis,id',
    ]);

    $user = auth()->user();
    $count = 0;

    if ($user->hasRole('kasi') || $user->hasRole('admin')) {
      $count = RhlTeknis::whereIn('id', $request->ids)
        ->where('status', 'waiting_kasi')
        ->update([
          'status' => 'waiting_cdk',
          'approved_by_kasi_at' => now(),
        ]);
    } elseif ($user->hasRole('kacdk') || $user->hasRole('admin')) {
      $count = RhlTeknis::whereIn('id', $request->ids)
        ->where('status', 'waiting_cdk')
        ->update([
          'status' => 'final',
          'approved_by_cdk_at' => now(),
        ]);
    }

    return redirect()->back()->with('success', $count . ' laporan berhasil disetujui.');
  }
}
