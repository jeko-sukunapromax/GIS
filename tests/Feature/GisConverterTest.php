<?php

namespace Tests\Feature;

use App\Models\Barangay;
use App\Models\MapFeature;
use App\Models\MapLayerType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class GisConverterTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_gis_converter_page(): void
    {
        $admin = $this->admin();

        $this
            ->actingAs($admin)
            ->get(route('admin.gis-converter.index'))
            ->assertOk()
            ->assertSee('GIS Converter')
            ->assertSee('Convert')
            ->assertSee('Inspect');
    }

    public function test_converter_previews_kml_and_downloads_geojson_without_saving(): void
    {
        $admin = $this->admin();
        $file = UploadedFile::fake()->createWithContent('sample-point.kml', $this->pointKml('Sample Point'));

        $this
            ->actingAs($admin)
            ->post(route('admin.gis-converter.preview'), ['gis_file' => $file])
            ->assertSessionHas('gis_conversion_preview');

        $this->assertDatabaseCount('map_features', 0);
        $this->assertDatabaseCount('barangays', 0);

        $preview = session('gis_conversion_preview');
        $this->assertSame('KML', $preview['source_format']);
        $this->assertSame(1, $preview['feature_count']);
        $this->assertSame(['Point' => 1], $preview['inspection']['geometry_counts']);

        $response = $this
            ->actingAs($admin)
            ->get(route('admin.gis-converter.download', ['preview_token' => $preview['token']]))
            ->assertOk();

        $geoJson = json_decode($response->streamedContent(), true);

        $this->assertSame('FeatureCollection', $geoJson['type']);
        $this->assertSame('Point', $geoJson['features'][0]['geometry']['type']);
        $this->assertSame([120.405, 15.805], $geoJson['features'][0]['geometry']['coordinates']);
    }

    public function test_converter_imports_kml_as_map_feature_after_preview(): void
    {
        $admin = $this->admin();
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

        $file = UploadedFile::fake()->createWithContent('alinggan-hall.kml', $this->pointKml('Alinggan Converter Hall'));

        $this
            ->actingAs($admin)
            ->post(route('admin.gis-converter.preview'), ['gis_file' => $file])
            ->assertSessionHas('gis_conversion_preview');

        $preview = session('gis_conversion_preview');

        $this
            ->actingAs($admin)
            ->post(route('admin.gis-converter.import'), [
                'preview_token' => $preview['token'],
                'import_mode' => 'features',
                'feature_barangay_id' => $barangay->id,
                'feature_type' => $layer->code,
            ])
            ->assertSessionHas('success');

        $feature = MapFeature::where('name', 'Alinggan Converter Hall')->firstOrFail();

        $this->assertSame($barangay->id, $feature->barangay_id);
        $this->assertSame('barangay_hall', $feature->feature_type);
        $this->assertEquals(15.805, (float) $feature->latitude);
        $this->assertEquals(120.405, (float) $feature->longitude);
        $this->assertSame('Operational', $feature->metadata['status']);
        $this->assertDatabaseHas('map_uploads', [
            'file_name' => 'alinggan-hall.kml',
            'file_type' => 'Converted GeoJSON Features',
            'status' => 'Processed',
        ]);
    }

    public function test_converter_imports_kml_as_boundary_after_preview(): void
    {
        $admin = $this->admin();
        $file = UploadedFile::fake()->createWithContent('alinggan-boundary.kml', $this->polygonKml('Alinggan'));

        $this
            ->actingAs($admin)
            ->post(route('admin.gis-converter.preview'), ['gis_file' => $file])
            ->assertSessionHas('gis_conversion_preview');

        $preview = session('gis_conversion_preview');

        $this
            ->actingAs($admin)
            ->post(route('admin.gis-converter.import'), [
                'preview_token' => $preview['token'],
                'import_mode' => 'boundaries',
            ])
            ->assertSessionHas('success');

        $barangay = Barangay::where('name', 'Alinggan')->firstOrFail();

        $this->assertFalse((bool) $barangay->is_municipal_boundary);
        $this->assertCount(5, $barangay->boundary);
        $this->assertDatabaseHas('map_uploads', [
            'file_name' => 'alinggan-boundary.kml',
            'file_type' => 'Converted GeoJSON Boundary',
            'status' => 'Processed',
        ]);
    }

    private function admin(): User
    {
        $admin = User::factory()->create();
        Role::findOrCreate('admin', 'web');
        $admin->assignRole('admin');

        return $admin;
    }

    private function pointKml(string $name): string
    {
        return <<<KML
<?xml version="1.0" encoding="UTF-8"?>
<kml xmlns="http://www.opengis.net/kml/2.2">
  <Document>
    <Placemark>
      <name>{$name}</name>
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
    }

    private function polygonKml(string $name): string
    {
        return <<<KML
<?xml version="1.0" encoding="UTF-8"?>
<kml xmlns="http://www.opengis.net/kml/2.2">
  <Document>
    <Placemark>
      <name>{$name}</name>
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
    }
}
