<?php

namespace Tests\Feature;

use App\Models\Barangay;
use App\Models\MapFeature;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DataCompletenessTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_barangay_data_completeness_dashboard(): void
    {
        $admin = $this->userWithRole('admin');

        Barangay::create([
            'name' => 'Bayambang Municipal',
            'is_municipal_boundary' => true,
            'is_visible' => true,
        ]);

        $complete = Barangay::create([
            'name' => 'Alinggan',
            'boundary' => [[15.80, 120.40], [15.81, 120.40], [15.81, 120.41]],
            'population' => '1200',
            'total_area' => 80.10,
            'hazard_level' => 'Low',
        ]);

        MapFeature::create([
            'barangay_id' => $complete->id,
            'name' => 'Alinggan Evacuation Center',
            'layer_type' => 'critical_facilities',
            'feature_type' => 'evac_center',
            'latitude' => 15.80,
            'longitude' => 120.40,
        ]);

        Barangay::create([
            'name' => 'Bical Norte',
            'is_visible' => true,
        ]);

        $this
            ->actingAs($admin)
            ->get(route('admin.data-completeness.index'))
            ->assertOk()
            ->assertSee('Data Completeness')
            ->assertSee('Alinggan')
            ->assertSee('Bical Norte')
            ->assertDontSee('Bayambang Municipal')
            ->assertSee('Missing 6 of 6');
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();

        Role::findOrCreate($role, 'web');
        $user->assignRole($role);

        return $user;
    }
}
