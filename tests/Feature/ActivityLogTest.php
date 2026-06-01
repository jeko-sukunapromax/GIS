<?php

namespace Tests\Feature;

use App\Models\Barangay;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ActivityLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_activity_logs(): void
    {
        $admin = $this->userWithRole('admin');

        activity('audit')
            ->causedBy($admin)
            ->event('upload.processed')
            ->withProperty('ip_address', '127.0.0.1')
            ->log('Processed upload Alinggan.geojson.');

        $this
            ->actingAs($admin)
            ->get(route('admin.activity-logs.index'))
            ->assertOk()
            ->assertSee('Activity Logs')
            ->assertSee('Processed upload Alinggan.geojson.');
    }

    public function test_visibility_change_is_logged(): void
    {
        $admin = $this->userWithRole('admin');
        $barangay = Barangay::create([
            'name' => 'Alinggan',
            'is_visible' => true,
        ]);

        $this
            ->actingAs($admin)
            ->post(route('admin.barangays.toggle-visibility', $barangay))
            ->assertOk();

        $this->assertDatabaseHas('activity_log', [
            'event' => 'barangay.visibility_changed',
            'subject_id' => $barangay->id,
        ]);
    }

    public function test_ihris_login_is_logged(): void
    {
        Http::fake([
            'https://testihris.bayambang.gov.ph/api/login' => Http::response([
                'user' => [
                    'name' => 'BDRRMC Admin',
                    'email' => 'admin@example.com',
                    'office' => 'BDRRMC Office',
                ],
                'token' => 'test-token',
            ]),
        ]);

        $this->post('/login', [
            'email' => 'admin@example.com',
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $this->assertDatabaseHas('activity_log', [
            'event' => 'user.logged_in',
            'description' => 'BDRRMC Admin logged in through iHRIS.',
        ]);
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();

        Role::findOrCreate($role, 'web');
        $user->assignRole($role);

        return $user;
    }
}
