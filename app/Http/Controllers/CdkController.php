<?php

namespace App\Http\Controllers;

use App\Models\Cdk;
use App\Models\Regencies;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CdkController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            abort_if(!auth()->user()->isAdminProvinsi(), 403);
            return $next($request);
        });
    }

    public function index(Request $request)
    {
        $query = Cdk::with('regencies');

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('nama', 'like', '%' . $search . '%')
                  ->orWhere('kode', 'like', '%' . $search . '%')
                  ->orWhere('kepala_nama', 'like', '%' . $search . '%');
            });
        }

        $cdks = $query->paginate(10)->withQueryString();
        $regencies = Regencies::where('province_id', '35')->orderBy('name', 'asc')->get(['id', 'name']);

        return Inertia::render('Cdk/Index', [
            'cdks' => $cdks,
            'regencies' => $regencies,
            'filters' => $request->only(['search']),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'kode' => 'required|string|unique:cdks,kode|max:255',
            'nama' => 'required|string|max:255',
            'kepala_nama' => 'nullable|string|max:255',
            'alamat' => 'nullable|string',
            'is_active' => 'required|boolean',
            'regencies' => 'nullable|array',
            'regencies.*' => 'exists:m_regencies,id',
        ]);

        $cdk = Cdk::create($request->only(['kode', 'nama', 'kepala_nama', 'alamat', 'is_active']));

        if ($request->has('regencies')) {
            $cdk->regencies()->sync($request->regencies);
        }

        return redirect()->route('cdks.index')->with('success', 'CDK berhasil ditambahkan.');
    }

    public function update(Request $request, Cdk $cdk)
    {
        $request->validate([
            'kode' => 'required|string|max:255|unique:cdks,kode,' . $cdk->id,
            'nama' => 'required|string|max:255',
            'kepala_nama' => 'nullable|string|max:255',
            'alamat' => 'nullable|string',
            'is_active' => 'required|boolean',
            'regencies' => 'nullable|array',
            'regencies.*' => 'exists:m_regencies,id',
        ]);

        $cdk->update($request->only(['kode', 'nama', 'kepala_nama', 'alamat', 'is_active']));

        if ($request->has('regencies')) {
            $cdk->regencies()->sync($request->regencies);
        } else {
            $cdk->regencies()->sync([]);
        }

        return redirect()->route('cdks.index')->with('success', 'CDK berhasil diperbarui.');
    }

    public function destroy(Cdk $cdk)
    {
        $cdk->delete();

        return redirect()->route('cdks.index')->with('success', 'CDK berhasil dihapus.');
    }
}
