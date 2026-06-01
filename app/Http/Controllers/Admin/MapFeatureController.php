<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Barangay;
use App\Models\MapFeature;
use App\Models\MapLayerType;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;

class MapFeatureController extends Controller
{
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

        $feature = MapFeature::create($validated);

        app(ActivityLogger::class)->log('map_feature.created', "Added map feature {$feature->name}.", $feature, [
            'barangay_id' => $feature->barangay_id,
            'feature_type' => $feature->feature_type,
            'layer_type' => $feature->layer_type,
        ], $request);

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

        $coordinates = null;
        if (! empty($validated['coordinates'])) {
            $coordinates = json_decode($validated['coordinates'], true);

            if (! is_array($coordinates)) {
                return back()->with('error', 'Coordinates must be valid JSON.');
            }
        }

        $feature->update([
            'name' => $validated['name'],
            'feature_type' => $validated['feature_type'],
            'layer_type' => $layerType->category,
            'latitude' => $validated['latitude'] ?? null,
            'longitude' => $validated['longitude'] ?? null,
            'coordinates' => $coordinates,
            'metadata' => $metadata,
            'is_public' => $request->boolean('is_public'),
            'status' => $validated['status'],
        ]);

        app(ActivityLogger::class)->log('map_feature.updated', "Updated map feature {$feature->name}.", $feature, [
            'feature_type' => $feature->feature_type,
            'status' => $feature->status,
            'is_public' => $feature->is_public,
        ], $request);

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

        return redirect()->route('admin.features.index', ['barangay_id' => $barangayId])
            ->with('success', 'Map feature deleted successfully!');
    }
}
