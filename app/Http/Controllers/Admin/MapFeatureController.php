<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Barangay;
use App\Models\MapFeature;
use App\Models\MapLayerType;
use Illuminate\Http\Request;

class MapFeatureController extends Controller
{
    public function index(Request $request)
    {
        $barangays = Barangay::all();
        
        $selectedBarangayId = $request->query('barangay_id');
        $selectedBarangay = null;

        if ($selectedBarangayId) {
            $selectedBarangay = Barangay::find($selectedBarangayId);
        }

        if (!$selectedBarangay && $barangays->count() > 0) {
            $selectedBarangay = $barangays->first();
        }

        $features = $selectedBarangay ? $selectedBarangay->features : collect();
        $layerTypes = MapLayerType::all();

        return view('admin.features.index', compact('barangays', 'selectedBarangay', 'features', 'layerTypes'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'barangay_id' => 'required|exists:barangays,id',
            'name' => 'required|string|max:255',
            'layer_type' => 'required|string|max:255',
            'feature_type' => 'required|string|max:255',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'coordinates' => 'nullable|string', // JSON string of latlng array from polyline/polygon
            'metadata' => 'nullable|array' // Array from metadata input fields
        ]);

        // If coordinates is provided as a JSON string, decode it into array for cast
        if (!empty($validated['coordinates'])) {
            $validated['coordinates'] = json_decode($validated['coordinates'], true);
        }

        // Filter out empty values in metadata
        if ($request->has('metadata')) {
            $validated['metadata'] = array_filter($request->input('metadata'), function ($value) {
                return $value !== null && $value !== '';
            });
        } else {
            $validated['metadata'] = [];
        }

        MapFeature::create($validated);

        return redirect()->route('admin.features.index', ['barangay_id' => $request->barangay_id])
            ->with('success', 'Map feature added successfully!');
    }

    public function destroy(MapFeature $feature)
    {
        $barangayId = $feature->barangay_id;
        $feature->delete();

        return redirect()->route('admin.features.index', ['barangay_id' => $barangayId])
            ->with('success', 'Map feature deleted successfully!');
    }
}
