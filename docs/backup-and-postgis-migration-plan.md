# Backup and PostgreSQL/PostGIS Migration Plan

## Purpose

This plan defines how GeoBayambang GIS should be backed up and migrated from MySQL to PostgreSQL/PostGIS after the application data model, metadata rules, GeoJSON import, and export workflows are stable.

## Current State

- Current database engine: MySQL
- Current GIS boundary storage: JSON arrays in `barangays.boundary`
- Current feature geometry storage: latitude/longitude fields and JSON arrays in `map_features.coordinates`
- Current area calculation: application-side hectare calculation from polygon coordinates
- Current clean GIS dump: `geobayambang_gis.sql`

## Current PostGIS Prototype

The application still uses MySQL as the default database. PostGIS is configured as a separate Laravel connection named `postgis` so spatial work can be tested without breaking the current app.

Prototype files:

- `config/database.php`: separate `postgis` connection using `POSTGIS_*` environment values
- `database/migrations/postgis/2026_06_05_000001_create_gis_geometries_table.php`: isolated PostGIS migration
- `app/Services/PostgisGeometrySync.php`: converts existing MySQL JSON geometry into PostGIS WKT/geometry
- `app/Services/PostgisSpatialAnalysis.php`: reads PostGIS geometry for per-barangay measurements
- `routes/console.php`: prototype commands
- Admin map right sidebar: shows synced PostGIS measurements for the selected barangay

Prototype commands:

```bash
php artisan gis:postgis-migrate
php artisan gis:postgis-sync --dry-run
php artisan gis:postgis-sync --truncate
php artisan gis:postgis-report
```

Current prototype output:

- 78 barangay boundary records scanned
- 3 map feature records scanned
- 80 geometry rows synced into `gis_geometries`
- 0 invalid PostGIS geometries
- 1 map feature skipped because it is a `road_network` polyline record but only has point latitude/longitude and no line coordinates

The prototype table stores source links, metadata JSONB, `geom geometry(Geometry, 4326)`, centroid, calculated polygon area in hectares, calculated line length in meters, and GiST spatial indexes.

The report command currently shows:

- geometry row count and invalid geometry count
- geometry counts grouped by source table and feature type
- largest polygon areas calculated by PostGIS
- stored hectare value versus PostGIS-computed hectare value
- point features that fall inside synced barangay boundaries

The admin map PostGIS panel currently shows:

- computed polygon area
- boundary perimeter
- stored area versus PostGIS area difference
- road length inside the selected barangay
- synced feature count inside the boundary
- nearest synced map feature from the barangay centroid

Current report highlights:

- `gis_geometries` rows: 80
- invalid geometries: 0
- total synced polygon hectares: 27351.78
- point containment check: `bambam` is inside Alinggan, and `brgy hall` is inside Amanperez

Data issue found:

- Map feature `#32 hall of hame` is saved as `road_network`, which requires a polyline, but it only has latitude/longitude point values. New server-side validation now prevents this mismatch from happening again during feature create/update.

## Backup Procedure Before Migration

1. Confirm application is using the clean GIS database.

   ```bash
   php artisan tinker --execute='dump(config("database.connections.mysql.database"));'
   ```

2. Run tests before exporting.

   ```bash
   php artisan test
   ```

3. Export a full MySQL backup.

   ```bash
   mysqldump -u root -p --single-transaction --routines --triggers --default-character-set=utf8mb4 --databases geobayambang_gis > backups/geobayambang_gis_YYYYMMDD.sql
   ```

4. Export selected GIS data separately for verification.

   ```bash
   mysqldump -u root -p geobayambang_gis barangays map_layer_types map_features boundary_versions map_uploads > backups/geobayambang_gis_core_YYYYMMDD.sql
   ```

5. Verify the backup by importing it into a temporary database and running migrations/status checks.

   ```bash
   mysql -u root -p -e "CREATE DATABASE geobayambang_verify;"
   mysql -u root -p geobayambang_verify < backups/geobayambang_gis_YYYYMMDD.sql
   DB_DATABASE=geobayambang_verify php artisan migrate:status
   ```

## PostgreSQL/PostGIS Migration Steps

1. Install PostgreSQL and PostGIS.

   ```bash
   sudo apt install postgresql postgresql-contrib postgis
   ```

2. Create the target database.

   ```sql
   CREATE DATABASE geobayambang_gis;
   \c geobayambang_gis
   CREATE EXTENSION IF NOT EXISTS postgis;
   ```

3. Add a Laravel PostgreSQL connection in `.env`.

   ```env
   DB_CONNECTION=pgsql
   DB_HOST=127.0.0.1
   DB_PORT=5432
   DB_DATABASE=geobayambang_gis
   DB_USERNAME=postgres
   DB_PASSWORD=
   ```

4. Run migrations on a fresh PostgreSQL database.

   ```bash
   php artisan migrate --seed
   ```

5. Import or transform MySQL data into PostgreSQL.

   Recommended approach: create a dedicated migration/import script that reads from the old MySQL backup or active MySQL DB and inserts normalized records into PostgreSQL.

6. Add PostGIS geometry columns after data migration is stable.

   Recommended future columns:

   - `barangays.boundary_geom geometry(Polygon, 4326)`
   - `map_features.point_geom geometry(Point, 4326)`
   - `map_features.line_geom geometry(LineString, 4326)`
   - `map_features.polygon_geom geometry(Polygon, 4326)`

7. Populate geometry columns from existing JSON coordinate fields.

8. Add spatial indexes.

   ```sql
   CREATE INDEX barangays_boundary_geom_gist ON barangays USING GIST (boundary_geom);
   CREATE INDEX map_features_point_geom_gist ON map_features USING GIST (point_geom);
   CREATE INDEX map_features_line_geom_gist ON map_features USING GIST (line_geom);
   CREATE INDEX map_features_polygon_geom_gist ON map_features USING GIST (polygon_geom);
   ```

9. Replace application-side GIS calculations gradually.

   - Area: `ST_Area(geography(boundary_geom)) / 10000`
   - Distance: `ST_Distance(...)`
   - Contains/intersects: `ST_Contains`, `ST_Intersects`

10. Run full regression tests and manual QA.

## Rollback Plan

1. Keep the MySQL database untouched until PostGIS verification is complete.
2. Keep `.env` values for both MySQL and PostgreSQL documented.
3. If PostgreSQL migration fails, restore `.env` to MySQL and clear config cache.

   ```bash
   php artisan config:clear
   ```

4. Re-import the latest verified MySQL backup if needed.

## Manual QA Checklist

- Admin login through iHRIS works.
- Barangay list loads.
- Admin map loads boundaries and features.
- Public map shows only public/active layers.
- Layer type CRUD works.
- Metadata fields render by layer type.
- GeoJSON upload preview rejects invalid geometry.
- GeoJSON upload updates barangay boundaries.
- Boundary GeoJSON download produces valid FeatureCollection.
- Map export report renders selected barangay and features.

## Go/No-Go Criteria

Go only when:

- Full Laravel test suite passes.
- A verified MySQL backup exists.
- PostgreSQL fresh migration succeeds.
- Imported data counts match MySQL.
- Manual QA checklist passes.

No-go when:

- Any import loses barangays, layer types, map features, or boundary versions.
- GeoJSON upload/download fails.
- Public map publishing rules regress.
- iHRIS login cannot authenticate against the configured live API.
