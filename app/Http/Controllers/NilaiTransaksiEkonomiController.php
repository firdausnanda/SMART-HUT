<?php

namespace App\Http\Controllers;

use App\Models\NilaiTransaksiEkonomi;
use App\Models\Commodity;
use App\Models\NilaiTransaksiEkonomiDetail;
use App\Actions\BulkWorkflowAction;
use App\Actions\SingleWorkflowAction;
use App\Enums\WorkflowAction;
use App\Enums\Satuan;
use App\Exports\NilaiTransaksiEkonomiExport;
use App\Exports\NilaiTransaksiEkonomiTemplateExport;
use App\Imports\NilaiTransaksiEkonomiImport;
use Illuminate\Validation\Rule;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\ImportBatch;
use App\Imports\StagingImport;
use App\Services\Imports\NilaiTransaksiEkonomiImportValidator;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Validators\ValidationException;

class NilaiTransaksiEkonomiController extends Controller
{
  use \App\Traits\HandlesImportFailures;

  public function index(Request $request)
  {
    $selectedYear = $request->query('year')
      ?? NilaiTransaksiEkonomi::max('year')
      ?? date('Y');

    $sort = $request->query('sort');
    $direction = $request->query('direction', 'asc');

    $datas = NilaiTransaksiEkonomi::query()
      ->with([
        'creator:id,name',
        'regency_rel:id,name',
        'district_rel:id,name',
        'village_rel:id,name',
        'details:id,nilai_transaksi_ekonomi_id,commodity_id,volume_produksi,satuan',
        'details.commodity' => fn($q) => $q->withoutGlobalScope('not_nilai_transaksi_ekonomi')->select('id', 'name'),
      ])
      ->where('year', $selectedYear)
      ->when($request->only_mine === 'true' || $request->only_mine === true, function ($q) {
        $q->where('created_by', auth()->id());
      })

      ->when($request->search, function ($q, $search) {
        $q->where(function ($q) use ($search) {
          $q->where('nama_kth', 'like', "{$search}%")
            ->orWhereHas('village_rel', fn($q) => $q->where('name', 'like', "{$search}%"))
            ->orWhereHas('district_rel', fn($q) => $q->where('name', 'like', "{$search}%"))
            ->orWhereHas('regency_rel', fn($q) => $q->where('name', 'like', "{$search}%"))
            ->orWhereHas('details.commodity', fn($q) => $q->where('name', 'like', "{$search}%"));
        });
      })
      ->when($request->month, function ($q, $month) {
        $q->where('month', $month);
      })
      ->when($request->status, function ($q, $status) {
        $q->where('status', $status);
      })
      ->when($request->regency_id, function ($q, $regencyId) {
        $q->where('regency_id', $regencyId);
      })
      ->when($request->district_id, function ($q, $districtId) {
        $q->where('district_id', $districtId);
      })
      ->when($request->village_id, function ($q, $villageId) {
        $q->where('village_id', $villageId);
      })
      ->when($request->commodity_id, function ($q, $commodityId) {
        $q->whereHas('details', function ($q) use ($commodityId) {
          $q->where('commodity_id', $commodityId);
        });
      })

      ->when($request->sort, function ($q) use ($request) {
        $user = auth()->user();
        $sortField = $request->query('sort', 'created_at');
        $sortDirection = $request->query('direction', 'desc');

        if ($sortField === 'created_at' && $sortDirection === 'desc') {
          if ($user->hasRole('kacdk')) {
            $q->orderByRaw("CASE WHEN status = 'waiting_cdk' THEN 0 ELSE 1 END");
          } elseif ($user->hasRole('kasi')) {
            $q->orderByRaw("CASE WHEN status = 'waiting_kasi' THEN 0 ELSE 1 END");
          } else {
            $q->orderByRaw("CASE WHEN status = 'rejected' THEN 0 WHEN status = 'draft' THEN 1 ELSE 2 END");
          }
        }

        match ($request->sort) {
          'nama_kth' => $q->orderBy('nama_kth', $request->direction),
          'nilai' => $q->orderBy('total_nilai_transaksi', $request->direction),
          'status' => $q->orderBy('status', $request->direction),
          'month' => $q->orderBy('month', $request->direction),
          'user' => $q->select('nilai_transaksi_ekonomi.*')
            ->leftJoin('users', 'nilai_transaksi_ekonomi.created_by', '=', 'users.id')
            ->orderBy('users.name', $request->direction),
          default => $q->latest(),
        };
      }, function ($q) {
        $user = auth()->user();
        if ($user->hasRole('kacdk')) {
          $q->orderByRaw("CASE WHEN status = 'waiting_cdk' THEN 0 ELSE 1 END");
        } elseif ($user->hasRole('kasi')) {
          $q->orderByRaw("CASE WHEN status = 'waiting_kasi' THEN 0 ELSE 1 END");
        } else {
          $q->orderByRaw("CASE WHEN status = 'rejected' THEN 0 WHEN status = 'draft' THEN 1 ELSE 2 END");
        }
        $q->latest();
      })

      ->paginate($request->integer('per_page', 10))
      ->withQueryString();

    $stats = cache()->remember(
      "nilai-transaksi-stats-{$selectedYear}",
      300,
      function () use ($selectedYear) {
        return [
          'total_transaksi' => NilaiTransaksiEkonomi::where('year', $selectedYear)->where('status', 'final')->count(),
          'total_nilai' => NilaiTransaksiEkonomi::where('year', $selectedYear)->where('status', 'final')->sum('total_nilai_transaksi'),
          'total_volume' => NilaiTransaksiEkonomiDetail::whereHas(
            'nilaiTransaksiEkonomi',
            fn($q) => $q->where('year', $selectedYear)->where('status', 'final')
          )->sum('volume_produksi'),
          'total_kth' => NilaiTransaksiEkonomi::where('year', $selectedYear)->where('status', 'final')->distinct()->count('nama_kth'),
        ];
      }
    );

    $availableYears = cache()->remember('nilai-transaksi-years', 3600, function () {
      $dbYears = NilaiTransaksiEkonomi::distinct()->pluck('year')->toArray();
      $fixedYears = range(2025, 2021);
      $years = array_unique(array_merge($dbYears, $fixedYears));
      rsort($years);
      return $years;
    });

    $commodities = Commodity::withoutGlobalScope('not_nilai_transaksi_ekonomi')
      ->where('is_nilai_transaksi_ekonomi', true)
      ->whereIn('id', function($query) {
          $query->select('commodity_id')
                ->from('nilai_transaksi_ekonomi_details');
      })
      ->orderBy('name')
      ->get();

    return Inertia::render('NilaiTransaksiEkonomi/Index', [
      'datas' => $datas,
      'stats' => $stats,
      'commodities' => $commodities,
      'filters' => [
        'year' => (int) $selectedYear,
        'search' => $request->search,
        'sort' => $sort,
        'direction' => $direction,
        'per_page' => (int) $request->query('per_page', 10),
        'only_mine' => $request->boolean('only_mine'),
        'month' => $request->month,
        'status' => $request->status,
        'regency_id' => $request->regency_id,
        'district_id' => $request->district_id,
        'village_id' => $request->village_id,
        'commodity_id' => $request->commodity_id,
      ],
      'availableYears' => $availableYears,
    ]);
  }

  public function bulkWorkflowAction(Request $request, BulkWorkflowAction $action)
  {
    $request->validate([
      'ids' => 'required|array',
      'ids.*' => 'exists:nilai_transaksi_ekonomi,id',
      'action' => 'required|string|in:submit,approve,reject,delete',
      'rejection_note' => 'nullable|string|required_if:action,reject',
    ]);

    $data = [
      'ids' => $request->ids,
      'action' => WorkflowAction::tryFrom($request->action),
      'rejection_note' => $request->rejection_note,
    ];

    match ($data['action']) {
      WorkflowAction::SUBMIT => $this->authorize('nilai-transaksi-ekonomi.edit'),
      WorkflowAction::APPROVE, WorkflowAction::REJECT => $this->authorize('nilai-transaksi-ekonomi.approve'),
      WorkflowAction::DELETE => $this->authorize('nilai-transaksi-ekonomi.delete'),
    };

    try {
      $count = $action->execute(
        model: NilaiTransaksiEkonomi::class,
        action: $data['action'],
        ids: $data['ids'],
        user: auth()->user(),
        extraData: ['rejection_note' => $data['rejection_note']]
      );
      $message = match ($request->action) {
        'delete' => "$count data berhasil dihapus.",
        'submit' => "$count data berhasil disubmit.",
        'approve' => "$count data berhasil disetujui.",
        'reject' => "$count data berhasil ditolak.",
      };
      return back()->with('success', $message);
    } catch (\Exception $e) {
      return back()->with('error', $e->getMessage());
    }
  }

  public function create()
  {
    return Inertia::render('NilaiTransaksiEkonomi/Create', [
      'commodities' => Commodity::withoutGlobalScope('not_nilai_transaksi_ekonomi')
        ->where('is_nilai_transaksi_ekonomi', true)
        ->get(),
      'satuanOptions' => collect(Satuan::cases())->map(fn($s) => ['value' => $s->value, 'label' => $s->label()]),
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
      'nama_kth' => 'required|string|max:255',
      'details' => 'required|array|min:1',
      'details.*.commodity_id' => 'required|exists:m_commodities,id',
      'details.*.volume_produksi' => 'required|numeric|min:0',
      'details.*.satuan' => 'required|string|max:50',
      'details.*.nilai_transaksi' => 'required|numeric|min:0',
    ]);

    $validated['status'] = 'draft';
    $validated['created_by'] = Auth::id();

    $totalValue = collect($request->details)->sum('nilai_transaksi');
    $validated['total_nilai_transaksi'] = $totalValue;

    $record = NilaiTransaksiEkonomi::create($validated);

    foreach ($request->details as $detail) {
      $record->details()->create([
        'commodity_id' => $detail['commodity_id'],
        'volume_produksi' => $detail['volume_produksi'],
        'satuan' => $detail['satuan'],
        'nilai_transaksi' => $detail['nilai_transaksi'],
      ]);
    }

    return redirect()->route('nilai-transaksi-ekonomi.index')->with('success', 'Data transaksi berhasil ditambahkan.');
  }

  public function edit(NilaiTransaksiEkonomi $nilai_transaksi_ekonomi)
  {
    $nilai_transaksi_ekonomi->load(['regency_rel', 'district_rel', 'village_rel', 'details.commodity']);

    return Inertia::render('NilaiTransaksiEkonomi/Edit', [
      'data' => $nilai_transaksi_ekonomi,
      'commodities' => Commodity::withoutGlobalScope('not_nilai_transaksi_ekonomi')
        ->where('is_nilai_transaksi_ekonomi', true)
        ->get(),
      'satuanOptions' => collect(Satuan::cases())->map(fn($s) => ['value' => $s->value, 'label' => $s->label()]),
    ]);
  }

  public function update(Request $request, NilaiTransaksiEkonomi $nilai_transaksi_ekonomi)
  {
    if (!in_array($nilai_transaksi_ekonomi->status, ['draft', 'rejected'])) {
      return redirect()->back()->with('error', 'Data tidak dapat diedit karena sedang dalam proses verifikasi atau sudah final.');
    }

    $validated = $request->validate([
      'year' => 'required|integer',
      'month' => 'required|integer|min:1|max:12',
      'province_id' => 'nullable|exists:m_provinces,id',
      'regency_id' => 'nullable|exists:m_regencies,id',
      'district_id' => 'nullable|exists:m_districts,id',
      'village_id' => 'nullable|exists:m_villages,id',
      'nama_kth' => 'required|string|max:255',
      'details' => 'required|array|min:1',
      'details.*.commodity_id' => 'required|exists:m_commodities,id',
      'details.*.volume_produksi' => 'required|numeric|min:0',
      'details.*.satuan' => 'required|string|max:50',
      'details.*.nilai_transaksi' => 'required|numeric|min:0',
    ]);

    $validated['updated_by'] = Auth::id();
    $totalValue = collect($request->details)->sum('nilai_transaksi');
    $validated['total_nilai_transaksi'] = $totalValue;

    $nilai_transaksi_ekonomi->update($validated);
    $nilai_transaksi_ekonomi->details()->delete();

    foreach ($request->details as $detail) {
      $nilai_transaksi_ekonomi->details()->create([
        'commodity_id' => $detail['commodity_id'],
        'volume_produksi' => $detail['volume_produksi'],
        'satuan' => $detail['satuan'],
        'nilai_transaksi' => $detail['nilai_transaksi'],
      ]);
    }

    return redirect()->route('nilai-transaksi-ekonomi.index')->with('success', 'Data transaksi berhasil diperbarui.');
  }

  public function destroy(NilaiTransaksiEkonomi $nilai_transaksi_ekonomi)
  {
    $nilai_transaksi_ekonomi->delete();
    return redirect()->route('nilai-transaksi-ekonomi.index')->with('success', 'Data transaksi berhasil dihapus.');
  }

  /**
   * Handle single workflow action.
   */
  public function singleWorkflowAction(Request $request, NilaiTransaksiEkonomi $nilai_transaksi_ekonomi, SingleWorkflowAction $action)
  {
    $request->validate([
      'action' => ['required', Rule::enum(WorkflowAction::class)],
      'rejection_note' => 'nullable|string|max:255',
    ]);

    $workflowAction = WorkflowAction::from($request->action);

    match ($workflowAction) {
      WorkflowAction::SUBMIT => $this->authorize('nilai-transaksi-ekonomi.edit'),
      WorkflowAction::APPROVE, WorkflowAction::REJECT => $this->authorize('nilai-transaksi-ekonomi.approve'),
      WorkflowAction::DELETE => $this->authorize('nilai-transaksi-ekonomi.delete'),
    };

    if ($workflowAction === WorkflowAction::REJECT && !$request->filled('rejection_note')) {
      return redirect()->back()->with('error', 'Catatan penolakan wajib diisi.');
    }

    $extraData = [];
    if ($request->filled('rejection_note')) {
      $extraData['rejection_note'] = $request->rejection_note;
    }

    if (!$nilai_transaksi_ekonomi->exists) {
        $nilai_transaksi_ekonomi = NilaiTransaksiEkonomi::findOrFail($request->route('nilai_transaksi_ekonomi'));
    }

    $success = $action->execute(
      model: $nilai_transaksi_ekonomi,
      action: $workflowAction,
      user: auth()->user(),
      extraData: $extraData
    );

    if ($success) {
      cache()->forget("nilai-transaksi-stats-{$nilai_transaksi_ekonomi->year}");

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
    return Excel::download(new NilaiTransaksiEkonomiExport($year), 'nilai-transaksi-ekonomi-' . date('Y-m-d') . '.xlsx');
  }

  public function template()
  {
    return Excel::download(new NilaiTransaksiEkonomiTemplateExport, 'template_import_nilai_transaksi_ekonomi.xlsx');
  }

  public function previewImport(Request $request)
  {
      $this->authorize('nilai-transaksi-ekonomi.import');
      $request->validate(['file' => 'required|mimes:xlsx,csv,xls']);
      
      $batch = ImportBatch::create([
          'user_id' => Auth::id(),
          'module_name' => 'nilai-transaksi-ekonomi',
          'filename' => $request->file('file')->getClientOriginalName(),
          'status' => 'pending',
      ]);

      Excel::import(
          new StagingImport($batch->id, new NilaiTransaksiEkonomiImportValidator()), 
          $request->file('file')
      );

      return redirect()->route('nilai-transaksi-ekonomi.show-preview', $batch->id);
  }

  public function showPreview(ImportBatch $batch)
  {
      if ($batch->module_name !== 'nilai-transaksi-ekonomi') abort(404);
      $this->authorize('nilai-transaksi-ekonomi.import');

      $rows = $batch->stagingRows()->paginate(50);
      
      return \Inertia\Inertia::render('NilaiTransaksiEkonomi/ImportPreview', [
          'batch' => $batch,
          'rows' => $rows
      ]);
  }

  public function commitImport(ImportBatch $batch)
  {
      if ($batch->module_name !== 'nilai-transaksi-ekonomi' || $batch->status !== 'pending') abort(400);
      $this->authorize('nilai-transaksi-ekonomi.import');
      
      $batch->update(['status' => 'processing']);
      
      $validRows = $batch->stagingRows()->where('status', 'valid')->get();
      $importedCount = 0;

      foreach ($validRows as $stagingRow) {
          $row = $stagingRow->data_payload;
          
          $kabupatenInfo = $row['nama_kabupaten'] ?? $row['kabupatenkota'] ?? null;
          $kecamatanInfo = $row['nama_kecamatan'] ?? $row['kecamatan'] ?? null;
          $desaInfo = $row['nama_desa'] ?? $row['desa'] ?? null;
          $bulanInfo = $row['bulan_1_12'] ?? $row['bulan'] ?? null;

          $regency = DB::table('m_regencies')
              ->whereRaw('LOWER(name) LIKE ?', ['%' . strtolower(trim($kabupatenInfo)) . '%'])
              ->first();

          $district = DB::table('m_districts')
              ->where('regency_id', $regency->id)
              ->whereRaw('LOWER(name) LIKE ?', ['%' . strtolower(trim($kecamatanInfo)) . '%'])
              ->first();

          $village = DB::table('m_villages')
              ->where('district_id', $district->id)
              ->whereRaw('LOWER(name) LIKE ?', ['%' . strtolower(trim($desaInfo)) . '%'])
              ->first();

          $transaction = NilaiTransaksiEkonomi::firstOrCreate([
              'year' => $row['tahun'],
              'month' => $bulanInfo,
              'nama_kth' => $row['nama_kth'],
              'province_id' => 35,
              'regency_id' => $regency->id,
              'district_id' => $district->id,
              'village_id' => $village->id,
          ], [
              'status' => 'draft',
              'created_by' => Auth::id(),
              'total_nilai_transaksi' => 0,
          ]);

          if (!$transaction->wasRecentlyCreated) {
              $transaction->update(['status' => 'draft']);
          }

          $commodities = array_map('trim', explode(',', (string) $row['komoditas']));
          $volumes = array_map('trim', explode(',', (string) $row['volume_produksi']));
          $satuans = array_map('trim', explode(',', (string) $row['satuan']));
          $nilais = array_map('trim', explode(',', (string) $row['nilai_transaksi_rp']));

          $count = count($commodities);
          $detailsToInsert = [];
          $totalNilai = 0;
          $now = now();

          for ($i = 0; $i < $count; $i++) {
              $commodityName = $commodities[$i] ?? null;
              if (!$commodityName) continue;

              $volumeStr = $volumes[$i] ?? '0';
              if ($volumeStr !== '') {
                  $volumeStr = str_replace([' ', "\r", "\n"], '', $volumeStr);
                  $volume = (float) str_replace(',', '.', $volumeStr);
              } else {
                  $volume = 0;
              }

              $nilaiStr = $nilais[$i] ?? '0';
              if ($nilaiStr !== '') {
                  $nilaiStr = str_replace([' ', "\r", "\n", '.'], '', $nilaiStr);
                  $nilaiStr = str_replace(',', '.', $nilaiStr);
                  $nilai = (float) $nilaiStr;
              } else {
                  $nilai = 0;
              }

              $satuanRaw = trim($satuans[$i] ?? '-');
              $satuan = $this->mapSatuan($satuanRaw);

              $commodity = \App\Models\Commodity::withoutGlobalScope('not_nilai_transaksi_ekonomi')
                  ->where('name', trim($commodityName))
                  ->first();

              if (!$commodity) continue;

              $detailsToInsert[] = [
                  'nilai_transaksi_ekonomi_id' => $transaction->id,
                  'commodity_id' => $commodity->id,
                  'volume_produksi' => $volume,
                  'satuan' => $satuan,
                  'nilai_transaksi' => $nilai,
                  'created_at' => $now,
                  'updated_at' => $now,
              ];

              $totalNilai += $nilai;
          }

          if ($totalNilai > 0 || $transaction->total_nilai_transaksi != $totalNilai) {
              $transaction->total_nilai_transaksi += $totalNilai;
              $transaction->save();
          }

          if (!empty($detailsToInsert)) {
              NilaiTransaksiEkonomiDetail::insert($detailsToInsert);
          }
          
          $importedCount++;
      }

      $batch->update(['status' => 'completed']);
      cache()->forget('nilai-transaksi-years');
      
      return redirect()->route('nilai-transaksi-ekonomi.index')->with('success', "Berhasil mengimport {$importedCount} data Nilai Transaksi Ekonomi yang valid.");
  }

  private function mapSatuan($satuanRaw)
  {
      $map = [
          'kg' => ['kg', 'kilogram (kg)', 'lg', 'kilogram'],
          'm3' => ['m3', 'meter kubik (m3)', 'meter kubik (m³)', 'meter kubik'],
          'batang' => ['batang', 'batangan', 'bantangan', 'btg'],
          'ton' => ['ton'],
          'pcs' => ['pcs'],
          'buah' => ['buah'],
          'bibit' => ['tanaman', 'bibit'],
          'stup' => ['stup'],
          'orang' => ['orang'],
          'ekor' => ['ekor'],
          'liter' => ['liter'],
          'ikat' => ['ikat'],
          'butir' => ['butir']
      ];
      $lower = strtolower($satuanRaw);
      foreach ($map as $canonical => $variations) {
          if (in_array($lower, $variations)) {
              return $canonical;
          }
      }
      return 'lainnya';
  }

  public function import(Request $request)
  {
    $request->validate(['file' => 'required|mimes:xlsx,csv,xls']);
    $import = new NilaiTransaksiEkonomiImport();

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
}

