<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        Http::fake([
            $this->ihrisLoginUrl() => Http::response([
                'user' => [
                    'name' => 'BDRRMC Admin',
                    'email' => 'admin@example.com',
                    'office' => 'BDRRMC Office',
                ],
                'token' => 'test-token',
            ]),
        ]);

        $response = $this->post('/login', [
            'email' => 'admin@example.com',
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect('/admin/barangays');
        $this->assertTrue(User::where('email', 'admin@example.com')->first()->hasRole('admin'));
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        Http::fake([
            $this->ihrisLoginUrl() => Http::response([], 401),
        ]);

        $this->post('/login', [
            'email' => 'admin@example.com',
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_ihris_connection_timeout_does_not_crash_login(): void
    {
        Http::fake([
            $this->ihrisLoginUrl() => fn () => throw new ConnectionException('Connection timed out.'),
        ]);

        $response = $this->post('/login', [
            'email' => 'admin@example.com',
            'password' => 'password',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors();
    }

    public function test_non_bdrrmc_users_can_not_authenticate(): void
    {
        Http::fake([
            $this->ihrisLoginUrl() => Http::response([
                'user' => [
                    'name' => 'Other Office User',
                    'email' => 'other@example.com',
                    'office' => 'Mayor Office',
                ],
            ]),
        ]);

        $this->post('/login', [
            'email' => 'other@example.com',
            'password' => 'password',
        ]);

        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email' => 'other@example.com']);
    }

    public function test_configured_admin_override_email_can_authenticate_from_another_office(): void
    {
        config(['services.ihris.admin_override_emails' => ['other@example.com']]);

        Http::fake([
            $this->ihrisLoginUrl() => Http::response([
                'user' => [
                    'name' => 'Other Office User',
                    'email' => 'other@example.com',
                    'office' => 'Mayor Office',
                ],
                'token' => 'test-token',
            ]),
        ]);

        $response = $this->post('/login', [
            'email' => 'other@example.com',
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect('/admin/barangays');

        $user = User::where('email', 'other@example.com')->first();

        $this->assertNotNull($user);
        $this->assertSame('Mayor Office', $user->office);
        $this->assertTrue($user->hasRole('admin'));
    }

    public function test_configured_super_admin_email_gets_super_admin_role(): void
    {
        config([
            'services.ihris.admin_override_emails' => ['villamorjerichoivan@gmail.com'],
            'services.ihris.super_admin_emails' => ['villamorjerichoivan@gmail.com'],
        ]);

        Http::fake([
            $this->ihrisLoginUrl() => Http::response([
                'user' => [
                    'name' => 'Jericho Villamor',
                    'email' => 'villamorjerichoivan@gmail.com',
                    'office' => 'Mayor Office',
                ],
                'token' => 'test-token',
            ]),
        ]);

        $response = $this->post('/login', [
            'email' => 'villamorjerichoivan@gmail.com',
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect('/admin/barangays');

        $user = User::where('email', 'villamorjerichoivan@gmail.com')->first();

        $this->assertNotNull($user);
        $this->assertTrue($user->hasRole('super-admin'));
        $this->assertFalse($user->hasRole('admin'));
    }

    public function test_existing_staff_role_is_preserved_on_ihris_login(): void
    {
        Role::findOrCreate('staff', 'web');

        User::factory()->create([
            'name' => 'BDRRMC Staff',
            'email' => 'staff@example.com',
        ])->assignRole('staff');

        Http::fake([
            $this->ihrisLoginUrl() => Http::response([
                'user' => [
                    'name' => 'BDRRMC Staff',
                    'email' => 'staff@example.com',
                    'office' => 'BDRRMC Office',
                ],
                'token' => 'test-token',
            ]),
        ]);

        $this->post('/login', [
            'email' => 'staff@example.com',
            'password' => 'password',
        ]);

        $user = User::where('email', 'staff@example.com')->first();

        $this->assertAuthenticated();
        $this->assertTrue($user->hasRole('staff'));
        $this->assertFalse($user->hasRole('admin'));
    }

    public function test_deactivated_user_can_not_authenticate(): void
    {
        User::factory()->create([
            'name' => 'Deactivated User',
            'email' => 'inactive@example.com',
            'deactivated_at' => now(),
        ]);

        Http::fake([
            $this->ihrisLoginUrl() => Http::response([
                'user' => [
                    'name' => 'Deactivated User',
                    'email' => 'inactive@example.com',
                    'office' => 'BDRRMC Office',
                ],
                'token' => 'test-token',
            ]),
        ]);

        $this->post('/login', [
            'email' => 'inactive@example.com',
            'password' => 'password',
        ]);

        $this->assertGuest();
    }
}
