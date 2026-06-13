<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Barangay;
use App\Services\ActivityLogger;
use App\Services\PostgisSpatialAnalysis;
use App\Traits\RefreshesPostgisGeometry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BarangayController extends Controller
{
    use RefreshesPostgisGeometry;

    public function index()
    {
        $barangays = Barangay::where('is_municipal_boundary', false)->get();
        return view('admin.barangays.index', compact('barangays'));
    }

    public function map()
    {
        $barangays = Barangay::where('is_visible', true)->get();
        $layerTypes = \App\Models\MapLayerType::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('category')
            ->orderBy('name')
            ->get();

        return view('admin.map', compact('barangays', 'layerTypes'));
    }

    public function features(Barangay $barangay)
    {
        return response()->json($barangay->features()->get());
    }

    public function spatialAnalysis(Barangay $barangay, PostgisSpatialAnalysis $analysis)
    {
        return response()->json($analysis->summary($barangay));
    }

    public function municipalBoundary()
    {
        $municipalBoundary = $this->municipalBoundaryRecord();
        $boundaryVersions = $municipalBoundary->boundaryVersions()->take(12)->get();

        return view('admin.municipal_boundary.index', compact('municipalBoundary', 'boundaryVersions'));
    }

    public function uploadMunicipalBoundary(Request $request)
    {
        $municipalBoundary = $this->municipalBoundaryRecord();

        return $this->replaceBoundaryFromUpload($request, $municipalBoundary, true);
    }

    public function resetMunicipalBoundary(Request $request)
    {
        $municipalBoundary = $this->municipalBoundaryRecord();
        $municipalBoundary->snapshotBoundary('Before municipal boundary reset', $request->user()?->name);

        $municipalBoundary->update([
            'boundary' => null,
            'latitude' => null,
            'longitude' => null,
            'total_area' => null,
            'boundary_source' => null,
            'boundary_updated_at' => null,
            'is_municipal_boundary' => true,
            'is_visible' => true,
        ]);

        app(ActivityLogger::class)->log('municipal_boundary.reset', 'Reset the current Bayambang municipal boundary.', $municipalBoundary, [], $request);

        $this->refreshPostgisGeometry('municipal_boundary.reset');

        return back()->with('success', 'Bayambang municipal boundary was reset.');
    }

    public function updateMunicipalDetails(Request $request)
    {
        $municipalBoundary = $this->municipalBoundaryRecord();
        
        $validated = $request->validate([
            'barangay_chairman' => 'nullable|string|max:255',
            'sk_chairman' => 'nullable|string|max:255',
            'population' => 'nullable|string|max:255',
            'total_area' => 'nullable|numeric',
        ]);

        $municipalBoundary->update($validated);

        app(ActivityLogger::class)->log('municipal_boundary.updated', 'Updated Bayambang municipal boundary metadata.', $municipalBoundary, [
            'fields' => array_keys($validated),
        ], $request);

        return back()->with('success', 'Municipal details updated successfully!');
    }

    public function create()
    {
        return view('admin.barangays.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'municipality' => 'nullable|string|max:255',
            'province' => 'nullable|string|max:255',
            'district' => 'nullable|string|max:255',
            'barangay_chairman' => 'nullable|string|max:255',
            'sk_chairman' => 'nullable|string|max:255',
            'total_area' => 'nullable|numeric',
            'population' => 'nullable|string|max:255',
            'land_use' => 'nullable|string|max:255',
            'hazard_level' => 'nullable|string|max:255',
            'status' => 'nullable|string|max:255',
            'agri_area' => 'nullable|numeric',
            'residential_area' => 'nullable|numeric',
            'commercial_area' => 'nullable|numeric',
            'unidentified_area' => 'nullable|numeric',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'boundary' => 'nullable|string', // Validated as string then decoded
            'description' => 'nullable|string'
        ]);

        if (!empty($validated['boundary'])) {
            $validated['boundary'] = json_decode($validated['boundary'], true);
        }

        $barangay = Barangay::create($validated);

        app(ActivityLogger::class)->log('barangay.created', "Created barangay {$barangay->name}.", $barangay, [], $request);

        $this->refreshPostgisGeometry('barangay.created');

        return redirect()->route('admin.barangays.index')->with('success', 'Barangay created successfully!');
    }

    public function edit(Barangay $barangay)
    {
        return view('admin.barangays.edit', compact('barangay'));
    }

    public function update(Request $request, Barangay $barangay)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'municipality' => 'nullable|string|max:255',
            'province' => 'nullable|string|max:255',
            'district' => 'nullable|string|max:255',
            'barangay_chairman' => 'nullable|string|max:255',
            'sk_chairman' => 'nullable|string|max:255',
            'total_area' => 'nullable|numeric',
            'population' => 'nullable|string|max:255',
            'land_use' => 'nullable|string|max:255',
            'hazard_level' => 'nullable|string|max:255',
            'status' => 'nullable|string|max:255',
            'agri_area' => 'nullable|numeric',
            'residential_area' => 'nullable|numeric',
            'commercial_area' => 'nullable|numeric',
            'unidentified_area' => 'nullable|numeric',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'boundary' => 'nullable|string',
            'description' => 'nullable|string'
        ]);

        if (!empty($validated['boundary'])) {
            $barangay->snapshotBoundary('Before manual barangay edit', $request->user()?->name);
            $validated['boundary'] = json_decode($validated['boundary'], true);
        }

        $barangay->update($validated);

        app(ActivityLogger::class)->log('barangay.updated', "Updated barangay {$barangay->name}.", $barangay, [
            'fields' => array_keys($validated),
        ], $request);

        $this->refreshPostgisGeometry('barangay.updated');

        return redirect()->route('admin.barangays.index')->with('success', 'Barangay updated successfully!');
    }

    public function destroy(Request $request, Barangay $barangay)
    {
        app(ActivityLogger::class)->log('barangay.deleted', "Deleted barangay {$barangay->name}.", $barangay, [], $request);

        $barangay->delete();

        $this->refreshPostgisGeometry('barangay.deleted');

        return redirect()->route('admin.barangays.index')->with('success', 'Barangay deleted successfully!');
    }

    /**
     * Show the Boundary & Layer Management page for a specific barangay.
     */
    public function manage(Barangay $barangay)
    {
        $boundaryVersions = $barangay->boundaryVersions()->take(12)->get();

        return view('admin.barangays.manage', compact('barangay', 'boundaryVersions'));
    }

    /**
     * Upload GeoJSON or Shapefile to replace the barangay boundary.
     */
    public function uploadBoundary(Request $request, Barangay $barangay)
    {
        return $this->replaceBoundaryFromUpload($request, $barangay);
    }

    private function replaceBoundaryFromUpload(Request $request, Barangay $barangay, bool $municipal = false)
    {
        $request->validate([
            'boundary_file' => 'required|file|max:10240', // Max 10MB
            'boundary_source' => 'nullable|string|max:255',
        ]);

        $file = $request->file('boundary_file');
        $extension = strtolower($file->getClientOriginalExtension());

        try {
            $boundary = null;
            $centroid = null;

            if ($extension === 'geojson' || $extension === 'json') {
                $result = $this->parseGeoJson($file);
                $boundary = $result['boundary'];
                $centroid = $result['centroid'];
            } elseif ($extension === 'shp') {
                // For .shp files, we also need .shx and .dbf
                $request->validate([
                    'shx_file' => 'required|file',
                    'dbf_file' => 'required|file',
                ]);
                $result = $this->parseShapefile($file, $request->file('shx_file'), $request->file('dbf_file'));
                $boundary = $result['boundary'];
                $centroid = $result['centroid'];
            } elseif ($extension === 'zip') {
                // ZIP containing .shp, .shx, .dbf
                $result = $this->parseShapefileZip($file);
                $boundary = $result['boundary'];
                $centroid = $result['centroid'];
            } elseif ($extension === 'kml') {
                $result = $this->parseKml($file);
                $boundary = $result['boundary'];
                $centroid = $result['centroid'];
            } else {
                return back()->with('error', 'Unsupported file format. Please upload .geojson, .json, .kml, or .zip (Shapefile).');
            }

            if (!$boundary || count($boundary) < 3) {
                return back()->with('error', 'Could not extract a valid polygon boundary from the uploaded file. Ensure it contains at least one polygon geometry.');
            }

            $computedArea = round($this->calculatePolygonArea($boundary), 4);
            $source = $request->input('boundary_source', $file->getClientOriginalName());
            $barangay->snapshotBoundary('Before '.$source, $request->user()?->name);

            $updates = [
                'name' => $municipal ? 'Bayambang' : $barangay->name,
                'boundary' => $boundary,
                'latitude' => $centroid['lat'],
                'longitude' => $centroid['lng'],
                'boundary_source' => $source,
                'boundary_updated_at' => now(),
                'is_municipal_boundary' => $municipal ? true : $barangay->is_municipal_boundary,
                'is_visible' => true,
            ];

            if ($computedArea > 0) {
                $updates['total_area'] = $computedArea;
            }

            $barangay->update($updates);

            app(ActivityLogger::class)->log($municipal ? 'municipal_boundary.replaced' : 'barangay.boundary_replaced', ($municipal ? 'Replaced Bayambang municipal boundary' : "Replaced {$barangay->name} boundary")." from {$file->getClientOriginalName()}.", $barangay, [
                'source' => $source,
                'file_name' => $file->getClientOriginalName(),
                'computed_area_hectares' => $computedArea > 0 ? $computedArea : null,
            ], $request);

            $this->refreshPostgisGeometry($municipal ? 'municipal_boundary.replaced' : 'barangay.boundary_replaced');

            $areaMessage = $computedArea > 0 ? ' Area: ' . number_format($computedArea, 2) . ' ha.' : '';

            return back()->with('success', 'Boundary updated successfully from ' . $file->getClientOriginalName() . '! (' . count($boundary) . ' vertices loaded).' . $areaMessage);

        } catch (\Exception $e) {
            Log::error('Boundary upload error: ' . $e->getMessage());
            return back()->with('error', 'Error processing file: ' . $e->getMessage());
        }
    }

    private function municipalBoundaryRecord(): Barangay
    {
        return Barangay::firstOrCreate(
            ['is_municipal_boundary' => true],
            [
                'name' => 'Bayambang',
                'status' => 'Active',
                'is_visible' => true,
                'municipality' => 'Bayambang',
                'province' => 'Pangasinan',
            ]
        );
    }

    /**
     * Toggle boundary visibility for public map.
     */
    public function toggleVisibility(Request $request, Barangay $barangay)
    {
        $barangay->update([
            'is_visible' => !$barangay->is_visible,
        ]);

        $status = $barangay->is_visible ? 'visible' : 'hidden';

        app(ActivityLogger::class)->log('barangay.visibility_changed', "{$barangay->name} is now {$status} on the public map.", $barangay, [
            'is_visible' => $barangay->is_visible,
        ], $request);

        $this->refreshPostgisGeometry('barangay.visibility_changed');

        return response()->json([
            'success' => true,
            'is_visible' => $barangay->is_visible,
            'message' => "Barangay {$barangay->name} is now {$status} on the public map."
        ]);
    }

    /**
     * Bulk update attributes for a barangay (population, hazard, land use, etc.)
     */
    public function updateAttributes(Request $request, Barangay $barangay)
    {
        $validated = $request->validate([
            'district' => 'nullable|string|max:255',
            'barangay_chairman' => 'nullable|string|max:255',
            'sk_chairman' => 'nullable|string|max:255',
            'population' => 'nullable|string|max:255',
            'land_use' => 'nullable|string|max:255',
            'hazard_level' => 'nullable|string|max:255',
            'total_area' => 'nullable|numeric',
            'agri_area' => 'nullable|numeric',
            'residential_area' => 'nullable|numeric',
            'commercial_area' => 'nullable|numeric',
            'unidentified_area' => 'nullable|numeric',
            'description' => 'nullable|string',
        ]);

        $barangay->update($validated);

        app(ActivityLogger::class)->log('barangay.attributes_updated', "Updated attributes for {$barangay->name}.", $barangay, [
            'fields' => array_keys($validated),
        ], $request);

        $this->refreshPostgisGeometry('barangay.attributes_updated');

        return back()->with('success', 'Attributes updated successfully for ' . $barangay->name . '!');
    }

    // ─── GeoJSON Parser ─────────────────────────────────────────────────

    private function parseGeoJson($file)
    {
        $content = file_get_contents($file->getRealPath());
        $geojson = json_decode($content, true);

        if (!$geojson) {
            throw new \Exception('Invalid GeoJSON format — could not parse JSON.');
        }

        $coordinates = null;

        foreach ($this->geoJsonFeatures($geojson) as $feature) {
            $coords = $this->extractPolygonFromGeometry($feature['geometry'] ?? null);
            if ($coords) {
                $coordinates = $coords;
                break;
            }
        }

        if (!$coordinates) {
            throw new \Exception('No valid polygon geometry found in the GeoJSON file.');
        }

        // GeoJSON uses [lng, lat] — convert to [lat, lng] for Leaflet
        $boundary = array_map(function ($coord) {
            return [$coord[1], $coord[0]];
        }, $coordinates);

        $centroid = $this->calculateCentroid($boundary);

        return ['boundary' => $boundary, 'centroid' => $centroid];
    }

    private function extractPolygonFromGeometry($geometry)
    {
        if (!$geometry || !isset($geometry['type'])) return null;

        if ($geometry['type'] === 'Polygon') {
            return $this->validOuterRing($geometry['coordinates'][0] ?? null);
        }

        if ($geometry['type'] === 'MultiPolygon') {
            return $this->validOuterRing($geometry['coordinates'][0][0] ?? null);
        }

        return null;
    }

    private function geoJsonFeatures(array $geojson): array
    {
        $type = $geojson['type'] ?? null;

        if ($type === 'FeatureCollection') {
            $features = $geojson['features'] ?? null;

            if (! is_array($features)) {
                throw new \Exception('Invalid GeoJSON FeatureCollection — features must be an array.');
            }

            return $features;
        }

        if ($type === 'Feature') {
            return [$geojson];
        }

        if (in_array($type, ['Polygon', 'MultiPolygon'], true)) {
            return [[
                'type' => 'Feature',
                'properties' => [],
                'geometry' => $geojson,
            ]];
        }

        throw new \Exception('GeoJSON must be a FeatureCollection, Feature, Polygon, or MultiPolygon.');
    }

    private function validOuterRing($ring): ?array
    {
        if (! is_array($ring) || count($ring) < 4) {
            return null;
        }

        $coordinates = [];

        foreach ($ring as $position) {
            if (! is_array($position) || count($position) < 2) {
                return null;
            }

            $lng = $position[0];
            $lat = $position[1];

            if (! is_numeric($lng) || ! is_numeric($lat)) {
                return null;
            }

            $lng = (float) $lng;
            $lat = (float) $lat;

            if ($lng < -180 || $lng > 180 || $lat < -90 || $lat > 90) {
                return null;
            }

            $coordinates[] = [$lng, $lat];
        }

        if ($coordinates[0] !== $coordinates[count($coordinates) - 1]) {
            $coordinates[] = $coordinates[0];
        }

        $uniquePoints = collect($coordinates)
            ->map(fn (array $point) => $point[0].','.$point[1])
            ->unique()
            ->count();

        return $uniquePoints >= 3 ? $coordinates : null;
    }

    // ─── Shapefile ZIP Parser ───────────────────────────────────────────

    private function parseShapefileZip($zipFile)
    {
        $extractPath = storage_path('app/temp_shp_' . uniqid());
        mkdir($extractPath, 0755, true);

        $zip = new \ZipArchive();
        if ($zip->open($zipFile->getRealPath()) !== true) {
            throw new \Exception('Could not open ZIP file.');
        }
        $zip->extractTo($extractPath);
        $zip->close();

        // Find the .shp file inside
        $shpFile = null;
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($extractPath));
        foreach ($iterator as $file) {
            if (strtolower($file->getExtension()) === 'shp') {
                $shpFile = $file->getRealPath();
                break;
            }
        }

        if (!$shpFile) {
            $this->cleanupDir($extractPath);
            throw new \Exception('No .shp file found inside the ZIP archive.');
        }

        // Check for required companion files
        $baseName = substr($shpFile, 0, -4);
        $shxFile = $baseName . '.shx';
        $dbfFile = $baseName . '.dbf';

        if (!file_exists($shxFile)) {
            // Try case-insensitive
            $shxFile = $baseName . '.SHX';
        }
        if (!file_exists($dbfFile)) {
            $dbfFile = $baseName . '.DBF';
        }

        if (!file_exists($shxFile) || !file_exists($dbfFile)) {
            $this->cleanupDir($extractPath);
            throw new \Exception('ZIP must contain .shp, .shx, and .dbf files together.');
        }

        try {
            $result = $this->readShapefileFromPath($shpFile);
        } finally {
            $this->cleanupDir($extractPath);
        }

        return $result;
    }

    private function parseShapefile($shpFile, $shxFile, $dbfFile)
    {
        $extractPath = storage_path('app/temp_shp_' . uniqid());
        mkdir($extractPath, 0755, true);

        $shpPath = $extractPath . '/data.shp';
        $shxPath = $extractPath . '/data.shx';
        $dbfPath = $extractPath . '/data.dbf';

        copy($shpFile->getRealPath(), $shpPath);
        copy($shxFile->getRealPath(), $shxPath);
        copy($dbfFile->getRealPath(), $dbfPath);

        try {
            $result = $this->readShapefileFromPath($shpPath);
        } finally {
            $this->cleanupDir($extractPath);
        }

        return $result;
    }

    /**
     * Read shapefile using a simple binary parser for Polygon shapes.
     * This avoids needing external PHP libraries.
     */
    private function readShapefileFromPath($shpPath)
    {
        $handle = fopen($shpPath, 'rb');
        if (!$handle) {
            throw new \Exception('Could not read .shp file.');
        }

        // Read file header (100 bytes)
        $header = fread($handle, 100);
        if (strlen($header) < 100) {
            fclose($handle);
            throw new \Exception('Invalid .shp file — header too short.');
        }

        // Check magic number (big-endian 9994)
        $fileCode = unpack('Ncode', substr($header, 0, 4))['code'];
        if ($fileCode !== 9994) {
            fclose($handle);
            throw new \Exception('Invalid .shp file — bad magic number.');
        }

        // Shape type at offset 32 (little-endian int32)
        $shapeType = unpack('Vtype', substr($header, 32, 4))['type'];
        // 5 = Polygon, 15 = PolygonZ, 25 = PolygonM
        if (!in_array($shapeType, [5, 15, 25])) {
            fclose($handle);
            throw new \Exception("Shapefile contains shape type {$shapeType} — only Polygon types (5/15/25) are supported.");
        }

        $coordinates = [];

        // Read records
        while (!feof($handle)) {
            // Record header: 8 bytes (record number + content length, big-endian)
            $recHeader = fread($handle, 8);
            if (strlen($recHeader) < 8) break;

            $recInfo = unpack('NrecNum/NcontentLen', $recHeader);
            $contentLen = $recInfo['contentLen'] * 2; // in bytes

            $recordData = fread($handle, $contentLen);
            if (strlen($recordData) < 4) break;

            // Shape type of this record
            $recShapeType = unpack('Vtype', substr($recordData, 0, 4))['type'];

            if (in_array($recShapeType, [5, 15, 25])) {
                // Polygon record layout:
                // 4 bytes: shape type
                // 32 bytes: bounding box (minx, miny, maxx, maxy) - doubles
                // 4 bytes: numParts (int32 LE)
                // 4 bytes: numPoints (int32 LE)
                // numParts * 4 bytes: part indices
                // numPoints * 16 bytes: points (x, y doubles)

                $offset = 4 + 32; // skip shape type and bbox
                $numParts = unpack('Vn', substr($recordData, $offset, 4))['n'];
                $offset += 4;
                $numPoints = unpack('Vn', substr($recordData, $offset, 4))['n'];
                $offset += 4;

                // Read part indices
                $parts = [];
                for ($i = 0; $i < $numParts; $i++) {
                    $parts[] = unpack('Vn', substr($recordData, $offset, 4))['n'];
                    $offset += 4;
                }

                // Read points
                $points = [];
                for ($i = 0; $i < $numPoints; $i++) {
                    $x = unpack('dx', substr($recordData, $offset, 8))['x']; // longitude
                    $offset += 8;
                    $y = unpack('dy', substr($recordData, $offset, 8))['y']; // latitude
                    $offset += 8;
                    $points[] = [$y, $x]; // [lat, lng] for Leaflet
                }

                // Use first part (outer ring) of first polygon
                if (count($points) > 0 && empty($coordinates)) {
                    $endIndex = isset($parts[1]) ? $parts[1] : count($points);
                    $coordinates = array_slice($points, $parts[0], $endIndex - $parts[0]);
                    break; // Use first polygon found
                }
            }
        }

        fclose($handle);

        if (empty($coordinates)) {
            throw new \Exception('No polygon geometry found in the Shapefile.');
        }

        $centroid = $this->calculateCentroid($coordinates);
        return ['boundary' => $coordinates, 'centroid' => $centroid];
    }

    // ─── KML Parser ─────────────────────────────────────────────────────

    private function parseKml($file)
    {
        $content = file_get_contents($file->getRealPath());
        
        // Remove namespace prefixes for simpler parsing
        $content = preg_replace('/xmlns[^=]*="[^"]*"/i', '', $content);
        $content = preg_replace('/<kml[^>]*>/', '<kml>', $content);
        
        $xml = simplexml_load_string($content);
        if (!$xml) {
            throw new \Exception('Invalid KML file — could not parse XML.');
        }

        $coordinates = $this->findKmlCoordinates($xml);

        if (!$coordinates || count($coordinates) < 3) {
            throw new \Exception('No polygon geometry found in the KML file.');
        }

        $centroid = $this->calculateCentroid($coordinates);
        return ['boundary' => $coordinates, 'centroid' => $centroid];
    }

    private function findKmlCoordinates($element)
    {
        // Look for Polygon > outerBoundaryIs > LinearRing > coordinates
        if (isset($element->Polygon)) {
            $coordStr = (string) $element->Polygon->outerBoundaryIs->LinearRing->coordinates;
            return $this->parseKmlCoordString($coordStr);
        }

        // Search children recursively
        foreach ($element->children() as $child) {
            $result = $this->findKmlCoordinates($child);
            if ($result) return $result;
        }

        return null;
    }

    private function parseKmlCoordString($str)
    {
        $str = trim($str);
        $pairs = preg_split('/\s+/', $str);
        $coords = [];
        foreach ($pairs as $pair) {
            $parts = explode(',', $pair);
            if (count($parts) >= 2) {
                $lng = floatval($parts[0]);
                $lat = floatval($parts[1]);
                $coords[] = [$lat, $lng]; // [lat, lng] for Leaflet
            }
        }
        return $coords;
    }

    // ─── Helpers ─────────────────────────────────────────────────────────

    private function calculateCentroid($points)
    {
        $latSum = 0;
        $lngSum = 0;
        $count = count($points);

        foreach ($points as $p) {
            $latSum += $p[0];
            $lngSum += $p[1];
        }

        return [
            'lat' => $count > 0 ? $latSum / $count : 0,
            'lng' => $count > 0 ? $lngSum / $count : 0,
        ];
    }

    private function calculatePolygonArea($points)
    {
        $count = count($points);
        if ($count < 3) return 0.0;

        $earthRadius = 6378137.0;
        $latSum = 0.0;

        foreach ($points as $point) {
            $latSum += $point[0];
        }

        $averageLatitude = ($latSum / $count) * M_PI / 180.0;
        $cosLatitude = cos($averageLatitude);
        $x = [];
        $y = [];

        foreach ($points as $point) {
            $x[] = $point[1] * (M_PI / 180.0) * $earthRadius * $cosLatitude;
            $y[] = $point[0] * (M_PI / 180.0) * $earthRadius;
        }

        $area = 0.0;
        for ($i = 0; $i < $count; $i++) {
            $j = ($i + 1) % $count;
            $area += $x[$i] * $y[$j];
            $area -= $y[$i] * $x[$j];
        }

        return abs($area) / 20000.0;
    }

    public function simulateEarthquake(Request $request)
    {
        $validated = $request->validate([
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'radius' => 'required|numeric|min:10|max:10000', // radius in meters
        ]);

        $lat = $validated['latitude'];
        $lng = $validated['longitude'];
        $radius = $validated['radius'];

        $driver = \Illuminate\Support\Facades\DB::getDriverName();

        if ($driver === 'sqlite') {
            return response()->json([
                'success' => true,
                'epicenter' => ['lat' => $lat, 'lng' => $lng],
                'radius' => $radius,
                'total_buildings' => 1,
                'total_people' => 8,
                'features' => [
                    [
                        'id' => 1,
                        'name' => 'Mock building',
                        'feature_type' => 'barangay_hall',
                        'latitude' => $lat,
                        'longitude' => $lng,
                        'occupants' => 8,
                        'distance_meters' => 10,
                    ]
                ],
                'type_breakdown' => [
                    'barangay_hall' => 8
                ]
            ]);
        }

        $features = \Illuminate\Support\Facades\DB::connection('postgis')
            ->select(<<<SQL
SELECT 
    id, 
    name, 
    feature_type, 
    latitude, 
    longitude,
    ST_Distance(
        geom::geography, 
        ST_SetSRID(ST_Point(:lng1, :lat1), 4326)::geography
    ) AS distance_meters,
    COALESCE(
        NULLIF(metadata->>'current_people_count', '')::numeric,
        NULLIF(metadata->'import_properties'->>'current_people_count', '')::numeric,
        NULLIF(metadata->>'capacity', '')::numeric,
        NULLIF(metadata->'import_properties'->>'capacity', '')::numeric,
        NULLIF(metadata->>'members', '')::numeric,
        CASE 
            WHEN feature_type = 'school' OR feature_type = 'covered_court' THEN 150
            WHEN feature_type = 'barangay_hall' OR feature_type = 'multipurpose_bldg' THEN 40
            WHEN feature_type = 'evac_center' THEN 80
            WHEN feature_type = 'health_center' OR feature_type = 'police_post' THEN 15
            ELSE 5
        END
    )::integer AS occupants
FROM map_features
WHERE geom IS NOT NULL
  AND ST_DWithin(
      geom::geography, 
      ST_SetSRID(ST_Point(:lng2, :lat2), 4326)::geography, 
      :radius
  )
ORDER BY distance_meters ASC
SQL, [
                'lng1' => $lng,
                'lat1' => $lat,
                'lng2' => $lng,
                'lat2' => $lat,
                'radius' => $radius,
            ]);

        $totalBuildings = count($features);
        $totalPeople = array_sum(array_column($features, 'occupants'));

        $typeBreakdown = [];
        foreach ($features as $f) {
            $type = $f->feature_type;
            $typeBreakdown[$type] = ($typeBreakdown[$type] ?? 0) + $f->occupants;
        }

        return response()->json([
            'success' => true,
            'epicenter' => ['lat' => $lat, 'lng' => $lng],
            'radius' => $radius,
            'total_buildings' => $totalBuildings,
            'total_people' => $totalPeople,
            'features' => $features,
            'type_breakdown' => $typeBreakdown,
        ]);
    }

    private function cleanupDir($dir)
    {
        if (!is_dir($dir)) return;
        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($items as $item) {
            $item->isDir() ? rmdir($item->getRealPath()) : unlink($item->getRealPath());
        }
        rmdir($dir);
    }
}
