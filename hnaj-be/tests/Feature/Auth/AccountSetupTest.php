<?php

namespace Tests\Feature\Auth;

use App\Enums\RoleName;
use App\Models\AccountSetupToken;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AccountSetupTest extends AuthTestCase
{
    private function createPendingSubAdmin(): array
    {
        $user = $this->createUserWithRole(RoleName::SubAdmin, [
            'email_verified_at' => null,
        ]);

        $plainToken = Str::random(64);
        AccountSetupToken::query()->create([
            'user_id' => $user->id,
            'token_hash' => hash('sha256', $plainToken),
            'expires_at' => Carbon::now()->addHours(24),
        ]);

        return [$user, $plainToken];
    }

    public function test_account_setup_sets_password_verifies_email_and_marks_token_used(): void
    {
        [$user, $plainToken] = $this->createPendingSubAdmin();

        $this->postJson('/api/auth/account/setup', [
            'token' => $plainToken,
            'password' => 'NewPassword123',
            'password_confirmation' => 'NewPassword123',
        ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $user->refresh();

        $this->assertNotNull($user->email_verified_at);
        $this->assertTrue(Hash::check('NewPassword123', $user->password));
        $this->assertNotNull(
            AccountSetupToken::query()->where('user_id', $user->id)->first()->used_at,
        );
    }

    public function test_account_setup_rejects_reused_token(): void
    {
        [$user, $plainToken] = $this->createPendingSubAdmin();

        $this->postJson('/api/auth/account/setup', [
            'token' => $plainToken,
            'password' => 'NewPassword123',
            'password_confirmation' => 'NewPassword123',
        ])->assertOk();

        $this->postJson('/api/auth/account/setup', [
            'token' => $plainToken,
            'password' => 'AnotherPass123',
            'password_confirmation' => 'AnotherPass123',
        ])
            ->assertUnprocessable();
    }

    public function test_account_setup_rejects_expired_token(): void
    {
        [$user, $plainToken] = $this->createPendingSubAdmin();

        AccountSetupToken::query()
            ->where('user_id', $user->id)
            ->update(['expires_at' => Carbon::now()->subMinute()]);

        $this->postJson('/api/auth/account/setup', [
            'token' => $plainToken,
            'password' => 'NewPassword123',
            'password_confirmation' => 'NewPassword123',
        ])
            ->assertUnprocessable();
    }

    public function test_sub_admin_can_login_via_user_login_endpoint(): void
    {
        [$user, $plainToken] = $this->createPendingSubAdmin();

        $this->postJson('/api/auth/account/setup', [
            'token' => $plainToken,
            'password' => 'NewPassword123',
            'password_confirmation' => 'NewPassword123',
        ])->assertOk();

        $this->postJson('/api/auth/login', [
            'username' => $user->username,
            'password' => 'NewPassword123',
        ])
            ->assertOk()
            ->assertJsonPath('success', true);
    }
}
