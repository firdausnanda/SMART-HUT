<?php

namespace App\Http\Controllers;

use App\Models\Districts;
use App\Models\Regencies;
use Illuminate\Http\Request;
use Inertia\Inertia;

use App\Models\Provinces;

class DistrictController extends Controller
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
    $query = Districts::with('regency.province');

    if ($request->filled('search')) {
      $query->where(function ($q) use ($request) {
        $q->where('m_districts.name', 'like', '%' . $request->search . '%')
          ->orWhere('m_districts.id', 'like', '%' . $request->search . '%');
      });
    }

    if ($request->filled('id')) {
      $query->where('m_districts.id', 'like', '%' . $request->id . '%');
    }

    if ($request->filled('name')) {
      $query->where('m_districts.name', 'like', '%' . $request->name . '%');
    }

    if ($request->filled('regency_id')) {
      $query->where('m_districts.regency_id', $request->regency_id);
    }

    if ($request->filled('province_id')) {
      $query->whereHas('regency', function ($q) use ($request) {
        $q->where('province_id', $request->province_id);
      });
    }

    if ($request->sort) {
      if ($request->sort === 'regency') {
        $query->join('m_regencies', 'm_districts.regency_id', '=', 'm_regencies.id')
              ->orderBy('m_regencies.name', $request->direction ?? 'asc')
              ->select('m_districts.*');
      } else {
        $query->orderBy('m_districts.' . $request->sort, $request->direction ?? 'asc');
      }
    } else {
      $query->orderBy('m_districts.id', 'asc');
    }

    $perPage = $request->per_page ? $request->per_page : 10;
    $districts = $query->paginate($perPage)->withQueryString();
    
    $provinces = Provinces::orderBy('name')->get();
    $regencies = Regencies::orderBy('name')->get();

    return Inertia::render('MasterData/Districts/Index', [
      'districts' => $districts,
      'provinces' => $provinces,
      'regencies' => $regencies,
      'filters' => $request->only(['search', 'id', 'name', 'regency_id', 'province_id', 'per_page', 'sort', 'direction']),
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
