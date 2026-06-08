<?php

namespace App\Services;

use Illuminate\Support\Facades\File;

class GisFileConverter
{
    public function convert(object $file, string $extension, ?string $fileName = null): array
    {
        $extension = strtolower($extension);

        $geoJson = match ($extension) {
            'geojson', 'json' => $this->normalizeGeoJson($this->decodeJsonFile($file)),
            'kml' => $this->kmlToGeoJson($file),
            'zip' => $this->shapefileZipToGeoJson($file),
            default => throw new \Exception('Unsupported GIS format. Upload GeoJSON, KML, or a zipped Shapefile.'),
        };

        $featureCount = count($geoJson['features'] ?? []);

        if ($featureCount === 0) {
            throw new \Exception('No importable GIS features were found in the uploaded file.');
        }

        return [
            'geojson' => $geoJson,
            'source_format' => $this->sourceFormat($extension),
            'converted' => ! in_array($extension, ['geojson', 'json'], true),
            'feature_count' => $featureCount,
            'download_name' => $this->convertedFileName($fileName),
        ];
    }

    public function writeGeoJson(array $geoJson, string $path): void
    {
        File::put(
            $path,
            json_encode($geoJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)
        );
    }

    private function decodeJsonFile(object $file): array
    {
        $content = file_get_contents($file->getRealPath());
        $geoJson = json_decode($content, true);

        if (! is_array($geoJson)) {
            throw new \Exception('Invalid GeoJSON format - could not parse JSON.');
        }

        return $geoJson;
    }

    private function normalizeGeoJson(array $geoJson): array
    {
        $type = $geoJson['type'] ?? null;

        if ($type === 'FeatureCollection') {
            $features = $geoJson['features'] ?? null;

            if (! is_array($features)) {
                throw new \Exception('Invalid GeoJSON FeatureCollection - features must be an array.');
            }

            return [
                'type' => 'FeatureCollection',
                'features' => array_values(array_map(fn (array $feature) => $this->normalizeFeature($feature), $features)),
            ];
        }

        if ($type === 'Feature') {
            return [
                'type' => 'FeatureCollection',
                'features' => [$this->normalizeFeature($geoJson)],
            ];
        }

        if (in_array($type, ['Point', 'LineString', 'Polygon', 'MultiPolygon'], true)) {
            return [
                'type' => 'FeatureCollection',
                'features' => [[
                    'type' => 'Feature',
                    'properties' => [],
                    'geometry' => $geoJson,
                ]],
            ];
        }

        throw new \Exception('GeoJSON must be a FeatureCollection, Feature, or supported geometry.');
    }

    private function normalizeFeature(array $feature): array
    {
        return [
            'type' => 'Feature',
            'properties' => is_array($feature['properties'] ?? null) ? $feature['properties'] : [],
            'geometry' => is_array($feature['geometry'] ?? null) ? $feature['geometry'] : null,
        ];
    }

    private function kmlToGeoJson(object $file): array
    {
        $content = file_get_contents($file->getRealPath());

        $previous = libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        $loaded = $dom->loadXML($content);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if (! $loaded) {
            throw new \Exception('Invalid KML file - could not parse XML.');
        }

        $xpath = new \DOMXPath($dom);
        $placemarks = $xpath->query('//*[local-name()="Placemark"]');
        $features = [];

        foreach ($placemarks as $index => $placemark) {
            $geometry = $this->kmlGeometry($xpath, $placemark);

            if (! $geometry) {
                continue;
            }

            $properties = $this->kmlProperties($xpath, $placemark);
            $properties['name'] ??= 'KML Feature '.($index + 1);

            $features[] = [
                'type' => 'Feature',
                'properties' => $properties,
                'geometry' => $geometry,
            ];
        }

        if ($features === []) {
            $geometry = $this->kmlGeometry($xpath, $dom);

            if ($geometry) {
                $features[] = [
                    'type' => 'Feature',
                    'properties' => ['name' => 'KML Feature 1'],
                    'geometry' => $geometry,
                ];
            }
        }

        if ($features === []) {
            throw new \Exception('KML file has no supported Point, LineString, or Polygon geometry.');
        }

        return [
            'type' => 'FeatureCollection',
            'features' => $features,
        ];
    }

    private function kmlProperties(\DOMXPath $xpath, \DOMNode $node): array
    {
        $properties = [];
        $name = $this->firstNodeText($xpath, './*[local-name()="name"]', $node);
        $description = $this->firstNodeText($xpath, './*[local-name()="description"]', $node);

        if ($name !== null) {
            $properties['name'] = $name;
        }

        if ($description !== null) {
            $properties['description'] = $description;
        }

        foreach ($xpath->query('.//*[local-name()="ExtendedData"]//*[local-name()="Data"]', $node) as $data) {
            $key = $data instanceof \DOMElement ? $data->getAttribute('name') : null;
            $value = $this->firstNodeText($xpath, './*[local-name()="value"]', $data);

            if ($key && $value !== null) {
                $properties[$key] = $value;
            }
        }

        foreach ($xpath->query('.//*[local-name()="SimpleData"]', $node) as $data) {
            $key = $data instanceof \DOMElement ? $data->getAttribute('name') : null;
            $value = trim($data->textContent ?? '');

            if ($key && $value !== '') {
                $properties[$key] = $value;
            }
        }

        return $properties;
    }

    private function kmlGeometry(\DOMXPath $xpath, \DOMNode $node): ?array
    {
        $polygon = $xpath->query('.//*[local-name()="Polygon"]', $node)->item(0);

        if ($polygon) {
            $coordinates = $this->firstNodeText($xpath, './/*[local-name()="outerBoundaryIs"]//*[local-name()="LinearRing"]/*[local-name()="coordinates"]', $polygon)
                ?? $this->firstNodeText($xpath, './/*[local-name()="coordinates"]', $polygon);
            $ring = $this->parseKmlCoordinates($coordinates);

            if (count($ring) >= 3) {
                if (! $this->sameCoordinate($ring[0], $ring[count($ring) - 1])) {
                    $ring[] = $ring[0];
                }

                return [
                    'type' => 'Polygon',
                    'coordinates' => [$ring],
                ];
            }
        }

        $line = $xpath->query('.//*[local-name()="LineString"]', $node)->item(0);

        if ($line) {
            $coordinates = $this->firstNodeText($xpath, './/*[local-name()="coordinates"]', $line);
            $points = $this->parseKmlCoordinates($coordinates);

            if (count($points) >= 2) {
                return [
                    'type' => 'LineString',
                    'coordinates' => $points,
                ];
            }
        }

        $point = $xpath->query('.//*[local-name()="Point"]', $node)->item(0);

        if ($point) {
            $coordinates = $this->firstNodeText($xpath, './/*[local-name()="coordinates"]', $point);
            $points = $this->parseKmlCoordinates($coordinates);

            if (count($points) >= 1) {
                return [
                    'type' => 'Point',
                    'coordinates' => $points[0],
                ];
            }
        }

        return null;
    }

    private function parseKmlCoordinates(?string $coordinates): array
    {
        if ($coordinates === null) {
            return [];
        }

        return collect(preg_split('/\s+/', trim($coordinates)) ?: [])
            ->map(function (string $pair) {
                $parts = explode(',', trim($pair));

                if (count($parts) < 2 || ! $this->validLngLat($parts[0], $parts[1])) {
                    return null;
                }

                return [(float) $parts[0], (float) $parts[1]];
            })
            ->filter()
            ->values()
            ->all();
    }

    private function firstNodeText(\DOMXPath $xpath, string $query, \DOMNode $node): ?string
    {
        $item = $xpath->query($query, $node)->item(0);
        $value = $item ? trim($item->textContent ?? '') : '';

        return $value !== '' ? $value : null;
    }

    private function shapefileZipToGeoJson(object $file): array
    {
        $extractPath = storage_path('app/temp_converter_shp_'.uniqid());
        File::ensureDirectoryExists($extractPath);

        $zip = new \ZipArchive();

        if ($zip->open($file->getRealPath()) !== true) {
            throw new \Exception('Could not open Shapefile ZIP.');
        }

        $zip->extractTo($extractPath);
        $zip->close();

        try {
            [$shpPath, $dbfPath] = $this->findShapefileParts($extractPath);
            $attributes = $dbfPath ? $this->parseDbf($dbfPath) : [];
            $features = $this->readShapefileFeatures($shpPath, $attributes);
        } finally {
            File::deleteDirectory($extractPath);
        }

        if ($features === []) {
            throw new \Exception('Shapefile has no supported WGS84 Point, PolyLine, or Polygon geometry.');
        }

        return [
            'type' => 'FeatureCollection',
            'features' => $features,
        ];
    }

    private function findShapefileParts(string $extractPath): array
    {
        $shpPath = null;
        $dbfCandidates = [];

        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($extractPath));

        foreach ($iterator as $file) {
            if (! $file instanceof \SplFileInfo || $file->isDir()) {
                continue;
            }

            $extension = strtolower($file->getExtension());

            if ($extension === 'shp' && ! $shpPath) {
                $shpPath = $file->getRealPath();
            }

            if ($extension === 'dbf') {
                $dbfCandidates[] = $file->getRealPath();
            }
        }

        if (! $shpPath) {
            throw new \Exception('ZIP must contain a .shp file.');
        }

        $baseName = strtolower(pathinfo($shpPath, PATHINFO_FILENAME));
        $dbfPath = collect($dbfCandidates)->first(fn (string $path) => strtolower(pathinfo($path, PATHINFO_FILENAME)) === $baseName)
            ?? ($dbfCandidates[0] ?? null);

        return [$shpPath, $dbfPath];
    }

    private function parseDbf(string $dbfPath): array
    {
        $handle = fopen($dbfPath, 'rb');

        if (! $handle) {
            return [];
        }

        $header = fread($handle, 32);

        if (strlen($header) < 32) {
            fclose($handle);

            return [];
        }

        $numRecords = unpack('V', substr($header, 4, 4))[1];
        $headerLength = unpack('v', substr($header, 8, 2))[1];
        $recordLength = unpack('v', substr($header, 10, 2))[1];
        $fields = [];

        while (! feof($handle)) {
            $fieldData = fread($handle, 32);

            if (strlen($fieldData) < 32 || ord($fieldData[0]) === 0x0D) {
                break;
            }

            $fieldName = trim(str_replace("\0", '', substr($fieldData, 0, 11)));
            $fieldLength = ord($fieldData[16]);

            if ($fieldName !== '' && $fieldLength > 0) {
                $fields[] = ['name' => $fieldName, 'length' => $fieldLength];
            }
        }

        fseek($handle, $headerLength);
        $records = [];

        for ($index = 0; $index < $numRecords; $index++) {
            $recordData = fread($handle, $recordLength);

            if (strlen($recordData) < $recordLength) {
                break;
            }

            if ($recordData[0] === '*') {
                $records[] = [];
                continue;
            }

            $offset = 1;
            $row = [];

            foreach ($fields as $field) {
                $value = substr($recordData, $offset, $field['length']);
                $row[$field['name']] = trim(str_replace("\0", '', $value));
                $offset += $field['length'];
            }

            $records[] = array_filter($row, fn ($value) => $value !== '');
        }

        fclose($handle);

        return $records;
    }

    private function readShapefileFeatures(string $shpPath, array $attributes): array
    {
        $handle = fopen($shpPath, 'rb');

        if (! $handle) {
            throw new \Exception('Could not read .shp file.');
        }

        $header = fread($handle, 100);

        if (strlen($header) < 100) {
            fclose($handle);
            throw new \Exception('Invalid .shp file - header too short.');
        }

        $fileCode = unpack('Ncode', substr($header, 0, 4))['code'];

        if ($fileCode !== 9994) {
            fclose($handle);
            throw new \Exception('Invalid .shp file - bad magic number.');
        }

        $features = [];
        $recordIndex = 0;

        while (! feof($handle)) {
            $recordHeader = fread($handle, 8);

            if (strlen($recordHeader) < 8) {
                break;
            }

            $recordInfo = unpack('NrecordNumber/NcontentLength', $recordHeader);
            $recordData = fread($handle, $recordInfo['contentLength'] * 2);

            if (strlen($recordData) < 4) {
                break;
            }

            $shapeType = unpack('Vtype', substr($recordData, 0, 4))['type'];
            $geometry = $this->shapefileGeometry($shapeType, $recordData);

            if ($geometry) {
                $features[] = [
                    'type' => 'Feature',
                    'properties' => $attributes[$recordIndex] ?? [],
                    'geometry' => $geometry,
                ];
            }

            $recordIndex++;
        }

        fclose($handle);

        return $features;
    }

    private function shapefileGeometry(int $shapeType, string $recordData): ?array
    {
        return match (true) {
            in_array($shapeType, [1, 11, 21], true) => $this->shapefilePointGeometry($recordData),
            in_array($shapeType, [3, 13, 23], true) => $this->shapefilePartsGeometry($recordData, 'LineString'),
            in_array($shapeType, [5, 15, 25], true) => $this->shapefilePartsGeometry($recordData, 'Polygon'),
            default => null,
        };
    }

    private function shapefilePointGeometry(string $recordData): ?array
    {
        if (strlen($recordData) < 20) {
            return null;
        }

        $lng = unpack('d', substr($recordData, 4, 8))[1];
        $lat = unpack('d', substr($recordData, 12, 8))[1];

        if (! $this->validLngLat($lng, $lat)) {
            return null;
        }

        return [
            'type' => 'Point',
            'coordinates' => [(float) $lng, (float) $lat],
        ];
    }

    private function shapefilePartsGeometry(string $recordData, string $geoJsonType): ?array
    {
        if (strlen($recordData) < 44) {
            return null;
        }

        $offset = 4 + 32;
        $numParts = unpack('V', substr($recordData, $offset, 4))[1];
        $offset += 4;
        $numPoints = unpack('V', substr($recordData, $offset, 4))[1];
        $offset += 4;

        if ($numParts < 1 || $numPoints < 1) {
            return null;
        }

        $parts = [];

        for ($index = 0; $index < $numParts; $index++) {
            $parts[] = unpack('V', substr($recordData, $offset, 4))[1];
            $offset += 4;
        }

        $points = [];

        for ($index = 0; $index < $numPoints; $index++) {
            if (strlen($recordData) < $offset + 16) {
                return null;
            }

            $lng = unpack('d', substr($recordData, $offset, 8))[1];
            $offset += 8;
            $lat = unpack('d', substr($recordData, $offset, 8))[1];
            $offset += 8;

            if (! $this->validLngLat($lng, $lat)) {
                return null;
            }

            $points[] = [(float) $lng, (float) $lat];
        }

        $start = $parts[0];
        $end = $parts[1] ?? count($points);
        $partPoints = array_slice($points, $start, $end - $start);

        if ($geoJsonType === 'LineString') {
            return count($partPoints) >= 2
                ? ['type' => 'LineString', 'coordinates' => $partPoints]
                : null;
        }

        if (count($partPoints) < 3) {
            return null;
        }

        if (! $this->sameCoordinate($partPoints[0], $partPoints[count($partPoints) - 1])) {
            $partPoints[] = $partPoints[0];
        }

        return [
            'type' => 'Polygon',
            'coordinates' => [$partPoints],
        ];
    }

    private function validLngLat(mixed $lng, mixed $lat): bool
    {
        if (! is_numeric($lng) || ! is_numeric($lat)) {
            return false;
        }

        $lng = (float) $lng;
        $lat = (float) $lat;

        return $lng >= -180 && $lng <= 180 && $lat >= -90 && $lat <= 90;
    }

    private function sameCoordinate(array $a, array $b): bool
    {
        return abs($a[0] - $b[0]) < 0.0000001 && abs($a[1] - $b[1]) < 0.0000001;
    }

    private function sourceFormat(string $extension): string
    {
        return match ($extension) {
            'geojson', 'json' => 'GeoJSON',
            'kml' => 'KML',
            'zip' => 'Shapefile ZIP',
            default => strtoupper($extension),
        };
    }

    private function convertedFileName(?string $fileName): string
    {
        $baseName = $fileName ? pathinfo($fileName, PATHINFO_FILENAME) : 'converted-gis-data';
        $slug = str($baseName)->slug()->value() ?: 'converted-gis-data';

        return $slug.'-converted.geojson';
    }
}
