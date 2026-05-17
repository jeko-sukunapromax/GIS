<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MapLayerType;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class MapLayerTypeController extends Controller
{
    public function index()
    {
        $layerTypes = MapLayerType::orderBy('category')->get();
        return view('admin.layer_types.index', compact('layerTypes'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'required|string|max:255',
            'icon' => 'required|string|max:255',
            'color' => 'required|string|max:7|starts_with:#',
            'geom_type' => 'required|in:point,polyline,polygon'
        ]);

        // Automatically slugify the code from the name
        $validated['code'] = Str::slug($request->name, '_');

        // Ensure code uniqueness
        $count = 1;
        $originalCode = $validated['code'];
        while (MapLayerType::where('code', $validated['code'])->exists()) {
            $validated['code'] = $originalCode . '_' . $count++;
        }

        $newLayer = MapLayerType::create($validated);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'New map layer type added successfully!',
                'layer_type' => $newLayer
            ]);
        }

        return redirect()->route('admin.layer-types.index')
            ->with('success', 'New map layer type added successfully!');
    }

    public function update(Request $request, MapLayerType $layerType)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'required|string|max:255',
            'icon' => 'required|string|max:255',
            'color' => 'required|string|max:7|starts_with:#',
            'geom_type' => 'required|in:point,polyline,polygon'
        ]);

        $validated['code'] = Str::slug($request->name, '_');

        $count = 1;
        $originalCode = $validated['code'];
        while (MapLayerType::where('code', $validated['code'])->where('id', '!=', $layerType->id)->exists()) {
            $validated['code'] = $originalCode . '_' . $count++;
        }

        $layerType->update($validated);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Map layer type updated successfully!',
                'layer_type' => $layerType
            ]);
        }

        return redirect()->route('admin.layer-types.index')
            ->with('success', 'Map layer type updated successfully!');
    }

    public function destroy(MapLayerType $layerType)
    {
        $layerType->delete();
        return redirect()->route('admin.layer-types.index')
            ->with('success', 'Map layer type deleted successfully!');
    }
}
