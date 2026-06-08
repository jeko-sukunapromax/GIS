<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('barangays', function (Blueprint $table) {
            $table->string('district')->nullable()->after('status');
        });

        // Backfill districts
        $jsonPath = database_path('seeders/extracted_districts.json');
        if (file_exists($jsonPath)) {
            $json = json_decode(file_get_contents($jsonPath), true);
            $dbBarangays = DB::table('barangays')->where('is_municipal_boundary', false)->get();

            $normalize = function ($str) {
                $str = strtolower($str);
                $str = str_replace(['1st', '2nd', '3rd', '4th'], ['i', 'ii', 'iii', 'iv'], $str);
                $str = preg_replace('/\s*\(.*?\)\s*/', '', $str);
                $str = preg_replace('/[^a-z0-9]/', '', $str);
                return $str;
            };

            foreach ($dbBarangays as $b) {
                $found = false;
                $normDb = $normalize($b->name);
                
                if (str_contains($normDb, 'darawey')) $normDb = 'darawey';
                if (str_contains($normDb, 'mhdelpilar')) $normDb = 'mhdelpilar';
                
                foreach ($json as $district => $brgys) {
                    foreach ($brgys as $item) {
                        $normJson = $normalize($item['barangay']);
                        if (str_contains($normJson, 'darawey')) $normJson = 'darawey';
                        if (str_contains($normJson, 'mhdelpilar')) $normJson = 'mhdelpilar';
                        
                        if ($normDb === $normJson) {
                            DB::table('barangays')
                                ->where('id', $b->id)
                                ->update(['district' => $district]);
                            $found = true;
                            break 2;
                        }
                    }
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('barangays', function (Blueprint $table) {
            $table->dropColumn('district');
        });
    }
};
