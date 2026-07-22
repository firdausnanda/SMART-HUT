<?php

namespace App\Http\Controllers;

use App\Models\Villages;
use App\Models\Districts;
use App\Models\Provinces;
use App\Models\Regencies;
use Illuminate\Http\Request;
use Inertia\Inertia;

class VillageController extends Controller
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
    $query = Villages::with('district.regency.province');

    if ($request->filled('search')) {
      $query->where(function ($q) use ($request) {
        $q->where('m_villages.name', 'like', '%' . $request->search . '%')
          ->orWhere('m_villages.id', 'like', '%' . $request->search . '%');
      });
    }

    if ($request->filled('id')) {
      $query->where('m_villages.id', 'like', '%' . $request->id . '%');
    }

    if ($request->filled('name')) {
      $query->where('m_villages.name', 'like', '%' . $request->name . '%');
    }

    if ($request->filled('district_id')) {
      $query->where('m_villages.district_id', $request->district_id);
    }

    if ($request->filled('regency_id')) {
      $query->whereHas('district', function ($q) use ($request) {
        $q->where('regency_id', $request->regency_id);
      });
    }

    if ($request->filled('province_id')) {
      $query->whereHas('district.regency', function ($q) use ($request) {
        $q->where('province_id', $request->province_id);
      });
    }

    if ($request->sort) {
      if ($request->sort === 'district') {
        $query->join('m_districts', 'm_villages.district_id', '=', 'm_districts.id')
              ->orderBy('m_districts.name', $request->direction ?? 'asc')
              ->select('m_villages.*');
      } else {
        $query->orderBy('m_villages.' . $request->sort, $request->direction ?? 'asc');
      }
    } else {
      $query->orderBy('m_villages.id', 'asc');
    }

    $perPage = $request->per_page ? $request->per_page : 10;
    $villages = $query->paginate($perPage)->withQueryString();

    $provinces = Provinces::orderBy('name')->get();
    $regencies = Regencies::orderBy('name')->get();
    $districts = Districts::with('regency')->orderBy('name')->get();

    return Inertia::render('MasterData/Villages/Index', [
      'villages' => $villages,
      'provinces' => $provinces,
      'regencies' => $regencies,
      'districts' => $districts,
      'filters' => $request->only(['search', 'id', 'name', 'district_id', 'regency_id', 'province_id', 'per_page', 'sort', 'direction']),
    ]);
  }

  public function store(Request $request)
  {
    $request->validate([
      'id' => 'required|numeric|unique:m_villages,id',
      'district_id' => 'required|exists:m_districts,id',
      'name' => 'required|string|max:255',
    ]);

    $data = $request->all();
    $data['name'] = strtoupper($request->name);
    Villages::create($data);

    return redirect()->route('villages.index')->with('success', 'Desa/Kelurahan berhasil ditambahkan.');
  }

  public function update(Request $request, Villages $village)
  {
    $request->validate([
      'district_id' => 'required|exists:m_districts,id',
      'name' => 'required|string|max:255',
    ]);

    $data = $request->all();
    $data['name'] = strtoupper($request->name);
    $village->update($data);

    return redirect()->route('villages.index')->with('success', 'Desa/Kelurahan berhasil diperbarui.');
  }

  public function destroy(Villages $village)
  {
    $village->delete();

    return redirect()->route('villages.index')->with('success', 'Desa/Kelurahan berhasil dihapus.');
  }
}
