<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Barangay;
use Illuminate\Http\UploadedFile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminUploadTest extends TestCase
{
    use RefreshDatabase;

    public function test_upload_data_page_can_be_rendered(): void
    {
        $admin = User::factory()->create();
        Role::findOrCreate('admin', 'web');
        $admin->assignRole('admin');

        $this
            ->actingAs($admin)
            ->get(route('admin.uploads.index'))
            ->assertOk()
            ->assertSee('Upload Data');
    }

    public function test_bayambang_boundary_upload_is_saved_as_municipal_boundary(): void
    {
        $admin = User::factory()->create();
        Role::findOrCreate('admin', 'web');
        $admin->assignRole('admin');

        $geoJson = [
            'type' => 'Feature',
            'properties' => ['NAME' => 'Bayambang'],
            'geometry' => [
                'type' => 'Polygon',
                'coordinates' => [[
                    [120.40, 15.80],
                    [120.45, 15.80],
                    [120.45, 15.85],
                    [120.40, 15.85],
                    [120.40, 15.80],
                ]],
            ],
        ];

        $file = UploadedFile::fake()->createWithContent('bayambang-boundary.geojson', json_encode($geoJson));

        $this
            ->actingAs($admin)
            ->post(route('admin.uploads.store'), ['upload_files' => [$file]])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('barangays', [
            'name' => 'Bayambang',
            'is_municipal_boundary' => true,
        ]);

        $this->assertDatabaseCount('barangays', 1);
    }

    public function test_upload_preview_does_not_save_boundaries_until_confirmed(): void
    {
        $admin = User::factory()->create();
        Role::findOrCreate('admin', 'web');
        $admin->assignRole('admin');

        $geoJson = [
            'type' => 'Feature',
            'properties' => ['NAME' => 'Bayambang'],
            'geometry' => [
                'type' => 'Polygon',
                'coordinates' => [[
                    [120.40, 15.80],
                    [120.45, 15.80],
                    [120.45, 15.85],
                    [120.40, 15.85],
                    [120.40, 15.80],
                ]],
            ],
        ];

        $file = UploadedFile::fake()->createWithContent('bayambang-boundary.geojson', json_encode($geoJson));

        $response = $this
            ->actingAs($admin)
            ->post(route('admin.uploads.preview'), ['upload_files' => [$file]])
            ->assertSessionHas('upload_preview');

        $this->assertDatabaseCount('barangays', 0);
        $this->assertDatabaseCount('map_uploads', 0);

        $preview = session('upload_preview');

        $this
            ->actingAs($admin)
            ->post(route('admin.uploads.store'), ['preview_token' => $preview['token']])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('barangays', [
            'name' => 'Bayambang',
            'is_municipal_boundary' => true,
        ]);
        $this->assertDatabaseHas('map_uploads', [
            'file_name' => 'bayambang-boundary.geojson',
            'status' => 'Processed',
        ]);
    }

    public function test_upload_preview_can_be_canceled_without_saving(): void
    {
        $admin = User::factory()->create();
        Role::findOrCreate('admin', 'web');
        $admin->assignRole('admin');

        $geoJson = [
            'type' => 'Feature',
            'properties' => ['NAME' => 'Alinggan'],
            'geometry' => [
                'type' => 'Polygon',
                'coordinates' => [[
                    [120.40, 15.80],
                    [120.45, 15.80],
                    [120.45, 15.85],
                    [120.40, 15.85],
                    [120.40, 15.80],
                ]],
            ],
        ];

        $file = UploadedFile::fake()->createWithContent('alinggan.geojson', json_encode($geoJson));

        $this
            ->actingAs($admin)
            ->post(route('admin.uploads.preview'), ['upload_files' => [$file]])
            ->assertSessionHas('upload_preview');

        $preview = session('upload_preview');
        $previewPath = storage_path("app/upload-previews/{$preview['token']}");

        $this->assertTrue(File::isDirectory($previewPath));

        $this
            ->actingAs($admin)
            ->delete(route('admin.uploads.cancel-preview'), ['preview_token' => $preview['token']])
            ->assertSessionHas('success');

        $this->assertFalse(File::isDirectory($previewPath));
        $this->assertFalse(session()->has("upload_previews.{$preview['token']}"));
        $this->assertDatabaseCount('barangays', 0);
        $this->assertDatabaseCount('map_uploads', 0);
    }

    public function test_boundary_upload_does_not_match_partial_barangay_names(): void
    {
        $admin = User::factory()->create();
        Role::findOrCreate('admin', 'web');
        $admin->assignRole('admin');

        Barangay::create([
            'name' => 'Ambayat I',
            'boundary' => [[15.80, 120.40], [15.81, 120.40], [15.81, 120.41]],
        ]);

        $geoJson = [
            'type' => 'Feature',
            'properties' => ['NAME' => 'Ambayat'],
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
        ];

        $file = UploadedFile::fake()->createWithContent('ambayat.geojson', json_encode($geoJson));

        $this
            ->actingAs($admin)
            ->post(route('admin.uploads.store'), ['upload_files' => [$file]])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('barangays', [
            'name' => 'Ambayat I',
            'latitude' => null,
            'longitude' => null,
        ]);

        $this->assertDatabaseHas('barangays', [
            'name' => 'Ambayat',
            'latitude' => 15.904,
            'longitude' => 120.504,
        ]);
    }
}
