<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Commodity;

class CommodityController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|unique:m_commodities,name|max:255',
            'type' => 'nullable|string',
        ]);

        $commodity = Commodity::create($validated);

        return response()->json([
            'message' => 'Komoditas berhasil ditambahkan',
            'commodity' => $commodity
        ]);
    }
}
