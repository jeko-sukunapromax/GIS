<?php

namespace Tests\Feature;

use App\Models\Barangay;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class BoundaryVersionTest extends TestCase
{
    use RefreshDatabase;

    public function test_boundary_upload_creates_version_and_restore_can_revert(): void
    {
        $admin = $this->adminUser();

        $barangay = Barangay::create([
            'name' => 'Alinggan',
            'boundary' => [[15.80, 120.40], [15.81, 120.40], [15.81, 120.41]],
            'latitude' => 15.80,
            'longitude' => 120.40,
            'boundary_source' => 'Original',
            'boundary_updated_at' => now()->subDay(),
        ]);

        $file = UploadedFile::fake()->createWithContent('alinggan-new.geojson', json_encode([
            'type' => 'Feature',
            'geometry' => [
                'type' => 'Polygon',
                'coordinates' => [[
                    [120.50, 15.90],
                    [120.51, 15.90],
                    [120.51, 15.91],
                    [120.50, 15.91],
                    [120.50, 15.90],
                ]],
            ],
        ]));

        $this
            ->actingAs($admin)
            ->post(route('admin.barangays.upload-boundary', $barangay), [
                'boundary_file' => $file,
                'boundary_source' => 'Updated boundary',
            ])
            ->assertSessionHas('success');

        $version = $barangay->fresh()->boundaryVersions()->first();

        $this->assertNotNull($version);
        $this->assertSame('Original', $version->boundary_source);
        $this->assertSame('Updated boundary', $barangay->fresh()->boundary_source);

        $this
            ->actingAs($admin)
            ->post(route('admin.barangays.boundary-versions.restore', [$barangay, $version]))
            ->assertSessionHas('success');

        $this->assertEquals([[15.80, 120.40], [15.81, 120.40], [15.81, 120.41]], $barangay->fresh()->boundary);
    }

    public function test_boundary_version_can_be_deleted(): void
    {
        $admin = $this->adminUser();

        $barangay = Barangay::create([
            'name' => 'Alinggan',
            'boundary' => [[15.80, 120.40], [15.81, 120.40], [15.81, 120.41]],
        ]);
        $version = $barangay->snapshotBoundary('Test snapshot', 'Tester');

        $this
            ->actingAs($admin)
            ->delete(route('admin.barangays.boundary-versions.destroy', [$barangay, $version]))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('boundary_versions', ['id' => $version->id]);
    }

    public function test_current_boundary_can_be_downloaded_as_geojson(): void
    {
        $admin = $this->adminUser();

        $barangay = Barangay::create([
            'name' => 'Bayambang',
            'is_municipal_boundary' => true,
            'boundary' => [[15.80, 120.40], [15.81, 120.40], [15.81, 120.41]],
            'boundary_source' => 'Municipal source',
        ]);

        $response = $this
            ->actingAs($admin)
            ->get(route('admin.barangays.boundary.download', $barangay))
            ->assertOk();

        $geoJson = json_decode($response->getContent(), true);

        $this->assertStringContainsString('application/geo+json', $response->headers->get('content-type'));
        $this->assertStringContainsString('attachment; filename="bayambang-current-boundary.geojson"', $response->headers->get('content-disposition'));
        $this->assertSame('FeatureCollection', $geoJson['type']);
        $this->assertSame([120.40, 15.80], $geoJson['features'][0]['geometry']['coordinates'][0][0]);
        $this->assertSame([120.40, 15.80], $geoJson['features'][0]['geometry']['coordinates'][0][3]);
        $this->assertSame('municipal', $geoJson['features'][0]['properties']['boundary_type']);
    }

    public function test_boundary_version_can_be_downloaded_as_geojson(): void
    {
        $admin = $this->adminUser();

        $barangay = Barangay::create([
            'name' => 'Alinggan',
            'boundary' => [[15.80, 120.40], [15.81, 120.40], [15.81, 120.41]],
            'boundary_source' => 'Original',
        ]);
        $version = $barangay->snapshotBoundary('Before update', 'Tester');

        $response = $this
            ->actingAs($admin)
            ->get(route('admin.barangays.boundary-versions.download', [$barangay, $version]))
            ->assertOk();

        $geoJson = json_decode($response->getContent(), true);

        $this->assertStringContainsString('attachment; filename="alinggan-boundary-version-'.$version->id.'.geojson"', $response->headers->get('content-disposition'));
        $this->assertSame('FeatureCollection', $geoJson['type']);
        $this->assertSame($version->id, $geoJson['features'][0]['properties']['version_id']);
        $this->assertSame('Before update', $geoJson['features'][0]['properties']['label']);
    }

    public function test_municipal_boundary_reset_saves_previous_version(): void
    {
        $admin = $this->adminUser();

        $municipalBoundary = Barangay::create([
            'name' => 'Bayambang',
            'is_municipal_boundary' => true,
            'is_visible' => true,
            'boundary' => [[15.80, 120.40], [15.81, 120.40], [15.81, 120.41]],
            'latitude' => 15.80,
            'longitude' => 120.40,
            'boundary_source' => 'Municipal original',
        ]);

        $this
            ->actingAs($admin)
            ->delete(route('admin.municipal-boundary.reset'))
            ->assertSessionHas('success');

        $this->assertNull($municipalBoundary->fresh()->boundary);
        $this->assertDatabaseHas('boundary_versions', [
            'barangay_id' => $municipalBoundary->id,
            'boundary_source' => 'Municipal original',
        ]);
    }

    private function adminUser(): User
    {
        $admin = User::factory()->create();

        Role::findOrCreate('admin', 'web');
        $admin->assignRole('admin');

        return $admin;
    }
}
