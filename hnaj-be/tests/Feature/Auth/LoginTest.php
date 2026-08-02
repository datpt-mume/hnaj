<?php

namespace Tests\Feature\Auth;

use App\Enums\RoleName;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class LoginTest extends AuthTestCase
{
    private const PASSWORD = 'canhcam2026';

    private function activeUser(array $attributes = []): User
    {
        return $this->createUserWithRole(RoleName::User, array_merge([
            'username' => 'thu.nguyen',
            'password' => Hash::make(self::PASSWORD),
        ], $attributes));
    }

    public function test_login_returns_a_bearer_token_and_the_user_with_roles(): void
    {
        $user = $this->activeUser();

        $response = $this->postJson('/api/auth/login', [
            'username' => 'thu.nguyen',
            'password' => self::PASSWORD,
        ]);

        $response
            ->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'user' => [
                        'username' => 'thu.nguyen',
                        'email_verified' => true,
                        'roles' => [RoleName::User->value],
                    ],
                ],
            ])
            ->assertJsonStructure(['data' => ['token', 'user' => ['id', 'username', 'roles']]]);

        $this->assertNotEmpty($response->json('data.token'), 'Login phải trả bearer token.');
        $this->assertDatabaseCount('personal_access_tokens', 1);
    }

    public function test_login_response_never_exposes_the_password_hash(): void
    {
        $this->activeUser();

        $response = $this->postJson('/api/auth/login', [
            'username' => 'thu.nguyen',
            'password' => self::PASSWORD,
        ])->assertOk();

        $this->assertArrayNotHasKey('password', $response->json('data.user'));
        $this->assertStringNotContainsString('$2y$', $response->getContent());
    }

    public function test_admin_cannot_sign_in_through_the_regular_user_endpoint(): void
    {
        $this->createUserWithRole(RoleName::Admin, [
            'username' => 'admin.only',
            'password' => Hash::make(self::PASSWORD),
        ]);

        $this->postJson('/api/auth/login', [
            'username' => 'admin.only',
            'password' => self::PASSWORD,
        ])->assertForbidden()
            ->assertJson([
                'success' => false,
                'code' => 'FORBIDDEN_ROLE',
            ]);
    }

    public function test_login_is_case_insensitive_for_the_username(): void
    {
        $this->activeUser();

        $this->postJson('/api/auth/login', [
            'username' => 'Thu.Nguyen',
            'password' => self::PASSWORD,
        ])->assertOk();
    }

    public function test_login_rejects_a_wrong_password(): void
    {
        $this->activeUser();

        $this->postJson('/api/auth/login', [
            'username' => 'thu.nguyen',
            'password' => 'sai-mat-khau',
        ])
            ->assertStatus(401)
            ->assertJson(['success' => false, 'code' => 'INVALID_CREDENTIALS']);

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_login_rejects_an_unknown_username_with_the_same_code(): void
    {
        $this->postJson('/api/auth/login', [
            'username' => 'khong.ton.tai',
            'password' => self::PASSWORD,
        ])
            ->assertStatus(401)
            ->assertJson(['success' => false, 'code' => 'INVALID_CREDENTIALS']);
    }

    public function test_login_is_blocked_until_the_email_is_verified(): void
    {
        $this->activeUser(['email_verified_at' => null]);

        $this->postJson('/api/auth/login', [
            'username' => 'thu.nguyen',
            'password' => self::PASSWORD,
        ])
            ->assertStatus(403)
            ->assertJson(['success' => false, 'code' => 'EMAIL_NOT_VERIFIED']);

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_login_is_blocked_for_a_suspended_account(): void
    {
        $this->activeUser(['status' => UserStatus::Suspended]);

        $this->postJson('/api/auth/login', [
            'username' => 'thu.nguyen',
            'password' => self::PASSWORD,
        ])
            ->assertStatus(403)
            ->assertJson(['success' => false, 'code' => 'ACCOUNT_NOT_ACTIVE']);
    }

    public function test_login_requires_username_and_password(): void
    {
        $this->postJson('/api/auth/login', [])
            ->assertStatus(422)
            ->assertJson(['code' => 'VALIDATION_ERROR'])
            ->assertJsonValidationErrors(['username', 'password']);
    }

    public function test_me_returns_the_authenticated_user(): void
    {
        $user = $this->activeUser();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/auth/me')
            ->assertOk()
            ->assertJson([
                'success' => true,
                'data' => ['user' => ['username' => 'thu.nguyen', 'roles' => [RoleName::User->value]]],
            ]);
    }

    public function test_me_rejects_an_unauthenticated_request_with_the_error_envelope(): void
    {
        $this->getJson('/api/auth/me')
            ->assertStatus(401)
            ->assertJson(['success' => false, 'code' => 'UNAUTHENTICATED']);
    }

    public function test_logout_revokes_only_the_current_token(): void
    {
        $user = $this->activeUser();

        $firstToken = $this->postJson('/api/auth/login', [
            'username' => 'thu.nguyen',
            'password' => self::PASSWORD,
        ])->json('data.token');

        $secondToken = $this->postJson('/api/auth/login', [
            'username' => 'thu.nguyen',
            'password' => self::PASSWORD,
        ])->json('data.token');

        $this->assertDatabaseCount('personal_access_tokens', 2);

        $this->withHeader('Authorization', 'Bearer '.$firstToken)
            ->postJson('/api/auth/logout')
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseCount('personal_access_tokens', 1);

        // Token đã thu hồi không dùng được nữa.
        // Guard của Sanctum cache user đã resolve trong cùng process test, trong khi mỗi
        // request HTTP thật là một process riêng. Reset guard để mô phỏng request mới.
        $this->app['auth']->forgetGuards();

        $this->withHeader('Authorization', 'Bearer '.$firstToken)
            ->getJson('/api/auth/me')
            ->assertStatus(401);

        // Phiên đăng nhập khác của cùng tài khoản vẫn hoạt động.
        $this->withHeader('Authorization', 'Bearer '.$secondToken)
            ->getJson('/api/auth/me')
            ->assertOk();
    }

    public function test_logout_requires_authentication(): void
    {
        $this->postJson('/api/auth/logout')
            ->assertStatus(401)
            ->assertJson(['success' => false, 'code' => 'UNAUTHENTICATED']);
    }
}
