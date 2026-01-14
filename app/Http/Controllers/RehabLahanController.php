<?php

namespace App\Http\Controllers;

use App\Models\RehabLahan;
use Illuminate\Http\Request;
use Inertia\Inertia;

class RehabLahanController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $datas = RehabLahan::query()
            ->latest()
            ->paginate(10);

        return Inertia::render('RehabLahan/Index', [
            'datas' => $datas,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('RehabLahan/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'year' => 'required|integer',
            'month' => 'required|integer|min:1|max:12',
            'target_annual' => 'required|numeric',
            'realization' => 'required|numeric',
            'fund_source' => 'required|string',
            'village' => 'nullable|string',
            'district' => 'nullable|string',
            'coordinates' => 'nullable|string',
        ]);

        RehabLahan::create($validated);

        return redirect()->route('rehab-lahan.index')->with('success', 'Data Created Successfully');
    }

    /**
     * Display the specified resource.
     */
    public function show(RehabLahan $rehabLahan)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(RehabLahan $rehabLahan)
    {
        return Inertia::render('RehabLahan/Edit', [
            'data' => $rehabLahan
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, RehabLahan $rehabLahan)
    {
        $validated = $request->validate([
            'year' => 'required|integer',
            'month' => 'required|integer|min:1|max:12',
            'target_annual' => 'required|numeric',
            'realization' => 'required|numeric',
            'fund_source' => 'required|string',
            'village' => 'nullable|string',
            'district' => 'nullable|string',
            'coordinates' => 'nullable|string',
        ]);

        $rehabLahan->update($validated);

        return redirect()->route('rehab-lahan.index')->with('success', 'Data Updated Successfully');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(RehabLahan $rehabLahan)
    {
        $rehabLahan->delete();

        return redirect()->route('rehab-lahan.index')->with('success', 'Data Deleted Successfully');
    }
}
