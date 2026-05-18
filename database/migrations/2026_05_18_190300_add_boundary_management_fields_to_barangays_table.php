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
        Schema::table('barangays', function (Blueprint $table) {
            $table->boolean('is_visible')->default(true)->after('status');
            $table->string('boundary_source')->nullable()->after('boundary');
            $table->timestamp('boundary_updated_at')->nullable()->after('boundary_source');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('barangays', function (Blueprint $table) {
            $table->dropColumn(['is_visible', 'boundary_source', 'boundary_updated_at']);
        });
    }
};
