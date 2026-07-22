<?php

namespace App\Http\Controllers;

use App\Models\JenisProduksi;
use Illuminate\Http\Request;
use Inertia\Inertia;

class JenisProduksiController extends Controller
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
    $query = JenisProduksi::query();

    if ($request->has('search')) {
      $query->where('name', 'like', '%' . $request->search . '%');
    }

    $jenisProduksi = $query->paginate(10)->withQueryString();

    return Inertia::render('MasterData/JenisProduksi/Index', [
      'jenisProduksi' => $jenisProduksi,
      'filters' => $request->only(['search']),
    ]);
  }

  public function store(Request $request)
  {
    $request->validate([
      'name' => 'required|string|max:255|unique:m_jenis_produksi,name',
    ]);

    JenisProduksi::create($request->all());

    return redirect()->route('jenis-produksi.index')->with('success', 'Data Jenis Produksi berhasil ditambahkan.');
  }

  public function update(Request $request, JenisProduksi $jenisProduksi)
  {
    $request->validate([
      'name' => 'required|string|max:255|unique:m_jenis_produksi,name,' . $jenisProduksi->id,
    ]);

    $jenisProduksi->update($request->only('name'));

    return redirect()->route('jenis-produksi.index')->with('success', 'Data Jenis Produksi berhasil diperbarui.');
  }

  public function destroy(JenisProduksi $jenisProduksi)
  {
    $jenisProduksi->delete();

    return redirect()->route('jenis-produksi.index')->with('success', 'Data Jenis Produksi berhasil dihapus.');
  }
}
