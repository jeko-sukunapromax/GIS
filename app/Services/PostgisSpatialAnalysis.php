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
            if (! $this->hasSpatialColumns()) {
                return $this->unavailable('PostGIS spatial columns are not set up on barangays/map_features tables yet.');
            }

            $boundary = $this->boundaryMetrics($barangay);

            if (! $boundary) {
                return $this->unavailable('This barangay has no spatial boundary set yet. Please upload a GeoJSON/KML boundary.');
            }

            $featureSummary = $this->featureSummary($barangay);
            $nearestFeature = $this->nearestFeature($barangay);
            $storedArea = $this->numericOrNull($barangay->total_area);
            $computedArea = $this->numericOrNull($boundary->computed_area_hectares ?? null);

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

    private function hasSpatialColumns(): bool
    {
        $result = DB::connection('postgis')->selectOne(<<<'SQL'
SELECT EXISTS (
    SELECT 1 FROM information_schema.columns 
    WHERE table_name='barangays' AND column_name='geom'
) AS has_geom
SQL
        );

        return (bool) ($result?->has_geom ?? false);
    }

    private function boundaryMetrics(Barangay $barangay): ?object
    {
        return DB::connection('postgis')->selectOne(<<<'SQL'
SELECT
    id,
    name,
    ROUND((ST_Area(geom::geography) / 10000)::numeric, 4) AS computed_area_hectares,
    ROUND(ST_Perimeter(geom::geography)::numeric, 2) AS perimeter_meters,
    updated_at
FROM barangays
WHERE id = ? AND geom IS NOT NULL
LIMIT 1
SQL, [$barangay->id]);
    }

    private function featureSummary(Barangay $barangay): ?object
    {
        return DB::connection('postgis')->selectOne(<<<'SQL'
SELECT
    COUNT(f.id) AS contained_features,
    ROUND(COALESCE(SUM(
        CASE
            WHEN f.feature_type = 'road_network' AND ST_GeometryType(f.geom) IN ('ST_LineString', 'ST_MultiLineString')
                THEN ST_Length(f.geom::geography)
            ELSE 0
        END
    ), 0)::numeric, 2) AS road_length_meters
FROM barangays b
LEFT JOIN map_features f
    ON ST_Intersects(b.geom, f.geom)
WHERE b.id = ?
GROUP BY b.id
SQL, [$barangay->id]);
    }

    private function nearestFeature(Barangay $barangay): ?object
    {
        return DB::connection('postgis')->selectOne(<<<'SQL'
SELECT
    f.name,
    f.feature_type,
    ROUND(ST_Distance(b.centroid::geography, f.geom::geography)::numeric, 2) AS distance_meters
FROM barangays b
CROSS JOIN map_features f
WHERE b.id = ? AND b.centroid IS NOT NULL AND f.geom IS NOT NULL
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
