<?php

namespace App\Http\Controllers;

use App\Models\Provinces;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProvinceController extends Controller
{
  public function index(Request $request)
  {
    $query = Provinces::query();

    if ($request->has('search')) {
      $query->where('name', 'like', '%' . $request->search . '%');
    }

    $provinces = $query->paginate(10)->withQueryString();

    return Inertia::render('MasterData/Provinces/Index', [
      'provinces' => $provinces,
      'filters' => $request->only(['search']),
    ]);
  }

  public function store(Request $request)
  {
    $request->validate([
      'id' => 'required|numeric|unique:m_provinces,id|max:99',
      'name' => 'required|string|max:255',
    ]);

    Provinces::create($request->all());

    return redirect()->route('provinces.index')->with('success', 'Provinsi berhasil ditambahkan.');
  }

  public function update(Request $request, Provinces $province)
  {
    $request->validate([
      'name' => 'required|string|max:255',
    ]);

    $province->update($request->only('name'));

    return redirect()->route('provinces.index')->with('success', 'Provinsi berhasil diperbarui.');
  }

  public function destroy(Provinces $province)
  {
    try {
      $province->delete();
      return redirect()->route('provinces.index')->with('success', 'Provinsi berhasil dihapus.');
    } catch (\Illuminate\Database\QueryException $e) {
      if ($e->getCode() === '23000') {
        return redirect()->route('provinces.index')->with('error', 'Provinsi tidak dapat dihapus karena masih digunakan oleh data Kabupaten/Kota.');
      }
      return redirect()->route('provinces.index')->with('error', 'Terjadi kesalahan saat menghapus provinsi.');
    }
  }
}
