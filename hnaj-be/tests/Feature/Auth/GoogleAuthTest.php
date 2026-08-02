<?php

namespace Tests\Feature\Auth;

use App\Actions\Auth\HandleGoogleCallback;
use App\Actions\Auth\RedirectToGoogle;
use App\Enums\RoleName;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class GoogleAuthTest extends AuthTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'app.frontend_url' => 'http://localhost:8082',
            'services.google.client_id' => 'test-client-id',
            'services.google.client_secret' => 'test-client-secret',
            'services.google.redirect_uri' => 'http://localhost/api/auth/google/callback',
        ]);
    }

    public function test_redirect_endpoint_returns_google_authorization_url(): void
    {
        $this->withCredentials()
            ->getJson('/api/auth/google/redirect')
            ->assertOk()
            ->assertJsonPath('data.authorization_url', function ($value) {
                return str_contains($value, 'https://accounts.google.com/o/oauth2/v2/auth');
            })
            ->assertCookie(RedirectToGoogle::FLOW_COOKIE);
    }

    public function test_redirect_endpoint_reports_missing_google_configuration(): void
    {
        config(['services.google.client_id' => null]);

        $this->withCredentials()
            ->getJson('/api/auth/google/redirect')
            ->assertStatus(422)
            ->assertJson(['success' => false, 'code' => 'GOOGLE_AUTH_FAILED']);
    }

    public function test_callback_creates_verified_user_with_generated_username_then_redirects(): void
    {
        [$state, $flowCookie] = $this->createGoogleFlow();
        $this->fakeGoogleProfile('google-user-123', 'thu.nguyen@example.com');

        $this->withUnencryptedCookie(RedirectToGoogle::FLOW_COOKIE, $flowCookie)
            ->get('/api/auth/google/callback?code=oauth-code&state='.$state)
            ->assertRedirect();

        $user = User::query()
            ->where('google_id', 'google-user-123')
            ->first();

        $this->assertNotNull($user);
        $this->assertSame('thu.nguyen@example.com', $user->email);
        $this->assertNotNull($user->email_verified_at);
        $this->assertStringContainsString('thu.nguyen_', $user->username);
    }

    public function test_generated_username_contains_random_suffix(): void
    {
        [$state, $flowCookie] = $this->createGoogleFlow();

        $this->fakeGoogleProfile('google-user-456', 'random.user@example.com');

        $this->withUnencryptedCookie(RedirectToGoogle::FLOW_COOKIE, $flowCookie)
            ->get('/api/auth/google/callback?code=oauth-code&state='.$state)
            ->assertRedirect();

        $user = User::query()
            ->where('google_id', 'google-user-456')
            ->first();

        $this->assertNotNull($user);
        // Username format: {local_part}_{6 random lowercase chars}.
        $this->assertMatchesRegularExpression('/^random\.user_[a-z0-9]{6}$/', $user->username);
    }

    public function test_google_login_links_an_existing_email_instead_of_creating_a_duplicate(): void
    {
        $existing = $this->createUserWithRole(RoleName::User, [
            'username' => 'thu.local',
            'email' => 'thu.nguyen@example.com',
            'email_verified_at' => null,
        ]);

        [$state, $flowCookie] = $this->createGoogleFlow();
        $this->fakeGoogleProfile('google-user-789', 'thu.nguyen@example.com');

        $this->withUnencryptedCookie(RedirectToGoogle::FLOW_COOKIE, $flowCookie)
            ->get('/api/auth/google/callback?code=oauth-code&state='.$state)
            ->assertRedirect();

        $this->assertDatabaseCount('users', 1);
        $this->assertSame('google-user-789', $existing->refresh()->google_id);
        $this->assertNotNull($existing->email_verified_at);
    }

    public function test_google_login_does_not_link_an_admin_account(): void
    {
        $this->createUserWithRole(RoleName::Admin, [
            'username' => 'admin.google',
            'email' => 'admin@example.com',
        ]);

        [$state, $flowCookie] = $this->createGoogleFlow();
        $this->fakeGoogleProfile('google-admin-123', 'admin@example.com');

        $this->withUnencryptedCookie(RedirectToGoogle::FLOW_COOKIE, $flowCookie)
            ->get('/api/auth/google/callback?code=oauth-code&state='.$state)
            ->assertRedirect('http://localhost:8082/auth/google/callback?error=FORBIDDEN_ROLE');

        $this->assertDatabaseMissing('users', ['google_id' => 'google-admin-123']);
    }

    public function test_google_login_rejects_linking_a_second_google_identity(): void
    {
        $this->createUserWithRole(RoleName::User, [
            'username' => 'linked.user',
            'email' => 'linked@example.com',
            'google_id' => 'google-existing-123',
        ]);

        [$state, $flowCookie] = $this->createGoogleFlow();
        $this->fakeGoogleProfile('google-other-456', 'linked@example.com');

        $this->withUnencryptedCookie(RedirectToGoogle::FLOW_COOKIE, $flowCookie)
            ->get('/api/auth/google/callback?code=oauth-code&state='.$state)
            ->assertRedirect('http://localhost:8082/auth/google/callback?error=GOOGLE_AUTH_FAILED');

        $this->assertDatabaseMissing('users', ['google_id' => 'google-other-456']);
    }

    public function test_provider_cancellation_redirects_to_the_spa_and_clears_the_flow_cookie(): void
    {
        [$state, $flowCookie] = $this->createGoogleFlow();

        $this->withUnencryptedCookie(RedirectToGoogle::FLOW_COOKIE, $flowCookie)
            ->get('/api/auth/google/callback?error=access_denied&error_description=Cancelled&state='.$state)
            ->assertRedirect('http://localhost:8082/auth/google/callback?error=GOOGLE_AUTH_FAILED')
            ->assertCookieExpired(RedirectToGoogle::FLOW_COOKIE);

        Http::assertNothingSent();
    }

    public function test_callback_rejects_an_invalid_or_reused_state(): void
    {
        $this->get('/api/auth/google/callback?code=oauth-code&state=invalid-state')
            ->assertRedirect('http://localhost:8082/auth/google/callback?error=GOOGLE_AUTH_FAILED');

        Http::assertNothingSent();
    }

    public function test_callback_rejects_a_missing_or_mismatched_flow_cookie(): void
    {
        [$state] = $this->createGoogleFlow();

        $this->get('/api/auth/google/callback?code=oauth-code&state='.$state)
            ->assertRedirect('http://localhost:8082/auth/google/callback?error=GOOGLE_AUTH_FAILED');

        Http::assertNothingSent();

        [$state] = $this->createGoogleFlow();

        $this->withUnencryptedCookie(RedirectToGoogle::FLOW_COOKIE, 'wrong-cookie')
            ->get('/api/auth/google/callback?code=oauth-code&state='.$state)
            ->assertRedirect('http://localhost:8082/auth/google/callback?error=GOOGLE_AUTH_FAILED');

        Http::assertNothingSent();
    }

    public function test_callback_missing_state_redirects_to_spa_and_expires_cookie(): void
    {
        $this->get('/api/auth/google/callback?code=oauth-code')
            ->assertRedirect('http://localhost:8082/auth/google/callback?error=GOOGLE_AUTH_FAILED')
            ->assertCookieExpired(RedirectToGoogle::FLOW_COOKIE);

        Http::assertNothingSent();
    }

    public function test_callback_missing_both_code_and_error_redirects_to_spa_and_expires_cookie(): void
    {
        [$state] = $this->createGoogleFlow();

        $this->get('/api/auth/google/callback?state='.$state)
            ->assertRedirect('http://localhost:8082/auth/google/callback?error=GOOGLE_AUTH_FAILED')
            ->assertCookieExpired(RedirectToGoogle::FLOW_COOKIE);

        Http::assertNothingSent();
    }

    public function test_callback_with_both_code_and_error_redirects_to_spa_and_expires_cookie(): void
    {
        [$state] = $this->createGoogleFlow();

        $this->get('/api/auth/google/callback?code=oauth-code&error=access_denied&state='.$state)
            ->assertRedirect('http://localhost:8082/auth/google/callback?error=GOOGLE_AUTH_FAILED')
            ->assertCookieExpired(RedirectToGoogle::FLOW_COOKIE);

        Http::assertNothingSent();
    }

    public function test_google_link_is_atomic_both_fields_set_or_neither(): void
    {
        $existing = $this->createUserWithRole(RoleName::User, [
            'username' => 'atomic.user',
            'email' => 'atomic@example.com',
            'email_verified_at' => null,
        ]);

        [$state, $flowCookie] = $this->createGoogleFlow();
        $this->fakeGoogleProfile('google-atomic-123', 'atomic@example.com');

        $this->withUnencryptedCookie(RedirectToGoogle::FLOW_COOKIE, $flowCookie)
            ->get('/api/auth/google/callback?code=oauth-code&state='.$state)
            ->assertRedirect();

        // After a successful link, both google_id AND email_verified_at
        // must be set together — never a partial state.
        $existing->refresh();
        $this->assertSame('google-atomic-123', $existing->google_id);
        $this->assertNotNull($existing->email_verified_at);
        $this->assertSame([RoleName::User->value], $existing->roleNames());
    }

    public function test_google_exchange_code_is_single_use(): void
    {
        $user = $this->createUserWithRole(RoleName::User);
        $code = Str::random(64);
        $flowCookie = Str::random(64);
        Cache::put(HandleGoogleCallback::exchangeCacheKey($code), [
            'user_id' => $user->id,
            'flow_hash' => RedirectToGoogle::hashFlowCookie($flowCookie),
        ], 60);

        $this->withCredentials()
            ->withUnencryptedCookie(RedirectToGoogle::FLOW_COOKIE, $flowCookie)
            ->postJson('/api/auth/google/exchange', ['code' => $code])
            ->assertOk();

        $this->withCredentials()
            ->withUnencryptedCookie(RedirectToGoogle::FLOW_COOKIE, $flowCookie)
            ->postJson('/api/auth/google/exchange', ['code' => $code])
            ->assertStatus(422)
            ->assertJson(['success' => false, 'code' => 'GOOGLE_AUTH_FAILED']);
    }

    public function test_google_exchange_rechecks_the_current_user_role_before_issuing_a_token(): void
    {
        $user = $this->createUserWithRole(RoleName::User);
        $code = Str::random(64);
        $flowCookie = Str::random(64);

        Cache::put(HandleGoogleCallback::exchangeCacheKey($code), [
            'user_id' => $user->id,
            'flow_hash' => RedirectToGoogle::hashFlowCookie($flowCookie),
        ], 60);

        $user->roles()->detach();

        $this->withCredentials()
            ->withUnencryptedCookie(RedirectToGoogle::FLOW_COOKIE, $flowCookie)
            ->postJson('/api/auth/google/exchange', ['code' => $code])
            ->assertForbidden()
            ->assertJson(['success' => false, 'code' => 'FORBIDDEN_ROLE']);

        $this->assertDatabaseCount('personal_access_tokens', 0);

        $this->withCredentials()
            ->withUnencryptedCookie(RedirectToGoogle::FLOW_COOKIE, $flowCookie)
            ->postJson('/api/auth/google/exchange', ['code' => $code])
            ->assertStatus(422)
            ->assertJson(['success' => false, 'code' => 'GOOGLE_AUTH_FAILED']);
    }

    public function test_google_exchange_rejects_a_code_from_another_browser_flow(): void
    {
        $user = $this->createUserWithRole(RoleName::User);
        $code = Str::random(64);
        $flowCookie = Str::random(64);

        Cache::put(HandleGoogleCallback::exchangeCacheKey($code), [
            'user_id' => $user->id,
            'flow_hash' => RedirectToGoogle::hashFlowCookie($flowCookie),
        ], 60);

        $this->withCredentials()
            ->withUnencryptedCookie(RedirectToGoogle::FLOW_COOKIE, 'another-browser-cookie')
            ->postJson('/api/auth/google/exchange', ['code' => $code])
            ->assertStatus(422)
            ->assertJson(['success' => false, 'code' => 'GOOGLE_AUTH_FAILED']);

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_google_userinfo_must_confirm_the_email_is_verified(): void
    {
        [$state, $flowCookie] = $this->createGoogleFlow();

        Http::fake([
            'https://oauth2.googleapis.com/token' => Http::response(['access_token' => 'token']),
            'https://www.googleapis.com/oauth2/v3/userinfo' => Http::response([
                'sub' => 'google-unverified',
                'email' => 'unverified@example.com',
                'email_verified' => false,
                'name' => 'Unverified User',
            ]),
        ]);

        $this->withUnencryptedCookie(RedirectToGoogle::FLOW_COOKIE, $flowCookie)
            ->get('/api/auth/google/callback?code=oauth-code&state='.$state)
            ->assertRedirect('http://localhost:8082/auth/google/callback?error=GOOGLE_AUTH_FAILED');

        $this->assertDatabaseMissing('users', ['email' => 'unverified@example.com']);
    }

    private function fakeGoogleProfile(string $googleId, string $email): void
    {
        Http::fake([
            'https://oauth2.googleapis.com/token' => Http::response([
                'access_token' => 'google-access-token',
            ]),
            'https://www.googleapis.com/oauth2/v3/userinfo' => Http::response([
                'sub' => $googleId,
                'email' => $email,
                'email_verified' => true,
                'name' => 'Nguyen Minh Thu',
            ]),
        ]);
    }

    /**
     * @return array{string, string}
     */
    private function createGoogleFlow(): array
    {
        $state = Str::random(40);
        $flowCookie = Str::random(64);

        Cache::put(RedirectToGoogle::cacheKey($state), [
            'flow_hash' => RedirectToGoogle::hashFlowCookie($flowCookie),
        ], 300);

        return [$state, $flowCookie];
    }
}
