<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('barangays', function (Blueprint $table) {
            $table->boolean('is_municipal_boundary')->default(false)->after('is_visible');
        });

        DB::table('barangays')
            ->whereRaw('LOWER(name) = ?', ['bayambang'])
            ->orWhereRaw('LOWER(name) LIKE ?', ['bayambang%boundary%'])
            ->orWhereRaw('LOWER(name) LIKE ?', ['%municipal%boundary%'])
            ->update([
                'name' => 'Bayambang',
                'is_municipal_boundary' => true,
                'is_visible' => true,
            ]);
    }

    public function down(): void
    {
        Schema::table('barangays', function (Blueprint $table) {
            $table->dropColumn('is_municipal_boundary');
        });
    }
};
