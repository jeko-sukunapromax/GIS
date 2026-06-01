<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Barangay;
use App\Models\MapLayerType;
use Illuminate\Http\Request;

class MapExportController extends Controller
{
    public function index(Request $request)
    {
        $basemap = $request->query('basemap', 'satellite');
        $basemap = in_array($basemap, ['satellite', 'street', 'light'], true) ? $basemap : 'satellite';

        $barangays = Barangay::query()
            ->where('is_municipal_boundary', false)
            ->orderBy('name')
            ->get();

        $selectedBarangay = null;

        if ($request->filled('barangay_id')) {
            $selectedBarangay = Barangay::query()
                ->where('is_municipal_boundary', false)
                ->with('features')
                ->find($request->integer('barangay_id'));
        }

        $selectedBarangay ??= $barangays->first()?->load('features');
        $features = $selectedBarangay?->features ?? collect();
        $layerTypes = MapLayerType::query()->orderBy('name')->get();

        return view('admin.map_export.index', [
            'barangays' => $barangays,
            'selectedBarangay' => $selectedBarangay,
            'features' => $features,
            'layerTypes' => $layerTypes,
            'facilityFeatures' => $features->where('layer_type', 'critical_facilities')->values(),
            'basemap' => $basemap,
        ]);
    }
}
