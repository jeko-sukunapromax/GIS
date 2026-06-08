<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MapLayerType;
use App\Services\ActivityLogger;
use App\Services\LayerMetadataSchema;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class MapLayerTypeController extends Controller
{
    public function index()
    {
        $layerTypes = MapLayerType::query()
            ->withCount('features')
            ->orderBy('sort_order')
            ->orderBy('category')
            ->orderBy('name')
            ->get();

        return view('admin.layer_types.index', compact('layerTypes'));
    }

    public function store(Request $request)
    {
        $validated = $this->validatedLayerType($request);

        $validated['is_public'] = $request->boolean('is_public', true);
        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['sort_order'] = $validated['sort_order'] ?? 0;
        $validated['category'] = $this->normalizeCategory($validated['category']);
        $validated['color'] = Str::lower($validated['color']);
        $validated['code'] = $this->uniqueCodeFromName($validated['name']);
        $validated['metadata_schema'] = app(LayerMetadataSchema::class)->fromJson(
            $request->input('metadata_schema_json'),
            $validated['code'],
            $validated['geom_type'],
        );
        unset($validated['metadata_schema_json']);

        $newLayer = MapLayerType::create($validated);

        app(ActivityLogger::class)->log('layer_type.created', "Created map layer type {$newLayer->name}.", $newLayer, [
            'code' => $newLayer->code,
            'category' => $newLayer->category,
            'geom_type' => $newLayer->geom_type,
            'metadata_fields' => count($newLayer->metadata_schema ?? []),
            'is_public' => $newLayer->is_public,
            'is_active' => $newLayer->is_active,
        ], $request);

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
        $validated = $this->validatedLayerType($request);

        $validated['is_public'] = $request->boolean('is_public');
        $validated['is_active'] = $request->boolean('is_active');
        $validated['sort_order'] = $validated['sort_order'] ?? 0;
        $validated['category'] = $this->normalizeCategory($validated['category']);
        $validated['color'] = Str::lower($validated['color']);
        $validated['metadata_schema'] = app(LayerMetadataSchema::class)->fromJson(
            $request->input('metadata_schema_json'),
            $layerType->code,
            $validated['geom_type'],
        );
        unset($validated['metadata_schema_json']);

        $layerType->update($validated);

        app(ActivityLogger::class)->log('layer_type.updated', "Updated map layer type {$layerType->name}.", $layerType, [
            'code' => $layerType->code,
            'category' => $layerType->category,
            'geom_type' => $layerType->geom_type,
            'metadata_fields' => count($layerType->metadata_schema ?? []),
            'is_public' => $layerType->is_public,
            'is_active' => $layerType->is_active,
            'sort_order' => $layerType->sort_order,
        ], $request);

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

    public function destroy(Request $request, MapLayerType $layerType)
    {
        $featuresCount = $layerType->features()->count();

        if ($featuresCount > 0) {
            $message = "Cannot delete {$layerType->name} because {$featuresCount} map feature(s) still use it. Mark it inactive instead or move those features to another layer.";

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $message,
                ], 409);
            }

            return redirect()->route('admin.layer-types.index')->with('error', $message);
        }

        $name = $layerType->name;
        $code = $layerType->code;
        $layerType->delete();

        app(ActivityLogger::class)->log('layer_type.deleted', "Deleted map layer type {$name}.", null, [
            'code' => $code,
        ], $request);

        return redirect()->route('admin.layer-types.index')
            ->with('success', 'Map layer type deleted successfully!');
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedLayerType(Request $request): array
    {
        return $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'icon' => ['required', 'string', 'max:255', 'regex:/^fa[-\\w\\s]+$/'],
            'color' => ['required', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'geom_type' => 'required|in:point,polyline,polygon',
            'metadata_schema_json' => 'nullable|string',
            'is_public' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);
    }

    private function normalizeCategory(string $category): string
    {
        return (string) Str::of($category)
            ->trim()
            ->slug('_');
    }

    private function uniqueCodeFromName(string $name): string
    {
        $baseCode = (string) Str::of($name)
            ->trim()
            ->slug('_');

        $baseCode = $baseCode !== '' ? $baseCode : 'layer_type';

        $code = $baseCode;
        $count = 1;

        while (MapLayerType::where('code', $code)->exists()) {
            $code = $baseCode . '_' . $count++;
        }

        return $code;
    }
}
