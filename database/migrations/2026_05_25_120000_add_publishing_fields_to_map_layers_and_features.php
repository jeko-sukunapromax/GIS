<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('map_layer_types', function (Blueprint $table) {
            $table->boolean('is_public')->default(true)->after('geom_type');
            $table->boolean('is_active')->default(true)->after('is_public');
            $table->unsignedInteger('sort_order')->default(0)->after('is_active');
        });

        Schema::table('map_features', function (Blueprint $table) {
            $table->boolean('is_public')->default(true)->after('metadata');
            $table->string('status')->default('active')->after('is_public');
        });
    }

    public function down(): void
    {
        Schema::table('map_layer_types', function (Blueprint $table) {
            $table->dropColumn(['is_public', 'is_active', 'sort_order']);
        });

        Schema::table('map_features', function (Blueprint $table) {
            $table->dropColumn(['is_public', 'status']);
        });
    }
};
