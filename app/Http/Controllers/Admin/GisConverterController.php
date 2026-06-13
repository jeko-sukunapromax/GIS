<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Barangay;
use App\Models\MapLayerType;
use App\Models\MapUpload;
use App\Services\ActivityLogger;
use App\Services\FeatureGeoJsonImporter;
use App\Services\GisFileConverter;
use App\Traits\ParsesBoundaryFiles;
use App\Traits\RefreshesPostgisGeometry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class GisConverterController extends Controller
{
    use ParsesBoundaryFiles;
    use RefreshesPostgisGeometry;

    public function index()
    {
        return view('admin.gis_converter.index', [
            'barangays' => Barangay::query()
                ->where('is_municipal_boundary', false)
                ->orderBy('name')
                ->get(),
            'layerTypes' => MapLayerType::query()
                ->where('is_active', true)
                ->orderBy('category')
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function preview(Request $request, GisFileConverter $converter)
    {
        $request->validate([
            'gis_file' => 'required|file|max:51200|extensions:geojson,json,kml,zip',
        ]);

        $file = $request->file('gis_file');
        $fileName = $file->getClientOriginalName();
        $extension = strtolower($file->getClientOriginalExtension());
        $token = (string) Str::uuid();
        $previewDir = storage_path("app/gis-converter-previews/{$token}");

        File::ensureDirectoryExists($previewDir);

        try {
            $storedName = 'source.'.($extension ?: 'gis');
            $storedPath = "{$previewDir}/{$storedName}";
            $file->move($previewDir, $storedName);

            $conversion = $converter->convert($this->storedFile($storedPath), $extension, $fileName);

            if ($request->boolean('dissolve_boundary')) {
                $featuresJson = json_encode($conversion['geojson']['features']);
                $result = \DB::select("
                    SELECT ST_AsGeoJSON(ST_Union(geom)) as geojson 
                    FROM (
                        SELECT ST_GeomFromGeoJSON(value->>'geometry') as geom 
                        FROM json_array_elements(?::json)
                    ) sub
                    WHERE geom IS NOT NULL
                ", [$featuresJson]);

                if (!empty($result) && !empty($result[0]->geojson)) {
                    $unionedGeom = json_decode($result[0]->geojson, true);
                    $conversion['geojson'] = [
                        'type' => 'FeatureCollection',
                        'features' => [
                            [
                                'type' => 'Feature',
                                'properties' => [
                                    'name' => 'Bayambang',
                                    'description' => 'Dissolved boundary from ' . $fileName,
                                ],
                                'geometry' => $unionedGeom
                            ]
                        ]
                    ];
                    $conversion['feature_count'] = 1;
                    $conversion['download_name'] = str_replace('-converted', '-dissolved', $conversion['download_name']);
                } else {
                    throw new \Exception('No valid polygon geometries found to dissolve.');
                }
            }

            $convertedPath = "{$previewDir}/converted.geojson";
            $converter->writeGeoJson($conversion['geojson'], $convertedPath);
            $inspection = $this->inspectGeoJson($conversion['geojson']);

            $request->session()->put("gis_converter_previews.{$token}", [
                'source_path' => $storedPath,
                'converted_path' => $convertedPath,
                'source_name' => $fileName,
                'source_format' => $conversion['source_format'],
                'download_name' => $conversion['download_name'],
                'feature_count' => $conversion['feature_count'],
                'inspection' => $inspection,
                'created_at' => now()->toISOString(),
            ]);
        } catch (\Exception $e) {
            File::deleteDirectory($previewDir);

            return back()->with('error', 'Unable to convert GIS file: '.$e->getMessage());
        }

        app(ActivityLogger::class)->log('gis_converter.previewed', "Converted {$fileName} to GeoJSON preview.", null, [
            'source_format' => $conversion['source_format'],
            'feature_count' => $conversion['feature_count'],
            'token' => $token,
        ], $request);

        return back()->with('gis_conversion_preview', [
            'token' => $token,
            'source_name' => $fileName,
            'source_format' => $conversion['source_format'],
            'download_name' => $conversion['download_name'],
            'feature_count' => $conversion['feature_count'],
            'inspection' => $inspection,
        ]);
    }

    public function download(Request $request)
    {
        $validated = $request->validate([
            'preview_token' => 'required|string',
        ]);

        $preview = $request->session()->get("gis_converter_previews.{$validated['preview_token']}");

        if (! $preview || empty($preview['converted_path']) || ! File::exists($preview['converted_path'])) {
            abort(404, 'Converted GeoJSON preview is no longer available.');
        }

        return response()->download(
            $preview['converted_path'],
            $preview['download_name'] ?? 'converted-gis-data.geojson',
            ['Content-Type' => 'application/geo+json']
        );
    }

    public function import(Request $request, FeatureGeoJsonImporter $featureImporter)
    {
        $validated = $request->validate([
            'preview_token' => 'required|string',
            'import_mode' => 'required|in:boundaries,features',
            'feature_barangay_id' => 'nullable|integer|exists:barangays,id',
            'feature_type' => 'nullable|string|exists:map_layer_types,code',
        ]);

        $preview = $request->session()->get("gis_converter_previews.{$validated['preview_token']}");

        if (! $preview || empty($preview['converted_path']) || ! File::exists($preview['converted_path'])) {
            return back()->with('error', 'Converted GeoJSON preview expired. Please convert the file again.');
        }

        $mode = $validated['import_mode'];
        $fileName = $preview['source_name'] ?? 'converted-gis-data.geojson';

        try {
            if ($mode === 'features') {
                $result = $featureImporter->import(
                    $this->storedFile($preview['converted_path']),
                    $validated['feature_barangay_id'] ?? null,
                    $validated['feature_type'] ?? null,
                    $fileName,
                );
            } else {
                $result = $this->parseBulkGeoJson($this->storedFile($preview['converted_path']), $fileName);
            }

            $this->refreshPostgisGeometry('gis_converter.imported');

            $upload = MapUpload::create([
                'file_name' => $fileName,
                'file_type' => $mode === 'features' ? 'Converted GeoJSON Features' : 'Converted GeoJSON Boundary',
                'file_size' => $this->formatFileSize((int) filesize($preview['converted_path'])),
                'uploaded_by' => auth()->user()?->name ?? 'Admin',
                'status' => 'Processed',
            ]);

            app(ActivityLogger::class)->log('gis_converter.imported', "Imported converted GIS file {$fileName}.", $upload, [
                'mode' => $mode,
                'source_format' => $preview['source_format'] ?? null,
                'matched' => $result['matched'] ?? 0,
                'created' => count($result['unmatched'] ?? []),
            ], $request);
        } catch (\Exception $e) {
            Log::error('GIS converter import error for '.$fileName.': '.$e->getMessage());

            return back()->with('error', "{$fileName}: ".$e->getMessage());
        }

        $request->session()->forget("gis_converter_previews.{$validated['preview_token']}");
        File::deleteDirectory(storage_path("app/gis-converter-previews/{$validated['preview_token']}"));

        return back()->with('success', $this->formatImportResult($fileName, $mode, $result));
    }

    public function cancel(Request $request)
    {
        $validated = $request->validate([
            'preview_token' => 'required|string',
        ]);

        $request->session()->forget("gis_converter_previews.{$validated['preview_token']}");
        File::deleteDirectory(storage_path("app/gis-converter-previews/{$validated['preview_token']}"));

        app(ActivityLogger::class)->log('gis_converter.canceled', 'Canceled converted GIS preview.', null, [
            'token' => $validated['preview_token'],
        ], $request);

        return back()->with('success', 'Converted GeoJSON preview canceled. No data was imported.');
    }

    private function inspectGeoJson(array $geoJson): array
    {
        $features = collect($geoJson['features'] ?? []);
        $propertyKeys = $features
            ->flatMap(fn (array $feature) => array_keys((array) ($feature['properties'] ?? [])))
            ->unique()
            ->sort()
            ->values()
            ->all();

        return [
            'geometry_counts' => $features
                ->countBy(fn (array $feature) => $feature['geometry']['type'] ?? 'Missing')
                ->sortKeys()
                ->all(),
            'property_keys' => $propertyKeys,
            'sample_rows' => $features
                ->values()
                ->map(fn (array $feature, int $index) => [
                    'name' => $this->featureName($feature, $index),
                    'geometry_type' => $feature['geometry']['type'] ?? 'Missing',
                    'property_count' => count((array) ($feature['properties'] ?? [])),
                    'properties' => collect((array) ($feature['properties'] ?? []))
                        ->take(4)
                        ->all(),
                ])
                ->all(),
        ];
    }

    private function featureName(array $feature, int $index): string
    {
        $properties = (array) ($feature['properties'] ?? []);

        foreach (['name', 'NAME', 'feature_name', 'asset_name', 'barangay', 'BGY_NAME', 'BRGY_NAME'] as $key) {
            foreach ($properties as $propertyKey => $value) {
                if (strcasecmp($propertyKey, $key) === 0 && $value !== null && $value !== '') {
                    return (string) $value;
                }
            }
        }

        return 'Feature '.($index + 1);
    }

    private function storedFile(string $path): object
    {
        return new class($path) {
            public function __construct(private string $path) {}

            public function getRealPath(): string
            {
                return $this->path;
            }
        };
    }

    private function formatFileSize(int $size): string
    {
        return $size > 1048576
            ? round($size / 1048576, 1).' MB'
            : round(max($size, 1) / 1024, 1).' KB';
    }

    private function formatImportResult(string $fileName, string $mode, array $result): string
    {
        if ($mode === 'features') {
            $message = "{$fileName}: imported {$result['created']} new map feature(s)";

            if (($result['matched'] ?? 0) > 0) {
                $message .= " and updated {$result['matched']} existing feature(s)";
            }

            if (($result['skipped'] ?? 0) > 0) {
                $message .= ". Skipped {$result['skipped']} invalid feature(s)";
            }

            return $message.'.';
        }

        $message = "{$fileName}: processed {$result['matched']} barangay boundaries.";

        if (count($result['unmatched'] ?? []) > 0) {
            $message .= ' Created new records: '.Str::limit(implode(', ', array_slice($result['unmatched'], 0, 3)), 120);
        }

        return $message;
    }
}
