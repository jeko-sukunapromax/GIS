<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('map_layer_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->string('category'); // e.g. critical_facilities, drrm, infrastructure, population
            $table->string('icon')->default('fa-solid fa-location-dot'); // FontAwesome icon class
            $table->string('color')->default('#38bdf8'); // Hex color code
            $table->string('geom_type')->default('point'); // point, polyline, polygon
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('map_layer_types');
    }
};
