<?php

namespace App\Services;

use App\Models\Barangay;
use App\Models\MapFeature;
use App\Models\MapLayerType;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PostgisGeometrySync
{
    public function connectionStatus(): object
    {
        return DB::connection('postgis')->selectOne(
            'SELECT current_database() AS database, current_user AS "user", PostGIS_Version() AS postgis_version'
        );
    }

    public function tableExists(): bool
    {
        $result = DB::connection('postgis')->selectOne("SELECT to_regclass('public.gis_geometries') AS table_name");

        return filled($result?->table_name);
    }

    public function sync(bool $dryRun = false, bool $truncate = false): array
    {
        if (! $this->tableExists()) {
            throw new RuntimeException('PostGIS table gis_geometries does not exist. Run php artisan gis:postgis-migrate first.');
        }

        $stats = [
            'barangays_scanned' => 0,
            'features_scanned' => 0,
            'synced' => 0,
            'skipped' => 0,
            'skipped_details' => [],
            'errors' => [],
        ];

        if ($truncate && ! $dryRun) {
            DB::connection('postgis')->statement('TRUNCATE TABLE gis_geometries RESTART IDENTITY');
        }

        Barangay::query()
            ->whereNotNull('boundary')
            ->orderBy('id')
            ->chunkById(100, function ($barangays) use (&$stats, $dryRun): void {
                foreach ($barangays as $barangay) {
                    $stats['barangays_scanned']++;

                    try {
                        $wkt = $this->polygonWktFromLatLngPairs($barangay->boundary);

                        if (! $wkt) {
                            $stats['skipped']++;
                            $stats['skipped_details'][] = "Barangay #{$barangay->id} {$barangay->name}: invalid or incomplete polygon boundary.";
                            continue;
                        }

                        $payload = [
                            'source_table' => 'barangays',
                            'source_id' => $barangay->id,
                            'barangay_id' => $barangay->is_municipal_boundary ? null : $barangay->id,
                            'name' => $barangay->name,
                            'layer_type' => 'boundary',
                            'feature_type' => $barangay->is_municipal_boundary ? 'municipal_boundary' : 'barangay_boundary',
                            'source_geometry' => 'boundary',
                            'properties' => [
                                'municipality' => $barangay->municipality,
                                'province' => $barangay->province,
                                'total_area' => $barangay->total_area,
                                'population' => $barangay->population,
                                'land_use' => $barangay->land_use,
                                'hazard_level' => $barangay->hazard_level,
                                'status' => $barangay->status,
                                'is_visible' => $barangay->is_visible,
                                'is_municipal_boundary' => $barangay->is_municipal_boundary,
                                'boundary_source' => $barangay->boundary_source,
                                'boundary_updated_at' => $barangay->boundary_updated_at?->toISOString(),
                            ],
                        ];

                        $this->syncGeometry($payload, $wkt, $dryRun);
                        $stats['synced']++;
                    } catch (\Throwable $e) {
                        $stats['skipped']++;
                        $stats['errors'][] = "Barangay #{$barangay->id}: {$e->getMessage()}";
                    }
                }
            });

        $layerTypes = MapLayerType::query()->get()->keyBy('code');

        MapFeature::query()
            ->orderBy('id')
            ->chunkById(100, function ($features) use (&$stats, $dryRun, $layerTypes): void {
                foreach ($features as $feature) {
                    $stats['features_scanned']++;

                    try {
                        $layerType = $layerTypes->get($feature->feature_type);
                        $geomType = $layerType?->geom_type ?? 'point';
                        $wkt = $this->featureWkt($feature, $geomType);

                        if (! $wkt) {
                            $stats['skipped']++;
                            $stats['skipped_details'][] = "Map feature #{$feature->id} {$feature->name}: missing or invalid {$geomType} geometry.";
                            continue;
                        }

                        $payload = [
                            'source_table' => 'map_features',
                            'source_id' => $feature->id,
                            'barangay_id' => $feature->barangay_id,
                            'name' => $feature->name,
                            'layer_type' => $feature->layer_type,
                            'feature_type' => $feature->feature_type,
                            'source_geometry' => $geomType,
                            'properties' => [
                                'metadata' => $feature->metadata ?? [],
                                'status' => $feature->status,
                                'is_public' => $feature->is_public,
                                'latitude' => $feature->latitude,
                                'longitude' => $feature->longitude,
                            ],
                        ];

                        $this->syncGeometry($payload, $wkt, $dryRun);
                        $stats['synced']++;
                    } catch (\Throwable $e) {
                        $stats['skipped']++;
                        $stats['errors'][] = "Map feature #{$feature->id}: {$e->getMessage()}";
                    }
                }
            });

        return $stats;
    }

    private function featureWkt(MapFeature $feature, string $geomType): ?string
    {
        if ($geomType === 'point') {
            if (! is_numeric($feature->latitude) || ! is_numeric($feature->longitude)) {
                return null;
            }

            return $this->pointWkt((float) $feature->latitude, (float) $feature->longitude);
        }

        if (! is_array($feature->coordinates)) {
            return null;
        }

        return match ($geomType) {
            'polygon' => $this->polygonWktFromLatLngPairs($feature->coordinates),
            default => $this->lineStringWktFromLatLngPairs($feature->coordinates),
        };
    }

    private function syncGeometry(array $payload, string $wkt, bool $dryRun): void
    {
        if ($dryRun) {
            return;
        }

        $properties = json_encode($payload['properties'], JSON_THROW_ON_ERROR);
        $sourceHash = hash('sha256', json_encode([$payload, $wkt], JSON_THROW_ON_ERROR));

        DB::connection('postgis')->statement(<<<'SQL'
WITH input AS (
    SELECT ST_SetSRID(ST_GeomFromText(?), 4326) AS geom
)
INSERT INTO gis_geometries (
    source_table,
    source_id,
    barangay_id,
    name,
    layer_type,
    feature_type,
    source_geometry,
    source_hash,
    properties,
    geom,
    centroid,
    area_hectares,
    length_meters,
    created_at,
    updated_at
)
SELECT
    ?, ?, ?, ?, ?, ?, ?, ?, ?::jsonb,
    input.geom,
    ST_PointOnSurface(input.geom),
    CASE
        WHEN GeometryType(input.geom) IN ('POLYGON', 'MULTIPOLYGON')
            THEN ROUND((ST_Area(input.geom::geography) / 10000)::numeric, 4)
        ELSE NULL
    END,
    CASE
        WHEN GeometryType(input.geom) IN ('LINESTRING', 'MULTILINESTRING')
            THEN ROUND(ST_Length(input.geom::geography)::numeric, 2)
        ELSE NULL
    END,
    CURRENT_TIMESTAMP,
    CURRENT_TIMESTAMP
FROM input
ON CONFLICT (source_table, source_id, source_geometry)
DO UPDATE SET
    barangay_id = EXCLUDED.barangay_id,
    name = EXCLUDED.name,
    layer_type = EXCLUDED.layer_type,
    feature_type = EXCLUDED.feature_type,
    source_hash = EXCLUDED.source_hash,
    properties = EXCLUDED.properties,
    geom = EXCLUDED.geom,
    centroid = EXCLUDED.centroid,
    area_hectares = EXCLUDED.area_hectares,
    length_meters = EXCLUDED.length_meters,
    updated_at = CURRENT_TIMESTAMP
SQL, [
            $wkt,
            $payload['source_table'],
            $payload['source_id'],
            $payload['barangay_id'],
            $payload['name'],
            $payload['layer_type'],
            $payload['feature_type'],
            $payload['source_geometry'],
            $sourceHash,
            $properties,
        ]);
    }

    private function pointWkt(float $lat, float $lng): ?string
    {
        if (! $this->validLatLng($lat, $lng)) {
            return null;
        }

        return 'POINT('.$this->formatCoordinate($lng).' '.$this->formatCoordinate($lat).')';
    }

    private function lineStringWktFromLatLngPairs(array $coordinates): ?string
    {
        $points = $this->cleanLatLngPairs($coordinates);

        if (count($points) < 2) {
            return null;
        }

        return 'LINESTRING('.implode(', ', $this->wktPoints($points)).')';
    }

    private function polygonWktFromLatLngPairs(array $coordinates): ?string
    {
        $points = $this->cleanLatLngPairs($coordinates);

        if (count($points) < 3) {
            return null;
        }

        if (! $this->samePoint($points[0], $points[count($points) - 1])) {
            $points[] = $points[0];
        }

        $uniquePoints = collect($points)
            ->slice(0, -1)
            ->map(fn (array $point) => $this->formatCoordinate($point[0]).','.$this->formatCoordinate($point[1]))
            ->unique()
            ->count();

        if ($uniquePoints < 3) {
            return null;
        }

        return 'POLYGON(('.implode(', ', $this->wktPoints($points)).'))';
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

    private function wktPoints(array $points): array
    {
        return array_map(
            fn (array $point) => $this->formatCoordinate($point[1]).' '.$this->formatCoordinate($point[0]),
            $points,
        );
    }

    private function validLatLng(float $lat, float $lng): bool
    {
        return $lat >= -90 && $lat <= 90 && $lng >= -180 && $lng <= 180;
    }

    private function samePoint(array $a, array $b): bool
    {
        return abs($a[0] - $b[0]) < 0.0000001 && abs($a[1] - $b[1]) < 0.0000001;
    }

    private function formatCoordinate(float $coordinate): string
    {
        return rtrim(rtrim(number_format($coordinate, 8, '.', ''), '0'), '.');
    }
}
