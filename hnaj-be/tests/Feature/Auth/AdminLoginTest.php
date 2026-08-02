<?php

namespace Tests\Feature\Auth;

use App\Enums\RoleName;
use App\Enums\UserStatus;
use Illuminate\Support\Facades\Hash;

class AdminLoginTest extends AuthTestCase
{
    public function test_admin_can_sign_in_through_the_admin_endpoint(): void
    {
        $this->createUserWithRole(RoleName::Admin, [
            'username' => 'quan.admin',
            'password' => Hash::make('quantri2026'),
        ]);

        $this->postJson('/api/admin/auth/login', [
            'username' => 'quan.admin',
            'password' => 'quantri2026',
        ])
            ->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'user' => [
                        'username' => 'quan.admin',
                        'roles' => [RoleName::Admin->value],
                    ],
                ],
            ])
            ->assertJsonStructure(['data' => ['token']]);
    }

    public function test_regular_user_cannot_sign_in_through_the_admin_endpoint(): void
    {
        $this->createUserWithRole(RoleName::User, [
            'username' => 'thu.nguyen',
            'password' => Hash::make('canhcam2026'),
        ]);

        $this->postJson('/api/admin/auth/login', [
            'username' => 'thu.nguyen',
            'password' => 'canhcam2026',
        ])
            ->assertStatus(403)
            ->assertJson(['success' => false, 'code' => 'FORBIDDEN_ROLE']);

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_sub_admin_cannot_sign_in_through_the_admin_endpoint(): void
    {
        $this->createUserWithRole(RoleName::SubAdmin, [
            'username' => 'chu.quan',
            'password' => Hash::make('quanly2026'),
        ]);

        $this->postJson('/api/admin/auth/login', [
            'username' => 'chu.quan',
            'password' => 'quanly2026',
        ])
            ->assertStatus(403)
            ->assertJson(['success' => false, 'code' => 'FORBIDDEN_ROLE']);
    }

    public function test_admin_endpoint_rejects_wrong_password_before_checking_role(): void
    {
        $this->createUserWithRole(RoleName::Admin, [
            'username' => 'quan.admin',
            'password' => Hash::make('quantri2026'),
        ]);

        $this->postJson('/api/admin/auth/login', [
            'username' => 'quan.admin',
            'password' => 'sai-mat-khau',
        ])
            ->assertStatus(401)
            ->assertJson(['success' => false, 'code' => 'INVALID_CREDENTIALS']);
    }

    public function test_suspended_admin_cannot_sign_in(): void
    {
        $this->createUserWithRole(RoleName::Admin, [
            'username' => 'quan.admin',
            'password' => Hash::make('quantri2026'),
            'status' => UserStatus::Suspended,
        ]);

        $this->postJson('/api/admin/auth/login', [
            'username' => 'quan.admin',
            'password' => 'quantri2026',
        ])
            ->assertStatus(403)
            ->assertJson(['success' => false, 'code' => 'ACCOUNT_NOT_ACTIVE']);
    }

    public function test_admin_login_requires_username_and_password(): void
    {
        $this->postJson('/api/admin/auth/login', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['username', 'password']);
    }
}
