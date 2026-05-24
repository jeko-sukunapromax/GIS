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
        Schema::table('users', function (Blueprint $table) {
            $table->string('ihris_id')->nullable()->unique()->after('id');
            $table->string('office')->nullable()->after('email');
            $table->json('ihris_payload')->nullable()->after('office');
            $table->timestamp('last_ihris_login_at')->nullable()->after('ihris_payload');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'ihris_id',
                'office',
                'ihris_payload',
                'last_ihris_login_at',
            ]);
        });
    }
};
