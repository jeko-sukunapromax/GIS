<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use App\Models\Barangay;
use App\Models\MapFeature;
use App\Models\MapLayerType;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $driverName = DB::getDriverName();

        if ($driverName === 'sqlite') {
            Schema::table('barangays', function (\Illuminate\Database\Schema\Blueprint $table) {
                $table->text('geom')->nullable();
                $table->text('centroid')->nullable();
            });
            Schema::table('map_features', function (\Illuminate\Database\Schema\Blueprint $table) {
                $table->text('geom')->nullable();
            });
        } else {
            // 1. Add spatial columns to barangays
            DB::statement('ALTER TABLE barangays ADD COLUMN IF NOT EXISTS geom geometry(Geometry, 4326) NULL');
            DB::statement('ALTER TABLE barangays ADD COLUMN IF NOT EXISTS centroid geometry(Point, 4326) NULL');
            
            DB::statement('CREATE INDEX IF NOT EXISTS barangays_geom_gist ON barangays USING GIST (geom)');
            DB::statement('CREATE INDEX IF NOT EXISTS barangays_centroid_gist ON barangays USING GIST (centroid)');

            // 2. Add spatial column to map_features
            DB::statement('ALTER TABLE map_features ADD COLUMN IF NOT EXISTS geom geometry(Geometry, 4326) NULL');
            DB::statement('CREATE INDEX IF NOT EXISTS map_features_geom_gist ON map_features USING GIST (geom)');
        }

        // 3. Backfill existing data
        $this->backfillBarangays();
        $this->backfillMapFeatures();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driverName = DB::getDriverName();

        if ($driverName === 'sqlite') {
            Schema::table('map_features', function (\Illuminate\Database\Schema\Blueprint $table) {
                $table->dropColumn('geom');
            });
            Schema::table('barangays', function (\Illuminate\Database\Schema\Blueprint $table) {
                $table->dropColumn(['geom', 'centroid']);
            });
        } else {
            DB::statement('DROP INDEX IF EXISTS map_features_geom_gist');
            DB::statement('ALTER TABLE map_features DROP COLUMN IF EXISTS geom');

            DB::statement('DROP INDEX IF EXISTS barangays_centroid_gist');
            DB::statement('DROP INDEX IF EXISTS barangays_geom_gist');
            DB::statement('ALTER TABLE barangays DROP COLUMN IF EXISTS centroid');
            DB::statement('ALTER TABLE barangays DROP COLUMN IF EXISTS geom');
        }
    }

    private function backfillBarangays(): void
    {
        $driverName = DB::getDriverName();

        Barangay::query()
            ->whereNotNull('boundary')
            ->chunkById(100, function ($barangays) use ($driverName): void {
                foreach ($barangays as $barangay) {
                    try {
                        $wkt = $this->polygonWktFromLatLngPairs($barangay->boundary);
                        if ($wkt) {
                            if ($driverName === 'sqlite') {
                                DB::table('barangays')
                                    ->where('id', $barangay->id)
                                    ->update([
                                        'geom' => $wkt,
                                        'centroid' => 'POINT(0 0)',
                                    ]);
                            } else {
                                DB::table('barangays')
                                    ->where('id', $barangay->id)
                                    ->update([
                                        'geom' => DB::raw("ST_SetSRID(ST_GeomFromText('{$wkt}'), 4326)"),
                                        'centroid' => DB::raw("ST_SetSRID(ST_PointOnSurface(ST_GeomFromText('{$wkt}')), 4326)"),
                                    ]);
                            }
                        }
                    } catch (\Throwable $e) {
                        // Keep going if one fails
                    }
                }
            });
    }

    private function backfillMapFeatures(): void
    {
        $layerTypes = MapLayerType::query()->get()->keyBy('code');
        $driverName = DB::getDriverName();

        MapFeature::query()
            ->chunkById(100, function ($features) use ($layerTypes, $driverName): void {
                foreach ($features as $feature) {
                    try {
                        $layerType = $layerTypes->get($feature->feature_type);
                        $geomType = $layerType?->geom_type ?? 'point';
                        $wkt = $this->featureWkt($feature, $geomType);

                        if ($wkt) {
                            if ($driverName === 'sqlite') {
                                DB::table('map_features')
                                    ->where('id', $feature->id)
                                    ->update([
                                        'geom' => $wkt,
                                    ]);
                            } else {
                                DB::table('map_features')
                                    ->where('id', $feature->id)
                                    ->update([
                                        'geom' => DB::raw("ST_SetSRID(ST_GeomFromText('{$wkt}'), 4326)"),
                                    ]);
                            }
                        }
                    } catch (\Throwable $e) {
                        // Keep going if one fails
                    }
                }
            });
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
};
