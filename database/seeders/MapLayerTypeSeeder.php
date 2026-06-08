<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\MapLayerType;
use App\Services\LayerMetadataSchema;

class MapLayerTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            [
                'name' => 'Barangay Hall',
                'code' => 'barangay_hall',
                'category' => 'critical_facilities',
                'icon' => 'fa-solid fa-building-flag',
                'color' => '#3b82f6',
                'geom_type' => 'point'
            ],
            [
                'name' => 'Health Center',
                'code' => 'health_center',
                'category' => 'critical_facilities',
                'icon' => 'fa-solid fa-house-medical',
                'color' => '#ef4444',
                'geom_type' => 'point'
            ],
            [
                'name' => 'Multi-purpose Bldg',
                'code' => 'multipurpose_bldg',
                'category' => 'critical_facilities',
                'icon' => 'fa-solid fa-building',
                'color' => '#a855f7',
                'geom_type' => 'point'
            ],
            [
                'name' => 'Covered Court',
                'code' => 'covered_court',
                'category' => 'critical_facilities',
                'icon' => 'fa-solid fa-basketball',
                'color' => '#f59e0b',
                'geom_type' => 'point'
            ],
            [
                'name' => 'Police/Tanod Post',
                'code' => 'police_post',
                'category' => 'critical_facilities',
                'icon' => 'fa-solid fa-shield-halved',
                'color' => '#94a3b8',
                'geom_type' => 'point'
            ],
            [
                'name' => 'Evacuation Center',
                'code' => 'evac_center',
                'category' => 'critical_facilities',
                'icon' => 'fa-solid fa-tent',
                'color' => '#10b981',
                'geom_type' => 'point'
            ],
            [
                'name' => 'BERT Responder',
                'code' => 'bert_member',
                'category' => 'drrm',
                'icon' => 'fa-solid fa-users-gear',
                'color' => '#06b6d4',
                'geom_type' => 'point'
            ],
            [
                'name' => 'Road Network',
                'code' => 'road_network',
                'category' => 'infrastructure',
                'icon' => 'fa-solid fa-route',
                'color' => '#8b5cf6',
                'geom_type' => 'polyline'
            ],
            [
                'name' => 'Density Zone',
                'code' => 'population_density',
                'category' => 'population',
                'icon' => 'fa-solid fa-draw-polygon',
                'color' => '#3b82f6',
                'geom_type' => 'polygon'
            ],
            [
                'name' => 'Household',
                'code' => 'household_distribution',
                'category' => 'population',
                'icon' => 'fa-solid fa-people-roof',
                'color' => '#14b8a6',
                'geom_type' => 'point'
            ]
        ];

        $schemas = app(LayerMetadataSchema::class);

        foreach ($types as $type) {
            $type = array_merge([
                'is_public' => true,
                'is_active' => true,
                'sort_order' => 0,
                'description' => null,
                'metadata_schema' => $schemas->defaultFor($type['code'], $type['geom_type']),
            ], $type);

            MapLayerType::updateOrCreate(['code' => $type['code']], $type);
        }
    }
}
