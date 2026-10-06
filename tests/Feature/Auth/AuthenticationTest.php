<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Tests\Concerns\UsesIsolatedUserDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use UsesIsolatedUserDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/login', [
            'username' => $user->username,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('public.dashboard'));
    }

    public function test_login_returns_to_the_selected_year_on_year_dashboard(): void
    {
        $user = User::factory()->create();

        $this->get(route('public.dashboard-yoy'))->assertRedirect(route('login'));

        $response = $this->post('/login', [
            'username' => $user->username,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('public.dashboard-yoy'));
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'username' => $user->username,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/');
    }
}
