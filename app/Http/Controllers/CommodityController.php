<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Commodity;
use Inertia\Inertia;

class CommodityController extends Controller
{

    public function index(Request $request)
    {
        $query = Commodity::query();

        if ($request->has('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $commodities = $query->paginate(10)->withQueryString();

        return Inertia::render('MasterData/Commodities/Index', [
            'commodities' => $commodities,
            'filters' => $request->only(['search']),
        ]);
    }

    public function store(Request $request)
    {
        if ($request->has('name')) {
            $name = trim(preg_replace('/\s+/', ' ', $request->name));
            $name = ucwords(strtolower($name));
            $request->merge(['name' => $name]);
        }

        $isTransaksi = $request->boolean('is_nilai_transaksi_ekonomi');
        $existing = Commodity::withoutGlobalScope('not_nilai_transaksi_ekonomi')
            ->where('is_nilai_transaksi_ekonomi', $isTransaksi)
            ->where('name', $request->name)
            ->first();

        if ($existing) {
            if ($request->wantsJson()) {
                return response()->json(['success' => true, 'commodity' => $existing]);
            }
            return redirect()->route('commodities.index')->with('success', 'Komoditas sudah ada');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'nullable|string',
            'is_nilai_transaksi_ekonomi' => 'nullable|boolean',
        ]);

        $commodity = Commodity::create($request->all());

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'commodity' => $commodity,
            ]);
        }

        return redirect()->route('commodities.index')->with('success', 'Komoditas berhasil ditambahkan');
    }

    public function update(Request $request, Commodity $commodity)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:m_commodities,name,' . $commodity->id,
            'type' => 'nullable|string',
        ]);

        $commodity->update($request->all());

        return redirect()->route('commodities.index')->with('success', 'Komoditas berhasil diperbarui');
    }

    public function destroy(Commodity $commodity)
    {
        $commodity->delete();

        return redirect()->route('commodities.index')->with('success', 'Komoditas berhasil dihapus');
    }
}
