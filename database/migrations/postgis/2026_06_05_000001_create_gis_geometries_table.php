<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    protected $connection = 'postgis';

    public function up(): void
    {
        $db = DB::connection($this->connection);

        $db->unprepared(<<<'SQL'
CREATE TABLE IF NOT EXISTS gis_geometries (
    id BIGSERIAL PRIMARY KEY,
    source_table VARCHAR(80) NOT NULL,
    source_id BIGINT NOT NULL,
    barangay_id BIGINT NULL,
    name VARCHAR(255) NOT NULL,
    layer_type VARCHAR(120) NULL,
    feature_type VARCHAR(120) NULL,
    source_geometry VARCHAR(40) NOT NULL,
    source_hash CHAR(64) NOT NULL,
    properties JSONB NOT NULL DEFAULT '{}'::jsonb,
    geom geometry(Geometry, 4326) NOT NULL,
    centroid geometry(Point, 4326) NULL,
    area_hectares NUMERIC(14, 4) NULL,
    length_meters NUMERIC(14, 2) NULL,
    created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE UNIQUE INDEX IF NOT EXISTS gis_geometries_source_unique
    ON gis_geometries (source_table, source_id, source_geometry);

CREATE INDEX IF NOT EXISTS gis_geometries_geom_gist
    ON gis_geometries USING GIST (geom);

CREATE INDEX IF NOT EXISTS gis_geometries_centroid_gist
    ON gis_geometries USING GIST (centroid);

CREATE INDEX IF NOT EXISTS gis_geometries_barangay_idx
    ON gis_geometries (barangay_id);

CREATE INDEX IF NOT EXISTS gis_geometries_layer_idx
    ON gis_geometries (layer_type, feature_type);
SQL);
    }

    public function down(): void
    {
        DB::connection($this->connection)->statement('DROP TABLE IF EXISTS gis_geometries');
    }
};
