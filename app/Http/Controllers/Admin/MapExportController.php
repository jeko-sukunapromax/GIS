<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Barangay;
use App\Models\MapLayerType;
use App\Services\GeoJsonExporter;
use Illuminate\Http\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

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

    public function geoJson(Request $request, GeoJsonExporter $exporter): Response
    {
        $scope = $request->query('scope', 'selected');
        $scope = in_array($scope, ['selected', 'public', 'all'], true) ? $scope : 'selected';

        $layerTypesQuery = MapLayerType::query()->orderBy('name');
        $barangaysQuery = Barangay::query()
            ->where('is_municipal_boundary', false)
            ->orderBy('name');

        if ($scope === 'public') {
            $publicLayerCodes = MapLayerType::query()
                ->where('is_active', true)
                ->where('is_public', true)
                ->pluck('code');

            $layerTypesQuery
                ->where('is_active', true)
                ->where('is_public', true);

            $barangaysQuery
                ->where('is_visible', true)
                ->with(['features' => fn ($query) => $query
                    ->where('is_public', true)
                    ->where('status', 'active')
                    ->whereIn('feature_type', $publicLayerCodes)
                    ->with('barangay')
                ]);

            $filename = 'geobayambang-public-layers.geojson';
        } elseif ($scope === 'all') {
            $barangaysQuery->with(['features' => fn ($query) => $query->with('barangay')]);
            $filename = 'geobayambang-all-layers.geojson';
        } else {
            $selectedBarangay = $barangaysQuery
                ->with(['features' => fn ($query) => $query->with('barangay')])
                ->findOrFail($request->integer('barangay_id'));

            $barangaysQuery = Barangay::query()->whereKey($selectedBarangay->id);
            $barangays = collect([$selectedBarangay]);
            $filename = Str::slug($selectedBarangay->name).'-gis-layers.geojson';

            return $exporter->downloadResponse(
                $exporter->barangayDataset($barangays, $layerTypesQuery->get()->keyBy('code')),
                $filename
            );
        }

        return $exporter->downloadResponse(
            $exporter->barangayDataset($barangaysQuery->get(), $layerTypesQuery->get()->keyBy('code')),
            $filename
        );
    }
}
