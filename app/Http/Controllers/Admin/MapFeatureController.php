<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Barangay;
use App\Models\MapFeature;
use App\Models\MapLayerType;
use App\Services\ActivityLogger;
use App\Services\LayerMetadataSchema;
use App\Traits\RefreshesPostgisGeometry;
use Illuminate\Http\Request;

class MapFeatureController extends Controller
{
    use RefreshesPostgisGeometry;

    public function index(Request $request)
    {
        $barangays = Barangay::where('is_municipal_boundary', false)->orderBy('name')->get();
        
        $selectedBarangayId = $request->query('barangay_id');
        $selectedBarangay = null;

        if ($selectedBarangayId) {
            $selectedBarangay = Barangay::find($selectedBarangayId);
        }

        if (!$selectedBarangay && $barangays->count() > 0) {
            $selectedBarangay = $barangays->first();
        }

        $features = $selectedBarangay ? $selectedBarangay->features()->latest()->get() : collect();
        $layerTypes = MapLayerType::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('category')
            ->orderBy('name')
            ->get();

        return view('admin.features.index', compact('barangays', 'selectedBarangay', 'features', 'layerTypes'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'barangay_id' => 'required|exists:barangays,id',
            'name' => 'required|string|max:255',
            'layer_type' => 'required|string|max:255',
            'feature_type' => 'required|string|exists:map_layer_types,code',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'coordinates' => 'nullable|string', // JSON string of latlng array from polyline/polygon
            'metadata' => 'nullable|array', // Array from metadata input fields
            'is_public' => 'nullable|boolean',
            'status' => 'required|in:active,inactive,draft',
        ]);

        $layerType = MapLayerType::where('code', $validated['feature_type'])->firstOrFail();
        $validated['layer_type'] = $layerType->category;
        $validated['is_public'] = $request->boolean('is_public', true);

        $geometry = $this->validatedGeometry($request, $layerType);

        if (isset($geometry['error'])) {
            return back()->withErrors(['geometry' => $geometry['error']])->withInput();
        }

        $validated['latitude'] = $geometry['latitude'];
        $validated['longitude'] = $geometry['longitude'];
        $validated['coordinates'] = $geometry['coordinates'];

        $validated['metadata'] = app(LayerMetadataSchema::class)->normalizeMetadata(
            $layerType,
            $request->input('metadata', []),
        );

        $feature = MapFeature::create($validated);

        app(ActivityLogger::class)->log('map_feature.created', "Added map feature {$feature->name}.", $feature, [
            'barangay_id' => $feature->barangay_id,
            'feature_type' => $feature->feature_type,
            'layer_type' => $feature->layer_type,
        ], $request);

        $this->refreshPostgisGeometry('map_feature.created');

        return redirect()->route('admin.features.index', ['barangay_id' => $request->barangay_id])
            ->with('success', 'Map feature added successfully!');
    }

    public function update(Request $request, MapFeature $feature)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'feature_type' => 'required|string|exists:map_layer_types,code',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'coordinates' => 'nullable|string',
            'metadata_json' => 'nullable|string',
            'is_public' => 'nullable|boolean',
            'status' => 'required|in:active,inactive,draft',
        ]);

        $layerType = MapLayerType::where('code', $validated['feature_type'])->firstOrFail();
        $metadata = [];

        if (! empty($validated['metadata_json'])) {
            $metadata = json_decode($validated['metadata_json'], true);

            if (! is_array($metadata)) {
                return back()->with('error', 'Metadata must be valid JSON.');
            }
        }

        $geometry = $this->validatedGeometry($request, $layerType);

        if (isset($geometry['error'])) {
            return back()->withErrors(['geometry' => $geometry['error']])->withInput();
        }

        $metadata = app(LayerMetadataSchema::class)->normalizeMetadata($layerType, $metadata);

        $feature->update([
            'name' => $validated['name'],
            'feature_type' => $validated['feature_type'],
            'layer_type' => $layerType->category,
            'latitude' => $geometry['latitude'],
            'longitude' => $geometry['longitude'],
            'coordinates' => $geometry['coordinates'],
            'metadata' => $metadata,
            'is_public' => $request->boolean('is_public'),
            'status' => $validated['status'],
        ]);

        app(ActivityLogger::class)->log('map_feature.updated', "Updated map feature {$feature->name}.", $feature, [
            'feature_type' => $feature->feature_type,
            'status' => $feature->status,
            'is_public' => $feature->is_public,
        ], $request);

        $this->refreshPostgisGeometry('map_feature.updated');

        return redirect()->route('admin.features.index', ['barangay_id' => $feature->barangay_id])
            ->with('success', 'Map feature updated successfully!');
    }

    public function togglePublic(Request $request, MapFeature $feature)
    {
        $feature->update([
            'is_public' => ! $feature->is_public,
        ]);

        app(ActivityLogger::class)->log('map_feature.visibility_changed', "{$feature->name} public visibility changed.", $feature, [
            'is_public' => $feature->is_public,
        ], $request);

        $this->refreshPostgisGeometry('map_feature.visibility_changed');

        return redirect()->route('admin.features.index', ['barangay_id' => $feature->barangay_id])
            ->with('success', $feature->is_public ? 'Feature published to public map.' : 'Feature hidden from public map.');
    }

    public function destroy(Request $request, MapFeature $feature)
    {
        $barangayId = $feature->barangay_id;

        app(ActivityLogger::class)->log('map_feature.deleted', "Deleted map feature {$feature->name}.", $feature, [
            'barangay_id' => $feature->barangay_id,
            'feature_type' => $feature->feature_type,
        ], $request);

        $feature->delete();

        $this->refreshPostgisGeometry('map_feature.deleted');

        return redirect()->route('admin.features.index', ['barangay_id' => $barangayId])
            ->with('success', 'Map feature deleted successfully!');
    }

    private function validatedGeometry(Request $request, MapLayerType $layerType): array
    {
        $geomType = $layerType->geom_type ?: 'point';
        $coordinates = $this->decodeCoordinates($request->input('coordinates'));

        if ($coordinates === false) {
            return ['error' => 'Coordinates must be valid JSON.'];
        }

        if ($geomType === 'point') {
            $lat = $request->input('latitude');
            $lng = $request->input('longitude');

            if (! is_numeric($lat) || ! is_numeric($lng)) {
                return ['error' => 'Point layers require latitude and longitude.'];
            }

            $lat = (float) $lat;
            $lng = (float) $lng;

            if (! $this->validLatLng($lat, $lng)) {
                return ['error' => 'Point latitude or longitude is outside the valid map range.'];
            }

            return [
                'latitude' => $lat,
                'longitude' => $lng,
                'coordinates' => null,
            ];
        }

        $points = is_array($coordinates) ? $this->cleanLatLngPairs($coordinates) : [];

        if ($geomType === 'polyline') {
            if (count($points) < 2) {
                return ['error' => 'Line layers require at least two valid coordinate points.'];
            }

            return [
                'latitude' => null,
                'longitude' => null,
                'coordinates' => $points,
            ];
        }

        if ($geomType === 'polygon') {
            $uniquePoints = collect($points)
                ->map(fn (array $point) => round($point[0], 7).','.round($point[1], 7))
                ->unique()
                ->count();

            if ($uniquePoints < 3) {
                return ['error' => 'Polygon layers require at least three unique coordinate points.'];
            }

            return [
                'latitude' => null,
                'longitude' => null,
                'coordinates' => $points,
            ];
        }

        return ['error' => 'Unsupported geometry type for this layer.'];
    }

    private function decodeCoordinates(?string $coordinates): array|bool|null
    {
        if (blank($coordinates)) {
            return null;
        }

        $decoded = json_decode($coordinates, true);

        if (json_last_error() !== JSON_ERROR_NONE || ! is_array($decoded)) {
            return false;
        }

        return $decoded;
    }

    private function cleanLatLngPairs(array $coordinates): array
    {
        $points = [];

        foreach ($coordinates as $coordinate) {
            if (! is_array($coordinate) || count($coordinate) < 2) {
                continue;
            }

            $lat = $coordinate[0];
            $lng = $coordinate[1];

            if (! is_numeric($lat) || ! is_numeric($lng)) {
                continue;
            }

            $lat = (float) $lat;
            $lng = (float) $lng;

            if (! $this->validLatLng($lat, $lng)) {
                continue;
            }

            $points[] = [$lat, $lng];
        }

        return $points;
    }

    private function validLatLng(float $lat, float $lng): bool
    {
        return $lat >= -90 && $lat <= 90 && $lng >= -180 && $lng <= 180;
    }
}
