<?php

namespace App\Services;

use App\Models\Barangay;
use Illuminate\Support\Facades\DB;
use Throwable;

class PostgisSpatialAnalysis
{
    public function summary(Barangay $barangay): array
    {
        try {
            if (! $this->tableExists()) {
                return $this->unavailable('PostGIS geometry table is not ready. Run php artisan gis:postgis-migrate and php artisan gis:postgis-sync.');
            }

            $boundary = $this->boundaryMetrics($barangay);

            if (! $boundary) {
                return $this->unavailable('This barangay has not been synced to PostGIS yet. Run php artisan gis:postgis-sync --truncate.');
            }

            $featureSummary = $this->featureSummary($barangay);
            $nearestFeature = $this->nearestFeature($barangay);
            $storedArea = $this->numericOrNull($barangay->total_area);
            $computedArea = $this->numericOrNull($boundary->computed_area_hectares ?? $boundary->area_hectares ?? null);

            return [
                'status' => 'ready',
                'barangay_id' => $barangay->id,
                'barangay_name' => $barangay->name,
                'computed_area_hectares' => $computedArea,
                'stored_area_hectares' => $storedArea,
                'area_difference_hectares' => ($storedArea !== null && $computedArea !== null)
                    ? round(abs($storedArea - $computedArea), 4)
                    : null,
                'perimeter_meters' => $this->numericOrNull($boundary->perimeter_meters ?? null),
                'contained_features' => (int) ($featureSummary?->contained_features ?? 0),
                'road_length_meters' => $this->numericOrNull($featureSummary?->road_length_meters ?? 0),
                'nearest_feature' => $nearestFeature ? [
                    'name' => $nearestFeature->name,
                    'feature_type' => $nearestFeature->feature_type,
                    'distance_meters' => $this->numericOrNull($nearestFeature->distance_meters),
                ] : null,
                'synced_at' => $boundary->updated_at,
                'message' => 'Measurements are computed from PostGIS geometry.',
            ];
        } catch (Throwable $e) {
            return $this->unavailable('PostGIS spatial analysis is unavailable: '.$e->getMessage());
        }
    }

    private function tableExists(): bool
    {
        $result = DB::connection('postgis')->selectOne("SELECT to_regclass('public.gis_geometries') AS table_name");

        return filled($result?->table_name);
    }

    private function boundaryMetrics(Barangay $barangay): ?object
    {
        return DB::connection('postgis')->selectOne(<<<'SQL'
SELECT
    source_id,
    name,
    area_hectares,
    ROUND((ST_Area(geom::geography) / 10000)::numeric, 4) AS computed_area_hectares,
    ROUND(ST_Perimeter(geom::geography)::numeric, 2) AS perimeter_meters,
    updated_at
FROM gis_geometries
WHERE source_table = 'barangays'
    AND source_id = ?
ORDER BY updated_at DESC
LIMIT 1
SQL, [$barangay->id]);
    }

    private function featureSummary(Barangay $barangay): ?object
    {
        return DB::connection('postgis')->selectOne(<<<'SQL'
WITH boundary AS (
    SELECT geom
    FROM gis_geometries
    WHERE source_table = 'barangays'
        AND source_id = ?
    LIMIT 1
)
SELECT
    COUNT(f.id) AS contained_features,
    ROUND(COALESCE(SUM(
        CASE
            WHEN f.feature_type = 'road_network' AND f.length_meters IS NOT NULL
                THEN f.length_meters
            ELSE 0
        END
    ), 0)::numeric, 2) AS road_length_meters
FROM boundary b
LEFT JOIN gis_geometries f
    ON f.source_table = 'map_features'
    AND ST_Intersects(b.geom, f.geom)
SQL, [$barangay->id]);
    }

    private function nearestFeature(Barangay $barangay): ?object
    {
        return DB::connection('postgis')->selectOne(<<<'SQL'
WITH boundary AS (
    SELECT centroid
    FROM gis_geometries
    WHERE source_table = 'barangays'
        AND source_id = ?
    LIMIT 1
)
SELECT
    f.name,
    f.feature_type,
    ROUND(ST_Distance(b.centroid::geography, f.geom::geography)::numeric, 2) AS distance_meters
FROM boundary b
JOIN gis_geometries f
    ON f.source_table = 'map_features'
ORDER BY b.centroid <-> f.geom
LIMIT 1
SQL, [$barangay->id]);
    }

    private function unavailable(string $message): array
    {
        return [
            'status' => 'unavailable',
            'message' => $message,
        ];
    }

    private function numericOrNull(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        return is_numeric($value) ? (float) $value : null;
    }
}
