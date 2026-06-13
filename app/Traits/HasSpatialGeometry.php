<?php

namespace App\Traits;

use Illuminate\Support\Facades\DB;
use App\Models\MapLayerType;

trait HasSpatialGeometry
{
    /**
     * Boot the trait to hook into Model events.
     */
    public static function bootHasSpatialGeometry(): void
    {
        static::saving(function ($model) {
            $model->updateSpatialGeometries();
        });
    }

    public function updateSpatialGeometries(): void
    {
        $driverName = DB::getDriverName();

        if ($this instanceof \App\Models\Barangay) {
            if (!empty($this->boundary) && is_array($this->boundary)) {
                $wkt = $this->polygonWktFromLatLngPairs($this->boundary);
                if ($wkt) {
                    if ($driverName === 'sqlite') {
                        $this->geom = $wkt;
                        $this->centroid = 'POINT(0 0)';
                    } else {
                        $this->geom = DB::raw("ST_SetSRID(ST_GeomFromText('{$wkt}'), 4326)");
                        $this->centroid = DB::raw("ST_SetSRID(ST_PointOnSurface(ST_GeomFromText('{$wkt}')), 4326)");
                    }
                } else {
                    $this->geom = null;
                    $this->centroid = null;
                }
            } else {
                $this->geom = null;
                $this->centroid = null;
            }
        }

        if ($this instanceof \App\Models\MapFeature) {
            $layerType = MapLayerType::where('code', $this->feature_type)->first();
            $geomType = $layerType?->geom_type ?? 'point';
            $wkt = $this->featureWkt($this, $geomType);

            if ($wkt) {
                if ($driverName === 'sqlite') {
                    $this->geom = $wkt;
                } else {
                    $this->geom = DB::raw("ST_SetSRID(ST_GeomFromText('{$wkt}'), 4326)");
                }
            } else {
                $this->geom = null;
            }
        }
    }

    private function featureWkt($feature, string $geomType): ?string
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
