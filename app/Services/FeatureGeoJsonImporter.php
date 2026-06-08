<?php

namespace App\Services;

use App\Models\Barangay;
use App\Models\MapFeature;
use App\Models\MapLayerType;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class FeatureGeoJsonImporter
{
    public function __construct(private LayerMetadataSchema $metadataSchema) {}

    public function preview(object $file, ?int $defaultBarangayId = null, ?string $defaultFeatureType = null, ?string $fallbackName = null): array
    {
        $geoJson = $this->decodeGeoJson($file);
        $features = $this->geoJsonFeatures($geoJson);
        $layerTypes = MapLayerType::query()->where('is_active', true)->get();
        $barangays = Barangay::query()->where('is_municipal_boundary', false)->get();
        $items = [];

        foreach ($features as $index => $feature) {
            $items[] = $this->previewFeature(
                feature: $feature,
                index: $index,
                layerTypes: $layerTypes,
                barangays: $barangays,
                defaultBarangayId: $defaultBarangayId,
                defaultFeatureType: $defaultFeatureType,
                fallbackName: $fallbackName,
            );
        }

        if (collect($items)->whereIn('action', ['Create', 'Update'])->isEmpty()) {
            throw new \Exception('GeoJSON has no importable map features. Select a default Barangay and Layer Type, or include valid barangay/feature_type properties in the GeoJSON.');
        }

        return $this->summarize($items);
    }

    public function import(object $file, ?int $defaultBarangayId = null, ?string $defaultFeatureType = null, ?string $fallbackName = null): array
    {
        $preview = $this->preview($file, $defaultBarangayId, $defaultFeatureType, $fallbackName);

        foreach ($preview['items'] as $item) {
            if ($item['action'] === 'Skipped') {
                continue;
            }

            $payload = [
                'barangay_id' => $item['barangay_id'],
                'name' => $item['display_name'],
                'layer_type' => $item['layer_category'],
                'feature_type' => $item['feature_type'],
                'latitude' => $item['latitude'],
                'longitude' => $item['longitude'],
                'coordinates' => $item['coordinates'],
                'metadata' => $item['metadata'],
                'is_public' => $item['is_public'],
                'status' => $item['status'],
            ];

            if ($item['existing_feature_id']) {
                MapFeature::whereKey($item['existing_feature_id'])->update($payload);
            } else {
                MapFeature::create($payload);
            }
        }

        return $preview;
    }

    private function decodeGeoJson(object $file): array
    {
        $content = file_get_contents($file->getRealPath());
        $geoJson = json_decode($content, true);

        if (! is_array($geoJson)) {
            throw new \Exception('Invalid GeoJSON format - could not parse JSON.');
        }

        return $geoJson;
    }

    private function geoJsonFeatures(array $geoJson): array
    {
        $type = $geoJson['type'] ?? null;

        if ($type === 'FeatureCollection') {
            if (! is_array($geoJson['features'] ?? null)) {
                throw new \Exception('Invalid GeoJSON FeatureCollection - features must be an array.');
            }

            return $geoJson['features'];
        }

        if ($type === 'Feature') {
            return [$geoJson];
        }

        if (in_array($type, ['Point', 'LineString', 'Polygon'], true)) {
            return [[
                'type' => 'Feature',
                'properties' => [],
                'geometry' => $geoJson,
            ]];
        }

        throw new \Exception('Feature import supports GeoJSON FeatureCollection, Feature, Point, LineString, or Polygon.');
    }

    private function previewFeature(
        array $feature,
        int $index,
        Collection $layerTypes,
        Collection $barangays,
        ?int $defaultBarangayId,
        ?string $defaultFeatureType,
        ?string $fallbackName,
    ): array {
        $properties = is_array($feature['properties'] ?? null) ? $feature['properties'] : [];
        $geometry = is_array($feature['geometry'] ?? null) ? $feature['geometry'] : [];
        $geometryType = $geometry['type'] ?? 'Missing';
        $name = $this->featureName($properties, $fallbackName, $index);
        $barangay = $this->resolveBarangay($properties, $barangays, $defaultBarangayId);
        $layerType = $this->resolveLayerType($properties, $layerTypes, $defaultFeatureType, $geometryType);

        if (! $barangay) {
            return $this->skippedItem($name, $geometryType, 'No Barangay matched. Select a default Barangay or include barangay/barangay_id in properties.');
        }

        if (! $layerType) {
            return $this->skippedItem($name, $geometryType, 'No Layer Type matched. Select a default Layer Type or include feature_type in properties.');
        }

        $geometryPayload = $this->geometryPayload($geometry, $layerType);

        if (! $geometryPayload) {
            return $this->skippedItem($name, $geometryType, "Geometry {$geometryType} is not valid for {$layerType->name} ({$layerType->geom_type}).");
        }

        $existing = $this->existingFeature($barangay, $layerType, $name);
        $metadata = $this->metadataFor($layerType, $properties);

        return [
            'display_name' => $name,
            'action' => $existing ? 'Update' : 'Create',
            'reason' => null,
            'geometry_type' => $geometryType,
            'barangay_id' => $barangay->id,
            'barangay_name' => $barangay->name,
            'feature_type' => $layerType->code,
            'feature_type_name' => $layerType->name,
            'layer_category' => $layerType->category,
            'existing_feature_id' => $existing?->id,
            'latitude' => $geometryPayload['latitude'],
            'longitude' => $geometryPayload['longitude'],
            'coordinates' => $geometryPayload['coordinates'],
            'metadata' => $metadata,
            'metadata_count' => count($metadata),
            'is_public' => $this->boolProperty($properties, ['is_public', 'public', 'visible_public'], true),
            'status' => $this->statusProperty($properties),
            'area' => null,
            'is_municipal_boundary' => false,
        ];
    }

    private function skippedItem(string $name, string $geometryType, string $reason): array
    {
        return [
            'display_name' => $name,
            'action' => 'Skipped',
            'reason' => $reason,
            'geometry_type' => $geometryType,
            'barangay_id' => null,
            'barangay_name' => null,
            'feature_type' => null,
            'feature_type_name' => null,
            'layer_category' => null,
            'existing_feature_id' => null,
            'latitude' => null,
            'longitude' => null,
            'coordinates' => null,
            'metadata' => [],
            'metadata_count' => 0,
            'is_public' => false,
            'status' => 'draft',
            'area' => null,
            'is_municipal_boundary' => false,
        ];
    }

    private function resolveBarangay(array $properties, Collection $barangays, ?int $defaultBarangayId): ?Barangay
    {
        $id = $this->propertyValue($properties, ['barangay_id', 'brgy_id']);

        if (is_numeric($id)) {
            $barangay = $barangays->firstWhere('id', (int) $id);

            if ($barangay) {
                return $barangay;
            }
        }

        $name = $this->propertyValue($properties, ['barangay', 'barangay_name', 'brgy', 'brgy_name', 'BGY_NAME', 'BRGY_NAME']);

        if (filled($name)) {
            $target = $this->normalizeName($name);
            $barangay = $barangays->first(fn (Barangay $barangay) => $this->normalizeName($barangay->name) === $target);

            if ($barangay) {
                return $barangay;
            }
        }

        return $defaultBarangayId ? $barangays->firstWhere('id', $defaultBarangayId) : null;
    }

    private function resolveLayerType(array $properties, Collection $layerTypes, ?string $defaultFeatureType, string $geometryType): ?MapLayerType
    {
        $code = $this->propertyValue($properties, ['feature_type', 'feature_type_code', 'layer_code', 'layer', 'layer_type']);

        if (filled($code)) {
            $normalized = $this->normalizeCode($code);
            $layerType = $layerTypes->first(fn (MapLayerType $type) => $type->code === $normalized || $this->normalizeCode($type->name) === $normalized);

            if ($layerType && $this->geometryTypeMatchesLayer($geometryType, $layerType)) {
                return $layerType;
            }
        }

        if ($defaultFeatureType) {
            $layerType = $layerTypes->firstWhere('code', $defaultFeatureType);

            if ($layerType && $this->geometryTypeMatchesLayer($geometryType, $layerType)) {
                return $layerType;
            }
        }

        $geomType = $this->layerGeomTypeForGeoJson($geometryType);
        $matching = $layerTypes->where('geom_type', $geomType)->values();

        return $matching->count() === 1 ? $matching->first() : null;
    }

    private function geometryPayload(array $geometry, MapLayerType $layerType): ?array
    {
        $geometryType = $geometry['type'] ?? null;
        $coordinates = $geometry['coordinates'] ?? null;

        if (! $this->geometryTypeMatchesLayer((string) $geometryType, $layerType)) {
            return null;
        }

        if ($geometryType === 'Point') {
            $point = $this->geoPointToLatLng($coordinates);

            return $point ? [
                'latitude' => $point[0],
                'longitude' => $point[1],
                'coordinates' => null,
            ] : null;
        }

        if ($geometryType === 'LineString') {
            $points = $this->geoLineToLatLngPairs($coordinates);

            return count($points) >= 2 ? [
                'latitude' => null,
                'longitude' => null,
                'coordinates' => $points,
            ] : null;
        }

        if ($geometryType === 'Polygon') {
            $points = $this->geoPolygonOuterRingToLatLngPairs($coordinates);

            return count($points) >= 3 ? [
                'latitude' => null,
                'longitude' => null,
                'coordinates' => $points,
            ] : null;
        }

        return null;
    }

    private function featureName(array $properties, ?string $fallbackName, int $index): string
    {
        $name = $this->propertyValue($properties, [
            'feature_name',
            'asset_name',
            'facility_name',
            'facility',
            'name',
            'NAME',
            'title',
            'label',
        ]);

        if (filled($name)) {
            return trim((string) $name);
        }

        $baseName = $fallbackName
            ? preg_replace('/\.(geojson|json|kml|zip|shp)$/i', '', $fallbackName)
            : 'Imported Feature';

        return Str::headline($baseName).' #'.($index + 1);
    }

    private function metadataFor(MapLayerType $layerType, array $properties): array
    {
        $metadata = $this->metadataSchema->normalizeMetadata($layerType, $properties);
        $schemaKeys = collect($this->metadataSchema->schemaFor($layerType))->pluck('key')->all();
        $reservedKeys = [
            'feature_name',
            'asset_name',
            'facility_name',
            'facility',
            'name',
            'NAME',
            'title',
            'label',
            'barangay_id',
            'brgy_id',
            'barangay',
            'barangay_name',
            'brgy',
            'brgy_name',
            'BGY_NAME',
            'BRGY_NAME',
            'feature_type',
            'feature_type_code',
            'layer_code',
            'layer',
            'layer_type',
            'is_public',
            'public',
            'visible_public',
            'map_status',
            'publishing_status',
        ];

        $extras = collect($properties)
            ->reject(fn ($value, string $key) => in_array($key, $reservedKeys, true) || in_array(Str::snake($key), $schemaKeys, true))
            ->filter(fn ($value) => $value !== null && $value !== '')
            ->all();

        if ($extras !== []) {
            $metadata['import_properties'] = $extras;
        }

        return $metadata;
    }

    private function existingFeature(Barangay $barangay, MapLayerType $layerType, string $name): ?MapFeature
    {
        $target = $this->normalizeName($name);

        return MapFeature::query()
            ->where('barangay_id', $barangay->id)
            ->where('feature_type', $layerType->code)
            ->get()
            ->first(fn (MapFeature $feature) => $this->normalizeName($feature->name) === $target);
    }

    private function propertyValue(array $properties, array $keys): mixed
    {
        foreach ($keys as $key) {
            foreach ($properties as $propertyKey => $value) {
                if (strcasecmp((string) $propertyKey, (string) $key) === 0 && $value !== null && $value !== '') {
                    return $value;
                }
            }
        }

        return null;
    }

    private function boolProperty(array $properties, array $keys, bool $default): bool
    {
        $value = $this->propertyValue($properties, $keys);

        return $value === null ? $default : filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    private function statusProperty(array $properties): string
    {
        $status = strtolower(trim((string) ($this->propertyValue($properties, ['map_status', 'publishing_status']) ?? 'active')));

        return in_array($status, ['active', 'inactive', 'draft'], true) ? $status : 'active';
    }

    private function geometryTypeMatchesLayer(string $geometryType, MapLayerType $layerType): bool
    {
        return $this->layerGeomTypeForGeoJson($geometryType) === $layerType->geom_type;
    }

    private function layerGeomTypeForGeoJson(string $geometryType): string
    {
        return match ($geometryType) {
            'Point' => 'point',
            'LineString' => 'polyline',
            'Polygon' => 'polygon',
            default => 'unsupported',
        };
    }

    private function geoPointToLatLng(mixed $coordinate): ?array
    {
        if (! is_array($coordinate) || count($coordinate) < 2) {
            return null;
        }

        return $this->validLngLat($coordinate[0], $coordinate[1])
            ? [(float) $coordinate[1], (float) $coordinate[0]]
            : null;
    }

    private function geoLineToLatLngPairs(mixed $coordinates): array
    {
        if (! is_array($coordinates)) {
            return [];
        }

        return collect($coordinates)
            ->map(fn ($coordinate) => $this->geoPointToLatLng($coordinate))
            ->filter()
            ->values()
            ->all();
    }

    private function geoPolygonOuterRingToLatLngPairs(mixed $coordinates): array
    {
        if (! is_array($coordinates) || ! is_array($coordinates[0] ?? null)) {
            return [];
        }

        $points = $this->geoLineToLatLngPairs($coordinates[0]);

        if (count($points) > 1 && $this->samePoint($points[0], $points[count($points) - 1])) {
            array_pop($points);
        }

        $unique = collect($points)
            ->map(fn (array $point) => round($point[0], 7).','.round($point[1], 7))
            ->unique()
            ->count();

        return $unique >= 3 ? $points : [];
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

    private function samePoint(array $a, array $b): bool
    {
        return abs($a[0] - $b[0]) < 0.0000001 && abs($a[1] - $b[1]) < 0.0000001;
    }

    private function normalizeName(mixed $value): string
    {
        $normalized = strtolower(trim((string) $value));
        $normalized = preg_replace('/^(brgy\.?|barangay)\s+/i', '', $normalized);
        $normalized = preg_replace('/[^a-z0-9]+/', ' ', $normalized);

        return trim(preg_replace('/\s+/', ' ', $normalized));
    }

    private function normalizeCode(mixed $value): string
    {
        return (string) Str::of((string) $value)->trim()->snake();
    }

    private function summarize(array $items): array
    {
        return [
            'mode' => 'features',
            'items' => $items,
            'matched' => collect($items)->where('action', 'Update')->count(),
            'created' => collect($items)->where('action', 'Create')->count(),
            'skipped' => collect($items)->where('action', 'Skipped')->count(),
            'municipal' => 0,
            'unmatched' => collect($items)->where('action', 'Create')->pluck('display_name')->values()->all(),
        ];
    }
}
