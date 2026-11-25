<?php

namespace Modules\Penanaman\App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Penanaman;
use App\Models\PenanamanTarget;
use App\Models\Regencies;
use App\Models\SumberDana;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Modules\Penanaman\App\Exports\PenanamanExport;

class PenanamanController extends Controller
{
    private function isProvincialAdmin(): bool
    {
        return $this->user()->hasRole(['admin', 'admin_provinsi']);
    }

    private function assertModuleAccess(): void
    {
        abort_unless(
            $this->isProvincialAdmin() ||
            ($this->user()->hasRole('operator_kabupaten') && $this->user()->regency_id),
            403
        );
    }

    private function reports()
    {
        $this->assertModuleAccess();

        return Penanaman::query()->when(
            !$this->isProvincialAdmin(),
            fn ($query) => $query->where('regency_id', $this->user()->regency_id)
        );
    }

    private function report(int $id): Penanaman
    {
        return $this->reports()->findOrFail($id);
    }

    private function regencyId(Request $request): string
    {
        return $this->isProvincialAdmin()
            ? (string) $request->input('regency_id', '')
            : (string) $this->user()->regency_id;
    }

    private function regencies()
    {
        return Regencies::where('province_id', '35')
            ->when(!$this->isProvincialAdmin(), fn ($query) => $query->whereKey($this->user()->regency_id))
            ->orderBy('name')->get(['id', 'name']);
    }

    public function index(Request $request)
    {
        $query = $this->reports()->with(['regency:id,name', 'district:id,name', 'village:id,name', 'creator:id,name'])
            ->withSum('species as total_batang', 'batang');
        $year = $request->integer('year', now()->year);
        $query->where('year', $year);

        if ($request->filled('month')) {
            $month = $request->validate(['month' => 'integer|between:1,12'])['month'];
            $query->where('month', $month);
        }
        if ($request->filled('status')) {
            $status = $request->validate(['status' => 'in:draft,final'])['status'];
            $query->where('status', $status);
        }
        if ($this->isProvincialAdmin() && $request->filled('regency_id')) {
            $query->where('regency_id', $request->validate([
                'regency_id' => Rule::exists('m_regencies', 'id')->where('province_id', '35'),
            ])['regency_id']);
        }
        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('location_name', 'like', "%{$search}%")
                    ->orWhere('fund_source', 'like', "%{$search}%")
                    ->orWhereHas('species', fn ($species) => $species->where('species_name', 'like', "%{$search}%"));
            });
        }

        $final = $this->reports()->where('year', $year)->where('status', 'final');
        $regencyFilter = $this->isProvincialAdmin() ? $request->input('regency_id') : $this->user()->regency_id;
        if ($regencyFilter) $final->where('regency_id', $regencyFilter);
        $regencies = $this->regencies();
        $targetRegencyIds = $regencyFilter ? [$regencyFilter] : $regencies->pluck('id')->all();
        $targetMissing = count($targetRegencyIds) > 0 && PenanamanTarget::where('year', $year)
            ->whereIn('regency_id', $targetRegencyIds)->count() < count($targetRegencyIds);

        $stats = [
            'target_ha' => PenanamanTarget::where('year', $year)
                ->when($regencyFilter, fn ($q) => $q->where('regency_id', $regencyFilter))->sum('target_ha'),
            'target_batang' => PenanamanTarget::where('year', $year)
                ->when($regencyFilter, fn ($q) => $q->where('regency_id', $regencyFilter))->sum('target_batang'),
            'realization_ha' => (clone $final)->sum('realization_ha'),
            'realization_batang' => DB::table('penanaman_species')
                ->join('penanaman', 'penanaman.id', '=', 'penanaman_species.penanaman_id')
                ->where('penanaman.year', $year)->where('penanaman.status', 'final')
                ->when($regencyFilter, fn ($q) => $q->where('penanaman.regency_id', $regencyFilter))
                ->sum('penanaman_species.batang'),
        ];

        return Inertia::render('Penanaman/Index', [
            'reports' => $query->latest()->paginate(15)->withQueryString(),
            'stats' => $stats,
            'regencies' => $regencies,
            'targetMissing' => $targetMissing,
            'filters' => $request->only(['year', 'month', 'status', 'regency_id', 'search']) + ['year' => $year],
            'isAdmin' => $this->isProvincialAdmin(),
            'canExport' => $this->user()->can('penanaman.export'),
            'canImport' => $this->user()->can('penanaman.import'),
            'canDelete' => $this->user()->can('penanaman.delete'),
            'availableYears' => $this->reports()->whereNotNull('year')->distinct()->orderByDesc('year')->pluck('year'),
            'exportFundSources' => $this->reports()->whereNotNull('fund_source')->distinct()->orderBy('fund_source')->pluck('fund_source'),
            'exportColumns' => collect(PenanamanExport::COLUMN_HEADINGS)
                ->map(fn ($label, $key) => ['key' => $key, 'label' => $label])->values(),
        ]);
    }

    private function formProps(?Penanaman $report = null): array
    {
        $year = $report?->year ?? now()->year;
        $regencyId = $report?->regency_id ?? $this->user()->regency_id;

        return [
            'report' => $report,
            'regencies' => $this->regencies(),
            'sumberDana' => SumberDana::orderBy('name')->get(['name']),
            'target' => $regencyId ? PenanamanTarget::where('regency_id', $regencyId)->where('year', $year)->first() : null,
            'isAdmin' => $this->isProvincialAdmin(),
        ];
    }

    public function create()
    {
        $this->assertModuleAccess();
        return Inertia::render('Penanaman/Form', $this->formProps());
    }

    public function store(Request $request)
    {
        $this->assertModuleAccess();
        $data = $this->validateDraft($request);
        $data['created_by'] = $this->user()->id;
        $data['updated_by'] = $this->user()->id;
        $data['status'] = 'draft';
        $species = $this->normalizeSpecies($data['species'] ?? []);
        unset($data['species']);

        $report = DB::transaction(function () use ($data, $species) {
            $report = Penanaman::create($data);
            $report->species()->createMany($species);
            return $report;
        });

        return redirect()->route('penanaman.show', $report)->with('success', 'Draft penanaman tersimpan.');
    }

    public function show(int $penanaman)
    {
        return Inertia::render('Penanaman/Show', [
            'report' => $this->report($penanaman)->load([
                'regency:id,name', 'district:id,name', 'village:id,name',
                'creator:id,name', 'events.actor:id,name',
            ]),
            'isAdmin' => $this->isProvincialAdmin(),
            'canDelete' => $this->user()->can('penanaman.delete'),
        ]);
    }

    public function edit(int $penanaman)
    {
        $report = $this->report($penanaman);
        abort_unless($report->status === 'draft', 403);
        return Inertia::render('Penanaman/Form', $this->formProps($report));
    }

    public function update(Request $request, int $penanaman)
    {
        $report = $this->report($penanaman);
        abort_unless($report->status === 'draft', 403);
        $data = $this->validateDraft($request);
        $replaceSpecies = array_key_exists('species', $data);
        $species = $replaceSpecies ? $this->normalizeSpecies($data['species']) : [];
        unset($data['species']);
        $data['updated_by'] = $this->user()->id;

        DB::transaction(function () use ($report, $data, $species, $replaceSpecies) {
            $report->update($data);
            if ($replaceSpecies) {
                $before = $report->species()->get(['species_name', 'batang'])->toArray();
                $report->species()->delete();
                $report->species()->createMany($species);
                if ($before !== $species) {
                    activity()->performedOn($report)->causedBy($this->user())
                        ->withProperties(['before' => $before, 'after' => $species])
                        ->log('Rincian tanaman diubah');
                }
            }
        });

        return redirect()->route('penanaman.show', $report)->with('success', 'Draft diperbarui.');
    }

    public function destroy(int $penanaman)
    {
        DB::transaction(function () use ($penanaman) {
            $report = $this->reports()->lockForUpdate()->findOrFail($penanaman);
            abort_unless($report->status === 'draft', 409);
            $report->species()->delete();
            $report->events()->delete();
            $report->delete();
        });

        return redirect()->route('penanaman.index')->with('success', 'Draft penanaman dihapus.');
    }

    public function submit(Request $request, int $penanaman)
    {
        $this->assertModuleAccess();
        $note = $request->validate(['keterangan' => 'nullable|string|min:3|max:1000'])['keterangan']
            ?? 'Laporan diajukan menjadi final.';

        DB::transaction(function () use ($penanaman, $note) {
            $report = $this->reports()->lockForUpdate()->findOrFail($penanaman);
            abort_unless($report->status === 'draft', 409);
            $missing = [];
            foreach (['year', 'month', 'district_id', 'village_id', 'location_name', 'realization_ha', 'fund_source'] as $field) {
                if ($report->{$field} === null || $report->{$field} === '') $missing[$field] = 'Wajib diisi sebelum pengajuan.';
            }
            if (!PenanamanTarget::where('regency_id', $report->regency_id)->where('year', $report->year)->exists()) {
                $missing['target'] = 'Target tahunan wilayah ini belum diisi.';
            }
            if ($missing) throw ValidationException::withMessages($missing);

            $report->update([
                'status' => 'final', 'submitted_at' => now(),
                'submitted_by' => $this->user()->id, 'updated_by' => $this->user()->id,
            ]);
            $report->events()->create([
                'user_id' => $this->user()->id, 'action' => 'submitted',
                'note' => $note, 'created_at' => now(),
            ]);
        });

        return back()->with('success', 'Laporan diajukan dan langsung final.');
    }

    public function returnToDraft(Request $request, int $penanaman)
    {
        $this->assertModuleAccess();
        abort_unless($this->isProvincialAdmin(), 403);
        $note = $request->validate(['alasan' => 'required|string|min:3|max:1000'])['alasan'];

        DB::transaction(function () use ($penanaman, $note) {
            $report = $this->reports()->lockForUpdate()->findOrFail($penanaman);
            abort_unless($report->status === 'final', 409);
            $report->update([
                'status' => 'draft', 'submitted_at' => null,
                'submitted_by' => null, 'updated_by' => $this->user()->id,
            ]);
            $report->events()->create([
                'user_id' => $this->user()->id, 'action' => 'returned',
                'note' => $note, 'created_at' => now(),
            ]);
        });

        return back()->with('success', 'Laporan dikembalikan ke draft.');
    }

    private function validateDraft(Request $request): array
    {
        $regencyId = $this->regencyId($request);
        $input = $request->all();
        $input['regency_id'] = $regencyId;
        $data = Validator::make($input, [
            'year' => 'required|integer|between:2000,2100',
            'month' => 'required|integer|between:1,12',
            'regency_id' => ['required', Rule::exists('m_regencies', 'id')->where('province_id', '35')],
            'district_id' => ['nullable', Rule::exists('m_districts', 'id')->where('regency_id', $regencyId)],
            'village_id' => ['nullable', Rule::exists('m_villages', 'id')->where('district_id', $request->input('district_id'))],
            'location_name' => 'nullable|string|max:255',
            'coordinates' => 'nullable|string|max:100',
            'realization_ha' => 'nullable|numeric|min:0|max:9999999999999.99',
            'fund_source' => ['nullable', 'string', 'max:255', Rule::exists('m_sumber_dana', 'name')],
            'species' => 'nullable|array|max:30',
            'species.*.species_name' => 'nullable|string|max:255',
            'species.*.batang' => 'nullable|integer|min:1',
        ])->validate();
        $data['regency_id'] = $regencyId;
        return $data;
    }

    private function normalizeSpecies(array $rows): array
    {
        return array_map(fn ($row) => [
            'species_name' => isset($row['species_name']) ? trim($row['species_name']) : null,
            'batang' => isset($row['batang']) && $row['batang'] !== '' ? (int) $row['batang'] : null,
        ], $rows);
    }
}
