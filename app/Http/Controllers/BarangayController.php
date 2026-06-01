<?php

namespace App\Http\Controllers;

use App\Models\Barangay;
use App\Models\MapLayerType;

class BarangayController extends Controller
{
    public function index()
    {
        $barangays = Barangay::where('is_visible', true)->get();
        $layerTypes = MapLayerType::query()
            ->where('is_active', true)
            ->where('is_public', true)
            ->orderBy('sort_order')
            ->orderBy('category')
            ->orderBy('name')
            ->get();

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
        $publicLayerCodes = MapLayerType::query()
            ->where('is_active', true)
            ->where('is_public', true)
            ->pluck('code');

        return response()->json(
            $barangay->features()
                ->where('is_public', true)
                ->where('status', 'active')
                ->whereIn('feature_type', $publicLayerCodes)
                ->get()
        );
    }
}
