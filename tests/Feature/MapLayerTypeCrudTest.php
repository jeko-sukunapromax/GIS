<?php

namespace Tests\Feature;

use App\Models\Barangay;
use App\Models\MapFeature;
use App\Models\MapLayerType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MapLayerTypeCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_layer_type_with_normalized_fields(): void
    {
        $admin = $this->userWithRole('admin');

        $this
            ->actingAs($admin)
            ->post(route('admin.layer-types.store'), [
                'name' => 'Flood Zone',
                'category' => 'Risk Areas',
                'description' => 'Flood-prone polygon layer for planning.',
                'icon' => 'fa-solid fa-water',
                'color' => '#ABCDEF',
                'geom_type' => 'polygon',
                'metadata_schema_json' => '[{"key":"hazard_level","label":"Hazard Level","type":"select","options":["Low","High"]}]',
                'is_public' => '0',
                'is_active' => '0',
                'sort_order' => 9,
            ])
            ->assertRedirect(route('admin.layer-types.index'));

        $this->assertDatabaseHas('map_layer_types', [
            'name' => 'Flood Zone',
            'code' => 'flood_zone',
            'category' => 'risk_areas',
            'description' => 'Flood-prone polygon layer for planning.',
            'color' => '#abcdef',
            'geom_type' => 'polygon',
            'is_public' => false,
            'is_active' => false,
            'sort_order' => 9,
        ]);

        $this->assertDatabaseHas('activity_log', [
            'event' => 'layer_type.created',
            'description' => 'Created map layer type Flood Zone.',
        ]);

        $layer = MapLayerType::where('code', 'flood_zone')->first();

        $this->assertSame('hazard_level', $layer->metadata_schema[0]['key']);
        $this->assertSame(['Low', 'High'], $layer->metadata_schema[0]['options']);
    }

    public function test_updating_layer_type_keeps_code_stable_for_existing_features(): void
    {
        $admin = $this->userWithRole('admin');
        $layerType = MapLayerType::create([
            'name' => 'Evacuation Center',
            'code' => 'evac_center',
            'category' => 'critical_facilities',
            'icon' => 'fa-solid fa-tent',
            'color' => '#10b981',
            'geom_type' => 'point',
            'is_public' => true,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this
            ->actingAs($admin)
            ->put(route('admin.layer-types.update', $layerType), [
                'name' => 'Evacuation Site',
                'category' => 'DRRM Assets',
                'description' => 'Updated evacuation layer description.',
                'icon' => 'fa-solid fa-house-flood-water',
                'color' => '#22C55E',
                'geom_type' => 'polygon',
                'metadata_schema_json' => '[{"key":"capacity","label":"Capacity","type":"number"}]',
                'is_public' => '1',
                'is_active' => '1',
                'sort_order' => 3,
            ])
            ->assertRedirect(route('admin.layer-types.index'));

        $layerType->refresh();

        $this->assertSame('Evacuation Site', $layerType->name);
        $this->assertSame('evac_center', $layerType->code);
        $this->assertSame('drrm_assets', $layerType->category);
        $this->assertSame('Updated evacuation layer description.', $layerType->description);
        $this->assertSame('#22c55e', $layerType->color);
        $this->assertSame('polygon', $layerType->geom_type);
        $this->assertSame('capacity', $layerType->metadata_schema[0]['key']);
        $this->assertSame(3, $layerType->sort_order);
    }

    public function test_layer_type_with_features_cannot_be_deleted(): void
    {
        $admin = $this->userWithRole('admin');
        $barangay = Barangay::create(['name' => 'Alinggan']);
        $layerType = MapLayerType::create([
            'name' => 'Road Network',
            'code' => 'road_network',
            'category' => 'infrastructure',
            'icon' => 'fa-solid fa-route',
            'color' => '#8b5cf6',
            'geom_type' => 'polyline',
            'is_public' => true,
            'is_active' => true,
        ]);

        MapFeature::create([
            'barangay_id' => $barangay->id,
            'name' => 'Main Road',
            'layer_type' => 'infrastructure',
            'feature_type' => $layerType->code,
            'coordinates' => [[15.8, 120.4], [15.81, 120.41]],
            'is_public' => true,
            'status' => 'active',
        ]);

        $this
            ->actingAs($admin)
            ->delete(route('admin.layer-types.destroy', $layerType))
            ->assertRedirect(route('admin.layer-types.index'))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('map_layer_types', [
            'id' => $layerType->id,
            'code' => 'road_network',
        ]);
    }

    public function test_unused_layer_type_can_be_deleted(): void
    {
        $admin = $this->userWithRole('admin');
        $layerType = MapLayerType::create([
            'name' => 'Temporary Layer',
            'code' => 'temporary_layer',
            'category' => 'other',
            'icon' => 'fa-solid fa-location-dot',
            'color' => '#38bdf8',
            'geom_type' => 'point',
            'is_public' => false,
            'is_active' => false,
        ]);

        $this
            ->actingAs($admin)
            ->delete(route('admin.layer-types.destroy', $layerType))
            ->assertRedirect(route('admin.layer-types.index'));

        $this->assertDatabaseMissing('map_layer_types', [
            'id' => $layerType->id,
        ]);
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();

        Role::findOrCreate($role, 'web');
        $user->assignRole($role);

        return $user;
    }
}
