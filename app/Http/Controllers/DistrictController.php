<?php

namespace App\Http\Controllers;

use App\Models\Districts;
use App\Models\Regencies;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DistrictController extends Controller
{
  public function index(Request $request)
  {
    $query = Districts::with('regency.province');

    if ($request->has('search')) {
      $query->where('name', 'like', '%' . $request->search . '%');
    }

    $districts = $query->paginate(10)->withQueryString();
    $regencies = Regencies::all();

    return Inertia::render('MasterData/Districts/Index', [
      'districts' => $districts,
      'regencies' => $regencies,
      'filters' => $request->only(['search']),
    ]);
  }

  public function store(Request $request)
  {
    $request->validate([
      'id' => 'required|numeric|unique:m_districts,id|max:9999999',
      'regency_id' => 'required|exists:m_regencies,id|max:9999',
      'name' => 'required|string|max:255',
    ]);

    Districts::create($request->all());

    return redirect()->route('districts.index')->with('success', 'Kecamatan berhasil ditambahkan.');
  }

  public function update(Request $request, Districts $district)
  {
    $request->validate([
      'regency_id' => 'required|exists:m_regencies,id|max:9999',
      'name' => 'required|string|max:255',
    ]);

    $district->update($request->all());

    return redirect()->route('districts.index')->with('success', 'Kecamatan berhasil diperbarui.');
  }

  public function destroy(Districts $district)
  {
    $district->delete();

    return redirect()->route('districts.index')->with('success', 'Kecamatan berhasil dihapus.');
  }
}
