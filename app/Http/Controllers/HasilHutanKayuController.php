<?php

namespace App\Http\Controllers;

use App\Models\HasilHutanKayu;
use App\Models\HasilHutanKayuDetail;
use App\Models\Kayu;
use App\Traits\HandlesImportFailures;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use App\Actions\SingleWorkflowAction;
use App\Actions\BulkWorkflowAction;
use App\Enums\WorkflowAction;
use App\Exports\HasilHutanKayuExport;
use App\Exports\HasilHutanKayuTemplateExport;
use Illuminate\Validation\Rule;
use App\Models\ImportBatch;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\StagingImport;
use App\Services\Imports\HasilHutanKayuImportValidator;
use Maatwebsite\Excel\Validators\ValidationException;
use App\Imports\HasilHutanKayuImport;
use App\Models\PengelolaHutan;
use App\Models\PengelolaWisata;

class HasilHutanKayuController extends Controller
{
  use HandlesImportFailures;

  
    private function authorizeForestType(string $forestType, string $action)
    {
        $permPrefix = match ($forestType) {
            'Hutan Negara' => 'produksi-hutan-negara',
            'Perhutanan Sosial' => 'produksi-perhutanan-sosial',
            'Hutan Rakyat' => 'produksi-hutan-rakyat',
            default => 'produksi-hutan-negara',
        };
        $this->authorize("{$permPrefix}.{$action}");
    }

    public function index(Request $request)

  {
    $forestType = $request->query('forest_type', 'Hutan Negara');
    $this->authorizeForestType($forestType, 'view');
    $defaultYear = HasilHutanKayu::where('forest_type', $forestType)->max('year') ?? now()->year;
    $selectedYear = $request->integer('year', $defaultYear);

    $sortField = $request->query('sort', 'created_at');
    $sortDirection = $request->query('direction', 'desc');

    $datas = HasilHutanKayu::query()
      ->select([
        'hasil_hutan_kayu.id',
        'hasil_hutan_kayu.year',
        'hasil_hutan_kayu.month',
        'hasil_hutan_kayu.regency_id',
        'hasil_hutan_kayu.district_id',
        'hasil_hutan_kayu.pengelola_hutan_id',
        'hasil_hutan_kayu.pengelola_wisata_id',
        'hasil_hutan_kayu.forest_type',
        'hasil_hutan_kayu.volume_target',
        'hasil_hutan_kayu.status',
        'hasil_hutan_kayu.rejection_note',
        'hasil_hutan_kayu.created_at',
        'hasil_hutan_kayu.created_by',
      ])
      ->with([
        'creator:id,name',
        'regency:id,name',
        'district:id,name',
        'pengelolaHutan:id,name',
        'pengelolaWisata:id,name',
        'details.kayu:id,name'
      ])
      ->where('forest_type', $forestType)
      ->where('year', $selectedYear)

      ->when($request->search, function ($query, $search) {
        $query->where(function ($q) use ($search) {
          $q->whereHas('details.kayu', fn($q2) => $q2->where('name', 'like', "{$search}%"))
            ->orWhereHas('regency', fn($q2) => $q2->where('name', 'like', "{$search}%"))
            ->orWhereHas('district', fn($q2) => $q2->where('name', 'like', "{$search}%"))
            ->orWhereHas('pengelolaHutan', fn($q2) => $q2->where('name', 'like', "{$search}%"))
            ->orWhereHas('pengelolaWisata', fn($q2) => $q2->where('name', 'like', "{$search}%"));
        });
      })

      ->when($sortField === 'location', function ($q) use ($sortDirection) {
        $q->leftJoin('m_regencies', 'hasil_hutan_kayu.regency_id', '=', 'm_regencies.id')
          ->leftJoin('m_districts', 'hasil_hutan_kayu.district_id', '=', 'm_districts.id')
          ->orderByRaw("COALESCE(m_districts.name, m_regencies.name) $sortDirection");
      })
      ->when($sortField === 'pengelola', function ($q) use ($sortDirection) {
        $q->leftJoin('m_pengelola_hutan', 'hasil_hutan_kayu.pengelola_hutan_id', '=', 'm_pengelola_hutan.id')
          ->leftJoin('m_pengelola_wisata', 'hasil_hutan_kayu.pengelola_wisata_id', '=', 'm_pengelola_wisata.id')
          ->orderByRaw("COALESCE(m_pengelola_hutan.name, m_pengelola_wisata.name) $sortDirection");
      })

      ->when(!in_array($sortField, ['location', 'pengelola']), function ($q) use ($sortField, $sortDirection) {
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
          'target' => $q->orderBy('volume_target', $sortDirection),
          // 'realization' => $q->orderBy('volume_realization', $sortDirection), // Complex with hasMany
          'status' => $q->orderBy('status', $sortDirection),
          default => $q->orderBy('created_at', 'desc'),
        };
      })

      ->paginate($request->integer('per_page', 10))
      ->withQueryString();

    // Calculate Stats with caching
    $cacheKey = "hhk-stats-{$forestType}-{$selectedYear}";
    $stats = cache()->remember($cacheKey, 300, function () use ($forestType, $selectedYear) {
      return [
        'total_count' => HasilHutanKayu::where('forest_type', $forestType)
          ->where('year', $selectedYear)
          ->whereNull('deleted_at')
          ->count(),
        'total_volume' => HasilHutanKayu::where('forest_type', $forestType)
          ->where('year', $selectedYear)
          ->where('status', 'final')
          ->whereNull('deleted_at')
          ->sum('volume_target'),
        'total_volume_realization' => (float) HasilHutanKayuDetail::whereHas('hasilHutanKayu', function ($q) use ($forestType, $selectedYear) {
          $q->where('forest_type', $forestType)
            ->where('year', $selectedYear)
            ->where('status', 'final')
            ->whereNull('deleted_at');
        })->sum('volume_realization'),
        'verified_count' => HasilHutanKayu::where('forest_type', $forestType)
          ->where('year', $selectedYear)
          ->where('status', 'final')
          ->whereNull('deleted_at')
          ->count(),
      ];
    });

    // Get available years with caching
    $yearsCacheKey = "hhk-years-{$forestType}";
    $availableYears = cache()->remember($yearsCacheKey, 3600, function () use ($forestType) {
      $dbYears = HasilHutanKayu::where('forest_type', $forestType)
        ->distinct()
        ->pluck('year')
        ->toArray();
      $fixedYears = range(2026, 2021);
      $years = array_unique(array_merge($dbYears, $fixedYears));
      rsort($years);
      return $years;
    });

    return Inertia::render('HasilHutanKayu/Index', [
      'datas' => $datas,
      'forest_type' => $forestType,
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

  public function create(Request $request)
  {
    $forestType = $request->query('forest_type', 'Hutan Negara');
    $this->authorizeForestType($forestType, 'create');
    $this->authorizeForestType($forestType, 'view');

    return Inertia::render('HasilHutanKayu/Create', [
      'forest_type' => $forestType,
      'kayu_list' => Kayu::all(),
      'provinces' => DB::table('m_provinces')->where('id', '35')->get(), // Default Jawa Timur
      'regencies' => DB::table('m_regencies')->where('province_id', '35')->get(),
      'pengelola_hutan_list' => PengelolaHutan::all(),
      'pengelola_wisata_list' => PengelolaWisata::all(),
    ]);
  }

  public function store(Request $request)
  {
    $this->authorizeForestType($request->input('forest_type', 'Hutan Negara'), 'create');
    $validated = $request->validate([
      'year' => 'required|integer|digits:4',
      'month' => 'required|integer|min:1|max:12',
      'province_id' => 'required|exists:m_provinces,id',
      'regency_id' => 'required|exists:m_regencies,id',
      'district_id' => 'nullable|exists:m_districts,id',
      'pengelola_hutan_id' => 'nullable|exists:m_pengelola_hutan,id',
      'pengelola_wisata_id' => 'nullable|exists:m_pengelola_wisata,id',
      'forest_type' => 'required|in:Hutan Negara,Hutan Rakyat,Perhutanan Sosial',
      'volume_target' => 'required|numeric|min:0',
      'details' => 'required|array|min:1',
      'details.*.kayu_id' => 'required|exists:m_kayu,id',
      'details.*.volume_realization' => 'required|numeric|min:0',
    ]);

    // Custom Validation Logic
    if ($validated['forest_type'] === 'Hutan Negara' && empty($validated['pengelola_hutan_id'])) {
      return redirect()->back()->withErrors(['pengelola_hutan_id' => 'Pengelola Hutan wajib diisi untuk Hutan Negara.'])->withInput();
    }
    if ($validated['forest_type'] === 'Hutan Rakyat' && empty($validated['district_id'])) {
      return redirect()->back()->withErrors(['district_id' => 'Kecamatan wajib diisi.'])->withInput();
    }
    if ($validated['forest_type'] === 'Perhutanan Sosial' && empty($validated['pengelola_wisata_id'])) {
      return redirect()->back()->withErrors(['pengelola_wisata_id' => 'Pengelola Wisata wajib diisi.'])->withInput();
    }

    DB::transaction(function () use ($validated) {
      $parent = HasilHutanKayu::create([
        'year' => $validated['year'],
        'month' => $validated['month'],
        'province_id' => $validated['province_id'],
        'regency_id' => $validated['regency_id'],
        'district_id' => ($validated['forest_type'] === 'Hutan Rakyat') ? $validated['district_id'] : null,
        'pengelola_hutan_id' => ($validated['forest_type'] === 'Hutan Negara') ? $validated['pengelola_hutan_id'] : null,
        'pengelola_wisata_id' => ($validated['forest_type'] === 'Perhutanan Sosial') ? $validated['pengelola_wisata_id'] : null,
        'forest_type' => $validated['forest_type'],
        'volume_target' => $validated['volume_target'],
        'status' => 'draft'
      ]);

      foreach ($validated['details'] as $detail) {
        $parent->details()->create([
          'kayu_id' => $detail['kayu_id'],
          'volume_realization' => $detail['volume_realization'],
        ]);
      }
    });

    cache()->forget("hhk-stats-{$validated['forest_type']}-{$validated['year']}");

    return redirect()->route('hasil-hutan-kayu.index', ['forest_type' => $validated['forest_type']])
      ->with('success', 'Data berhasil ditambahkan');
  }

  public function edit(HasilHutanKayu $hasilHutanKayu)
  {
    $this->authorizeForestType($hasilHutanKayu->forest_type, 'edit');
    return Inertia::render('HasilHutanKayu/Edit', [
      'data' => $hasilHutanKayu->load(['details.kayu', 'regency', 'district', 'pengelolaHutan', 'pengelolaWisata']),
      'kayu_list' => Kayu::all(),
      'provinces' => DB::table('m_provinces')->where('id', '35')->get(),
      'regencies' => DB::table('m_regencies')->where('province_id', '35')->get(),
      'pengelola_hutan_list' => PengelolaHutan::all(),
      'pengelola_wisata_list' => PengelolaWisata::all(),
    ]);
  }

  public function update(Request $request, HasilHutanKayu $hasilHutanKayu)
  {
    $this->authorizeForestType($hasilHutanKayu->forest_type, 'edit');
    $validated = $request->validate([
      'year' => 'required|integer|digits:4',
      'month' => 'required|integer|min:1|max:12',
      'province_id' => 'required|exists:m_provinces,id',
      'regency_id' => 'required|exists:m_regencies,id',
      'district_id' => 'nullable|exists:m_districts,id',
      'pengelola_hutan_id' => 'nullable|exists:m_pengelola_hutan,id',
      'pengelola_wisata_id' => 'nullable|exists:m_pengelola_wisata,id',
      'forest_type' => 'required|in:Hutan Negara,Hutan Rakyat,Perhutanan Sosial',
      'volume_target' => 'required|numeric|min:0',
      'details' => 'required|array|min:1',
      'details.*.kayu_id' => 'required|exists:m_kayu,id',
      'details.*.volume_realization' => 'required|numeric|min:0',
    ]);

    // Custom Validation Logic
    if ($validated['forest_type'] === 'Hutan Negara' && empty($validated['pengelola_hutan_id'])) {
      return redirect()->back()->withErrors(['pengelola_hutan_id' => 'Pengelola Hutan wajib diisi untuk Hutan Negara.'])->withInput();
    }
    if ($validated['forest_type'] === 'Hutan Rakyat' && empty($validated['district_id'])) {
      return redirect()->back()->withErrors(['district_id' => 'Kecamatan wajib diisi.'])->withInput();
    }
    if ($validated['forest_type'] === 'Perhutanan Sosial' && empty($validated['pengelola_wisata_id'])) {
      return redirect()->back()->withErrors(['pengelola_wisata_id' => 'Pengelola Wisata wajib diisi.'])->withInput();
    }

    DB::transaction(function () use ($validated, $hasilHutanKayu) {
      $hasilHutanKayu->update([
        'year' => $validated['year'],
        'month' => $validated['month'],
        'province_id' => $validated['province_id'],
        'regency_id' => $validated['regency_id'],
        'district_id' => ($validated['forest_type'] === 'Hutan Rakyat') ? $validated['district_id'] : null,
        'pengelola_hutan_id' => ($validated['forest_type'] === 'Hutan Negara') ? $validated['pengelola_hutan_id'] : null,
        'pengelola_wisata_id' => ($validated['forest_type'] === 'Perhutanan Sosial') ? $validated['pengelola_wisata_id'] : null,
        'forest_type' => $validated['forest_type'],
        'volume_target' => $validated['volume_target'],
      ]);

      $hasilHutanKayu->details()->delete();
      foreach ($validated['details'] as $detail) {
        $hasilHutanKayu->details()->create([
          'kayu_id' => $detail['kayu_id'],
          'volume_realization' => $detail['volume_realization'],
        ]);
      }
    });

    cache()->forget("hhk-stats-{$hasilHutanKayu->forest_type}-{$hasilHutanKayu->year}");

    return redirect()->route('hasil-hutan-kayu.index', ['forest_type' => $hasilHutanKayu->forest_type])
      ->with('success', 'Data berhasil diperbarui');
  }

  public function destroy(HasilHutanKayu $hasilHutanKayu)
  {
    $forestType = $hasilHutanKayu->forest_type;
    $this->authorizeForestType($forestType, 'delete');
    $year = $hasilHutanKayu->year;
    $hasilHutanKayu->delete();

    cache()->forget("hhk-stats-{$forestType}-{$year}");

    return redirect()->route('hasil-hutan-kayu.index', ['forest_type' => $forestType])
      ->with('success', 'Data berhasil dihapus');
  }

  /**
   * Single workflow action.
   */
  public function singleWorkflowAction(Request $request, HasilHutanKayu $hasilHutanKayu, SingleWorkflowAction $action)
  {
    $request->validate([
      'action' => ['required', Rule::enum(WorkflowAction::class)],
      'rejection_note' => 'nullable|string|max:255',
    ]);

    $workflowAction = WorkflowAction::from($request->action);

    match ($workflowAction) {
      WorkflowAction::SUBMIT => $this->authorizeForestType($hasilHutanKayu->forest_type, 'edit'),
      WorkflowAction::APPROVE, WorkflowAction::REJECT => $this->authorizeForestType($hasilHutanKayu->forest_type, 'approve'),
      WorkflowAction::DELETE => $this->authorizeForestType($hasilHutanKayu->forest_type, 'delete'),
    };

    if ($workflowAction === WorkflowAction::REJECT && !$request->filled('rejection_note')) {
      return redirect()->back()->with('error', 'Catatan penolakan wajib diisi.');
    }

    $extraData = [];
    if ($request->filled('rejection_note')) {
      $extraData['rejection_note'] = $request->rejection_note;
    }

    $success = $action->execute(
      model: $hasilHutanKayu,
      action: $workflowAction,
      user: auth()->user(),
      extraData: $extraData
    );

    if ($success) {
      cache()->forget("hhk-stats-{$hasilHutanKayu->forest_type}-{$hasilHutanKayu->year}");

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
    $forestType = $request->query('forest_type', 'Hutan Negara');
    $this->authorizeForestType($forestType, 'export');
    $this->authorizeForestType($forestType, 'view');
    $year = $request->query('year');
    return Excel::download(new HasilHutanKayuExport($forestType, $year), 'hasil-hutan-kayu-' . date('Y-m-d') . '.xlsx');
  }

  public function template(Request $request)
  {
    $forestType = $request->query('forest_type', 'Hutan Negara');
    $this->authorizeForestType($forestType, 'create');
    $this->authorizeForestType($forestType, 'view');
    return Excel::download(new HasilHutanKayuTemplateExport($forestType), 'template_import_hasil_hutan_kayu.xlsx');
  }

  public function previewImport(Request $request)
  {
      $this->authorizeForestType($request->input('forest_type', 'Hutan Negara'), 'import');
      $request->validate([
          'file' => 'required|mimes:xlsx,csv,xls',
          'forest_type' => 'required'
      ]);
      
      $batch = ImportBatch::create([
          'user_id' => Auth::id(),
          'module_name' => 'hasil-hutan-kayu|' . $request->input('forest_type'),
          'filename' => $request->file('file')->getClientOriginalName(),
          'status' => 'pending',
      ]);

      Excel::import(
          new StagingImport($batch->id, new HasilHutanKayuImportValidator($request->input('forest_type'))), 
          $request->file('file')
      );

      return redirect()->route('hasil-hutan-kayu.show-preview', $batch->id);
  }

  public function showPreview(ImportBatch $batch)
  {
      if (!str_starts_with($batch->module_name, 'hasil-hutan-kayu|')) abort(404);

      $forestType = explode('|', $batch->module_name)[1];
      $this->authorizeForestType($forestType, 'import');

      $rows = $batch->stagingRows()->paginate(50);
      
      return Inertia::render('HasilHutanKayu/ImportPreview', [
          'batch' => $batch,
          'rows' => $rows,
          'forestType' => $forestType
      ]);
  }

  public function commitImport(ImportBatch $batch)
  {
      if (!str_starts_with($batch->module_name, 'hasil-hutan-kayu|') || $batch->status !== 'pending') abort(400);
      
      $forestType = explode('|', $batch->module_name)[1];
      $this->authorizeForestType($forestType, 'import');
      
      $batch->update(['status' => 'processing']);
      
      $validRows = $batch->stagingRows()->where('status', 'valid')->get();
      $importedCount = 0;

      $orderedNames = [
          'Jati', 'Sengon', 'Mahoni', 'Gmelina', 'Sonokeling', 'Pinus', 
          'Akasia', 'Mindi', 'Balsa', 'Jabon', 'Kayu Lainnya'
      ];
      $allKayu = Kayu::all()->sortBy(function ($model) use ($orderedNames) {
          $index = array_search($model->name, $orderedNames);
          return $index === false ? 9999 + $model->id : $index;
      })->values();

      foreach ($validRows as $stagingRow) {
          $row = $stagingRow->data_payload;
          
          $regency = DB::table('m_regencies')
              ->where('province_id', 35)
              ->where('name', 'like', '%' . $row['nama_kabupaten'] . '%')
              ->first();
          
          $districtId = null;
          if ($forestType === 'Hutan Rakyat' && !empty($row['nama_kecamatan'])) {
              $district = DB::table('m_districts')
                  ->where('regency_id', $regency->id)
                  ->where('name', 'like', '%' . $row['nama_kecamatan'] . '%')
                  ->first() ?? DB::table('m_districts')->where('name', 'like', '%' . $row['nama_kecamatan'] . '%')->first();
              $districtId = $district ? $district->id : null;
          }

          $pengelolaWisataId = null;
          if ($forestType === 'Perhutanan Sosial' && !empty($row['nama_pengelola_wisata'])) {
              $pw = PengelolaWisata::where('name', 'like', '%' . $row['nama_pengelola_wisata'] . '%')->first();
              $pengelolaWisataId = $pw ? $pw->id : null;
          }

          $pengelolaHutanId = null;
          if ($forestType === 'Hutan Negara' && !empty($row['nama_pengelola_hutan'])) {
              $ph = PengelolaHutan::firstOrCreate(['name' => $row['nama_pengelola_hutan']]);
              $pengelolaHutanId = $ph->id;
          }

          DB::transaction(function () use ($row, $regency, $districtId, $pengelolaHutanId, $pengelolaWisataId, $forestType, $allKayu) {
              $parent = HasilHutanKayu::create([
                  'year' => $row['tahun'],
                  'month' => $row['bulan_angka'],
                  'province_id' => 35,
                  'regency_id' => $regency->id,
                  'district_id' => $districtId,
                  'pengelola_hutan_id' => $pengelolaHutanId,
                  'pengelola_wisata_id' => $pengelolaWisataId,
                  'forest_type' => $forestType,
                  'volume_target' => $row['total_target_m3'],
                  'status' => 'draft',
                  'created_by' => Auth::id(),
              ]);

              foreach ($allKayu as $kayu) {
                  $slugName = \Illuminate\Support\Str::slug($kayu->name, '_');
                  $realizationKey = $slugName . '_realisasi';
                  if (array_key_exists($realizationKey, $row)) {
                      $realization = $row[$realizationKey] ?? 0;
                      if ($realization >= 0) {
                          $parent->details()->create([
                              'kayu_id' => $kayu->id,
                              'volume_realization' => $realization,
                          ]);
                      }
                  }
              }
          });
          
          $importedCount++;
      }

      $batch->update(['status' => 'completed']);
      
      foreach (range(date('Y'), date('Y') - 5) as $y) {
          cache()->forget("hhk-stats-{$forestType}-{$y}");
      }
      
      return redirect()->route('hasil-hutan-kayu.index', ['forest_type' => $forestType])->with('success', "Berhasil mengimport {$importedCount} data Hasil Hutan Kayu yang valid.");
  }

  public function import(Request $request)
  {
    $this->authorizeForestType($request->input('forest_type', 'Hutan Negara'), 'import');
    $request->validate([
      'file' => 'required|mimes:xlsx,csv,xls',
      'forest_type' => 'required',
    ]);

    $import = new HasilHutanKayuImport($request->forest_type);

    try {
      Excel::import($import, $request->file('file'));
    } catch (ValidationException $e) {
      return redirect()->back()->with('import_errors', $this->mapImportFailures($e->failures()));
    }

    // Check for failures if SkipsOnFailure is used (which doesn't throw exception usually, but accumulates)
    if ($import->failures()->isNotEmpty()) {
      return redirect()->back()->with('import_errors', $this->mapImportFailures($import->failures()));
    }

    // Clear cache for last 5 years as import might contain multiple years
    foreach (range(date('Y'), date('Y') - 5) as $y) {
      cache()->forget("hhk-stats-{$request->forest_type}-{$y}");
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
      'ids.*' => 'exists:hasil_hutan_kayu,id',
      'action' => ['required', Rule::enum(WorkflowAction::class)],
      'rejection_note' => 'nullable|string|max:255',
    ]);

    $workflowAction = WorkflowAction::from($request->action);

    match ($workflowAction) {
      WorkflowAction::SUBMIT => $this->authorizeForestType($hasilHutanKayu->forest_type, 'edit'),
      WorkflowAction::APPROVE, WorkflowAction::REJECT => $this->authorizeForestType($hasilHutanKayu->forest_type, 'approve'),
      WorkflowAction::DELETE => $this->authorizeForestType($hasilHutanKayu->forest_type, 'delete'),
    };

    if ($workflowAction === WorkflowAction::REJECT && !$request->filled('rejection_note')) {
      return redirect()->back()->with('error', 'Catatan penolakan wajib diisi.');
    }

    $extraData = [];
    if ($request->filled('rejection_note')) {
      $extraData['rejection_note'] = $request->rejection_note;
    }

    $count = $action->execute(
      model: HasilHutanKayu::class,
      action: $workflowAction,
      ids: $request->ids,
      user: auth()->user(),
      extraData: $extraData
    );

    $affected = HasilHutanKayu::withTrashed()->whereIn('id', $request->ids)->get();
    foreach ($affected as $item) {
      cache()->forget("hhk-stats-{$item->forest_type}-{$item->year}");
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



