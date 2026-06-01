<?php

namespace Tests\Feature;

use App\Models\Barangay;
use App\Models\MapFeature;
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
            ->assertSee('Alinggan Evacuation Center');
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();

        Role::findOrCreate($role, 'web');
        $user->assignRole($role);

        return $user;
    }
}
