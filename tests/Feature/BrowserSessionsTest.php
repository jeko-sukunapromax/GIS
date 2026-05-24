<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Jetstream\Http\Livewire\LogoutOtherBrowserSessionsForm;
use Livewire\Livewire;
use Tests\TestCase;

class BrowserSessionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_other_browser_sessions_can_be_logged_out(): void
    {
        $user = User::factory()->create();

        Http::fake([
            'https://testihris.bayambang.gov.ph/api/login' => Http::response([
                'user' => [
                    'name' => $user->name,
                    'email' => $user->email,
                    'office' => 'BDRRMC Office',
                ],
            ]),
        ]);

        $this->actingAs($user);

        Livewire::test(LogoutOtherBrowserSessionsForm::class)
            ->set('password', 'password')
            ->call('logoutOtherBrowserSessions')
            ->assertSuccessful();
    }
}
