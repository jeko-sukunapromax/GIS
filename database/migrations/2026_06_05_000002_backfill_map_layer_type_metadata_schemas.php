<?php

use App\Services\LayerMetadataSchema;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $schemas = app(LayerMetadataSchema::class);

        DB::table('map_layer_types')
            ->select(['id', 'code', 'geom_type', 'metadata_schema'])
            ->orderBy('id')
            ->get()
            ->each(function ($layerType) use ($schemas) {
                if ($layerType->metadata_schema !== null) {
                    return;
                }

                DB::table('map_layer_types')
                    ->where('id', $layerType->id)
                    ->update([
                        'metadata_schema' => json_encode(
                            $schemas->defaultFor($layerType->code, $layerType->geom_type ?: 'point'),
                            JSON_UNESCAPED_SLASHES
                        ),
                    ]);
            });
    }

    public function down(): void
    {
        DB::table('map_layer_types')->update([
            'metadata_schema' => null,
        ]);
    }
};
