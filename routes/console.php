<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use App\Services\PostgisAppDataMigrator;
use App\Services\PostgisGeometrySync;
use Illuminate\Support\Facades\DB;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('gis:postgis-migrate', function () {
    $this->call('migrate', [
        '--database' => 'postgis',
        '--path' => 'database/migrations/postgis',
        '--force' => true,
    ]);
})->purpose('Run the isolated PostGIS prototype migrations');

Artisan::command('gis:postgis-sync {--dry-run : Validate conversion without writing to PostGIS} {--truncate : Clear prototype rows before syncing}', function (PostgisGeometrySync $sync) {
    $status = $sync->connectionStatus();

    $this->components->info("Connected to {$status->database} as {$status->user}");
    $this->line("PostGIS: {$status->postgis_version}");

    $stats = $sync->sync(
        dryRun: (bool) $this->option('dry-run'),
        truncate: (bool) $this->option('truncate'),
    );

    $this->table(['Metric', 'Count'], [
        ['Barangays scanned', $stats['barangays_scanned']],
        ['Map features scanned', $stats['features_scanned']],
        ['Synced', $stats['synced']],
        ['Skipped', $stats['skipped']],
        ['Errors', count($stats['errors'])],
    ]);

    foreach (array_slice($stats['skipped_details'], 0, 10) as $skipped) {
        $this->line('<comment>Skipped:</comment> '.$skipped);
    }

    if (count($stats['skipped_details']) > 10) {
        $this->warn('Additional skipped rows hidden: '.(count($stats['skipped_details']) - 10));
    }

    foreach (array_slice($stats['errors'], 0, 10) as $error) {
        $this->warn($error);
    }

    if (count($stats['errors']) > 10) {
        $this->warn('Additional errors hidden: '.(count($stats['errors']) - 10));
    }
})->purpose('Sync existing MySQL GIS JSON geometry into the PostGIS prototype table');

Artisan::command('gis:postgis-report {--limit=8 : Number of rows to show per report section}', function (PostgisGeometrySync $sync) {
    if (! $sync->tableExists()) {
        $this->components->error('PostGIS table gis_geometries does not exist. Run php artisan gis:postgis-migrate first.');

        return 1;
    }

    $limit = max(1, min(25, (int) $this->option('limit')));
    $postgis = DB::connection('postgis');
    $status = $sync->connectionStatus();

    $this->components->info("Connected to {$status->database} as {$status->user}");
    $this->line("PostGIS: {$status->postgis_version}");

    $quality = $postgis->selectOne(<<<'SQL'
SELECT
    COUNT(*) AS total,
    COUNT(*) FILTER (WHERE NOT ST_IsValid(geom)) AS invalid,
    ROUND(SUM(area_hectares) FILTER (WHERE area_hectares IS NOT NULL), 2) AS total_polygon_hectares,
    ROUND(SUM(length_meters) FILTER (WHERE length_meters IS NOT NULL), 2) AS total_line_meters
FROM gis_geometries
SQL);

    $this->table(['Metric', 'Value'], [
        ['Geometry rows', $quality->total],
        ['Invalid geometries', $quality->invalid],
        ['Total polygon hectares', $quality->total_polygon_hectares ?? 'N/A'],
        ['Total line meters', $quality->total_line_meters ?? 'N/A'],
    ]);

    $counts = $postgis->select(<<<'SQL'
SELECT
    source_table,
    feature_type,
    ST_GeometryType(geom) AS geometry_type,
    COUNT(*) AS rows
FROM gis_geometries
GROUP BY source_table, feature_type, ST_GeometryType(geom)
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
SELECT name, feature_type, ROUND(area_hectares, 2) AS hectares
FROM gis_geometries
WHERE area_hectares IS NOT NULL
ORDER BY area_hectares DESC
LIMIT {$limit}
SQL);

    $this->table(
        ['Largest Areas', 'Type', 'Hectares'],
        array_map(fn ($row) => [$row->name, $row->feature_type, $row->hectares], $largest),
    );

    $areaDifferences = $postgis->select(<<<SQL
WITH areas AS (
    SELECT
        name,
        CASE
            WHEN properties->>'total_area' ~ '^-?[0-9]+(\\.[0-9]+)?$'
                THEN (properties->>'total_area')::numeric
            ELSE NULL
        END AS stored_area,
        area_hectares
    FROM gis_geometries
    WHERE source_table = 'barangays'
        AND area_hectares IS NOT NULL
)
SELECT
    name,
    ROUND(stored_area, 2) AS stored_area,
    ROUND(area_hectares, 2) AS postgis_area,
    ROUND(ABS(stored_area - area_hectares), 2) AS difference
FROM areas
WHERE stored_area IS NOT NULL
ORDER BY ABS(stored_area - area_hectares) DESC
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
FROM gis_geometries f
JOIN gis_geometries b
    ON b.source_table = 'barangays'
    AND b.feature_type = 'barangay_boundary'
    AND ST_Covers(b.geom, f.geom)
WHERE f.source_table = 'map_features'
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
        $this->line('No point features currently fall inside a synced barangay boundary.');
    }

    return 0;
})->purpose('Show a readable PostGIS spatial QA report');

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
