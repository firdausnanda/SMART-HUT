<?php

namespace Modules\Penanaman\App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\PenanamanTarget;
use App\Models\Regencies;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class PenanamanTargetController extends Controller
{
    private function isAdmin(): bool
    {
        return $this->user()->hasRole(['admin', 'admin_provinsi']);
    }

    private function authorizeRegion(string $regencyId): void
    {
        abort_unless(
            $this->isAdmin() ||
            ($this->user()->hasRole('operator_kabupaten') && $this->user()->regency_id === $regencyId),
            403
        );
    }

    public function index(Request $request)
    {
        abort_unless(
            $this->isAdmin() ||
            ($this->user()->hasRole('operator_kabupaten') && $this->user()->regency_id),
            403
        );
        $year = $request->integer('year', now()->year);
        abort_unless($year >= 2000 && $year <= 2100, 422);
        $regencies = Regencies::where('province_id', '35')
            ->when(!$this->isAdmin(), fn ($q) => $q->whereKey($this->user()->regency_id))
            ->orderBy('name')->get(['id', 'name']);
        $targets = PenanamanTarget::where('year', $year)
            ->when(!$this->isAdmin(), fn ($q) => $q->where('regency_id', $this->user()->regency_id))
            ->get()->keyBy('regency_id');

        return Inertia::render('Penanaman/Targets', [
            'year' => $year,
            'regencies' => $regencies,
            'targets' => $targets,
            'isAdmin' => $this->isAdmin(),
        ]);
    }

    public function update(Request $request, string $regency, int $year)
    {
        $this->authorizeRegion($regency);
        abort_unless($year >= 2000 && $year <= 2100, 422);
        $data = $request->validate([
            'target_ha' => 'required|numeric|min:0|max:9999999999999.99',
            'target_batang' => 'sometimes|integer|min:0',
        ]);
        validator(['regency' => $regency], [
            'regency' => Rule::exists('m_regencies', 'id')->where('province_id', '35'),
        ])->validate();

        $current = PenanamanTarget::where('regency_id', $regency)->where('year', $year)->first();
        $data['target_batang'] = $data['target_batang'] ?? $current?->target_batang ?? 0;

        PenanamanTarget::updateOrCreate(
            ['regency_id' => $regency, 'year' => $year],
            $data + ['updated_by' => $this->user()->id]
        );

        return back()->with('success', 'Target tahunan tersimpan.');
    }

    public function value(Request $request)
    {
        $data = $request->validate([
            'regency_id' => ['required', Rule::exists('m_regencies', 'id')->where('province_id', '35')],
            'year' => 'required|integer|between:2000,2100',
        ]);
        $this->authorizeRegion($data['regency_id']);

        return response()->json(PenanamanTarget::where('regency_id', $data['regency_id'])
            ->where('year', $data['year'])->first(['target_ha', 'target_batang']));
    }
}
