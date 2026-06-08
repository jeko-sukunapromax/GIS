<?php

namespace Tests\Feature;

use App\Models\Barangay;
use App\Models\User;
use App\Services\PostgisSpatialAnalysis;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PostgisSpatialAnalysisTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_can_load_postgis_spatial_analysis_for_barangay(): void
    {
        $staff = User::factory()->create();
        Role::findOrCreate('staff', 'web');
        $staff->assignRole('staff');

        $barangay = Barangay::create([
            'name' => 'Alinggan',
            'total_area' => 79.64,
        ]);

        $this->mock(PostgisSpatialAnalysis::class, function ($mock) use ($barangay): void {
            $mock->shouldReceive('summary')
                ->once()
                ->withArgs(fn (Barangay $argument): bool => $argument->is($barangay))
                ->andReturn([
                    'status' => 'ready',
                    'barangay_id' => $barangay->id,
                    'computed_area_hectares' => 80.12,
                    'stored_area_hectares' => 79.64,
                    'area_difference_hectares' => 0.48,
                    'perimeter_meters' => 4120.33,
                    'contained_features' => 3,
                    'road_length_meters' => 820.4,
                    'nearest_feature' => [
                        'name' => 'Alinggan Barangay Hall',
                        'feature_type' => 'barangay_hall',
                        'distance_meters' => 214.5,
                    ],
                    'message' => 'Measurements are computed from PostGIS geometry.',
                ]);
        });

        $this
            ->actingAs($staff)
            ->getJson(route('admin.barangays.spatial-analysis', $barangay))
            ->assertOk()
            ->assertJsonPath('status', 'ready')
            ->assertJsonPath('computed_area_hectares', 80.12)
            ->assertJsonPath('perimeter_meters', 4120.33)
            ->assertJsonPath('nearest_feature.name', 'Alinggan Barangay Hall');
    }
}
