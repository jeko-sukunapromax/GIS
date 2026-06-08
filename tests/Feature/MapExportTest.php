<?php

namespace Tests\Feature;

use App\Models\Barangay;
use App\Models\MapFeature;
use App\Models\MapLayerType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MapExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_can_view_map_export_report(): void
    {
        $staff = $this->userWithRole('staff');
        $barangay = Barangay::create([
            'name' => 'Alinggan',
            'boundary' => [[15.80, 120.40], [15.81, 120.40], [15.81, 120.41]],
            'population' => '1200',
            'total_area' => 80.10,
            'hazard_level' => 'Low',
        ]);

        MapFeature::create([
            'barangay_id' => $barangay->id,
            'name' => 'Alinggan Evacuation Center',
            'layer_type' => 'critical_facilities',
            'feature_type' => 'evac_center',
            'latitude' => 15.80,
            'longitude' => 120.40,
        ]);

        $this
            ->actingAs($staff)
            ->get(route('admin.map-export.index', ['barangay_id' => $barangay->id]))
            ->assertOk()
            ->assertSee('Map Export / Print')
            ->assertSee('Alinggan Barangay Map Report')
            ->assertSee('Satellite')
            ->assertSee('Download PNG')
            ->assertSee('Print / Save PDF')
            ->assertSee('Selected GeoJSON')
            ->assertSee('Public Layers GeoJSON')
            ->assertSee('Alinggan Evacuation Center');
    }

    public function test_staff_can_download_selected_barangay_geojson_export(): void
    {
        $staff = $this->userWithRole('staff');
        $barangay = Barangay::create([
            'name' => 'Alinggan',
            'boundary' => [[15.80, 120.40], [15.81, 120.40], [15.81, 120.41]],
            'is_visible' => true,
        ]);
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

        MapFeature::create([
            'barangay_id' => $barangay->id,
            'name' => 'Alinggan Evacuation Center',
            'layer_type' => 'critical_facilities',
            'feature_type' => $layer->code,
            'latitude' => 15.805,
            'longitude' => 120.405,
            'is_public' => true,
            'status' => 'active',
        ]);

        $response = $this
            ->actingAs($staff)
            ->get(route('admin.map-export.geojson', [
                'barangay_id' => $barangay->id,
                'scope' => 'selected',
            ]))
            ->assertOk();

        $geoJson = json_decode($response->getContent(), true);

        $this->assertStringContainsString('application/geo+json', $response->headers->get('content-type'));
        $this->assertStringContainsString('alinggan-gis-layers.geojson', $response->headers->get('content-disposition'));
        $this->assertSame('FeatureCollection', $geoJson['type']);
        $this->assertCount(2, $geoJson['features']);
        $this->assertSame('Polygon', $geoJson['features'][0]['geometry']['type']);
        $this->assertSame('Point', $geoJson['features'][1]['geometry']['type']);
        $this->assertSame([120.405, 15.805], $geoJson['features'][1]['geometry']['coordinates']);
    }

    public function test_public_geojson_feed_only_contains_public_active_layers_and_features(): void
    {
        $visibleBarangay = Barangay::create([
            'name' => 'Alinggan',
            'boundary' => [[15.80, 120.40], [15.81, 120.40], [15.81, 120.41]],
            'is_visible' => true,
        ]);
        $hiddenBarangay = Barangay::create([
            'name' => 'Hidden Barangay',
            'boundary' => [[15.90, 120.50], [15.91, 120.50], [15.91, 120.51]],
            'is_visible' => false,
        ]);
        $publicLayer = MapLayerType::create([
            'name' => 'Public Layer',
            'code' => 'public_layer',
            'category' => 'critical_facilities',
            'icon' => 'fa-solid fa-location-dot',
            'color' => '#38bdf8',
            'geom_type' => 'point',
            'is_public' => true,
            'is_active' => true,
        ]);
        $privateLayer = MapLayerType::create([
            'name' => 'Private Layer',
            'code' => 'private_layer',
            'category' => 'critical_facilities',
            'icon' => 'fa-solid fa-lock',
            'color' => '#ef4444',
            'geom_type' => 'point',
            'is_public' => false,
            'is_active' => true,
        ]);

        MapFeature::create([
            'barangay_id' => $visibleBarangay->id,
            'name' => 'Published Feature',
            'layer_type' => 'critical_facilities',
            'feature_type' => $publicLayer->code,
            'latitude' => 15.805,
            'longitude' => 120.405,
            'is_public' => true,
            'status' => 'active',
        ]);
        MapFeature::create([
            'barangay_id' => $visibleBarangay->id,
            'name' => 'Hidden Feature',
            'layer_type' => 'critical_facilities',
            'feature_type' => $publicLayer->code,
            'latitude' => 15.806,
            'longitude' => 120.406,
            'is_public' => false,
            'status' => 'active',
        ]);
        MapFeature::create([
            'barangay_id' => $visibleBarangay->id,
            'name' => 'Private Layer Feature',
            'layer_type' => 'critical_facilities',
            'feature_type' => $privateLayer->code,
            'latitude' => 15.807,
            'longitude' => 120.407,
            'is_public' => true,
            'status' => 'active',
        ]);
        MapFeature::create([
            'barangay_id' => $hiddenBarangay->id,
            'name' => 'Hidden Barangay Feature',
            'layer_type' => 'critical_facilities',
            'feature_type' => $publicLayer->code,
            'latitude' => 15.905,
            'longitude' => 120.505,
            'is_public' => true,
            'status' => 'active',
        ]);

        $response = $this->get('/api/geojson')->assertOk();
        $geoJson = json_decode($response->getContent(), true);
        $properties = collect($geoJson['features'])->pluck('properties.name')->values();

        $this->assertStringContainsString('application/geo+json', $response->headers->get('content-type'));
        $this->assertTrue($properties->contains('Alinggan'));
        $this->assertTrue($properties->contains('Published Feature'));
        $this->assertFalse($properties->contains('Hidden Barangay'));
        $this->assertFalse($properties->contains('Hidden Feature'));
        $this->assertFalse($properties->contains('Private Layer Feature'));
        $this->assertFalse($properties->contains('Hidden Barangay Feature'));
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();

        Role::findOrCreate($role, 'web');
        $user->assignRole($role);

        return $user;
    }
}
