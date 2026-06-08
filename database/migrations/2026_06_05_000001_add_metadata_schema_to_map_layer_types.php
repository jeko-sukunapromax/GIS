<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('map_layer_types', function (Blueprint $table) {
            $table->text('description')->nullable()->after('category');
            $table->json('metadata_schema')->nullable()->after('geom_type');
        });
    }

    public function down(): void
    {
        Schema::table('map_layer_types', function (Blueprint $table) {
            $table->dropColumn(['description', 'metadata_schema']);
        });
    }
};
