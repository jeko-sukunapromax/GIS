<?php

namespace App\Services;

use App\Models\Barangay;
use App\Models\BoundaryVersion;
use App\Models\MapFeature;
use App\Models\MapLayerType;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;

class GeoJsonExporter
{
    public function barangayBoundary(Barangay $barangay, array $extraProperties = []): array
    {
        return $this->featureCollection([
            $this->boundaryFeature($barangay, $extraProperties),
        ]);
    }

    public function boundaryVersion(Barangay $barangay, BoundaryVersion $version, array $extraProperties = []): array
    {
        return $this->featureCollection([
            $this->boundaryFeature($barangay, array_merge([
                'version_id' => $version->id,
                'label' => $version->label,
                'total_area' => $version->total_area,
                'source' => $version->boundary_source,
                'boundary_updated_at' => $version->boundary_updated_at?->toISOString(),
                'snapshot_created_at' => $version->created_at?->toISOString(),
                'snapshot_created_by' => $version->created_by,
            ], $extraProperties), $version->boundary),
        ]);
    }

    /**
     * @param  Collection<int, Barangay>  $barangays
     * @param  Collection<string, MapLayerType>  $layerTypes
     */
    public function barangayDataset(Collection $barangays, Collection $layerTypes): array
    {
        $features = [];

        foreach ($barangays as $barangay) {
            if (! empty($barangay->boundary)) {
                $features[] = $this->boundaryFeature($barangay);
            }

            foreach ($barangay->features as $mapFeature) {
                $geoJsonFeature = $this->mapFeature($mapFeature, $layerTypes->get($mapFeature->feature_type));

                if ($geoJsonFeature) {
                    $features[] = $geoJsonFeature;
                }
            }
        }

        return $this->featureCollection($features);
    }

    public function downloadResponse(array $geoJson, string $filename): Response
    {
        return response()->make(
            json_encode($geoJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
            200,
            [
                'Content-Type' => 'application/geo+json',
                'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            ]
        );
    }

    public function inlineResponse(array $geoJson): Response
    {
        return response()->make(
            json_encode($geoJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
            200,
            ['Content-Type' => 'application/geo+json']
        );
    }

    private function boundaryFeature(Barangay $barangay, array $extraProperties = [], ?array $boundary = null): ?array
    {
        $coordinates = $this->polygonCoordinates($boundary ?? $barangay->boundary ?? []);

        if (! $coordinates) {
            return null;
        }

        return [
            'type' => 'Feature',
            'id' => 'barangay-'.$barangay->id,
            'properties' => $this->cleanProperties(array_merge([
                'id' => $barangay->id,
                'name' => $barangay->name,
                'source_type' => 'barangay_boundary',
                'boundary_type' => $barangay->is_municipal_boundary ? 'municipal' : 'barangay',
                'municipality' => $barangay->municipality,
                'province' => $barangay->province,
                'district' => $barangay->district,
                'total_area' => $barangay->total_area,
                'population' => $barangay->population,
                'land_use' => $barangay->land_use,
                'hazard_level' => $barangay->hazard_level,
                'status' => $barangay->status,
                'is_visible' => $barangay->is_visible,
                'source' => $barangay->boundary_source,
                'updated_at' => $barangay->boundary_updated_at?->toISOString(),
            ], $extraProperties)),
            'geometry' => [
                'type' => 'Polygon',
                'coordinates' => [$coordinates],
            ],
        ];
    }

    private function mapFeature(MapFeature $feature, ?MapLayerType $layerType): ?array
    {
        $geometry = $this->mapFeatureGeometry($feature, $layerType?->geom_type ?: 'point');

        if (! $geometry) {
            return null;
        }

        return [
            'type' => 'Feature',
            'id' => 'map-feature-'.$feature->id,
            'properties' => $this->cleanProperties([
                'id' => $feature->id,
                'name' => $feature->name,
                'source_type' => 'map_feature',
                'barangay_id' => $feature->barangay_id,
                'barangay_name' => $feature->barangay?->name,
                'layer_type' => $feature->layer_type,
                'feature_type' => $feature->feature_type,
                'layer_name' => $layerType?->name,
                'category' => $layerType?->category,
                'color' => $layerType?->color,
                'icon' => $layerType?->icon,
                'geom_type' => $layerType?->geom_type,
                'status' => $feature->status,
                'is_public' => $feature->is_public,
                'metadata' => $feature->metadata ?? [],
            ]),
            'geometry' => $geometry,
        ];
    }

    private function mapFeatureGeometry(MapFeature $feature, string $geomType): ?array
    {
        if ($geomType === 'point') {
            if (! is_numeric($feature->latitude) || ! is_numeric($feature->longitude)) {
                return null;
            }

            return [
                'type' => 'Point',
                'coordinates' => [(float) $feature->longitude, (float) $feature->latitude],
            ];
        }

        $coordinates = collect($feature->coordinates ?? [])
            ->map(fn ($point) => $this->toGeoJsonCoordinate($point))
            ->filter()
            ->values()
            ->all();

        if ($geomType === 'polygon') {
            $polygon = $this->polygonCoordinates($feature->coordinates ?? []);

            return $polygon ? [
                'type' => 'Polygon',
                'coordinates' => [$polygon],
            ] : null;
        }

        return count($coordinates) >= 2 ? [
            'type' => 'LineString',
            'coordinates' => $coordinates,
        ] : null;
    }

    /**
     * @param  array<int, mixed>  $features
     */
    private function featureCollection(array $features): array
    {
        $features = collect($features)->filter()->values()->all();

        $geoJson = [
            'type' => 'FeatureCollection',
            'features' => $features,
        ];

        $bbox = $this->bbox($features);

        if ($bbox) {
            $geoJson['bbox'] = $bbox;
        }

        return $geoJson;
    }

    private function polygonCoordinates(array $points): ?array
    {
        $coordinates = collect($points)
            ->map(fn ($point) => $this->toGeoJsonCoordinate($point))
            ->filter()
            ->values()
            ->all();

        if (count($coordinates) < 3) {
            return null;
        }

        if ($coordinates[0] !== $coordinates[count($coordinates) - 1]) {
            $coordinates[] = $coordinates[0];
        }

        $uniquePoints = collect($coordinates)
            ->slice(0, -1)
            ->map(fn (array $point) => round($point[0], 7).','.round($point[1], 7))
            ->unique()
            ->count();

        return $uniquePoints >= 3 ? $coordinates : null;
    }

    private function toGeoJsonCoordinate(mixed $point): ?array
    {
        if (! is_array($point) || count($point) < 2) {
            return null;
        }

        $lat = $point['lat'] ?? $point[0] ?? null;
        $lng = $point['lng'] ?? $point[1] ?? null;

        if (! is_numeric($lat) || ! is_numeric($lng)) {
            return null;
        }

        $lat = (float) $lat;
        $lng = (float) $lng;

        if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
            return null;
        }

        return [$lng, $lat];
    }

    /**
     * @param  array<int, array<string, mixed>>  $features
     */
    private function bbox(array $features): ?array
    {
        $coordinates = [];

        foreach ($features as $feature) {
            $coordinates = array_merge($coordinates, $this->flattenCoordinates($feature['geometry']['coordinates'] ?? []));
        }

        if ($coordinates === []) {
            return null;
        }

        $lngs = collect($coordinates)->pluck(0);
        $lats = collect($coordinates)->pluck(1);

        return [
            (float) $lngs->min(),
            (float) $lats->min(),
            (float) $lngs->max(),
            (float) $lats->max(),
        ];
    }

    private function flattenCoordinates(array $coordinates): array
    {
        if (isset($coordinates[0], $coordinates[1]) && is_numeric($coordinates[0]) && is_numeric($coordinates[1])) {
            return [[$coordinates[0], $coordinates[1]]];
        }

        $flat = [];

        foreach ($coordinates as $item) {
            if (is_array($item)) {
                $flat = array_merge($flat, $this->flattenCoordinates($item));
            }
        }

        return $flat;
    }

    private function cleanProperties(array $properties): array
    {
        return array_filter($properties, fn ($value) => $value !== null && $value !== '');
    }
}
