<?php

namespace App\Http\Controllers;

use App\Models\Barangay;

class BarangayController extends Controller
{
    public function index()
    {
        $barangays = Barangay::all();
        $layerTypes = \App\Models\MapLayerType::all();
        return view('map', compact('barangays', 'layerTypes'));
    }

    public function getBarangays()
    {
        return response()->json(Barangay::all());
    }

    public function getFeatures(Barangay $barangay)
    {
        return response()->json($barangay->features);
    }
}
