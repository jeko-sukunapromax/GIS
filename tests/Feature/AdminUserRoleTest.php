<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminUserRoleTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_update_user_role(): void
    {
        $superAdmin = User::factory()->create();
        $user = User::factory()->create();

        Role::findOrCreate('super-admin', 'web');
        Role::findOrCreate('admin', 'web');
        Role::findOrCreate('staff', 'web');

        $superAdmin->assignRole('super-admin');
        $user->assignRole('admin');

        $this
            ->actingAs($superAdmin)
            ->patch(route('admin.users.update-role', $user), ['role' => 'staff'])
            ->assertSessionHas('success');

        $this->assertTrue($user->fresh()->hasRole('staff'));
        $this->assertFalse($user->fresh()->hasRole('admin'));
    }

    public function test_admin_cannot_update_user_role(): void
    {
        $admin = User::factory()->create();
        $user = User::factory()->create();

        Role::findOrCreate('admin', 'web');
        Role::findOrCreate('staff', 'web');

        $admin->assignRole('admin');
        $user->assignRole('admin');

        $this
            ->actingAs($admin)
            ->patch(route('admin.users.update-role', $user), ['role' => 'staff'])
            ->assertForbidden();

        $this->assertTrue($user->fresh()->hasRole('admin'));
        $this->assertFalse($user->fresh()->hasRole('staff'));
    }

    public function test_super_admin_cannot_demote_self(): void
    {
        $superAdmin = User::factory()->create();

        Role::findOrCreate('super-admin', 'web');
        Role::findOrCreate('staff', 'web');

        $superAdmin->assignRole('super-admin');

        $this
            ->actingAs($superAdmin)
            ->patch(route('admin.users.update-role', $superAdmin), ['role' => 'staff'])
            ->assertSessionHas('error');

        $this->assertTrue($superAdmin->fresh()->hasRole('super-admin'));
        $this->assertFalse($superAdmin->fresh()->hasRole('staff'));
    }

    public function test_super_admin_can_remove_admin_access(): void
    {
        $superAdmin = User::factory()->create();
        $admin = User::factory()->create();

        Role::findOrCreate('super-admin', 'web');
        Role::findOrCreate('admin', 'web');
        Role::findOrCreate('staff', 'web');

        $superAdmin->assignRole('super-admin');
        $admin->assignRole('admin');

        $this
            ->actingAs($superAdmin)
            ->patch(route('admin.users.remove-admin', $admin))
            ->assertSessionHas('success');

        $this->assertFalse($admin->fresh()->hasRole('admin'));
        $this->assertTrue($admin->fresh()->hasRole('staff'));
    }

    public function test_super_admin_can_deactivate_and_reactivate_user(): void
    {
        $superAdmin = User::factory()->create();
        $user = User::factory()->create();

        Role::findOrCreate('super-admin', 'web');
        $superAdmin->assignRole('super-admin');

        $this
            ->actingAs($superAdmin)
            ->patch(route('admin.users.deactivate', $user))
            ->assertSessionHas('success');

        $this->assertNotNull($user->fresh()->deactivated_at);

        $this
            ->actingAs($superAdmin)
            ->patch(route('admin.users.reactivate', $user))
            ->assertSessionHas('success');

        $this->assertNull($user->fresh()->deactivated_at);
    }

    public function test_super_admin_cannot_deactivate_self(): void
    {
        $superAdmin = User::factory()->create();

        Role::findOrCreate('super-admin', 'web');
        $superAdmin->assignRole('super-admin');

        $this
            ->actingAs($superAdmin)
            ->patch(route('admin.users.deactivate', $superAdmin))
            ->assertSessionHas('error');

        $this->assertNull($superAdmin->fresh()->deactivated_at);
    }
}
