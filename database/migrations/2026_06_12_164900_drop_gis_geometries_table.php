<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement('DROP TABLE IF EXISTS gis_geometries');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No down migration since gis_geometries is completely deprecated.
    }
};
