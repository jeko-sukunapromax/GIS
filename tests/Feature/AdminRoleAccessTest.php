<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminRoleAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_can_access_map_and_features_only(): void
    {
        $staff = $this->userWithRole('staff');

        $this->actingAs($staff)->get(route('admin.map'))->assertOk();
        $this->actingAs($staff)->get(route('admin.map-export.index'))->assertOk();
        $this->actingAs($staff)->get(route('admin.features.index'))->assertOk();

        $this->actingAs($staff)->get(route('admin.uploads.index'))->assertForbidden();
        $this->actingAs($staff)->get(route('admin.barangays.index'))->assertForbidden();
        $this->actingAs($staff)->get(route('admin.data-completeness.index'))->assertForbidden();
        $this->actingAs($staff)->get(route('admin.activity-logs.index'))->assertForbidden();
        $this->actingAs($staff)->get(route('admin.layer-types.index'))->assertForbidden();
        $this->actingAs($staff)->get(route('admin.users.index'))->assertForbidden();
    }

    public function test_admin_can_manage_data_but_not_users(): void
    {
        $admin = $this->userWithRole('admin');

        $this->actingAs($admin)->get(route('admin.map'))->assertOk();
        $this->actingAs($admin)->get(route('admin.features.index'))->assertOk();
        $this->actingAs($admin)->get(route('admin.uploads.index'))->assertOk();
        $this->actingAs($admin)->get(route('admin.barangays.index'))->assertOk();
        $this->actingAs($admin)->get(route('admin.data-completeness.index'))->assertOk();
        $this->actingAs($admin)->get(route('admin.activity-logs.index'))->assertOk();
        $this->actingAs($admin)->get(route('admin.layer-types.index'))->assertOk();

        $this->actingAs($admin)->get(route('admin.users.index'))->assertForbidden();
    }

    public function test_super_admin_can_access_user_management(): void
    {
        $superAdmin = $this->userWithRole('super-admin');

        $this->actingAs($superAdmin)->get(route('admin.users.index'))->assertOk();
    }

    public function test_dashboard_redirects_staff_to_features(): void
    {
        $staff = $this->userWithRole('staff');

        $this
            ->actingAs($staff)
            ->get(route('dashboard'))
            ->assertRedirect(route('admin.features.index'));
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();

        Role::findOrCreate($role, 'web');
        $user->assignRole($role);

        return $user;
    }
}
