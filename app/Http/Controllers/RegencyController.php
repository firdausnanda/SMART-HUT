<?php

namespace App\Http\Controllers;

use App\Models\Regencies;
use App\Models\Provinces;
use Illuminate\Http\Request;
use Inertia\Inertia;

class RegencyController extends Controller
{
  public function __construct()
  {
    $this->middleware('permission:master.view')->only(['index', 'show', 'create', 'edit']);
    $this->middleware('permission:master.create')->only(['store']);
    $this->middleware('permission:master.edit')->only(['update']);
    $this->middleware('permission:master.delete')->only(['destroy']);
  }

  public function index(Request $request)
  {
    $query = Regencies::with('province');

    if ($request->has('search')) {
      $query->where('name', 'like', '%' . $request->search . '%');
    }

    $regencies = $query->paginate(10)->withQueryString();
    $provinces = Provinces::all();

    return Inertia::render('MasterData/Regencies/Index', [
      'regencies' => $regencies,
      'provinces' => $provinces,
      'filters' => $request->only(['search']),
    ]);
  }

  public function store(Request $request)
  {
    $request->validate([
      'id' => 'required|numeric|unique:m_regencies,id|max:9999',
      'province_id' => 'required|exists:m_provinces,id|max:99',
      'name' => 'required|string|max:255',
    ]);

    Regencies::create($request->all());

    return redirect()->route('regencies.index')->with('success', 'Kabupaten/Kota berhasil ditambahkan.');
  }

  public function update(Request $request, Regencies $regency)
  {
    $request->validate([
      'province_id' => 'required|exists:m_provinces,id|max:99',
      'name' => 'required|string|max:255',
    ]);

    $regency->update($request->all());

    return redirect()->route('regencies.index')->with('success', 'Kabupaten/Kota berhasil diperbarui.');
  }

  public function destroy(Regencies $regency)
  {
    try {
      $regency->delete();
      return redirect()->route('regencies.index')->with('success', 'Kabupaten/Kota berhasil dihapus.');
    } catch (\Illuminate\Database\QueryException $e) {
      if ($e->getCode() == '23000') {
        return redirect()->route('regencies.index')->with('error', 'Kabupaten/Kota tidak dapat dihapus karena masih digunakan di data lain (Kecamatan).');
      }
      return redirect()->route('regencies.index')->with('error', 'Terjadi kesalahan saat menghapus data.');
    }
  }
}
