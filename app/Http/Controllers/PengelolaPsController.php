<?php

namespace App\Http\Controllers;

use App\Models\PengelolaPS;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PengelolaPsController extends Controller
{
  public function index(Request $request)
  {
    $query = PengelolaPS::query();

    if ($request->has('search')) {
      $query->where('name', 'like', '%' . $request->search . '%');
    }

    $pengelolaPs = $query->paginate(10)->withQueryString();

    return Inertia::render('MasterData/PengelolaPs/Index', [
      'pengelolaPs' => $pengelolaPs,
      'filters' => $request->only(['search']),
    ]);
  }

  public function store(Request $request)
  {
    $request->validate([
      'name' => 'required|string|max:255|unique:m_pengelola_ps,name',
    ]);

    PengelolaPS::create($request->all());

    return redirect()->route('pengelola-ps.index')->with('success', 'Data Pengelola PS berhasil ditambahkan.');
  }

  public function update(Request $request, PengelolaPS $pengelolaPs)
  {
    $request->validate([
      'name' => 'required|string|max:255|unique:m_pengelola_ps,name,' . $pengelolaPs->id,
    ]);

    $pengelolaPs->update($request->only('name'));

    return redirect()->route('pengelola-ps.index')->with('success', 'Data Pengelola PS berhasil diperbarui.');
  }

  public function destroy(PengelolaPS $pengelolaPs)
  {
    $pengelolaPs->delete();

    return redirect()->route('pengelola-ps.index')->with('success', 'Data Pengelola PS berhasil dihapus.');
  }
}
