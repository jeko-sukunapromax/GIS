<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use App\Services\PostgisAppDataMigrator;
use Illuminate\Support\Facades\DB;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('gis:postgis-report {--limit=8 : Number of rows to show per report section}', function () {
    $limit = max(1, min(25, (int) $this->option('limit')));
    $postgis = DB::connection('postgis');
    $status = $postgis->selectOne(
        'SELECT current_database() AS database, current_user AS "user", PostGIS_Version() AS postgis_version'
    );

    $this->components->info("Connected to {$status->database} as {$status->user}");
    $this->line("PostGIS: {$status->postgis_version}");

    $quality = $postgis->selectOne(<<<'SQL'
SELECT
    (SELECT COUNT(*) FROM barangays WHERE geom IS NOT NULL) + (SELECT COUNT(*) FROM map_features WHERE geom IS NOT NULL) AS total,
    (SELECT COUNT(*) FROM barangays WHERE geom IS NOT NULL AND NOT ST_IsValid(geom)) +
    (SELECT COUNT(*) FROM map_features WHERE geom IS NOT NULL AND NOT ST_IsValid(geom)) AS invalid,
    ROUND(COALESCE((SELECT SUM(ST_Area(geom::geography) / 10000) FROM barangays WHERE geom IS NOT NULL), 0)::numeric, 2) AS total_polygon_hectares,
    ROUND(COALESCE((SELECT SUM(ST_Length(geom::geography)) FROM map_features WHERE geom IS NOT NULL AND feature_type = 'road_network'), 0)::numeric, 2) AS total_line_meters
SQL);

    $this->table(['Metric', 'Value'], [
        ['Geometry rows', $quality->total],
        ['Invalid geometries', $quality->invalid],
        ['Total polygon hectares', $quality->total_polygon_hectares ?? 'N/A'],
        ['Total line meters', $quality->total_line_meters ?? 'N/A'],
    ]);

    $counts = $postgis->select(<<<'SQL'
SELECT
    'barangays' AS source_table,
    CASE WHEN is_municipal_boundary THEN 'municipal_boundary' ELSE 'barangay_boundary' END AS feature_type,
    ST_GeometryType(geom) AS geometry_type,
    COUNT(*) AS rows
FROM barangays
WHERE geom IS NOT NULL
GROUP BY is_municipal_boundary, ST_GeometryType(geom)
UNION ALL
SELECT
    'map_features' AS source_table,
    feature_type,
    ST_GeometryType(geom) AS geometry_type,
    COUNT(*) AS rows
FROM map_features
WHERE geom IS NOT NULL
GROUP BY feature_type, ST_GeometryType(geom)
ORDER BY source_table, feature_type, geometry_type
SQL);

    $this->table(
        ['Source', 'Feature Type', 'Geometry', 'Rows'],
        array_map(fn ($row) => [
            $row->source_table,
            $row->feature_type,
            $row->geometry_type,
            $row->rows,
        ], $counts),
    );

    $largest = $postgis->select(<<<SQL
SELECT name, CASE WHEN is_municipal_boundary THEN 'municipal_boundary' ELSE 'barangay_boundary' END AS feature_type, ROUND((ST_Area(geom::geography) / 10000)::numeric, 2) AS hectares
FROM barangays
WHERE geom IS NOT NULL
ORDER BY hectares DESC
LIMIT {$limit}
SQL);

    $this->table(
        ['Largest Areas', 'Type', 'Hectares'],
        array_map(fn ($row) => [$row->name, $row->feature_type, $row->hectares], $largest),
    );

    $areaDifferences = $postgis->select(<<<SQL
SELECT
    name,
    ROUND(total_area, 2) AS stored_area,
    ROUND((ST_Area(geom::geography) / 10000)::numeric, 2) AS postgis_area,
    ROUND(ABS(total_area - (ST_Area(geom::geography) / 10000))::numeric, 2) AS difference
FROM barangays
WHERE geom IS NOT NULL AND total_area IS NOT NULL
ORDER BY difference DESC
LIMIT {$limit}
SQL);

    $this->table(
        ['Area QA', 'Stored ha', 'PostGIS ha', 'Diff'],
        array_map(fn ($row) => [$row->name, $row->stored_area, $row->postgis_area, $row->difference], $areaDifferences),
    );

    $containedPoints = $postgis->select(<<<SQL
SELECT
    f.name AS feature,
    f.feature_type,
    b.name AS containing_barangay
FROM map_features f
JOIN barangays b
    ON b.is_municipal_boundary = false
    AND ST_Covers(b.geom, f.geom)
WHERE f.geom IS NOT NULL
    AND ST_GeometryType(f.geom) = 'ST_Point'
ORDER BY f.name
LIMIT {$limit}
SQL);

    if ($containedPoints) {
        $this->table(
            ['Point Feature', 'Type', 'Inside Barangay'],
            array_map(fn ($row) => [$row->feature, $row->feature_type, $row->containing_barangay], $containedPoints),
        );
    } else {
        $this->line('No point features currently fall inside a barangay boundary.');
    }

    return 0;
})->purpose('Show a readable PostGIS spatial QA report directly from native tables');

Artisan::command('db:postgis-copy {--truncate : Truncate target PostgreSQL app tables before copying} {--include-ephemeral : Also copy sessions, cache, and queue tables}', function (PostgisAppDataMigrator $migrator) {
    $summary = $migrator->copy(
        truncate: (bool) $this->option('truncate'),
        includeEphemeral: (bool) $this->option('include-ephemeral'),
    );

    $this->table(
        ['Table', 'MySQL Rows', 'PostgreSQL Rows', 'Copied This Run', 'Status'],
        collect($summary)
            ->map(fn (array $row, string $table) => [
                $table,
                $row['source'],
                $row['target'],
                $row['inserted'],
                $row['match'] ? 'OK' : 'Mismatch',
            ])
            ->values()
            ->all(),
    );

    $mismatches = $migrator->mismatches($summary);

    if ($mismatches !== []) {
        $this->components->error('Copy finished, but these tables do not match: '.implode(', ', $mismatches));

        return 1;
    }

    $this->components->info('MySQL app data copied to PostgreSQL/PostGIS successfully.');

    return 0;
})->purpose('Copy current MySQL app data into the PostgreSQL/PostGIS app schema');

Artisan::command('db:postgis-compare {--include-ephemeral : Also compare sessions, cache, and queue tables}', function (PostgisAppDataMigrator $migrator) {
    $summary = $migrator->compare(
        includeEphemeral: (bool) $this->option('include-ephemeral'),
    );

    $this->table(
        ['Table', 'MySQL Rows', 'PostgreSQL Rows', 'Status'],
        collect($summary)
            ->map(fn (array $row, string $table) => [
                $table,
                $row['source'],
                $row['target'],
                $row['match'] ? 'OK' : 'Mismatch',
            ])
            ->values()
            ->all(),
    );

    $mismatches = $migrator->mismatches($summary);

    if ($mismatches !== []) {
        $this->components->error('These tables do not match: '.implode(', ', $mismatches));

        return 1;
    }

    $this->components->info('MySQL and PostgreSQL app table counts match.');

    return 0;
})->purpose('Compare current MySQL app data counts against PostgreSQL/PostGIS');
