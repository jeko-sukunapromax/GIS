<?php

namespace Tests\Feature;

use App\Models\Barangay;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MunicipalBoundaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_municipal_boundary_is_hidden_from_barangay_management(): void
    {
        $admin = User::factory()->create();
        Role::findOrCreate('admin', 'web');
        $admin->assignRole('admin');

        Barangay::create([
            'name' => 'Bayambang',
            'is_visible' => true,
            'is_municipal_boundary' => true,
        ]);

        Barangay::create([
            'name' => 'Bical Norte',
            'is_visible' => true,
        ]);

        $this
            ->actingAs($admin)
            ->get(route('admin.barangays.index'))
            ->assertOk()
            ->assertDontSee('<strong>Bayambang</strong>', false)
            ->assertSee('Bical Norte');
    }

    public function test_municipal_boundary_manager_shows_current_outline_preview(): void
    {
        $admin = User::factory()->create();
        Role::findOrCreate('admin', 'web');
        $admin->assignRole('admin');

        Barangay::create([
            'name' => 'Bayambang',
            'is_visible' => true,
            'is_municipal_boundary' => true,
            'boundary' => [[15.80, 120.40], [15.81, 120.40], [15.81, 120.41]],
        ]);

        $this
            ->actingAs($admin)
            ->get(route('admin.municipal-boundary.index'))
            ->assertOk()
            ->assertSee('Current Outline Preview')
            ->assertSee('Download Current GeoJSON');
    }

    public function test_barangay_api_excludes_municipal_boundary(): void
    {
        Barangay::create([
            'name' => 'Bayambang',
            'is_visible' => true,
            'is_municipal_boundary' => true,
        ]);

        Barangay::create([
            'name' => 'Bical Norte',
            'is_visible' => true,
        ]);

        $this
            ->getJson('/api/barangays')
            ->assertOk()
            ->assertJsonMissing(['name' => 'Bayambang'])
            ->assertJsonFragment(['name' => 'Bical Norte']);
    }
}
