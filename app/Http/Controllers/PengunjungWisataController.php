<?php

namespace App\Http\Controllers;

use App\Models\PengunjungWisata;
use App\Models\PengelolaWisata;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PengunjungWisataController extends Controller
{
  use \App\Traits\HandlesImportFailures;
  public function __construct()
  {
    $this->middleware('permission:perlindungan.view')->only(['index', 'show']);
    $this->middleware('permission:perlindungan.create')->only(['create', 'store']);
    $this->middleware('permission:perlindungan.edit')->only(['edit', 'update', 'submit']);
    $this->middleware('permission:perlindungan.delete')->only(['destroy']);
  }

  public function index(Request $request)
  {
    $defaultYear = PengunjungWisata::max('year') ?? now()->year;
    $selectedYear = $request->integer('year', $defaultYear);

    $sortField = $request->query('sort', 'created_at');
    $sortDirection = $request->query('direction', 'desc');

    $datas = PengunjungWisata::query()
      ->select([
        'pengunjung_wisata.id',
        'pengunjung_wisata.year',
        'pengunjung_wisata.month',
        'pengunjung_wisata.id_pengelola_wisata',
        'pengunjung_wisata.number_of_visitors',
        'pengunjung_wisata.gross_income',
        'pengunjung_wisata.status',
        'pengunjung_wisata.created_at',
        'pengunjung_wisata.created_by',
      ])
      ->with([
        'pengelolaWisata:id,name',
        'creator:id,name'
      ])
      ->where('year', $selectedYear)

      ->when($request->search, function ($q, $search) {
        $q->whereHas('pengelolaWisata', fn($qq) => $qq->where('name', 'like', "%{$search}%"));
      })

      ->when($sortField === 'pengelola', function ($q) use ($sortDirection) {
        $q->leftJoin('m_pengelola_wisata', 'pengunjung_wisata.id_pengelola_wisata', '=', 'm_pengelola_wisata.id')
          ->orderBy('m_pengelola_wisata.name', $sortDirection);
      })

      ->when($sortField !== 'pengelola', function ($q) use ($sortField, $sortDirection) {
        match ($sortField) {
          'month' => $q->orderBy('month', $sortDirection),
          'visitors' => $q->orderBy('number_of_visitors', $sortDirection),
          'income' => $q->orderBy('gross_income', $sortDirection),
          'status' => $q->orderBy('status', $sortDirection),
          default => $q->orderBy('created_at', 'desc'),
        };
      })

      ->paginate($request->integer('per_page', 10))
      ->withQueryString();

    $stats = cache()->remember(
      "wisata-stats-{$selectedYear}",
      300,
      fn() => [
        'total_visitors' => PengunjungWisata::where('year', $selectedYear)->where('status', 'final')->sum('number_of_visitors'),
        'total_income' => PengunjungWisata::where('year', $selectedYear)->where('status', 'final')->sum('gross_income'),
        'total_count' => PengunjungWisata::where('year', $selectedYear)->where('status', 'final')->count(),
      ]
    );

    $availableYears = cache()->remember('wisata-years', 3600, function () {
      $dbYears = PengunjungWisata::distinct()->pluck('year')->toArray();
      $fixedYears = range(2025, 2021);
      $years = array_unique(array_merge($dbYears, $fixedYears));
      rsort($years);
      return $years;
    });

    return Inertia::render('PengunjungWisata/Index', [
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
    ]);
  }

  public function create()
  {
    return Inertia::render('PengunjungWisata/Create', [
      'pengelolaWisata' => PengelolaWisata::all()
    ]);
  }

  public function store(Request $request)
  {
    $validated = $request->validate([
      'year' => 'required|integer',
      'month' => 'required|integer|min:1|max:12',
      'id_pengelola_wisata' => 'required|exists:m_pengelola_wisata,id',
      'number_of_visitors' => 'required|numeric',
      'gross_income' => 'required|numeric',
    ]);

    PengunjungWisata::create($validated);

    return redirect()->route('pengunjung-wisata.index')->with('success', 'Data Created Successfully');
  }

  public function edit(PengunjungWisata $pengunjungWisata)
  {
    return Inertia::render('PengunjungWisata/Edit', [
      'data' => $pengunjungWisata->load('pengelolaWisata'),
      'pengelolaWisata' => PengelolaWisata::all()
    ]);
  }

  public function update(Request $request, PengunjungWisata $pengunjungWisata)
  {
    $validated = $request->validate([
      'year' => 'required|integer',
      'month' => 'required|integer|min:1|max:12',
      'id_pengelola_wisata' => 'required|exists:m_pengelola_wisata,id',
      'number_of_visitors' => 'required|numeric',
      'gross_income' => 'required|numeric',
    ]);

    $pengunjungWisata->update($validated);

    return redirect()->route('pengunjung-wisata.index')->with('success', 'Data Updated Successfully');
  }

  public function destroy(PengunjungWisata $pengunjungWisata)
  {
    $pengunjungWisata->delete();

    return redirect()->route('pengunjung-wisata.index')->with('success', 'Data Deleted Successfully');
  }

  public function submit(PengunjungWisata $pengunjungWisata)
  {
    $pengunjungWisata->update(['status' => 'waiting_kasi']);
    return redirect()->back()->with('success', 'Laporan berhasil diajukan untuk verifikasi Kasi.');
  }

  public function approve(PengunjungWisata $pengunjungWisata)
  {
    $user = auth()->user();

    if (($user->hasRole('kasi') || $user->hasRole('admin')) && $pengunjungWisata->status === 'waiting_kasi') {
      $pengunjungWisata->update([
        'status' => 'waiting_cdk',
        'approved_by_kasi_at' => now(),
      ]);
      return redirect()->back()->with('success', 'Laporan disetujui dan diteruskan ke KaCDK.');
    }

    if (($user->hasRole('kacdk') || $user->hasRole('admin')) && $pengunjungWisata->status === 'waiting_cdk') {
      $pengunjungWisata->update([
        'status' => 'final',
        'approved_by_cdk_at' => now(),
      ]);
      return redirect()->back()->with('success', 'Laporan telah disetujui secara final.');
    }

    return redirect()->back()->with('error', 'Aksi tidak diijinkan.');
  }

  public function reject(Request $request, PengunjungWisata $pengunjungWisata)
  {
    $request->validate([
      'rejection_note' => 'required|string|max:255',
    ]);

    $pengunjungWisata->update([
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
    return \Maatwebsite\Excel\Facades\Excel::download(new \App\Exports\PengunjungWisataExport($year), 'pengunjung-wisata-' . date('Y-m-d') . '.xlsx');
  }

  /**
   * Download import template.
   */
  public function template()
  {
    return \Maatwebsite\Excel\Facades\Excel::download(new \App\Exports\PengunjungWisataTemplateExport, 'template_import_pengunjung_wisata.xlsx');
  }

  /**
   * Import data from Excel.
   */
  public function import(Request $request)
  {
    $request->validate([
      'file' => 'required|mimes:xlsx,csv,xls',
    ]);

    $import = new \App\Imports\PengunjungWisataImport();

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
      'ids.*' => 'exists:pengunjung_wisata,id', // Adjusted table name if needed, assuming standard naming
    ]);

    $user = auth()->user();
    $count = 0;

    if ($user->hasAnyRole(['kasi', 'kacdk'])) {
      return redirect()->back()->with('error', 'Aksi tidak diijinkan.');
    }

    if ($user->hasAnyRole(['pk', 'peh', 'pelaksana'])) {
      $count = PengunjungWisata::whereIn('id', $request->ids)
        ->where('status', 'draft')
        ->delete();

      if ($count === 0) {
        return redirect()->back()->with('error', 'Hanya data dengan status draft yang dapat dihapus.');
      }

      return redirect()->back()->with('success', $count . ' data berhasil dihapus.');
    }

    if ($user->hasRole('admin')) {
      $count = PengunjungWisata::whereIn('id', $request->ids)->delete();

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
      'ids.*' => 'exists:pengunjung_wisata,id',
    ]);

    $count = PengunjungWisata::whereIn('id', $request->ids)
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
      'ids.*' => 'exists:pengunjung_wisata,id',
    ]);

    $user = auth()->user();
    $count = 0;

    if ($user->hasRole('kasi') || $user->hasRole('admin')) {
      $count = PengunjungWisata::whereIn('id', $request->ids)
        ->where('status', 'waiting_kasi')
        ->update([
          'status' => 'waiting_cdk',
          'approved_by_kasi_at' => now(),
        ]);
    } elseif ($user->hasRole('kacdk') || $user->hasRole('admin')) {
      $count = PengunjungWisata::whereIn('id', $request->ids)
        ->where('status', 'waiting_cdk')
        ->update([
          'status' => 'final',
          'approved_by_cdk_at' => now(),
        ]);
    }

    return redirect()->back()->with('success', $count . ' laporan berhasil disetujui.');
  }
}
