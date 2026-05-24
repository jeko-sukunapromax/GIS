<?php

namespace App\Http\Controllers;

use App\Models\Barangay;

class BarangayController extends Controller
{
    public function index()
    {
        $barangays = Barangay::where('is_visible', true)->get();
        $layerTypes = \App\Models\MapLayerType::all();
        return view('map', compact('barangays', 'layerTypes'));
    }

    public function getBarangays()
    {
        return response()->json(
            Barangay::where('is_visible', true)
                ->where('is_municipal_boundary', false)
                ->get()
        );
    }

    public function getFeatures(Barangay $barangay)
    {
        return response()->json($barangay->features);
    }
}
