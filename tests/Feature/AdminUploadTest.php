<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Barangay;
use App\Models\MapFeature;
use App\Models\MapLayerType;
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

    public function test_boundary_upload_matches_common_barangay_name_variants_without_creating_duplicates(): void
    {
        $admin = User::factory()->create();
        Role::findOrCreate('admin', 'web');
        $admin->assignRole('admin');

        Barangay::create(['name' => 'Zone I (pob.)']);
        Barangay::create(['name' => 'M. H. Del Pilar']);
        Barangay::create(['name' => 'Darawey (tangal)']);

        $geoJson = [
            'type' => 'FeatureCollection',
            'features' => [
                $this->boundaryFeature('Zone I', 120.40, 15.80),
                $this->boundaryFeature('Mh Del Pilar', 120.42, 15.82),
                $this->boundaryFeature('Darawey', 120.44, 15.84),
                $this->boundaryFeature('Feature 1', 120.46, 15.86),
            ],
        ];

        $file = UploadedFile::fake()->createWithContent('variant-boundaries.geojson', json_encode($geoJson));

        $this
            ->actingAs($admin)
            ->post(route('admin.uploads.store'), ['upload_files' => [$file]])
            ->assertSessionHas('success');

        $this->assertDatabaseCount('barangays', 3);
        $this->assertDatabaseHas('barangays', [
            'name' => 'Zone I (pob.)',
            'latitude' => 15.804,
            'longitude' => 120.404,
        ]);
        $this->assertDatabaseHas('barangays', [
            'name' => 'M. H. Del Pilar',
            'latitude' => 15.824,
            'longitude' => 120.424,
        ]);
        $this->assertDatabaseHas('barangays', [
            'name' => 'Darawey (tangal)',
            'latitude' => 15.844,
            'longitude' => 120.444,
        ]);
        $this->assertDatabaseMissing('barangays', ['name' => 'Zone I']);
        $this->assertDatabaseMissing('barangays', ['name' => 'Mh Del Pilar']);
        $this->assertDatabaseMissing('barangays', ['name' => 'Darawey']);
        $this->assertDatabaseMissing('barangays', ['name' => 'Feature 1']);
    }

    public function test_boundary_preview_decision_can_match_unmatched_boundary_to_existing_barangay(): void
    {
        $admin = User::factory()->create();
        Role::findOrCreate('admin', 'web');
        $admin->assignRole('admin');

        $barangay = Barangay::create(['name' => 'Tococ East']);
        $file = UploadedFile::fake()->createWithContent(
            'tococ-mismatch.geojson',
            json_encode([
                'type' => 'FeatureCollection',
                'features' => [
                    $this->boundaryFeature('Tococ E.', 120.40, 15.80),
                ],
            ]),
        );

        $this
            ->actingAs($admin)
            ->post(route('admin.uploads.preview'), ['upload_files' => [$file]])
            ->assertSessionHas('upload_preview');

        $preview = session('upload_preview');

        $this
            ->actingAs($admin)
            ->post(route('admin.uploads.store'), [
                'preview_token' => $preview['token'],
                'boundary_decisions' => [
                    0 => [
                        0 => [
                            'action' => 'match',
                            'barangay_id' => $barangay->id,
                        ],
                    ],
                ],
            ])
            ->assertSessionHas('success');

        $this->assertDatabaseCount('barangays', 1);
        $this->assertDatabaseHas('barangays', [
            'name' => 'Tococ East',
            'latitude' => 15.804,
            'longitude' => 120.404,
        ]);
        $this->assertDatabaseMissing('barangays', ['name' => 'Tococ E.']);
    }

    public function test_boundary_preview_decision_can_skip_unmatched_boundary_without_creating_barangay(): void
    {
        $admin = User::factory()->create();
        Role::findOrCreate('admin', 'web');
        $admin->assignRole('admin');

        $file = UploadedFile::fake()->createWithContent(
            'unknown-boundary.geojson',
            json_encode([
                'type' => 'FeatureCollection',
                'features' => [
                    $this->boundaryFeature('Unknown Boundary', 120.40, 15.80),
                ],
            ]),
        );

        $this
            ->actingAs($admin)
            ->post(route('admin.uploads.preview'), ['upload_files' => [$file]])
            ->assertSessionHas('upload_preview');

        $preview = session('upload_preview');

        $this
            ->actingAs($admin)
            ->post(route('admin.uploads.store'), [
                'preview_token' => $preview['token'],
                'boundary_decisions' => [
                    0 => [
                        0 => [
                            'action' => 'skip',
                        ],
                    ],
                ],
            ])
            ->assertSessionHas('success');

        $this->assertDatabaseCount('barangays', 0);
        $this->assertDatabaseHas('map_uploads', [
            'file_name' => 'unknown-boundary.geojson',
            'status' => 'Processed',
        ]);
    }

    public function test_invalid_geojson_coordinates_are_rejected(): void
    {
        $admin = User::factory()->create();
        Role::findOrCreate('admin', 'web');
        $admin->assignRole('admin');

        $geoJson = [
            'type' => 'Feature',
            'properties' => ['NAME' => 'Invalid Boundary'],
            'geometry' => [
                'type' => 'Polygon',
                'coordinates' => [[
                    [999, 15.80],
                    [120.45, 15.80],
                    [120.45, 15.85],
                    [999, 15.80],
                ]],
            ],
        ];

        $file = UploadedFile::fake()->createWithContent('invalid-boundary.geojson', json_encode($geoJson));

        $this
            ->actingAs($admin)
            ->post(route('admin.uploads.store'), ['upload_files' => [$file]])
            ->assertSessionHas('error');

        $this->assertDatabaseMissing('barangays', [
            'name' => 'Invalid Boundary',
        ]);
        $this->assertDatabaseHas('map_uploads', [
            'file_name' => 'invalid-boundary.geojson',
            'status' => 'Failed',
        ]);
    }

    public function test_geojson_line_string_is_rejected_by_boundary_upload_center(): void
    {
        $admin = User::factory()->create();
        Role::findOrCreate('admin', 'web');
        $admin->assignRole('admin');

        $geoJson = [
            'type' => 'Feature',
            'properties' => ['NAME' => 'Road Segment'],
            'geometry' => [
                'type' => 'LineString',
                'coordinates' => [
                    [120.40, 15.80],
                    [120.45, 15.85],
                ],
            ],
        ];

        $file = UploadedFile::fake()->createWithContent('road-segment.geojson', json_encode($geoJson));

        $this
            ->actingAs($admin)
            ->post(route('admin.uploads.store'), ['upload_files' => [$file]])
            ->assertSessionHas('error');

        $this->assertDatabaseMissing('barangays', [
            'name' => 'Road Segment',
        ]);
        $this->assertDatabaseHas('map_uploads', [
            'file_name' => 'road-segment.geojson',
            'status' => 'Failed',
        ]);
    }

    public function test_kml_boundary_upload_converts_to_geojson_and_saves_barangay_boundary(): void
    {
        $admin = User::factory()->create();
        Role::findOrCreate('admin', 'web');
        $admin->assignRole('admin');

        $kml = <<<'KML'
<?xml version="1.0" encoding="UTF-8"?>
<kml xmlns="http://www.opengis.net/kml/2.2">
  <Document>
    <Placemark>
      <name>Alinggan</name>
      <Polygon>
        <outerBoundaryIs>
          <LinearRing>
            <coordinates>
              120.400,15.800,0
              120.410,15.800,0
              120.410,15.810,0
              120.400,15.810,0
              120.400,15.800,0
            </coordinates>
          </LinearRing>
        </outerBoundaryIs>
      </Polygon>
    </Placemark>
  </Document>
</kml>
KML;

        $file = UploadedFile::fake()->createWithContent('alinggan-boundary.kml', $kml);

        $this
            ->actingAs($admin)
            ->post(route('admin.uploads.store'), ['upload_files' => [$file]])
            ->assertSessionHas('success');

        $barangay = Barangay::where('name', 'Alinggan')->firstOrFail();

        $this->assertFalse((bool) $barangay->is_municipal_boundary);
        $this->assertCount(5, $barangay->boundary);
        $this->assertEquals(15.804, (float) $barangay->latitude);
        $this->assertEquals(120.404, (float) $barangay->longitude);
        $this->assertDatabaseHas('map_uploads', [
            'file_name' => 'alinggan-boundary.kml',
            'file_type' => 'KML Boundary',
            'status' => 'Processed',
        ]);
    }

    public function test_feature_geojson_import_creates_point_map_feature_with_metadata(): void
    {
        $admin = User::factory()->create();
        Role::findOrCreate('admin', 'web');
        $admin->assignRole('admin');

        $barangay = Barangay::create(['name' => 'Alinggan']);
        $layer = MapLayerType::create([
            'name' => 'Barangay Hall',
            'code' => 'barangay_hall',
            'category' => 'critical_facilities',
            'icon' => 'fa-solid fa-building',
            'color' => '#38bdf8',
            'geom_type' => 'point',
            'is_public' => true,
            'is_active' => true,
            'metadata_schema' => [
                ['key' => 'official', 'label' => 'Official', 'type' => 'text'],
                ['key' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => ['Operational', 'Closed']],
                ['key' => 'contact', 'label' => 'Contact', 'type' => 'text'],
            ],
        ]);

        $geoJson = [
            'type' => 'Feature',
            'properties' => [
                'name' => 'Alinggan Barangay Hall',
                'status' => 'Operational',
                'official' => 'Capt. Santos',
                'contact' => '0917-000-0000',
                'source_file_id' => 'A-001',
            ],
            'geometry' => [
                'type' => 'Point',
                'coordinates' => [120.405, 15.805],
            ],
        ];

        $file = UploadedFile::fake()->createWithContent('barangay-hall.geojson', json_encode($geoJson));

        $this
            ->actingAs($admin)
            ->post(route('admin.uploads.store'), [
                'import_mode' => 'features',
                'feature_barangay_id' => $barangay->id,
                'feature_type' => $layer->code,
                'upload_files' => [$file],
            ])
            ->assertSessionHas('success');

        $feature = MapFeature::where('name', 'Alinggan Barangay Hall')->firstOrFail();

        $this->assertSame($barangay->id, $feature->barangay_id);
        $this->assertSame('barangay_hall', $feature->feature_type);
        $this->assertEquals(15.805, (float) $feature->latitude);
        $this->assertEquals(120.405, (float) $feature->longitude);
        $this->assertSame('Operational', $feature->metadata['status']);
        $this->assertSame('Capt. Santos', $feature->metadata['official']);
        $this->assertSame('A-001', $feature->metadata['import_properties']['source_file_id']);
        $this->assertDatabaseHas('map_uploads', [
            'file_name' => 'barangay-hall.geojson',
            'file_type' => 'GeoJSON Features',
            'status' => 'Processed',
        ]);
    }

    public function test_feature_geojson_preview_imports_line_string_after_confirmation(): void
    {
        $admin = User::factory()->create();
        Role::findOrCreate('admin', 'web');
        $admin->assignRole('admin');

        Barangay::create(['name' => 'Alinggan']);
        MapLayerType::create([
            'name' => 'Road Network',
            'code' => 'road_network',
            'category' => 'infrastructure',
            'icon' => 'fa-solid fa-route',
            'color' => '#8b5cf6',
            'geom_type' => 'polyline',
            'is_public' => true,
            'is_active' => true,
            'metadata_schema' => [
                ['key' => 'status', 'label' => 'Condition', 'type' => 'select', 'options' => ['Good Condition', 'Needs Maintenance']],
                ['key' => 'width', 'label' => 'Average Width', 'type' => 'text'],
            ],
        ]);

        $geoJson = [
            'type' => 'FeatureCollection',
            'features' => [
                [
                    'type' => 'Feature',
                    'properties' => [
                        'name' => 'Alinggan Access Road',
                        'barangay' => 'Alinggan',
                        'feature_type' => 'road_network',
                        'status' => 'Good Condition',
                        'width' => '6m',
                    ],
                    'geometry' => [
                        'type' => 'LineString',
                        'coordinates' => [
                            [120.400, 15.800],
                            [120.410, 15.810],
                        ],
                    ],
                ],
            ],
        ];

        $file = UploadedFile::fake()->createWithContent('roads.geojson', json_encode($geoJson));

        $this
            ->actingAs($admin)
            ->post(route('admin.uploads.preview'), [
                'import_mode' => 'features',
                'upload_files' => [$file],
            ])
            ->assertSessionHas('upload_preview');

        $this->assertDatabaseCount('map_features', 0);
        $preview = session('upload_preview');
        $this->assertSame('features', $preview['files'][0]['mode'] ?? $preview['context']['mode']);

        $this
            ->actingAs($admin)
            ->post(route('admin.uploads.store'), ['preview_token' => $preview['token']])
            ->assertSessionHas('success');

        $feature = MapFeature::where('name', 'Alinggan Access Road')->firstOrFail();

        $this->assertNull($feature->latitude);
        $this->assertSame([[15.8, 120.4], [15.81, 120.41]], $feature->coordinates);
        $this->assertSame('Good Condition', $feature->metadata['status']);
        $this->assertSame('6m', $feature->metadata['width']);
    }

    public function test_kml_feature_preview_can_download_converted_geojson_and_save_feature(): void
    {
        $admin = User::factory()->create();
        Role::findOrCreate('admin', 'web');
        $admin->assignRole('admin');

        $barangay = Barangay::create(['name' => 'Alinggan']);
        $layer = MapLayerType::create([
            'name' => 'Barangay Hall',
            'code' => 'barangay_hall',
            'category' => 'critical_facilities',
            'icon' => 'fa-solid fa-building',
            'color' => '#38bdf8',
            'geom_type' => 'point',
            'is_public' => true,
            'is_active' => true,
            'metadata_schema' => [
                ['key' => 'status', 'label' => 'Status', 'type' => 'text'],
            ],
        ]);

        $kml = <<<'KML'
<?xml version="1.0" encoding="UTF-8"?>
<kml xmlns="http://www.opengis.net/kml/2.2">
  <Document>
    <Placemark>
      <name>Alinggan KML Barangay Hall</name>
      <ExtendedData>
        <Data name="status"><value>Operational</value></Data>
      </ExtendedData>
      <Point>
        <coordinates>120.405,15.805,0</coordinates>
      </Point>
    </Placemark>
  </Document>
</kml>
KML;

        $file = UploadedFile::fake()->createWithContent('alinggan-hall.kml', $kml);

        $this
            ->actingAs($admin)
            ->post(route('admin.uploads.preview'), [
                'import_mode' => 'features',
                'feature_barangay_id' => $barangay->id,
                'feature_type' => $layer->code,
                'upload_files' => [$file],
            ])
            ->assertSessionHas('upload_preview');

        $preview = session('upload_preview');
        $this->assertSame('KML', $preview['files'][0]['source_format']);
        $this->assertTrue($preview['files'][0]['converted']);
        $this->assertSame('KML Features', $preview['files'][0]['file_type']);

        $download = $this
            ->actingAs($admin)
            ->get(route('admin.uploads.preview.converted', [
                'preview_token' => $preview['token'],
                'file' => 0,
            ]))
            ->assertOk();

        $converted = json_decode($download->streamedContent(), true);
        $this->assertSame('FeatureCollection', $converted['type']);
        $this->assertSame('Point', $converted['features'][0]['geometry']['type']);
        $this->assertSame([120.405, 15.805], $converted['features'][0]['geometry']['coordinates']);

        $this
            ->actingAs($admin)
            ->post(route('admin.uploads.store'), ['preview_token' => $preview['token']])
            ->assertSessionHas('success');

        $feature = MapFeature::where('name', 'Alinggan KML Barangay Hall')->firstOrFail();

        $this->assertSame($barangay->id, $feature->barangay_id);
        $this->assertSame('barangay_hall', $feature->feature_type);
        $this->assertEquals(15.805, (float) $feature->latitude);
        $this->assertEquals(120.405, (float) $feature->longitude);
        $this->assertSame('Operational', $feature->metadata['status']);
        $this->assertDatabaseHas('map_uploads', [
            'file_name' => 'alinggan-hall.kml',
            'file_type' => 'KML Features',
            'status' => 'Processed',
        ]);
    }

    public function test_zipped_shapefile_feature_import_converts_to_geojson_and_saves_point(): void
    {
        $admin = User::factory()->create();
        Role::findOrCreate('admin', 'web');
        $admin->assignRole('admin');

        $barangay = Barangay::create(['name' => 'Alinggan']);
        $layer = MapLayerType::create([
            'name' => 'Barangay Hall',
            'code' => 'barangay_hall',
            'category' => 'critical_facilities',
            'icon' => 'fa-solid fa-building',
            'color' => '#38bdf8',
            'geom_type' => 'point',
            'is_public' => true,
            'is_active' => true,
            'metadata_schema' => [
                ['key' => 'status', 'label' => 'Status', 'type' => 'text'],
            ],
        ]);

        $file = $this->fakePointShapefileZip('alinggan-hall.zip', 120.405, 15.805, [
            'NAME' => 'Alinggan Shapefile Hall',
            'status' => 'Operational',
        ]);

        $this
            ->actingAs($admin)
            ->post(route('admin.uploads.store'), [
                'import_mode' => 'features',
                'feature_barangay_id' => $barangay->id,
                'feature_type' => $layer->code,
                'upload_files' => [$file],
            ])
            ->assertSessionHas('success');

        $feature = MapFeature::where('name', 'Alinggan Shapefile Hall')->firstOrFail();

        $this->assertSame($barangay->id, $feature->barangay_id);
        $this->assertSame('barangay_hall', $feature->feature_type);
        $this->assertEquals(15.805, (float) $feature->latitude);
        $this->assertEquals(120.405, (float) $feature->longitude);
        $this->assertSame('Operational', $feature->metadata['status']);
        $this->assertDatabaseHas('map_uploads', [
            'file_name' => 'alinggan-hall.zip',
            'file_type' => 'Shapefile Features',
            'status' => 'Processed',
        ]);
    }

    private function boundaryFeature(string $name, float $lng, float $lat): array
    {
        return [
            'type' => 'Feature',
            'properties' => ['NAME' => $name],
            'geometry' => [
                'type' => 'Polygon',
                'coordinates' => [[
                    [$lng, $lat],
                    [$lng + 0.01, $lat],
                    [$lng + 0.01, $lat + 0.01],
                    [$lng, $lat + 0.01],
                    [$lng, $lat],
                ]],
            ],
        ];
    }

    private function fakePointShapefileZip(string $fileName, float $lng, float $lat, array $attributes): UploadedFile
    {
        $directory = storage_path('framework/testing/shapefile_'.uniqid());
        File::ensureDirectoryExists($directory);

        $shpPath = "{$directory}/sample.shp";
        $dbfPath = "{$directory}/sample.dbf";
        $zipPath = "{$directory}/{$fileName}";

        File::put($shpPath, $this->pointShp($lng, $lat));
        File::put($dbfPath, $this->dbf([$attributes]));

        $zip = new \ZipArchive();
        $zip->open($zipPath, \ZipArchive::CREATE);
        $zip->addFile($shpPath, 'sample.shp');
        $zip->addFile($dbfPath, 'sample.dbf');
        $zip->close();

        $content = file_get_contents($zipPath);
        File::deleteDirectory($directory);

        return UploadedFile::fake()->createWithContent($fileName, $content);
    }

    private function pointShp(float $lng, float $lat): string
    {
        $fileLengthWords = 64;

        $header = pack('N', 9994)
            .str_repeat("\0", 20)
            .pack('N', $fileLengthWords)
            .pack('V', 1000)
            .pack('V', 1)
            .pack('d', $lng)
            .pack('d', $lat)
            .pack('d', $lng)
            .pack('d', $lat)
            .pack('d', 0)
            .pack('d', 0)
            .pack('d', 0)
            .pack('d', 0);

        $record = pack('N', 1)
            .pack('N', 10)
            .pack('V', 1)
            .pack('d', $lng)
            .pack('d', $lat);

        return $header.$record;
    }

    private function dbf(array $records): string
    {
        $fields = collect(array_keys($records[0] ?? []))
            ->map(fn (string $name) => [
                'name' => substr($name, 0, 10),
                'length' => 50,
            ])
            ->values()
            ->all();

        $headerLength = 32 + (count($fields) * 32) + 1;
        $recordLength = 1 + collect($fields)->sum('length');
        $header = chr(0x03).chr(126).chr(6).chr(8)
            .pack('V', count($records))
            .pack('v', $headerLength)
            .pack('v', $recordLength)
            .str_repeat("\0", 20);

        foreach ($fields as $field) {
            $name = str_pad($field['name'], 11, "\0");
            $header .= $name.'C'.str_repeat("\0", 4).chr($field['length']).chr(0).str_repeat("\0", 14);
        }

        $header .= chr(0x0D);
        $body = '';

        foreach ($records as $record) {
            $body .= ' ';

            foreach ($fields as $field) {
                $body .= str_pad(substr((string) ($record[$field['name']] ?? ''), 0, $field['length']), $field['length']);
            }
        }

        return $header.$body.chr(0x1A);
    }
}
