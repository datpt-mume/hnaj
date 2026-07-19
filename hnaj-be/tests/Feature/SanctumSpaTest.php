<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class SanctumSpaTest extends TestCase
{
    use RefreshDatabase;

    public function test_csrf_cookie_endpoint_returns_no_content_and_cookie(): void
    {
        $response = $this->get('/api/v1/auth/csrf-cookie');

        $response->assertNoContent();
        $response->assertCookie('XSRF-TOKEN');
    }

    public function test_admin_api_remains_protected_for_guests(): void
    {
        $this->postJson('/api/v1/admin/imports/places/preview')
            ->assertUnauthorized();
    }

    public function test_user_can_register_and_read_current_session(): void
    {
        $this->postJson('/api/v1/auth/register', [
            'name' => 'New User',
            'email' => 'new@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertCreated()->assertJsonPath('data.email', 'new@example.com');

        $this->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('data.name', 'New User');
    }

    public function test_user_can_login_and_logout(): void
    {
        $user = User::factory()->create(['email' => 'login@example.com', 'password' => 'password123']);

        $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'password123',
        ])->assertOk()->assertJsonPath('data.id', $user->id);

        $this->postJson('/api/v1/auth/logout')
            ->assertNoContent()
            ->assertCookie(config('session.cookie'), null, false);

        $this->assertGuest('web');
    }
}
