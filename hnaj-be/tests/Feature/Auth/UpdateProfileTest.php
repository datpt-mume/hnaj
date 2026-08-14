<?php

namespace Tests\Feature\Auth;

use App\Enums\RoleName;

class UpdateProfileTest extends AuthTestCase
{
    public function test_user_can_update_full_name(): void
    {
        $user = $this->createUserWithRole(RoleName::User);

        $this->actingAs($user, 'sanctum')
            ->patchJson('/api/auth/me', [
                'full_name' => '  Tên Mới Đã Cập Nhật  ',
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.full_name', 'Tên Mới Đã Cập Nhật')
            ->assertJsonPath('data.user.username', $user->username)
            ->assertJsonPath('data.user.email', $user->email);

        $this->assertSame('Tên Mới Đã Cập Nhật', $user->refresh()->name);
    }

    public function test_read_only_fields_are_not_changed_by_update(): void
    {
        $user = $this->createUserWithRole(RoleName::User);
        $originalAvatarUrl = $user->avatar_url;

        $this->actingAs($user, 'sanctum')
            ->patchJson('/api/auth/me', [
                'full_name' => 'Tên Mới',
                'username' => 'username-moi-khong-hop-le!!!',
                'email' => 'khong-hop-le-email@x',
                'avatar_url' => 'https://example.com/avatar-moi.jpg',
            ])
            ->assertOk();

        $user->refresh();

        $this->assertSame('Tên Mới', $user->name);
        $this->assertNotSame('username-moi-khong-hop-le!!!', $user->username);
        $this->assertNotSame('khong-hop-le-email@x', $user->email);
        $this->assertSame($originalAvatarUrl, $user->avatar_url);
    }

    public function test_full_name_is_required(): void
    {
        $user = $this->createUserWithRole(RoleName::User);

        $this->actingAs($user, 'sanctum')
            ->patchJson('/api/auth/me', [])
            ->assertUnprocessable()
            ->assertJsonPath('success', false);
    }

    public function test_unauthenticated_request_is_rejected(): void
    {
        $this->patchJson('/api/auth/me', [
            'full_name' => 'Tên Mới',
        ])
            ->assertStatus(401);
    }

    public function test_sub_admin_can_update_own_profile(): void
    {
        $user = $this->createUserWithRole(RoleName::SubAdmin);

        $this->actingAs($user, 'sanctum')
            ->patchJson('/api/auth/me', [
                'full_name' => 'Sub Admin Mới',
            ])
            ->assertOk()
            ->assertJsonPath('data.user.full_name', 'Sub Admin Mới');
    }

    public function test_admin_cannot_use_user_profile_endpoint(): void
    {
        $admin = $this->createUserWithRole(RoleName::Admin);

        $this->actingAs($admin, 'sanctum')
            ->patchJson('/api/auth/me', [
                'full_name' => 'Admin Không Hợp Lệ',
            ])
            ->assertStatus(403);
    }

    public function test_response_uses_success_envelope(): void
    {
        $user = $this->createUserWithRole(RoleName::User);

        $response = $this->actingAs($user, 'sanctum')
            ->patchJson('/api/auth/me', [
                'full_name' => 'Tên Envelope',
            ]);

        $response->assertOk();
        $response->assertJsonStructure([
            'success',
            'message',
            'data' => ['user' => ['id', 'full_name', 'username', 'email', 'roles']],
        ]);
    }
}