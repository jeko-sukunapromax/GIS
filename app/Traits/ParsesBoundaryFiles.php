<?php

namespace App\Traits;

use App\Models\Barangay;

trait ParsesBoundaryFiles
{
    /**
     * Parse GeoJSON and attempt to map all polygons to barangays by name.
     */
    protected function parseBulkGeoJson($file, $fallbackName = null)
    {
        $content = file_get_contents($file->getRealPath());
        $geojson = json_decode($content, true);

        if (!$geojson) {
            throw new \Exception('Invalid GeoJSON format — could not parse JSON.');
        }

        $features = [];
        if (isset($geojson['type']) && $geojson['type'] === 'FeatureCollection') {
            $features = $geojson['features'];
        } elseif (isset($geojson['type']) && $geojson['type'] === 'Feature') {
            $features = [$geojson];
        }

        $matchedCount = 0;
        $unmatched = [];

        foreach ($features as $feature) {
            $coords = $this->extractPolygonFromGeometry($feature['geometry'] ?? null);
            if (!$coords) continue;

            $props = $feature['properties'] ?? [];
            
            // Try to find a barangay name in common property fields
            $name = $this->extractNameFromProperties($props);
            
            // Fallback to filename if no name found and it's a single feature or we want to force it
            if (!$name && $fallbackName) {
                // Remove extension and typical suffix
                $name = preg_replace('/\.(geojson|json|shp|kml|zip)$/i', '', $fallbackName);
                $name = str_replace('_', ' ', $name);
            }
            
            $boundary = array_map(function ($coord) {
                return [$coord[1], $coord[0]]; // [lat, lng]
            }, $coords);
            $centroid = $this->calculateCentroid($boundary);
            $extractedAttrs = $this->extractAttributesFromProperties($props);

            // Automatically calculate Total Area (Hectares) from geometry if not provided in properties
            if (!isset($extractedAttrs['total_area']) || empty($extractedAttrs['total_area'])) {
                $computedArea = $this->calculatePolygonArea($boundary);
                $extractedAttrs['total_area'] = round($computedArea, 4);
                
                // Set unidentified_area to match the computed total area if empty
                if (!isset($extractedAttrs['unidentified_area']) || empty($extractedAttrs['unidentified_area'])) {
                    $extractedAttrs['unidentified_area'] = round($computedArea, 4);
                }
            }

            if ($name) {
                // Clean the name for display (capitalize properly, etc)
                $displayName = ucwords(strtolower($name));
                
                $barangay = $this->findBarangayByName($name);
                if ($barangay) {
                    $barangay->update(array_merge([
                        'boundary' => $boundary,
                        'latitude' => $centroid['lat'],
                        'longitude' => $centroid['lng'],
                        'boundary_source' => 'Bulk GeoJSON Upload',
                        'boundary_updated_at' => now(),
                    ], $extractedAttrs));
                    $matchedCount++;
                } else {
                    // Automatically create missing barangay!
                    Barangay::create(array_merge([
                        'name' => $displayName,
                        'status' => 'Active',
                        'is_visible' => true,
                        'boundary' => $boundary,
                        'latitude' => $centroid['lat'],
                        'longitude' => $centroid['lng'],
                        'boundary_source' => 'Bulk GeoJSON Upload',
                        'boundary_updated_at' => now(),
                    ], $extractedAttrs));
                    $unmatched[] = $displayName . " (Created)";
                }
            }
        }

        return [
            'matched' => $matchedCount,
            'unmatched' => $unmatched
        ];
    }

    /**
     * Parse ZIP containing SHP and DBF for bulk upload.
     */
    protected function parseBulkShapefileZip($zipFile, $fallbackName = null)
    {
        $extractPath = storage_path('app/temp_shp_' . uniqid());
        mkdir($extractPath, 0755, true);

        $zip = new \ZipArchive();
        if ($zip->open($zipFile->getRealPath()) !== true) {
            throw new \Exception('Could not open ZIP file.');
        }
        $zip->extractTo($extractPath);
        $zip->close();

        $shpFile = null;
        $dbfFile = null;
        
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($extractPath));
        foreach ($iterator as $file) {
            $ext = strtolower($file->getExtension());
            if ($ext === 'shp') $shpFile = $file->getRealPath();
            if ($ext === 'dbf') $dbfFile = $file->getRealPath();
        }

        if (!$shpFile || !$dbfFile) {
            $this->cleanupDir($extractPath);
            throw new \Exception('ZIP must contain both .shp and .dbf files for bulk processing.');
        }

        try {
            $attributes = $this->parseDbf($dbfFile);
            $geometries = $this->readAllShapefilePolygons($shpFile);

            $matchedCount = 0;
            $unmatched = [];

            // Combine attributes with geometries based on index
            foreach ($geometries as $index => $boundary) {
                $props = $attributes[$index] ?? [];
                $name = $this->extractNameFromProperties($props);
                
                if (!$name && $fallbackName && count($geometries) === 1) {
                    $name = preg_replace('/\.(geojson|json|shp|kml|zip)$/i', '', $fallbackName);
                    $name = str_replace('_', ' ', $name);
                }
                
                $centroid = $this->calculateCentroid($boundary);
                $extractedAttrs = $this->extractAttributesFromProperties($props);

                // Automatically calculate Total Area (Hectares) from geometry if not provided in properties
                if (!isset($extractedAttrs['total_area']) || empty($extractedAttrs['total_area'])) {
                    $computedArea = $this->calculatePolygonArea($boundary);
                    $extractedAttrs['total_area'] = round($computedArea, 4);
                    
                    if (!isset($extractedAttrs['unidentified_area']) || empty($extractedAttrs['unidentified_area'])) {
                        $extractedAttrs['unidentified_area'] = round($computedArea, 4);
                    }
                }

                if ($name) {
                    $displayName = ucwords(strtolower($name));
                    
                    $barangay = $this->findBarangayByName($name);
                    if ($barangay) {
                        $barangay->update(array_merge([
                            'boundary' => $boundary,
                            'latitude' => $centroid['lat'],
                            'longitude' => $centroid['lng'],
                            'boundary_source' => 'Bulk Shapefile Upload',
                            'boundary_updated_at' => now(),
                        ], $extractedAttrs));
                        $matchedCount++;
                    } else {
                        // Automatically create missing barangay!
                        Barangay::create(array_merge([
                            'name' => $displayName,
                            'status' => 'Active',
                            'is_visible' => true,
                            'boundary' => $boundary,
                            'latitude' => $centroid['lat'],
                            'longitude' => $centroid['lng'],
                            'boundary_source' => 'Bulk Shapefile Upload',
                            'boundary_updated_at' => now(),
                        ], $extractedAttrs));
                        $unmatched[] = $displayName . " (Created)";
                    }
                }
            }
        } finally {
            $this->cleanupDir($extractPath);
        }

        return [
            'matched' => $matchedCount,
            'unmatched' => $unmatched
        ];
    }

    /**
     * Extract name from common attribute keys
     */
    protected function extractNameFromProperties($props)
    {
        $nameKeys = ['NAME_3', 'NAME_4', 'BGY_NAME', 'BRGY_NAME', 'BARANGAY', 'NAME', 'BRGY', 'ADM4_EN'];
        foreach ($nameKeys as $key) {
            // Case-insensitive key search
            foreach ($props as $k => $v) {
                if (strcasecmp($k, $key) === 0 && !empty($v)) {
                    return $v;
                }
            }
        }
        return null;
    }

    /**
     * Find barangay in database using flexible string matching
     */
    protected function findBarangayByName($name)
    {
        // Clean strings (remove "Brgy.", "Barangay", trim spaces)
        $cleanName = trim(preg_replace('/^(brgy\.?|barangay)\s+/i', '', $name));
        
        return Barangay::where('name', 'LIKE', '%' . $cleanName . '%')->first();
    }

    /**
     * Automatically extract key metrics/attributes from the GIS feature/DBF properties
     */
    protected function extractAttributesFromProperties($props)
    {
        $attributes = [];
        
        // Helper to find case-insensitive keys
        $findValue = function($keys, $properties) {
            foreach ($keys as $key) {
                foreach ($properties as $propKey => $propVal) {
                    if (strcasecmp($propKey, $key) === 0) {
                        return $propVal;
                    }
                }
            }
            return null;
        };

        // Extract Population
        $pop = $findValue(['population', 'pop', 'total_pop', 'pop_2020', 'pop2020', 'pop_2023', 'pop2023'], $props);
        if ($pop !== null) {
            $attributes['population'] = (int) filter_var($pop, FILTER_SANITIZE_NUMBER_INT);
        }

        // Extract Total Area
        $area = $findValue(['area', 'total_area', 'hectares', 'land_area', 'sqm', 'area_ha', 'ha'], $props);
        if ($area !== null) {
            $areaVal = (float) filter_var($area, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
            // If the value is in sqm, convert to hectares (sqm / 10000)
            if ($areaVal > 100000) { 
                $areaVal = $areaVal / 10000;
            }
            $attributes['total_area'] = $areaVal;
        }

        // Extract Hazard Level
        $hazard = $findValue(['hazard', 'hazard_level', 'risk', 'risk_level', 'hazard_lvl'], $props);
        if ($hazard !== null) {
            $attributes['hazard_level'] = ucwords(strtolower(trim($hazard)));
        }

        // Extract Land Use
        $landUse = $findValue(['landuse', 'primary_landuse', 'land_use', 'use'], $props);
        if ($landUse !== null) {
            $attributes['land_use'] = trim($landUse);
        }

        // Extract Agricultural Area
        $agri = $findValue(['agri_area', 'agricultural', 'agri'], $props);
        if ($agri !== null) {
            $attributes['agri_area'] = (float) $agri;
        }

        // Extract Residential Area
        $res = $findValue(['residential_area', 'residential', 'res_area'], $props);
        if ($res !== null) {
            $attributes['residential_area'] = (float) $res;
        }

        // Extract Commercial Area
        $comm = $findValue(['commercial_area', 'commercial', 'comm_area'], $props);
        if ($comm !== null) {
            $attributes['commercial_area'] = (float) $comm;
        }

        // Extract Unidentified Area
        $unidentified = $findValue(['unidentified_area', 'unidentified', 'other_area'], $props);
        if ($unidentified !== null) {
            $attributes['unidentified_area'] = (float) $unidentified;
        }

        // Extract Description
        $desc = $findValue(['description', 'notes', 'desc', 'remarks'], $props);
        if ($desc !== null) {
            $attributes['description'] = trim($desc);
        }

        // Filter out empty or null values to prevent overwriting existing data with empty values
        return array_filter($attributes, function ($val) {
            return $val !== null && $val !== '';
        });
    }

    /**
     * Compute polygon area in Hectares mathematically using spherical projection and Shoelace formula
     */
    protected function calculatePolygonArea($points)
    {
        $count = count($points);
        if ($count < 3) return 0.0;

        // Earth's mean radius in meters
        $r = 6378137.0; 
        
        // Calculate average latitude to scale longitude degree sizes
        $latSum = 0;
        foreach ($points as $p) {
            $latSum += $p[0];
        }
        $avgLat = ($latSum / $count) * M_PI / 180.0;
        $cosLat = cos($avgLat);

        // Convert spherical degrees to Cartesian local meters projection
        $x = [];
        $y = [];
        foreach ($points as $p) {
            $x[] = $p[1] * (M_PI / 180.0) * $r * $cosLat;
            $y[] = $p[0] * (M_PI / 180.0) * $r;
        }

        // Apply Cartesian Shoelace Formula
        $area = 0.0;
        for ($i = 0; $i < $count; $i++) {
            $j = ($i + 1) % $count;
            $area += $x[$i] * $y[$j];
            $area -= $y[$i] * $x[$j];
        }
        
        $area = abs($area) / 2.0; // Area in square meters
        
        // Convert to Hectares (1 Hectare = 10,000 square meters)
        return $area / 10000.0;
    }

    // --- DBF Parser (Extracts Attributes) ---
    protected function parseDbf($dbfPath) {
        $handle = fopen($dbfPath, 'rb');
        if (!$handle) return [];
        
        $header = fread($handle, 32);
        if (strlen($header) < 32) return [];

        $numRecords = unpack('V', substr($header, 4, 4))[1];
        $headerLength = unpack('v', substr($header, 8, 2))[1];
        $recordLength = unpack('v', substr($header, 10, 2))[1];
        
        $fields = [];
        while (!feof($handle)) {
            $fieldData = fread($handle, 32);
            if (strlen($fieldData) < 32 || ord($fieldData[0]) === 0x0D) break; 
            
            $fieldName = trim(substr($fieldData, 0, 11));
            $fieldLen = ord($fieldData[16]);
            if ($fieldLen > 0) {
                $fields[] = ['name' => $fieldName, 'len' => $fieldLen];
            }
        }
        
        fseek($handle, $headerLength);
        $records = [];
        for ($i = 0; $i < $numRecords; $i++) {
            $recordData = fread($handle, $recordLength);
            if (strlen($recordData) < $recordLength) break;
            
            // First byte is deletion flag (space = valid, * = deleted)
            if ($recordData[0] === '*') continue;
            
            $offset = 1;
            $row = [];
            foreach ($fields as $field) {
                $val = substr($recordData, $offset, $field['len']);
                // Remove null bytes and trim
                $row[$field['name']] = trim(str_replace("\0", '', $val));
                $offset += $field['len'];
            }
            $records[] = $row;
        }
        fclose($handle);
        return $records;
    }

    // --- SHP Parser (Extracts ALL Geometries) ---
    protected function readAllShapefilePolygons($shpPath)
    {
        $handle = fopen($shpPath, 'rb');
        if (!$handle) return [];

        $header = fread($handle, 100);
        $geometries = [];

        while (!feof($handle)) {
            $recHeader = fread($handle, 8);
            if (strlen($recHeader) < 8) break;

            $recInfo = unpack('NrecNum/NcontentLen', $recHeader);
            $contentLen = $recInfo['contentLen'] * 2;
            $recordData = fread($handle, $contentLen);
            if (strlen($recordData) < 4) break;

            $recShapeType = unpack('Vtype', substr($recordData, 0, 4))['type'];
            
            if (in_array($recShapeType, [5, 15, 25])) {
                $offset = 4 + 32; 
                $numParts = unpack('Vn', substr($recordData, $offset, 4))['n'];
                $offset += 4;
                $numPoints = unpack('Vn', substr($recordData, $offset, 4))['n'];
                $offset += 4;

                $parts = [];
                for ($i = 0; $i < $numParts; $i++) {
                    $parts[] = unpack('Vn', substr($recordData, $offset, 4))['n'];
                    $offset += 4;
                }

                $points = [];
                for ($i = 0; $i < $numPoints; $i++) {
                    $x = unpack('dx', substr($recordData, $offset, 8))['x']; 
                    $offset += 8;
                    $y = unpack('dy', substr($recordData, $offset, 8))['y']; 
                    $offset += 8;
                    $points[] = [$y, $x]; // Leaflet format [lat, lng]
                }

                if (count($points) > 0) {
                    $endIndex = isset($parts[1]) ? $parts[1] : count($points);
                    $coordinates = array_slice($points, $parts[0], $endIndex - $parts[0]);
                    $geometries[] = $coordinates;
                } else {
                    $geometries[] = []; // Keep index aligned with DBF
                }
            } else {
                $geometries[] = []; // Empty for non-polygons to maintain index
            }
        }
        fclose($handle);
        return $geometries;
    }

    // --- Helpers ---
    protected function extractPolygonFromGeometry($geometry)
    {
        if (!$geometry || !isset($geometry['type'])) return null;
        if ($geometry['type'] === 'Polygon') return $geometry['coordinates'][0]; 
        if ($geometry['type'] === 'MultiPolygon') return $geometry['coordinates'][0][0]; 
        return null;
    }

    protected function calculateCentroid($points)
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

    protected function cleanupDir($dir)
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
