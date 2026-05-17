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
        Schema::create('map_features', function (Blueprint $table) {
            $table->id();
            $table->foreignId('barangay_id')->constrained('barangays')->onDelete('cascade');
            $table->string('name');
            $table->string('layer_type'); // e.g., critical_facilities, drrm, population, infrastructure
            $table->string('feature_type'); // e.g., barangay_hall, health_center, multipurpose_bldg, covered_court, police_post, evac_center, bert_member, road_network
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->json('coordinates')->nullable(); // For lines (e.g. roads) or polygons
            $table->json('metadata')->nullable(); // For extra details (e.g., status, capacity, etc.)
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('map_features');
    }
};
