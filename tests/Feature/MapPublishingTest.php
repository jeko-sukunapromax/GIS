<?php

namespace Tests\Feature;

use App\Models\Barangay;
use App\Models\MapFeature;
use App\Models\MapLayerType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MapPublishingTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_map_only_exposes_public_active_layers_and_features(): void
    {
        $barangay = Barangay::create([
            'name' => 'Alinggan',
            'is_visible' => true,
        ]);

        MapLayerType::create([
            'name' => 'Public Layer',
            'code' => 'public_layer',
            'category' => 'test',
            'icon' => 'fa-solid fa-location-dot',
            'color' => '#38bdf8',
            'geom_type' => 'point',
            'is_public' => true,
            'is_active' => true,
        ]);

        MapLayerType::create([
            'name' => 'Private Layer',
            'code' => 'private_layer',
            'category' => 'test',
            'icon' => 'fa-solid fa-lock',
            'color' => '#ef4444',
            'geom_type' => 'point',
            'is_public' => false,
            'is_active' => true,
        ]);

        MapFeature::create([
            'barangay_id' => $barangay->id,
            'name' => 'Published Feature',
            'layer_type' => 'test',
            'feature_type' => 'public_layer',
            'latitude' => 15.8,
            'longitude' => 120.4,
            'is_public' => true,
            'status' => 'active',
        ]);

        MapFeature::create([
            'barangay_id' => $barangay->id,
            'name' => 'Hidden Feature',
            'layer_type' => 'test',
            'feature_type' => 'public_layer',
            'latitude' => 15.81,
            'longitude' => 120.41,
            'is_public' => false,
            'status' => 'active',
        ]);

        MapFeature::create([
            'barangay_id' => $barangay->id,
            'name' => 'Draft Feature',
            'layer_type' => 'test',
            'feature_type' => 'public_layer',
            'latitude' => 15.82,
            'longitude' => 120.42,
            'is_public' => true,
            'status' => 'draft',
        ]);

        MapFeature::create([
            'barangay_id' => $barangay->id,
            'name' => 'Private Layer Feature',
            'layer_type' => 'test',
            'feature_type' => 'private_layer',
            'latitude' => 15.83,
            'longitude' => 120.43,
            'is_public' => true,
            'status' => 'active',
        ]);

        $this
            ->get("/api/barangays/{$barangay->id}/features")
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonFragment(['name' => 'Published Feature'])
            ->assertJsonMissing(['name' => 'Hidden Feature'])
            ->assertJsonMissing(['name' => 'Draft Feature'])
            ->assertJsonMissing(['name' => 'Private Layer Feature']);
    }

    public function test_admin_can_update_feature_publishing_details(): void
    {
        $admin = User::factory()->create();
        Role::findOrCreate('staff', 'web');
        $admin->assignRole('staff');

        $barangay = Barangay::create(['name' => 'Alinggan']);
        $layer = MapLayerType::create([
            'name' => 'Evacuation Center',
            'code' => 'evac_center',
            'category' => 'critical_facilities',
            'icon' => 'fa-solid fa-tent',
            'color' => '#10b981',
            'geom_type' => 'point',
            'is_public' => true,
            'is_active' => true,
        ]);

        $feature = MapFeature::create([
            'barangay_id' => $barangay->id,
            'name' => 'Old Name',
            'layer_type' => 'critical_facilities',
            'feature_type' => $layer->code,
            'latitude' => 15.8,
            'longitude' => 120.4,
            'is_public' => true,
            'status' => 'active',
        ]);

        $this
            ->actingAs($admin)
            ->put(route('admin.features.update', $feature), [
                'name' => 'Updated Evac Center',
                'feature_type' => $layer->code,
                'latitude' => 15.8123456,
                'longitude' => 120.4567891,
                'metadata_json' => '{"capacity":"200 persons"}',
                'status' => 'draft',
            ])
            ->assertRedirect(route('admin.features.index', ['barangay_id' => $barangay->id]));

        $feature->refresh();

        $this->assertSame('Updated Evac Center', $feature->name);
        $this->assertSame('draft', $feature->status);
        $this->assertFalse($feature->is_public);
        $this->assertSame('200 persons', $feature->metadata['capacity']);
        $this->assertEquals(15.8123456, (float) $feature->latitude);
        $this->assertEquals(120.4567891, (float) $feature->longitude);
    }
}
