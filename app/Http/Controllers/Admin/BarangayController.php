<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Barangay;
use Illuminate\Http\Request;

class BarangayController extends Controller
{
    public function index()
    {
        $barangays = Barangay::all();
        return view('admin.barangays.index', compact('barangays'));
    }

    public function create()
    {
        return view('admin.barangays.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'municipality' => 'nullable|string|max:255',
            'province' => 'nullable|string|max:255',
            'total_area' => 'nullable|numeric',
            'population' => 'nullable|string|max:255',
            'land_use' => 'nullable|string|max:255',
            'hazard_level' => 'nullable|string|max:255',
            'status' => 'nullable|string|max:255',
            'agri_area' => 'nullable|numeric',
            'residential_area' => 'nullable|numeric',
            'commercial_area' => 'nullable|numeric',
            'unidentified_area' => 'nullable|numeric',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'boundary' => 'nullable|string', // Validated as string then decoded
            'description' => 'nullable|string'
        ]);

        if (!empty($validated['boundary'])) {
            $validated['boundary'] = json_decode($validated['boundary'], true);
        }

        Barangay::create($validated);
        return redirect()->route('admin.barangays.index')->with('success', 'Barangay created successfully!');
    }

    public function edit(Barangay $barangay)
    {
        return view('admin.barangays.edit', compact('barangay'));
    }

    public function update(Request $request, Barangay $barangay)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'municipality' => 'nullable|string|max:255',
            'province' => 'nullable|string|max:255',
            'total_area' => 'nullable|numeric',
            'population' => 'nullable|string|max:255',
            'land_use' => 'nullable|string|max:255',
            'hazard_level' => 'nullable|string|max:255',
            'status' => 'nullable|string|max:255',
            'agri_area' => 'nullable|numeric',
            'residential_area' => 'nullable|numeric',
            'commercial_area' => 'nullable|numeric',
            'unidentified_area' => 'nullable|numeric',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'boundary' => 'nullable|string',
            'description' => 'nullable|string'
        ]);

        if (!empty($validated['boundary'])) {
            $validated['boundary'] = json_decode($validated['boundary'], true);
        }

        $barangay->update($validated);
        return redirect()->route('admin.barangays.index')->with('success', 'Barangay updated successfully!');
    }

    public function destroy(Barangay $barangay)
    {
        $barangay->delete();
        return redirect()->route('admin.barangays.index')->with('success', 'Barangay deleted successfully!');
    }
}
